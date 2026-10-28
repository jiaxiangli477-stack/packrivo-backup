# 27i hub metadata import

The approved copy is `dsh-out/hubs-27i.json`. Do not edit its English or translations. This package changes SEO metadata only; post_title, H1, body and URLs remain untouched. The child theme projects the existing metadata fields for the eight hubs, including archives and pages whose parent description output is absent. It does not hardcode customer copy.

Local commands, from `E:\破甲工作区\packrivo-live`:

```powershell
$env:PATH='D:\Docker\App\resources\bin;'+$env:PATH
docker compose -f docker-compose.yml -f 'E:\破甲工作区\.tmp\packrivo-27c-compose.yml' -f 'E:\破甲工作区\.tmp\packrivo-27g-i18n-compose.yml' -f 'E:\破甲工作区\.tmp\packrivo-27i-data-compose.yml' run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup:/package' cli wp eval-file /package/scripts/hubs-27i/import.php apply /package http://localhost:8081
```

Replace apply with plan, backup, verify or rollback-check for non-writing inspection/backup modes; rollback is an explicitly writing restore and was not executed. Original metadata is exclusively created once at `deploy/rollback-27i.json`; repeat runs reuse it. expected-site and approved-input hashes are required to match. The importer uses a transaction and checks all untouched post fields and unrelated meta.

`/inquiry/` preserves the actual pre-existing English description, including its extra artwork/reference-file sentence. Its approved short description remains in the input file unchanged. Existing production full-sentence translations are retained; do not map the long sentence to the short translation.

Dictionary regeneration:

```powershell
node E:\破甲工作区\packrivo-backup\scripts\hubs-27i\merge-i18n.mjs --root E:\破甲工作区\packrivo-backup --base E:\破甲工作区\.tmp\packrivo-27e-dictionaries --js-base E:\破甲工作区\.tmp\packrivo-hubs-27i\js-baseline --paths-base E:\破甲工作区\.tmp\packrivo-hubs-27i\paths-baseline.json
node E:\破甲工作区\packrivo-backup\scripts\hubs-27i\verify-merge.mjs E:\破甲工作区\.tmp\packrivo-27e-dictionaries E:\破甲工作区\.tmp\packrivo-hubs-27i\js-baseline E:\破甲工作区\.tmp\packrivo-hubs-27i\paths-baseline.json
```

`--metadata-backup` optionally selects a separately captured backup for the inquiry preservation rule. `prior-i18n-27g.json` freezes all previous proposals. The main dictionaries use the 27e read-only production baseline; JS and paths were read-only downloaded during this task. Production values take precedence over reviewed proposals; all conflicts are written out. Runtime exact adds one button entry per language; all old entries and patterns remain. paths.json adds only /blog/.

Validation:

- `curl-hubs.mjs` uses curl raw HTML for 8 × en/ar/de/ja, without DOM metadata counting.
- `verify-output.mjs` separates strict dsh equality from production-priority/inquiry exceptions.
- `verify-sticky.cjs` proves the existing runtime translates a later textContent rewrite and captures a default 375px screenshot.

The current 32 responses have one title/description each. 27 match dsh exactly; 5 have documented preservation exceptions. Do not claim all 32 are verbatim dsh. Eight English H1 values are unchanged. Deploy and rollback commands are prepared in deploy/README.md but have not been run on production.
