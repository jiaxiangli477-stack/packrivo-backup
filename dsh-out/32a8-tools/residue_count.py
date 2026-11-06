# -*- coding: utf-8 -*-
import io, re, collections

sec = ''
c = collections.Counter()
cj = collections.Counter()
for line in io.open(r'E:\破甲工作区\.tmp\32a8\residue.txt', encoding='utf-8'):
    s = line.rstrip('\n')
    if s.startswith('=='):
        sec = s
        continue
    if not s.strip():
        continue
    m = re.match(r'^([A-Za-z-]+) ', s)
    if not m:
        continue
    if '\u60ac\u5782' in sec:
        c[m.group(1)] += 1
    elif '\u62c9\u4e01' in sec:
        cj[m.group(1)] += 1
out = ['dangle total=%d %s' % (sum(c.values()), dict(sorted(c.items()))),
       'cjk-latin total=%d %s' % (sum(cj.values()), dict(sorted(cj.items())))]
io.open(r'E:\破甲工作区\.tmp\32a8\rescount.txt', 'w', encoding='utf-8', newline='\n').write('\n'.join(out) + '\n')
print('\n'.join(out))
