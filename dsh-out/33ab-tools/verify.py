# -*- coding: utf-8 -*-
"""Independent verification of dsh-out/33ab-i18n.csv (does not reuse build.py)."""
import csv
import io
import json
import os
import re
import sys
from collections import Counter

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.abspath(os.path.join(HERE, '..'))
CSV = os.path.join(OUT, '33ab-i18n.csv')
LANGS = ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]
EXPECT_HEADER = ['round', 'en', 'bucket', 'lang', 'value', 'origin']
PH = re.compile(r'%(?:\d+\$)?[sd]|\{[A-Za-z0-9_]+\}')
NUM = re.compile(r'\d+(?:[.,]\d+)*')
ENT = re.compile(r'&(?:amp|lt|gt|quot|#\d+|#x[0-9a-fA-F]+);')
TAG = re.compile(r'<[^>]+>')

fail = []


class _Tee(object):
    """Mirror stdout into verify-out.txt as UTF-8 (PowerShell redirect would emit UTF-16)."""

    def __init__(self, path):
        self.f = io.open(path, 'w', encoding='utf-8', newline='\n')
        self.stdout = sys.stdout

    def write(self, s):
        self.stdout.write(s)
        self.f.write(s)
        return len(s)

    def flush(self):
        self.stdout.flush()
        self.f.flush()


sys.stdout = _Tee(os.path.join(HERE, 'verify-out.txt'))


def chk(cond, msg):
    if not cond:
        fail.append(msg)


raw = open(CSV, 'rb').read()
print('bytes           %d' % len(raw))
print('bom             %s' % ('yes' if raw.startswith(b'\xef\xbb\xbf') else 'no'))
print('crlf            %d' % raw.count(b'\r\n'))
chk(not raw.startswith(b'\xef\xbb\xbf'), 'file has BOM')
chk(raw.count(b'\r\n') == 0, 'file has CRLF')

text = raw.decode('utf-8')
lines = text.split('\n')
chk(lines[-1] == '', 'file does not end with a newline')
data_lines = [l for l in lines[:-1]]
print('physical lines  %d (incl header)' % len(data_lines))

rows = list(csv.DictReader(io.StringIO(text, newline='')))
with open(CSV, encoding='utf-8', newline='') as f:
    header = next(csv.reader(f))
print('header          %s' % ','.join(header))
chk(header == EXPECT_HEADER, 'header mismatch: %r' % (header,))

print('data rows       %d' % len(rows))
chk(len(rows) == 1364, 'row count %d != 1364' % len(rows))

per = Counter(r['lang'] for r in rows)
for l in LANGS:
    chk(per[l] == 124, 'lang %s rows %d != 124' % (l, per[l]))
chk(set(per) == set(LANGS), 'unexpected langs: %r' % (set(per) - set(LANGS)))
print('langs           %s' % ' '.join('%s:%d' % (l, per[l]) for l in LANGS))

keys = Counter((r['en'], r['lang']) for r in rows)
dup = [k for k, v in keys.items() if v > 1]
chk(not dup, 'duplicate (en,lang) pairs: %r' % dup[:5])
print('unique pairs    %d  duplicates %d' % (len(keys), len(dup)))

ens = []
for r in rows:
    if r['en'] not in ens:
        ens.append(r['en'])
print('unique en       %d' % len(ens))
chk(len(ens) == 124, 'unique en %d != 124' % len(ens))
for en in ens:
    got = sorted(r['lang'] for r in rows if r['en'] == en)
    chk(got == sorted(LANGS), 'en %r missing langs: %r' % (en, set(LANGS) - set(got)))

empty = [r for r in rows if not r['value'].strip()]
chk(not empty, 'empty values: %d' % len(empty))
print('empty values    %d' % len(empty))

bad_bucket = {r['bucket'] for r in rows} - {'title', 'attr', 'block', 'text'}
bad_origin = {r['origin'] for r in rows} - {'reused', 'new'}
chk(not bad_bucket, 'bad bucket %r' % bad_bucket)
chk(not bad_origin, 'bad origin %r' % bad_origin)
print('buckets         %s' % Counter(r['bucket'] for r in rows))
print('origins         %s' % Counter(r['origin'] for r in rows))

plan = json.load(open(os.path.join(HERE, 'plan.json'), encoding='utf-8'))
pmeta = {}
for p in plan:
    pmeta.setdefault(p['en'], p)
chk(set(pmeta) == set(ens), 'en set differs from plan.json')

REF = r'E:\破甲工作区\packrivo-backup-32a\deploy\i18n-merged'
DICT = {}
for l in LANGS:
    DICT[l] = json.load(io.open(os.path.join(REF, '%s-dict.json' % l), encoding='utf-8'))

n_ph = n_num = n_ent = n_tag = n_pct = n_reuse = 0
for r in rows:
    en, lang, v = r['en'], r['lang'], r['value']
    if list(PH.findall(en)) != list(PH.findall(v)):
        n_ph += 1
        fail.append('PH %s/%s: %r vs %r' % (lang, en, PH.findall(en), PH.findall(v)))
    if en.count('%') != v.count('%'):
        n_pct += 1
        fail.append('percent %s/%s' % (lang, en))
    if Counter(NUM.findall(PH.sub(' ', en))) != Counter(NUM.findall(PH.sub(' ', v))):
        n_num += 1
        fail.append('NUM %s/%s: %r vs %r'
                    % (lang, en, NUM.findall(PH.sub(' ', en)), NUM.findall(PH.sub(' ', v))))
    if sorted(ENT.findall(en)) != sorted(ENT.findall(v)):
        n_ent += 1
        fail.append('ENT %s/%s: %r vs %r' % (lang, en, ENT.findall(en), ENT.findall(v)))
    if sorted(TAG.findall(en)) != sorted(TAG.findall(v)):
        n_tag += 1
        fail.append('TAG %s/%s: %r vs %r' % (lang, en, TAG.findall(en), TAG.findall(v)))
    p = pmeta[en]
    if r['round'] != p['round'] or r['bucket'] != p['bucket']:
        fail.append('meta drift %s/%s' % (lang, en))
    if r['origin'] == 'reused':
        n_reuse += 1
        cand = [DICT[lang].get(b, {}).get(en) for b in ('block', 'text', 'attr', 'title')]
        cand = [c for c in cand if c]
        if not cand:
            fail.append('reused but absent from 32a dict %s/%s' % (lang, en))
        elif v not in cand:
            fail.append('reused value drift %s/%s: %r not in %r' % (lang, en, v, cand))
    else:
        for b in ('block', 'text', 'attr', 'title'):
            hit = DICT[lang].get(b, {}).get(en)
            if hit == v:
                fail.append('marked new but identical to 32a %s/%s bucket %s' % (lang, en, b))

print('ph mismatches   %d' % n_ph)
print('percent mism    %d' % n_pct)
print('num mismatches  %d' % n_num)
print('entity mism     %d' % n_ent)
print('markup mism     %d' % n_tag)
print('reused rows     %d (values taken verbatim from 32a dictionary)' % n_reuse)

# invariants that must show up verbatim in every language
for token in ['250', '5–10', '7–30', '2 to 120', 'hello@packrivo.com']:
    hits = [r for r in rows if token in r['en']]
    print('token %-20s en rows %3d' % (token, len(hits)))
    for r in hits:
        if token not in r['value'] and token.split()[0] not in r['value']:
            fail.append('token %r lost in %s/%s' % (token, r['lang'], r['en']))

print('')
if fail:
    print('FAIL %d' % len(fail))
    for m in fail[:40]:
        print('  ' + m)
    sys.exit(1)
print('PASS all checks')
