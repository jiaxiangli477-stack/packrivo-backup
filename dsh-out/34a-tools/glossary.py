# -*- coding: utf-8 -*-
"""Term glossary across the 11 live dictionaries: exact-key then substring lookup, short values only."""
import io, json

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS = ('title','attr','block','text')

TERMS = [
    'rigid boxes', 'mailer boxes', 'pouches', 'gift bags', 'inserts', 'labels',
    'corrugated', 'chipboard', 'kraft', 'tissue paper', 'ribbon', 'magnetic',
    'collapsible', 'die-cut', 'folding cartons', 'sleeve', 'tray', 'drawer',
    'pillow', 'velvet', 'foam', 'molded pulp', 'window', 'auto bottom',
    'reverse tuck', 'snap bottom', 'gusset', 'gable', 'belly band', 'header card',
    'dust flap', 'end caps', 'dividers', 'counter display', 'stand-up pouch',
    'poly mailer', 'bubble mailer', 'shipping carton', 'dieline', 'MOQ',
    'sampling', 'lead time', 'quote', 'supplier', 'tooling', 'freight',
    'landed cost', 'unit price', 'foil stamping', 'emboss', 'spot UV',
    'matte lamination', 'certificate', 'worksheet',
]

lines = []
add = lines.append
for term in TERMS:
    add('===== %s' % term)
    for lang in LANGS:
        d = json.loads(io.open(ROOT + r'\deploy\i18n-merged\%s-dict.json' % lang,
                               encoding='utf-8-sig').read())
        found = []
        for b in BUCKETS:
            for k, v in d[b].items():
                if k.strip().lower() == term:
                    found.insert(0, v)
                elif term in k.lower() and len(k) <= len(term) + 22:
                    found.append(v)
        uniq = []
        for v in found:
            if v not in uniq:
                uniq.append(v)
        if uniq:
            add('  %-6s %s' % (lang, ' || '.join(u[:90] for u in uniq[:3])))
        else:
            add('  %-6s -' % lang)
    add('')

io.open(TOOLS + r'\glossary.txt','w',encoding='utf-8',newline='\n').write('\n'.join(lines)+'\n')
print('lines', len(lines))
