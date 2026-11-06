# -*- coding: utf-8 -*-
"""交付前自检：结构、条数、空值、数字、占位符、破折号、HTML、同族一致性、编码。

读 dsh-out/32a8-i18n.csv 与 32a8-rows.json / 32a8-class.json，实打实跑一遍并打印可粘贴的结果。
"""
import csv, io, json, os, re, sys

TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')
REPO = r'E:\破甲工作区\packrivo-backup'
CSV_P = os.path.join(REPO, 'dsh-out', '32a8-i18n.csv')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
TEMPLATE_EN = {1, 3, 4, 7, 20, 21}
NUM = re.compile(r'\d+(?:[.,]\d+)?')
BAD_DASH = '\u2013\u2014\uff5e\u301c'
TOKENS = ['Packrivo', 'MOQ', 'DDP', 'FOB', 'WhatsApp', 'hello@packrivo.com']

rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}


def norm_digits(s):
    # 先合并千分位前的空格/NBSP/NNBSP（fr 1 000 / de 1.000 / ja 1,000個 都归一成同一串）
    s = re.sub(r'(?<=\d)[\u00a0\u202f ](?=\d)', '', s)
    out = []
    for m in NUM.findall(s):
        out.append(m.replace(',', '').replace('.', '').replace('\u00a0', '').replace(' ', ''))
    return sorted(out)


raw = io.open(CSV_P, 'rb').read()
checks = []
bom = raw.startswith(b'\xef\xbb\xbf')
cr = raw.count(b'\r')
lf = raw.count(b'\n')
checks.append(('BOM', 'PASS' if not bom else 'FAIL', 'bom=%s' % bom))
checks.append(('换行', 'PASS' if cr == 0 and lf > 0 else 'FAIL', 'CR=%d LF=%d' % (cr, lf)))

text = raw.decode('utf-8')
tbl = list(csv.reader(io.StringIO(text)))
hdr, body = tbl[0], [r for r in tbl[1:] if r and any(c.strip() for c in r)]
checks.append(('列头', 'PASS' if hdr == ['en', 'kind', 'field', 'lang', 'value', 'note'] else 'FAIL', str(hdr)))
bad_width = [i for i, r in enumerate(body) if len(r) != 6]
checks.append(('列数=6', 'PASS' if not bad_width else 'FAIL', 'bad=%d' % len(bad_width)))

want = 196 * 11
checks.append(('数据行=2156', 'PASS' if len(body) == want else 'FAIL', 'rows=%d want=%d' % (len(body), want)))

# 每语种条数
per = {}
for r in body:
    per[r[3]] = per.get(r[3], 0) + 1
ok_per = all(per.get(l) == 196 for l in LANGS) and len(per) == 11
checks.append(('每语种 196 条', 'PASS' if ok_per else 'FAIL',
               ' '.join('%s=%s' % (l, per.get(l)) for l in LANGS)))

# 空值
empty = [(r[3], r[0][:40]) for r in body if not r[4].strip()]
checks.append(('空值 0', 'PASS' if not empty else 'FAIL', 'empty=%d %r' % (len(empty), empty[:5])))

# 数字比对（模板行豁免）
# 判据分两层：源里出现的数字必须原样出现在译文（丢失=FAIL）；
# 译文多出的数字（日/韩/中的量词与「10月」这类日期改写）单独列为 INFO，不算失败。
from collections import Counter
num_bad, num_extra, num_skip = [], [], 0
for r in body:
    en, lang, v = r[0], r[3], r[4]
    idx = None
    for i in rows:
        if rows[i]['en'] == en and rows[i]['kind'] == r[1]:
            idx = i
            break
    if idx in TEMPLATE_EN:
        num_skip += 1
        continue
    need = Counter(norm_digits(en)) - Counter(norm_digits(v))
    if need:
        num_bad.append((lang, en[:45], norm_digits(en), norm_digits(v), v[:45]))
    extra = Counter(norm_digits(v)) - Counter(norm_digits(en))
    if extra:
        num_extra.append((lang, en[:35], sorted(extra.elements()), v[:35]))
checks.append(('数字不丢失（源数字 ⊆ 译文）', 'PASS' if not num_bad else 'FAIL',
               'mismatch=%d (模板豁免 %d 行) 译文多出数字=%d 行(INFO)' % (len(num_bad), num_skip, len(num_extra))))

# 占位符（运行时 functions.php:368-377 只回填大写 {N}）
ph_bad = [(r[3], r[0], r[4][:40]) for r in body
          if ('{N}' in r[0] or '{n}' in r[0]) and '{N}' not in r[4]]
ph_lower = [(r[3], r[0]) for r in body if '{n}' in r[4]]
checks.append(('模板占位符 {N}', 'PASS' if not ph_bad else 'FAIL', 'bad=%d %r' % (len(ph_bad), ph_bad[:5])))
checks.append(('无小写 {n} 泄漏', 'PASS' if not ph_lower else 'FAIL', 'hits=%d' % len(ph_lower)))

# 破折号：区间必须用 ASCII 连字符（数字两侧带 – — ～ 〜 即 FAIL）；
# 德/法等语言正文里的破折句法（Gedankenstrich）不算违规，单独列为 INFO。
RANGE_DASH = re.compile(r'\d[ \u00a0]*[%s][ \u00a0]*\d' % BAD_DASH)
dash_bad, dash_prose = [], []
for r in body:
    if RANGE_DASH.search(r[4]):
        dash_bad.append((r[3], r[4][:45]))
    for ch in BAD_DASH:
        if ch in r[4] and ch not in r[0] and not RANGE_DASH.search(r[4]):
            dash_prose.append((r[3], ch, r[4][:40]))
            break
checks.append(('数字区间无 – — ～ 〜', 'PASS' if not dash_bad else 'FAIL',
               'hits=%d 正文破折句法=%d 行(INFO)' % (len(dash_bad), len(dash_prose))))

# HTML
html_bad = [r[3] for r in body if '<' in r[4] and '<' not in r[0]]
checks.append(('HTML 标签一致', 'PASS' if not html_bad else 'FAIL',
               '源带标签行=0, 译文新增标签=%d' % len(html_bad)))

# 固定 token
tok_bad = []
for r in body:
    for t in TOKENS:
        if t in r[0] and t not in r[4]:
            tok_bad.append((r[3], t, r[0][:40]))
checks.append(('专有名词/MOQ/DDP/FOB 保留', 'PASS' if not tok_bad else 'FAIL',
               'missing=%d %r' % (len(tok_bad), tok_bad[:5])))

# 箭头/间隔号：→ 必须保留（阿拉伯语 RTL 允许镜像成 ←）；· 只查丢失，多出的列表分隔属自然语言
arrow_bad, arrow_extra = [], []
for r in body:
    en, lang, v = r[0], r[3], r[4]
    n_en_arrow = en.count('\u2192')
    n_v = v.count('\u2192') + (v.count('\u2190') if lang == 'ar' else 0)
    if n_v != n_en_arrow:
        arrow_bad.append((lang, '\u2192', en[:30], v[:30]))
    if v.count('\u00b7') < en.count('\u00b7'):
        arrow_bad.append((lang, '\u00b7', en[:30], v[:30]))
    elif v.count('\u00b7') > en.count('\u00b7'):
        arrow_extra.append((lang, v.count('\u00b7'), en[:30], v[:40]))
checks.append(('→ 保留 / · 不丢失', 'PASS' if not arrow_bad else 'FAIL',
               'hits=%d 多出 · 的行=%d(INFO)' % (len(arrow_bad), len(arrow_extra))))

# 同族一致性
fam = {}
for r in body:
    fam.setdefault(r[3], {})[r[0]] = r[4]
fam_bad = []
for lang, m in fam.items():
    g = lambda k: m.get(k)
    pairs = [([i for i in rows if rows[i]['en'] == a][0], [i for i in rows if rows[i]['en'] == b][0])
             for a, b in (('All 344 articles \u2192', 'All {N} articles \u2192'),
                          ('Journal \u00b7 344 articles', 'Journal \u00b7 {N} articles'),
                          ('Structures & fit', 'STRUCTURES & FIT'))]
    for a, b in pairs:
        va, vb = g(rows[a]['en']), g(rows[b]['en'])
        if va != vb:
            fam_bad.append((lang, a, b, (va or '')[:25], (vb or '')[:25]))
    c22 = g('MATERIALS & PRINT')
    for en, want in (('MATERIALS & PRINT \u00b7 2026', (c22 or '') + ' \u00b7 2026'),
                     ('STRUCTURES & FIT \u00b7 2026', (g('STRUCTURES & FIT') or '') + ' \u00b7 2026')):
        if g(en) != want:
            fam_bad.append((lang, en, '', (g(en) or '')[:30], want[:30]))
checks.append(('同族译名一致（3=4, 20=21, 22=38=39, 带年份后缀）', 'PASS' if not fam_bad else 'FAIL',
               'mismatch=%d' % len(fam_bad)))

# kind/field 合法性
kinds = sorted(set(r[1] for r in body))
fields = sorted(set(r[2] for r in body))
checks.append(('kind 取值', 'PASS' if kinds == ['blog', 'dict', 'post_content', 'postmeta'] else 'WARN', str(kinds)))
checks.append(('field 已去括注', 'PASS' if not any('（' in f or '(' in f for f in fields) else 'FAIL',
               '%d 种: %s' % (len(fields), ', '.join(fields))))

# 语言无关行
neutral = {}
for r in body:
    if r[0] in ('hello@packrivo.com', 'MOQ 250'):
        neutral.setdefault(r[0], set()).add(r[4])
checks.append(('语言无关行全语种同值', 'PASS' if all(len(v) == 1 for v in neutral.values()) else 'WARN',
               '; '.join('%s -> %r' % (k, sorted(v)[0]) for k, v in neutral.items())))

fails = [c for c in checks if c[1] == 'FAIL']
for name, st, detail in checks:
    print('%-34s %-4s %s' % (name, st, detail))
print('---')
print('FAIL_COUNT=%d' % len(fails))
if num_bad:
    print('num_bad_sample=%r' % num_bad[:8])
if num_extra:
    print('num_extra_INFO=%d 行, 前 6: %r' % (len(num_extra), num_extra[:6]))
if dash_prose:
    print('dash_prose_INFO=%r' % (dash_prose[:6],))
if arrow_extra:
    print('arrow_extra_INFO=%r' % (arrow_extra[:6],))
if fam_bad:
    print('fam_bad=%r' % fam_bad[:8])
io.open(os.path.join(BASE, 'check.log'), 'w', encoding='utf-8', newline='\n').write(
    '\n'.join('%-34s %-4s %s' % c for c in checks) + '\nFAIL_COUNT=%d\n' % len(fails))
sys.exit(1 if fails else 0)
