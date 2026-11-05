# -*- coding: utf-8 -*-
"""Assemble dsh-out/33ab-i18n.csv from plan.json + tr1..tr4 tables, then self-check.

Writes: dsh-out/33ab-i18n.csv, dsh-out/33ab-tools/check-out.txt
Never touches the dictionaries, the container or the database.
"""
import csv
import json
import os
import re
import sys
from collections import Counter, OrderedDict

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.abspath(os.path.join(HERE, '..'))
ROOT = os.path.abspath(os.path.join(OUT, '..'))

sys.path.insert(0, HERE)
import tr1, tr2, tr3, tr4  # noqa: E402

LANGS = ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]

TR = {}
for mod in (tr1, tr2, tr3, tr4):
    for k, v in mod.TR.items():
        if k in TR:
            raise SystemExit('duplicate key across tables: %r' % k)
        if len(v) != len(LANGS):
            raise SystemExit('wrong value count for %r: %d' % (k, len(v)))
        TR[k] = v

PH = re.compile(r'%(?:\d+\$)?[sd]|\{[A-Za-z0-9_]+\}')
NUM = re.compile(r'\d+(?:[.,]\d+)*')
ENT = re.compile(r'&(?:amp|lt|gt|quot|#\d+|#x[0-9a-fA-F]+);')
HTML = re.compile(r'<[^>]+>')


def strip_ph(s):
    return PH.sub('', s)


def check_row(en, value, lang):
    errs = []
    en_ph = PH.findall(en)
    v_ph = PH.findall(value)
    if en_ph != v_ph:
        errs.append('placeholder %r -> %r' % (en_ph, v_ph))
    if en.count('%') != value.count('%'):
        errs.append('percent count %d -> %d' % (en.count('%'), value.count('%')))
    en_num = NUM.findall(strip_ph(en))
    v_num = NUM.findall(strip_ph(value))
    if Counter(en_num) != Counter(v_num):
        errs.append('number %r -> %r' % (en_num, v_num))
    if sorted(ENT.findall(en)) != sorted(ENT.findall(value)):
        errs.append('entity %r -> %r' % (ENT.findall(en), ENT.findall(value)))
    if sorted(HTML.findall(en)) != sorted(HTML.findall(value)):
        errs.append('markup %r -> %r' % (HTML.findall(en), HTML.findall(value)))
    if lang in ('zh-CN', 'zh-TW') and re.search(r'[\u4e00-\u9fff]', en):
        errs.append('english already contains CJK')
    return errs


def main():
    plan = json.load(open(os.path.join(HERE, 'plan.json'), encoding='utf-8'))
    new_ens = []
    for r in plan:
        if r['origin'] == 'new' and r['en'] not in new_ens:
            new_ens.append(r['en'])

    missing = [e for e in new_ens if e not in TR]
    extra = [k for k in TR if k not in new_ens]
    if missing or extra:
        for e in missing:
            print('MISSING %r' % e)
        for e in extra:
            print('EXTRA   %r' % e)
        raise SystemExit('translation table does not match the new-string list: %d missing / %d extra'
                         % (len(missing), len(extra)))

    rows = []
    empty = []
    ph_err = []
    for r in plan:
        en, lang = r['en'], r['lang']
        if r['origin'] == 'reused':
            value = r['value']
        else:
            value = TR[en][LANGS.index(lang)]
        if not value or not value.strip():
            empty.append((en, lang))
        for e in check_row(en, value, lang):
            ph_err.append((en, lang) + (e,))
        rows.append(OrderedDict([
            ('round', r['round']), ('en', en), ('bucket', r['bucket']),
            ('lang', lang), ('value', value), ('origin', r['origin']),
        ]))

    csv_path = os.path.join(OUT, '33ab-i18n.csv')
    with open(csv_path, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['round', 'en', 'bucket', 'lang', 'value', 'origin'],
                           lineterminator='\n')
        w.writeheader()
        w.writerows(rows)

    per_lang = OrderedDict((l, Counter()) for l in LANGS)
    for r in rows:
        per_lang[r['lang']][r['origin']] += 1
    bucket_counts = Counter()
    for r in rows:
        bucket_counts[(r['bucket'], r['origin'])] += 1

    lines = []
    lines.append('build.py raw output')
    lines.append('csv            %s' % csv_path)
    lines.append('rows           %d' % len(rows))
    lines.append('unique en      %d' % len({r['en'] for r in rows}))
    lines.append('new en written %d' % len(new_ens))
    lines.append('lines (wc -l)  %d' % (len(rows) + 1))
    lines.append('')
    lines.append('per language   new / reused / total')
    for l in LANGS:
        c = per_lang[l]
        lines.append('  %-6s %5d %5d %5d' % (l, c['new'], c['reused'], c['new'] + c['reused']))
    lines.append('')
    lines.append('placeholder / number / entity / markup mismatches   %d' % len(ph_err))
    lines.append('empty values                                        %d' % len(empty))
    lines.append('')
    lines.append('bucket x origin')
    for (b, o), n in sorted(bucket_counts.items()):
        lines.append('  %-6s %-7s %5d' % (b, o, n))
    if ph_err:
        lines.append('')
        lines.append('MISMATCHES')
        for en, lang, e in ph_err:
            lines.append('  [%s] %s :: %s | %s' % (lang, en, e, TR[en][LANGS.index(lang)] if en in TR else ''))
    if empty:
        lines.append('')
        lines.append('EMPTY')
        for en, lang in empty:
            lines.append('  [%s] %s' % (lang, en))

    text = '\n'.join(lines) + '\n'
    with open(os.path.join(HERE, 'check-out.txt'), 'w', encoding='utf-8', newline='\n') as f:
        f.write(text)
    sys.stdout.write(text)


if __name__ == '__main__':
    main()
