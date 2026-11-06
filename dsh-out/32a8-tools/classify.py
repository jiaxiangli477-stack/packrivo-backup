# -*- coding: utf-8 -*-
"""32a8 分类与风险扫描：A/B/C/D 归类，标记 MOQ 500、自封工厂、ISO/ISTA/FSC、空白异常。"""
import json, io, os, re
from collections import OrderedDict

TMP = r'E:\破甲工作区\.tmp'
rows = json.load(io.open(os.path.join(TMP, '32a8-rows.json'), encoding='utf-8'))

PAGES0 = {r['idx'] for r in rows if r['pages'] == 0}
TEMPL = {1, 3, 4, 7, 20, 21, 22, 23, 38, 39, 40}          # 数字模板 / 分类名
D_NEW_EN = {28}                                            # 用新英文
D_REPORT = {6}                                             # 不翻，交 Codex

RISK = {
    'MOQ_500': re.compile(r'MOQ\s*500|500\s*pieces\s*minimum|minimum\s+of\s+500', re.I),
    'SELF_FACTORY': re.compile(r'\bour\s+(?:own\s+)?(?:factory|plant|workshop|manufactur)|we\s+manufactur|we\s+own\s+(?:a\s+)?factory|our\s+production\s+lines?', re.I),
    'CERT': re.compile(r'\b(?:ISO\s*\d|ISTA|FSC|SGS|RoHS|FDA)\b', re.I),
}
BAD_WS = re.compile(r'^\s|\s$|\s{2,}')
DASH = re.compile('[\u301c\uff5e\u2013\u2014\u2012\u2212]')
TEMPL_TOK = re.compile(r'\{[Nn]\}')

report = []
bodies = []
cls = OrderedDict()
for r in rows:
    i = r['idx']
    en = r['en']
    if i in PAGES0:
        c = 'B'
    elif i in D_REPORT:
        c = 'D2'
    elif i in D_NEW_EN:
        c = 'D1'
    elif i in TEMPL:
        c = 'C'
    elif r['kind'] == 'dict':
        c = 'A'
    else:
        c = 'A'
    cls[i] = c
    flags = [k for k, rx in RISK.items() if rx.search(en)]
    ws = bool(BAD_WS.search(en))
    dash = bool(DASH.search(en))
    tok = bool(TEMPL_TOK.search(en))
    lead = en[:12].replace('\n', '\\n')
    report.append('%3d %-2s p%-2d %-12s %-30s flags=%-28s ws=%-5s dash=%-5s ph=%-5s | %s' % (
        i, c, r['pages'], r['kind'], r['field'], ','.join(flags) or '-', ws, dash, tok, lead))

out = []
out.append('== 分类统计 ==')
from collections import Counter
cnt = Counter(cls.values())
for k in ('A', 'B', 'C', 'D1', 'D2'):
    out.append('  %-3s %d' % (k, cnt.get(k, 0)))
out.append('  A+C+D1 合计（×11 语言行数基础）= %d' % (cnt.get('A', 0) + cnt.get('C', 0) + cnt.get('D1', 0)))
out.append('\n== 每行分类与风险标记 ==')
out.extend(report)

io.open(os.path.join(TMP, '32a8-classify.txt'), 'w', encoding='utf-8', newline='\n').write('\n'.join(out) + '\n')
json.dump(cls, io.open(os.path.join(TMP, '32a8-class.json'), 'w', encoding='utf-8', newline='\n'), indent=1)
print('A=%d B=%d C=%d D1=%d D2=%d total_to_translate=%d langs=11 csv_rows=%d'
      % (cnt.get('A', 0), cnt.get('B', 0), cnt.get('C', 0), cnt.get('D1', 0), cnt.get('D2', 0),
         cnt.get('A', 0) + cnt.get('C', 0) + cnt.get('D1', 0),
         (cnt.get('A', 0) + cnt.get('C', 0) + cnt.get('D1', 0)) * 11))
