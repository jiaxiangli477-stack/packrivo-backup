# -*- coding: utf-8 -*-
"""核查源清单里的截断（尾部 ...）与碎片行，并检查词典里同键的处理方式。"""
import json, io, os, re, csv

TMP = r'E:\破甲工作区\.tmp'
MAIN = r'E:\破甲工作区\packrivo-backup\deploy\i18n-merged'
CSV34A = r'E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-i18n.csv'
rows = json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}

trunc = [r for r in rows if r['en'].rstrip().endswith('...')]
short = [r for r in rows if len(r['en']) <= 12]
lowstart = [r for r in rows if r['en'][:1].islower()]
print('尾部...=%d  长度<=12=%d  小写开头=%d  分句标点开头=%d' % (
    len(trunc), len(short), len(lowstart),
    len([r for r in rows if r['en'][:1] in '.,;:)]'])))

# 词典里是否有这些键
de = json.load(io.open(os.path.join(MAIN, 'de-dict.json'), encoding='utf-8'))
dekeys = set()
for b, m in de.items():
    if isinstance(m, dict):
        dekeys |= set(m.keys())
print('\n-- 截断行（源 en 尾部 ...），词典是否含全文键：')
for r in trunc:
    stem = r['en'].rstrip()[:-3]
    hits = [k for k in dekeys if k.startswith(stem) and len(k) > len(stem)]
    print('%3d cls=%s len=%3d field=%s hit%d | %s' % (r['idx'], cls[r['idx']], len(r['en']), r['field'], len(hits), r['en'][:110]))
    if hits:
        print('      FULL: %s' % sorted(hits, key=len)[0][:400])
print('\n-- 极短行（<=12 字符）：')
for r in short:
    print('%3d cls=%s p%d %-14s | %r' % (r['idx'], cls[r['idx']], r['pages'], r['field'], r['en']))
print('\n-- 34a CSV 里的短键（<=12）与碎片键示例：')
with io.open(CSV34A, encoding='utf-8', newline='') as f:
    rd = list(csv.DictReader(f))
keys = {}
for r in rd:
    keys.setdefault(r['en'], 0)
    keys[r['en']] += 1
s = [k for k in keys if len(k) <= 12]
print('34a 唯一键=%d，其中<=12字符=%d' % (len(keys), len(s)))
print('  ' + ' | '.join(sorted(s)[:40]))
frag = [k for k in keys if k[:1] in '.,;:)' or (k[:1].islower() and len(k) < 40)]
print('34a 下开头/小写短碎片=%d' % len(frag))
print('  ' + ' | '.join(sorted(frag)[:25]))
