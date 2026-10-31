# -*- coding: utf-8 -*-
"""Verify residual English on specific pages (same logic as scan-residual.py)."""
import importlib.util, re, sys, subprocess, collections

sys.stdout.reconfigure(encoding='utf-8')
I902 = r'E:\Ai\ARTEX-Portable\app\data\tasks\67\i902'
spec = importlib.util.spec_from_file_location('scanres', I902 + r'\scan-residual.py')
sr = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sr)

def page_residual(lang, path):
    en_url = sr.BASE + (path if path != '/' else '/')
    url = f'{sr.BASE}/{lang}{path}' if path != '/' else f'{sr.BASE}/{lang}/'
    en_body = sr.fetch(en_url)
    body = sr.fetch(url)
    if not en_body or not body:
        return None
    en_segs = sr.segments_of(sr.strip_blog_marker(en_body))
    segs = sr.segments_of(sr.strip_blog_marker(body))
    res = []
    n = min(len(segs), len(en_segs))
    for i in range(n):
        s = segs[i]
        if not sr.has_english(s):
            continue
        if sr.norm(s) == sr.norm(en_segs[i]):
            res.append((s, len(sr.eng_words(s))))
    return res

if __name__ == '__main__':
    paths = sys.argv[2:] if len(sys.argv) > 2 else ['/product-kraft-bubble-parcel-envelope/',
        '/product-paper-carton-sealing-tape/', '/product-latch-metal-gift-container/',
        '/product-press-close-metal-gift-container/', '/product-hook-floor-gift-display/',
        '/product-tray-floor-gift-display/', '/product-multi-tier-floor-gift-display/',
        '/about/', '/inquiry/']
    lang = sys.argv[1] if len(sys.argv) > 1 else 'de'
    for p in paths:
        res = page_residual(lang, p)
        if res is None:
            print(f'{lang}{p}: FETCH FAIL')
            continue
        tot = sum(w for _, w in res)
        print(f'{lang}{p}: total={tot} words, segments={len(res)}')
        for s, w in res:
            print(f'   [{w}w] {s[:110]}')
