# -*- coding: utf-8 -*-
"""29y dict reset: restore the 11 merged dictionaries from the .pre29y-card
snapshots (state before any 29y dict work) and sync to the rehearsal uploads.
Usage:
  python reset-dicts-29y.py [--dry-run]
"""
import os, sys, io, shutil, hashlib

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

MERGED = r'E:\破甲工作区\packrivo-backup\deploy\i18n-merged'
UP = r'E:\破甲工作区\.tmp\28q\rehearsal\home\web\html\www.packrivo.com\wordpress\wp-content\uploads\packrivo-i18n'
LANGS = ['de', 'fr', 'es', 'it', 'nl', 'pt', 'ar', 'ja', 'ko', 'zh-CN', 'zh-TW']
dry = '--dry-run' in sys.argv

for l in LANGS:
    snap = os.path.join(MERGED, f'{l}-dict.json.pre29y-card')
    cur = os.path.join(MERGED, f'{l}-dict.json')
    if not os.path.exists(snap):
        print(f'{l}: no .pre29y-card snapshot, skip')
        continue
    if not dry:
        shutil.copyfile(snap, cur)
        shutil.copyfile(cur, os.path.join(UP, f'{l}-dict.json'))
    h = hashlib.md5(open(cur, 'rb').read()).hexdigest()
    print(f'{l}: restored from pre29y-card md5={h[:12]} (dry={dry})')
print('done (dry=%s)' % dry)
