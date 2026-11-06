"""Second read-only probe: long-form shells that the 28 new strings plug into
(related-link headings, catalogue naming, sample-room captions)."""
import io
import json
import os

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
DICTS = os.path.join(ROOT, 'deploy', 'i18n-merged')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
BUCKETS = ['title', 'attr', 'block', 'text']

PROBES = [
    'velvet-lined', 'jewelry boxes', 'pouches and bags', 'sample room',
    'walkthrough', 'more in', 'related', 'whatsapp', 'illustrative',
    'english', 'presentation box', 'wine bottle', 'storage box',
    'pu leather', 'reference catalogue', 'supplier photos', 'permission',
    'language', 'switch language', 'guides', 'category',
]


def load(lang):
    with io.open(os.path.join(DICTS, '%s-dict.json' % lang), encoding='utf-8') as f:
        return json.load(f)


dicts = {l: load(l) for l in LANGS}
out = []
for probe in PROBES:
    out.append('\n=== %s' % probe)
    shown = 0
    for b in BUCKETS:
        for k in dicts['zh-CN'][b]:
            if probe not in k.lower():
                continue
            out.append('  [%s] %s' % (b, k))
            for l in LANGS:
                v = dicts[l][b].get(k)
                if v:
                    out.append('    %-6s %s' % (l, v))
            shown += 1
            if shown >= 2:
                break
        if shown >= 2:
            break
    if not shown:
        out.append('  (no key contains this term)')

io.open(os.path.join(ROOT, '..', '.tmp', '31b', 'probe2.txt'), 'w',
        encoding='utf-8', newline='\n').write('\n'.join(out))
print('\n'.join(out[:0]) or 'wrote probe2.txt')
