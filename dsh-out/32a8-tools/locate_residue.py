# -*- coding: utf-8 -*-
"""定位待修残留：row86 句尾多余冠词、row175 里引用的按钮名是否本地化。"""
import csv, io

CSV_P = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n.csv'
rows = list(csv.reader(io.open(CSV_P, encoding='utf-8')))
body = rows[1:]
for r in body:
    en, lang, v = r[0], r[3], r[4]
    if en.startswith('as a direct comparison'):
        print('R86 %-5s ...%r' % (lang, v[-70:]))
print('-' * 60)
for r in body:
    en, lang, v = r[0], r[3], r[4]
    if en.startswith('The brief builder prepares'):
        i = v.find('Send My')
        print('R175 %-5s len=%d tail=%r' % (lang, len(v), v[-40:]))
        if i >= 0:
            print('       引号内英文保持: %r' % v[max(0, i - 20):i + 40])
