# -*- coding: utf-8 -*-
"""Deterministic row sample from the composed CSV for the back-translation section of the report."""
import csv, io, math

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
CSV = ROOT + r'\dsh-out\34a-i18n.csv'
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS = ['title', 'attr', 'text']
N = {'ar': 20, 'ja': 20, 'zh-CN': 20}
DEF = 5

rows = {}
with io.open(CSV, encoding='utf-8-sig', newline='') as f:
    for r in csv.DictReader(f):
        rows.setdefault(r['lang'], []).append(r)


def spread(pool, k):
    """从 pool 里均匀取 k 条。"""
    if not pool or k <= 0:
        return []
    k = min(k, len(pool))
    step = len(pool) / float(k)
    return [pool[min(int(i * step), len(pool) - 1)] for i in range(k)]


lines = []
for lang in LANGS:
    rs = rows.get(lang, [])
    want = N.get(lang, DEF)
    pools = {b: [r for r in rs if r['bucket'] == b] for b in BUCKETS}
    per = int(math.ceil(want / float(len(BUCKETS))))
    buckets_pick = {b: spread(pools[b], per) for b in BUCKETS}
    pick, i = [], 0
    while len(pick) < want:
        b = BUCKETS[i % len(BUCKETS)]
        i += 1
        lst = buckets_pick[b]
        if lst:
            pick.append(lst.pop(0))
        elif all(not buckets_pick[x] for x in BUCKETS):
            break
    lines.append('===== %s   sampled=%d total=%d buckets=%s' % (
        lang, len(pick), len(rs),
        {b: len(pools[b]) for b in BUCKETS}))
    for r in pick:
        lines.append('[%s] en: %s' % (r['bucket'], r['en']))
        lines.append('     tr: %s   (%s)' % (r['value'], r['origin']))
    lines.append('')

io.open(TOOLS + r'\backtrans-sample.txt', 'w', encoding='utf-8', newline='\n').write(
    '\n'.join(lines) + '\n')
print('ok langs=%d' % len(LANGS))
