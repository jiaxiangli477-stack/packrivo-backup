# -*- coding: utf-8 -*-
"""34a-译文-2 交付前自检：逐语言行数/空值/数字一致/禁用词/新键覆盖/中文本地化。"""
import csv, io, re
from collections import Counter, defaultdict

CSV = r"E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-i18n.csv"
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
NUM = re.compile(r'\d+(?:[.,]\d+)?')
NEW_MID = '8-15% coordination fee, agreed in writing before work starts.'
OLD_MID = '8-15% coordination fee on production only.'
NEWKEYS = [
 'Custom packaging sourcing. Then a quote. ' + NEW_MID + ' Request a quote.',
 'Custom packaging guide. ' + NEW_MID + ' Dieline with every quote. Request a quote.',
 'Custom packaging quote request. ' + NEW_MID + ' Dieline with every quote. Request a quote.',
 'Custom packaging finishes. ' + NEW_MID + ' Dieline with every quote. Request a quote.',
]

rows = list(csv.DictReader(io.open(CSV, encoding='utf-8-sig')))
out = []
out.append('rows=%d' % len(rows))
keys = sorted({r['en'] for r in rows})
out.append('unique_keys=%d' % len(keys))

per = Counter(r['lang'] for r in rows)
out.append('per_lang: ' + ' '.join('%s=%d' % (l, per[l]) for l in LANGS))
out.append('langs_all_337=%s' % (all(per[l] == 337 for l in LANGS) and len(per) == 11))

empty = [r for r in rows if not r['value'].strip()]
out.append('empty_values=%d' % len(empty))

bad_num = []
for r in rows:
    a, b = NUM.findall(r['en']), NUM.findall(r['value'])
    if a != b:
        bad_num.append((r['lang'], r['en'][:50], a, b))
out.append('digit_mismatch=%d' % len(bad_num))
for x in bad_num[:10]:
    out.append('  NUM %s en=%r %r != %r' % x)

ret = [r for r in rows if 'retainer' in r['en'].lower()]
ponly = [r for r in rows if 'production only' in r['en'].lower()]
out.append('en_contains_retainer=%d' % len(ret))
out.append('en_contains_production_only=%d' % len(ponly))
out.append('en_contains_old_segment=%d' % len([r for r in rows if OLD_MID in r['en']]))

triples = Counter((r['en'], r['bucket'], r['lang']) for r in rows)
out.append('dup_triples=%d' % sum(1 for v in triples.values() if v > 1))

for k in NEWKEYS:
    sub = [r for r in rows if r['en'] == k]
    buckets = sorted({r['bucket'] for r in sub})
    origins = sorted({r['origin'] for r in sub})
    langs = {r['lang'] for r in sub}
    out.append('newkey rows=%d langs=%d buckets=%s origins=%s  %s'
               % (len(sub), len(langs), buckets, origins, k[:60]))
    for r in sorted(sub, key=lambda r: LANGS.index(r['lang'])):
        out.append('    %-5s %s' % (r['lang'], r['value']))

out.append('buckets: ' + str(Counter(r['bucket'] for r in rows)))
out.append('origins: ' + str(Counter(r['origin'] for r in rows)))

zh = [r for r in rows if r['lang'] == 'zh-CN']
zt = [r for r in rows if r['lang'] == 'zh-TW']
out.append('zh-CN 收纳袋=%d 首饰袋=%d' % (sum('收纳袋' in r['value'] for r in zh),
                                        sum('首饰袋' in r['value'] for r in zh)))
out.append('zh-TW 收納袋=%d 首飾袋=%d' % (sum('收納袋' in r['value'] for r in zt),
                                        sum('首飾袋' in r['value'] for r in zt)))
for r in zh:
    if '收纳袋' in r['value']:
        out.append('  ZHCN-LEFTOVER [%s] %s | %s' % (r['bucket'], r['en'][:60], r['value'][:70]))
for r in zt:
    if '收納袋' in r['value']:
        out.append('  ZHTW-LEFTOVER [%s] %s | %s' % (r['bucket'], r['en'][:60], r['value'][:70]))

txt = '\n'.join(out)
io.open(r'E:\破甲工作区\.tmp\34a\check_fix2.txt', 'w', encoding='utf-8', newline='\n').write(txt + '\n')
print(txt[:6000])
