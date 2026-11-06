#!/usr/bin/env python3
"""Import reviewed translations; validate the complete set before any write."""
import argparse,csv,json,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
LANGS=('ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW')
BUCKETS=('title','attr','block','text')
def unique(pairs):
 d={}
 for k,v in pairs:
  if k in d: raise ValueError('duplicate JSON key: '+k)
  d[k]=v
 return d
def main():
 p=argparse.ArgumentParser();p.add_argument('--csv',type=Path,default=ROOT/'dsh-out/31b-i18n.csv');p.add_argument('--dict-dir',type=Path,default=ROOT/'deploy/i18n-merged');a=p.parse_args()
 try:
  docs={}; formats={}
  for lang in LANGS:
   path=a.dict_dir/(lang+'-dict.json'); raw=path.read_bytes(); data=json.loads(raw.decode('utf-8-sig'),object_pairs_hook=unique)
   if not isinstance(data,dict) or any(not isinstance(data.get(b),dict) for b in BUCKETS):raise ValueError('invalid buckets '+lang)
   docs[lang]=data;formats[lang]=(raw.startswith(b'\xef\xbb\xbf'),b'\r\n' in raw,raw.endswith(b'\n'))
  with a.csv.open(encoding='utf-8-sig',newline='') as f:
   rd=csv.DictReader(f)
   if not {'en','bucket','lang','value'}.issubset(rd.fieldnames or []):raise ValueError('CSV columns missing')
   rows=list(rd)
  if not rows: raise ValueError('empty CSV')
  conflicts=[]; seen={}; changes={l:0 for l in LANGS}
  for n,r in enumerate(rows,2):
   lang,bucket,en,val=(r.get(k,'') for k in ('lang','bucket','en','value'))
   if lang not in LANGS or bucket not in BUCKETS or not en.strip() or not val.strip():raise ValueError('invalid row '+str(n))
   key=(lang,bucket,en)
   if key in seen and seen[key]!=val:conflicts.append((n,lang,bucket,en,'duplicate CSV differs'))
   seen[key]=val
   current=docs[lang][bucket].get(en)
   if en in docs[lang][bucket] and current!=val:conflicts.append((n,lang,bucket,en,'existing value differs'))
  if conflicts:
   for x in conflicts: print('CONFLICT',json.dumps(x,ensure_ascii=False))
   print('STOP no dictionaries written');return 1
  for (lang,bucket,en),val in seen.items():
   if en not in docs[lang][bucket]: docs[lang][bucket][en]=val;changes[lang]+=1
  for lang,count in changes.items():
   if count:
    bom,crlf,newline=formats[lang];out=json.dumps(docs[lang],ensure_ascii=False,indent=2)+('\n' if newline else '')
    if crlf:out=out.replace('\n','\r\n')
    (a.dict_dir/(lang+'-dict.json')).write_bytes((b'\xef\xbb\xbf' if bom else b'')+out.encode('utf8'))
  for lang,count in changes.items():print(f'{lang} added={count}')
  print(f'IMPORT PASS rows={len(rows)} added={sum(changes.values())}');return 0
 except (OSError,ValueError,UnicodeError,csv.Error,json.JSONDecodeError) as e:print('IMPORT ERROR',e);return 2
if __name__=='__main__':sys.exit(main())
