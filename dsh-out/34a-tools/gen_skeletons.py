# -*- coding: utf-8 -*-
"""Generate one empty-value JSON skeleton per target language for the translators to fill."""
import io, json, os

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
LANGDIR = TOOLS + r'\lang'

kj = json.load(io.open(TOOLS + r'\keys.json', encoding='utf-8'))
ORDER = ['SEG', 'H1', 'TITLE', 'OTHER']
src = {'SEG': kj['segments'], 'H1': kj['h1'], 'TITLE': kj['titles'], 'OTHER': kj['other']}

os.makedirs(LANGDIR, exist_ok=True)
for lang in kj['langs']:
    p = os.path.join(LANGDIR, lang + '.json')
    if os.path.exists(p):
        # 已存在则只补缺失键，不覆盖已填内容
        cur = json.load(io.open(p, encoding='utf-8'))
    else:
        cur = {}
    out = {}
    added = 0
    for sec in ORDER:
        cur_sec = cur.get(sec, {})
        new = {}
        for k in src[sec]:
            if k in cur_sec:
                new[k] = cur_sec[k]
            else:
                new[k] = ''
                added += 1
        out[sec] = new
    io.open(p, 'w', encoding='utf-8', newline='\n').write(
        json.dumps(out, ensure_ascii=False, indent=1))
    print('%-6s SEG=%d H1=%d TITLE=%d OTHER=%d added=%d' % (
        lang, len(out['SEG']), len(out['H1']), len(out['TITLE']), len(out['OTHER']), added))
