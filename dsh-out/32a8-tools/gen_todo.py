# -*- coding: utf-8 -*-
"""为每种语言生成待译清单 todo/<lang>.tsv，并给出发音锚点 anchors/<lang>.txt。"""
import json, io, os
from collections import defaultdict

TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
for sub in ('todo', 'tr', 'anchors'):
    os.makedirs(os.path.join(BASE, sub), exist_ok=True)

rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
reuse = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-reuse.json'), encoding='utf-8')).items()}

# D1 用新英文
NEW_EN = {'28': 'Multi-colour sheet-fed offset printing press for packaging'}

def src_en(i):
    return NEW_EN.get(str(i), rows[i]['en'])

summary = []
for lang in LANGS:
    todo = []
    for i in sorted(rows):
        if cls[i] not in ('A', 'C', 'D1'):
            continue
        if lang in reuse.get(i, {}):
            continue
        todo.append(i)
    # anchors：同字段族里已有译文的示例
    fields = {rows[i]['field'] for i in todo}
    anchors = []
    for i in sorted(rows):
        if cls[i] not in ('A', 'C', 'D1'):
            continue
        if lang not in reuse.get(i, {}):
            continue
        if rows[i]['field'] in fields:
            anchors.append((rows[i]['field'], rows[i]['en'], reuse[i][lang]['value']))
    anchors = anchors[:30]
    with io.open(os.path.join(BASE, 'todo', '%s.tsv' % lang), 'w', encoding='utf-8', newline='\n') as f:
        for i in todo:
            f.write('%d\t%s\t%s\t%d\t%s\n' % (i, cls[i], rows[i]['field'], rows[i]['pages'], src_en(i)))
    with io.open(os.path.join(BASE, 'anchors', '%s.txt' % lang), 'w', encoding='utf-8', newline='\n') as f:
        for fld, en, tr in anchors:
            f.write('[%s]\nEN: %s\nTR: %s\n\n' % (fld, en, tr))
    summary.append('%-6s todo=%3d anchors=%2d' % (lang, len(todo), len(anchors)))

io.open(os.path.join(BASE, 'todo-summary.txt'), 'w', encoding='utf-8', newline='\n').write('\n'.join(summary) + '\n')
print('\n'.join(summary))
print('total_cells=%d' % sum(int(x.split('todo=')[1].split()[0]) for x in summary))
