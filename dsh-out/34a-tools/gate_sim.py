# -*- coding: utf-8 -*-
"""Simulate real gate runs against a TEMP copy of the dictionaries.

Run A: gate as-is, its --csv stays dsh-out/34a-new-strings.csv (the real input).
Run B: gate against a temp CSV copy where the 5 authoritative meta rows are
       replaced by the prompt-supplied text (what dsh-out/34a-i18n.csv delivers).
Nothing under deploy/ is modified; temp dicts live in E:\\破甲工作区\\.tmp\\34a.
"""
import csv, io, json, os, shutil, subprocess

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
CSV = ROOT + r'\dsh-out\34a-i18n.csv'
SRC = ROOT + r'\dsh-out\34a-new-strings.csv'
TMPROOT = r'E:\破甲工作区\.tmp\34a'
TMPDICT = TMPROOT + r'\i18n-merged'
TMPCSV = TMPROOT + r'\34a-new-strings-auth.csv'
GATE = ROOT + r'\deploy\34a-i18n-gate.py'
PY = r'C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe'
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS = ('title','attr','block','text')

kj = json.load(io.open(TOOLS + r'\keys.json', encoding='utf-8'))
FINAL = kj['final']          # "<url> <source>" -> authoritative english

# ---- 1. merge translations into temp dictionary copies -------------------
if os.path.isdir(TMPDICT):
    shutil.rmtree(TMPDICT)
os.makedirs(TMPDICT)
for lang in LANGS:
    shutil.copyfile(ROOT + r'\deploy\i18n-merged\%s-dict.json' % lang,
                    os.path.join(TMPDICT, '%s-dict.json' % lang))
merged = {l: json.load(io.open(os.path.join(TMPDICT, '%s-dict.json' % l), encoding='utf-8-sig'))
          for l in LANGS}
n = 0
with io.open(CSV, encoding='utf-8-sig', newline='') as f:
    for row in csv.DictReader(f):
        lang, b = row['lang'], row['bucket']
        if lang not in merged or b not in BUCKETS:
            raise SystemExit('bad row %r' % row)
        merged[lang][b][row['en']] = row['value']
        n += 1
for lang in LANGS:
    io.open(os.path.join(TMPDICT, '%s-dict.json' % lang), 'w', encoding='utf-8',
            newline='\n').write(json.dumps(merged[lang], ensure_ascii=False, indent=1))

# ---- 2. temp CSVs ------------------------------------------------------
with io.open(SRC, encoding='utf-8-sig', newline='') as f:
    rd = csv.DictReader(f)
    fields = rd.fieldnames
    src_rows = list(rd)

SKIPPED = ('Custom Packaging for Jewelry, Candles & Gifts | Packrivo',)
NO_TL = set()          # (url, source) rows this batch deliberately leaves alone

# B: 把 5 条权威 meta 的英文换成提示词给定值
rows_b, swapped = [], 0
for row in src_rows:
    row = dict(row)
    if row['source'] in ('yoast_meta', 'hub_desc') and (row['url'] + ' meta') in FINAL:
        new = FINAL[row['url'] + ' meta']
        if row['new_en'] != new:
            swapped += 1
        row['new_en'] = new
    rows_b.append(row)

# C: B 再去掉本批明确不译的两处（首页 title 两行、/cases/ meta 一行）
rows_c = []
for row in rows_b:
    key = row['new_en']
    if key in SKIPPED or (row['url'] == '/cases/' and row['source'] in ('yoast_meta', 'hub_desc')):
        NO_TL.add((row['url'], row['source']))
        continue
    rows_c.append(row)

TMPCSV_B = TMPROOT + r'\34a-new-strings-auth.csv'
TMPCSV_C = TMPROOT + r'\34a-new-strings-auth-scoped.csv'
for path, rows in ((TMPCSV_B, rows_b), (TMPCSV_C, rows_c)):
    with io.open(path, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=fields, lineterminator='\n')
        w.writeheader()
        w.writerows(rows)


def run_gate(csv_path, tag):
    rep = TMPROOT + r'\gate-report-%s.csv' % tag
    proc = subprocess.run([PY, '-X', 'utf8', GATE, '--csv', csv_path,
                           '--dict-dir', TMPDICT, '--report', rep],
                          capture_output=True, text=True, encoding='utf-8')
    return proc.returncode, (proc.stdout or '') + (proc.stderr or '')


blocks, summary = [], []
summary.append('merged_rows=%d temp_csv_meta_swapped=%d dropped_rows=%d'
               % (n, swapped, len(src_rows) - len(rows_c)))
for tag, path in (('A_real_csv', SRC), ('B_auth_csv', TMPCSV_B), ('C_auth_scoped', TMPCSV_C)):
    code, out = run_gate(path, tag)
    blocks.append('===== run %s  csv=%s  exit=%d\n%s' % (tag, path, code, out.rstrip()))
    miss = []
    for line in out.splitlines():
        if ' missing=' in line:
            lang, val = line.split(' missing=')
            miss.append('%s=%s' % (lang.strip(), val.strip()))
        elif 'GATE ' in line or 'csv_rows=' in line:
            summary.append('%s %s' % (tag, line.strip()))
    summary.append('%s per-lang %s' % (tag, ' '.join(miss)))

io.open(TOOLS + r'\gate.txt', 'w', encoding='utf-8', newline='\n').write(
    '\n'.join(summary) + '\n\n' + '\n\n'.join(blocks) + '\n')
print('\n'.join(summary))
print()
print('\n\n'.join(blocks))
