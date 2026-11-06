# -*- coding: utf-8 -*-
"""Self-checks for dsh-out/31b-i18n.csv. Reads the produced artifact back.

Hard checks (must be 0 errors):
  C1  row count / per-language count / per-string count
  C2  header, bucket vocabulary, origin column
  C3  placeholder ({Topic}) set per string equals the English source's set
  C4  empty values
  C5  digits + protected ASCII tokens (Packrivo, WhatsApp) preserved
  C6  place-name token present (verbatim or approved local form)
  C7  "supplier" token present in the 3 supplier rows
  C8  permission token present in the photo-credit row
  C9  no factory/manufacturer self-description
  C10 coverage vs missing.json (28 expected, 9 present must not appear)
"""
import csv
import io
import json
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, '..', '..'))
CSV_PATH = os.path.join(ROOT, 'dsh-out', '31b-i18n.csv')
MISSING = os.path.join(HERE, 'missing.json')

LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
PRESENT_DO_NOT_TOUCH = [
    'Custom Printed Tissue Paper', 'Guides', 'Bags & pouches',
    'Packaging examples', 'Materials & print', 'Supplier briefs',
    'Samples & approval', 'Structures & fit', 'Shipping & rules',
]

PLACE_FORMS = {
    'Dongguan': {
        'ar': ['دونغقوان'], 'de': ['Dongguan'], 'es': ['Dongguan'], 'fr': ['Dongguan'],
        'it': ['Dongguan'], 'ja': ['東莞'], 'ko': ['둥관'], 'nl': ['Dongguan'],
        'pt': ['Dongguan'], 'zh-CN': ['东莞'], 'zh-TW': ['東莞'],
    },
    'Shantou': {
        'ar': ['شانتو'], 'de': ['Shantou'], 'es': ['Shantou'], 'fr': ['Shantou'],
        'it': ['Shantou'], 'ja': ['汕頭'], 'ko': ['산터우'], 'nl': ['Shantou'],
        'pt': ['Shantou'], 'zh-CN': ['汕头'], 'zh-TW': ['汕頭'],
    },
}

SUPPLIER_TOKEN = {
    'ar': 'المورد', 'de': 'ieferant', 'es': 'proveedor', 'fr': 'fournisseur',
    'it': 'fornitore', 'ja': 'サプライヤー', 'ko': '공급업체', 'nl': 'everancier',
    'pt': 'fornecedor', 'zh-CN': '供应商', 'zh-TW': '供應商',
}

PERMISSION_TOKEN = {
    'ar': 'بإذن', 'de': 'Erlaubnis', 'es': 'permiso', 'fr': 'autorisation',
    'it': 'autorizzazione', 'ja': '使用許諾', 'ko': '허가', 'nl': 'toestemming',
    'pt': 'autorização', 'zh-CN': '许可', 'zh-TW': '許可',
}

SELF_DESCRIPTION = [
    'our factory', 'our own factory', 'our factory in', 'our plant', 'we manufacture',
    'unsere Fabrik', 'unser Werk', 'wir produzieren', 'nuestra fábrica', 'fabricamos',
    'notre usine', 'nous fabriquons', 'la nostra fabbrica', 'produciamo',
    '当社の工場', '私たちの工場', '自社工場', '우리 공장', '자체 공장', 'onze fabriek',
    'a nossa fábrica', 'fabricamos', 'مصنعنا', '我们的工厂', '我们的工厂', '我們的工廠', '我厂',
]

errors = []
notes = []


def err(check, msg):
    errors.append('%s: %s' % (check, msg))


def digits(s):
    return sorted(re.findall(r'\d+', s))


with io.open(CSV_PATH, encoding='utf-8', newline='') as f:
    raw = f.read()
rows = list(csv.reader(io.StringIO(raw)))
header, data = rows[0], rows[1:]

# ---- C2 header / vocabulary -------------------------------------------------
if header != ['en', 'bucket', 'lang', 'value', 'origin(new)']:
    err('C2', 'header mismatch: %r' % (header,))
bad_bucket = [r for r in data if r[1] not in ('title', 'attr', 'block', 'text')]
if bad_bucket:
    err('C2', 'bucket out of vocabulary: %r' % (bad_bucket[:3],))
bad_origin = [r for r in data if r[4] != 'new']
if bad_origin:
    err('C2', 'origin != new: %r' % (bad_origin[:3],))
if len({r[1] for r in data}) - 1:
    pass

# ---- C1 counts --------------------------------------------------------------
by_en = {}
by_lang = {}
for r in data:
    by_en.setdefault(r[0], []).append(r)
    by_lang.setdefault(r[2], []).append(r)
print('C1 strings=%d rows=%d langs=%d' % (len(by_en), len(data), len(by_lang)))
if len(data) != 308:
    err('C1', 'expected 308 data rows, got %d' % len(data))
if len(by_lang) != 11 or set(by_lang) != set(LANGS):
    err('C1', 'language set mismatch: %r' % (sorted(by_lang),))
for lang in LANGS:
    n = len(by_lang.get(lang, []))
    if n != 28:
        err('C1', '%s has %d rows, expected 28' % (lang, n))
for en, rs in by_en.items():
    if len(rs) != 11:
        err('C1', '%r has %d language rows' % (en, len(rs)))
    buckets = {r[1] for r in rs}
    if len(buckets) != 1:
        err('C1', '%r spans multiple buckets %r' % (en, buckets))

# ---- C3 placeholders --------------------------------------------------------
ph_errors = 0
for en, rs in by_en.items():
    want = set(re.findall(r'\{[A-Za-z]+\}', en))
    for r in rs:
        got = set(re.findall(r'\{[A-Za-z]+\}', r[3]))
        if got != want:
            ph_errors += 1
            err('C3', '%s placeholder diff for %r: %r vs %r' % (r[2], en, got, want))
print('C3 placeholder diffs=%d' % ph_errors)

# ---- C4 empty ---------------------------------------------------------------
empty = [r for r in data if not r[3].strip()]
print('C4 empty values=%d' % len(empty))
if empty:
    err('C4', 'empty values: %r' % ([(r[0], r[2]) for r in empty],))

# ---- C5 digits + protected ascii tokens ------------------------------------
tok_errors = 0
PROTECTED = ['Packrivo', 'WhatsApp']
for en, rs in by_en.items():
    for r in rs:
        v = r[3]
        if digits(v) != digits(en):
            tok_errors += 1
            err('C5', '%s digits %r vs en %r for %r' % (r[2], digits(v), digits(en), en))
        for tok in PROTECTED:
            if en.count(tok) != v.count(tok):
                tok_errors += 1
                err('C5', '%s token %s count %d vs en %d for %r'
                    % (r[2], tok, v.count(tok), en.count(tok), en))
print('C5 digit/token errors=%d' % tok_errors)

# ---- C6 place names ---------------------------------------------------------
place_errors = 0
place_report = []
for en, rs in by_en.items():
    for place, forms in PLACE_FORMS.items():
        if place not in en:
            continue
        for r in rs:
            ok = place in r[3] or any(f in r[3] for f in forms[r[2]])
            if not ok:
                place_errors += 1
                err('C6', '%s missing %s for %r' % (r[2], place, en))
            else:
                used = place if place in r[3] else forms[r[2]][0]
                place_report.append((place, r[2], used))
print('C6 place-name errors=%d' % place_errors)
print('C6 place forms used:')
for place in PLACE_FORMS:
    line = ', '.join('%s=%s' % (l, next((u for p, lg, u in place_report
                                         if p == place and lg == l), '-'))
                     for l in LANGS)
    print('   %-9s %s' % (place, line))

# ---- C7 supplier token ------------------------------------------------------
sup_errors = 0
SUP_ROWS = [en for en in by_en if en.startswith('Supplier')]
print('C7 supplier rows=%d' % len(SUP_ROWS))
for en in SUP_ROWS:
    for r in by_en[en]:
        if SUPPLIER_TOKEN[r[2]] not in r[3]:
            sup_errors += 1
            err('C7', '%s missing supplier token in %r' % (r[2], en))
print('C7 supplier token errors=%d' % sup_errors)

# ---- C8 permission ----------------------------------------------------------
perm_errors = 0
CREDIT = 'Supplier photos, used with permission.'
for r in by_en.get(CREDIT, []):
    if PERMISSION_TOKEN[r[2]] not in r[3]:
        perm_errors += 1
        err('C8', '%s missing permission token: %r' % (r[2], r[3]))
print('C8 permission token errors=%d' % perm_errors)

# ---- C9 self-description ----------------------------------------------------
hits = []
for r in data:
    low = r[3].lower()
    for phrase in SELF_DESCRIPTION:
        if phrase.lower() in low:
            hits.append((r[2], phrase, r[0]))
print('C9 factory self-description hits=%d' % len(hits))
for h in hits:
    err('C9', 'self-description %r in %s for %r' % (h[1], h[0], h[2]))

# ---- C10 coverage -----------------------------------------------------------
with io.open(MISSING, encoding='utf-8') as f:
    missing = json.load(f)
missing_en = [m['en'] for m in missing]
missing_bucket = {m['en']: m['bucket'] for m in missing}
print('C10 missing.json rows=%d' % len(missing_en))
if len(set(missing_en)) != len(missing_en):
    err('C10', 'duplicate entries in missing.json')
for en in missing_en:
    if en not in by_en:
        err('C10', 'missing from CSV: %r' % en)
for en in by_en:
    if en not in missing_bucket:
        err('C10', 'CSV contains a string that is not in the missing list: %r' % en)
    elif by_en[en][0][1] != missing_bucket[en]:
        err('C10', 'bucket mismatch for %r: csv=%s missing.json=%s'
            % (en, by_en[en][0][1], missing_bucket[en]))
leaked = [en for en in PRESENT_DO_NOT_TOUCH if en in by_en]
print('C10 already-present strings inside CSV=%d' % len(leaked))
if leaked:
    err('C10', 'strings that already exist were re-emitted: %r' % (leaked,))

# ---- extra: English label decision, bucket distribution ---------------------
eng_vals = {r[3] for r in by_en.get('English', [])}
print('X  English values=%r' % (sorted(eng_vals),))
if eng_vals != {'English'}:
    err('X', 'English label should stay "English": %r' % (sorted(eng_vals),))
dist = {}
for r in data:
    dist[r[1]] = dist.get(r[1], 0) + 1
print('X  bucket distribution (rows): %r' % (dist,))
print('X  strings per bucket: %r' % ({b: len([1 for en, rs in by_en.items()
                                              if rs[0][1] == b])
                                      for b in ('title', 'attr', 'block', 'text')},))

print('')
print('=== RESULT ===')
if errors:
    print('FAIL  %d error(s)' % len(errors))
    for e in errors:
        print('  - %s' % e)
    sys.exit(1)
print('PASS  0 errors across C1-C10')
