#!/usr/bin/env python3
"""Merge the 32a dictionaries into main without dropping either side's keys.

Run at the repository root during the 32a merge. Default: inspect only;
--write validates all eleven languages before writing any dictionary.
"""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import sys

LANGS = ('ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW')
BASE = '1726bb8006180334589375b5f1c082914c970025'
MAIN = '50e000f9d4c0d2d28134c5a364391d69ffb69bd4'
BRANCH = '9ec011ab5d41c41c8942ba7417196b7767cc9316'
MISSING = object()


def parse_json(raw):
    def pairs(items):
        out = {}
        for key, value in items:
            if key in out:
                raise ValueError(f'duplicate JSON key: {key}')
            out[key] = value
        return out
    return json.loads(raw, object_pairs_hook=pairs)


def merge_dicts(base, main, branch):
    if set(base) != set(main) or set(base) != set(branch):
        raise ValueError('dictionary bucket sets differ')
    result = copy.deepcopy(main)
    conflicts = []
    for bucket in main:
        if not all(isinstance(d[bucket], dict) for d in (base, main, branch)):
            raise ValueError(f'non-object bucket: {bucket}')
        b, m, t = base[bucket], main[bucket], branch[bucket]
        for key in b.keys() | m.keys() | t.keys():
            old, left, right = b.get(key, MISSING), m.get(key, MISSING), t.get(key, MISSING)
            # A deletion cannot satisfy both preserving changes and key union;
            # stop for human review instead of silently discarding either side.
            if old is not MISSING and (left is MISSING or right is MISSING):
                conflicts.append((bucket, key))
            elif left != old and right != old and left != right:
                conflicts.append((bucket, key))
        # Iterating the branch preserves its own order for new keys while main's
        # existing order remains untouched, including keys whose values change.
        for key, right in t.items():
            old, left = b.get(key, MISSING), m.get(key, MISSING)
            if right != old and (left == old or left == right):
                result[bucket][key] = right
    return result, sorted(set(conflicts))


def flatten(data):
    return {(bucket, key): value for bucket, values in data.items() for key, value in values.items()}


def read_revision(revision, path):
    return subprocess.check_output(['git','show',revision+':'+path]).decode('utf-8')


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--write', action='store_true')
    options = parser.parse_args()
    output = []
    failures = 0
    for lang in LANGS:
        path = f'deploy/i18n-merged/{lang}-dict.json'
        try:
            base_raw, main_raw, branch_raw = (read_revision(rev, path) for rev in (BASE, MAIN, BRANCH))
            base, left, right = map(parse_json, (base_raw, main_raw, branch_raw))
            # This also enforces the original main indentation, Unicode style,
            # order and final newline; do not silently reformat another format.
            if json.dumps(left, ensure_ascii=False, indent=2)+'\n' != main_raw:
                raise ValueError('main JSON formatting is not preserved by serializer')
            merged, conflicts = merge_dicts(base, left, right)
            before, main_flat, branch_flat, after = map(flatten, (base, left, right, merged))
            if set(after) != set(main_flat) | set(branch_flat):
                raise ValueError('merged key set is not the union')
            left_added = sum(key not in main_flat for key in after)
            left_changed = sum(key in main_flat and after[key] != main_flat[key] for key in after)
            right_added = sum(key not in branch_flat for key in after)
            right_changed = sum(key in branch_flat and after[key] != branch_flat[key] for key in after)
            for key in after:
                if key in main_flat and after[key] != main_flat[key] and not (key in branch_flat and branch_flat[key] != before.get(key, MISSING)):
                    raise ValueError(f'unexpected change relative to main: {key!r}')
                if key in branch_flat and after[key] != branch_flat[key] and not (key in main_flat and main_flat[key] != before.get(key, MISSING)):
                    raise ValueError(f'unexpected change relative to branch: {key!r}')
            print(f'{lang} main={len(main_flat)} 32a={len(branch_flat)} merged={len(after)} union={len(set(main_flat)|set(branch_flat))} conflicts={len(conflicts)} vs_main_added={left_added} vs_main_changed={left_changed} vs_32a_added={right_added} vs_32a_changed={right_changed}')
            for bucket, key in conflicts:
                print(f'  CONFLICT {lang} {bucket} {key!r}')
            failures += len(conflicts)
            output.append((path, json.dumps(merged, ensure_ascii=False, indent=2)+'\n'))
        except (OSError, subprocess.CalledProcessError, ValueError, TypeError, KeyError) as exc:
            failures += 1
            print(f'ERROR {lang}: {exc}', file=sys.stderr)
    if failures:
        print(f'MERGE_ABORTED errors_or_conflicts={failures}')
        return 1
    if options.write:
        for path, data in output:
            Path(path).write_bytes(data.encode('utf-8'))
        print(f'WRITE_OK dictionaries={len(output)}')
    else:
        print(f'DRY_RUN_OK dictionaries={len(output)}')
    return 0


if __name__ == '__main__':
    sys.exit(main())
