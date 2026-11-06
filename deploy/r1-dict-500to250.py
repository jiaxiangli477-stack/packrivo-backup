#!/usr/bin/env python3
"""Add 250 translations only when a reviewed old value differs by that number."""
import argparse,copy,json,os,sys,tempfile
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
LANGS=('ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW')
PAIRS=[('Order a sample of your custom box or pouch to check size, print and finish before your 500-piece order. Get a price.','Order a sample of your custom box or pouch to check size, print and finish before your 250-piece order. Get a price.'),('Custom Packaging MOQ: Plan 500 Pieces per Design','Custom Packaging MOQ: Plan 250 Pieces per Design')]
DESC_OLD='Plan a 500-piece packaging MOQ by design. Separate selling products, packaging variants and reserve allocations with a clear quantity worksheet for your brief.'
DESC_NEW='Plan a 250-piece packaging MOQ by design. Separate selling products, packaging variants and reserves with a clear quantity worksheet for your brief.'
def plan(docs,pairs):
 out=copy.deepcopy(docs);changed=0
 for old,new in pairs:
  if old.count('500')!=1 or old.replace('500','250')!=new:raise ValueError('English target is not solely 500 to 250')
  for lang,data in docs.items():
   for bucket,values in data.items():
    if not isinstance(values,dict) or old not in values:continue
    text=values[old]
    if not isinstance(text,str) or text.count('500')!=1:raise ValueError('old translation needs exactly one 500: '+lang+'/'+bucket)
    prefix,suffix=text.split('500');translated=prefix+'250'+suffix
    if translated[:len(prefix)]!=prefix or translated[len(prefix)+3:]!=suffix:raise ValueError('non-number change')
    if new in values and values[new]!=translated:raise ValueError('new key conflicts: '+lang+'/'+bucket)
    if new not in values:out[lang][bucket][new]=translated;changed+=1
 return out,changed
def main():
 p=argparse.ArgumentParser();p.add_argument('--dict-dir',type=Path,default=ROOT/'deploy/i18n-merged');args=p.parse_args()
 try:
  docs={};raws={}
  for lang in LANGS:
   path=args.dict_dir/(lang+'-dict.json');raw=path.read_bytes();raws[lang]=raw;data=json.loads(raw.decode('utf-8-sig'))
   if not isinstance(data,dict) or any(not isinstance(data.get(b),dict) for b in ('title','attr','block','text')):raise ValueError('invalid dictionary '+lang)
   if not any(PAIRS[0][0] in data[b] for b in ('title','attr','block','text')):raise ValueError('required old key missing '+lang)
   if any(DESC_OLD in data[b] for b in ('title','attr','block','text')):raise ValueError('448 description target changes more than a number; reviewed translation required '+lang)
   docs[lang]=data
  updated,n=plan(docs,PAIRS);staged=[]
  for lang in LANGS:
   if updated[lang]==docs[lang]:continue
   raw=raws[lang];text=json.dumps(updated[lang],ensure_ascii=False,indent=2)+('\n' if raw.endswith(b'\n') else '')
   if b'\r\n' in raw:text=text.replace('\n','\r\n')
   payload=(b'\xef\xbb\xbf' if raw.startswith(b'\xef\xbb\xbf') else b'')+text.encode('utf8')
   with tempfile.NamedTemporaryFile(dir=args.dict_dir,prefix='.r1-dict-',delete=False) as f:f.write(payload);staged.append((Path(f.name),args.dict_dir/(lang+'-dict.json')))
  for temp,path in staged:os.replace(temp,path)
  print(f'DICT_PASS languages={len(LANGS)} added={n}');return 0
 except (OSError,ValueError,json.JSONDecodeError) as exc:print('DICT_STOP',exc);return 1
if __name__=='__main__':sys.exit(main())
