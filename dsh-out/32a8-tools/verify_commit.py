# -*- coding: utf-8 -*-
"""Verify the committed blob: no CR, no BOM, row counts, hash."""
import hashlib, io, subprocess, sys, csv

REPO = r'E:\破甲工作区\packrivo-backup'
PATHS = ['dsh-out/32a8-i18n.csv', 'dsh-out/32a8-i18n-report.md', 'dsh-out/32a8-tools/check_csv.py']

for p in PATHS:
    blob = subprocess.run(['git', '-C', REPO, 'cat-file', '-p', 'HEAD:' + p],
                          capture_output=True).stdout
    disk = io.open(REPO + '\\' + p.replace('/', '\\'), 'rb').read()
    print('%-34s blob=%d disk=%d CR_blob=%d CR_disk=%d bom=%s same=%s' % (
        p.rsplit('/', 1)[-1], len(blob), len(disk), blob.count(b'\r'), disk.count(b'\r'),
        blob[:3] == b'\xef\xbb\xbf', hashlib.sha256(blob).hexdigest() == hashlib.sha256(disk).hexdigest()))

csvp = REPO + r'\dsh-out\32a8-i18n.csv'
raw = io.open(csvp, 'rb').read()
print('csv sha256', hashlib.sha256(raw).hexdigest())
rows = list(csv.reader(io.StringIO(raw.decode('utf-8'))))
print('rows', len(rows) - 1, 'header', rows[0])
langs = {}
notes = {}
for r in rows[1:]:
    langs[r[3]] = langs.get(r[3], 0) + 1
    notes[r[5] or 'empty'] = notes.get(r[5] or 'empty', 0) + 1
print('per-lang', dict(sorted(langs.items())))
print('notes', dict(sorted(notes.items())))
rep = io.open(REPO + r'\dsh-out\32a8-i18n-report.md', encoding='utf-8').read()
print('report lines', rep.count('\n'), 'bytes', len(rep.encode('utf-8')))
print('report has footer', '工作台 #37' in rep)
