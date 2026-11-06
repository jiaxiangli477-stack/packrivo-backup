# -*- coding: utf-8 -*-
"""Verify the report's tables against dsh-out/31b-i18n.csv.

Every 4-column table row (| # | en | value | back-translation |) is matched to
the CSV triple (en, lang, value); the language comes from the enclosing
heading. Counts must be 28 for ar/ja/zh-CN and 5 for the other eight.
"""
import csv
import io
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, '..', '..'))
CSV_PATH = os.path.join(ROOT, 'dsh-out', '31b-i18n.csv')
MD_PATH = os.path.join(ROOT, 'dsh-out', '31b-i18n-report.md')

HEAD_MAP = {
    '阿拉伯语': 'ar', '日语': 'ja', '简体中文': 'zh-CN',
    'de': 'de', 'es': 'es', 'fr': 'fr', 'it': 'it',
    'ko': 'ko', 'nl': 'nl', 'pt': 'pt', 'zh-TW': 'zh-TW',
}
EXPECT = {'ar': 28, 'ja': 28, 'zh-CN': 28,
          'de': 5, 'es': 5, 'fr': 5, 'it': 5,
          'ko': 5, 'nl': 5, 'pt': 5, 'zh-TW': 5}

with io.open(CSV_PATH, encoding='utf-8', newline='') as f:
    rows = list(csv.DictReader(f))
csv_map = {}
for r in rows:
    csv_map[(r['en'], r['lang'])] = r['value']
en_set = {r['en'] for r in rows}

SPLIT = re.compile(r'(?<!\\)\|')

text = io.open(MD_PATH, encoding='utf-8').read()
lines = text.split('\n')

lang = None
checked = {}
pairs = set()
errors = []
for ln, line in enumerate(lines, 1):
    s = line.strip()
    if s.startswith('#'):
        head = s.lstrip('#').strip()
        if head in HEAD_MAP:
            lang = HEAD_MAP[head]
        else:
            lang = None
            for key, code in HEAD_MAP.items():
                if key in head:
                    lang = code
                    break
        continue
    if not s.startswith('|'):
        continue
    cells = [c.strip() for c in SPLIT.split(s)[1:-1]]
    if len(cells) != 4:
        continue
    if set(cells[0]) <= set('-: '):
        continue
    en = cells[1].replace('\\|', '|').strip('`').strip()
    val = cells[2].replace('\\|', '|').strip()
    if en not in en_set:
        continue
    if lang is None:
        errors.append('line %d: table row without language context: %r' % (ln, en))
        continue
    got = csv_map.get((en, lang))
    if got is None:
        errors.append('line %d: (%r, %s) not in CSV' % (ln, en, lang))
        continue
    if got != val:
        errors.append('line %d: %s value mismatch for %r\n    md  : %r\n    csv : %r'
                      % (ln, lang, en, val, got))
    checked[lang] = checked.get(lang, 0) + 1
    pairs.add((en, lang))

print('report tables matched against CSV:')
for code in ['ar', 'ja', 'zh-CN', 'de', 'es', 'fr', 'it', 'ko', 'nl', 'pt', 'zh-TW']:
    n = checked.get(code, 0)
    flag = 'ok' if n == EXPECT[code] else 'MISMATCH'
    print('  %-6s %2d rows (expected %2d)  %s' % (code, n, EXPECT[code], flag))
    if n != EXPECT[code]:
        errors.append('%s has %d back-translated rows, expected %d'
                      % (code, n, EXPECT[code]))

# coverage: every ar/ja/zh-CN string of the CSV must be back-translated
for code in ['ar', 'ja', 'zh-CN']:
    missing = sorted(en for (en, lg) in csv_map if lg == code and (en, code) not in pairs)
    if missing:
        errors.append('%s missing back-translation for %d string(s): %r'
                      % (code, len(missing), missing[:5]))
print('  total matched rows: %d' % sum(checked.values()))

print('')
print('=== RESULT ===')
if errors:
    print('FAIL  %d error(s)' % len(errors))
    for e in errors:
        print('  - %s' % e)
    sys.exit(1)
print('PASS  report tables are byte-identical to the CSV values')
