# -*- coding: utf-8 -*-
"""32a8 待译清单盘点：列结构、类型/桶分布、每行长度，输出可读清单。"""
import csv, io, os, sys
from collections import Counter, OrderedDict

REPO = r'E:\破甲工作区\packrivo-backup'
SRC = os.path.join(REPO, 'dsh-out', '32a8-missing-strings.csv')
OUT = r'E:\破甲工作区\.tmp\32a8-inventory.txt'

rows = []
with io.open(SRC, encoding='utf-8-sig', newline='') as f:
    rdr = csv.DictReader(f)
    fields = rdr.fieldnames
    for r in rdr:
        rows.append(r)

lines = []
lines.append('fields=%s' % fields)
lines.append('rows=%d' % len(rows))
for f in fields:
    vals = [r.get(f, '') for r in rows]
    empt = sum(1 for v in vals if not (v or '').strip())
    lines.append('  col %-16s empty=%d  maxlen=%d' % (f, empt, max(len(v or '') for v in vals)))

kind_c = Counter((r.get('类型') or r.get('kind') or '').strip() for r in rows)
lines.append('\n== 类型分布 ==')
for k, v in kind_c.most_common():
    lines.append('  %-12s %d' % (k, v))

bucket_c = Counter((r.get('桶或字段') or r.get('field') or '').strip() for r in rows)
lines.append('\n== 桶/字段分布 ==')
for k, v in bucket_c.most_common():
    lines.append('  %-40s %d' % (k, v))

lines.append('\n== 全量行（idx | 类型 | 桶/字段 | 页数 | 英文长度 | 英文） ==')
for i, r in enumerate(rows, 1):
    kind = (r.get('类型') or '').strip()
    field = (r.get('桶或字段') or '').strip()
    pages = ''
    for cand in ('出现页面数', 'pages', '页数'):
        if cand in r:
            pages = (r.get(cand) or '').strip()
            break
    en = (r.get('英文') or r.get('en') or '').strip()
    lines.append('%3d | %-10s | %-28s | %-4s | %3d | %s' % (i, kind, field, pages, len(en), en))

io.open(OUT, 'w', encoding='utf-8', newline='\n').write('\n'.join(lines) + '\n')
print('wrote %s lines=%d' % (OUT, len(lines)))
