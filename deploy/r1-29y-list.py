#!/usr/bin/env python3
"""Build the 29y localized route list; performs no HTTP requests."""
import argparse,json,sys
from pathlib import Path
from urllib.parse import urlsplit
LANGS=('ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW')
def build(paths,origin,langs=LANGS):
 u=urlsplit(origin)
 if u.scheme not in ('http','https') or not u.hostname or u.username or u.password or u.path or u.query or u.fragment:raise ValueError('invalid origin')
 if not isinstance(paths,list) or not paths or len(set(paths))!=len(paths):raise ValueError('invalid paths')
 for lang in langs:
  if lang not in LANGS:raise ValueError('invalid language')
 for p in paths:
  if not isinstance(p,str) or not p.startswith('/') or p.startswith('//') or '..' in p or any(x in p for x in ('\r','\n','?','#')):raise ValueError('invalid path')
 return [lang+' '+origin+'/'+lang+('/' if p=='/' else p) for lang in langs for p in paths]
def main():
 p=argparse.ArgumentParser();p.add_argument('paths',type=Path);p.add_argument('origin');p.add_argument('output',type=Path);a=p.parse_args()
 try:
  lines=build(json.loads(a.paths.read_text(encoding='utf-8-sig')),a.origin)
  with a.output.open('x',encoding='utf8',newline='\n') as f:f.write('\n'.join(lines)+'\n')
  print('29Y_LIST languages=11 rows='+str(len(lines)));return 0
 except (OSError,ValueError,json.JSONDecodeError) as e:print('29Y_LIST_STOP',e);return 1
if __name__=='__main__':sys.exit(main())
