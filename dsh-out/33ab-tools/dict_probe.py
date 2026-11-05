# -*- coding: utf-8 -*-
"""Where does the reviewed 32a dict keep a given English string? bucket + sample values."""
import io, json, os, sys

REF = r'E:\破甲工作区\packrivo-backup-32a\deploy\i18n-merged'
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
d = {}
for lang in LANGS:
    d[lang] = json.load(io.open(os.path.join(REF, '%s-dict.json' % lang), encoding='utf-8'))

targets = [
    'Ex-works. Freight quoted separately. Setup amortised into the unit price.',
    'Indicative, excludes freight. At 5,000 pieces the unit price is %s% lower than at 500. Final price confirmed after dieline review.',
    'Freight & duties quoted per destination. At 5,000 pieces the unit price is %s% lower than at 500. Final price confirmed after dieline review.',
    'Sourcing fee', 'Reference estimate', 'Order total', 'Goods', 'Quantity', 'Samples',
    'Board', 'Size', 'Rigid greyboard', 'Unit price, sourcing fee included',
    'Estimate', 'Select', 'Options', 'Specification', 'Dieline', 'Delivery', 'MOQ', 'None',
    'Inserts', 'Setup', 'Goods total', 'Home', 'Questions', 'Explore', 'Read', 'Topics',
    'Frequently asked questions', 'Order a sample', 'Blind emboss', 'Paperboard insert',
    'Breadcrumb', 'Product images', 'View image %s', '%s articles', 'From %s units',
    'Freight & duties', 'Waived', 'Quoted per destination', 'Customization options',
    'Surface finishes', 'Logo finishes', 'Shipping options', 'Consolidation', 'Related products',
    'Example quote · Rigid magnetic box', 'Milestone: Brief', 'Protective inserts', 'EVA / PU',
    'Folding boxes', 'Reference size', 'From 250 pieces', 'Illustrative flat layout',
    'DDP to door', 'FOB Shenzhen', 'Your brief', 'Build your quote',
]
out = []
for t in targets:
    hits = []
    for b in ('title', 'attr', 'block', 'text'):
        for lang in LANGS:
            if t in d[lang].get(b, {}):
                hits.append((b, lang))
                break
    if hits:
        b = hits[0][0]
        out.append('[%s] %s' % (b, t))
        for lang in ('de', 'ja', 'zh-CN'):
            v = d[lang].get(b, {}).get(t)
            if v:
                out.append('      %-6s %s' % (lang, v))
    else:
        out.append('[MISS] %s' % t)
p = os.path.join(r'E:\破甲工作区\packrivo-backup-33b', 'dsh-out', '33ab-tools', 'dict-probe.txt')
io.open(p, 'w', encoding='utf-8', newline='\n').write('\n'.join(out) + '\n')
print('wrote', p, len(out), 'lines')
