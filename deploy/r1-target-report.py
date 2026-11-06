#!/usr/bin/env python3
"""Validate the fixed R1 page, routing, language and GEO checks."""
import argparse,re,sys
from pathlib import Path
from urllib.parse import urlsplit
PAGES={'/','/products/','/jewelry-boxes/','/get-a-quote/','/2026/10/01/packaging-moq-design-planning/','/2026/10/02/multi-sku-jewelry-packaging-fit-matrix/','/2026/10/15/retail-demonstrator-material-plan/','/product-magnetic-jewelry-box/','/product-front-tuck-mailer/','/product-collapsible-magnetic-gift-box/','/product-double-wall-tray-lid/','/product-lock-cap-auto-bottom-carton/'}
LANGS={'ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW'}
ROUTES={'/PRIVACY/?x=AbC':'/privacy/?x=AbC','/de/about':'/de/about/','/zh-CN/about':'/zh-CN/about/','/blog/page/2/':'/blog/?pg=2'}
BOTS={'GPTBot','OAI-SearchBot','ChatGPT-User','ClaudeBot','Claude-SearchBot','Claude-User','PerplexityBot','Perplexity-User','Google-Extended','Applebot-Extended','Bingbot'}
def target_ok(line):return bool(re.fullmatch(r'TARGET \S+ HTTP=200 MOQ250=1 MOQ500=0',line))
def main():
 p=argparse.ArgumentParser();p.add_argument('log',type=Path);a=p.parse_args()
 try:
  lines=a.log.read_text(encoding='utf8').splitlines();issues=[];pages={};langs={};routes={};geo={};bots={};canonical=[]
  for line in lines:
   if line.startswith('TARGET '):
    parts=line.split();path=parts[1];pages[path]=target_ok(line)
   elif line.startswith('LANG_HOME '):
    parts=line.split();langs[parts[1]]=bool(re.search(r'HTTP=200 HREFLANG_COUNT=\d+ MISSING=$',line))
   elif line.startswith('ROUTE '):
    m=re.fullmatch(r'ROUTE (\S+) HTTP=(\d+) LOCATION=(.*)',line)
    if m:
     u=urlsplit(m.group(3));routes[m.group(1)]=(int(m.group(2)),(u.path or '')+('?' + u.query if u.query else ''))
   elif line.startswith('PAGINATION '):canonical.append(line)
   elif line.startswith('GEO '):
    m=re.fullmatch(r'GEO (\S+) HTTP=(\d+) MOQ250=(\d+) MOQ500=(\d+)',line)
    if m:geo[m.group(1)]=tuple(map(int,m.groups()[1:]))
   elif line.startswith('BOT '):
    m=re.fullmatch(r'BOT (\S+) BLOCK_ALL=(\d+)',line)
    if m:bots[m.group(1)]=int(m.group(2))
  if set(pages)!=PAGES or not all(pages.values()):issues.append('target pages')
  if set(langs)!=LANGS or not all(langs.values()):issues.append('language home links')
  if set(routes)!=set(ROUTES) or any(routes.get(k)!=(301,v) for k,v in ROUTES.items()):issues.append('routes')
  if canonical!=['PAGINATION HTTP=200 CANONICAL=https://www.packrivo.com/blog/?pg=2']:issues.append('pagination canonical')
  if set(geo)!={'/llms.txt','/llms-full.txt','/robots.txt'} or any(geo.get(x,(0,0,1))[0]!=200 or geo[x][2]!=0 or geo[x][1]<1 for x in ('/llms.txt','/llms-full.txt')) or geo.get('/robots.txt',(0,))[0]!=200:issues.append('GEO')
  if set(bots)!=BOTS or any(bots.values()):issues.append('robots crawlers')
  print('R1_TARGET_REPORT targets='+str(len(pages))+' languages='+str(len(langs))+' routes='+str(len(routes))+' bots='+str(len(bots))+' issues='+str(len(issues)))
  for x in issues:print('FAIL',x)
  return int(bool(issues))
 except (OSError,ValueError,IndexError) as e:print('TARGET_REPORT_STOP',e);return 2
if __name__=='__main__':sys.exit(main())
