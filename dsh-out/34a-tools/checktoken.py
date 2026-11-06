# -*- coding: utf-8 -*-
"""逐条检查必须原样保留的拉丁令牌与数字区间写法。"""
import io, json

T = r'E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-tools\lang'
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
TOKENS = ['MOQ', 'DDP', 'FOB', 'Packrivo', '5-10', '7-30', '8-15', '1,000']
BAD_DASH = ['\u301c', '\uff5e', '\u2013', '\u2014', '\u2012', '\u2212']

total = 0
for lang in LANGS:
    try:
        d = json.load(io.open(T + '\\' + lang + '.json', encoding='utf-8'))
    except Exception as exc:
        print(lang, 'LOAD FAIL', exc)
        continue
    errs = []
    for sec in ('SEG', 'H1', 'TITLE', 'OTHER'):
        for en, tr in d.get(sec, {}).items():
            if not (tr or '').strip():
                errs.append(('EMPTY', sec, en))
                continue
            for tok in TOKENS:
                if tok in en and tok not in tr:
                    errs.append(('LOST ' + tok, sec, en))
            for ch in BAD_DASH:
                if ch in tr:
                    errs.append(('DASH ' + hex(ord(ch)), sec, tr[:60]))
    total += len(errs)
    print('##### %-6s token_errors=%d' % (lang, len(errs)))
    for e in errs[:12]:
        print('   ', e[0], '|', e[2][:80])
print('total_token_errors=%d' % total)
