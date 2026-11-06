# -*- coding: utf-8 -*-
"""为 32a8 的 196 条待译行，从 main/34a 现有词典与 34a CSV 中提取可复用译文（精确/归一匹配）。"""
import json, io, os, csv, re

TMP = r'E:\破甲工作区\.tmp'
MAIN = r'E:\破甲工作区\packrivo-backup\deploy\i18n-merged'
OLD = r'E:\破甲工作区\packrivo-backup-34a\deploy\i18n-merged'
CSV34A = r'E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-i18n.csv'
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']

rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
TARGET = [i for i in sorted(rows) if cls[i] in ('A', 'C', 'D1')]

def norm(s):
    s = s.strip().lower()
    s = re.sub(r'[\s\u00a0]+', ' ', s)
    s = re.sub(r'[.,;:!?\u2014\u2013-]+$', '', s)
    return s

def load_dict(path):
    d = json.load(io.open(path, encoding='utf-8'))
    out = {}
    for bucket, m in d.items():
        if not isinstance(m, dict):
            continue
        for en, tr in m.items():
            if isinstance(tr, str) and tr.strip():
                out.setdefault(en, (bucket, tr))
    return out

reuse = {}
stats = {'exact': 0, 'norm': 0, 'none': 0}
for i in TARGET:
    reuse[i] = {}
for lang in LANGS:
    dm = load_dict(os.path.join(MAIN, '%s-dict.json' % lang))
    do = load_dict(os.path.join(OLD, '%s-dict.json' % lang))
    pool = {}
    pool.update({norm(k): v for k, v in do.items()})
    pool.update({norm(k): v for k, v in dm.items()})
    exact = {}
    for k, v in do.items():
        exact.setdefault(k, v)
    for k, v in dm.items():
        exact.setdefault(k, v)
    csvmap = {}
    with io.open(CSV34A, encoding='utf-8', newline='') as f:
        for r in csv.DictReader(f):
            if r['lang'] == lang and r['value'].strip():
                csvmap.setdefault(r['en'].strip(), (r['bucket'], r['value']))
    for i in TARGET:
        en = rows[i]['en']
        hit = exact.get(en) or csvmap.get(en)
        org = 'exact'
        if not hit:
            hit = pool.get(norm(en))
            org = 'norm'
        if hit:
            reuse[i][lang] = {'bucket': hit[0], 'value': hit[1], 'origin': org}
            stats[org] += 1
        else:
            stats['none'] += 1

io.open(os.path.join(TMP, '32a8-reuse.json'), 'w', encoding='utf-8', newline='\n').write(
    json.dumps(reuse, ensure_ascii=False, indent=1) + '\n')

per_row = {i: len(reuse[i]) for i in TARGET}
full = sum(1 for i in TARGET if per_row[i] == 11)
part = sum(1 for i in TARGET if 0 < per_row[i] < 11)
none = sum(1 for i in TARGET if per_row[i] == 0)
lines = ['target_rows=%d' % len(TARGET), 'hit_stats exact=%d norm=%d none=%d' % (stats['exact'], stats['norm'], stats['none']),
         'rows_with_all_11=%d rows_partial=%d rows_none=%d' % (full, part, none), '',
         '== 已有译文可复用的行 ==']
for i in TARGET:
    if per_row[i]:
        lines.append('%3d langs=%2d | %s' % (i, per_row[i], rows[i]['en'][:90]))
io.open(os.path.join(TMP, '32a8-reuse.txt'), 'w', encoding='utf-8', newline='\n').write('\n'.join(lines) + '\n')
print('\n'.join(lines[:4]))
