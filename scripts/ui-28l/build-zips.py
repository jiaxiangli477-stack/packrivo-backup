from pathlib import Path
import hashlib,json,zipfile
repo=Path('E:/破甲工作区/packrivo-backup'); work=Path('E:/破甲工作区/packrivo-wp')
out=repo/'deploy'; artifacts={}
for name in ['packrivo-home-v4','packrivo-quote']:
 root=work/name;dest=out/(name+'.zip')
 files=sorted(p for p in root.rglob('*') if p.is_file() and not any(x in {'.git','node_modules','__pycache__'} for x in p.relative_to(root).parts) and not (name=='packrivo-quote' and p.relative_to(root).parts[0]=='demo'))
 assert files
 for f in files:
  backup=repo/'packrivo-wp'/name/f.relative_to(root)
  assert backup.is_file() and backup.read_bytes()==f.read_bytes(),f'Source/backup mismatch: {f}'
 with zipfile.ZipFile(dest,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
  for f in files:z.write(f,name+'/'+f.relative_to(root).as_posix())
 with zipfile.ZipFile(dest) as z:
  assert set(z.namelist())=={name+'/'+f.relative_to(root).as_posix() for f in files}
  for f in files:assert z.read(name+'/'+f.relative_to(root).as_posix())==f.read_bytes()
 artifacts[name]={'files':len(files),'sha256':hashlib.sha256(dest.read_bytes()).hexdigest(),'exclude_demo':name=='packrivo-quote'}
 print(name,'files='+str(len(files)),'sha256='+artifacts[name]['sha256'],'source_backup_zip=identical','demo_excluded='+str(artifacts[name]['exclude_demo']))
(out/'verification-28l/package-manifest.json').write_text(json.dumps(artifacts,indent=2)+'\n',encoding='utf8')
print('PASS packages checked byte-for-byte; quote demo and its local data excluded')
