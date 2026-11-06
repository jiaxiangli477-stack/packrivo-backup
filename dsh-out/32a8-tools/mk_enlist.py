# -*- coding: utf-8 -*-
"""生成紧凑英文清单：idx|pages|kind|field|EN"""
import json, io

rows = json.load(io.open(r'E:\破甲工作区\.tmp\32a8-rows.json', encoding='utf-8'))
out = []
for r in rows:
    out.append('%3d|p%-2d|%s|%s|%s' % (r['idx'], r['pages'], r['kind'], r['field'], r['en']))
io.open(r'E:\破甲工作区\.tmp\32a8-en-list.txt', 'w', encoding='utf-8', newline='\n').write('\n'.join(out) + '\n')
print('rows=%d bytes=%d' % (len(out), sum(len(x) + 1 for x in out)))
