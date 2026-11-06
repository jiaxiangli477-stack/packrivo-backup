# -*- coding: utf-8 -*-
"""核对 privacy 长句（row175）是否完整：原文/译文尾部 + 邮箱出现位置。"""
import csv, io

CSV_P = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n.csv'
rows = list(csv.reader(io.open(CSV_P, encoding='utf-8')))
body = rows[1:]
for r in body:
    en, lang, v = r[0], r[3], r[4]
    if not en.startswith('The brief builder prepares'):
        continue
    if lang in ('de', 'ja', 'ko', 'en'):
        pass
print('EN len=%d tail=%r' % (len(body[0][0]), body[0][0][-120:]) if False else '')
for r in body:
    if r[0].startswith('The brief builder prepares'):
        print('EN  len=%d' % len(r[0]))
        print('   head=%r' % r[0][:80])
        print('   tail=%r' % r[0][-140:])
        break
for r in body:
    en, lang, v = r[0], r[3], r[4]
    if not en.startswith('The brief builder prepares'):
        continue
    i = v.find('hello@')
    print('%-5s len=%d 邮箱位置=%s head=%r' % (lang, len(v), i, v[:60]))
    if lang in ('de', 'ja', 'ko'):
        print('     全文=%r' % v)
