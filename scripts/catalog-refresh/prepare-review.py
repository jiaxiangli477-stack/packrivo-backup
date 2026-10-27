import csv, hashlib, json
from pathlib import Path
root=Path('E:/破甲工作区/audit-parkrivo/codex-output'); src=Path('D:/Codex/packrivo-image-work/sources'); out=root/'_qa-review'; out.mkdir(exist_ok=True)
with (root/'交付索引.csv').open(encoding='utf-8-sig',newline='') as f: rows=list(csv.DictReader(f))
with Path('E:/破甲工作区/audit-parkrivo/catalog-qa.csv').open(encoding='utf-8-sig',newline='') as f: qa={r['slug']:r for r in csv.DictReader(f)}
manifest=[]
for r in rows:
 q=qa[r['slug']]; source=src/(r['slug']+'.webp'); main=root/r['分类目录']/r['交付文件名']
 if q['conclusion']!='需人工看' or not source.is_file() or not main.is_file(): continue
 paths=[source,main]
 for key in ('第二图1200','第三图1200'):
  p=root/r['分类目录']/r[key]
  if r[key] and p.is_file(): paths.append(p)
 ident=hashlib.sha256((''.join(hashlib.sha256(p.read_bytes()).hexdigest() for p in paths)).encode()).hexdigest()[:12]
 manifest.append({'slug':r['slug'],'paths':[str(p) for p in paths],'out':str(out/(r['slug']+'-'+ident+'.png'))})
Path('E:/破甲工作区/.tmp/catalog-review-manifest.json').write_text(json.dumps(manifest,ensure_ascii=False),encoding='utf8')
print('review sheets queued:',len(manifest))
