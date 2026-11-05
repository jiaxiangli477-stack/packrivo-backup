# 33b-2 本地验收报告

基于 33b 提交 `8586c17`，在独立 33b 实例完成运输文案与 Product schema 条件输出调整。未合并、未部署，未操作 32a/33a 容器或工作区。

无可算价格的页面不输出 Product，也不伪造 AggregateOffer；BreadcrumbList 和 FAQPage 保留。更新后的英文清单仍为 85 条。旧有全站禁词存量命中属于 33b 既有未通过项，本轮未改写存量。

## Schema 脚本

命令：`python tests/33b-schema-test.py`

```text
product-magnetic-jewelry-box HTTP 200 JSON_BLOCKS 3 PRODUCT 1 BREADCRUMB 1 FAQ 1 SERVICE 1 OFFER 1 OFFER_DETAIL USD low=1.45 high=3.41 expected=1.45/3.41 tiers=7 ERRORS 0
product-drawer-jewelry-box HTTP 200 JSON_BLOCKS 2 PRODUCT 0 BREADCRUMB 1 FAQ 1 SERVICE 1 OFFER 0 OFFER_DETAIL none ERRORS 0
product-collapsible-magnetic-gift-box HTTP 200 JSON_BLOCKS 2 PRODUCT 0 BREADCRUMB 1 FAQ 1 SERVICE 1 OFFER 0 OFFER_DETAIL none ERRORS 0
TOTAL_PAGES 3 ERRORS 0
exit=0
```

## curl 和 grep

对三个页面逐一执行 `curl.exe -fsS URL`，以 `rg --only-matching --fixed-strings --text` 分别统计旧语句和完整新语句。

```text
product-magnetic-jewelry-box curl_exit=0 old_grep_count=0 new_grep_count=1
product-drawer-jewelry-box curl_exit=0 old_grep_count=0 new_grep_count=1
product-collapsible-magnetic-gift-box curl_exit=0 old_grep_count=0 new_grep_count=1
CSV_ROWS 85 OLD_COUNT 0 NEW_COUNT 1
exit=0
```

## 五个非试点页面

命令：`node .rehearsal-33b/compare-baseline.cjs`；对比的是原 33b 实例在新模板改动之前保存的完整 HTML。

```text
COMPARE product-auto-bottom-display-carton HTTP 200 BYTES_BEFORE 96197 BYTES_AFTER 96197 SHA256 6995a45557c883bcf4d3ec157cc7d90d924f319adb22c3f49764656d0c2abe81 BYTE_EQUAL true
COMPARE product-bookend-folding-carton HTTP 200 BYTES_BEFORE 96355 BYTES_AFTER 96355 SHA256 2075899424108142b7ddccf79070ab8b9ffe51765d015d2b2e92e4ae6cbb5e5a BYTE_EQUAL true
COMPARE product-molded-pulp-corner-cradle HTTP 200 BYTES_BEFORE 86358 BYTES_AFTER 86358 SHA256 9fcc7aa7426bbaf73ba63ad9d45ad89ae7aac50ce5dae4e5eb6b4d5a2c50fb56 BYTE_EQUAL true
COMPARE product-slide-in-card-blister HTTP 200 BYTES_BEFORE 95393 BYTES_AFTER 95393 SHA256 e7c614ee49bbf5fe8edb04166b923ccaecc40d19b17a04b2fffb8de34a6299b0 BYTE_EQUAL true
COMPARE product-six-corner-pre-glued-carton HTTP 200 BYTES_BEFORE 96221 BYTES_AFTER 96221 SHA256 f2e1795bad314d7be241bb0feaacb165e0aed693363dd18d79d3008993de72a0 BYTE_EQUAL true
TOTAL 5 FAILURES 0
exit=0
```

## 环境核对

```text
TEMPLATE_WORKTREE_RUNTIME_SHA256 fd6dc964fc2b2509b1aa76221b516470920581f8e9ad9a2c4612356e85de3d5a MATCH true
```

Schema 是本地脚本检查，并非 Google Rich Results Test。
