#!/usr/bin/env python3
"""Fail closed until every CSV English key exists in all 11 site dictionaries.

The quote widget reads a separate dictionary, so its three new keys are also
required there. This checks presence and nonempty values, not translation quality.
"""
from pathlib import Path
import argparse,csv,json,re,sys

ROOT=Path(__file__).resolve().parent.parent
LANGS=['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS=('title','attr','block','text')
QUOTE_KEYS=("Under 250? Send the brief and we'll quote by hand.",
 'Goods {goods} · Sourcing fee {fee} ({percent}%, min. {minimum}) · Total {total}',
 'Instant quotes include the sourcing fee at {percent}%; your final rate is confirmed on the written quote.')
TITLE_404='Page not found | Packrivo'

def main():
 p=argparse.ArgumentParser()
 p.add_argument('--csv',type=Path,default=ROOT/'dsh-out/32a-new-strings.csv')
 p.add_argument('--dict-dir',type=Path,default=ROOT/'deploy/i18n-merged')
 p.add_argument('--quote-dir',type=Path,default=ROOT/'packrivo-wp/packrivo-quote/includes/quote-i18n')
 p.add_argument('--report',type=Path,help='Optional CSV of missing keys')
 args=p.parse_args()
 try:
  with args.csv.open(encoding='utf-8-sig',newline='') as f:
   reader=csv.DictReader(f)
   if not reader.fieldnames or '英文原文' not in reader.fieldnames:raise ValueError('missing 英文原文 column')
   rows=list(reader)
  if not rows or any(not isinstance(row.get('英文原文'),str) or not row['英文原文'].strip() for row in rows):raise ValueError('empty CSV or English key')
  # Git's Windows checkout can convert newlines inside quoted CSV fields to
  # CRLF; dictionary keys and the committed CSV use canonical LF.
  keys=list(dict.fromkeys(row['英文原文'].replace('\r\n','\n').replace('\r','\n') for row in rows))
  if not set(QUOTE_KEYS).issubset(keys) or TITLE_404 not in keys:raise ValueError('CSV missing required quote-widget or 404 title keys')
 except (OSError,UnicodeError,csv.Error,ValueError) as e:
  print('GATE INPUT ERROR:',e);return 2
 print(f'csv_rows={len(rows)} unique_keys={len(keys)} quote_keys={len(QUOTE_KEYS)}')
 missing=[];title_bucket_missing=[];errors=0;title_errors=0
 for lang in LANGS:
  try:
   data=json.loads((args.dict_dir/(lang+'-dict.json')).read_text(encoding='utf-8-sig'))
   if not isinstance(data,dict) or any(not isinstance(data.get(b),dict) for b in BUCKETS):raise ValueError('site dictionary missing required buckets')
   quote=json.loads((args.quote_dir/(lang+'.json')).read_text(encoding='utf-8-sig'))
   if not isinstance(quote,dict):raise ValueError('quote dictionary is not an object')
   absent=[k for k in keys if not any(isinstance(data[b].get(k),str) and data[b][k].strip() for b in BUCKETS)]
   qabsent=[k for k in QUOTE_KEYS if not isinstance(quote.get(k),str) or not quote[k].strip()]
   placeholders=lambda s:set(re.findall(r'\{[A-Za-z_]\w*\}',s))
   qinvalid=[k for k in QUOTE_KEYS if k not in qabsent and placeholders(k)!=placeholders(quote[k])]
   title_value=data['title'].get(TITLE_404)
   title_missing=not isinstance(title_value,str) or not title_value.strip()
   if title_missing:title_errors+=1;title_bucket_missing.append((lang,'title-bucket',TITLE_404))
   missing.extend((lang,'site',k) for k in absent);missing.extend((lang,'quote',k) for k in qabsent)
   missing.extend((lang,'quote-placeholder',k) for k in qinvalid)
   print(f'{lang} missing={len(absent)} quote_missing={len(qabsent)} quote_invalid={len(qinvalid)} title_missing={int(title_missing)}')
  except (OSError,UnicodeError,json.JSONDecodeError,ValueError) as e:
   errors+=1;print(f'{lang} ERROR {e}')
 if args.report:
  try:
   with args.report.open('w',encoding='utf-8-sig',newline='') as f:
    w=csv.writer(f);w.writerow(['language','dictionary','english_key']);w.writerows(missing);w.writerows(title_bucket_missing)
  except OSError as e:print('REPORT ERROR:',e);return 2
 print(f'GATE {"PASS" if not missing and not errors and not title_errors else "FAIL"} missing_entries={len(missing)} title_errors={title_errors} errors={errors}')
 return 0 if not missing and not errors and not title_errors else 1

if __name__=='__main__':sys.exit(main())
