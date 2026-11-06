"""Find which 31b-new-strings.csv rows are absent from the 11 reference dictionaries.

Read-only against deploy/i18n-merged/*.json. Writes nothing except stdout and
dsh-out/31b-tools/missing.json (the working list for the next step).
"""
import csv
import io
import json
import os

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
SRC = os.path.join(ROOT, 'dsh-out', '31b-new-strings.csv')
DICTS = os.path.join(ROOT, 'deploy', 'i18n-merged')
OUT = os.path.join(ROOT, 'dsh-out', '31b-tools', 'missing.json')
LANGS = ['ar', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pt', 'zh-CN', 'zh-TW']
BUCKETS = ['title', 'attr', 'block', 'text']

# bucket per row, by the rule in the task: <title> -> title, alt/button/aria -> attr, rest -> text
BUCKET_OF = {
    '{Topic} | Packrivo': 'title',
    'English': 'attr',
    'Supplier sample room with shelves of rigid gift boxes and jewelry boxes': 'attr',
    'Operator feeding a printed proof sheet into a wide-format digital proofing printer': 'attr',
    'Multi-colour sheet-fed offset printing press in a packaging factory': 'attr',
    'Finished jewelry boxes packed in export cartons before shipping': 'attr',
}


def load(lang):
    p = os.path.join(DICTS, '%s-dict.json' % lang)
    with io.open(p, encoding='utf-8') as f:
        return json.load(f)


def main():
    rows = []
    with io.open(SRC, encoding='utf-8-sig', newline='') as f:
        for r in csv.DictReader(f):
            rows.append(r)
    print('rows in %s: %d' % (os.path.basename(SRC), len(rows)))

    dicts = {l: load(l) for l in LANGS}
    for l in LANGS:
        total = sum(len(dicts[l][b]) for b in BUCKETS)
        print('  %-6s buckets %s  total strings %d' % (
            l, '/'.join(str(len(dicts[l][b])) for b in BUCKETS), total))

    missing, present = [], []
    print('\n%-3s %-6s %s' % ('#', 'state', 'english'))
    for i, r in enumerate(rows, 1):
        en = r['英文原文']
        where = {l: [b for b in BUCKETS if en in dicts[l][b]] for l in LANGS}
        have = [l for l in LANGS if where[l]]
        if len(have) == len(LANGS):
            present.append(en)
            print('%-3d %-6s %s  [all 11 dicts]' % (i, 'HAVE', en))
        else:
            missing.append({
                'en': en,
                'bucket': BUCKET_OF.get(en, 'text'),
                'where': r['出现位置'],
                'pages': r['涉及页面'],
                'have': have,
                'absent': [l for l in LANGS if not where[l]],
            })
            flag = 'MISSING' if not have else 'PARTIAL(%d/11)' % len(have)
            print('%-3d %-6s %s' % (i, flag, en))

    print('\nmissing rows: %d   already present: %d   expected 28 / 9' %
          (len(missing), len(present)))
    if present:
        print('already present (not touched):')
        for en in present:
            print('  -', en)
    partial = [m for m in missing if m['have']]
    if partial:
        print('partial (present in some languages only):')
        for m in partial:
            print('  - %s -> %s' % (m['en'], ','.join(m['have'])))

    with io.open(OUT, 'w', encoding='utf-8', newline='\n') as f:
        json.dump(missing, f, ensure_ascii=False, indent=1)
    print('\nwrote %s (%d rows)' % (OUT, len(missing)))


if __name__ == '__main__':
    main()
