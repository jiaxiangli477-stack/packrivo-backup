# -*- coding: utf-8 -*-
"""生成 dsh-out/32a8-i18n.csv：复用既有词典译文 + 本轮补译，统一模板/数字/连字符口径。

输出：repo 内 dsh-out/32a8-i18n.csv（UTF-8 无 BOM、LF），
      E:\\破甲工作区\\.tmp\\32a8\\final.json（lang -> idx -> value，供报告与自检复用）
"""
import json, io, os, re, unicodedata

TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')
REPO = r'E:\破甲工作区\packrivo-backup'
OUT = os.path.join(REPO, 'dsh-out', '32a8-i18n.csv')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
TEMPLATE = (1, 3, 4, 7, 20, 21)
NEW_EN = {'28': 'Multi-colour sheet-fed offset printing press for packaging'}

rows = {r['idx']: r for r in json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))}
cls = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-class.json'), encoding='utf-8')).items()}
reuse = {int(k): v for k, v in json.load(io.open(os.path.join(TMP, '32a8-reuse.json'), encoding='utf-8')).items()}
TR = {}
for lang in LANGS:
    p = os.path.join(BASE, 'tr', '%s.json' % lang)
    TR[lang] = {int(k): v for k, v in json.load(io.open(p, encoding='utf-8-sig')).items()} if os.path.exists(p) else {}

OVER = {}
_ovp = os.path.join(BASE, 'overrides.json')
if os.path.exists(_ovp):
    OVER = {lang: {int(k): v for k, v in d.items()}
            for lang, d in json.load(io.open(_ovp, encoding='utf-8')).items()}

TARGET = [i for i in sorted(rows) if cls[i] in ('A', 'C', 'D1')]
DASH_IN_RANGE = re.compile(r'(?<=\d)[ \u00a0]*[\u2013\u2014\uff5e\u301c][ \u00a0]*(?=\d)')
notes_log = []


def src_en(i):
    return NEW_EN.get(str(i), rows[i]['en'])


def clean_field(f):
    for sep in ('（', '('):
        if sep in f:
            f = f.split(sep)[0]
    return f.strip()


def raw_value(lang, i):
    h = reuse.get(i, {}).get(lang)
    if h:
        return h['value'], 'reuse'
    v = TR[lang].get(i)
    if v is not None:
        return v, 'fresh'
    return None, 'none'


def build_lang(lang):
    """返回 idx -> (value, source_tag)。"""
    m = {}
    for i in TARGET:
        v, tag = raw_value(lang, i)
        m[i] = [v, tag]
    # 语言中立行：原文照抄
    for i in (17, 25):
        m[i] = [rows[i]['en'], 'neutral']
    # 模板行：占位符统一成 {N}（运行时 packrivo-home-v4/functions.php:368-377 只认大写 {N}）
    for i in (4, 21):
        v, tag = m[i]
        if v:
            m[i] = [v.replace('{n}', '{N}'), tag]
    for i in (1, 7):
        v, tag = m[i]
        # 复用值里写死的是 134，替换成占位符
        v2 = re.sub(r'\b134\b', '{N}', v) if v else v
        if v2 != v:
            notes_log.append('%s row%d 计数参数化: %r -> %r' % (lang, i, v, v2))
        m[i] = [v2, tag]
    for i in (3, 20):
        v, tag = m[i]
        if v:
            v2 = re.sub(r'\b344\b', '{N}', v.replace('{n}', '{N}'))
            if v2 != v:
                notes_log.append('%s row%d 计数参数化: %r -> %r' % (lang, i, v, v2))
            m[i] = [v2, tag]
    # 同族强制一致：4 是 {N} 后备键（权威），3 是写死计数的动态值；21/20 同；
    # 38 与 39 只是大小写形态不同，必须同译名；22/38 权威，23/40 = 分类名 + ' · 2026'
    for a, b in ((4, 3), (21, 20), (38, 39)):
        base = m[a][0] or m[b][0]
        if base:
            m[a] = [base, m[a][1] if m[a][0] else 'derived']
            m[b] = [base, m[b][1] if m[b][0] else 'derived']
    for plain, suffixed in ((22, 23), (38, 40)):
        core = (m[plain][0] or '').replace(' · 2026', '').strip()
        if not core:
            core = (m[suffixed][0] or '').replace(' · 2026', '').strip()
            m[plain] = [core, m[plain][1]]
        if core:
            m[suffixed] = [core + ' · 2026', m[suffixed][1] if m[suffixed][0] else 'derived']
    # 人工术语校正：优先级最高（MOQ 按 34a 既有写法原样保留），来源 fix_moq.py / moq-patch.log
    for i, v in OVER.get(lang, {}).items():
        if not m[i][0] or m[i][0] != v:
            notes_log.append('%s row%d 术语校正: %r -> %r'
                             % (lang, i, (m[i][0] or '')[:50], v[:50]))
            m[i] = [v, 'fixed']
    return m


def normalize(lang, i, v):
    """数字区间连字符统一为 ASCII；日语句末 ASCII 句点改全角。"""
    out = DASH_IN_RANGE.sub('-', v)
    if out != v:
        notes_log.append('%s row%d 连字符归一: %r -> %r' % (lang, i, v[:60], out[:60]))
    if lang == 'ja' and out.endswith('.') and not out.endswith(('..', 'etc.')):
        notes_log.append('%s row%d 句末标点修正: %r' % (lang, i, out[-20:]))
        out = out[:-1] + '。'
    return out


def main():
    finals = {}
    for lang in LANGS:
        m = build_lang(lang)
        finals[lang] = {i: normalize(lang, i, m[i][0]) if m[i][0] is not None else None for i in TARGET}
        miss = [i for i in TARGET if not finals[lang][i]]
        if miss:
            print('!! %s 缺失 %d 条: %r' % (lang, len(miss), miss[:10]))
    # 写 CSV
    lines = ['en,kind,field,lang,value,note']
    n = 0
    for i in TARGET:
        en = rows[i]['en']
        kind = rows[i]['kind']
        field = clean_field(rows[i]['field'])
        note = 'template' if i in TEMPLATE else ('new_en' if str(i) in NEW_EN else '')
        for lang in LANGS:
            v = finals[lang][i]
            if v is None:
                v = ''
            lines.append(csv_line([en, kind, field, lang, v, note]))
            n += 1
    io.open(OUT, 'w', encoding='utf-8', newline='\n').write('\n'.join(lines) + '\n')
    json.dump(finals, io.open(os.path.join(BASE, 'final.json'), 'w', encoding='utf-8', newline='\n'),
              ensure_ascii=False, indent=1)
    io.open(os.path.join(BASE, 'normalize.log'), 'w', encoding='utf-8', newline='\n').write('\n'.join(notes_log) + '\n')
    print('rows=%d data_rows=%d file=%s' % (len(TARGET), n, OUT))


def csv_line(cells):
    out = []
    for c in cells:
        if any(ch in c for ch in ',"\n\r'):
            c = '"' + c.replace('"', '""') + '"'
        out.append(c)
    return ','.join(out)


main()
