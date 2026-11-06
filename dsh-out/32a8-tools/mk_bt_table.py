# -*- coding: utf-8 -*-
"""把 backtrans.txt 转成报告用的 markdown 表（每语种一张）。"""
import io, re

SRC = r'E:\破甲工作区\.tmp\32a8\backtrans.txt'
OUT = r'E:\破甲工作区\.tmp\32a8\bt-table.md'

lang = None
cur = None
blocks = []   # [(lang, [rowdict])]
for raw in io.open(SRC, encoding='utf-8'):
    line = raw.rstrip('\n')
    m = re.match(r'^### (\S+) \((\d+)\)$', line)
    if m:
        lang = m.group(1)
        cur = []
        blocks.append((lang, cur))
        continue
    m = re.match(r'^\[(\d+)\] ([^|]+)\| (.+)$', line)
    if m:
        cur.append({'idx': int(m.group(1)), 'kind': m.group(2).strip(),
                    'field': m.group(3).strip(), 'en': '', 'tr': '', 'len': ''})
        continue
    m = re.match(r'^   EN : (.*)$', line)
    if m and cur:
        cur[-1]['en'] = m.group(1)
        continue
    m = re.match(r'^   TR : (.*)$', line)
    if m and cur:
        cur[-1]['tr'] = m.group(1)
        continue
    m = re.match(r'^   len (\d+) -> (\d+) \(([\d.]+)\)  digits \[(.*?)\] -> \[(.*?)\]$', line)
    if m and cur:
        cur[-1]['len'] = '%s->%s (%s)' % (m.group(1), m.group(2), m.group(3))
        cur[-1]['d'] = (m.group(4), m.group(5))
        continue

def cut(s, n):
    s = s.replace('|', '\\|')
    return s if len(s) <= n else s[:n].rstrip() + '…'

L = []
for lang, rows in blocks:
    L.append('#### %s（%d 行）' % (lang, len(rows)))
    L.append('')
    L.append('| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |')
    L.append('| --- | --- | --- | --- | --- | --- |')
    for r in rows:
        d = r.get('d', ('', ''))
        dag = '一致' if d[0] == d[1] else ('%s→%s' % (d[0], d[1]) if (d[0] or d[1]) else '无')
        L.append('| %d | %s | %s | %s | %s | %s |' % (r['idx'], r['field'], cut(r['en'], 62),
                                                      cut(r['tr'], 62), r['len'], dag))
    L.append('')

io.open(OUT, 'w', encoding='utf-8', newline='\n').write('\n'.join(L))
print('wrote', OUT, len(L), 'lines')
