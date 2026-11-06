# -*- coding: utf-8 -*-
"""32a8 待译清单盘点（v2）：用真实列名，导出分组清单与 JSON。"""
import csv, io, json, os
from collections import Counter, defaultdict

REPO = r'E:\破甲工作区\packrivo-backup'
SRC = os.path.join(REPO, 'dsh-out', '32a8-missing-strings.csv')
TXT = r'E:\破甲工作区\.tmp\32a8-inventory.txt'
JS = r'E:\破甲工作区\.tmp\32a8-rows.json'

rows = []
with io.open(SRC, encoding='utf-8-sig', newline='') as f:
    for r in csv.DictReader(f):
        rows.append({
            'idx': len(rows) + 1,
            'en': r['英文原文'],
            'kind': r['类型'],
            'field': r['桶或字段'],
            'src': r['来源'],
            'pages': int(r['出现页面数']),
            'sample': r['示例页面'],
        })

io.open(JS, 'w', encoding='utf-8', newline='\n').write(json.dumps(rows, ensure_ascii=False, indent=1) + '\n')

def esc(s):
    return s.replace('\\', '\\\\').replace('\n', '\\n').replace('\r', '\\r').replace('\t', '\\t')

L = []
L.append('rows=%d' % len(rows))
L.append('pages=0 的行数=%d' % sum(1 for r in rows if r['pages'] == 0))
L.append('含 HTML 标签的行数=%d' % sum(1 for r in rows if '<' in r['en']))
L.append('多行文本的行数=%d' % sum(1 for r in rows if '\n' in r['en']))

groups = defaultdict(list)
for r in rows:
    if r['pages'] == 0:
        key = 'PAGES0'
    elif r['kind'] == 'dict':
        key = 'DICT'
    else:
        key = r['kind'].upper()
    groups[key].append(r)

for key in ('PAGES0', 'DICT', 'POST_CONTENT', 'POSTMETA', 'BLOG'):
    L.append('\n########## %s (%d) ##########' % (key, len(groups[key])))
    for r in groups[key]:
        L.append('[%3d] kind=%s field=%s pages=%d len=%d' % (r['idx'], r['kind'], r['field'], r['pages'], len(r['en'])))
        L.append('      EN: %s' % esc(r['en']))
        L.append('      SRC: %s' % esc(r['src'])[:200])
        L.append('      SAMPLE: %s' % r['sample'][:150])

io.open(TXT, 'w', encoding='utf-8', newline='\n').write('\n'.join(L) + '\n')
print('wrote %s lines=%d json=%s' % (TXT, len(L), JS))
