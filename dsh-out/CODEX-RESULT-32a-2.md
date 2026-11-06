# 32a-2 MOQ 文案收尾与本地验证

> 32a-3 更新：下方关于本地工作区投影固定 500 的内容为当时的历史发现。后续代码与快照迁移、回滚演练、翻译闸门和尚未验证事项见 `CODEX-RESULT-32a-3.md`；生产仍未部署。

- 基线 `9c2531519fc95d3e9eaa9a002bd6c03e6deed84f`，继续在独立 `32a` worktree 和独立 WP/DB 实例执行；不合并、不部署、未访问生产。数据库来自 31b 本地彩排快照，生产导出基础日期为 10 月 30 日洛杉矶时间。
- 用户确认按未舍入货值计算所得数值为正确验收值；此前 $0.01 报价差异不再视为问题。
- 本地四个目标页面的正文和 `/products/` 标题已验证；页面 399 个中按精确旧串变更 1,666 处；博客和修订未改。Yoast、主题投影、FAQ 结构化数据及旧产品页面元数据中与计划匹配的旧文案也纳入。
- `packrivo_moq` 是父主题的独立选项，缺失时默认 500。迁移在同一事务中仅将它从缺失/500 设为 250，未触碰报价插件其他设置；异常值会在任何写入前停止。
- **发布前待复核：**本地 `pkpw_pilot_enabled()` 为真，但旧产品工作区 `PKPW_Product_Validation::public_projection()` 仍产生 `commercial.moq=500`，管理表单和校验也固定 500。drawer 页实抓未出现 `MOQ 500`，不代表全部数据消费者都不读该值；改变工作区数据契约和既有记录超出此次精确文案迁移，未擅改。RUNBOOK 已记录先复核该契约，再决定是否部署。
- 旧 `packrivo-wave1.php`、`packrivo-site-remediation-20261022.php`、产品工作区 renderer 和父主题 `functions.php` 不在仓库源码或 Git 引用中；本地仅对各自独立快照运行有精确匹配数量保护的候选补丁，不将整份插件提交。生产必须在另行授权后按 RUNBOOK 获取当时原件、备份、比对、校验语法和哈希；不匹配则停下。

## 1. 代码与本地插件检索

```text
RAW_MATCHES 3
EXCEPTION admin-workspace-contract-500 .rehearsal-32a/wp-content/plugins/packrivo-product-workspace\includes\class-product-admin.php:244:        echo '<details class="pkpw-panel" open><summary>5 · 商业条件</summary><p>MOQ 500 pieces per product / design。当前公开策略为 quote-only；未核验价格与成本不进入公开内容。</p>';
EXCEPTION admin-price-example .rehearsal-32a/wp-content/themes/packrivo-static-bridge-1.2.38\master-product-facts.php:138:    foreach(['amount'=>'Customer amount per piece','currency'=>'Customer currency (USD, GBP, EUR, CNY, CAD or AUD)','quantity_conditions'=>'Quantity and scope, e.g. 500 pieces per design; freight excluded'] as $key=>$label) {
EXCEPTION input-matching-only .rehearsal-32a/wp-content/themes/packrivo-static-bridge-1.2.38\remediation-functions.php:296:        foreach(['MOQ: 500 pieces per product / design','MOQ 500 pieces per product / design','MOQ: 500 pieces per product/design','MOQ 500 pieces per product/design','MOQ: 500 pieces per design','MOQ 500 pieces per design','starting quantity is 500 pieces per product / design']as $policy){
VISITOR_MATCHES 0
```

三个原始命中都不是当前访客文案：后台 500 件参考价格示例；父主题识别旧文案的输入匹配表（当前 `packrivo_moq=250`）；旧工作区后台仍显示 500 的契约，需按上面的发布前复核处理。`.bak`、README 和词典不是加载中的代码，未修改。另有 `deploy/patches-29z/strip-1688.php` 历史迁移脚本保留旧固定尾句，非当前主题/插件运行代码，勿不经审查重新运行。

## 2. 数据库 dry-run 与迁移两次

四条 WP-CLI `search-replace --dry-run --include-columns=...` 原始表统计：

```text
Table	Column	Replacements	Type
wp_posts	post_content	22	SQL
Success: 22 replacements to be made.
Table	Column	Replacements	Type
wp_posts	post_content	1971	SQL
Success: 1971 replacements to be made.
Table	Column	Replacements	Type
wp_postmeta	meta_value	443	PHP
Success: 443 replacements to be made.
Table	Column	Replacements	Type
wp_postmeta	meta_value	7	PHP
Success: 7 replacements to be made.
```

顺序为 posts 的 `MOQ 500`、`500 pieces per`，再 postmeta 的相同两项；CLI 的 postmeta 数字覆盖全部 meta_value，清单逐条标注类型/字段，并补录实际影响输出的主题投影及社交描述。共 3,239 条命中及补充投影记录，见 `32a-moq-db-hits.csv`。匹配清单包括博客、修订，但脚本仅处理计划中的 page。

```text
RUN 1 exit=0
before MOQ 500=452 | 500 pieces per=550
site_moq_before=missing(default 500)
after MOQ 500=0 | 500 pieces per=1
replaced=1666 | fields=1123 | pages=399
yoast_cache_replaced=921 | indexable_rows=363
site_moq_after=250 | site_moq_updated=1
RUN 2 exit=0
before MOQ 500=0 | 500 pieces per=1
site_moq_before=250
after MOQ 500=0 | 500 pieces per=1
replaced=0 | fields=0 | pages=0
yoast_cache_replaced=0 | indexable_rows=0
site_moq_after=250 | site_moq_updated=0
```

最后一条 `500 pieces per` 为明确的 500 件报价示例，按要求保留。迁移前与迁移后独立核对博客正文、标题和 Yoast meta：

```text
AFTER_PAGE_HITS 1 ALL_HITS 2099
```

唯一的 page 命中位于 `packaging-cost` 的 500 件出口纸箱报价参考。再对博客内容做哈希核验：

```text
BLOG_POSTS=365 BLOG_YOAST_META=728 SHA256=e0a197bfc87c5a6faf0503e09bed0762ba94c2bc878bee2aa4017c63d53ddff6
```

## 3. 实际 HTTP 及数据库检查

```text
/about/ HTTP 200
<title>About Packrivo | Custom Packaging from Shenzhen</title>
BODY_250=['MOQ: 250 pieces per product/design'] BODY_MOQ250=['MOQ: 250 pieces per product/design. AI-assisted concept visualization. Not a physical sa'] BODY_OLD_500=0
/product-collapsible-magnetic-gift-box/ HTTP 200
<title>Custom Collapsible Magnetic Gift Boxes | Packrivo</title>
BODY_250=['MOQ: 250 pieces per product / design'] BODY_MOQ250=['MOQ: 250 pieces per product / design.  Agree what a structural sample will check and whi'] BODY_OLD_500=0
/product-front-tuck-mailer/ HTTP 200
<title>Custom Front Tuck Mailer Boxes | Packrivo</title>
BODY_250=['MOQ: 250 pieces per product / design'] BODY_MOQ250=['MOQ: 250 pieces per product / design.  Agree what a structural sample will check and whi'] BODY_OLD_500=0
/products/ HTTP 200
<title>Custom Packaging: 134 Styles, MOQ 250 | Packrivo</title>
BODY_250=[] BODY_MOQ250=['MOQ 250 · Dielines included · Live prices in the quote builder Filter by family, sort b'] BODY_OLD_500=0
```

```text
PASS about
PASS product-collapsible-magnetic-gift-box
PASS product-front-tuck-mailer
PASS products Yoast title
PASS products theme-projected title
PASS site project MOQ
PASS 500-piece price reference retained
```

额外抓取 `/product-drawer-jewelry-box/`：HTTP 响应内容中 `MOQ 500`、`500 pieces per`、`minimum ... 500` 各 0；可见产品规格及 sticky 文字为 250。该结果不能证明上一节所述工作区内部投影契约已更新。

```text
pkpw_pilot=enabled
public_projection=array
public_moq=500
```

## 4. 32a 定价回归

```text
rigid none+foil qty=249 ERROR pkq_qty {"status":422}
rigid none+foil qty=250 {"unit":5.39,"total":1346.89,"goods_unit":4.68,"goods_total":1171.21,"fee":175.68,"fee_is_min":false,"setup":340,"quantity":250,"size_factor":0.3218,"tier_factor":1.468,"rush":false}
rigid none+foil qty=500 {"unit":3.39,"total":1693.3,"goods_unit":2.94,"goods_total":1472.43,"fee":220.86,"fee_is_min":false,"setup":340,"quantity":500,"size_factor":0.3218,"tier_factor":1,"rush":false}
rigid none+foil qty=1000 {"unit":2.81,"total":2813.27,"goods_unit":2.45,"goods_total":2446.33,"fee":366.95,"fee_is_min":false,"setup":340,"quantity":1000,"size_factor":0.3218,"tier_factor":0.93,"rush":false}
rigid none+foil qty=3000 {"unit":2.22,"total":6670.33,"goods_unit":1.93,"goods_total":5800.28,"fee":870.04,"fee_is_min":false,"setup":120,"quantity":3000,"size_factor":0.3218,"tier_factor":0.836,"rush":false}
rigid full-color+matte qty=500 {"unit":3.26,"total":1630.05,"goods_unit":2.83,"goods_total":1417.43,"fee":212.61,"fee_is_min":false,"setup":340,"quantity":500,"size_factor":0.3218,"tier_factor":1,"rush":false}
rigid full-color+matte qty=1000 {"unit":2.7,"total":2695.63,"goods_unit":2.34,"goods_total":2344.03,"fee":351.6,"fee_is_min":false,"setup":340,"quantity":1000,"size_factor":0.3218,"tier_factor":0.93,"rush":false}
carton white one-color none qty=250 {"unit":2.38,"total":596.13,"goods_unit":1.78,"goods_total":446.13,"fee":150,"fee_is_min":true,"setup":340,"quantity":250,"size_factor":0.2539,"tier_factor":1.468,"rush":false}
```

```text
MATRIX_ROWS 31752
MATRIX_ROWS 31752
BASELINE_ROWS 31752 AFTER_ROWS 31752 DIFFERENCES 0
qty=250 PHP unit=5.39 fee=175.68 JS unit=5.39 fee=175.68 PASS
qty=300 PHP unit=4.88 fee=191.08 JS unit=4.88 fee=191.08 PASS
qty=400 PHP unit=4.07 fee=212.33 JS unit=4.07 fee=212.33 PASS
qty=499 PHP unit=3.39 fee=220.84 JS unit=3.39 fee=220.84 PASS
qty=500 PHP unit=3.39 fee=220.86 JS unit=3.39 fee=220.86 PASS
qty=1000 PHP unit=2.81 fee=366.95 JS unit=2.81 fee=366.95 PASS
```

## 5. 翻译清单与生成链

`32a-new-strings.csv` 原有 21 行，新追加 **545 行**：`source=db` 534、`source=theme` 11；每条新行都包含旧英文和新英文。未修改词典或 quote-i18n。

WP 报价插件的 `pricing.json` 确由 `packrivo-shared/build-shared.mjs` 从 `packrivo-v2` 源生成：两份现有 JSON 的 SHA-256 均为 `d4a5e9bde16d405857e303914175d71e159a857e74cf05ea1a6bcee8827a6253`。档位和费用默认值的生成源是 `packrivo-v2/lib/pricing.ts`；旧 Next 站和生成链不随 WP 上线，本轮未改这两个目录。**不能笼统声称所有价格只需改一个文件**：生成器仍内置 setup 等规则，PHP/JS 有回退表，生产 wp_options 又可覆盖设置；以后必须核对这些同源关系与选项，再生成和验收，避免只改源码导致运行值不一致。
