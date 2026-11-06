# -*- coding: utf-8 -*-
"""Compose dsh-out/34a-i18n.csv from the per-language JSON files."""
import csv, io, json, os, re, sys

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
LANGDIR = TOOLS + r'\lang'
OUTCSV = ROOT + r'\dsh-out\34a-i18n.csv'
NO_SPACE_JOIN = {'ja', 'zh-CN', 'zh-TW'}
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS = ('title','attr','block','text')

kj = json.load(io.open(TOOLS + r'\keys.json', encoding='utf-8'))
KEYS, REQ, FINAL = kj['keys'], kj['req'], kj['final']
# 权威值 → en 键（inventory 已把 CSV 的 5 条 meta 换成权威值）
AUTH = set(FINAL.values())

# 已存在的词典条目（origin=reused 用）
dicts = {l: json.loads(io.open(ROOT + r'\deploy\i18n-merged\%s-dict.json' % l,
                               encoding='utf-8-sig').read()) for l in LANGS}


def bucket_for(key, lang, d):
    """按闸门分组选择写入桶。"""
    groups = REQ[key]
    for g in groups:
        for b in g:
            if isinstance(d[b].get(key), str) and d[b][key].strip():
                return b, d[b][key]          # 已存在 → 复用
    for g in groups:
        if g[0] == 'title':
            return 'title', None
        if g[0] == 'attr':
            return 'attr', None
        return ('text', None)                # block/text 组优先 text
    raise SystemExit('no bucket for ' + key)


def main():
    segs = {l: json.load(io.open(os.path.join(LANGDIR, l + '.json'), encoding='utf-8'))
            for l in LANGS}
    rows, problems = [], []
    for key in KEYS:
        parts = [p.strip() for p in re.split(r'(?<=\.)\s+', key) if p.strip()]
        for lang in LANGS:
            d = segs[lang]
            bucket, reuse = bucket_for(key, lang, dicts[lang])
            if reuse is not None:
                value, origin = reuse, 'reused'
            else:
                origin = 'new'
                if bucket == 'attr':
                    miss = [p for p in parts if not (d['SEG'].get(p) or '').strip()]
                    if miss:
                        problems.append('%s attr missing segments: %s' % (lang, miss))
                        value = ''
                    else:
                        joiner = '' if lang in NO_SPACE_JOIN else ' '
                        value = joiner.join(d['SEG'][p].strip() for p in parts)
                elif bucket == 'title':
                    value = (d['TITLE'].get(key) or '').strip()
                else:
                    value = (d['H1'].get(key) or d['OTHER'].get(key) or '').strip()
                if not value:
                    problems.append('%s %s empty value: %s' % (lang, bucket, key[:80]))
            rows.append({'en': key, 'bucket': bucket, 'lang': lang,
                         'value': value, 'origin': origin})

    if problems:
        print('PROBLEMS %d' % len(problems))
        for p in problems[:40]:
            print('  ' + p)
        if '--force' not in sys.argv:
            print('abort (use --force to write anyway)')
            return 1

    with io.open(OUTCSV, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['en','bucket','lang','value','origin'],
                           lineterminator='\n')
        w.writeheader()
        for r in rows:
            w.writerow(r)
    print('wrote %s rows=%d keys=%d' % (OUTCSV, len(rows), len(KEYS)))
    from collections import Counter
    print('bucket:', Counter(r['bucket'] for r in rows))
    print('origin:', Counter(r['origin'] for r in rows))
    print('empty:', sum(1 for r in rows if not r['value'].strip()))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
