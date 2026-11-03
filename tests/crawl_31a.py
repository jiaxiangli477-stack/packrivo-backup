"""Offline, no-JS sitemap and link check against the isolated 31a rehearsal."""
import concurrent.futures
from html.parser import HTMLParser
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET

BASE = 'http://localhost:8081'
LANG = re.compile(r'^/(?:de|fr|es|it|nl|pt|ar|ja|ko|zh-cn|zh-tw)(?:/|$)', re.I)
STATIC = re.compile(r'\.[a-z0-9]{1,8}$', re.I)
EXCEPTIONS = {'/catalog-jewelry-pouch-164909/', '/catalog-jewelry-pouch-996892/', '/catalog-pouch/', '/catalog-velvet-lined-box-916436/', '/catalog-velvet/'}

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl): return None

def fetch(path):
    request = urllib.request.Request(BASE + path, headers={'User-Agent':'31a-local-rehearsal/1'})
    try: response = urllib.request.build_opener(NoRedirect()).open(request, timeout=120)
    except urllib.error.HTTPError as exc: response = exc
    except Exception as exc: return {'status':0, 'error':str(exc), 'html':'', 'headers':{}}
    with response: return {'status':response.status, 'headers':dict(response.headers), 'html':response.read().decode('utf-8','replace')}

def local(url, context='/'):
    parsed = urllib.parse.urlsplit(urllib.parse.urljoin('https://www.packrivo.com' + context, url))
    if parsed.scheme not in ('http','https') or parsed.hostname not in ('www.packrivo.com','packrivo.com','localhost','127.0.0.1'): return None
    path = parsed.path or '/'
    if LANG.match(path) or path.startswith(('/wp-','/cdn-cgi/')) or STATIC.search(path): return None
    query = urllib.parse.parse_qs(parsed.query)
    if path in ('/blog/','/products/') and query.get('pg', [''])[0].isdigit():
        return path + '?pg=' + query['pg'][0]
    return path

class Page(HTMLParser):
    def __init__(self): super().__init__(); self.hrefs=[]; self.noindex=False
    def handle_starttag(self, tag, attrs):
        attrs=dict(attrs)
        if tag.lower()=='a' and attrs.get('href'): self.hrefs.append(attrs['href'])
        if tag.lower()=='meta' and attrs.get('name','').lower() in ('robots','googlebot') and re.search(r'\bnoindex\b', attrs.get('content',''), re.I): self.noindex=True

def page(path):
    result=fetch(path); p=Page(); p.feed(result['html'])
    result['links']=[local(x,path) for x in p.hrefs]
    result['bad_catalog']=[x for x in p.hrefs if re.search(r'/catalog-[^/?#]*[A-Z]', urllib.parse.urlsplit(x).path)]
    result['noindex']=p.noindex or 'noindex' in result['headers'].get('X-Robots-Tag','').lower()
    result['redirect']=local(result['headers'].get('Location',''), path) if result['headers'].get('Location') else None
    del result['html']; del result['headers']
    return result

index=fetch('/sitemap_index.xml')
if index['status'] != 200: raise SystemExit('sitemap index HTTP '+str(index['status']))
root=ET.fromstring(index['html'])
ns={'s':'http://www.sitemaps.org/schemas/sitemap/0.9'}
urls=set()
for node in root.findall('s:sitemap/s:loc',ns):
    parsed=urllib.parse.urlsplit(node.text or '')
    if parsed.hostname not in ('www.packrivo.com','packrivo.com','localhost','127.0.0.1') or LANG.match(parsed.path): continue
    if re.search(r'(?:^|/)(?:de|fr|es|it|nl|pt|ar|ja|ko|zh-cn|zh-tw)-sitemap', parsed.path, re.I): continue
    child=parsed.path
    result=fetch(child)
    if result['status'] != 200: raise SystemExit('sitemap HTTP error '+child+' '+str(result['status']))
    for loc in ET.fromstring(result['html']).findall('s:url/s:loc',ns):
        path=local(loc.text or '')
        if path: urls.add(path)
print('SITEMAP_ENGLISH_TOTAL',len(urls),flush=True)
results={}
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
    futures={pool.submit(page,p):p for p in sorted(urls | {'/'})}
    for n,future in enumerate(concurrent.futures.as_completed(futures),1):
        path=futures[future]
        try: results[path]=future.result()
        except Exception as exc: results[path]={'status':0,'error':str(exc),'noindex':False,'links':[],'bad_catalog':[]}
        if n % 100 == 0: print('CHECKED',n,flush=True)
for path in ('/foldable-gift-boxes/', '/inserts/', '/jewelry-boxes/'):
    results[path] = page(path)
anomalies=[(p,r['status'],r.get('noindex'),r.get('error','')) for p,r in results.items() if p in urls and (r['status'] != 200 or r.get('noindex'))]
print('SITEMAP_ANOMALIES',len(anomalies),flush=True)
for item in anomalies: print('ANOMALY',*item,flush=True)
frontier={'/'}; seen=set()
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
    while frontier:
        batch=frontier-seen
        frontier=set()
        seen.update(batch)
        if len(seen)>4000: raise SystemExit('crawl limit exceeded')
        missing=[p for p in batch if p not in results]
        if missing:
            print('REACHABLE',len(seen),'FETCHING',len(missing),flush=True)
            futures={pool.submit(page,p):p for p in missing}
            for future in concurrent.futures.as_completed(futures):
                path=futures[future]
                try: results[path]=future.result()
                except Exception as exc: results[path]={'status':0,'error':str(exc),'links':[],'bad_catalog':[]}
        for path in batch:
            result=results[path]
            if result['status']==200:
                frontier.update(x for x in result.get('links',[]) if x and x not in seen)
            elif 300 <= result['status'] < 400 and result.get('redirect'):
                frontier.add(result['redirect'])
orphans=sorted(p for p in urls if p not in seen and p not in EXCEPTIONS)
bad=[(p,x) for p,r in results.items() for x in r.get('bad_catalog',[])]
print('CATALOG_UPPERCASE_HREF_COUNT',len(bad),flush=True)
for item in bad: print('BAD_HREF',*item,flush=True)
print('HOME_REACHABLE_COUNT',len(seen),flush=True)
print('ORPHANS_EXCLUDING_C',len(orphans),flush=True)
for path in orphans: print('ORPHAN',path,flush=True)
sys.exit(1 if anomalies or bad or orphans else 0)
