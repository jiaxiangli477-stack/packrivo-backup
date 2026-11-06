#!/usr/bin/env python3
"""Summarize a complete R1 HTML scan and fail closed on field or sitemap defects."""
import argparse,csv,json,sys
from pathlib import Path
from urllib.parse import urlsplit
CATALOG={'/catalog-paper-box-o1cn019mnvjf1i/','/catalog-paper-box-o1cn01gehn2a1i/','/catalog-paper-box-o1cn01p56uj3qy/'}
def is_r2_catalog(path):return path in CATALOG
def is_1094(path):return path=='/2026/10/10/fsc-packaging-label-permission/'
def canonical_path(value,host):
 if not value:return ''
 p=urlsplit(value)
 if p.scheme not in ('http','https') or p.hostname!=host or p.username or p.password or p.fragment:raise ValueError('invalid canonical: '+value)
 return (p.path or '/')+('?' + p.query if p.query else '')
def assess(rows,sitemap,host='www.packrivo.com'):
 by={}
 for r in rows:
  p=r['path']
  if p in by:raise ValueError('duplicate scanned path '+p)
  if not isinstance(r.get('header_noindex'),bool) or not isinstance(r.get('noindex'),bool):raise ValueError('robots evidence missing '+p)
  by[p]=r
 field=[];body=[];sbad=[];omissions=[];catalog=[]
 for path,r in by.items():
  for f in r.get('fields',[]):
   if not is_1094(urlsplit(path).path):field.append((path,f['field'],f['value']))
  for value in r.get('body',[]):body.append((path,value))
  if r['status']!=200 or r['noindex'] or r['header_noindex']:continue
  canonical=canonical_path(r.get('canonical',''),host) or path
  if canonical not in sitemap:
   (catalog if is_r2_catalog(urlsplit(canonical).path) else omissions).append((path,canonical))
 for path in sorted(sitemap):
  r=by.get(path)
  if r is None or r['status']!=200 or r['noindex'] or r['header_noindex']:sbad.append((path,'unscanned' if r is None else str(r['status'])+' noindex='+str(r['noindex'])+' header_noindex='+str(r['header_noindex'])))
 return {'pages':len(rows),'sitemap':len(sitemap),'field_hits':len(field),'sitemap_bad':len(sbad),'indexable_omissions':len(omissions),'catalog_r2':len(catalog),'body_hits':len(body),'field_rows':field,'body_rows':body,'sitemap_rows':sbad,'omission_rows':omissions,'catalog_rows':catalog}
def write_csv(path,header,rows):
 with path.open('x',encoding='utf8',newline='') as f:
  w=csv.writer(f,lineterminator='\n');w.writerow(header);w.writerows(rows)
def main():
 p=argparse.ArgumentParser();p.add_argument('results',type=Path);p.add_argument('sitemap',type=Path);p.add_argument('--paths',type=Path,required=True);p.add_argument('--out-dir',type=Path,required=True);p.add_argument('--host',default='www.packrivo.com');a=p.parse_args()
 try:
  rows=[json.loads(x) for x in a.results.read_text(encoding='utf8').splitlines() if x.strip()];paths=json.loads(a.paths.read_text(encoding='utf8'));sitemap=set(json.loads(a.sitemap.read_text(encoding='utf8')));
  if not isinstance(paths,list) or set(paths)!={r.get('path') for r in rows} or len(paths)!=len(rows):raise ValueError('scan coverage mismatch')
  s=assess(rows,sitemap,a.host);a.out_dir.mkdir(parents=False,exist_ok=False)
  for name,header,key in [('fields.csv',['path','field','value'],'field_rows'),('body.csv',['path','context'],'body_rows'),('sitemap-bad.csv',['path','reason'],'sitemap_rows'),('omissions.csv',['path','canonical'],'omission_rows'),('catalog-r2.csv',['path','canonical'],'catalog_rows')]:write_csv(a.out_dir/name,header,s[key])
  for key in ('field_rows','body_rows','sitemap_rows','omission_rows','catalog_rows'):del s[key]
  print('R1_AUDIT',json.dumps(s,sort_keys=True));return 1 if s['field_hits'] or s['sitemap_bad'] or s['indexable_omissions'] else 0
 except (OSError,ValueError,KeyError,TypeError,json.JSONDecodeError) as e:print('R1_AUDIT_STOP',e);return 2
if __name__=='__main__':sys.exit(main())
