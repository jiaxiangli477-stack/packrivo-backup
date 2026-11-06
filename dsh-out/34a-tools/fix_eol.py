# -*- coding: utf-8 -*-
"""恢复 lang/*.json 的结尾换行（fix2_apply 的 json.dumps 掉了它），让 diff 只剩语义改动。"""
import io, subprocess

REPO = r'E:\破甲工作区\packrivo-backup-34a'
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
COL = {'ar', 'de', 'es', 'fr', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW'}
KR = {'ja'}

for lang in LANGS:
    rel = 'dsh-out/34a-tools/lang/%s.json' % lang
    full = REPO + '\\' + rel.replace('/', '\\')
    head = subprocess.check_output(['git', '-C', REPO, 'show', 'HEAD:' + rel])
    cur = io.open(full, 'rb').read()
    head_lf, cur_lf = head.endswith(b'\n'), cur.endswith(b'\n')
    if head_lf and not cur_lf:
        io.open(full, 'ab').write(b'\n')
        print('%-6s head_lf=%s cur_lf=%s -> appended LF' % (lang, head_lf, cur_lf))
    else:
        print('%-6s head_lf=%s cur_lf=%s -> untouched' % (lang, head_lf, cur_lf))
