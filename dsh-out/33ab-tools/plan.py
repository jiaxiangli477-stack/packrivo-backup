# -*- coding: utf-8 -*-
"""Freeze the bucket column and resolve per-language reuse against the 32a dictionaries.

Bucket rules (justified in the report):
  title  -> the string is a document <title>
  attr   -> the string occurs as an HTML attribute value (aria-label / alt / placeholder /
            title / value / content). 32a's attr bucket holds exactly this class of string
            (Close image, Product concept image, you@example.com, Choose language).
  block  -> paragraph-level prose: >=2 sentence terminators, or a single sentence >=70 chars,
            or a multi-sentence runtime notice.
  text   -> headings, labels, table cells, kickers, short one-line notes, JS runtime hints.
Button/link captions stay in text: 32a stores the same class of CTA in text/block
(Order a sample -> block, Home -> block), and attr is attribute-values only.

Reuse rule: same English + same bucket -> reused. A hit in the sibling bucket of the
{block,text} family is also reused, because scripts/ui-28k/merge-i18n.mjs:22-26 writes every
non-attr string into both buckets, so block<->text is one storage family, not two kinds.
A hit only in attr/title while the string is assigned elsewhere is NOT reusable (kind change).
"""
import io, json, os
from collections import Counter

WS = r'E:\破甲工作区\packrivo-backup-33b'
REF = r'E:\破甲工作区\packrivo-backup-32a\deploy\i18n-merged'
TOOLS = os.path.join(WS, 'dsh-out', '33ab-tools')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']

DATA = json.load(io.open(os.path.join(TOOLS, 'strings.json'), encoding='utf-8'))
DET = json.load(io.open(os.path.join(TOOLS, 'bucket-map.json'), encoding='utf-8'))
auto = {d['en']: d for d in DET}

ATTR_PREFIX = ('Breadcrumb', 'Product images', 'View image ', 'Scrollable price table',
               'Illustrative flat layout')
BLOCK_PREFIX = (
    'Print setup is waived from ', 'Ex-works. Freight & duties', 'Freight & duties quoted per destination. At ',
    'Reference estimate: unprinted', 'Prices include the sourcing fee. Freight',
    'To request a quote without JavaScript',
)

dicts = {}
for lang in LANGS:
    dicts[lang] = json.load(io.open(os.path.join(REF, lang + '-dict.json'), encoding='utf-8'))

FAMILY = {'text': ['text', 'block'], 'block': ['block', 'text'],
          'attr': ['attr'], 'title': ['title']}

rows, decided = [], {}
for en in DATA['order']:
    meta = DATA['meta'][en]
    tag = set(auto[en]['tags'])
    if en.startswith(ATTR_PREFIX):
        bucket = 'attr'
    elif en.startswith(BLOCK_PREFIX):
        bucket = 'block'
    elif tag <= {'js'}:
        bucket = 'text'
    elif auto[en]['bucket'] in ('text', 'block', 'attr', 'title'):
        bucket = auto[en]['bucket']
    else:
        bucket = 'text'
    decided[en] = bucket
    for lang in LANGS:
        d = dicts[lang]
        hit = None
        for b in FAMILY[bucket]:
            v = d.get(b, {}).get(en)
            if isinstance(v, str) and v.strip():
                hit = (b, v)
                break
        rows.append({
            'round': meta['round'], 'en': en, 'bucket': bucket, 'lang': lang,
            'origin': 'reused' if hit else 'new',
            'hit_bucket': hit[0] if hit else '',
            'value': hit[1] if hit else '',
            'where': meta['where'], 'pages': meta['pages'], 'source': meta['source'],
        })

io.open(os.path.join(TOOLS, 'plan.json'), 'w', encoding='utf-8', newline='\n').write(
    json.dumps(rows, ensure_ascii=False, indent=1) + '\n')
io.open(os.path.join(TOOLS, 'buckets.json'), 'w', encoding='utf-8', newline='\n').write(
    json.dumps(decided, ensure_ascii=False, indent=1) + '\n')

print('rows', len(rows), 'strings', len(DATA['order']))
print('origin', dict(Counter(r['origin'] for r in rows)))
print('bucket', dict(Counter(decided.values())))
for lang in LANGS:
    c = Counter(r['origin'] for r in rows if r['lang'] == lang)
    print(' ', lang, dict(c))
print('new strings (all-lang new):',
      sum(1 for en in DATA['order'] if all(r['origin'] == 'new' for r in rows if r['en'] == en)))
print('fully reused:',
      sum(1 for en in DATA['order'] if all(r['origin'] == 'reused' for r in rows if r['en'] == en)))
