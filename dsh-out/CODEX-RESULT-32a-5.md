# 32a-5 本地验收：博客与页头 MOQ

在 32a 独立工作区和实例执行；没有部署、合并、访问生产、修改词典/quote-i18n 或定时任务。开始状态为 `32a`、HEAD `dbb52be3ba02a389fe161077ce8558196ab2ac1a`，唯一未跟踪项 `.rehearsal-32a/`。上一轮原 Apache 动态挂载仍卡住；本轮页面验收使用同一 32a 容器、数据库和 uploads 的 shadow 代码快照，经 localhost:8083 访问，已同步并校验主题四个改动文件的哈希。此结果不证明原 Apache 已修复。

## 页头和起步价

三个页面原始抓取：
```text
PAGE / HTTP 200 HEADER_250 1 HEADER_500 0
PAGE /products/ HTTP 200 HEADER_250 1 HEADER_500 0
PAGE /jewelry-boxes/ HTTP 200 HEADER_250 1 HEADER_500 0
```
共享产品卡和产品详情价格条以同一个 250 变量传入 `pk_from_price`、显示数量和报价链接；其默认参数仍为 500。产品页样本：`product-window-lid-rigid-gift-box` 的价格条为 `from $5.20 / unit at 250 pcs`，询价链接 `qty=250`。

以下为与引擎的真实对比。`/jewelry-boxes/` 当前是无价格的 `.text-card`，并未使用共享报价卡，因此该页三卡核价**未完成**，检查脚本以 1 退出；没有把不存在的价格当作通过，也没有给该品类增加新的价格 UI。

```text
/ product-window-lid-rigid-gift-box qty=250 card=5.20 engine=5.20 PASS
/ product-hinged-nonmagnetic-rigid-box qty=250 card=5.20 engine=5.20 PASS
/ product-corrugated-roll-end-hinged-lid qty=250 card=4.60 engine=4.60 PASS
/products/ product-auto-bottom-slotted-outer-carton qty=250 card=4.36 engine=4.36 PASS
/products/ product-book-style-rigid-presentation-box qty=250 card=5.20 engine=5.20 PASS
/products/ product-bookend-folding-carton qty=250 card=2.84 engine=2.84 PASS
/jewelry-boxes/ only 0 priced shared cards; NOT TESTED
exit=1
```

核价脚本及抓取样本分别为`tests/32a5-card-check.php` 和本机 `.rehearsal-32a/32a5-card-samples.json`。首页独立 `500` 是价格阶梯表的数量格，非起始 MOQ；500 与 5000 的比较及 500 件 FAQ 参考报价保留。

### `pk_from_price` 调用方及数量

| 调用方 | 数量与用途 |
| --- | --- |
| `template-parts/product-card.php` | 250，卡片显示与 Configure 链接 |
| `page.php` | 250，产品详情价格条及 Configure 链接 |
| `front-page.php` | 默认 500 用于筛选可显示产品；另显式 250 计算 stats 起步价；卡片由共享模板按 250 自算 |
| `template-products-v4.php` | 默认 500，产品价格排序，按要求保持 |
| `functions.php` | 默认 500，检查产品是否可报价；函数自身默认值仍为 500 |
| `deploy/32a4-generate-llms.php` | 默认 500，用于明确标注的产品参考价格；标题已改为 reference prices，避免称为当前卡片价格 |
| `deploy/scripts-29y/gen-llms-prices.php` | 历史生成器，默认 500，本轮未改 |
| `deploy/verification-28m/diag-home-cards.php` | 历史诊断两处，默认 500，本轮未改 |

## 博客分类与迁移

完整逐句表为 `32a-5-blog-hits.csv`；分类单位是含 500 的句子或表格单元格，`price-tier` 也覆盖明确的数量及计算示例。`rewrite` 为 448、508、1621 三篇依赖 500 MOQ 的文章，整篇正文、标题、slug、meta 保留，供 dsh 重写；22 是三篇中所有含 500 的分类条数，不是 grep 目标的命中数。计划按 post ID、slug 和 HTML 原始整句精确绑定，涉及 276 篇的 318 个正文句子。已发布博客的 Yoast title/metadesc/indexable 中，同类旧 MOQ 命中仅 post 448，因整篇 rewrite 而保留；没有直接改修订。

```
meta MOQLIKE 2 POST_IDS [448]
indexable MOQLIKE 3 POST_IDS [448]
```

本地完整数据库迁移前备份 `.rehearsal-32a/32a5-before.sql`：SHA256 `7f8da71967eea8085556e030670964663dc91cd686dcd8dc0726d9e0de4af30c`。逐篇备份 `.rehearsal-32a/32a5-blog-backup-v2.json`：SHA256 `8cbbbdfd3c46c5a0aff3fc37eb23f97ec54eb4dd947bd5fa9f91e7387db1e646`。两者含原始业务内容，仅保留本机，不提交。

迁移、重跑和回滚演练原始输出：
```text
mode=dry-run plan=318 posts=276 state=before old=318 new=0
mode=backup plan=318 posts=276 state=before old=318 new=0
backup_rows=276 sha256=8cbbbdfd3c46c5a0aff3fc37eb23f97ec54eb4dd947bd5fa9f91e7387db1e646
mode=apply plan=318 posts=276 state=before old=318 new=0
changed=318 posts=276
mode=apply plan=318 posts=276 state=after old=0 new=318
changed=0 posts=0
mode=rollback plan=318 posts=276 state=after old=0 new=318
changed=318 posts=276
ROLLBACK_ROWS 276 DIFFERENCES 0
REVISION_ROWS 2050 SHA256 cd4864ca012c2fc137d912bf919cd251cd34e82dcce4779aec9b48c525653afb MATCH True
mode=apply plan=318 posts=276 state=before old=318 new=0
changed=318 posts=276
NEGATIVE_PLAN_EXIT 1
Error: Exact sentence count mismatch post=366 old=0 new=0; nothing changed.
GUARD_PASS True
SCRATCH_EXTRA_OCCURRENCE exit=1
Error: Published-post occurrence count mismatch; nothing changed.
SCRATCH_AFTER_ROLLBACK mode=dry-run plan=318 posts=276 state=before old=318 new=0
```

以下包含最终逐篇精确对照、grep 及 5 篇修改与 3 篇例句。目标 grep 剩 1 条，且该条在 rewrite 类；22 个 rewrite 条目中其余 21 条是不同的含 500 句子，不匹配该 grep。

```text
CATEGORIES {'price-tier': 153, 'moq': 318, 'rewrite': 22} POSTS 344
PLAN 318 POSTS 276
PLANNED_EXACT_DIFF_ERRORS 0 []
REWRITE_UNCHANGED True
GREP_REMAINING_MATCHES 1 POSTS 1
REMAINING 448 packaging-moq-design-planning MOQ 500 categories=rewrite PASS
REVISION_ROWS 2050 SHA256 cd4864ca012c2fc137d912bf919cd251cd34e82dcce4779aec9b48c525653afb UNCHANGED True
EXAMPLES_CHANGED
366 eu-ppwr-packaging-buyer-checklist BEFORE These records establish no supplier offer, service availability or MOQ exception; its published baseline is 500 pieces per product/design. AFTER These records establish no supplier offer, service availability or MOQ exception; its published baseline is 250 pieces per product/design.
367 food-contact-packaging-documents-checklist BEFORE Its published MOQ is 500 pieces per product/design; the small quantities in these planning examples do not establish a smaller custom-production offer. AFTER Its published MOQ is 250 pieces per product/design; the small quantities in these planning examples do not establish a smaller custom-production offer.
449 sample-revision-record BEFORE The published starting MOQ is 500 pieces per product/design; actual component, design and commercial scope require confirmation. AFTER The published starting MOQ is 250 pieces per product/design; actual component, design and commercial scope require confirmation.
450 supplier-clarification-matrix BEFORE The published starting MOQ is 500 pieces per product/design; actual component, design and commercial scope require confirmation. AFTER The published starting MOQ is 250 pieces per product/design; actual component, design and commercial scope require confirmation.
451 flat-assembled-delivery-scope BEFORE The published starting MOQ is 500 pieces per product/design; actual component, design and commercial scope require confirmation. AFTER The published starting MOQ is 250 pieces per product/design; actual component, design and commercial scope require confirmation.
EXAMPLES_PRICE_TIER
91 folding-gift-box-specifications BEFORE Planning example: compare two proposals for 500 gift boxes of one design. AFTER Planning example: compare two proposals for 500 gift boxes of one design.
94 packaging-supplier-brief-small-brand BEFORE Planning example: the intended gift contains one lidded jar and one care card, with 500 identical presentation boxes under consideration. AFTER Planning example: the intended gift contains one lidded jar and one care card, with 500 identical presentation boxes under consideration.
233 custom-packaging-quote-comparison-checklist BEFORE Planning quantity example: 500 sets each containing one box, one insert, one sleeve and two different cards require 500 pieces of each of those five designs. AFTER Planning quantity example: 500 sets each containing one box, one insert, one sleeve and two different cards require 500 pieces of each of those five designs.
exit=0
```

报价矩阵本轮重跑：
```text
MATRIX_ROWS 31752
MATRIX_ROWS 31752
BASELINE_ROWS 31752 AFTER_ROWS 31752 DIFFERENCES 0
```

### 博客语言路径

11 语言路由只允许 `paths.json` 内路径；实查当前已发布博客 344 条完整 permalink，与 146 个可翻译路径交集为 0。各篇为英文原文，外语路径不渲染该正文，而返回 404 并提示阅读英文；导航明确标示博客为 English。因此本轮博客句子未加入词典键清单；页头新句及 llms 新标签已追加。示例 post 448 在本地的 permalink 为 `/2026/10/01/packaging-moq-design-planning/`，不改 URL、不加 301。

```text
PUBLISHED_POSTS 344 TRANSLATED_PATH_MATCHES 0
/2026/09/21/packaging-supplier-brief-small-brand/ 200
/de/2026/09/21/packaging-supplier-brief-small-brand/ 404
/zh-CN/2026/09/21/packaging-supplier-brief-small-brand/ 404
/ja/2026/09/21/packaging-supplier-brief-small-brand/ 404
```

翻译闸门仍未通过；`title_missing` 与 `missing` 有重叠，不重复相加。完整待译键只保存在 `.rehearsal-32a/32a5-i18n-missing.csv`：

```text
csv_rows=573 unique_keys=565 quote_keys=3
ar missing=564 quote_missing=3 quote_invalid=0 title_missing=1
de missing=564 quote_missing=3 quote_invalid=0 title_missing=1
es missing=564 quote_missing=3 quote_invalid=0 title_missing=1
fr missing=564 quote_missing=3 quote_invalid=0 title_missing=1
it missing=564 quote_missing=3 quote_invalid=0 title_missing=1
ja missing=564 quote_missing=3 quote_invalid=0 title_missing=1
ko missing=564 quote_missing=3 quote_invalid=0 title_missing=1
nl missing=564 quote_missing=3 quote_invalid=0 title_missing=1
pt missing=564 quote_missing=3 quote_invalid=0 title_missing=1
zh-CN missing=564 quote_missing=3 quote_invalid=0 title_missing=1
zh-TW missing=564 quote_missing=3 quote_invalid=0 title_missing=1
GATE FAIL missing_entries=6237 title_errors=11 errors=0
exit=1
```

## AI 摘要与上线后事项

因卡片由 500 改为 250，旧 llms 中 `Card prices (500 pieces …)` 容易被误认为当前卡价，已改名为 `Product reference prices (500 pieces …)`；引擎调用仍按要求使用默认 500。两份本地摘要已根据本轮页面重新生成，旧文件已在 `.rehearsal-32a/32a5-before-llms*` 备份：

```text
llms.txt HTTP 200 bytes 7377 sha256 c9c1bb9fd57f14640ad1390279ad41aa9daf525aa89f4b8c9d0598cb675ab3ea old_header 0
llms-full.txt HTTP 200 bytes 857131 sha256 cbe6268e3eadbab81ba98e002f409dd79a948ee3835212358e2cf264477a8554 old_header 0
pages=348 stale_moq=0
```

`deploy/RUNBOOK-launch.md` 已加入正式授权后的 dry-run、备份、执行、回滚和定时任务修改时机。正常公开 URL 验证上线之后才由站长改定时任务；本轮未改。
