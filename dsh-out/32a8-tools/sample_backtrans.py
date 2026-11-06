# -*- coding: utf-8 -*-
"""打印回译抽检样本（ar/zh-CN/de 各 15 行，其余各 5 行），供人工回读核对。"""
import io, json, os, re

TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
PICK = {'ar': 15, 'zh-CN': 15, 'de': 15}
NUM = re.compile(r'\d+(?:[.,]\d+)?')

rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
final = {lang: {int(k): v for k, v in d.items()} for lang, d in
         json.load(io.open(os.path.join(BASE, 'final.json'), encoding='utf-8')).items()}
TARGET = [i for i in sorted(rows) if cls[i] in ('A', 'C', 'D1')]

out = []
for lang in LANGS:
    n = PICK.get(lang, 5)
    # 均匀取样：按 idx 顺序跨整个目标集，保证覆盖 A/C 与不同 field
    step = max(1, len(TARGET) // n)
    picks = TARGET[::step][:n]
    out.append('### %s (%d)' % (lang, len(picks)))
    for i in picks:
        v = final[lang][i]
        en = rows[i]['en']
        out.append('[%d] %s | %s' % (i, rows[i]['kind'], rows[i]['field']))
        out.append('   EN : %s' % en)
        out.append('   TR : %s' % v)
        out.append('   len %d -> %d (%.2f)  digits %s -> %s' % (
            len(en), len(v), len(v) / max(1, len(en)),
            sorted(NUM.findall(en)), sorted(NUM.findall(v))))
    out.append('')
txt = '\n'.join(out)
io.open(os.path.join(BASE, 'backtrans.txt'), 'w', encoding='utf-8', newline='\n').write(txt + '\n')
print('wrote %d lines' % len(out))
