#!/usr/bin/env python3
"""Check all 31b keys in every dictionary, and imported keys in their intended bucket."""
import csv,json,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
LANGS=('ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW')
BUCKETS=('title','attr','block','text')
def present(data,key):
 return any(isinstance(data.get(b,{}).get(key),str) and data[b][key].strip() for b in BUCKETS)
def main():
 try:
  with (ROOT/'dsh-out/31b-new-strings.csv').open(encoding='utf-8-sig',newline='') as f: rows=list(csv.DictReader(f))
  with (ROOT/'dsh-out/31b-i18n.csv').open(encoding='utf-8-sig',newline='') as f: imports=list(csv.DictReader(f))
  keys=[r['英文原文'] for r in rows]
  if len(keys)!=37 or len(set(keys))!=37 or len(imports)!=308: raise ValueError('unexpected input counts or duplicate keys')
  print('rows=37 languages=11')
  total=0
  for lang in LANGS:
   data=json.loads((ROOT/'deploy/i18n-merged'/(lang+'-dict.json')).read_text(encoding='utf-8-sig'))
   missing=[k for k in keys if not present(data,k)]
   wrong=[r['en'] for r in imports if r['lang']==lang and data.get(r['bucket'],{}).get(r['en'])!=r['value']]
   print(f'{lang} missing={len(missing)} import_mismatch={len(wrong)}');total+=len(missing)+len(wrong)
  print(f'GATE {"PASS" if total==0 else "FAIL"} issues={total}');return int(total!=0)
 except (OSError,ValueError,KeyError,TypeError,csv.Error) as e:print('GATE ERROR',e);return 2
if __name__=='__main__':sys.exit(main())
