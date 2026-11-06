# -*- coding: utf-8 -*-
"""Check a language JSON against the 34a requirements. Usage: verify_langs.py [lang ...]"""
import io, json, os, re, sys

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
LANGDIR = TOOLS + r'\lang'
CJK = {'ja', 'ko', 'zh-CN', 'zh-TW'}
TOKENS = ['MOQ', 'DDP', 'FOB', 'Packrivo']

kj = json.load(io.open(TOOLS + r'\keys.json', encoding='utf-8'))
SRC = {'SEG': kj['segments'], 'H1': kj['h1'], 'TITLE': kj['titles'], 'OTHER': kj['other']}


def nums(s):
    return re.findall(r'\d+(?:[.,]\d+)?', s)


def check(lang):
    errs, warns = [], []
    p = os.path.join(LANGDIR, lang + '.json')
    try:
        d = json.load(io.open(p, encoding='utf-8'))
    except Exception as e:
        return ['JSON parse error: %s' % e], []
    for sec, keys in SRC.items():
        got = d.get(sec, {})
        miss = [k for k in keys if k not in got]
        extra = [k for k in got if k not in keys]
        if miss:
            errs.append('%s missing %d keys: %s' % (sec, len(miss), ' | '.join(miss[:5])))
        if extra:
            errs.append('%s extra %d keys: %s' % (sec, len(extra), ' | '.join(list(extra)[:5])))
        for k in keys:
            v = (got.get(k) or '').strip()
            if not v:
                errs.append('%s empty: %s' % (sec, k[:90]))
                continue
            if v == k:
                errs.append('%s untranslated (identical to en): %s' % (sec, k[:90]))
            if nums(k) != nums(v):
                errs.append('%s digits %s != %s in: %s' % (sec, nums(v), nums(k), k[:80]))
            for t in TOKENS:
                if t in k and t not in v:
                    errs.append('%s lost token %s: %s' % (sec, t, k[:80]))
            if sec == 'TITLE':
                if not v.endswith('| Packrivo'):
                    errs.append('TITLE missing "| Packrivo": %s' % k[:80])
                lim = 44 if lang in CJK else 60
                if len(v) > lim:
                    warns.append('TITLE %d>%d: %s' % (len(v), lim, v))
            if sec == 'SEG' and len(k) > 40:
                lim = 1.35 if lang not in CJK else 1.0
                if len(v) > max(len(k) * lim, len(k) + 25) and lang in CJK:
                    warns.append('SEG long %d vs %d: %s' % (len(v), len(k), k[:60]))
    return errs, warns


def main():
    langs = sys.argv[1:] or kj['langs']
    bad = 0
    report = []
    for lang in langs:
        errs, warns = check(lang)
        report.append('##### %s  errors=%d warnings=%d' % (lang, len(errs), len(warns)))
        for e in errs:
            report.append('  ERR ' + e)
        for w in warns:
            report.append('  WARN ' + w)
        bad += len(errs)
    io.open(TOOLS + r'\verify-%s.txt' % (langs[0] if len(langs) == 1 else 'all'),
            'w', encoding='utf-8', newline='\n').write('\n'.join(report) + '\n')
    print('\n'.join(report[:60]))
    print('total_errors=%d' % bad)


if __name__ == '__main__':
    main()
