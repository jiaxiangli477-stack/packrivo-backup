# -*- coding: utf-8 -*-
"""报告取数：分类清单、覆写清单、归一化统计、长度 Top10 表、比例区间。
输出 E:\\破甲工作区\\.tmp\\32a8\\report-data.txt
"""
import csv, io, json, os, re, collections

TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')
CSV_P = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n.csv'
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
L = []


def w(s=''):
    L.append(s)


rows = json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
over = json.load(io.open(os.path.join(BASE, 'overrides.json'), encoding='utf-8'))

# 1) 分类清单
w('== 分类 ==')
for k in ('A', 'B', 'C', 'D1', 'D2'):
    ids = [i for i, v in cls.items() if v == k]
    w('%s n=%d idx=%s' % (k, len(ids), ','.join(str(i) for i in ids)))
w()
w('== B 清单（不译，交 Codex / pages=0）==')
for i in sorted(i for i, v in cls.items() if v == 'B'):
    r = [x for x in rows if x['idx'] == i][0]
    w('[%d] %s | %s | %s' % (i, r['kind'], r['field'], r['en'][:110]))
w()
w('== C 清单（模板/动态）==')
for i in sorted(i for i, v in cls.items() if v == 'C'):
    r = [x for x in rows if x['idx'] == i][0]
    w('[%d] %s | %s | %s' % (i, r['kind'], r['field'], r['en'][:90]))
w()
for i in sorted(i for i, v in cls.items() if v in ('D1', 'D2')):
    r = [x for x in rows if x['idx'] == i][0]
    w('[%s] [%d] %s | %s' % (cls[i], i, r['field'], r['en'][:100]))
w()

# 2) 覆写清单
w('== 覆写（overrides.json）==')
tot = 0
for lang in LANGS:
    d = over.get(lang, {})
    if not d:
        continue
    tot += len(d)
    w('%-5s n=%d idx=%s' % (lang, len(d), ','.join(sorted(d, key=lambda x: int(x)))))
w('合计 %d 条' % tot)
w()

# 3) 归一化统计
w('== 归一化（normalize.log）==')
cat = collections.Counter()
bylang = collections.Counter()
for line in io.open(os.path.join(BASE, 'normalize.log'), encoding='utf-8'):
    m = re.match(r'^(\S+) row(\d+) (\S+?):', line)
    if m:
        cat[m.group(3)] += 1
        bylang[m.group(1)] += 1
for k, v in cat.most_common():
    w('%-10s %d' % (k, v))
w('per-lang: ' + ', '.join('%s=%d' % (k, bylang[k]) for k in LANGS if bylang[k]))
w('total=%d' % sum(cat.values()))
w()

# 4) 数据
data = collections.defaultdict(dict)   # en -> lang -> value
recs = {}
for r in csv.reader(io.open(CSV_P, encoding='utf-8')):
    if r[0] == 'en':
        continue
    data[r[0]][r[3]] = r[4]
    recs[r[0]] = (r[1], r[2])

# 5) 标题长度 Top10
w('== 标题/meta 长度 Top10（post_title + _packrivo_description，按英文长度降序；单位=字符）==')
heads = [(en, k, f) for en, (k, f) in recs.items() if f in ('post_title', '_packrivo_description')]
heads.sort(key=lambda t: -len(t[0]))
w('| # | idx_en 前缀 | field | EN | ' + ' | '.join(LANGS) + ' |')
for n, (en, k, f) in enumerate(heads[:10], 1):
    cells = []
    for lang in LANGS:
        v = data[en].get(lang, '')
        cells.append('%d(%.2f)' % (len(v), len(v) / len(en)) if v else '-')
    w('| %d | %s | %s | %d | %s |' % (n, en[:38].replace('|', '/'), f, len(en), ' | '.join(cells)))
w()

# 6) post_title 比例区间
w('== post_title 长度比（译文/英文）==')
for lang in LANGS:
    rr = [len(data[en][lang]) / len(en) for en, (k, f) in recs.items()
          if f == 'post_title' and data[en].get(lang) and k == 'post_content']
    if rr:
        rr.sort()
        w('%-5s n=%d min=%.2f p50=%.2f max=%.2f' % (lang, len(rr), rr[0], rr[len(rr) // 2], rr[-1]))
w()

# 7) 空值/数字总览（附报告用）
w('== 行数与语言 ==')
w('data_rows=%d langs=%d per_lang=%d' % (sum(len(v) for v in data.values()), len(data), len(data[list(data)[0]])))

io.open(os.path.join(BASE, 'report-data.txt'), 'w', encoding='utf-8', newline='\n').write('\n'.join(L) + '\n')
print('wrote %d lines' % len(L))
