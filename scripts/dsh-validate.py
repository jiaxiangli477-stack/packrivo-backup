"""Validate dsh copy batches before Codex imports them.

Usage: python -I scripts/dsh-validate.py [repo_root]
Exit code 1 if any error is found.
"""
import csv
import glob
import json
import os
import re
import sys

ROOT = sys.argv[1] if len(sys.argv) > 1 else os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
BANNED = re.compile(
    r'sourcing|supplier review|near me|1688|alibaba|\bdiy\b|for women|for girls|e-flute|\bsbs\b|\bgsm\b|'
    r'delta e|\bista\b|dieline|\d\s?mm\b|our factory|we manufacture|fsc|iso 9001|\$\s?\d',
    re.I,
)
FIELDS = ['slug', 'old_title', 'new_title', 'new_h1', 'new_description', 'primary_keyword', 'modifier', 'i18n']

errors, warnings = [], []

inv = os.path.join(ROOT, 'scripts', 'product-buyers-27e', 'full-inventory.csv')
with open(inv, encoding='utf-8-sig') as f:
    rows = list(csv.DictReader(f))
pending = [r['slug'] for r in rows if r['status'] == 'unchanged']
done_titles = {r['current_title'].lower() for r in rows if r['status'] != 'unchanged'}

seen_slugs, seen_titles = {}, {}
for path in sorted(glob.glob(os.path.join(ROOT, 'dsh-out', 'batch-*.json'))):
    name = os.path.basename(path)
    try:
        items = json.load(open(path, encoding='utf-8'))
    except ValueError as e:
        errors.append(f'{name}: not valid JSON ({e})')
        continue
    for i, it in enumerate(items):
        tag = f"{name}[{i}] {it.get('slug', '?')}"
        missing = [k for k in FIELDS if not it.get(k)]
        if missing:
            errors.append(f'{tag}: missing {missing}')
            continue
        slug, title = it['slug'], it['new_title']
        if slug not in pending:
            errors.append(f'{tag}: slug is not one of the 130 pending pages')
        if slug in seen_slugs:
            errors.append(f'{tag}: slug repeated (also in {seen_slugs[slug]})')
        seen_slugs[slug] = name
        if not title.endswith(' | Packrivo'):
            errors.append(f'{tag}: title must end with " | Packrivo"')
        if len(title) > 60:
            errors.append(f'{tag}: title {len(title)} chars > 60')
        if len(it['new_description']) > 155:
            errors.append(f"{tag}: description {len(it['new_description'])} chars > 155")
        key = title.lower()
        if key in seen_titles or key in done_titles:
            errors.append(f'{tag}: duplicate title "{title}"')
        seen_titles[key] = tag
        for field in ('new_title', 'new_h1', 'new_description'):
            m = BANNED.search(it[field])
            if m:
                errors.append(f'{tag}: banned term "{m.group(0)}" in {field}')
        for lang in LANGS:
            tr = it['i18n'].get(lang) or {}
            if not all(tr.get(k) for k in ('title', 'h1', 'description')):
                errors.append(f'{tag}: i18n {lang} incomplete')
            elif 'Packrivo' not in tr['title']:
                errors.append(f'{tag}: i18n {lang} title lost "Packrivo"')
            elif len(tr['title']) > 70:
                warnings.append(f"{tag}: i18n {lang} title {len(tr['title'])} chars")

left = [s for s in pending if s not in seen_slugs]

ui = os.path.join(ROOT, 'dsh-out', 'ui-strings.json')
ui_count = 0
if os.path.exists(ui):
    try:
        strings = json.load(open(ui, encoding='utf-8'))
    except ValueError as e:
        errors.append(f'ui-strings.json: not valid JSON ({e})')
        strings = {}
    ui_count = len(strings)
    for en, tr in strings.items():
        vars_en = sorted(re.findall(r'\{[^}]+\}', en))
        for lang in LANGS:
            t = tr.get(lang)
            if not t:
                errors.append(f'ui-strings "{en[:40]}": {lang} missing')
            elif sorted(re.findall(r'\{[^}]+\}', t)) != vars_en:
                errors.append(f'ui-strings "{en[:40]}": {lang} placeholders changed')

print(f'pages done: {len(seen_slugs)}/{len(pending)}   still to do: {len(left)}   ui strings: {ui_count}')
for w in warnings:
    print('WARN ', w)
for e in errors:
    print('ERROR', e)
print('RESULT', 'FAIL' if errors else 'PASS')
sys.exit(1 if errors else 0)
