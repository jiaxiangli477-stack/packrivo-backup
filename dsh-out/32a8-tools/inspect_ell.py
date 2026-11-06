# -*- coding: utf-8 -*-
"""核查 en 尾部字符（U+2026 省略号 vs ...），并尝试从词典恢复全文。"""
import json, io, os

TMP = r'E:\破甲工作区\.tmp'
MAIN = r'E:\破甲工作区\packrivo-backup\deploy\i18n-merged'
rows = json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
ell = [r for r in rows if r['en'].rstrip().endswith('\u2026')]
print('尾部 U+2026 的行数=%d' % len(ell))
de = json.load(io.open(os.path.join(MAIN, 'de-dict.json'), encoding='utf-8'))
dekeys = set()
for b, m in de.items():
    if isinstance(m, dict):
        dekeys |= set(m.keys())
for r in ell:
    tail = r['en'][-20:]
    stem = r['en'].rstrip()[:-1].rstrip()
    hits = [k for k in dekeys if k.startswith(stem) and len(k) > len(stem)]
    print('%3d cls=%s len=%3d p%-2d field=%s' % (r['idx'], cls[r['idx']], len(r['en']), r['pages'], r['field']))
    print('     TAIL=%r hits=%d' % (tail, len(hits)))
    if hits:
        print('     FULL=%r' % sorted(hits, key=len)[0][:300])
