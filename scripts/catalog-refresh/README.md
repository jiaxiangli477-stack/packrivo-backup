# Catalog refresh — local QA preparation (incomplete)

This directory currently implements read-only WordPress inventory export, repeatable QA inventory and review-sheet generation. It does not update WordPress, generate buyer keywords, translations, schemas, sitemap, or a deployable package. Do not run it as a production import.

## Inventory

From `E:\破甲工作区\packrivo-live` with Docker on PATH:

```powershell
docker compose run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup\scripts\catalog-refresh:/task' cli wp eval-file /task/export-inventory.php --skip-plugins --skip-themes > 'E:\破甲工作区\packrivo-backup\scripts\catalog-refresh\local-catalog-inventory.csv'
```

The CSV records raw Yoast noindex metadata, not effective robots output. The imported local DB is a historical snapshot, not current production.

## Re-run QA after image batches

```powershell
$env:PYTHONDONTWRITEBYTECODE='1'
$env:PYTHONIOENCODING='utf-8'
& 'E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe' 'E:\破甲工作区\packrivo-backup\scripts\catalog-refresh\catalog_refresh.py' --index 'E:\破甲工作区\audit-parkrivo\codex-output\交付索引.csv' --sources 'D:\Codex\packrivo-image-work\sources' --qa 'E:\破甲工作区\audit-parkrivo\catalog-qa.csv' --inventory 'E:\破甲工作区\packrivo-backup\scripts\catalog-refresh\local-catalog-inventory.csv' --report 'E:\破甲工作区\packrivo-backup\scripts\catalog-refresh\inventory-summary.json'
& 'E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe' 'E:\破甲工作区\packrivo-backup\scripts\catalog-refresh\prepare-review.py'
node 'E:\破甲工作区\packrivo-backup\scripts\catalog-refresh\review-sheets.cjs' 'E:\破甲工作区\packrivo-v2\node_modules\sharp' 'E:\破甲工作区\.tmp\catalog-review-manifest.json'
Copy-Item -LiteralPath 'E:\破甲工作区\.tmp\catalog-review-manifest.json' -Destination 'E:\破甲工作区\audit-parkrivo\codex-output\_qa-review\manifest.json'
```

Review sheets show source, main and then available angle/detail in that order; the local manifest records the exact source paths. Missing output remains pending. A passed main image requires all four checks and reasons plus both size files. Changed source or main hashes reset its approval. Each variant has its own independent fields; no image is automatically approved. Known rights/missing-source and embossing exceptions remain rejected. The script currently checks presence/hash, not actual 480/1200 pixel dimensions: verify dimensions before approving. Review sheets are aids, not proof of quality or supplier permission. Do not edit the image-generation index from this script.

## Still required before any deployment package

Complete per-image visual review and rights verification; implement and test SKU/content generation, existing-page noindex/sitemap handling, buyer-keyword and 11-language dictionary gates, media import idempotence, rollback snapshot, schema/HTTP checks and a real deployment package. Image-generation deliverables are AI-generated from supplier images; photo caption accuracy needs review. Until all requirements are met, this folder is preparation only.
