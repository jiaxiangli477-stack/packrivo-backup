# -*- coding: utf-8 -*-
"""检查 C 类（模板/分类名）与 D1 在各语言的复用命中情况。"""
import json, io, os
TMP = r'E:\破甲工作区\.tmp'
rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
reuse = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-reuse.json'), encoding='utf-8')).items()}
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
C = [i for i in sorted(rows) if cls[i] in ('C', 'D1')]
for i in C:
    print('=== %3d cls=%s p%-2d %s | %r' % (i, cls[i], rows[i]['pages'], rows[i]['field'], rows[i]['en']))
    for lang in LANGS:
        h = reuse[i].get(lang)
        print('    %-6s %s' % (lang, ('%s  [%s]' % (h['value'], h['bucket'])) if h else '-- 待译'))
