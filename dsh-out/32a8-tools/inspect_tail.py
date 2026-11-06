# -*- coding: utf-8 -*-
import json, io, os
TMP = r'E:\破甲工作区\.tmp'
rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
for i in (84, 92, 175, 174, 18, 82):
    if i not in rows:
        print(i, 'MISSING')
        continue
    en = rows[i]['en']
    print('%3d len=%4d tail=%r' % (i, len(en), en[-40:]))
    print('     head=%r' % en[:40])
print()
print('len>=700:', [(r['idx'], len(r['en'])) for r in rows.values() if len(r['en']) >= 620])
