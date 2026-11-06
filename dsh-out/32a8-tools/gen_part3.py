# -*- coding: utf-8 -*-
"""Generate i18n-part-3.csv (ko/nl/pt, languages 7-9) from the authoritative
full 32a8-i18n.csv. Format identical to part-1: key-major, header en,kind,field,
lang,value,note; no BOM; LF only; trailing newline."""
import io, csv, json, sys

SRC = r'dsh-out/32a8-i18n.csv'
OUT = r'dsh-out/32a8-tools/i18n-part-3.csv'
KEY_ORDER = r'E:\破甲工作区\.tmp\32a8\key_order.json'
LANGS = ['ko', 'nl', 'pt']

# 1. load full i18n rows (key-major, 11 langs/key)
rows = list(csv.reader(io.open(SRC, encoding='utf-8', newline='')))
header, body = rows[0], rows[1:]
assert header == ['en', 'kind', 'field', 'lang', 'value', 'note'], header

# 2. filter to target langs, preserving key-major order
part = [r for r in body if r[3] in LANGS]

# 3. verify against key_order
key_order = json.load(io.open(KEY_ORDER, encoding='utf-8'))
seq_keys = []
for r in part:
    seq_keys.append(r[0])
seen = []
for k in seq_keys:
    if k not in seen:
        seen.append(k)
assert seen == key_order, ('key order mismatch', seen[:5], key_order[:5])

# 4. verify per-key lang sequence
from collections import defaultdict
g = defaultdict(list)
for r in part:
    g[r[0]].append(r[3])
bad = [k for k, ls in g.items() if ls != LANGS]
assert not bad, ('bad lang sequence', bad[:5])
assert len(g) == 196, len(g)
assert len(part) == 588, len(part)

# 5. ko template {N} fix already applied
ko_templates = [(r[0], r[4]) for r in part if r[5] == 'template' and r[3] == 'ko']
for en, val in ko_templates:
    if '134' in en:
        assert '{N}' in val, ('ko template not {N}', en, val)

# 6. write
buf = io.StringIO()
w = csv.writer(buf, lineterminator='\n')
w.writerow(header)
w.writerows(part)
out = buf.getvalue()
assert '\r' not in out, 'CR found'
assert out.endswith('\n'), 'no trailing newline'
with io.open(OUT, 'w', encoding='utf-8', newline='') as f:
    f.write(out)
print('wrote', OUT)
print('lines:', len(part) + 1, 'data rows:', len(part), 'keys:', len(g))
print('ko/nl/pt rows:', {k: len(v) for k, v in g.items().__class__.__call__(g) if False} or {l: sum(1 for r in part if r[3]==l) for l in LANGS})
