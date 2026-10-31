# -*- coding: utf-8 -*-
"""29y G3 fix (derive): split concatenated walker keys+values into per-run fragment keys.

Mechanism (verified 2026-10-30 i959):
- plugin loose-run regex '>([^<>]+)<' matches only individual text runs between tags;
  paragraphs with an inline <a> never match the block regex, so the concatenated
  walker key ("The identifies the named direction. ...") is never looked up.
- Fix: for each residual concatenated sentence starting with "The", derive
    post-run key  = sentence without leading "The" (trimmed)
    post-run value= concatenated value minus leading substitute noun phrase
                    (Die/Der/Das X / Ein X / etc.)
  plus one global key "The" -> "" (empty; removes the orphan article run).
- Values already exist for ALL residual rows (orchestrator translated walker
  segments); no translation channel needed.

Usage:
  python derive-fragments.py --lang de --dry-run
  python derive-fragments.py --lang de            (apply to workspace dict)
"""
import argparse, json, os, re, sys, collections

sys.stdout.reconfigure(encoding='utf-8')
REPO = r'E:\破甲工作区\packrivo-backup'
I907 = r'E:\Ai\ARTEX-Portable\app\data\tasks\67\i907'
MERGED = os.path.join(REPO, 'deploy', 'i18n-merged')

LEAD_NP = re.compile(r'^(?:Die|Der|Das|Eine|Einer|Ein|Den|Dem|Des|Dieser|Diese|Dieses)\s+[A-ZÄÖÜ][a-zäöüß]+\s+')
PREFIXES = ('The ', 'A ')

def norm(s):
    return re.sub(r'\s+', ' ', s).strip()

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--lang', default='de')
    ap.add_argument('--dry-run', action='store_true')
    ap.add_argument('--state', default=os.path.join(I907, 'scan-after-state.json'))
    a = ap.parse_args()
    lang = a.lang

    state = json.load(open(a.state, encoding='utf-8'))
    rows = [r for r in state['rows'] if r['lang'] == lang]
    d = json.load(open(os.path.join(MERGED, f'{lang}-dict.json'), encoding='utf-8'))
    for b in ('block', 'text'):
        d.setdefault(b, {})
    blk = {norm(k): v for k, v in d['block'].items()}
    txt = {norm(k): v for k, v in d['text'].items()}

    plan = []       # (post_key, post_value, src_url)
    no_key = []
    non_the = []
    unparsed = []
    for r in rows:
        if '/blog/' in r['url']:
            continue
        s = norm(r['sentence'])
        pref = next((p for p in PREFIXES if s.startswith(p)), None)
        if pref is None:
            non_the.append((s[:80], r['url']))
            continue
        v = None
        if s in blk:
            v = norm(blk[s])
        elif s in txt:
            v = norm(txt[s])
        if v is None:
            no_key.append((s[:80], r['url']))
            continue
        m = LEAD_NP.match(v)
        if not m:
            unparsed.append((s[:60], v[:70], r['url']))
            continue
        post_key = norm(s[len(pref):])
        post_val = norm(v[m.end():])
        plan.append((post_key, post_val, r['url']))

    print(f'[{lang}] residual rows={len(rows)} | plan post-run keys={len(plan)} | no_key={len(no_key)} | non-The={len(non_the)} | unparsed={len(unparsed)}')
    for s, u in non_the:
        print(f'  NON-THE: {s} | {u}')
    for s, u in no_key:
        print(f'  NO-KEY:  {s} | {u}')
    for s, v, u in unparsed:
        print(f'  UNPARSED: {s} | V={v} | {u}')
    # dedupe plan by post_key
    seen = {}
    for pk, pv, u in plan:
        seen.setdefault(pk, pv)
    print('unique post-run keys:', len(seen))
    for pk, pv in sorted(seen.items()):
        print(f'  + {pk[:70]}')
        print(f'      -> {pv[:80]}')
    if a.dry_run:
        return
    # apply: workspace-based merge (NOT git HEAD); no global article keys
    n_add = 0
    for pk, pv in seen.items():
        bucket = d['block']
        if pk in bucket and norm(str(bucket[pk])) == pv:
            continue
        bucket[pk] = pv
        n_add += 1
    # write with the repo's canonical format (indent=2, sort_keys=False, LF, no BOM)
    p = os.path.join(MERGED, f'{lang}-dict.json')
    with open(p, 'w', encoding='utf-8', newline='\n') as fh:
        json.dump(d, fh, ensure_ascii=False, indent=2, sort_keys=False)
        fh.write('\n')
    raw = open(p, 'rb').read()
    assert not raw.startswith(b'\xef\xbb\xbf') and b'\r\n' not in raw
    d2 = json.load(open(p, encoding='utf-8'))
    print(f'[{lang}] applied: new keys={n_add} total_keys={sum(len(v) for v in d2.values())}')

if __name__ == '__main__':
    main()
