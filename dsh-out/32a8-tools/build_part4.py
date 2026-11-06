# -*- coding: utf-8 -*-
"""生成 dsh-out/32a8-tools/i18n-part-4.csv（第 4/4 块：目标语种第 10-11 个 = zh-CN/zh-TW）。
数据源 = 已通过 check_csv.py 校验的最终 CSV（dsh-out/32a8-i18n.csv），
从中抽取 lang in (zh-CN, zh-TW) 的行，保持最终 CSV 的行序（idx 升序，语种序 zh-CN→zh-TW），
分片与最终 CSV 同构（en,kind,field,lang,value,note），LF、无 BOM。不改动其他分片与最终文件。"""
import csv, io, os, re, sys
from collections import Counter

REPO = r'E:\破甲工作区\packrivo-backup'
SRC = os.path.join(REPO, 'dsh-out', '32a8-i18n.csv')
OUT = os.path.join(REPO, 'dsh-out', '32a8-tools', 'i18n-part-4.csv')
LANGS = ['zh-CN', 'zh-TW']  # 目标语种第 10-11 个
raw = io.open(SRC, 'rb').read()
text = raw.decode('utf-8')
tbl = list(csv.reader(io.StringIO(text)))
hdr = tbl[0]
body = [r for r in tbl[1:] if r and any(c.strip() for c in r)]

# 抽取本块语种
part = [r for r in body if r[3] in LANGS]

checks = []
checks.append(('header', hdr == ['en', 'kind', 'field', 'lang', 'value', 'note'], str(hdr)))
checks.append(('rows_392', len(part) == 196 * 2, 'got=%d' % len(part)))

per = Counter(r[3] for r in part)
checks.append(('per_lang_196', all(per[l] == 196 for l in LANGS) and len(per) == 2, str(dict(per))))

# 行序：idx 升序，每 idx 按 zh-CN→zh-TW（最终 CSV 内语种序 ar..zh-TW，过滤后相邻为 zh-CN,zh-TW）
seq = [r[3] for r in part]
exp = ['zh-CN', 'zh-TW'] * 196
checks.append(('lang_seq', seq == exp, 'first2=%r last2=%r' % (seq[:2], seq[-2:])))

# 空值
empty = [r for r in part if not r[4].strip()]
checks.append(('empty_0', not empty, 'empty=%d' % len(empty)))

# 数字一致（note=template 的 C 类模板行豁免）
NUM = re.compile(r'\d+(?:[.,]\d+)?')
def norm_digits(s):
    s = re.sub(r'(?<=\d)[\u00a0\u202f ](?=\d)', '', s)
    out = []
    for m in NUM.findall(s):
        out.append(m.replace(',', '').replace('.', '').replace('\u00a0', '').replace(' ', ''))
    return sorted(out)
# 数字：源中出现的数字必须原样出现在译文（丢失才 FAIL）；译文多出的数字
# （如 October→10 月 这种把月份名改写成数字）与最终 check_csv.py 口径一致，仅 INFO。
num_bad = []
num_extra = []
for r in part:
    if r[5] == 'template':
        continue
    en, v = r[0], r[4]
    need = Counter(norm_digits(en)) - Counter(norm_digits(v))
    if need:
        num_bad.append((r[3], en[:40], norm_digits(en), norm_digits(v), v[:40]))
    extra = Counter(norm_digits(v)) - Counter(norm_digits(en))
    if extra:
        num_extra.append((r[3], en[:35], sorted(extra.elements()), v[:35]))
checks.append(('digits_kept', not num_bad, 'lost=%d (extra=%d INFO)' % (len(num_bad), len(num_extra))))

# 模板行 {N} 占位符（运行时 functions.php 只回填大写 {N}）
ph_bad = [r for r in part if r[5] == 'template' and '{N}' not in r[4]]
ph_lower = [r for r in part if '{n}' in r[4]]
checks.append(('template_N', not ph_bad, 'bad=%d %r' % (len(ph_bad), ph_bad[:3])))
checks.append(('no_lower_n', not ph_lower, 'hits=%d' % len(ph_lower)))

# HTML 标签：源无标签则译文不得新增
html_bad = [r[3] for r in part if '<' in r[4] and '<' not in r[0]]
checks.append(('html_tags', not html_bad, 'new_tags=%d' % len(html_bad)))

# 连字符（ASCII -，禁 –—〜～；原文自带除外）
BAD_DASH = '\u2013\u2014\uff5e\u301c'
dash_bad = [r for r in part if any(c in r[4] for c in BAD_DASH) and not any(c in r[0] for c in BAD_DASH)]
checks.append(('dash_ascii', not dash_bad, 'bad=%d %r' % (len(dash_bad), dash_bad[:3])))

# 专有名词/MOQ 保留
TOKENS = ['Packrivo', 'MOQ', 'DDP', 'FOB', 'WhatsApp', 'hello@packrivo.com']
tok_bad = [r for r in part for t in TOKENS if t in r[0] and t not in r[4]]
checks.append(('tokens_kept', not tok_bad, 'missing=%d %r' % (len(tok_bad), tok_bad[:3])))

# 写分片（LF，无 BOM，与最终 CSV 同构）
def csv_line(cells):
    out = []
    for c in cells:
        if any(ch in c for ch in ',"\n\r'):
            c = '"' + c.replace('"', '""') + '"'
        out.append(c)
    return ','.join(out)

lines = [csv_line(hdr)] + [csv_line(r) for r in part]
io.open(OUT, 'w', encoding='utf-8', newline='\n').write('\n'.join(lines) + '\n')

rb = io.open(OUT, 'rb').read()
checks.append(('out_no_BOM', not rb.startswith(b'\xef\xbb\xbf'), 'bom=%s' % rb.startswith(b'\xef\xbb\xbf')))
checks.append(('out_LF_only', rb.count(b'\r') == 0 and rb.count(b'\n') == len(part) + 1,
               'CR=%d LF=%d want_LF=%d' % (rb.count(b'\r'), rb.count(b'\n'), len(part) + 1)))

fails = [c for c in checks if not c[1]]
for name, st, detail in checks:
    print('%-16s %-4s %s' % (name, 'PASS' if st else 'FAIL', detail))
print('FAIL_COUNT=%d' % len(fails))
print('OUT=%s rows=%d' % (OUT, len(part)))
sys.exit(1 if fails else 0)
