# Codex 结果 · 2026-10-27c（继续执行）

## 总结

A–F 已实施并分别提交；G 提交和推送见本报告最后的 Git 回执。没有部署生产、修改服务器或修改 www.packrivo.com。以下本地验证使用 `E:\破甲工作区\packrivo-live` 的快照数据库，端口 8081；本地结果不是对生产行为的验证。

27b 结果文件仍不存在。本地创建的 `/get-a-quote/` 页面 ID 为 3220；旧产品标题中的 Sourcing 没有批量改动，九个计划中的首饰子品类在本地均不存在。

本地环境通过 `E:\破甲工作区\.tmp\packrivo-27c-compose.yml` 将工作主题和插件挂载进容器。子主题声明父主题目录为 `packrivo-static-bridge`，本地原目录叫 `packrivo-static-bridge-1.2.38`，测试用同一目录的别名挂载。`DISABLE_WP_CRON`、`WP_HTTP_BLOCK_EXTERNAL` 在本地 WordPress 配置中启用。

重要：本地副本的 `packrivo-site-remediation-20261022.php` 将 `/get-a-quote/` 301 到 `/inquiry/`。本轮通过临时本地 MU 插件副本仅移除这条重定向后验证，原 MU 插件未改动。未来任何上线前必须检查并解决真实环境同类冲突；本次未核实生产的当前行为。

## A. URL 预填：代码和本地 HTTP 已验证

原 A 代码提交 `64c029f`。本轮命令（完整浏览器脚本 `E:\破甲工作区\.tmp\packrivo-27c-browser.cjs`）：

```text
$ node E:\破甲工作区\.tmp\packrivo-27c-browser.cjs
status: 200
url: http://localhost:8081/get-a-quote/?style=rigid&qty=1000
root: 1
attrs: { style: 'rigid', qty: '1000', product: '' }
active tiles: [ 'Rigid boxfrom $7.45' ]
buttons: [
  '01 Style',
  '02 Size',
  '03 Material',
  '04 Quantity',
  '05 Details',
  'Mailer boxfrom $3.65',
  'Product boxfrom $3.19',
  'Shipping boxfrom $2.88',
  'Rigid boxfrom $7.45',
  'Folding cartonfrom $2.34',
  'Stand-up pouchfrom $1.80',
  'Paper bagfrom $2.69',
  'Sticker sheetfrom $1.23',
  'Poly mailerfrom $2.11',
  '← Back',
  'Continue'
]
quantity-display: 1,000 units
quantity-slider: 1000
```

短代码原始输出（同一插件代码、禁用其他插件和主题）：

```text
$ wp eval-file /tasktest/pkq-27c-test.php --skip-plugins --skip-themes
<div class="pkq-root" data-style="rigid" data-qty="1000" data-product="product-drawer-jewelry-box"><noscript>This configurator needs JavaScript. Email hello@packrivo.com and we will quote manually.</noscript></div>
<div class="pkq-root" data-style="" data-qty="0" data-product=""><noscript>This configurator needs JavaScript. Email hello@packrivo.com and we will quote manually.</noscript></div>
<div class="pkq-root" data-style="" data-qty="250000" data-product=""><noscript>This configurator needs JavaScript. Email hello@packrivo.com and we will quote manually.</noscript></div>
```

截图：`E:\破甲工作区\.tmp\packrivo-27c-quote-step1.png`、`E:\破甲工作区\.tmp\packrivo-27c-quote-quantity.png`。原无覆盖时 `curl` 返回 301，响应头 `X-Redirect-By: Packrivo`，`Location: http://localhost:8081/inquiry/`；覆盖后返回 200。订单保存及测试邮件截获证据见上一版结果和提交 `64c029f`。

## B. 共用卡片和价格：完成，提交 `7f0ae15`

卡片使用独立产品页和报价链接；无安全结构映射或无法计算价格时不显示价格、使用 Get a price。价格以 500 件、指定 style 的参考尺寸和 v2 `DEFAULT_MATERIAL`、full-color、matte 计算，缓存键包含 pricing.json 和设置。未手改 pricing.json。`poly` 在 v2 首个产品的 25×1×35 输入下不满足 PHP 引擎最小宽度，故为 null，不用回退价；v2 没有 product style 的产品尺寸，因此也不臆造参考尺寸。

```text
$ wp eval-file /tasktest/pk27c-b-test.php
product-drawer-jewelry-box => 'rigid'
product-magnetic-jewelry-box => 'rigid'
textile-jewelry-pouch => NULL
product-sheet-labels => 'sticker'
product-regular-slotted-shipping-carton => 'shipping'
PASS mappings

$ wp eval-file /tasktest/pk27c-mapping.php | select three products
product-drawer-jewelry-box    rigid    2.83
product-regular-slotted-shipping-carton    shipping    2.31
product-sheet-labels    sticker    0.96

$ node <TypeScript priceQuote with the same 500-piece inputs>
rigid 2.83
sticker 0.96
shipping 2.31
```

输入依次为 rigid/rigidboard/9.5×9.5×4，sticker/white/8×8×0.1，shipping/kraft/30×25×20；均为 full-color/matte、500、rush=false。Node 对照代码位于本轮命令记录；WP 输出来自实际本地数据库。

全部 134 个产品页映射（slug → style → USD from；null 表示不显示）：

```text
clear-display-box	null	null
gift-bag-tissue-set	null	null
molded-pulp-insert	null	null
paper-mailer-bag	bag	1.63
poly-mailer-bag	poly	null
product-auto-bottom-display-carton	null	null
product-auto-bottom-slotted-outer-carton	shipping	2.31
product-auto-lock-bottom-carton	null	null
product-book-style-rigid-presentation-box	rigid	2.83
product-bookend-folding-carton	carton	1.28
product-box-bottom-fabric-tote	null	null
product-circle-roll-labels	null	null
product-coated-labels	null	null
product-collapsible-magnetic-gift-box	rigid	2.83
product-corrugated-book-wrap-mailer	null	null
product-corrugated-bottle-gift-carrier	null	null
product-corrugated-divider	null	null
product-corrugated-gusset-folder	null	null
product-corrugated-inlay	null	null
product-corrugated-reverse-tuck-pack	null	null
product-corrugated-roll-end-hinged-lid	mailer	2.48
product-corrugated-tuck-top-auto-bottom	null	null
product-corrugated-tuck-top-snap-bottom	null	null
product-counter-display-carton	null	null
product-cube-carry-carton	null	null
product-die-cut-handle-paper-bag	null	null
product-die-cut-insert	null	null
product-double-wall-frame-tray-lid	null	null
product-double-wall-frame-tray-sleeve	null	null
product-double-wall-tray-lid	null	null
product-drawer-jewelry-box	rigid	2.83
product-drawstring-fabric-gift-bag	null	null
product-euro-tote-paper-carrier	bag	1.63
product-eva-foam-holder	null	null
product-expandable-kraft-parcel-bag	null	null
product-finished-folding-gift-box	null	null
product-five-panel-corrugated-folder	null	null
product-five-panel-hanger-carton	null	null
product-flat-delivery-mailer	null	null
product-flip-out-dispenser-carton	null	null
product-flocked-foam-holder	null	null
product-flower-petal-top-gift-carton	null	null
product-fold-over-flap-fabric-pouch	null	null
product-foldable-lid-base-box	null	null
product-folded-box-liner	null	null
product-folded-tray-sleeve-box	null	null
product-folding-carton	carton	1.28
product-folding-gift-box	null	null
product-four-corner-display-tray	null	null
product-four-corner-pre-glued-carton	null	null
product-front-tuck-mailer	mailer	2.48
product-full-flap-auto-bottom-carton	null	null
product-full-overlap-seal-end-carton	null	null
product-full-overlap-slotted-outer-carton	shipping	2.31
product-gable-top-carry-carton	null	null
product-gift-hang-tag	null	null
product-gusset-folder-carton	null	null
product-hanging-tab-retail-carton	null	null
product-header-card-bag-topper	null	null
product-hexagonal-folding-carton	carton	1.28
product-hinged-metal-gift-container	null	null
product-hinged-nonmagnetic-rigid-box	rigid	2.83
product-hinged-thermoformed-clamshell	null	null
product-hook-floor-gift-display	null	null
product-information-card	null	null
product-jewelry-box-with-insert	null	null
product-jewelry-mounting-card	null	null
product-kraft-bubble-parcel-envelope	null	null
product-latch-metal-gift-container	null	null
product-lid-and-base-jewelry-box	rigid	2.83
product-lightweight-mailer	null	null
product-lock-cap-auto-bottom-carton	null	null
product-magnetic-jewelry-box	rigid	2.83
product-mailer-with-insert	null	null
product-molded-pulp-corner-cradle	null	null
product-molded-pulp-end-support-pair	null	null
product-multi-piece-pe-foam-support	null	null
product-multi-tier-floor-gift-display	null	null
product-nest-stack-thermoformed-tray	null	null
product-paired-corrugated-end-caps	null	null
product-paper-belly-band	null	null
product-paper-carton-sealing-tape	null	null
product-paper-merchandise-sleeve-bag	null	null
product-paper-padded-parcel-envelope	null	null
product-paperboard-grid-divider	null	null
product-paperboard-insert	null	null
product-paperboard-roll-end-dust-flap-carton	null	null
product-peg-hook-counter-display	null	null
product-pillow-jewelry-box	null	null
product-pinch-lock-paperboard-tray	null	null
product-pocket-floor-gift-display	null	null
product-poly-bubble-parcel-envelope	null	null
product-polyurethane-foam-holder	null	null
product-press-close-metal-gift-container	null	null
product-printed-mailer	mailer	2.48
product-printed-tissue-sheet	null	null
product-rectangle-roll-labels	null	null
product-regular-slotted-shipping-carton	shipping	2.31
product-reinforced-paper-sealing-tape	null	null
product-returnable-paper-parcel-bag	null	null
product-returnable-poly-parcel-bag	null	null
product-reverse-tuck-retail-carton	carton	1.28
product-rigid-chipboard-divider	null	null
product-rigid-paperboard-postal-envelope	null	null
product-round-metal-gift-container	null	null
product-screw-top-metal-gift-container	null	null
product-self-locking-corrugated-folder	null	null
product-sheet-labels	sticker	0.96
product-shelf-counter-display	null	null
product-shipping-document-window-pouch	null	null
product-shoulder-neck-rigid-box	rigid	2.83
product-side-lock-display-carton	null	null
product-side-lock-hinged-gift-tray	null	null
product-six-corner-pre-glued-carton	null	null
product-slide-in-card-blister	null	null
product-snap-bottom-slotted-outer-carton	null	null
product-snap-lock-bottom-carton	null	null
product-sos-paper-gift-bag	bag	1.63
product-square-roll-labels	null	null
product-straight-tuck-retail-carton	carton	1.28
product-tab-lock-roll-end-corrugated-pack	null	null
product-thermoformed-multi-cavity-tray	null	null
product-three-flap-lock-corrugated-pack	null	null
product-tray-floor-gift-display	null	null
product-tuck-end-gift-box	null	null
product-tuck-end-sleeve-carton	null	null
product-two-door-rigid-presentation-box	rigid	2.83
product-wide-gusset-paper-carrier	bag	1.63
product-window-lid-rigid-gift-box	rigid	2.83
product-zippered-fabric-gift-pouch	null	null
round-cardboard-tube-box	null	null
textile-jewelry-pouch	null	null
thermoformed-insert-tray	null	null
windowed-folding-carton	carton	1.28
```

## C. 首页：完成主体，4 张合格卡片，提交 `7bd2f34`；两张后续补图见 F

卡片依序为 Drawer Jewelry、Lid-and-Base Jewelry、Window Gift、Mailer。只找到四个同时有价格映射和封面记录的优先产品；其中后两张沿用已有远程封面，未在禁止外部请求的本地浏览器中验证图片加载。未凑够 8 张。FAQ 文字维持原样，5 项与 schema 一致。首页统计、样品区、价格说明和手风琴已实现。

```text
$ wp eval-file /tasktest/pk27c-home-eval.php
Warning: Attempt to read property "comment_count" on null in /var/www/html/wp-includes/general-template.php on line 3224
forbidden_terms=none
configure_count=4
all_have_style=yes
faq_details=5 faq_schema=5
```

上述 warning 出现在 wp eval 的人工模板调用；实际 HTTP 页面返回 200。初次检测 375/1280 溢出，修复页头后复验：

```text
$ node E:\破甲工作区\.tmp\pk27c-home-test.cjs
{"width":375,"status":200,"overflow":false,"details":5,"schema":5,"configure":["http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box","http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-lid-and-base-jewelry-box","http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-window-lid-rigid-gift-box","http://localhost:8081/get-a-quote/?style=mailer&qty=500&product=product-corrugated-roll-end-hinged-lid"],"banned":[]}
{"width":768,"status":200,"overflow":false,"details":5,"schema":5,"configure":["http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box","http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-lid-and-base-jewelry-box","http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-window-lid-rigid-gift-box","http://localhost:8081/get-a-quote/?style=mailer&qty=500&product=product-corrugated-roll-end-hinged-lid"],"banned":[]}
{"width":1280,"status":200,"overflow":false,"details":5,"schema":5,"configure":["http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box","http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-lid-and-base-jewelry-box","http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-window-lid-rigid-gift-box","http://localhost:8081/get-a-quote/?style=mailer&qty=500&product=product-corrugated-roll-end-hinged-lid"],"banned":[]}
```

截图在 `E:\破甲工作区\.tmp\packrivo-27c-home-375.png`、`-768.png`、`-1280.png`。

## D. 产品页：完成主体，提交 `641238f`

GET 搜索、六组筛选、价格排序、空状态、底部 CTA 和分页已实施。九个子类在本地库都不存在，所以不显示子类 chip，无法用“一个存在的子类”验收。`wp eval`（本地模板调用）实际输出：

```text
$ wp eval-file /tasktest/pk27c-product-eval.php
group=jewelry | 7 structures | Custom Drawer Jewelry Box Sourcing / Custom Jewelry Box with Fitted Insert Sourcing / Custom Jewelry Mounting Card Sourcing
group=jewelry | 7 structures | Custom Drawer Jewelry Box Sourcing / Custom Jewelry Box with Fitted Insert Sourcing / Custom Jewelry Mounting Card Sourcing
q=drawer | 1 structures matching “drawer” | Custom Drawer Jewelry Box Sourcing
sort=price-asc | 134 structures | Custom Sheet Labels Sourcing / Custom Bookend Folding Carton Sourcing / Custom Folding Cartons and Gift Boxes Sourcing
```

第二行输入了不存在的 `sub=drawer-jewelry-boxes`，因此按无子类处理；不能视为存在子类的验收。wp eval 出现 `SERVER_NAME` 和 `comment_count` 的 CLI 警告。HTTP 检查四种参数返回 200；canonical 为 `http://localhost:8081/products/`，`q=drawer` robots 含 `noindex, follow`。价格排序初次超时，按产品预计算一次后复测通过。

## E. 博客：完成主体，提交 `d95beb8`

```text
$ wp eval-file /tasktest/pk27c-journal-eval.php
pg=1 | articles=12 | guide=Start with the product and the delivered packing job / Choose between an integrated tray and separate support positions / Read product-pack labels separately from insert-part counts / Compare two complete purchase scopes / Check what your packing team will receive
pg=28 | articles=8 | guide=

$ wp eval-file /tasktest/pk27c-related.php
One-Piece Trays vs Separate End Supports in Molded Pulp Packs
group=mailer
  Custom Corrugated Roll End Hinged Lid Sourcing
  Custom Mailer Box Sourcing
  Custom Paper Mailer Bag Sourcing
Jewelry Pouch, Box or Both: Choose the Customer Handoff
group=jewelry
  Custom Drawer Jewelry Box Sourcing
  Custom Lid-and-Base Jewelry Box Sourcing
  Custom Rigid Magnetic Jewelry Box Sourcing
Paper or Book Cloth Covers for a Rigid Jewelry Gift Box
group=jewelry
  Custom Drawer Jewelry Box Sourcing
  Custom Lid-and-Base Jewelry Box Sourcing
  Custom Rigid Magnetic Jewelry Box Sourcing
```

`rg -n '/inquiry/' E:\破甲工作区\packrivo-wp\packrivo-home-v4` 无输出（退出码 1）。博客 HTTP 的第一页、28 页、topic、sort 参数均有根 canonical；单篇实际页面返回 200、相关产品 3 张，CTA 两按钮存在。封面在本地浏览器中没有测试外部图片下载。

## F. 卡片图：两张已补，提交 `ba8439b`

| 产品页 | 图像 | 原图对照 |
| --- | --- | --- |
| product-drawer-jewelry-box | drawer-box-796237 | 红色抽屉、拉带、内托及开合状态与原图一致；保留产品结构，去掉图上叠加文字 |
| product-lid-and-base-jewelry-box | rigid-lid-and-base-box-251101 | 黑白天地盖、内托与配件和原图相符；去掉图上叠加文字 |
| 排除 | drawer-box-033975 | 原图玫瑰压纹及压印文字丢失 |

两图均有本地媒体库 800 与 1200 WebP，描述性 alt 和指定 caption。媒体 ID 为 3222–3225，媒体及本地数据库修改不在 Git 中。原图对照图位于 `E:\破甲工作区\audit-parkrivo\codex-output\_qa-review\`。

```text
$ node E:\破甲工作区\.tmp\pk27c-convert.cjs
drawer 800 800 800 webp
drawer 1200 1200 1200 webp
lid-base 800 800 800 webp
lid-base 1200 1200 1200 webp

$ curl -w <HTTP status and type> <local image>
drawer 1200: HTTP 200 image/webp
lid 800: HTTP 200 image/webp
```

## 字典与待补译清单

本地当前 `/wp-content/uploads/packrivo-i18n` 不存在。找到 `E:\破甲工作区\packrivo-handoff\live` 下 11 个 2026-10-23 快照，但不能证明是当前实际字典；没有直接修改它们。插件匹配 title、text、block、attr（含正文和 alt/placeholder）。下面列出本轮新增或改动、需要与实际 11 个语种字典核对和补译的英语，动态数值用 `{...}` 表示：

- `From`；`from ${price} / unit`；`at 500 pcs · {dimensions} cm`；`MOQ 500`；`Configure →`；`Get a price →`。
- `Custom jewelry boxes, pouches and gift boxes with your logo`；`Jewelry boxes`；`Gift boxes`；`Mailer boxes`；`Custom Drawer Jewelry Boxes`；`Custom Magnetic Jewelry Boxes`；`Custom Jewelry Boxes`；`Custom Window Gift Boxes`；`Custom Mailer Boxes`；`Custom Sheet Labels`。
- `Setup is itemised`；`and waived above {freePrintSetupFrom} printed pieces.`；`Freight is separate`；`quoted per shipment, never buried in the unit price.`；`No tooling surprise`；`one-time plate and die cost, shown before you submit.`；`Indicative, excludes freight. At 5,000 pieces the unit price is {N}% lower than at 500. Final price confirmed after dieline review.`
- `Samples`；`Hold the board before you approve the board.`；`See the structure, board and finish in hand before you commit to a run.`；`Rigid / Greyboard 2 mm`；`Mailer / Kraft E-flute`；`Drawer / Rigid board`；`Pouch / Fabric`；`Order a sample`；`Ask a question`；`Ask a specific question →`。
- `Dielines included`；`Reference prices on every page`；`Search`；`Price: low to high`；`Price: high to low`；`{N} structures`；`{N} structures matching “{q}”`；`Nothing matches that yet.`；`Describe what you need and we will source it.`；`Describe your pack`；`Need something not on this page?`；`Counter displays, rigid trays, book-style boxes — send a photo of the product and a rough volume, and we will come back with a structure and a price range.`；`Send a brief`；`Start from a price`。
- `Supplier briefs, material notes and pricing explainers for small brands sourcing custom packaging.`；`Featured`；`{N} min read`；`{N} min`；`Read the guide →`；`In this guide`；`Related structures`；`Put it to work on a real pack`；`The quote builder applies these numbers to your size and quantity. We confirm the dieline before anything is produced.`；`Build a quote`。
- `Supplier product photo, retouched (text and watermarks removed)`；`Red drawer jewelry box with ribbon pull and insert`；`Black and white lid-and-base jewelry boxes with inserts`。

以上是待核对清单，不是已翻译的证明。旧字典快照中抽查的首页旧标题、旧定价说明与博客旧导语均无精确键；实际环境仍需核对每个语言的实际输出。

## 尚需处理

- 先解决实际环境的旧报价重定向、父主题目录匹配以及 11 语种字典；生产上线不在本轮。
- 原 FAQ 的现有问答含固定价格，任务同时要求问答一字不改及价格均由引擎计算，本轮按“FAQ 一字不改”保留，需站长决定如何消除冲突。产品页指定的 `Reference prices on every page` 与无映射产品不显示价格的要求也存在表述冲突。
- 没有现存九个子品类，子筛选实际选择行为待页面创建后验证。首页不足 8 张，剩余产品缺封面或映射。

## G. 备份仓库推送回执

```text
$ git push origin main
To https://github.com/jiaxiangli477-stack/packrivo-backup.git
   bb63d3d..66221c6  main -> main
```

逐节提交：B `7f0ae15`、C `7bd2f34`、D `641238f`、E `d95beb8`、F `ba8439b`；报告提交 `66221c6`。本回执本身随后作为补充报告提交推送。
