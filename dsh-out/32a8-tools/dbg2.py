# -*- coding: utf-8 -*-
import csv, io
CSV_P = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n.csv'
rows = list(csv.reader(io.open(CSV_P, encoding='utf-8')))
body = rows[1:]
first = body[:196]
print('len(body)=%d len(first)=%d' % (len(body), len(first)))
hit = [(i, r[0][:40]) for i, r in enumerate(first) if 'direct comparison' in r[0]]
print('hits=', hit)
print('i=86 ->', repr(body[86][0][:60]), body[86][3])
print('i=175 ->', repr(body[175][0][:60]), body[175][3])
