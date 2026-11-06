# -*- coding: utf-8 -*-
"""复用译文质检：空值、破折号、数字集合差异、疑似未翻译、模板 token。"""
import json, io, os, re
TMP = r'E:\破甲工作区\.tmp'
rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
reuse = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-reuse.json'), encoding='utf-8')).items()}
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
NUM = re.compile(r'\d+(?:[.,]\d+)?')
BAD = '–—～〜'
TARGET = [i for i in sorted(rows) if cls[i] in ('A', 'C', 'D1')]
CJK = {'ja', 'ko', 'zh-CN', 'zh-TW'}
out = []
prob = {'empty': 0, 'dash': 0, 'num': 0, 'same_as_en': 0, 'latin_heavy': 0}
for lang in LANGS:
    for i in TARGET:
        h = reuse.get(i, {}).get(lang)
        if not h:
            continue
        en, v = rows[i]['en'], h['value']
        tag = []
        if not v.strip():
            tag.append('EMPTY'); prob['empty'] += 1
        if any(c in v for c in BAD):
            tag.append('DASH'); prob['dash'] += 1
        if i not in (1, 3, 4, 7, 20, 21) and sorted(NUM.findall(en)) != sorted(NUM.findall(v)):
            tag.append('NUM'); prob['num'] += 1
        if v.strip() == en.strip():
            tag.append('SAME'); prob['same_as_en'] += 1
        letters = [c for c in v if c.isalpha()]
        if lang in CJK and letters and sum(1 for c in letters if ord(c) < 0x250) > 0.5 * len(letters):
            tag.append('LATIN'); prob['latin_heavy'] += 1
        if tag:
            out.append('%-6s %3d %-8s | %s\n         EN=%r' % (lang, i, ','.join(tag), v[:90], en[:90]))
print('reuse_problems=%r' % prob)
print('---')
print('\n'.join(out[:80]))
io.open(os.path.join(TMP, '32a8-reuse-qa.txt'), 'w', encoding='utf-8', newline='\n').write(
    'reuse_problems=%r\n---\n' % prob + '\n'.join(out) + '\n')
print('total_flagged=%d' % len(out))
