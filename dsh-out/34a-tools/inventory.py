# -*- coding: utf-8 -*-
"""Inventory 34a keys: dedupe, bucket groups, and segment inventories for translation."""
import csv, io, json, re
from collections import Counter, OrderedDict

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
CSV = ROOT + r'\dsh-out\34a-new-strings.csv'
OUT = ROOT + r'\dsh-out\34a-tools'
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
QUOTE_TITLE = 'Get a Custom Packaging Quote | Packrivo'

FINAL = OrderedDict([
    ('/ meta',
     'Custom jewelry boxes, pouches and gift packaging with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production.'),
    ('/products/ meta',
     'Packaging for jewelry brands: rigid boxes, pouches, gift bags, mailers and inserts, printed with your logo. MOQ 250 and a dieline with every quote.'),
    ('/bags-pouches/ meta',
     'Custom jewelry pouches, gift bags and mailer bags with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production.'),
    ('/product-drawstring-fabric-gift-bag/ meta',
     'Custom drawstring gift bags in bulk for jewelry and small gifts. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production.'),
    ('/product-zippered-fabric-gift-pouch/ meta',
     'Custom zippered fabric pouches for jewelry and gifts. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling.'),
])
FINAL_BY_URL = {
    '/': FINAL['/ meta'],
    '/products/': FINAL['/products/ meta'],
    '/bags-pouches/': FINAL['/bags-pouches/ meta'],
    '/product-drawstring-fabric-gift-bag/': FINAL['/product-drawstring-fabric-gift-bag/ meta'],
    '/product-zippered-fabric-gift-pouch/': FINAL['/product-zippered-fabric-gift-pouch/ meta'],
}


def skip(source, url, key):
    # 首页 title 两行（yoast_title + hub_title 同串）与 /cases/ meta 一行：保持原值，不进 CSV
    if key == 'Custom Packaging for Jewelry, Candles & Gifts | Packrivo':
        return True
    if url == '/cases/' and source in ('yoast_meta', 'hub_desc'):
        return True
    return False


def groups(source, url, key):
    if source in ('yoast_title', 'hub_title'):
        return [('title',)]
    if source in ('yoast_meta', 'hub_desc'):
        return [('attr',)]
    if source == 'h1':
        return [('block', 'text')]
    if source == 'theme' and url == '/get-a-quote/':
        return [('title',)] if key == QUOTE_TITLE else [('text', 'block')]
    raise SystemExit('unknown source/url: %r %r' % (source, url))


def w(path, text):
    io.open(path, 'w', encoding='utf-8', newline='\n').write(text)


def main():
    rows = list(csv.DictReader(io.open(CSV, encoding='utf-8-sig', newline='')))
    req = OrderedDict()
    where = {}
    skipped = []
    for r in rows:
        key = r['new_en'].replace('\r\n', '\n').replace('\r', '\n')
        src, url = r['source'], r['url']
        if skip(src, url, key):
            skipped.append((src, url, key))
            continue
        if src in ('yoast_meta', 'hub_desc') and url in FINAL_BY_URL:
            key = FINAL_BY_URL[url]
        gsrc = {'hub_title': 'yoast_title', 'hub_desc': 'yoast_meta'}.get(src, src)
        for g in groups(gsrc, url, key):
            req.setdefault(key, set()).add(g)
        where.setdefault(key, []).append(src + ' ' + url)

    keys = list(req)
    lines = []
    add = lines.append
    add('rows=%d unique_new_en=%d deliverable_keys=%d skipped=%d' % (
        len(rows), len({r['new_en'] for r in rows}), len(keys), len(skipped)))
    for s in skipped:
        add('  skip: %-11s %-60s %s' % (s[0], s[1], s[2][:70]))
    add('group patterns: ' + str(Counter(tuple(sorted(v)) for v in req.values())))

    by_bucket = {'title': [], 'attr': [], 'h1': [], 'other': []}
    for k in keys:
        p = tuple(sorted(req[k]))
        if p == (('title',),):
            by_bucket['title'].append(k)
        elif p == (('attr',),):
            by_bucket['attr'].append(k)
        elif p == (('block', 'text'),):
            by_bucket['h1'].append(k)
        else:
            by_bucket['other'].append(k)
    add('bucket split: ' + str({k: len(v) for k, v in by_bucket.items()}))

    # meta 句段盘点
    sents = Counter()
    for k in by_bucket['attr']:
        for s in re.split(r'(?<=\.)\s+', k):
            if s.strip():
                sents[s.strip()] += 1
    add('')
    add('=== meta sentence segments (%d) ===' % len(sents))
    for s, c in sents.most_common():
        add('%3d  %s' % (c, s))

    add('')
    add('=== h1 keys (%d) ===' % len(by_bucket['h1']))
    for k in by_bucket['h1']:
        add('  ' + k)
    add('')
    add('=== title keys (%d) ===' % len(by_bucket['title']))
    for k in by_bucket['title']:
        add('  ' + k)
    add('')
    add('=== other (%d) ===' % len(by_bucket['other']))
    for k in by_bucket['other']:
        add('  ' + k + '   ' + str(sorted(req[k])))

    w(OUT + r'\inventory.txt', '\n'.join(lines) + '\n')
    seg_list = [s for s, c in sents.most_common()]   # 已按出现频次排序
    json.dump({'keys': keys,
               'req': {k: [list(g) for g in req[k]] for k in keys},
               'where': where, 'final': FINAL, 'langs': LANGS,
               'segments': seg_list,
               'h1': by_bucket['h1'],
               'titles': by_bucket['title'],
               'other': by_bucket['other'],
               'buckets': {k: v for k, v in by_bucket.items()}},
              io.open(OUT + r'\keys.json', 'w', encoding='utf-8', newline='\n'),
              ensure_ascii=False, indent=1)
    print('\n'.join(lines[:6]))
    print('wrote inventory.txt / keys.json')


if __name__ == '__main__':
    main()
