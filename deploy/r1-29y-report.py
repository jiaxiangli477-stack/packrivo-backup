#!/usr/bin/env python3
"""List 29y localized residuals; content failures are nonblocking, missing scans are not."""
import argparse,csv,json,sys
from pathlib import Path
from urllib.parse import urlsplit
CJK={'ja','ko','zh-CN','zh-TW'}
def failed(r):return r.get('long_runs',0)>0 if r.get('lang') in CJK else r.get('residual_count',-1)>5
def main():
 p=argparse.ArgumentParser();p.add_argument('results',type=Path);p.add_argument('--expected',type=Path,required=True);p.add_argument('--out',type=Path,required=True);a=p.parse_args()
 try:
  rows=[json.loads(x) for x in a.results.read_text(encoding='utf8').splitlines() if x.strip()];expected=[tuple(x.split(' ',1)) for x in a.expected.read_text(encoding='utf8').splitlines() if x.strip()];observed=[(r['lang'],r['url']) for r in rows]
  if len(observed)!=len(set(observed)) or set(observed)!=set(expected) or len(observed)!=len(expected):raise ValueError('scan coverage mismatch')
  errors=[r for r in rows if r.get('http')!=200 or not r.get('main_found') or r.get('residual_count',-1)<0]
  bad=[r for r in rows if r not in errors and failed(r)]
  with a.out.open('x',encoding='utf8',newline='') as f:
   w=csv.writer(f,lineterminator='\n');w.writerow(['language','path','residual_count','long_runs','review_reason','examples'])
   for r in bad:
    path=urlsplit(r['url']).path;reason='privacy page or shared text; inspect examples' if '/privacy/' in path else ('product page or shared text; inspect examples' if '/product-' in path else 'page or shared component; inspect examples');samples=r.get('long_run_samples') or r.get('english_segments') or [];w.writerow([r['lang'],path,r.get('residual_count'),r.get('long_runs'),reason,' | '.join(samples[:3])[:800]])
  print('29Y_REPORT pages='+str(len(rows))+' residual_nonblocking='+str(len(bad))+' scan_errors='+str(len(errors)))
  return 1 if errors else 0
 except (OSError,ValueError,KeyError,TypeError,json.JSONDecodeError) as e:print('29Y_REPORT_STOP',e);return 2
if __name__=='__main__':sys.exit(main())
