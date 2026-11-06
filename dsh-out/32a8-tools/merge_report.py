# -*- coding: utf-8 -*-
import io, os

T = r'E:\破甲工作区\.tmp\32a8'
OUT = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n-report.md'
parts = [os.path.join(T, n) for n in ('report-head.md', 'bt-table.md', 'report-tail.md')]
buf = []
for p in parts:
    s = io.open(p, encoding='utf-8').read()
    if not s.endswith('\n'):
        s += '\n'
    buf.append(s)
text = '\n'.join(buf)
# 段落之间不留三连空行
while '\n\n\n\n' in text:
    text = text.replace('\n\n\n\n', '\n\n\n')
io.open(OUT, 'w', encoding='utf-8', newline='\n').write(text)
n = text.count('\n')
print('wrote', OUT, 'lines=%d chars=%d' % (n, len(text)))
