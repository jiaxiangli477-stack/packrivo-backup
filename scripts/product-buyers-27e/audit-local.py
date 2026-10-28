import pathlib,json,csv,collections,re
p=pathlib.Path('E:/破甲工作区/.tmp');old=json.loads((p/'pk27e-products.json').read_text(encoding='utf-8-sig'));new=json.loads((p/'pk27e-products-after.json').read_text(encoding='utf-8-sig'));before={x['slug']:x for x in old}
rows=[(x['slug'],before[x['slug']]['title'],x['title'],x['description'],'changed' if x['title']!=before[x['slug']]['title'] else 'unchanged') for x in new]
out=pathlib.Path('E:/破甲工作区/packrivo-backup/scripts/product-buyers-27e/full-inventory.csv')
with out.open('w',encoding='utf-8-sig',newline='') as f:
 w=csv.writer(f);w.writerow(['slug','old_title','current_title','current_description','status']);w.writerows(rows)
c=collections.Counter(x['title'].casefold() for x in new)
pat=re.compile(r'near me|in delhi|surat|1688|alibaba|\bdiy\b|how to make|for women|for girls|for sale|e.flute|sbs\s*400|greyboard\s*2\s*mm|\bgsm\b|delta e|\bd50\b|\bista\b|\bastm\b|dieline',re.I)
hits=[(x['slug'],m.group(0)) for x in new if (m:=pat.search(x['title']+' '+x['h1']+' '+x['description']))]
print('total:',len(new),'changed:',sum(r[-1]=='changed' for r in rows),'unchanged:',sum(r[-1]=='unchanged' for r in rows))
print('duplicate title groups:',sum(v>1 for v in c.values()))
print('rows with banned terms:',len(hits));print('hits:',hits)
print('titles containing sourcing:',sum('sourcing' in x['title'].lower() for x in new))
