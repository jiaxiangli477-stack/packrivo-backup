#!/usr/bin/env python3
"""Stage the combined R1 release only after a verified backup and maintenance gate."""
import argparse,hashlib,importlib.util,json,os,re,stat,subprocess,sys,zipfile
from pathlib import Path,PurePosixPath
LANGS=('ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW')
PACKS=[('packrivo-home-v4-4.0.7.zip','packrivo-home-v4','themes'),('packrivo-quote-1.0.2.zip','packrivo-quote','plugins'),('packrivo-language-selector-1.0.10.zip','packrivo-language-selector','plugins')]
def sha(path):
 h=hashlib.sha256()
 with path.open('rb') as f:
  for data in iter(lambda:f.read(1024*1024),b''):h.update(data)
 return h.hexdigest()
def safe_rel(value):
 p=PurePosixPath(value);return bool(value and not p.is_absolute() and '..' not in p.parts and not any(c in value for c in ('\\',':','\r','\n','\x00')) and not value.startswith('-'))
def verify_manifest(pkg):
 seen=set()
 for line in (pkg/'MANIFEST.sha256').read_text(encoding='utf8').splitlines():
  if not re.fullmatch(r'[0-9a-f]{64}  .+',line):raise ValueError('invalid manifest line')
  digest,path=line.split('  ',1)
  if not safe_rel(path) or path in seen:raise ValueError('unsafe or duplicate manifest path')
  f=pkg.joinpath(*PurePosixPath(path).parts)
  if not f.is_file() or f.is_symlink() or sha(f)!=digest:raise ValueError('manifest mismatch '+path)
  seen.add(path)
 actual={p.relative_to(pkg).as_posix() for p in pkg.rglob('*') if p.is_file() and p.relative_to(pkg).as_posix()!='MANIFEST.sha256'}
 if seen!=actual:raise ValueError('unlisted or missing package files')
 return len(seen)
def check_baseline(expected,actual,label):
 if expected.replace('\r\n','\n')!=actual.replace('\r\n','\n'):raise ValueError('baseline fingerprint mismatch '+label)

def extract_zip(archive,dest,expected):
 count=0
 with zipfile.ZipFile(archive) as z:
  if z.testzip() is not None:raise ValueError('corrupt ZIP')
  seen=set()
  for x in z.infolist():
   p=PurePosixPath(x.filename);mode=x.external_attr>>16
   if not safe_rel(x.filename) or p.parts[0]!=expected or x.filename in seen or stat.S_ISLNK(mode):raise ValueError('unsafe ZIP member')
   seen.add(x.filename);target=dest.joinpath(*p.parts)
   if x.is_dir():target.mkdir(parents=True,exist_ok=True)
   else:
    target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(z.read(x));count+=1
 return count
def dk(args):
 p=subprocess.run(['docker',*args],stdout=subprocess.PIPE,stderr=subprocess.PIPE)
 if p.returncode:raise RuntimeError('docker failure '+str(args[:3])+': '+p.stderr.decode('utf8',errors='replace')[-1000:])
 return p.stdout.decode('utf8',errors='replace')
def main():
 p=argparse.ArgumentParser();p.add_argument('--pkg',type=Path,required=True);p.add_argument('--stage',type=Path,required=True);p.add_argument('--wp-container',required=True);p.add_argument('--db-container',required=True);p.add_argument('--wp-root',required=True);p.add_argument('--db-name',required=True);p.add_argument('--backup-dir',type=Path,required=True);p.add_argument('--backup-sha256',required=True);p.add_argument('--workspace-backup',type=Path,required=True);p.add_argument('--workspace-sha256',required=True);p.add_argument('--manifest-sha256',required=True);a=p.parse_args()
 try:
  if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9_.-]*',a.wp_container) or not a.wp_root.startswith('/') or '..' in PurePosixPath(a.wp_root).parts or a.wp_root in ('/','/var','/var/www'):raise ValueError('invalid container or WP root')
  if sha(a.pkg/'MANIFEST.sha256')!=a.manifest_sha256 or verify_manifest(a.pkg)<1:raise ValueError('package manifest mismatch')
  if sha(a.backup_dir/'backup.json')!=a.backup_sha256:raise ValueError('backup manifest hash mismatch')
  spec=importlib.util.spec_from_file_location('r1snapshot',a.pkg/'r1-snapshot.py');module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
  snap=json.loads((a.backup_dir/'backup.json').read_text(encoding='utf8'));module.verify_snapshot(a.backup_dir,snap,a)
  if sha(a.workspace_backup)!=a.workspace_sha256 or len(a.workspace_backup.read_bytes().splitlines())<=1:raise ValueError('workspace backup mismatch or empty')
  dk(['exec',a.wp_container,'test','-f',a.wp_root+'/.maintenance'])
  for label,rel in module.ITEMS.items():
   expected=(a.pkg/'r1-baseline'/(label+'.sha256')).read_text(encoding='utf8')
   command=r'if [ -d "$1" ]; then cd "$1" && find . -type f -exec sha256sum {} \; | LC_ALL=C sort -k2; elif [ -f "$1" ]; then sha256sum "$1"; else echo MISSING; fi'
   actual=dk(['exec',a.wp_container,'sh','-c',command,'sh',a.wp_root+'/'+rel])
   if label in ('llms','llms_full') and expected.strip()!='MISSING' and actual.strip()!='MISSING':
    if expected.split()[0]!=actual.split()[0]:raise ValueError('baseline fingerprint mismatch '+label)
   else:check_baseline(expected,actual,label)
   print('BASELINE_MATCH',label,flush=True)
  expected=sha(a.pkg/'site-29y/mu-plugins/packrivo-i18n.php');actual=dk(['exec',a.wp_container,'sha256sum',a.wp_root+'/wp-content/mu-plugins/packrivo-i18n.php']).split()[0]
  if actual!=expected:raise ValueError('existing i18n router differs from reviewed version')
  a.stage.mkdir(parents=False,exist_ok=False)
  for filename,folder,kind in PACKS:print('STAGED_ZIP',filename,'files',extract_zip(a.pkg/'r1-packages'/filename,a.stage,folder),flush=True)
  for _,folder,kind in PACKS:
   target=a.wp_root+'/wp-content/'+kind+'/'+folder
   dk(['exec',a.wp_container,'mkdir','-p',target]);dk(['exec',a.wp_container,'find',target,'-mindepth','1','-maxdepth','1','-exec','rm','-rf','--','{}','+']);dk(['cp',str(a.stage/folder)+os.sep+'.',a.wp_container+':'+target+'/']);print('INSTALLED',folder,flush=True)
  mu=a.wp_root+'/wp-content/mu-plugins/'
  for rel in ('site-29y/mu-plugins/packrivo-privacy-noindex-29y.php','31b/mu-plugins/000-packrivo-31b-routes.php','32a/mu-plugins/001-packrivo-32a-404-title.php'):
   src=a.pkg/rel;dk(['cp',str(src),a.wp_container+':'+mu+src.name]);print('INSTALLED_MU',src.name,flush=True)
  for lang in LANGS:dk(['cp',str(a.pkg/'i18n-merged'/(lang+'-dict.json')),a.wp_container+':'+a.wp_root+'/wp-content/uploads/packrivo-i18n/'+lang+'-dict.json'])
  print('INSTALLED_DICTIONARIES',len(LANGS));print('STAGE_RELEASE_PASS');return 0
 except (OSError,ValueError,KeyError,RuntimeError,zipfile.BadZipFile,json.JSONDecodeError) as e:print('STAGE_RELEASE_STOP',str(e));return 1
if __name__=='__main__':sys.exit(main())
