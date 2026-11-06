#!/usr/bin/env python3
"""Check one saved local/authorized HTML response; body examples are nonblocking."""
import argparse,html,re,sys
from html.parser import HTMLParser
from pathlib import Path
FIELDS={'description','og:title','og:description','twitter:description'}
class Probe(HTMLParser):
 def __init__(self):super().__init__(convert_charrefs=True);self.stack=[];self.fields=[]
 def handle_starttag(self,tag,attrs):
  tag=tag.lower();a=dict(attrs)
  if tag in ('title','h1'):self.stack.append([tag,[]])
  elif tag=='meta':
   names={(a.get(key) or '').lower() for key in ('name','property')}
   for name in names & FIELDS:self.fields.append((name,a.get('content') or ''))
 def handle_data(self,data):
  for item in self.stack:item[1].append(data)
 def handle_endtag(self,tag):
  if self.stack and self.stack[-1][0]==tag.lower():
   kind,chunks=self.stack.pop();self.fields.append((kind,''.join(chunks)))
def inspect(text,products):
 p=Probe();p.feed(text);hits=[(k,html.unescape(v)) for k,v in p.fields if '500' in html.unescape(v)]
 if products:
  for match in re.finditer(r'\bMOQ\s*:?\s*500\b',text,re.I):hits.append(('products_raw_moq500',text[max(0,match.start()-200):match.end()+200]))
 return hits
def main():
 p=argparse.ArgumentParser();p.add_argument('file',type=Path);p.add_argument('--products',action='store_true');args=p.parse_args()
 try:hits=inspect(args.file.read_text(encoding='utf8'),args.products)
 except (OSError,UnicodeError) as e:print('CHECK_ERROR',e);return 2
 for k,v in hits:print('HIT',k,repr(v))
 print(f'FIRST_RESPONSE_FIELD_AND_PRODUCTS_HITS={len(hits)}');return int(bool(hits))
if __name__=='__main__':sys.exit(main())
