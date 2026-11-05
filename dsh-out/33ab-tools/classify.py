# -*- coding: utf-8 -*-
"""Classify each 33a/33b English string into the dict bucket by its real markup context.

PHP blocks are masked with equal-length spaces so offsets stay valid; the enclosing tag /
attribute is read from the masked markup, which keeps the HTML around a PHP echo
(<th scope="row"><?= esc_html('Board') ?></th> -> <th>). JS files are not markup, so they
only contribute an occurrence record, never a tag inference.
"""
import io, json, os, re
from collections import Counter

WS = r'E:\破甲工作区\packrivo-backup-33b'
TOOLS = os.path.join(WS, 'dsh-out', '33ab-tools')
DATA = json.load(io.open(os.path.join(TOOLS, 'strings.json'), encoding='utf-8'))
order = DATA['order']

PHP_FILES, JS_FILES = [], []
for dirpath, dirnames, filenames in os.walk(os.path.join(WS, 'packrivo-wp')):
    for fn in filenames:
        p = os.path.join(dirpath, fn)
        if fn.endswith('.php'):
            PHP_FILES.append(p)
        elif fn.endswith('.js'):
            JS_FILES.append(p)

BTN = re.compile(r'class\s*=\s*["\'][^"\']*(?:btn|button|cta)[^"\']*["\']', re.I)
PHP_OPEN = re.compile(r'<\?php\b|<\?=', re.I)


def mask_php(text):
    out = list(text)
    i = 0
    while True:
        m = PHP_OPEN.search(text, i)
        if not m:
            break
        start, end = m.start(), text.find('?>', m.end())
        end = len(text) if end == -1 else end + 2
        for k in range(start, end):
            if out[k] != '\n':
                out[k] = ' '
        i = end
    return ''.join(out)


CACHE = {}
for f in PHP_FILES:
    t = io.open(f, encoding='utf-8', errors='replace').read()
    CACHE[f] = (t, mask_php(t))
JS = {f: io.open(f, encoding='utf-8', errors='replace').read() for f in JS_FILES}


def occ(text, s):
    out, i = [], text.find(s)
    while i != -1:
        out.append(i)
        i = text.find(s, i + 1)
    return out


def classify_at(masked, i):
    lt, gt = masked.rfind('<', 0, i), masked.rfind('>', 0, i)
    if lt > gt:                                     # inside an open tag
        frag = masked[lt:i]
        m = re.search(r'([a-zA-Z-:]+)\s*=\s*["\']?$', frag)
        tagm = re.match(r'<\s*([a-zA-Z0-9-]+)', frag)
        tag = tagm.group(1).lower() if tagm else '?'
        return ('attr', tag, m.group(1).lower()) if m else ('attr', tag, 'tag')
    opens = list(re.finditer(r'<\s*([a-zA-Z0-9-]+)([^<>]*)>', masked[:i]))
    if not opens:
        return 'text', '?', ''
    tag, attrs = opens[-1].group(1).lower(), opens[-1].group(2)
    # A <a class=btn>/<button> caption is a text node, not an attribute value: 32a keeps
    # CTAs of this class in block/text (Order a sample, Home, Explore) and reserves attr
    # for real attribute values (Close image, Product concept image, you@example.com).
    if tag in ('a', 'button') and BTN.search(attrs):
        return 'text', tag, 'btn'
    return 'text', tag, ''


def sentence_like(s):
    return len(s) >= 70 or bool(re.search(r'[.!?;]\s+[A-Z(]', s))


def probe(en):
    """(bucket, where-list, tags) for one English string, with dynamic-composite fallback."""
    hits = []
    for f, (raw, masked) in CACHE.items():
        for i in occ(raw, en):
            kind, tag, attr = classify_at(masked, i)
            hits.append((os.path.basename(f), tag, attr, kind))
    for f, text in JS.items():
        for _ in occ(text, en):
            hits.append((os.path.basename(f), 'js', '', 'js'))
    if not hits:                                     # composite: longest literal fragment
        frags = [p for p in re.split(r'\d[\d,\.]*(?:\s*[–-]\s*\d[\d,\.]*)?|\{[a-z]+\}|%[0-9$]*s', en) if len(p.strip()) >= 8]
        frags.sort(key=len, reverse=True)
        for frag in frags:
            for f, (raw, masked) in CACHE.items():
                for i in occ(raw, frag):
                    sub = probe(frag)
                    if sub[0] != 'UNSEEN':
                        return sub[0], [f'{os.path.basename(f)} <{sub[2]}*composite: {frag.strip()[:40]}>'], sub[2]
        return 'UNSEEN', [], []
    if any(h[1] == 'title' for h in hits):
        bucket = 'title'
    elif any(h[3] == 'attr' for h in hits):
        bucket = 'attr'
    elif sentence_like(en):
        bucket = 'block'
    elif any(h[1] == 'li' and len(en) >= 45 for h in hits):
        bucket = 'block'
    else:
        bucket = 'text'
    where = [f'{h[0]} <{h[1]}{(" " + h[2]) if h[2] else ""}>' for h in hits]
    tags = sorted({h[1] for h in hits})
    return bucket, where, tags


rows = []
for en in order:
    bucket, where, tags = probe(en)
    rows.append({'en': en, 'bucket': bucket, 'where': where, 'tags': tags})

io.open(os.path.join(TOOLS, 'bucket-map.json'), 'w', encoding='utf-8', newline='\n').write(
    json.dumps(rows, ensure_ascii=False, indent=1) + '\n')

lines = []
for n, r in enumerate(rows, 1):
    lines.append('%3d %-6s %-96s | tags=%s | %s' % (
        n, r['bucket'], r['en'][:96], ','.join(r['tags'])[:40], ' '.join(r['where'])[:170]))
lines.append('')
if any(r['bucket'] == 'UNSEEN' for r in rows):
    lines.append('## UNSEEN')
    for r in rows:
        if r['bucket'] == 'UNSEEN':
            lines.append('    ' + r['en'])
    lines.append('')
lines.append('## 分布 ' + json.dumps(Counter(r['bucket'] for r in rows), ensure_ascii=False))
io.open(os.path.join(TOOLS, 'bucket-map.txt'), 'w', encoding='utf-8', newline='\n').write('\n'.join(lines) + '\n')
print('strings', len(rows), 'php', len(PHP_FILES), 'js', len(JS_FILES), Counter(r['bucket'] for r in rows))
