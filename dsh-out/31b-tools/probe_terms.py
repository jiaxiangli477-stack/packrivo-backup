"""Read-only probe: harvest how the reference dictionaries render the terms
that appear in the 28 missing strings, so the new translations reuse the same
terminology. Prints one block per probe term."""
import io
import json
import os

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
DICTS = os.path.join(ROOT, 'deploy', 'i18n-merged')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
BUCKETS = ['title', 'attr', 'block', 'text']

PROBES = [
    'velvet', 'catalogue', 'drawer', 'mailer', 'pouch', 'kraft', 'insert',
    'tissue paper', 'dieline', 'supplier', 'sample', 'quote', 'corrugated',
    'faux fur', 'offset', 'proof', 'carton', 'dongguan', 'shantou',
    'with permission', 'reference catalogue', 'ribbon', 'soap', 'cosmetic',
    'subscription', 'stand-up', 'window', 'shipper', 'book-style',
]


def load(lang):
    with io.open(os.path.join(DICTS, '%s-dict.json' % lang), encoding='utf-8') as f:
        return json.load(f)


dicts = {l: load(l) for l in LANGS}

for probe in PROBES:
    print('\n=== %s' % probe)
    shown = 0
    for b in BUCKETS:
        for k in dicts['zh-CN'][b]:
            if probe not in k.lower():
                continue
            print('  [%s] %s' % (b, k))
            for l in LANGS:
                v = dicts[l][b].get(k)
                if v:
                    print('    %-6s %s' % (l, v))
            shown += 1
            if shown >= 2:
                break
        if shown >= 2:
            break
    if not shown:
        print('  (no key contains this term)')
