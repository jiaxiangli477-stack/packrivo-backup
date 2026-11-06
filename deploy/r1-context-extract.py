#!/usr/bin/env python3
"""Save every MOQ or 500 occurrence with 200 characters of context."""
import argparse,re,sys
from pathlib import Path
def contexts(text):
 return [{'token':m.group(),'offset':m.start(),'before':text[max(0,m.start()-200):m.start()],'after':text[m.end():m.end()+200]} for m in re.finditer(r'MOQ|500',text,re.I)]
def main():
 p=argparse.ArgumentParser();p.add_argument('html',type=Path);p.add_argument('out',type=Path);a=p.parse_args()
 try:
  hits=contexts(a.html.read_text(encoding='utf8'))
  with a.out.open('x',encoding='utf8',newline='\n') as f:
   for i,x in enumerate(hits,1):f.write(f"=== MATCH {i} {x['token']} OFFSET {x['offset']} ===\n{x['before']}{x['token']}{x['after']}\n")
  print('CONTEXT_MATCHES='+str(len(hits)));return 0
 except (OSError,UnicodeError) as e:print('CONTEXT_STOP',e);return 1
if __name__=='__main__':sys.exit(main())
