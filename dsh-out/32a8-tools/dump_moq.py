# -*- coding: utf-8 -*-
"""列出目标行中 en 含 MOQ 的行与各语种取值（截取 MOQ 附近窗口）。"""
import io, json, os, re

TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
final = {lang: {int(k): v for k, v in d.items()} for lang, d in
         json.load(io.open(os.path.join(BASE, 'final.json'), encoding='utf-8')).items()}
out = []
for i in sorted(final['de']):
    en = rows[i]['en']
    if 'MOQ' not in en:
        continue
    out.append('=== %d  %s  %s' % (i, rows[i]['kind'], rows[i]['field']))
    out.append('EN: ' + en)
    for lang in LANGS:
        v = final[lang][i]
        out.append(' %-5s %s %s' % (lang, 'OK ' if 'MOQ' in v else 'MISS', v))
txt = '\n'.join(out)
io.open(os.path.join(BASE, 'moq.txt'), 'w', encoding='utf-8', newline='\n').write(txt + '\n')
print('rows with MOQ =', sum(1 for i in final['de'] if 'MOQ' in rows[i]['en']))
print('chars =', len(txt))
