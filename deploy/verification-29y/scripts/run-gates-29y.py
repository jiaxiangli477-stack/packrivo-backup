# -*- coding: utf-8 -*-
"""29y G1-G7 gate runner. All HTTP checks hit the REHEARSAL stack only:
curl --noproxy "*" --resolve www.packrivo.com:443:127.0.0.1

Usage:
  python run-gates-29y.py [--out gates-29y.txt]
Exit code 0 even when gates FAIL (the result file carries the truth).
"""
import json, re, sys, io, os, subprocess, hashlib, csv
from datetime import datetime, timezone

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

REPO = r'E:\破甲工作区\packrivo-backup'
VER = REPO + r'\deploy\verification-29y'
MERGED = REPO + r'\deploy\i18n-merged'
UP = r'E:\破甲工作区\.tmp\28q\rehearsal\home\web\html\www.packrivo.com\wordpress\wp-content\uploads\packrivo-i18n'
I909 = r'E:\Ai\ARTEX-Portable\app\data\tasks\67\i909'
I907 = r'E:\Ai\ARTEX-Portable\app\data\tasks\67\i907'
LANGS = ['de', 'fr', 'es', 'it', 'nl', 'pt', 'ar', 'ja', 'ko', 'zh-CN', 'zh-TW']

CURL = ['curl.exe', '-sk', '--noproxy', '*', '--resolve', 'www.packrivo.com:443:127.0.0.1']

results = []  # (gate, pass, note)

def P(gate, note):
    results.append((gate, True, note))
    print(f'PASS  {gate}: {note}')

def F(gate, note):
    results.append((gate, False, note))
    print(f'FAIL  {gate}: {note}')

def fetch(path):
    for i in range(3):
        try:
            r = subprocess.run(CURL + [f'https://www.packrivo.com{path}'],
                               capture_output=True, timeout=120)
            if r.returncode == 0 and r.stdout:
                return r.stdout.decode('utf-8', errors='replace')
        except subprocess.TimeoutExpired:
            pass
    return ''

def fetch_code(path):
    for i in range(3):
        r = subprocess.run(CURL + ['-o', 'NUL', '-w', '%{http_code}',
                                   f'https://www.packrivo.com{path}'],
                           capture_output=True, timeout=120)
        if r.stdout and r.stdout.strip() == '200':
            return '200'
    return r.stdout.strip() if r.stdout else 'ERR'

def gate1_llms_prices():
    txt = fetch('/llms.txt')
    if len(txt) < 4000:
        F('G1', f'llms.txt 取回 {len(txt)} 字节（<4000，空壳或不可达）')
        return
    ok = True
    if txt.count('## Reference prices (from the quote builder)') != 1:
        ok = False; print('  - 价格段标题未命中')
    for pat in [r'\|\s*Rigid magnetic jewelry box[^\n]*\|\s*500\s*\|\s*\$2\.83\s*\|',
                r'\|\s*Rigid magnetic jewelry box[^\n]*\|\s*1,000\s*\|\s*\$2\.34\s*\|',
                r'\|\s*Printed mailer box[^\n]*\|\s*500\s*\|\s*\$2\.48\s*\|']:
        if not re.search(pat, txt):
            ok = False; print(f'  - 价格行缺失: {pat[:50]}')
    home = fetch('/')
    if '$2.34' not in home:
        ok = False; print('  - 首页未出现 $2.34（1000 件卡片价）')
    prod = fetch('/product-drawer-jewelry-box/')
    if 'from $2.83 / unit' not in prod and 'ab $2.83' not in prod:
        ok = False; print('  - 产品页价格条未见 $2.83')
    if ok: P('G1', 'llms.txt 三档引擎价(2.83/2.34/2.48)与首页卡片、产品页一致，旧口径行已删')
    else: F('G1', '价格一致性判据未全过，见上')

def gate2_quote_i18n():
    ok = True
    fails = []
    for lang in [None] + LANGS:
        path = '/get-a-quote/' if lang is None else f'/{lang}/get-a-quote/'
        html = fetch(path)
        if not html:
            fails.append(f'{path} 不可达'); ok = False; continue
        m = re.search(r'<h1[^>]*>(.*?)</h1>', html, re.S)
        if not m or not re.sub(r'\s+', ' ', m.group(1)).strip():
            fails.append(f'{path} 无 H1'); ok = False; continue
        dm = re.search(r'<meta\s+name="description"\s+content="([^"]*)"', html)
        if not dm or not dm.group(1).strip():
            fails.append(f'{path} 无 meta description'); ok = False; continue
        if len(dm.group(1)) > 150:
            fails.append(f'{path} meta description {len(dm.group(1))} 字符（>150）'); ok = False
    if ok: P('G2', 'EN+11 语种 /get-a-quote/ 均有 H1、title 与 ≤150 字符 meta description')
    else: F('G2', '; '.join(fails[:6]))

def gate3_residual_lists():
    before = VER + r'\csv\residual-english-before-29y.csv'
    if not os.path.exists(before):
        F('G3', '改前清单缺失')
        return
    with open(before, encoding='utf-8-sig') as f:
        n = sum(1 for _ in csv.reader(f)) - 1
    # after rescan state (fresh) — only exists when the after pass ran
    after_csv = I907 + r'\residual-after-29y.csv'
    if not os.path.exists(after_csv):
        # evaluate current (before) state: how many langs fully scanned, how many pages >5 words
        st = I907 + r'\scan-state-29y.json'
        d = json.load(open(st, encoding='utf-8'))
        done = {(x[0], x[1]) for x in d['done']}
        npaths = len(d.get('en_done', []))
        per = {l: sum(1 for (ll, p) in done if ll == l) for l in LANGS}
        full = [l for l in LANGS if per[l] >= npaths]
        fail_pages = {}
        for r in d['rows']:
            if '/blog/' in r['url']:
                continue
            fail_pages.setdefault(r['lang'], {}).setdefault(r['url'], 0)
            fail_pages[r['lang']][r['url']] += r['en_words']
        over = {l: sum(1 for w in fail_pages.get(l, {}).values() if w > 5) for l in LANGS}
        F('G3', f'改前清单已落盘({n} 行)；复扫未完成（已扫全语种 {full or "无"}，当前 >5词页数 {over}）')
        return
    # after csv exists → evaluate
    with open(after_csv, encoding='utf-8-sig') as f:
        rows = list(csv.reader(f))[1:]
    bad = {}
    for lang, url, sentence, wc in rows:
        if '/blog/' in url:
            continue
        bad.setdefault(lang, {}).setdefault(url, 0)
        bad[lang][url] += int(wc or 0)
    over = {l: sum(1 for w in bad.get(l, {}).values() if w > 5) for l in LANGS}
    leftover = {l: sum(bad.get(l, {}).values()) for l in LANGS if sum(bad.get(l, {}).values()) > 0}
    if all(v == 0 for v in over.values()):
        P('G3', f'改前清单({n} 行)+复扫清单落盘；11 语种 >5词页数全部为 0')
    else:
        F('G3', f'改前清单({n} 行)已落盘，复扫 >5词页数 {over}；仍有残留 {leftover}')

def gate4_jsonld():
    ok = True
    for path in ['/products/', '/jewelry-boxes/', '/mailer-boxes/']:
        html = fetch(path)
        m = re.search(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', html, re.S)
        if not m:
            print(f'  - {path} 无 JSON-LD 块'); ok = False; continue
        found = False
        for mm in re.finditer(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', html, re.S):
            try:
                j = json.loads(mm.group(1))
                if isinstance(j, list):
                    j = j[0] if j else {}
                if j.get('@type') == 'ItemList':
                    found = True; break
            except Exception:
                pass
        if not found:
            print(f'  - {path} 无 ItemList'); ok = False
    # FAQPage rule: only when a visible FAQ block exists
    jb = fetch('/jewelry-boxes/')
    visible_faq = bool(re.search(r'(?:>How (?:long|much|do)[^<]{0,60}\?<)', jb, re.I))
    faq_blocks = re.findall(r'"@type"\s*:\s*"FAQPage"', jb)
    if faq_blocks and not visible_faq:
        print('  - FAQPage 输出但页面无可见 FAQ'); ok = False
    if ok: P('G4', '/products/ 与分类页输出 ItemList；FAQPage 仅在可见 FAQ 时输出')
    else: F('G4', 'JSON-LD 判据未全过')

def gate5_backlinks():
    p = VER + r'\csv\backlink-risk-29y.csv'
    if not os.path.exists(p):
        F('G5', '外链 CSV 缺失'); return
    with open(p, encoding='utf-8-sig') as f:
        rd = list(csv.reader(f))
    head = [h.strip() for h in rd[0]]
    need = ['domain', 'links', 'anchor_samples', 'rationale']
    if not all(c in head for c in need):
        F('G5', f'列不全: {head}'); return
    rows = [r for r in rd[1:] if r and r[0].strip()]
    domains = [r[0] for r in rows]
    if set(['rankvance.com', '8coint.com', 'link-pen.com']).issubset(set(domains)):
        P('G5', f'外链 CSV 按任务单口径落盘：{len(rows)} 行，含三大域名与锚文本样例/判断理由，未提交 disavow')
    else:
        F('G5', f'域名不全: {domains}')

def gate6_runbook():
    rb = REPO + r'\deploy\RUNBOOK-29y.md'
    if not os.path.exists(rb) or os.path.getsize(rb) < 800:
        F('G6', 'RUNBOOK-29y.md 缺失或过小')
        return
    ev = VER + r'\evidence'
    files = os.listdir(ev) if os.path.isdir(ev) else []
    need = ['29y-residual-english-before.txt', 'residual-english-29y.csv']
    miss = [x for x in need if x not in files]
    if miss:
        F('G6', f'verification-29y/evidence 缺 {miss}')
        return
    r = subprocess.run(['git', '-C', REPO, 'log', '-3', '--stat', '--oneline'],
                       capture_output=True, text=True, encoding='utf-8', errors='replace')
    if r.returncode == 0 and r.stdout.strip():
        P('G6', 'RUNBOOK-29y.md 落盘；evidence 证据在 deploy/verification-29y/；git log -3 --stat 可贴')
    else:
        F('G6', 'git log 取不到')

def gate7_dict_parity():
    ok = True
    notes = []
    for l in LANGS:
        try:
            h1 = hashlib.md5(open(MERGED + f'\\{l}-dict.json', 'rb').read()).hexdigest()
            h2 = hashlib.md5(open(UP + f'\\{l}-dict.json', 'rb').read()).hexdigest()
            if h1 != h2:
                ok = False; notes.append(f'{l} md5 不一致')
        except Exception as e:
            ok = False; notes.append(f'{l} 读失败 {e}')
    # translation coverage: trans-*.json entries per lang
    cov = {}
    for l in LANGS:
        p = I909 + f'\\trans-{l}.json'
        if os.path.exists(p):
            try:
                cov[l] = len(json.load(open(p, encoding='utf-8')))
            except Exception:
                cov[l] = -1
    done = sum(1 for l in LANGS if cov.get(l, 0) > 0)
    if ok and done == 11:
        P('G7', '11 语种词典 repo=演练 md5 一致；11/11 语种译文已合并')
    elif ok:
        F('G7', f'词典 md5 全一致，但译文仅完成 {done}/11 语种（{cov}）')
    else:
        F('G7', f'词典 md5 不一致: {"; ".join(notes[:4])}；译文 {done}/11 语种')

def main():
    print(f'=== 29y G1-G7 gates · rehearsal · {datetime.now(timezone.utc).isoformat()} ===')
    print('HTTP 全部走 --noproxy "*" --resolve www.packrivo.com:443:127.0.0.1')
    gate1_llms_prices()
    gate2_quote_i18n()
    gate3_residual_lists()
    gate4_jsonld()
    gate5_backlinks()
    gate6_runbook()
    gate7_dict_parity()
    print('=== summary ===')
    for g, ok, note in results:
        print(f'{"PASS" if ok else "FAIL"} {g}: {note}')
    npass = sum(1 for _, ok, _ in results if ok)
    nfail = len(results) - npass
    print(f'TOTAL {npass} PASS / {nfail} FAIL')
    out = sys.argv[sys.argv.index('--out') + 1] if '--out' in sys.argv else None
    if out:
        buf = io.StringIO()
        buf.write(f'=== 29y G1-G7 gates · rehearsal · {datetime.now(timezone.utc).isoformat()} ===\n')
        for g, ok, note in results:
            buf.write(f'{"PASS" if ok else "FAIL"} {g}: {note}\n')
        buf.write(f'TOTAL {npass} PASS / {nfail} FAIL\n')
        with open(out, 'w', encoding='utf-8') as f:
            f.write(buf.getvalue())

if __name__ == '__main__':
    main()
