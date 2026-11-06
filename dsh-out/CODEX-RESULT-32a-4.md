# 32a-4 实例复原与剩余阻塞 — 本地验收

范围：独立 32a worktree 和 32a 本地容器。未登录或访问生产，未部署、合并、清 Cloudflare；未修改词典、dsh 工作区或 dsh 容器。当前数据库完整备份为 `.rehearsal-32a/32a-4-before-recovery.sql`，SHA256 `0d5d5e8df8d8023ab22f60e4b7df234fa09dda6e83622c46a566e1e4a9e926bb`，含业务数据，不提交 Git。

## A. 数据库复原与回归

基线为 2026-11-05 04:07 UTC 的 `32a-3-pre-migration.sql`，早于 04:30 的 34a 写入；用 32a-2 导出和迁移计划重建核对：400 页内容、944 项 meta 检查均无差异。32a-3 的工作区六个快照另外核对。对本地 410 页及 410 条 Yoast indexable 比较 post title、h1、h1 以外内容、指定 meta、indexable 标题和描述及社交字段：

```text
EXPORT_RECONSTRUCTED_PAGES 400 CONTENT_CHECKS 400 META_CHECKS 944 MISMATCHES 0
BASELINE_PAGES 410 CURRENT_PAGES 410 BASELINE_INDEXABLE_ROWS 410 CURRENT_INDEXABLE_ROWS 410
BEFORE DIFF_FIELDS 961 DIFF_PAGES 141
post.h1=130; meta._yoast_wpseo_title=64; meta._yoast_wpseo_metadesc=141
meta._packrivo_document_title=4; meta._packrivo_description=7
indexable title=64 description=141 open_graph_title=64 open_graph_description=141 twitter_title=64 twitter_description=141
AFTER DIFF_FIELDS 0 DIFF_PAGES 0 COUNTS {}
```

具体 page ID、slug、字段与值哈希见 `32a-4-before.csv`；`32a-4-after.csv` 仅有表头。未经发布的原值对照仅保存在本机 `.rehearsal-32a/32a-4-before-full.csv`，不提交。34a CSV 的旧值与基线对照：866 匹配、6 不匹配、396 处 NULL/空字符串不可区分，而且缺少七个 hub 的自定义 meta；见 `32a-4-backup-evidence.csv`。因此没有直接运行 34a 回滚脚本，而是先在隔离试验库验证，再按独立基线逐字段、事务式恢复本地库：130 个 h1、216 个 meta 和 615 个 indexable 字段，共 961 项；没有整体覆盖数据库。

生成 llms-full 时 Yoast 惰性刷新了 324 页社交描述和 2 个社交图片字段，之后经隔离试验验证并恢复 326 项，最终上述指定字段为零差异。Yoast 其他派生缓存如 permalink、version、updated_at 可能变化；后续正常访问仍可能重建缓存，不能把零差异解读为所有 indexable 列字节相同。试验 schema 保留在独立 32a 数据库卷，便于复核。

容器内 28 个 `34a-*` / `b*.sh` 文件已按相对路径移入 `/tmp/_dsh-34a-quarantine/`，逐项核对大小与 SHA256，没有删除；清单 `32a-4-quarantine.json`。恢复脚本、恢复计划和原始日志保留在 `.rehearsal-32a/`，计划含未公开旧值，不提交。

### 32a-2 页面及迁移复测原始输出

```text
/about/ HTTP 200 title=About Packrivo | Custom Packaging from Shenzhen MOQ250=True OLD_MOQ500=False PASS
/product-collapsible-magnetic-gift-box/ HTTP 200 title=Custom Collapsible Magnetic Gift Boxes | Packrivo MOQ250=True OLD_MOQ500=False PASS
/product-front-tuck-mailer/ HTTP 200 title=Custom Front Tuck Mailer Boxes | Packrivo MOQ250=True OLD_MOQ500=False PASS
/products/ HTTP 200 title=Custom Packaging: 134 Styles, MOQ 250 | Packrivo MOQ250=True OLD_MOQ500=False PASS
exit=0
```

```text
TRIAL_32A2_RUN_1 exit=0
before MOQ 500=452 | 500 pieces per=550
site_moq_before=missing(default 500)
after MOQ 500=0 | 500 pieces per=1
replaced=1666 | fields=1123 | pages=399
yoast_cache_replaced=921 | indexable_rows=363
site_moq_after=250 | site_moq_updated=1

TRIAL_32A2_RUN_2 exit=0
before MOQ 500=0 | 500 pieces per=1
site_moq_before=250
after MOQ 500=0 | 500 pieces per=1
replaced=0 | fields=0 | pages=0
yoast_cache_replaced=0 | indexable_rows=0
site_moq_after=250 | site_moq_updated=0
```

### 32a-3 工作区迁移、回滚、读取及快照原始输出

```text
ROLLBACK_1 exit=0
before_500=0 before_250=6
after_500=6 after_250=0
replaced=6 mode=rollback snapshots=6

ROLLBACK_2 exit=0
before_500=6 before_250=0
after_500=6 after_250=0
replaced=0 mode=rollback snapshots=6

ROLLED_BACK_INTEGRITY exit=0
PASS revision=1 before-hash-and-non-MOQ-bytes
PASS revision=2 before-hash-and-non-MOQ-bytes
PASS revision=3 before-hash-and-non-MOQ-bytes
PASS revision=4 before-hash-and-non-MOQ-bytes
PASS revision=5 before-hash-and-non-MOQ-bytes
PASS revision=6 before-hash-and-non-MOQ-bytes
HEAD {"page_id":"11","candidate_revision":"5","public_revision":"6"}

ROLLED_BACK_PUBLIC exit=0
public_moq=500

MIGRATE_1 exit=0
before_500=6 before_250=0
after_500=0 after_250=6
replaced=6 mode=migrate snapshots=6

MIGRATE_2 exit=0
before_500=0 before_250=6
after_500=0 after_250=6
replaced=0 mode=migrate snapshots=6

FINAL_READS_AND_VALIDATION exit=0
PASS read_public(11) moq=250
PASS read_candidate(11) moq=250
PASS read_preview(11,3) moq=250
PASS public projection removes known stale MOQ text
PASS historical preview removes known stale MOQ text
PASS validation moq=250 commercial_rejected=no
PASS validation moq=500 commercial_rejected=yes
PASS snapshot revision=1 moq=250 projection_normalized=yes
PASS snapshot revision=2 moq=250 projection_normalized=yes
PASS snapshot revision=3 moq=250 projection_normalized=yes
PASS snapshot revision=4 moq=250 projection_normalized=yes
PASS snapshot revision=5 moq=250 projection_normalized=yes
PASS snapshot revision=6 moq=250 projection_normalized=yes

FINAL_INTEGRITY exit=0
PASS revision=1 after-hash-and-non-MOQ-bytes
PASS revision=2 after-hash-and-non-MOQ-bytes
PASS revision=3 after-hash-and-non-MOQ-bytes
PASS revision=4 after-hash-and-non-MOQ-bytes
PASS revision=5 after-hash-and-non-MOQ-bytes
PASS revision=6 after-hash-and-non-MOQ-bytes
HEAD {"page_id":"11","candidate_revision":"5","public_revision":"6"}
```

### 报价矩阵和 PHP / JS 一致性原始输出

```text
MATRIX_ROWS 31752
MATRIX_ROWS 31752
BASELINE_ROWS 31752 AFTER_ROWS 31752 DIFFERENCES 0
```

```text
qty=250 PHP unit=5.39 fee=175.68 JS unit=5.39 fee=175.68 PASS
qty=300 PHP unit=4.88 fee=191.08 JS unit=4.88 fee=191.08 PASS
qty=400 PHP unit=4.07 fee=212.33 JS unit=4.07 fee=212.33 PASS
qty=499 PHP unit=3.39 fee=220.84 JS unit=3.39 fee=220.84 PASS
qty=500 PHP unit=3.39 fee=220.86 JS unit=3.39 fee=220.86 PASS
qty=1000 PHP unit=2.81 fee=366.95 JS unit=2.81 fee=366.95 PASS
```

### WP 状态与报价原始输出

```text
RUN /tmp/32a-2-moq.php exit=0
PASS about
PASS product-collapsible-magnetic-gift-box
PASS product-front-tuck-mailer
PASS products Yoast title
PASS products theme-projected title
PASS site project MOQ
PASS 500-piece price reference retained

RUN /tmp/32a-3-workspace.php exit=0
PASS read_public(11) moq=250
PASS read_candidate(11) moq=250
PASS read_preview(11,3) moq=250
PASS public projection removes known stale MOQ text
PASS historical preview removes known stale MOQ text
PASS validation moq=250 commercial_rejected=no
PASS validation moq=500 commercial_rejected=yes
PASS snapshot revision=1 moq=250 projection_normalized=yes
PASS snapshot revision=2 moq=250 projection_normalized=yes
PASS snapshot revision=3 moq=250 projection_normalized=yes
PASS snapshot revision=4 moq=250 projection_normalized=yes
PASS snapshot revision=5 moq=250 projection_normalized=yes
PASS snapshot revision=6 moq=250 projection_normalized=yes

RUN /tmp/32a-3-integrity.php exit=0
PASS revision=1 after-hash-and-non-MOQ-bytes
PASS revision=2 after-hash-and-non-MOQ-bytes
PASS revision=3 after-hash-and-non-MOQ-bytes
PASS revision=4 after-hash-and-non-MOQ-bytes
PASS revision=5 after-hash-and-non-MOQ-bytes
PASS revision=6 after-hash-and-non-MOQ-bytes
HEAD {"page_id":"11","candidate_revision":"5","public_revision":"6"}

RUN /tmp/32a-wp-pricing.php exit=0
rigid none+foil qty=249 ERROR pkq_qty {"status":422}
rigid none+foil qty=250 {"unit":5.39,"total":1346.89,"goods_unit":4.68,"goods_total":1171.21,"fee":175.68,"fee_is_min":false,"setup":340,"quantity":250,"size_factor":0.3218,"tier_factor":1.468,"rush":false}
rigid none+foil qty=500 {"unit":3.39,"total":1693.3,"goods_unit":2.94,"goods_total":1472.43,"fee":220.86,"fee_is_min":false,"setup":340,"quantity":500,"size_factor":0.3218,"tier_factor":1,"rush":false}
rigid none+foil qty=1000 {"unit":2.81,"total":2813.27,"goods_unit":2.45,"goods_total":2446.33,"fee":366.95,"fee_is_min":false,"setup":340,"quantity":1000,"size_factor":0.3218,"tier_factor":0.93,"rush":false}
rigid none+foil qty=3000 {"unit":2.22,"total":6670.33,"goods_unit":1.93,"goods_total":5800.28,"fee":870.04,"fee_is_min":false,"setup":120,"quantity":3000,"size_factor":0.3218,"tier_factor":0.836,"rush":false}
rigid full-color+matte qty=500 {"unit":3.26,"total":1630.05,"goods_unit":2.83,"goods_total":1417.43,"fee":212.61,"fee_is_min":false,"setup":340,"quantity":500,"size_factor":0.3218,"tier_factor":1,"rush":false}
rigid full-color+matte qty=1000 {"unit":2.7,"total":2695.63,"goods_unit":2.34,"goods_total":2344.03,"fee":351.6,"fee_is_min":false,"setup":340,"quantity":1000,"size_factor":0.3218,"tier_factor":0.93,"rush":false}
carton white one-color none qty=250 {"unit":2.38,"total":596.13,"goods_unit":1.78,"goods_total":446.13,"fee":150,"fee_is_min":true,"setup":340,"quantity":250,"size_factor":0.2539,"tier_factor":1.468,"rush":false}
```

### 公开投影读取方原始输出

```text
/product-drawer-jewelry-box/ HTTP 200 OLD_MOQ_CLAIMS 0
DRAWER_FAQ_250 True
DRAWER_500_QUANTITY_QUESTION_PRESERVED True
JSON_LD_BLOCKS 1 PARSE_ERRORS 0 OLD_MOQ_CLAIMS 0 COMMERCIAL_MOQ_FIELDS 0
/jewelry-boxes/ HTTP 200 OLD_MOQ_CLAIMS 0
/products/ HTTP 200 OLD_MOQ_CLAIMS 0
/wp-json/wp/v2/pages/11 HTTP 200 OLD_MOQ_CLAIMS 0
REST_FAQ_250 True
/wp-json/wp/v2/pages/8 HTTP 200 OLD_MOQ_CLAIMS 0
/page-sitemap.xml HTTP 200 OLD_MOQ_CLAIMS 0
SITEMAP_HAS_DRAWER True SITEMAP_MOQ_TAGS 0
/llms.txt HTTP 200 OLD_MOQ_CLAIMS 0
/llms-full.txt HTTP 200 OLD_MOQ_CLAIMS 0
RESULTS [["/product-drawer-jewelry-box/", "ok"], ["/jewelry-boxes/", "ok"], ["/products/", "ok"], ["/wp-json/wp/v2/pages/11", "ok"], ["/wp-json/wp/v2/pages/8", "ok"], ["/page-sitemap.xml", "ok"], ["/llms.txt", "ok"], ["/llms-full.txt", "ok"]]
exit=0
```

### 旧 grep 判据原始输出及解释
原脚本 exit=1：两处 VISITOR_MATCHES 是 32a-3 用于将旧字符串替换为 250 的 `str_replace` 输入字面量，不是输出；另两处分别是后台报价数量示例和仅用于识别旧输入的列表。页面和工作区运行态的对应检查见上。不能把这次旧 grep 的原始结果写成通过。

```text
RAW_MATCHES 4
EXCEPTION admin-price-example .rehearsal-32a/wp-content/themes/packrivo-static-bridge-1.2.38\master-product-facts.php:138:    foreach(['amount'=>'Customer amount per piece','currency'=>'Customer currency (USD, GBP, EUR, CNY, CAD or AUD)','quantity_conditions'=>'Quantity and scope, e.g. 500 pieces per design; freight excluded'] as $key=>$label) {
EXCEPTION input-matching-only .rehearsal-32a/wp-content/themes/packrivo-static-bridge-1.2.38\remediation-functions.php:296:        foreach(['MOQ: 500 pieces per product / design','MOQ 500 pieces per product / design','MOQ: 500 pieces per product/design','MOQ 500 pieces per product/design','MOQ: 500 pieces per design','MOQ 500 pieces per design','starting quantity is 500 pieces per product / design']as $policy){
VISITOR_MATCHES 2
.rehearsal-32a/wp-content/plugins/packrivo-product-workspace\includes\class-product-validation.php:116:            $faq['answer']=str_replace('The current MOQ is 500 pieces per product / design.', 'The current MOQ is 250 pieces per product / design.', $faq['answer']);
.rehearsal-32a/wp-content/plugins/packrivo-product-workspace\includes\class-product-validation.php:119:        $out['seo']['description']=str_replace('MOQ 500 pieces per product/design;', 'MOQ 250 pieces per product/design;', $out['seo']['description']);
exit=1
```

### 翻译闸门原始输出（仍阻止上线）
新 llms 英文串已追加清单，较 32a-3 多 3 个键；title_missing 与 site missing 有重叠，不可重复相加。完整缺失清单保存在本机 `.rehearsal-32a/32a-4-i18n-missing.csv`。

```text
csv_rows=571 unique_keys=563 quote_keys=3
ar missing=562 quote_missing=3 quote_invalid=0 title_missing=1
de missing=562 quote_missing=3 quote_invalid=0 title_missing=1
es missing=562 quote_missing=3 quote_invalid=0 title_missing=1
fr missing=562 quote_missing=3 quote_invalid=0 title_missing=1
it missing=562 quote_missing=3 quote_invalid=0 title_missing=1
ja missing=562 quote_missing=3 quote_invalid=0 title_missing=1
ko missing=562 quote_missing=3 quote_invalid=0 title_missing=1
nl missing=562 quote_missing=3 quote_invalid=0 title_missing=1
pt missing=562 quote_missing=3 quote_invalid=0 title_missing=1
zh-CN missing=562 quote_missing=3 quote_invalid=0 title_missing=1
zh-TW missing=562 quote_missing=3 quote_invalid=0 title_missing=1
GATE FAIL missing_entries=6215 title_errors=11 errors=0
exit=1
```

## B. 404 标题与 llms

404 早退路径读取对应语言词典的 `title["Page not found | Packrivo"]`，值缺失或无效时安全回退英文。原词典未修改，12 项文件/路径哈希与已有清单相符。下面 fixture 是独立临时测试词典，不是现有译文；11 种语言各 3 个地址均 HTTP 404、标题与 fixture 一致。实际词典下各 3 个地址均 404，因 11 份词典均缺对应标题键而回退英文；必须等 dsh 补译并通过闸门。逐 URL 原始记录分别见 `32a-4-404-fixture.csv`、`32a-4-404-real.csv`、`32a-4-404-real-shadow.csv`。

```text
fixture ar requests=3 pass=3 title='TEST_ar_404'
fixture de requests=3 pass=3 title='TEST_de_404'
fixture es requests=3 pass=3 title='TEST_es_404'
fixture fr requests=3 pass=3 title='TEST_fr_404'
fixture it requests=3 pass=3 title='TEST_it_404'
fixture ja requests=3 pass=3 title='TEST_ja_404'
fixture ko requests=3 pass=3 title='TEST_ko_404'
fixture nl requests=3 pass=3 title='TEST_nl_404'
fixture pt requests=3 pass=3 title='TEST_pt_404'
fixture zh-CN requests=3 pass=3 title='TEST_zh_CN_404'
fixture zh-TW requests=3 pass=3 title='TEST_zh_TW_404'
TOTAL 33 FAIL 0 CSV E:\破甲工作区\packrivo-backup-32a\dsh-out\32a-4-404-fixture.csv
exit=0
```

```text
real ar requests=3 pass=3 title='Page not found | Packrivo'
real de requests=3 pass=3 title='Page not found | Packrivo'
real es requests=3 pass=3 title='Page not found | Packrivo'
real fr requests=3 pass=3 title='Page not found | Packrivo'
real it requests=3 pass=3 title='Page not found | Packrivo'
real ja requests=3 pass=3 title='Page not found | Packrivo'
real ko requests=3 pass=3 title='Page not found | Packrivo'
real nl requests=3 pass=3 title='Page not found | Packrivo'
real pt requests=3 pass=3 title='Page not found | Packrivo'
real zh-CN requests=3 pass=3 title='Page not found | Packrivo'
real zh-TW requests=3 pass=3 title='Page not found | Packrivo'
TOTAL 33 FAIL 0 CSV E:\破甲工作区\packrivo-backup-32a\dsh-out\32a-4-404-real-shadow.csv
exit=0
```

测试：404 单元测试 4 项通过；翻译闸门单元测试 6 项通过。

两份文件由 32a 本地数据库、英文 sitemap 与当前定价引擎生成；短文件中的价格包含协调费。英文 sitemap 共 360 项、去重后 348 个路径，重复 12 项，生成时去重；本轮未改变 sitemap。

```text
llms_bytes=7364 sha256=5a4da9ad696f4985b9455fd02e1f1759b929ad8386cbcf78599fc684e8884c2f cards=8
rigid_500 qty=500 unit=3.26 fee=212.61
rigid_1000 qty=1000 unit=2.70 fee=351.60
mailer_500 qty=500 unit=2.85 fee=185.86
exit=0
```

```text
processed=25/348 errors=0
processed=50/348 errors=0
processed=75/348 errors=0
processed=100/348 errors=0
processed=125/348 errors=0
processed=150/348 errors=0
processed=175/348 errors=0
processed=200/348 errors=0
processed=225/348 errors=0
processed=250/348 errors=0
processed=275/348 errors=0
processed=300/348 errors=0
processed=325/348 errors=0
pages=348 bytes=858175 sha256=54c44078a5b6827b654271712e9df8317989eca1d82a97aa8a6670a45acf8f51 stale_moq=0
exit=0
```

```text
32a4-llms.txt bytes=7364 sha256=5a4da9ad696f4985b9455fd02e1f1759b929ad8386cbcf78599fc684e8884c2f MOQ_500=0 MINIMUM_500=0 500_PIECE_MINIMUM=0 500_PIECES_MOQ=0 OLD_FEE_EXCLUSION=0 preserved_500_quote_examples=0
32a4-llms-full.txt bytes=858175 sha256=54c44078a5b6827b654271712e9df8317989eca1d82a97aa8a6670a45acf8f51 MOQ_500=0 MINIMUM_500=0 500_PIECE_MINIMUM=0 500_PIECES_MOQ=0 OLD_FEE_EXCLUSION=0 preserved_500_quote_examples=1
```

更新旧 MOQ 检测器后重新验证：
```text
PASS extract visible page text and preserve 500-piece quote example
PASS detect five stale MOQ forms
llms.txt stale_moq=0
llms-full.txt stale_moq=0
```
本地静态读取两份文件均 HTTP 200，字节数与上面 SHA256 相符。保留的 500 数字是报价示例，不是起订量。文件副本为 `.rehearsal-32a/32a4-llms.txt` 和 `.rehearsal-32a/32a4-llms-full.txt`；未来获授权上线时的生成与校验命令见 `deploy/RUNBOOK-launch.md`，本轮没有运行生产命令。

## C. 调试文件

31a、31b、32a 本地快照均含 `zz-debug-29y.php`，SHA256 `ea540689c0c5a32f0cc46cab32fd199a24d7f7435f836e827a5a8334e2857c56`；Git 历史未找到该文件。快照存在不能证明生产有此文件，且本轮不得访问生产，故生产存在与否未知。本实例文件已移至 `.rehearsal-32a/32a-4-zz-debug-29y.php.disabled` 并保留原哈希；本地页面无 DBG29Y 注释。RUNBOOK 写入未来获授权时条件检查、先备份再删除的步骤。

## 实例限制

32a 原 Apache 动态 PHP 请求在 Windows 绑定挂载处卡住，静态文件可访问；仅重启 32a WP 容器未解决。为完成本地验收，在同一个 32a WP 容器中以同一数据库、同一 uploads 和已核对的 32a 代码快照运行备用服务，经 `127.0.0.1:8083` 可访问。9 个关键代码文件与 worktree 哈希一致。备用服务是代码快照，后续源码变动需重新同步；不能称原 Apache 已修复。上述 llms 与多语言实测注明了所用路径，线上状态未核实。
