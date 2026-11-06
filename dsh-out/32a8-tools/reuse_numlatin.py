# -*- coding: utf-8 -*-
"""打印复用译文里数字不一致 / 疑似拉丁未翻译的条目，逐条人工核对。"""
import json, io, os, re
TMP = r'E:\破甲工作区\.tmp'
rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
reuse = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-reuse.json'), encoding='utf-8')).items()}
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
NUM = re.compile(r'\d+(?:[.,]\d+)?')
TARGET = [i for i in sorted(rows) if cls[i] in ('A', 'C', 'D1')]
CJK = {'ja', 'ko', 'zh-CN', 'zh-TW'}
lines = []
for lang in LANGS:
    for i in TARGET:
        h = reuse.get(i, {}).get(lang)
        if not h:
            continue
        en, v = rows[i]['en'], h['value']
        bad = []
        if i not in (1, 3, 4, 7, 20, 21) and sorted(NUM.findall(en)) != sorted(NUM.findall(v)):
            bad.append('NUM en=%s v=%s' % (NUM.findall(en), NUM.findall(v)))
        letters = [c for c in v if c.isalpha()]
        if lang in CJK and letters and sum(1 for c in letters if ord(c) < 0x250) > 0.5 * len(letters):
            bad.append('LATIN')
        if bad:
            lines.append('%-6s %3d %s' % (lang, i, ' | '.join(bad)))
            lines.append('     EN= %s' % en)
            lines.append('     TR= %s' % v)
            lines.append('')
p = os.path.join(TMP, '32a8-reuse-numlatin.txt')
io.open(p, 'w', encoding='utf-8', newline='\n').write('\n'.join(lines))
print('lines=%d' % len(lines))
