# -*- coding: utf-8 -*-
"""Survey 33a/33b new strings against the reviewed 32a dictionaries.

Reads only: dsh-out/33{a,b}-new-strings.csv, 32a deploy/i18n-merged/*-dict.json.
Writes only: dsh-out/33ab-tools/probe-out.txt
"""
import csv, io, json, os, sys

WS = r'E:\破甲工作区\packrivo-backup-33b'
REF = r'E:\破甲工作区\packrivo-backup-32a\deploy\i18n-merged'
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
OUT = os.path.join(WS, 'dsh-out', '33ab-tools', 'probe-out.txt')

lines = []
def out(s=''):
    lines.append(s)
    print(s)


def read_rows(name):
    p = os.path.join(WS, 'dsh-out', name)
    with io.open(p, encoding='utf-8-sig', newline='') as f:
        r = csv.reader(f)
        header = next(r)
        rows = [x for x in r if x and any(c.strip() for c in x)]
    return header, rows


h33a, r33a = read_rows('33a-new-strings.csv')
h33b, r33b = read_rows('33b-new-strings.csv')
out('header 33a: %s' % h33a)
out('header 33b: %s' % h33b)
out('rows 33a=%d 33b=%d total=%d' % (len(r33a), len(r33b), len(r33a) + len(r33b)))

# dedupe by exact english, first occurrence wins
seen = {}
order = []
for rnd, rows in (('33a', r33a), ('33b', r33b)):
    for r in rows:
        en = r[0]
        if en not in seen:
            seen[en] = {'round': rnd, 'en': en, 'where': r[1], 'pages': r[2],
                        'old': r[3] if len(r) > 3 else '', 'source': r[4] if len(r) > 4 else ''}
            order.append(en)
out('unique english = %d (dupes merged = %d)' % (len(order), len(r33a) + len(r33b) - len(order)))
dupes = []
for rnd, rows in (('33a', r33a), ('33b', r33b)):
    for r in rows:
        if seen[r[0]]['round'] != rnd:
            dupes.append((r[0], seen[r[0]]['round'], rnd))
out('merged duplicates:')
for d in dupes:
    out('  [%s<-%s] %s' % (d[1], d[2], d[0]))

dicts = {}
for lang in LANGS:
    p = os.path.join(REF, '%s-dict.json' % lang)
    with io.open(p, encoding='utf-8') as f:
        d = json.load(f)
    dicts[lang] = d
    out('dict %-5s buckets=%s' % (lang, {k: len(v) for k, v in d.items()}))

buckets = sorted(dicts['de'].keys())
out('bucket names = %s' % buckets)

# per-string coverage: in which buckets does the exact english key exist
out('')
out('=== coverage of 33a/33b english keys in 32a dicts (zh-CN as probe) ===')
zh = dicts['zh-CN']
n_any = 0
for en in order:
    hits = [b for b in buckets if en in zh.get(b, {})]
    if hits:
        n_any += 1
        out('HIT  %-45s %s' % (hits, en))
out('strings with at least one same-bucket-agnostic hit: %d / %d' % (n_any, len(order)))

json.dump({'order': order, 'meta': seen}, io.open(os.path.join(WS, 'dsh-out', '33ab-tools', 'strings.json'), 'w', encoding='utf-8'),
          ensure_ascii=False, indent=1)

with io.open(OUT, 'w', encoding='utf-8', newline='\n') as f:
    f.write('\n'.join(lines) + '\n')
