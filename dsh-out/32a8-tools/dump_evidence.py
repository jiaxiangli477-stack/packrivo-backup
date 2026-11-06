# -*- coding: utf-8 -*-
"""取报告要引用的具体行证：row6 / row28 / row86 三语 / row174 / row175 de。"""
import csv, io, json, os

CSV_P = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n.csv'
rows = json.load(io.open(r'E:\破甲工作区\.tmp\32a8-rows.json', encoding='utf-8'))
d = {}
for r in csv.reader(io.open(CSV_P, encoding='utf-8')):
    if r[0] == 'en':
        continue
    d.setdefault(r[0], {})[r[3]] = r[4]

L = []
def w(s=''):
    L.append(s)

def show(idx, langs=None, n=400):
    r = [x for x in rows if x['idx'] == idx][0]
    got = d.get(r['en'], {})
    w('### [%d] %s/%s  note=%s  rows_in_csv=%d' % (idx, r['kind'], r['field'],
                                                   got.get('note', ''), len(got)))
    w('EN : ' + r['en'])
    for lang in (langs or ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']):
        v = got.get(lang, '')
        w('%-5s: %s' % (lang, v[:n]))
    w()

for i in (6, 28, 86, 174):
    show(i)
show(175, ['de', 'en'] if False else ['de', 'ja', 'ko'])
w('### [175] tails (all langs)')
r = [x for x in rows if x['idx'] == 175][0]
for lang in ('ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW'):
    w('%-5s tail: ...%s' % (lang, d[r['en']][lang][-40:]))
w()
w('### [86] tails')
r = [x for x in rows if x['idx'] == 86][0]
for lang in ('es', 'fr', 'pt'):
    w('%-5s tail: ...%s' % (lang, d[r['en']][lang][-45:]))

io.open(r'E:\破甲工作区\.tmp\32a8\evidence.txt', 'w', encoding='utf-8', newline='\n').write('\n'.join(L) + '\n')
print('wrote', len(L))
