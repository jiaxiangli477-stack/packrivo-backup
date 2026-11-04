# 32a 报价引擎本地复核记录

- 基线：31b `d691e55502090c2a5ad183296d2767b7c94fac90`；独立 worktree 和分支 `32a`。未合并、未上线。
- 本地：`packrivo-32a-wp`、`packrivo-32a-db`、独立数据卷和内部网络，本机入口 `http://localhost:8083`。数据库从 31b 的独立本地彩排数据库只读导出再导入，原始基线为 10-30 洛杉矶时间生产导出，并已包含 31b 的本地变更；不是新的生产快照。所有浏览器外部请求已拦截，通知邮件由本地测试钩子截获。
- 生效生成链：工作区外原生成器写死 `E:/破甲工作区`，旧生成物有 3 处材料名称与 31b 不同；32a 内复制生成器及源文件，对齐现有名称，改成相对本 worktree 生成，未写工作区外输出。运行 `node packrivo-shared/build-shared.mjs`；没有引入新依赖。
- 250/100 系数只用一个 rigid 规格校准，其他结构的供应商条件未获相同验证。
- **验收 D 未满足**：按要求对原始货值不预先舍入并按总额计算，1000 件为 `$2.70` 而不是 `$2.69`；500 件为 `$3.26` 而不是 `$3.25`。A 中 250 件协调费为 `$175.68`（题示约 `$175.50`），小彩盒货值为 `$446.13`（题示约 `$445`）；若这些近似值也按 ±$0.01 判定，A 亦不通过。详细计算见 A、D。未为匹配示例改底价、设定费或先舍入货值。
- **全站旧文案仍需复核**：数据库和旧 mu-plugin 中有未限定印刷的 500 件文案/SEO 标题，见 `32a-grep-audit.md`；这些不属于指定的主题源文件，部分为具体产品或历史示例，不假定其他供应商已接受 250 件。

## A. 32a WP 引擎原始输出

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

真实 REST 请求另验证 249 件：

```text
REST_249 422 {"code": "pkq_qty", "message": "Quantity must be between 250 and 250000.", "data": {"status": 422}}
```

## B. 全组合比对

脚本 `tests/32a-matrix.py` + `tests/32a-matrix.php` 对比 d691e55 的 PHP unit 与 32a goods_unit：

```text
MATRIX_ROWS 31752
MATRIX_ROWS 31752
BASELINE_ROWS 31752 AFTER_ROWS 31752 DIFFERENCES 0
```

## C. PHP / JS parity

```text
qty=250 PHP unit=5.39 fee=175.68 JS unit=5.39 fee=175.68 PASS
qty=300 PHP unit=4.88 fee=191.08 JS unit=4.88 fee=191.08 PASS
qty=400 PHP unit=4.07 fee=212.33 JS unit=4.07 fee=212.33 PASS
qty=499 PHP unit=3.39 fee=220.84 JS unit=3.39 fee=220.84 PASS
qty=500 PHP unit=3.39 fee=220.86 JS unit=3.39 fee=220.86 PASS
qty=1000 PHP unit=2.81 fee=366.95 JS unit=2.81 fee=366.95 PASS
```

## D、E、I. 浏览器与阿拉伯语

```text
HOME_HERO Example quote · Rigid magnetic box · 9.5×9.5×4 cm |  | $2.70 |  | per unit at 1,000 pieces, sourcing fee included
HOME_STATS FROM · 250 PCS | $3.36 / unit | MOQ | 250 / design | PRODUCTION | 7–30 days | SOURCING FEE | 8–15%
HOME_FAQ_PRICE How much does a custom box cost?At 500 pieces, a 9.5×9.5×4 cm rigid jewelry box is about $3.26 per unit and a 30×22×12 cm printed mailer box about $2.85. The unit price drops as quantity goes up — the quote builder shows the full curve for your size.
AR_STATS_BDI ['$3.36 / unit', '250 / design', '7–30 days', '8–15%']
AR_STATS_DIR ['ltr', 'ltr', 'ltr', 'ltr']
QUOTE_TABLE QUANTITY	UNIT	TOTAL | 250	$4.60	$1,149.9 | 500	$2.85	$1,424.9 | 1,000	$2.31	$2,314.05 | 2,500	$1.81	$4,532.08 | 5,000	$1.61	$8,064.42 | 10,000	$1.49	$14,888.16 | 25,000	$1.36	$34,118.7
QUOTE_DETAIL LIVE ESTIMATE |  | $2.31 |  | per unit · 1,000 units |  | Style | Mailer box | Size | 30 × 22 × 12 cm | Material | Kraft corrugated | Print | Full-colour CMYK | Finish | Matte varnish | Order total | $2,314.05 |  | Goods $2,012.22 · Sourcing fee $301.83 (15%, min. $150) · Total $2,314.05 |  | Instant quotes include the sourcing fee at 15%; your final rate is confirmed on the written quote. |  | Ex-works. Freight quoted separately. Setup amortised into the unit price.
QUOTE_UNDER_250 Under 250? Send the brief and we'll quote by hand. http://localhost:8083/contact/
QUOTE_CONTINUE_DISABLED True
QUOTE_DETAILS_STEP_DISABLED True
PAGE_ERRORS []
CONSOLE_ERRORS ['Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED', 'Failed to load resource: net::ERR_FAILED']
EXTERNAL_REQUESTS_BLOCKED 13
```

资源请求的 `ERR_FAILED` 是测试脚本明确阻断外部域名所致；`PAGE_ERRORS []` 是未捕获 JavaScript 异常结果。Arabic stats 实际截图：`32a-ar-stats.png`。首页 $2.70 = 2695.63 / 1000 四舍五入；500 件 $3.26 = 1630.05 / 500 四舍五入。

## F. 本地测试询价

```text
REST_250 200 {"ok": true, "ref": "PKQ-W0BVH", "unit": 5.39, "total": 1346.89, "goods_total": 1171.21, "fee": 175.68, "quantity": 250}
pkq_goods_total meta=1171.21 list=1,171.21 heading=Goods total
pkq_fee meta=175.68 list=175.68 heading=Sourcing fee
CSV_FINAL_COLUMNS ['goods_total', 'fee']
CSV_TEST_ROWS [('PKQ-W0BVH', '1171.21', '175.68')]
```

本地拦截邮件正文原始相关行（收件地址省略）：

```text
Unit: 5.39
Goods total: 1171.21
Sourcing fee: 175.68
Total: 1346.89
```

## G. 选项迁移及幂等

首次执行前、执行两次、执行后：

```text
other_keys_md5=707d18de190aaba4b3545006ff575044
pkq_settings: updated
title-404-wpseo: updated
pkq_settings: unchanged
title-404-wpseo: unchanged
min_qty=250 fee_percent=15 min_fee=150
other_keys_md5=707d18de190aaba4b3545006ff575044
```

首次修改前 `title-404-wpseo` 为 `(/ﾟДﾟ)/没找到页面 %%sep%% %%sitename%%`。`deploy/32a-update-options.php` 只动指定的三个报价键和该 Yoast 404 键；现存费率键不覆盖。RUNBOOK 说明了备份、两次执行与回滚。

## H. 全仓检索及新字符串

见 `32a-grep-audit.md` 和 `32a-new-strings.csv`。原始匹配结果为本 worktree 的 `.rehearsal-32a/grep-raw.txt`，未纳入提交。词典及 quote-i18n 只读，等待 dsh 翻译。

## I. 404 HTTP 验证

```text
/this-should-404-xyz123/ status 404
/de/this-should-404-xyz123/ status 404
en <title>Page not found | Packrivo</title>
de <title>Page not found | Packrivo</title>
```

德语不存在的地址被旧语言路由提前终止，不经过 Yoast；新增独立 32a mu-plugin 只替换该路由的 404 标题，保留状态码和其余内容。
