# -*- coding: utf-8 -*-
"""How much of the 337 deliverable keys already exists in the live dictionaries."""
import io, json

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS = ('title','attr','block','text')

kj = json.load(io.open(TOOLS + r'\keys.json', encoding='utf-8'))
keys = kj['keys']
req = kj['req']

dicts = {}
for lang in LANGS:
    dicts[lang] = json.loads(io.open(
        ROOT + r'\deploy\i18n-merged\%s-dict.json' % lang, encoding='utf-8-sig').read())

lines = []
add = lines.append
for lang in LANGS:
    d = dicts[lang]
    hit = []
    for k in keys:
        groups = req[k]
        if all(any(isinstance(d[b].get(k), str) and d[b][k].strip() for b in g) for g in groups):
            hit.append(k)
    add('%-6s keys=%d already_satisfied=%d' % (lang, len(keys), len(hit)))
    for k in hit[:40]:
        add('    hit: ' + k[:100])

# 字典规模
add('')
for lang in LANGS:
    d = dicts[lang]
    add('%-6s dict sizes: %s' % (lang, ' '.join('%s=%d' % (b, len(d[b])) for b in BUCKETS)))

io.open(TOOLS + r'\overlap.txt','w',encoding='utf-8',newline='\n').write('\n'.join(lines)+'\n')
print('\n'.join(lines[:13]))
