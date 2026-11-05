# CODEX-RESULT · 34a 写入关键词表到现有英文页

日期：2026-11-05（容器内 UTC+0，宿主本地 11-04 21:xx）
分支：`34a`（worktree `E:\破甲工作区\packrivo-backup-34a`，HEAD `b300e6c`），**未合并、未发布**
范围：只写已存在的英文页 title / meta description / H1；不建新页、不改正文、不改 slug / canonical / hreflang、不动词典文件

---

## 0. 结论（先看这四行）

1. 141 行词表全部解析成功（`map_rows=141 resolved=141 missing=0`），8 个排名页断言门 `8/8` 通过。
2. 真跑实写 **7 页 / 11 个字段**，全部落在 7 个 hub 页的 `_packrivo_document_title` / `_packrivo_description` 上；其余 134 页的三个字段在当前库上**已经等于**词表的「新」值，走幂等跳过。
3. 第二次跑 `changed_pages=0`，`Success: 34a finished: idempotent no-op.`
4. 渲染层 13 页 × 3 字段 = 39 项，`ok=34 mismatch=5 keyword_hits=0`；5 项不一致全部是**模板/词典里的字面量**（见 §6），改库无效，不在 34a 范围。

---

## 1. 交付物（磁盘真实路径）

| 文件 | 说明 |
| --- | --- |
| `deploy/34a-keyword-map.php` | 594 行，`php -l` 通过；`dry-run` / `--map=` / `--file` 无关；写 4 个 meta 键 + indexable 两列 |
| `deploy/34a-rollback.php` | 238 行，`php -l` 通过；读最新 `34a-before-*.csv`，白名单 4 键 + `idx:` 行 |
| `deploy/backup/34a-before-20261105-043010.csv` | 全量基线：`posts=141 meta_rows=282 h1_rows=130 idx_rows=846` |
| `deploy/backup/34a-before-20261105-044321.csv` | 197,301 B，1,282 行 |
| `deploy/backup/34a-before-20261105-051918.csv` | 本次演练后真跑生成的 1,282 行备份（容器 `/tmp/backup/`） |
| `dsh-out/34a-new-strings.csv` | `source,url,old_en,new_en`，**347 行**数据 |
| `deploy/RUNBOOK-launch.md` | 追加 `## §34a`（1576 行 / 79,671 B） |

容器内副本与宿主词表哈希一致：`1880f4d154779134111ba1e117e3212e93f79345c8951f2854a2a36d1a479351`。

---

## 2. 前置条件：32a 还没合并进 main（原文记录）

指令原文：

> 32a 和 32a-2 必须已经合并进 main（keyword-map 的标题写的是 MOQ 250）。如果还没合并，从 32a 分支拉出 34a 分支，并在 CODEX-RESULT 里注明。

实测：

```
$ git rev-list --left-right --count main...32a
12	4
```

main 领先 12、32a 领先 4 ⇒ **未合并**。按指令从 `32a` 拉出 `34a` 分支 + worktree，**没有做任何合并**。

后果与处理：词表「现 title」列是 32a 之后的 250 版，共 **15 行**含 `500`：

```
/jewelry-boxes/                    Custom Jewelry Boxes: Rigid, Drawer, MOQ 500 | Packrivo   -> … MOQ 250 …
/products/                         Custom Packaging: 134 Styles, MOQ 500 | Packrivo           -> … MOQ 250 …
/candle-gift-set-box/              Candle Gift Set Box: Insert + Sleeve (MOQ 500) | Packrivo
/corrugated-wine-shipper/          Corrugated Wine Bottle Shipper (MOQ 500) | Packrivo
/drawer-box-with-ribbon/           Drawer Box With Ribbon Pull (MOQ 500) | Packrivo
/eco-kraft-cosmetic-box/           Eco Kraft Cosmetic Box for Small Batches (MOQ 500) | Packrivo
/jewelry-pouch-and-box-set/        Jewelry Pouch and Box Set (MOQ 500) | Packrivo
/kraft-stand-up-pouch/             Kraft Stand-Up Pouch With Window (MOQ 500) | Packrivo
/magnetic-book-style-box/          Magnetic Book-Style Presentation Box (MOQ 500) | Packrivo
/paper-insert-card/                Printed Insert Cards and Thank-You Cards (MOQ 500) | Packrivo
/printed-tissue-paper/             Custom Printed Tissue Paper (MOQ 500) | Packrivo
/soap-box-with-window/             Custom Kraft Soap Box with Window (MOQ 500) | Packrivo
/subscription-mailer-with-insert/  Subscription Mailer Box With Insert (MOQ 500) | Packrivo
/jewelry-box-manufacturer/         Jewelry Box Manufacturer: 500-Unit Costs | Packrivo
/packaging-moq/                    Custom Packaging MOQ: 500 Pieces Explained | Packrivo
```

如果先跑 34a 再跑 32a，这 15 行会按「现库 500 ≠ 词表现 title 500」之外的口径产生 drift 判断差异 —— 所以 RUNBOOK §34a.1 把顺序钉死：`32a-update-options → 32a-moq-content → 34a-keyword-map`。
另外核对：**词表的「新 title / 新 meta / 新 H1」三列里 `500` 出现 0 次**（`new_fields_with_500 0`），与验收项 (6) 的 grep 判据一致。

---

## 3. 验收项 (1)：字段来源勘查表（每页型 1 页，实测）

探针：`/tmp/fieldsrc2.php`（宿主 `E:\破甲工作区\.tmp\34a\fieldsrc2.php`），一次打印 `title_src / meta_src / 4 个 meta 值 / indexable / post_content 里的 h1`。

| 页型 | 样本 | id | hub | `<title>` 来源 | meta description 来源 | H1 来源 |
| --- | --- | --- | --- | --- | --- | --- |
| landing | `/foldable-gift-boxes/` | 15 | no | `_yoast_wpseo_title`（`indexable.title` 同值） | `_yoast_wpseo_metadesc`（`indexable.description` 同值） | `post_content` 的 `<h1>` |
| category | `/inserts/` | 33 | no | `_yoast_wpseo_title`（**`_packrivo_document_title` 存着不同值但不生效**） | `_yoast_wpseo_metadesc` | `post_content` 的 `<h1>` |
| product | `/product-magnetic-jewelry-box/` | 10 | no | `_yoast_wpseo_title` | `_yoast_wpseo_metadesc` | `post_content` 的 `<h1>` |
| guide | `/guide-jewelry-insert-fit/` | 48 | no | `_yoast_wpseo_title` | `_yoast_wpseo_metadesc` | `post_content` 的 `<h1>` |
| vs | `/packrivo-vs-arka/` | 2907 | no | `_yoast_wpseo_title`（该页 `doc_title` 为空） | `_yoast_wpseo_metadesc`（`_packrivo_description` 为空） | `post_content` 的 `<h1>` |
| about | `/about/` | 39 | **yes** | `_packrivo_document_title` | `_packrivo_description` | `post_content` 的 `<h1>` |
| services | `/services/` | 40 | no | `_yoast_wpseo_title`（`doc_title`="Packaging Sourcing & Project Coordination" **不生效**） | `_yoast_wpseo_metadesc` | `post_content` 的 `<h1>` |
| products | `/products/` | 8 | **yes** | `_packrivo_document_title` | `_packrivo_description` | 主题 `template-products-v4.php:54` 覆盖 |

原始输出摘录（可复现命令见 §9）：

```
[category] /inserts/  id=33 slug=inserts hub=no
    title_src = _yoast_wpseo_title (indexable.title 同值)
    yoast_title  = [Packaging Inserts: Foam, Paperboard, Fit Check | Packrivo]
    doc_title    = [Custom Packaging Inserts & Cards | Packrivo]
    idx_title    = [Packaging Inserts: Foam, Paperboard, Fit Check | Packrivo]
    meta_src  = _yoast_wpseo_metadesc (indexable.description 同值)

[about] /about/  id=39 slug=about hub=yes
    title_src = _packrivo_document_title (hub 白名单, wpseo_title@1100)
    meta_src  = _packrivo_description (hub 白名单, wpseo_metadesc@1100)

[products] /products/  id=8 slug=products hub=yes
    title_src = _packrivo_document_title (hub 白名单, wpseo_title@1100)
    h1(post_content) = [Custom packaging products.]
```

`/products/` 的 `post_content` H1 是旧文案（"Custom packaging products."），词表新 H1 是 "Every structure we can source"，因此脚本在该行走 `h1 conflict=1`（见 §7）；但渲染出来的 H1 由 `template-products-v4.php:54` 输出，等于词表新值 —— 这是冲突但结果正确的唯一一处。

### 3.1 渲染层归属机制（为什么要写 4 个键）

`wpseo_metadesc` 运行时挂载（实测 dump）：

```
p=10    PKPW_Product_Renderer::seo_description
p=99    Closure@packrivo-site-polish-20260922.php:8
p=1000  Closure@remediation-functions.php:353
p=1100  Closure@functions.php:101          <- hub 白名单页覆盖成 _packrivo_description
p=1200  Closure@quote-page.php:43          <- /get-a-quote/ 专用，压过一切
```

`pre_get_document_title` / `wpseo_title` 同构：p=10 `PKPW_Product_Renderer::seo_title`、p=1000 Closure、p=1100 `pkv4_hub_document_title`、p=1200 Closure。`pkv4_hub_document_title`（`functions.php:91-98`）只在 `pkv4_hub_metadata_id()` 白名单里取 `_packrivo_document_title`，白名单 = `products/jewelry-boxes/mailer-boxes/samples/blog/about/inquiry` + 首页（`functions.php:82-89, 117-136`）。

**哨兵验证**：把 `/about/`（id 39）的 `_packrivo_document_title` 改成 `SENTINEL HUB TITLE 9Z | Packrivo`，渲染 `<title>` 逐字变成它，而 `og:title` 仍是 `Custom Packaging Supplier: Who You Work With | Packrivo`（来自 indexable）。验证后已还原原值。
⇒ 结论：hub 页可见 `<title>` 由 `_packrivo_document_title` 决定，`og:*` 由 `wp_yoast_indexable` 决定；所以 34a 同时写 4 个 meta 键**和** indexable 的 `title/description`。

---

## 4. 验收项 (2)：dry-run 统计（原始输出）

```
map_rows=141 resolved=141 missing=0
gate_rank8=pass (8/8 strip_eq)
title: update=0 drift_update=0 skip=139 conflict=2 empty_new=0
meta:  update=0 skip=141
hub:   title update=4 drift_update=0 skip=3 conflict=0 | desc update=7 skip=0
h1:    update=0 drift_update=0 skip=139 conflict=1 no_content_h1=1
changed_pages=7 changed_fields=11
dry_run=1 (no database change, no backup file)
Success: 34a dry-run finished.
```

缺失清单：`missing=0`（141 个 URL 全部 `url_to_postid` 命中）。
唯一未解析的相邻项：词表里没有 `/blog/` 行，`/blog/` 也不在 hub 白名单命中集里 —— 无待写字段，不影响 141 行。

断言门（写库前执行，任一条不通过整轮中止）：
把新 title 里的 `MOQ 250`/`MOQ 500` 去掉后，必须与当前库 title 逐字相同。8 个排名页 `/labels/ /foldable-gift-boxes/ /jewelry-boxes/ /inserts/ /products/ /services/ /guide-jewelry-insert-fit/ /product-magnetic-jewelry-box/` → **8/8 通过**。

---

## 5. 验收项 (3)：两次本地跑（第一次 changed=N，第二次 changed=0）

第一次（真跑）：

```
backup=/tmp/backup/34a-before-20261105-050956.csv rows=1282
hub:   title update=4 drift_update=0 skip=3 conflict=0 | desc update=7 skip=0
changed_pages=7 changed_fields=11
after_check title_bad=0 meta_bad=0 h1_bad=0 hub_bad=0
new_strings=/tmp/out/34a-new-strings.csv new=0 total=347
Success: 34a applied: pages=7 fields=11
```

第二次（同一命令）：

```
title: update=0 drift_update=0 skip=139 conflict=2 empty_new=0
meta:  update=0 skip=141
hub:   title update=0 drift_update=0 skip=7 conflict=0 | desc update=0 skip=7
h1:    update=0 drift_update=0 skip=139 conflict=1 no_content_h1=1
changed_pages=0 changed_fields=0
changed_pages=0 — nothing to write (already applied).
Success: 34a finished: idempotent no-op.
```

幂等等值在**比较阶段**完成（同值直接 skip，不靠数据库唯一键），所以重跑不产生备份垃圾行、`34a-new-strings.csv` 也不追加（`new=0`）。

---

## 6. 验收项 (4)：回滚演练（回滚 → 抽检 5 页 → 再真跑恢复）

```
=== 1) rollback dry-run ===
rollback_file=/tmp/backup/34a-before-20261105-050956.csv
posts=141 meta_rows=296 h1_rows=0 idx_rows=846
dry_run=1 (no database change)
Success: 34a rollback dry-run finished.
=== 2) rollback real ===
restored meta=11 h1=0 idx=0 h1_problems=0
rolled_back=1
=== 3) spot-check 5 pages after rollback ===
/about/            About Packrivo | Custom Packaging from Shenzhen
/mailer-boxes/     Custom Mailer Boxes &amp; Shipping Boxes with Logo | Packrivo
/inquiry/          Request a Custom Packaging Quote | Packrivo
/products/         Custom Packaging: 134 Styles, MOQ 250 | Packrivo
/labels/           Custom Packaging Labels | Packrivo
=== 4) re-run real ===
hub:   title update=4 drift_update=0 skip=3 conflict=0 | desc update=7 skip=0
changed_pages=7 changed_fields=11
backup=/tmp/backup/34a-before-20261105-051918.csv rows=1282
after_check title_bad=0 meta_bad=0 h1_bad=0 hub_bad=0
Success: 34a applied: pages=7 fields=11
=== 5) spot-check 5 pages after re-apply ===
/about/            Custom Packaging Supplier: Who You Work With | Packrivo
/mailer-boxes/     Custom Mailer Boxes: Printed &amp; Shipping Ready | Packrivo
/inquiry/          Packaging Quote Request: Custom Boxes | Packrivo
/products/         Custom Packaging: 134 Styles, MOQ 250 | Packrivo
/labels/           Custom Packaging Labels | Packrivo
```

抽检读法：`/about/ /mailer-boxes/ /inquiry/` 三页回滚后回到旧 title、复跑后回到新 title ⇒ meta 回滚与重写都真实生效；`/products/` 新旧 title 相同（`MOQ 250` 版在两态一致，34a 对它只改 desc），`/labels/` 是非 hub 页，34a 没碰它 ⇒ 两态不变，符合预期。

回滚脚本行为说明：
- 默认取 `PK34A_BACKUP_DIR` 下最新 `34a-before-*.csv`；`h1_rows=0` 是因为本次备份里被写的都是 meta，H1 一列没写（`changed_fields` 全在 hub）。
- 141 条 `SKIP <id> h1 new_value 为空（这页 H1 未写入），无需回滚` 是预期噪声（H1 未改动）；输出里已用 `grep -v '^SKIP'` 裁剪。
- **实测坑**：`eval-file /tmp/34a-rb.php --file=/tmp/backup/xxx.csv` 会被 WP-CLI 拦成 `unknown --file parameter`（`eval-file` 不吃前导破折号参数）。第一次演练因此静默没回滚，抽检看到的仍是已应用状态。修正后改用「默认取最新一份」或裸 token 传参，drill 才真实执行（上面的 `restored meta=11` 就是真回滚）。
- 全量回滚基线（`34a-before-20261105-043010.csv`）一次演练结果：`posts=141 meta_rows=282 h1_rows=130 idx_rows=846` → `restored meta=205 h1=130 idx=615`，回滚后库回到 pre-34a 状态。

测完必须再真跑一次回到已应用态（上面第 4/5 步），否则站点停在被回滚的样子。

---

## 7. 验收项 (5)(6)：渲染层逐字比对

13 页（含 3 个排名页 `/labels/ /products/ /jewelry-boxes/` + 10 个其他页型）× 3 字段：

```
pages=13 checked_fields=39 ok=34 mismatch=5 keyword_hits=0
```

不一致的 5 项（全部是模板字面量，与 §6 表一一对应）：

```
MISMATCH /samples/      h1     got =Packaging samples                                       want=Custom Packaging Samples
MISMATCH /             h1     got =Custom packaging for jewelry, candles & gifts.           want=Custom packaging for jewelry, candles and gifts
MISMATCH /get-a-quote/  title  got =Custom Packaging Quote & Price Calculator | Packrivo    want=Get a Custom Packaging Quote | Packrivo
MISMATCH /get-a-quote/  meta   got =Box size, quantity, print and finish set the unit price… want=Custom packaging quote. DDP or FOB, quoted both ways.…
MISMATCH /get-a-quote/  h1     got =Custom Packaging Quote & Price Calculator               want=Get a packaging quote
```

三字段全中的 10 页：`/labels/ /products/ /jewelry-boxes/ /guide-jewelry-insert-fit/ /about/ /mailer-boxes/ /inquiry/ /candle-gift-set-box/ /corrugated-wine-shipper/ /magnetic-book-style-box/`。

验收项 (6)：`keyword_hits` = 在 `<title>` / `<meta name="description">` / 第一个 `<h1>` 上匹配 `moq 500|500-piece|500 pieces?|manufacturer|factory`（白名单放行 `not a factory`）的条数，**0**。

---

## 8. 三处模板字面量例外（改库无效；本次不动主题）

| 页面 | 字段 | 渲染值来源 | 为什么 34a 改不动 |
| --- | --- | --- | --- |
| `/samples/` | H1 | `wp-content/themes/packrivo-static-bridge-1.2.38/master-site-remediation.php:295` `packrivo_master_samples()` 写死 `<h1>Packaging samples</h1>` | 父主题在 `the_content` 里整块替换正文，`post_content` 的 H1 不参与渲染 |
| `/` | H1 | `wp-content/themes/packrivo-home-v4/front-page.php:72-74`，走 `pkv4_i18n_lookup('Custom packaging for jewelry, candles & gifts.')` | 首页 H1 由模板字面量 + 词典给，`post_content` 不参与 |
| `/get-a-quote/` | title + meta + H1 | `wp-content/themes/packrivo-home-v4/inc/quote-page.php:14-51` `pkv4_quote_strings()`，挂在 `wpseo_title` / `pre_get_document_title` / `wpseo_metadesc` 的 **priority 1200** | 高于 34a 写的 1100，压过所有库内键；`:13` 注释写明要同步 `deploy/i18n-merged/*-dict.json`，属 31b/34b |

另有 1 处「冲突但结果正确」：`/products/` 的 `post_content` H1 是 `Custom packaging products.`，词表要 `Every structure we can source`；渲染 H1 由 `template-products-v4.php:54` 输出，等于词表值，所以脚本报 `h1 conflict=1` 但页面正确。

---

## 9. 未落库 / 跳过的行（原样清单，跨轮次稳定）

```
CONFLICT /collapsible-magnetic-gift-box/ | title | db=Collapsible Magnetic Gift Box (MOQ 250) | Packrivo
                                            csv_old=Custom Collapsible Magnetic Gift Boxes | csv_new=Collapsible Magnetic Gift Boxes
CONFLICT /get-a-quote/                   | title | db=(空) | csv_old=Get a Quote - Packrivo
SKIP     /get-a-quote/                   | h1    | post_content 里没有 <h1>，该页 H1 由主题/词典输出
CONFLICT /products/                      | h1    | db=Custom packaging products. | csv_old=Every structure we can source
```

`/collapsible-magnetic-gift-box/` 是唯一一条「库里的值和词表 `现 title` 完全对不上」的 title：库里已经是 250 版且措辞不同（带 `with Logo` 之外的差异），脚本按保守策略不覆盖，留给 Claude 判断（词表只读，我不能改）。
`/get-a-quote/` 的 title 由 `quote-page.php:14-51` @1200 决定，库内为空是正常的。

---

## 10. 验收项 (7)：`34a-new-strings.csv`

`source,url,old_en,new_en`，表头 1 行 + **347 行**数据（66,754 B）。
两次运行都是 `new=0 total=347` ⇒ 本轮没有新增英文串，全部是历史轮次累积的待翻译对（外文页正文用）。
外文（`/ko/`、`/zh-CN/`）不是独立 post 行，是同一份内容在渲染期按 URL 前缀套词典，所以 141 行词表里没有外文行；英文 title 换完之后，若该页正文是外文，按 RUNBOOK §34a.9 记「等 dsh 翻译后与词典一起上线」。

---

## 11. 其他记录

- 渲染输出里有一条早前轮次留下的调试标记：`<!--DBG29Y uri=/about/ qs= is_page_privacy=0 is_page_50=0-`。不影响验收，但上线前应清掉那条 mu-plugin 的 echo。
- 词表统计（只读核对）：`title_same=73`、`h1_same=9` ⇒ 141 行里 73 行的 title 本来就不变、9 行 H1 不变；`new_fields_with_500=0`。
- 页型分布：`product 90 / landing 25 / category 6 / hub 5 / vs 4 / guide 3 / supplier 2 / cost-info 2 / home 1 / cost 1 / moq 1 / compare 1`。
- 探针复跑记录：`/inserts/` 的 meta description 首次探测时输出过一条与库内值不同的串，同一条命令复跑不复现（两次调用间隔约 20 分钟，库内值未变），已按复跑结果记录：`Custom packaging inserts. Compare structures and materials. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote.`（库内 `_yoast_wpseo_metadesc` / `indexable.description` 同值，页面只有 1 个 `name="description"`）。

---

## 12. 复现命令

```bash
# 干跑
docker exec -e PK34A_OUT_DIR=/tmp/out -e PK34A_BACKUP_DIR=/tmp/backup \
  packrivo-32a-wp php /tmp/wp-cli.phar --allow-root --path=/var/www/html \
  eval-file /tmp/34a-m.php /tmp/34a-map.csv dry-run

# 真跑（自动先写备份）
docker exec -e PK34A_OUT_DIR=/tmp/out -e PK34A_BACKUP_DIR=/tmp/backup \
  packrivo-32a-wp php /tmp/wp-cli.phar --allow-root --path=/var/www/html \
  eval-file /tmp/34a-m.php /tmp/34a-map.csv

# 回滚（取最新一份备份；不要传 --file=）
docker exec -e PK34A_BACKUP_DIR=/tmp/backup \
  packrivo-32a-wp php /tmp/wp-cli.phar --allow-root --path=/var/www/html \
  eval-file /tmp/34a-rb.php

# 字段源勘查 / 渲染比对
docker exec packrivo-32a-wp php /tmp/wp-cli.phar --allow-root --path=/var/www/html eval-file /tmp/fieldsrc2.php
docker exec packrivo-32a-wp sh /tmp/render13.sh
python .tmp/34a/compare3.py .tmp/34a/rout5
```

宿主辅助脚本：`.tmp/34a/{fieldsrc2.php, drill2.sh, probe5.sh, descowner.php, compare3.py, render13.sh}`。
