# 32a-7 本地合并验收

- 合并前 `main=50e000f9d4c0d2d28134c5a364391d69ffb69bd4`，`origin/32a=9ec011ab5d41c41c8942ba7417196b7767cc9316`；已跟踪文件无改动。35a 未跟踪文件保留。
- **未通过**：`/packaging/` 返回 404，数据库没有该 slug 的页面；29y 原扫描器 1,606 页中 170 页不满足残留英文判据，排除博客页后 159 页不满足。不能以 `/products/` 代替声称 `/packaging/` 通过。
- 当前改动是本地合并，不是生产发布或 push。若用户未放宽验收，保持合并待处理；若另行确认接受未通过项，可据此完成合并提交。

## 词典三方合并与两侧对比

`python -B deploy/32a7-dict-merge.py`（末列为逐侧新增和改值数）：

```text
ar main=8262 32a=8723 merged=8826 union=8826 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=103 vs_32a_changed=9
de main=8259 32a=8822 merged=8823 union=8823 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=1 vs_32a_changed=0
es main=8259 32a=8771 merged=8823 union=8823 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=52 vs_32a_changed=76
fr main=8262 32a=8775 merged=8826 union=8826 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=51 vs_32a_changed=79
it main=8256 32a=8772 merged=8820 union=8820 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=48 vs_32a_changed=191
ja main=8251 32a=8726 merged=8815 union=8815 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=89 vs_32a_changed=11
ko main=8254 32a=8727 merged=8818 union=8818 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=91 vs_32a_changed=14
nl main=8272 32a=8778 merged=8836 union=8836 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=58 vs_32a_changed=94
pt main=8270 32a=8785 merged=8834 union=8834 conflicts=0 vs_main_added=564 vs_main_changed=0 vs_32a_added=49 vs_32a_changed=86
zh-CN main=8215 32a=8778 merged=8779 union=8779 conflicts=0 vs_main_added=564 vs_main_changed=4 vs_32a_added=1 vs_32a_changed=0
zh-TW main=8163 32a=8726 merged=8727 union=8727 conflicts=0 vs_main_added=564 vs_main_changed=4 vs_32a_added=1 vs_32a_changed=0
DRY_RUN_OK dictionaries=11
```

脚本另外通过 4 项单元测试。主分支 JSON 序列化格式经逐字节比对；11 种词典均满足两侧键并集。独立主实例的 11 份运行时词典与工作区 SHA-256 全部一致。

## 32a 翻译闸门

```text
csv_rows=573 unique_keys=565 quote_keys=3
ar missing=0 quote_missing=0 quote_invalid=0 title_missing=0
de missing=0 quote_missing=0 quote_invalid=0 title_missing=0
es missing=0 quote_missing=0 quote_invalid=0 title_missing=0
fr missing=0 quote_missing=0 quote_invalid=0 title_missing=0
it missing=0 quote_missing=0 quote_invalid=0 title_missing=0
ja missing=0 quote_missing=0 quote_invalid=0 title_missing=0
ko missing=0 quote_missing=0 quote_invalid=0 title_missing=0
nl missing=0 quote_missing=0 quote_invalid=0 title_missing=0
pt missing=0 quote_missing=0 quote_invalid=0 title_missing=0
zh-CN missing=0 quote_missing=0 quote_invalid=0 title_missing=0
zh-TW missing=0 quote_missing=0 quote_invalid=0 title_missing=0
GATE PASS missing_entries=0 title_errors=0 errors=0
```

Windows `core.autocrlf=true` 会把 CSV 多行键中的 LF 变成 CRLF，造成 11 个假缺失；闸门读取时现将行尾统一为 LF，新增回归测试，并且原有 7 项测试通过。已人工核对首页保留 29y 翻译/响应式图，以及 32a 报价、250 起订量和 LTR 隔离。

## 29y 残留英文扫描：未通过

使用原 `deploy/scripts-29y/scan-main-english-29y.php` 和原 local-stop、lexicon、allow 表，单线程请求隔离实例的 11 种语言 × 146 个路径。以下按 `HTTP=200`、`main_found`、`residual_count<=5` 且 `long_runs=0` 严格统计；旧 29y G3 排除博客页，故同时列排除博客数。

```text
ar TOTAL 146 FAIL 7 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 6
de TOTAL 146 FAIL 26 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 25
es TOTAL 146 FAIL 9 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 8
fr TOTAL 146 FAIL 8 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 7
it TOTAL 146 FAIL 8 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 7
ja TOTAL 146 FAIL 8 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 7
ko TOTAL 146 FAIL 8 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 7
nl TOTAL 146 FAIL 14 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 13
pt TOTAL 146 FAIL 8 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 7
zh-CN TOTAL 146 FAIL 37 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 36
zh-TW TOTAL 146 FAIL 37 WITHOUT_BLOG_TOTAL 145 WITHOUT_BLOG_FAIL 36
ALL TOTAL 1606 HTTP_200 1606 FAIL 170
EXCLUDING_BLOG TOTAL 1595 FAIL 159
```

扫描器自身的原始尾行：`完成 1606 条，扣过本族词后>5 的页面 138，异常 0`；其 138 只按词数标记，与上方额外计入英文连续段的 170 不同。日语和阿拉伯语首页各有 15 个英文连续段，例子包括新增产品卡说明和供应商照片说明。`31b-new-strings.csv` 共 37 行，当前日语与阿拉伯语词典各有 28 行键缺失；仅保留两分支词典键并集无法补齐这些缺译。完整 JSONL 在 `E:/破甲工作区/32a7-main-rehearsal/scan-all.jsonl`。历史 G1–G7 运行器硬编码旧价格及旧演练地址，没有冒充该套 G1–G7 全部通过。

## PHP 和旧 MOQ 字符串

`php -l`：48 个相对 main 新增或修改的 PHP 文件全部通过。

```text
No syntax errors detected in /tmp/32a7-lint/deploy/31b/default-category.php
No syntax errors detected in /tmp/32a7-lint/deploy/31b/mu-plugins/000-packrivo-31b-routes.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a/mu-plugins/001-packrivo-32a-404-title.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a6-blog-rollback.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a5-blog-migrate.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a-workspace-moq-lib.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a6-blog-rewrite.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a-patch-legacy-moq.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a-moq-content.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a4-llms-text.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a-workspace-moq-backup.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a-workspace-moq-rollback.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a-workspace-moq-migrate.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a4-generate-llms-full.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a4-generate-llms.php
No syntax errors detected in /tmp/32a7-lint/deploy/32a-update-options.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-language-selector/packrivo-language-selector.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/template-parts/product-card.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/template-journal-v4.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/inc/seo-31a.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/inc/site-31b.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/functions.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/front-page.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/template-products-v4.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/page.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/single.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/footer.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-home-v4/header.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-quote/packrivo-quote.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-quote/includes/class-pkq-admin.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-quote/includes/class-pkq-pricing.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-quote/includes/class-pkq-rest.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-quote/demo/parity.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-quote/demo/index.php
No syntax errors detected in /tmp/32a7-lint/packrivo-wp/packrivo-quote/demo/order.php
No syntax errors detected in /tmp/32a7-lint/tests/test_31a_language.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-4-llms-text.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-matrix.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-4-404-harness.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-wp-pricing.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-3-json.php
No syntax errors detected in /tmp/32a7-lint/tests/test_31a_catalog.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-3-integrity.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-3-admin.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-2-moq.php
No syntax errors detected in /tmp/32a7-lint/tests/32a5-card-check.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-3-workspace.php
No syntax errors detected in /tmp/32a7-lint/tests/32a-pricing-test.php
```

`git grep -n -E "MOQ 500|'qty'=>500" -- packrivo-wp`：0 命中（exit 1）。主题 README 中一处旧目标说明同步改为 250；没有声称生产站现已改变。

## 独立实例及页面

32a 数据卷以只读方式复制至独立快照；未操作 32a、33a、33b 容器。快照独立启动后 `CHECK TABLE`：40 表、0 错误；确认包含 32a-6 的 448 新标题，导出 SQL SHA-256 `f698c7e36e4e641bc9d11991bcb30c7acaa702bd648e253fb586181db25aeaab`，再导入 `packrivo-main-db`。源卷复制时原数据库仍运行，40 表检查及 448 抽查不等于对原库取得事务一致性快照的证明。独立实例禁用 WP Cron 并拦截邮件；主题 54 文件、报价插件 23 文件与工作区逐字节一致。

```text
HOME HTTP 200 CARDS 5 MOQ250 5 AT250 5 SRCSET_480_960 5 WIDTH400_HEIGHT300 5
/packaging/ HTTP 404 CARDS 0  [未通过]
/products/ HTTP 200 CARDS 24 MOQ250 24 AT250 5 SRCSET_480_960 17 WIDTH400_HEIGHT300 17  [非指定地址，仅供排查]
```

首页卡片示例：`MOQ 250`，`from $5.20 / unit`，`at 250 pcs · 9.5×9.5×4 cm`；图像标签含 `480w`、`960w`、`width="400" height="300"`。首页 1,000 件示例价 `$2.70` 与 32a 原始验收记录一致；32a 原报告已记载与题示 `$2.69` 的差异。

## 日语和阿拉伯语首页

两个页面各 HTTP 200。下列键相对 29y 前状态有变化并在页面出现；动态价格代入 `$5.20`、数量 `250`、尺寸 `9.5×9.5×4`：

```text
ja  from $%s / unit     -> $%s / 個〜                  页面 $5.20 / 個〜
ja  at %s pcs · %s cm  -> %s個〜 · %s cm              页面 250個〜 · 9.5×9.5×4 cm
ja  or                  -> または                     页面长句中的子串，并非独立键调用证据
ar  from $%s / unit     -> ابتداءً من %s$ للوحدة       页面 ابتداءً من 5.20$ للوحدة
ar  at %s pcs · %s cm  -> من %s قطعة · %s سم          页面 من 250 قطعة · 9.5×9.5×4 سم
ar  Shenzhen, China     -> شنتشن، الصين              页面句首含该短语
```

日语第三项仅证明文字出现在另一译文长句内，不能证明该键被单独调用；因此“每种语言各三个键均已独立验证”仍不满足。

## 其他核对

- `git diff --cached --check` 对 32a 已提交的两份历史报告提示两处行尾空白和一处末尾空行；本轮没有改写这些上游报告。
- PHP 语法、词典键级合并、32a i18n 闸门及首页卡片通过，不抵消 `/packaging/` 和 29y 扫描未通过。
