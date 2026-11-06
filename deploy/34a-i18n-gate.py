#!/usr/bin/env python3
"""Block 34a deployment until every new English key has 11 translations."""
from pathlib import Path
import argparse
import csv
import json
import sys

ROOT = Path(__file__).resolve().parent.parent
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
BUCKETS = ('title','attr','block','text')
QUOTE_TITLE = 'Get a Custom Packaging Quote | Packrivo'


def lookup_buckets(row, key):
    source = row.get('source', '')
    if source in ('yoast_title', 'hub_title'):
        return ('title',)
    if source in ('yoast_meta', 'hub_desc'):
        return ('attr',)
    if source == 'h1':
        return ('block', 'text')
    if source == 'theme' and row.get('url') == '/get-a-quote/':
        return ('title',) if key == QUOTE_TITLE else ('text', 'block')
    raise ValueError(f'unknown source or theme URL: {source!r}')


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--csv', type=Path, default=ROOT/'dsh-out/34a-new-strings.csv')
    parser.add_argument('--dict-dir', type=Path, default=ROOT/'deploy/i18n-merged')
    parser.add_argument('--report', type=Path)
    args = parser.parse_args()
    try:
        with args.csv.open(encoding='utf-8-sig', newline='') as f:
            reader = csv.DictReader(f)
            if not reader.fieldnames or not {'source','url','new_en'}.issubset(reader.fieldnames):
                raise ValueError('CSV missing source, url or new_en column')
            rows = list(reader)
        if not rows or any(not isinstance(row.get('new_en'), str) or not row['new_en'].strip() for row in rows):
            raise ValueError('CSV contains no rows or an empty new_en key')
        requirements = {}
        for row in rows:
            key = row['new_en'].replace('\r\n','\n').replace('\r','\n')
            requirements.setdefault(key, set()).add(lookup_buckets(row, key))
        keys = list(requirements)
    except (OSError, UnicodeError, csv.Error, ValueError) as exc:
        print('GATE INPUT ERROR:', exc)
        return 2
    print(f'csv_rows={len(rows)} unique_keys={len(keys)}')
    missing = []
    errors = 0
    for lang in LANGS:
        try:
            data = json.loads((args.dict_dir / (lang+'-dict.json')).read_text(encoding='utf-8-sig'))
            if not isinstance(data, dict) or any(not isinstance(data.get(b), dict) for b in BUCKETS):
                raise ValueError('site dictionary missing required buckets')
            absent = [key for key in keys if any(not any(isinstance(data[b].get(key),str) and data[b][key].strip() for b in group) for group in requirements[key])]
            missing.extend((lang,key,';'.join('/'.join(group) for group in sorted(requirements[key]))) for key in absent)
            print(f'{lang} missing={len(absent)}')
        except (OSError, UnicodeError, json.JSONDecodeError, ValueError) as exc:
            errors += 1
            print(f'{lang} ERROR {exc}')
    if args.report:
        try:
            with args.report.open('w', encoding='utf-8-sig', newline='') as f:
                writer = csv.writer(f)
                writer.writerow(['language','english_key','required_buckets'])
                writer.writerows(missing)
        except OSError as exc:
            print('REPORT ERROR:', exc)
            return 2
    print(f'GATE {"PASS" if not missing and not errors else "FAIL"} missing_entries={len(missing)} errors={errors}')
    return 0 if not missing and not errors else 1


if __name__ == '__main__':
    sys.exit(main())
