#!/usr/bin/env python3
"""Check sitemap crawl, 16 inbound targets and the explicitly exempt 301."""
import argparse,collections,csv,json,sys
from pathlib import Path
from urllib.parse import urlsplit
def assess(data,targets):
 if targets.get('version')!=1 or not isinstance(targets.get('inbound_targets'),list) or len(set(targets['inbound_targets']))!=len(targets['inbound_targets']):raise ValueError('invalid target list')
 inbound=collections.defaultdict(set);red={'moq_500':0,'standard':0,'factory_self':0}
 for source,record in data['records'].items():
  if urlsplit(source).path!='/2026/10/10/fsc-packaging-label-permission/' and isinstance(record.get('redlines'),dict):
   for k in red:red[k]+=int(record['redlines'].get(k,0))
  if record.get('status')==200:
   for href in record.get('links',[]):inbound[urlsplit(href).path].add(source)
 missing=[path for path in targets['inbound_targets'] if not inbound[path]];redirect=targets['redirect'];r=data['records'].get(redirect['path'],{});loc=urlsplit(r.get('redirect',''));redirect_ok=r.get('status')==redirect['status'] and loc.hostname=='www.packrivo.com' and loc.path==redirect['target'];issues=len(missing)+int(not redirect_ok)+len(data.get('sitemap_bad',[]))+len(data.get('orphans',[]))+len(data.get('query_links',[]))+len(data.get('errors',[]))+sum(red.values())
 return {'targets':len(targets['inbound_targets']),'missing_inbound':missing,'redirect_ok':redirect_ok,'sitemap_bad':len(data.get('sitemap_bad',[])),'orphans':len(data.get('orphans',[])),'query_links':len(data.get('query_links',[])),'crawl_errors':len(data.get('errors',[])),'redlines_excluding_1094':red,'issues':issues,'inbound':{p:sorted(inbound[p]) for p in targets['inbound_targets']}}
def main():
 p=argparse.ArgumentParser();p.add_argument('crawl',type=Path);p.add_argument('targets',type=Path);p.add_argument('--out-dir',type=Path,required=True);a=p.parse_args()
 try:
  d=json.loads(a.crawl.read_text(encoding='utf8'));targets=json.loads(a.targets.read_text(encoding='utf8'));s=assess(d,targets);a.out_dir.mkdir(parents=False,exist_ok=False)
  with (a.out_dir/'inbound.csv').open('x',encoding='utf8',newline='') as f:
   w=csv.writer(f,lineterminator='\n');w.writerow(['path','source_count','sources']);[w.writerow([p,len(src),' | '.join(src)]) for p,src in s['inbound'].items()]
  with (a.out_dir/'widget-targets.csv').open('x',encoding='utf8',newline='') as f:
   w=csv.writer(f,lineterminator='\n');w.writerow(['path','occurrences']);w.writerows(sorted(d.get('widget_targets',{}).items()))
  del s['inbound'];print('R1_LINK_AUDIT',json.dumps(s,ensure_ascii=False,sort_keys=True));return int(bool(s['issues']))
 except (OSError,KeyError,TypeError,ValueError,json.JSONDecodeError) as e:print('R1_LINK_AUDIT_STOP',e);return 2
if __name__=='__main__':sys.exit(main())
