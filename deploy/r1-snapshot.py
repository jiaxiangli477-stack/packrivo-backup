#!/usr/bin/env python3
"""Create or restore a bounded R1 database/files snapshot; requires an authorized maintenance window."""
import argparse,hashlib,json,re,shutil,subprocess,sys,tarfile,tempfile
from pathlib import Path,PurePosixPath
ITEMS={'theme':'wp-content/themes/packrivo-home-v4','parent':'wp-content/themes/packrivo-static-bridge-1.2.38','quote':'wp-content/plugins/packrivo-quote','selector':'wp-content/plugins/packrivo-language-selector','workspace':'wp-content/plugins/packrivo-product-workspace','mu':'wp-content/mu-plugins','dictionaries':'wp-content/uploads/packrivo-i18n','llms':'llms.txt','llms_full':'llms-full.txt'}
def valid_db_name(name):return bool(re.fullmatch(r'[A-Za-z0-9_]+',name or ''))
def digest(path):
 h=hashlib.sha256()
 with path.open('rb') as f:
  for data in iter(lambda:f.read(1024*1024),b''):h.update(data)
 return h.hexdigest()
def verify_archive(path,expected,extract_to=None):
 count=0;prefix=PurePosixPath(expected)
 with tarfile.open(path,'r:') as tar:
  for m in tar:
   name=PurePosixPath(m.name)
   if name.is_absolute() or '..' in name.parts or not (name==prefix or prefix in name.parents) or not (m.isfile() or m.isdir()):raise ValueError('unsafe tar member '+m.name)
   if m.isfile():
    f=tar.extractfile(m)
    if f is None:raise ValueError('unreadable tar member')
    if extract_to is None:
     while f.read(1024*1024):pass
    else:
     target=extract_to.joinpath(*name.parts);target.parent.mkdir(parents=True,exist_ok=True)
     with target.open('wb') as out:shutil.copyfileobj(f,out)
    count+=1
   elif extract_to is not None:extract_to.joinpath(*name.parts).mkdir(parents=True,exist_ok=True)
 return count
def archive_hashes(path,expected):
 verify_archive(path,expected);prefix=PurePosixPath(expected);result={}
 with tarfile.open(path,'r:') as tar:
  for m in tar:
   if not m.isfile():continue
   relative=PurePosixPath(m.name).relative_to(prefix).as_posix()
   if relative in result:raise ValueError('duplicate tar member '+relative)
   h=hashlib.sha256();f=tar.extractfile(m)
   for block in iter(lambda:f.read(1024*1024),b''):h.update(block)
   result[relative]=h.hexdigest()
 return result

def live_hashes(a,path):
 code=r'''$p=$argv[1];if(!file_exists($p)){echo json_encode(['present'=>false,'files'=>[]]);exit;}if(is_link($p)){fwrite(STDERR,"symlink root");exit(1);}$out=[];if(is_file($p)){$out['.']=hash_file('sha256',$p);}elseif(is_dir($p)){$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);foreach($it as $f){if($f->isLink()){fwrite(STDERR,"symlink member");exit(1);}if($f->isFile()){$rel=substr($f->getPathname(),strlen(rtrim($p,'/'))+1);$out[$rel]=hash_file('sha256',$f->getPathname());}}}else{fwrite(STDERR,"unsupported path");exit(1);}ksort($out);echo json_encode(['present'=>true,'files'=>(object)$out],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);'''
 return json.loads(command(['exec',a.wp_container,'php','-r',code,path]).decode('utf8'))

def command(args,stdin=None,stdout=None):
 p=subprocess.run(['docker',*args],stdin=stdin,stdout=stdout if stdout is not None else subprocess.PIPE,stderr=subprocess.PIPE)
 if p.returncode:raise RuntimeError('Docker command failed: '+str(args[:3])+': '+p.stderr.decode('utf8',errors='replace')[-1200:])
 return p.stdout
def db_command(a,action):
 script='export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; '
 if action=='backup':script+='exec mysqldump -uroot --single-transaction --quick --skip-lock-tables --skip-add-locks --no-tablespaces --set-gtid-purged=OFF --routines --events --triggers --default-character-set=utf8mb4 --skip-dump-date --order-by-primary "$R1_DB_NAME"'
 else:script+='exec mysql -uroot "$R1_DB_NAME"'
 return ['exec','-i','-e','R1_DB_NAME='+a.db_name,a.db_container,'sh','-c',script]
def verify_snapshot(folder,m,a):
 identity={'wp_container':a.wp_container,'db_container':a.db_container,'wp_root':a.wp_root,'db_name':a.db_name}
 if m.get('version')!=1 or m.get('identity')!=identity or set(m.get('items',{}))!=set(ITEMS):raise ValueError('snapshot identity or item set mismatch')
 db=folder/'db.sql'
 if not db.is_file() or db.stat().st_size!=m['database']['bytes'] or digest(db)!=m['database']['sha256']:raise ValueError('database backup hash mismatch')
 for name,rel in ITEMS.items():
  entry=m['items'][name]
  if entry.get('relative')!=rel or not isinstance(entry.get('present'),bool):raise ValueError('invalid snapshot item '+name)
  if entry['present']:
   f=folder/(name+'.tar')
   if not f.is_file() or f.stat().st_size!=entry['bytes'] or digest(f)!=entry['sha256'] or verify_archive(f,rel)!=entry['files']:raise ValueError('archive mismatch '+name)
 return True
def main():
 p=argparse.ArgumentParser();p.add_argument('mode',choices=('backup','restore','check'));p.add_argument('--wp-container',required=True);p.add_argument('--db-container',required=True);p.add_argument('--wp-root',required=True);p.add_argument('--db-name',required=True);p.add_argument('--wp-cli',required=True);p.add_argument('--out',type=Path,required=True);a=p.parse_args()
 if not valid_db_name(a.db_name) or not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9_.-]*',a.wp_container) or not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9_.-]*',a.db_container) or not a.wp_root.startswith('/') or '..' in PurePosixPath(a.wp_root).parts or a.wp_root in ('/','/var','/var/www') or not a.wp_cli.startswith('/'):print('SNAPSHOT_STOP invalid arguments');return 2
 a.wp_root=a.wp_root.rstrip('/');identity={'wp_container':a.wp_container,'db_container':a.db_container,'wp_root':a.wp_root,'db_name':a.db_name};folder=a.out
 try:
  if a.mode=='backup':
   folder.mkdir(parents=False,exist_ok=False);m={'version':1,'identity':identity,'items':{}}
   with (folder/'db.sql').open('xb') as f:command(db_command(a,'backup'),stdout=f)
   f=folder/'db.sql'
   if not f.stat().st_size:raise ValueError('empty database backup')
   m['database']={'bytes':f.stat().st_size,'sha256':digest(f)};print('DB_BACKUP',m['database'],flush=True)
   for name,rel in ITEMS.items():
    probe=subprocess.run(['docker','exec',a.wp_container,'test','-e',a.wp_root+'/'+rel],stdout=subprocess.DEVNULL,stderr=subprocess.PIPE)
    if probe.returncode not in (0,1):raise RuntimeError('presence check failed '+name)
    exists=probe.returncode==0
    entry={'relative':rel,'present':exists};m['items'][name]=entry
    if exists:
     f=folder/(name+'.tar')
     with f.open('xb') as out:command(['exec',a.wp_container,'tar','-C',a.wp_root,'-cf','-',rel],stdout=out)
     with tempfile.TemporaryDirectory(prefix='verify-',dir=folder) as tmp:count=verify_archive(f,rel,Path(tmp))
     entry.update({'bytes':f.stat().st_size,'sha256':digest(f),'files':count})
    print('FILE_BACKUP',name,json.dumps(entry,sort_keys=True),flush=True)
   (folder/'backup.json').write_text(json.dumps(m,sort_keys=True,indent=2)+'\n',encoding='utf8',newline='\n');verify_snapshot(folder,m,a);print('BACKUP_PASS manifest_sha256='+digest(folder/'backup.json'));return 0
  m=json.loads((folder/'backup.json').read_text(encoding='utf8'));verify_snapshot(folder,m,a)
  if a.mode=='check':
   with tempfile.NamedTemporaryFile(prefix='r1-db-check-',dir=folder.parent,delete=True) as f:
    command(db_command(a,'backup'),stdout=f);f.flush();f.seek(0);h=hashlib.sha256()
    for block in iter(lambda:f.read(1024*1024),b''):h.update(block)
    current=h.hexdigest()
   if current!=m['database']['sha256']:raise ValueError('live database differs from snapshot')
   print('CHECK_DATABASE_MATCH',current,flush=True)
   for name,rel in ITEMS.items():
    live=live_hashes(a,a.wp_root+'/'+rel);expected=m['items'][name]
    if live['present']!=expected['present']:raise ValueError('live presence mismatch '+name)
    if expected['present'] and live['files']!=archive_hashes(folder/(name+'.tar'),rel):raise ValueError('live files mismatch '+name)
    print('CHECK_ITEM_MATCH',name,flush=True)
   print('CHECK_PASS');return 0
  print('RESTORE_PREFLIGHT_PASS',flush=True)
  wp=['exec',a.wp_container,'php','-d','disable_functions=',a.wp_cli,'--path='+a.wp_root,'--allow-root','--skip-plugins','--skip-themes']
  command(wp+['maintenance-mode','activate'])
  with (folder/'db.sql').open('rb') as f:command(db_command(a,'restore'),stdin=f)
  for name,rel in ITEMS.items():
   path=a.wp_root+'/'+rel;entry=m['items'][name]
   if entry['present']:
    command(['exec',a.wp_container,'sh','-c','if [ -d "$1" ]; then find "$1" -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +; else rm -f -- "$1"; fi','sh',path])
    with (folder/(name+'.tar')).open('rb') as f:command(['exec','-i',a.wp_container,'tar','-C',a.wp_root,'-xf','-'],stdin=f)
   else:command(['exec',a.wp_container,'rm','-rf','--',path])
   print('RESTORE_ITEM',name,'present='+str(entry['present']).lower(),flush=True)
  command(wp+['cache','flush']);print('RESTORE_COMPLETE maintenance=active: compare database and files; verify HTTP only behind an isolated upstream, then explicitly end maintenance');return 0
 except (OSError,ValueError,KeyError,RuntimeError,tarfile.TarError,json.JSONDecodeError) as e:print('SNAPSHOT_STOP',str(e));return 1
if __name__=='__main__':sys.exit(main())
