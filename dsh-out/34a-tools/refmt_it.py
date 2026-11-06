# -*- coding: utf-8 -*-
"""把 lang/it.json 重排回 HEAD 的 indent=2 风格，只留真正的语义改动（新增一条 SEG）。"""
import io, json, subprocess, sys

REPO = r'E:\破甲工作区\packrivo-backup-34a'
REL = 'dsh-out/34a-tools/lang/it.json'
FULL = REPO + '\\' + REL.replace('/', '\\')

head = subprocess.check_output(['git', '-C', REPO, 'show', 'HEAD:' + REL])
cur_raw = io.open(FULL, 'rb').read()
cur = json.loads(cur_raw.decode('utf-8'))
old = json.loads(head.decode('utf-8'))

out = []
out.append('cur_sections=%s' % sorted(cur))
for sec in ('SEG', 'H1', 'TITLE', 'OTHER'):
    a, b = old.get(sec, {}), cur.get(sec, {})
    added = [k for k in b if k not in a]
    removed = [k for k in a if k not in b]
    changed = [k for k in a if k in b and a[k] != b[k]]
    out.append('%s old=%d new=%d added=%d removed=%d changed=%d' % (sec, len(a), len(b), len(added), len(removed), len(changed)))
    for k in added:
        out.append('  + %r -> %r' % (k, b[k]))
    for k in removed:
        out.append('  - %r' % k)
    for k in changed:
        out.append('  ~ %r : %r -> %r' % (k, a[k], b[k]))
    # 键顺序是否与旧文件一致（新增项插在中间就会显形）
    old_order = [k for k in a]
    new_order = [k for k in b if k in a]
    out.append('  order_preserved=%s' % (old_order == new_order))

out.append('head_ends_lf=%s cur_ends_lf=%s' % (head.endswith(b'\n'), cur_raw.endswith(b'\n')))

# 只有一处新增时才重排
if len([1 for sec in ('SEG', 'H1', 'TITLE', 'OTHER')
        for k in cur.get(sec, {}) if k not in old.get(sec, {})]) == 0:
    out.append('nothing added -> abort')
    print('\n'.join(out))
    sys.exit(1)

text = json.dumps(cur, ensure_ascii=False, indent=2)
if head.endswith(b'\n'):
    text += '\n'
io.open(FULL, 'w', encoding='utf-8', newline='\n').write(text)
out.append('rewritten with indent=2, bytes=%d' % len(io.open(FULL, 'rb').read()))

# 语义回读校验
back = json.loads(io.open(FULL, encoding='utf-8').read())
out.append('semantics_equal_after_rewrite=%s' % (back == cur))

print('\n'.join(out))
