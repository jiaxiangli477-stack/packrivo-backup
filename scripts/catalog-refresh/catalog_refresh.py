import argparse, csv, hashlib, json, os, re
from pathlib import Path

FIELDS=['slug','category','main','source_sha256','main_sha256','main_480_sha256','structure','structure_reason','markings','markings_reason','branding','branding_reason','defects','defects_reason','conclusion','reason','angle_sha256','angle_480_sha256','angle_conclusion','angle_reason','detail_sha256','detail_480_sha256','detail_conclusion','detail_reason']
for variant in ('angle','detail'):
    for criterion in ('structure','markings','branding','defects'):
        FIELDS.extend([variant+'_'+criterion, variant+'_'+criterion+'_reason'])
EXCEPTIONS={'catalog-ring-box-o1cn01gdgp':'版权形象','catalog-storage-box-o1cn01jaxj':'版权形象','catalog-jewelry-packaging-box-':'原图缺失','catalog-drawer-box-033975':'已知产品玫瑰压纹和压印文字丢失'}

def digest(path):
    if not path.is_file(): return ''
    h=hashlib.sha256()
    with path.open('rb') as f:
        for block in iter(lambda:f.read(1024*1024),b''): h.update(block)
    return h.hexdigest()

def safe_name(s):
    if not s or Path(s).name != s or s in ('.','..'): raise ValueError('unsafe path component')
    return s

def classify(row, root, sources, old):
    slug=safe_name(row['slug']); category=safe_name(row['分类目录']); name=safe_name(row['交付文件名'])
    source=sources/(slug+'.webp'); main=root/category/name
    small=row.get('同时需要','')
    hashes={'source_sha256':digest(source),'main_sha256':digest(main),'main_480_sha256':digest(root/category/safe_name(small)) if small else ''}
    x={k:'' for k in FIELDS}; x.update(slug=slug,category=category,main=name,**hashes)
    if old and all(old.get(k)==v for k,v in hashes.items()):
        for k in FIELDS:
            if k not in ('slug','category','main','source_sha256','main_sha256','main_480_sha256'): x[k]=old.get(k,'')
    for label,key,key480 in [('angle','第二图1200','第二图480'),('detail','第三图1200','第三图480')]:
        filename=row.get(key,''); new_hash=digest(root/category/safe_name(filename)) if filename else ''
        smallname=row.get(key480,''); small_hash=digest(root/category/safe_name(smallname)) if smallname else ''
        if not old or old.get(label+'_sha256') != new_hash or old.get(label+'_480_sha256') != small_hash or old.get('source_sha256') != hashes['source_sha256']:
            x[label+'_conclusion']='需人工看' if new_hash else '未交付'; x[label+'_reason']='尚未逐图核验' if new_hash else '文件不存在';
            for criterion in ('structure','markings','branding','defects'):
                x[label+'_'+criterion]=''; x[label+'_'+criterion+'_reason']=''
        x[label+'_sha256']=new_hash
        x[label+'_480_sha256']=small_hash
        checks=all(x[label+'_'+k]=='pass' and x[label+'_'+k+'_reason'] for k in ('structure','markings','branding','defects'))
        if x[label+'_conclusion']=='通过' and (not new_hash or not small_hash or not checks):
            x[label+'_conclusion']='需人工看'; x[label+'_reason']='变体缺少完整尺寸或质检依据'
    if slug.lower() in EXCEPTIONS:
        x['conclusion']='不通过'; x['reason']=EXCEPTIONS[slug.lower()]
    elif not all(hashes.values()):
        x['conclusion']='需人工看'; x['reason']='原图或主图尚未交付'
    elif not old or any(old.get(k)!=v for k,v in hashes.items()):
        x['conclusion']='需人工看'; x['reason']='尚未逐图核验或图像已改变'
    elif x['conclusion']=='通过' and not all(x[k]=='pass' and x[k+'_reason'] for k in ('structure','markings','branding','defects')):
        x['conclusion']='需人工看'; x['reason']='通过须有四项 pass 及各项依据'
    elif x['conclusion'] not in ('通过','不通过','需人工看'):
        x['conclusion']='需人工看'; x['reason']='尚未逐图核验'
    return x

def load(path):
    with path.open(encoding='utf-8-sig',newline='') as f: return list(csv.DictReader(f))

def main():
    p=argparse.ArgumentParser(); p.add_argument('--index',type=Path,required=True); p.add_argument('--sources',type=Path,required=True); p.add_argument('--qa',type=Path,required=True); p.add_argument('--inventory',type=Path,required=True); p.add_argument('--report',type=Path,required=True); args=p.parse_args()
    rows=load(args.index); old={r['slug'].lower():r for r in load(args.qa)} if args.qa.exists() else {}
    seen=set(); qa=[]
    for row in rows:
        key=row['slug'].lower()
        if key in seen: raise ValueError('duplicate slug: '+key)
        seen.add(key); qa.append(classify(row,args.index.parent,args.sources,old.get(key)))
    pages=load(args.inventory); published={r['slug'].lower() for r in pages if r['status']=='publish'}
    index_slugs={r['slug'].lower() for r in rows}
    report={'csv_rows':len(rows),'local_pages':len(pages),'local_published':len(published),'local_published_and_csv':len(published & index_slugs),'csv_not_local_published':len(index_slugs-published),'local_published_not_csv':len(published-index_slugs),'qa':{s:sum(r['conclusion']==s for r in qa) for s in ('通过','不通过','需人工看')},'note':'local snapshot only; not current production evidence'}
    args.qa.parent.mkdir(parents=True,exist_ok=True)
    tmp=args.qa.with_suffix(args.qa.suffix+'.tmp')
    with tmp.open('w',encoding='utf-8-sig',newline='') as f:
        w=csv.DictWriter(f,fieldnames=FIELDS); w.writeheader(); w.writerows(qa)
    os.replace(tmp,args.qa)
    args.report.write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print(json.dumps(report,ensure_ascii=False))
if __name__=='__main__': main()
