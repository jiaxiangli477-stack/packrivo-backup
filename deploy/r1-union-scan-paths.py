#!/usr/bin/env python3
"""Union published routes, sitemap routes and locally crawled HTTP 200 routes."""
import argparse,json,sys
from pathlib import Path
def valid(path):return isinstance(path,str) and path.startswith('/') and not path.startswith('//') and '://' not in path and '..' not in path and not any(x in path for x in ('\r','\n','#'))
def build(base,crawl):
 if not isinstance(base,list) or not isinstance(crawl.get('sitemap_paths'),list) or not isinstance(crawl.get('records'),dict):raise ValueError('invalid route inputs')
 paths=set(base)|set(crawl['sitemap_paths'])|{p for p,r in crawl['records'].items() if isinstance(r,dict) and r.get('status')==200}
 if any(not valid(p) for p in paths):raise ValueError('unsafe route')
 return sorted(paths)
def main():
 p=argparse.ArgumentParser();p.add_argument('base',type=Path);p.add_argument('crawl',type=Path);p.add_argument('out',type=Path);a=p.parse_args()
 try:
  paths=build(json.loads(a.base.read_text(encoding='utf8')),json.loads(a.crawl.read_text(encoding='utf8')))
  with a.out.open('x',encoding='utf8',newline='\n') as f:json.dump(paths,f,ensure_ascii=False,separators=(',',':'))
  print('UNION_PATHS='+str(len(paths)));return 0
 except (OSError,ValueError,TypeError,json.JSONDecodeError) as e:print('UNION_STOP',e);return 1
if __name__=='__main__':sys.exit(main())
