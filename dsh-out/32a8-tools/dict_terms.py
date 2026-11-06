# -*- coding: utf-8 -*-
"""查词典里的术语口径：Send My Packaging Request / logo / Bookend。"""
import json, io, os

REPO = r'E:\破甲工作区\packrivo-backup'
LANGS = ['de', 'ja', 'ko', 'zh-CN', 'zh-TW']


def flat(o, out):
    if isinstance(o, dict):
        for k, v in o.items():
            if isinstance(v, str):
                out[k] = v
            else:
                flat(v, out)
    return out


for L in LANGS:
    p = os.path.join(REPO, 'deploy', 'i18n-merged', '%s-dict.json' % L)
    d = flat(json.load(io.open(p, encoding='utf-8')), {})
    print('==', L, 'keys=%d' % len(d))
    for kw in ('Send My Packaging Request', 'Bookend'):
        hit = [(k, v) for k, v in d.items() if kw in k]
        print('   %-28s %s' % (kw, hit[:2]))
    lo = [(k, v) for k, v in d.items() if 'logo' in v.lower() and k.lower().count('logo')]
    print('   logo_in_value=%d ex=%r' % (len(lo), lo[:2]))
