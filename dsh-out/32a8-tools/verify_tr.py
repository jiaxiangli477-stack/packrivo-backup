# -*- coding: utf-8 -*-
"""校验各语种 tr/<lang>.json 与 todo 的对齐、数字、破折号、空值、模板 token。"""
import json, io, os, re, sys
TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')
rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
NEW_EN = {'28': 'Multi-colour sheet-fed offset printing press for packaging'}
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
TEMPLATE = {1, 3, 4, 7, 20, 21}
NUM = re.compile(r'\d+(?:[.,]\d+)?')
BAD = '–—～〜'


def norm_digits(s):
    """去掉千分位后返回数字多重集，兼容 1.000 / 1,000 / 1 000 三种写法。"""
    out = []
    for m in NUM.findall(s):
        d = m.replace(',', '').replace('.', '').replace('\u00a0', '').replace(' ', '')
        out.append(d)
    return sorted(out)


def todo_idx(lang):
    p = os.path.join(BASE, 'todo', '%s.tsv' % lang)
    txt = io.open(p, encoding='utf-8').read()
    return [int(l.split('\t')[0]) for l in txt.split('\n') if l.strip()]


def main():
    total_bad = 0
    for lang in LANGS:
        p = os.path.join(BASE, 'tr', '%s.json' % lang)
        if not os.path.exists(p):
            print('%-6s MISSING %s' % (lang, p))
            total_bad += 1
            continue
        raw = io.open(p, 'rb').read()
        bom = raw.startswith(b'\xef\xbb\xbf')
        try:
            obj = json.loads(raw.decode('utf-8-sig'))
        except Exception as e:
            print('%-6s JSON_ERROR %s' % (lang, e))
            total_bad += 1
            continue
        want = todo_idx(lang)
        got = sorted(int(k) for k in obj)
        missing = sorted(set(want) - set(got))
        extra = sorted(set(got) - set(want))
        empty = [k for k, v in obj.items() if not isinstance(v, str) or not v.strip()]
        dash = {k: v for k, v in obj.items() if any(c in v for c in BAD)}
        num = []
        for k, v in obj.items():
            i = int(k)
            if i in TEMPLATE:
                continue
            en = NEW_EN.get(k, rows[i]['en'])
            if norm_digits(en) != norm_digits(v):
                num.append((k, norm_digits(en), norm_digits(v)))
        tmpl = {k: v for k, v in obj.items() if int(k) in TEMPLATE}
        tok = {k: v for k, v in tmpl.items() if '{n}' not in v}
        latin = []
        if lang in ('ja', 'ko', 'zh-CN', 'zh-TW'):
            for k, v in obj.items():
                ls = [c for c in v if c.isalpha()]
                if ls and sum(1 for c in ls if ord(c) < 0x250) > 0.5 * len(ls):
                    latin.append((k, v[:40]))
        bad = bool(missing or extra or empty or dash or num or bom or tok)
        total_bad += 1 if bad else 0
        print('%-6s n=%3d bom=%s miss=%s extra=%s empty=%s dash=%s num=%s tok=%s latin=%s' % (
            lang, len(obj), bom, missing[:5], extra[:5], empty[:3],
            list(dash.items())[:3], num[:3], tok, latin[:3]))
        if bad:
            io.open(os.path.join(BASE, 'verify-%s.txt' % lang), 'w', encoding='utf-8', newline='\n').write(
                'missing=%r\nextra=%r\nempty=%r\ndash=%r\nnum=%r\ntok=%r\nlatin=%r\n' % (
                    missing, extra, empty, sorted(dash.items()), num, tok, latin))
    print('bad_langs=%d' % total_bad)


main()
