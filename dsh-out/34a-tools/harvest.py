# -*- coding: utf-8 -*-
"""Harvest existing dictionary phrasings for the 34a sentence tails and product terms."""
import io, json

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS = ('title','attr','block','text')

NEEDLES = [
    'Dieline with every quote',
    'MOQ 250, 5-10 day sampling',
    '7-30 day production',
    'DDP or FOB',
    'Board, corrugated or rigid stock',
    'Recycled-content',
    'Dieline before production',
    'coordination fee',
    'retainer',
    'Request a quote',
    'Compare structures and materials',
    'MOQ and tooling compared',
    'Sampling and production time',
    'Every structure we can source',
    'Custom packaging for jewelry, candles',
    'H1',  # placeholder, never matches
]

lines = []
add = lines.append
for lang in LANGS:
    d = json.loads(io.open(ROOT + r'\deploy\i18n-merged\%s-dict.json' % lang,
                           encoding='utf-8-sig').read())
    add('##### %s' % lang)
    for needle in NEEDLES:
        if needle == 'H1':
            continue
        hits = []
        seen = set()
        for b in BUCKETS:
            for k, v in d[b].items():
                if needle.lower() in k.lower() and v not in seen:
                    seen.add(v)
                    hits.append((b, k, v))
        add('== %s (%d unique values)' % (needle, len(hits)))
        for b, k, v in hits[:6]:
            add('  [%s] %s' % (b, k[:130]))
            add('       -> %s' % v[:220])
    add('')

io.open(TOOLS + r'\harvest.txt','w',encoding='utf-8',newline='\n').write('\n'.join(lines)+'\n')
print('lines', len(lines))
