# -*- coding: utf-8 -*-
"""Build dsh-out/31b-i18n.csv from translations.py.

Output contract: UTF-8 without BOM, LF line endings, header
`en,bucket,lang,value,origin(new)`, one row per (string, language),
28 strings x 11 languages = 308 data rows.
"""
import csv
import io
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from translations import LANGS, ROWS  # noqa: E402

ROOT = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))
OUT = os.path.join(ROOT, 'dsh-out', '31b-i18n.csv')

BUCKETS = ('title', 'attr', 'block', 'text')

rows = []
for en, bucket, values in ROWS:
    assert bucket in BUCKETS, (en, bucket)
    for lang in LANGS:
        rows.append([en, bucket, lang, values[lang], 'new'])

with io.open(OUT, 'w', encoding='utf-8', newline='') as f:
    w = csv.writer(f, lineterminator='\n')
    w.writerow(['en', 'bucket', 'lang', 'value', 'origin(new)'])
    w.writerows(rows)

with io.open(OUT, 'rb') as f:
    head = f.read(3)
print('strings   : %d' % len(ROWS))
print('data rows : %d' % len(rows))
print('languages : %d' % len(LANGS))
print('bom       : %s' % ('present' if head == b'\xef\xbb\xbf' else 'absent'))
print('bytes     : %d' % os.path.getsize(OUT))
print('path      : %s' % OUT)
