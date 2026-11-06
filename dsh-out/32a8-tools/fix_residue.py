# -*- coding: utf-8 -*-
"""残留修补：并入 overrides.json（构建时最高优先级）。

修 3 类机械缺陷：
 1. es/fr row86 句末多挂一个冠词 "La"（源句已是句号结尾）
 2. pt  row86 句末多挂一个冠词 "O"
 3. de  row175 引用的按钮名仍是英文；词典口径为 „Meine Verpackungsanfrage senden“
"""
import csv, io, json, os, sys

BASE = r'E:\破甲工作区\.tmp\32a8'
CSV_P = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n.csv'
OVP = os.path.join(BASE, 'overrides.json')

rows = json.load(io.open(r'E:\破甲工作区\.tmp\32a8-rows.json', encoding='utf-8'))
R86 = R175 = None
i86 = i175 = -1
for r in rows:
    if r['en'].startswith('as a direct comparison'):
        i86, R86 = r['idx'], r['en']
    elif r['en'].startswith('The brief builder prepares'):
        i175, R175 = r['idx'], r['en']
if i86 < 0 or i175 < 0:
    print('MISS 定位失败 i86=%d i175=%d' % (i86, i175))
    sys.exit(1)
print('idx86=%d idx175=%d' % (i86, i175))

cur = {}
if os.path.exists(OVP):
    cur = json.load(io.open(OVP, encoding='utf-8'))

cur = {lang: {int(k): v for k, v in d.items()} for lang, d in cur.items()}
n = 0


def put(lang, i, newv, why):
    global n
    cur.setdefault(lang, {})[i] = newv
    n += 1
    log.append('%-5s row%-4d %s' % (lang, i, why))


log = []
val = {}
for r in csv.reader(io.open(CSV_P, encoding='utf-8')):
    if r[0] == 'en':
        continue
    val[(r[3], r[0])] = r[4]

CASES = [
    ('es', i86, ' La', '', '句末冠词 La'),
    ('fr', i86, ' La', '', '句末冠词 La'),
    ('pt', i86, ' O', '', '句末冠词 O'),
    ('de', i175, '\u201eSend My Packaging Request\u201c',
     '\u201eMeine Verpackungsanfrage senden\u201c', '按钮名按词典本地化'),
]
for lang, i, old, new, why in CASES:
    src = val.get((lang, R86 if i == i86 else R175), '')
    if not src:
        print('MISS value %s row%d' % (lang, i))
        sys.exit(1)
    bad = (not src.endswith(old)) if old.startswith(' ') else (old not in src)
    if bad:
        print('MISS anchor %s row%d %r | tail=%r' % (lang, i, old, src[-30:]))
        sys.exit(1)
    v = src[:-len(old)] if old.startswith(' ') else src.replace(old, new)
    put(lang, i, v, why + ' | %r -> %r' % (old, new if new else ''))

io.open(OVP, 'w', encoding='utf-8', newline='\n').write(
    json.dumps({k: {str(i): v for i, v in d.items()} for k, d in cur.items()},
               ensure_ascii=False, indent=0, sort_keys=True))
io.open(os.path.join(BASE, 'residue-patch.log'), 'w', encoding='utf-8', newline='\n').write(
    '\n'.join(log) + '\n')
print('residue overrides=%d 处' % n)
print('\n'.join(log))
print('overrides.json 语言数=%d 总条数=%d' % (len(cur), sum(len(d) for d in cur.values())))
