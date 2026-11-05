# 32a-3 工作区 MOQ 与翻译闸门本地记录

- 基线 `b300e6c0bb0173d23c92ce012f13492f78e3adae`；独立 32a worktree、独立 WP/DB；未合并、未部署、未连接生产。数据库原始基线为 10 月 30 日洛杉矶时间导出，经 31b、32a 和 32a-2 本地彩排变更。
- 当前本地 `pkpw_heads` 只有 page 11，candidate revision 5、public revision 6；数据库实际有 6 条候选/公开快照，而非 3 条不同页面记录。六条原 `commercial.moq` 均为 500。
- 旧值及前后/非 MOQ 哈希备份：`32a-3-workspace-moq-backup-local.csv`，SHA-256 `87904c173b79f9e38593448ac54bef16223dd53c9133bcc71c8ebf6bae991d15`。它只适用于本次本地库；未来生产必须重新生成专属 CSV。另有不提交的本地完整数据库备份 `.rehearsal-32a/32a-3-pre-migration.sql`。
- 产品工作区源码不在本仓库；提交的是有精确匹配数保护的补丁器和测试，不是整份插件。本地候选按“备份 → diff → PHP 语法检查 → 哈希核对”彩排；差异和哈希见 `32a-3-plugin-patch-review.txt`。站长上线前须按 RUNBOOK 从当时现场获取原文件，任何匹配差异都停下。
- **翻译闸门未通过，禁止按本 RUNBOOK 上线。** 11 语言缺键及报价组件字典缺键详见下方。闸门只检查键、非空值及报价组件占位符，不保证桶位和翻译质量；语言 404 早退路径目前仍固定输出英文标题，需单独修复并验收。
- **AI 摘要验收未通过：**本地 `/llms.txt` 和 `/llms-full.txt` 均为 404，无法抓取验证；仓库历史 `deploy/artifacts-29y/llms.txt:7` 仍写 `Minimum order quantity: 500 pieces per design.`，不能假设生产摘要已经更新。上线前应生成并核对最终摘要，明确保留 500 件报价例子。
- Docker 容器执行期间出现集体退出；当时其他容器 `29y-sel`、`web`、`mysql`、`php` 也显示 Exited (255)，本轮只恢复了 `packrivo-32a-db` 和 `packrivo-32a-wp`，没有对其他容器执行启动或修改。

## 1. 数据来源及读取方

`class-product-validation.php` 原先固定校验 500、公开投影固定 500；`class-product-admin.php` 提示和隐藏字段也固定 500。`class-product-store.php` 从 `pkpw_heads` 定位 `pkpw_snapshots.record_json`。本轮在快照里只替换 `commercial.moq` 的数字 token，保留其余全部字节、修订信息和 head；旧 FAQ、SEO 原文字段不被迁移。公开投影中对两条已知旧 MOQ 说明做精确显示归一，500 件问题例子保留。

| 路径或消费者 | 与公开投影关系 | 本地证据与限制 |
| --- | --- | --- |
| `read_public(11)`、candidate、preview | 分别读取公开/候选/历史快照；公开和预览调用 `public_projection` | 三个真实调用均为 250；全部六条快照为 250。 |
| 产品页、品类、产品中心 | renderer 的 `prepare()` 读取公开投影；卡片/详情使用标题、摘要、FAQ 等，MOQ 可见模板为 250 | 三个页面 HTTP 200，旧 MOQ 说法计数 0。 |
| JSON-LD | `seo_graph()`/`schema_node()` 读取投影的标题、摘要及图片，不读取 `commercial.moq` | drawer 页 1 个 JSON-LD 块可解析，未输出 MOQ 字段或旧 MOQ 说法。 |
| WordPress REST pages 11、8 | `rest_prepare_page` 调用 `read_public(11)` 再渲染页面 | 两个端点 HTTP 200；page 11 FAQ 显示 250，旧 MOQ 说法 0。 |
| 询价身份解析 | `PKPW_Product_Inquiry::resolve` 读公开 revision，不读取 MOQ 值 | 只读实际调用返回 page 11、revision 6；未提交外部询价。 |
| Sitemap | Yoast URL 清单；代码未发现直接读取工作区投影 | `/page-sitemap.xml` HTTP 200，包含 drawer URL，无 MOQ 标签。 |
| AI 摘要 | 29y `gen-llms-prices.php` 读取 `PKQ_Pricing` 和静态底稿，未发现读取该工作区投影 | 本地两个摘要 URL 404；旧归档仍有 500 起订说法，实际摘要未验证。 |

## 2. 快照迁移、幂等及回滚原始输出

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
PASS snapshot revision=1 moq=250
PASS snapshot revision=2 moq=250
PASS snapshot revision=3 moq=250
PASS snapshot revision=4 moq=250
PASS snapshot revision=5 moq=250
PASS snapshot revision=6 moq=250

FINAL_INTEGRITY exit=0
PASS revision=1 after-hash-and-non-MOQ-bytes
PASS revision=2 after-hash-and-non-MOQ-bytes
PASS revision=3 after-hash-and-non-MOQ-bytes
PASS revision=4 after-hash-and-non-MOQ-bytes
PASS revision=5 after-hash-and-non-MOQ-bytes
PASS revision=6 after-hash-and-non-MOQ-bytes
HEAD {"page_id":"11","candidate_revision":"5","public_revision":"6"}
```

每条 `before/after-hash-and-non-MOQ-bytes` 校验都比较了原始 JSON 的非 MOQ 字节；head 未改变。另用篡改备份哈希的 CSV 测试，脚本拒绝回滚，6 条记录仍为 250。

所有六个历史快照分别构造公开投影的补充验证：

```text
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
exit=0
```

```text
Error: Rollback failed: non-MOQ data differs from backup
TAMPERED_BACKUP_REJECTED
moq	rows_count
250	6
```

## 3. 用户界面及读取方实际调用

```text
ADMIN exit=0
ADMIN_MOQ_PROMPT_250=true
ADMIN_HIDDEN_MOQ_250=true

INQUIRY exit=0
INQUIRY_RESOLVE {"status":"known","code":"drawer-jewelry-box","page_id":11,"canonical_url":"http://localhost:8083/product-drawer-jewelry-box/","revision":6}
```

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
/llms.txt HTTP 404 UNAVAILABLE
/llms-full.txt HTTP 404 UNAVAILABLE
RESULTS [["/product-drawer-jewelry-box/", "ok"], ["/jewelry-boxes/", "ok"], ["/products/", "ok"], ["/wp-json/wp/v2/pages/11", "ok"], ["/wp-json/wp/v2/pages/8", "ok"], ["/page-sitemap.xml", "ok"], ["/llms.txt", "unavailable"], ["/llms-full.txt", "unavailable"]]
exit=0
```

`Can 500 pieces be divided across different designs?` 是明确数量问题而非旧起订量，按前轮指令保留；FAQ 答案里的旧 `MOQ 500` 在公开投影中变为 250，数据库原文不变。

## 4. 11 语言闸门原始输出

```text
csv_rows=568 unique_keys=560 quote_keys=3
ar missing=559 quote_missing=3 quote_invalid=0
de missing=559 quote_missing=3 quote_invalid=0
es missing=559 quote_missing=3 quote_invalid=0
fr missing=559 quote_missing=3 quote_invalid=0
it missing=559 quote_missing=3 quote_invalid=0
ja missing=559 quote_missing=3 quote_invalid=0
ko missing=559 quote_missing=3 quote_invalid=0
nl missing=559 quote_missing=3 quote_invalid=0
pt missing=559 quote_missing=3 quote_invalid=0
zh-CN missing=559 quote_missing=3 quote_invalid=0
zh-TW missing=559 quote_missing=3 quote_invalid=0
GATE FAIL missing_entries=6182 errors=0
exit=1
```

清单共 568 行、去重后 560 个英文键：包含 32a 第一轮 21 行、32a-2 新增 545 行和本轮新增 2 行。每种语言站点字典缺 559 个唯一键，报价组件独立 `quote-i18n` 各缺 3 个键。`GATE FAIL` 是当前真实状态，不是通过。缺项逐条列表在本地 `.rehearsal-32a/32a-3-i18n-missing.csv`，翻译由 dsh 处理，词典未修改。闸门 5 个合成测试通过，覆盖齐全、缺失、损坏 JSON、报价键和占位符缺失。

## 5. 报价回归

```text
rigid none+foil qty=249 ERROR pkq_qty {"status":422}
rigid none+foil qty=250 {"unit":5.39,"total":1346.89,"goods_unit":4.68,"goods_total":1171.21,"fee":175.68,"fee_is_min":false,"setup":340,"quantity":250,"size_factor":0.3218,"tier_factor":1.468,"rush":false}
rigid none+foil qty=500 {"unit":3.39,"total":1693.3,"goods_unit":2.94,"goods_total":1472.43,"fee":220.86,"fee_is_min":false,"setup":340,"quantity":500,"size_factor":0.3218,"tier_factor":1,"rush":false}
rigid none+foil qty=1000 {"unit":2.81,"total":2813.27,"goods_unit":2.45,"goods_total":2446.33,"fee":366.95,"fee_is_min":false,"setup":340,"quantity":1000,"size_factor":0.3218,"tier_factor":0.93,"rush":false}
rigid none+foil qty=3000 {"unit":2.22,"total":6670.33,"goods_unit":1.93,"goods_total":5800.28,"fee":870.04,"fee_is_min":false,"setup":120,"quantity":3000,"size_factor":0.3218,"tier_factor":0.836,"rush":false}
rigid full-color+matte qty=500 {"unit":3.26,"total":1630.05,"goods_unit":2.83,"goods_total":1417.43,"fee":212.61,"fee_is_min":false,"setup":340,"quantity":500,"size_factor":0.3218,"tier_factor":1,"rush":false}
rigid full-color+matte qty=1000 {"unit":2.7,"total":2695.63,"goods_unit":2.34,"goods_total":2344.03,"fee":351.6,"fee_is_min":false,"setup":340,"quantity":1000,"size_factor":0.3218,"tier_factor":0.93,"rush":false}
carton white one-color none qty=250 {"unit":2.38,"total":596.13,"goods_unit":1.78,"goods_total":446.13,"fee":150,"fee_is_min":true,"setup":340,"quantity":250,"size_factor":0.2539,"tier_factor":1.468,"rush":false}
exit=0
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
