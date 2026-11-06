# 32a-blog 改写稿 · 3 篇以 500 件起订量为论点的博客（改写为 250 pieces per product / design）

交付范围：只出改写稿文件。未改动容器、数据库、词典、WP；未发布、未导入。

## 一、原文来源与口径

- 原文正文：`E:\破甲工作区\packrivo-backup-32a\.rehearsal-32a\32a5-blog-data.json` 的 `posts` 表，post_id 448 / 508 / 1621 的 `post_title` 与 `post_content`，逐字节取用（正文里的 `&#8217;` 等实体原样保留）。
- 线上核对：`https://www.packrivo.com/2026/10/01/packaging-moq-design-planning/`（HTTP 200）首段与库内文本一致：`planning basis of 500 pieces per product / design`、`A total of 500 across several different designs doesn't meet that planning basis.`
- 元字段来源：同库 `meta` 表 meta_id 869/871（448）、1100/1101（508）、7586/7587（1621），本轮只有 448 的 `_yoast_wpseo_title` 与 `_yoast_wpseo_metadesc` 需要改。
- 起订量口径统一为 **250 pieces per product / design**（指令规则 1）。

## 二、数字处理选择（指令规则 1 要求注明）

| 篇 | 处理 | 说明 |
| --- | --- | --- |
| 448 | **按 250 重算举例** | 原文举例按 500 起订量设计（计划 400 件、订 500 剩 100），250 下计划量必须同时下调才能自洽，因此示例需求与分配表整组重算：计划 200 件、订 250 剩 50。 |
| 508 | 只改起订量数字 | 全文没有依赖 500 的算例，2 处均为口径陈述与链接文字。 |
| 1621 | **保留 500 件举例** | `If a hypothetical order were 500 identical holders and all 416 planned uses occurred, 84 would remain` 里的 500 是举例下单量、不是起订量；416 件用量来自本文消耗表，改成 250 会让 416 > 下单量、算例失效，故保留举例并只改起订量陈述。500 - 416 = 84 原样成立。 |

已批准的表述 `MOQ 250 for printed boxes; unprinted stock from 100 pieces; runs of 100–249 quoted on request` 本轮未插入正文：三篇的论点是数量工作表，不涉及未印库存起订或 100–249 区间报价。该表述作为既定口径保留，供后续需要写未印库存时直接使用。

## 三、post 448 · packaging-moq-design-planning

slug 不变：`packaging-moq-design-planning`，不加 301。全文改动 17 处 `500`，归为 16 组（图 1 alt 一组含 2 处）。

### 3.1 标题与 meta

| field | 原句 | 新句 | 备注 |
| --- | --- | --- | --- |
| post_title | Custom Packaging MOQ: Plan 500 Pieces per Design | Custom Packaging MOQ: Plan 250 Pieces per Design | 48 字符（≤60），含 MOQ 与 250 |
| _yoast_wpseo_title | Custom Packaging MOQ: Plan 500 Pieces per Design | Custom Packaging MOQ: Plan 250 Pieces per Design | 与 post_title 取同值 |
| _yoast_wpseo_metadesc | Plan a 500-piece packaging MOQ by design. Separate selling products, packaging variants and reserve allocations with a clear quantity worksheet for your brief. | Plan a 250-piece packaging MOQ by design. Separate selling products, packaging variants and reserves with a clear quantity worksheet for your brief. | 148 字符（120–155） |

### 3.2 正文逐句对照（16 组，覆盖 17 处 500）

| # | 位置 | 原句 | 新句 | 说明/验算 |
| --- | --- | --- | --- | --- |
| 1 | 首段 | current project planning basis of 500 pieces per product / design | current project planning basis of 250 pieces per product / design | 首段起订量口径 500→250 |
| 2 | 首段 | A total of 500 across several different designs doesn&#8217;t meet that planning basis. | A total of 250 across several different designs doesn&#8217;t meet that planning basis. | “多款合计 500”同步为 250：多设计合计仍不等于单设计起订量 |
| 3 | section-1 第 4 段 | current project planning basis is 500 pieces per product / design | current project planning basis is 250 pieces per product / design | section-1 起订量口径陈述 500→250 |
| 4 | section-1 末段 | If you need 500 complete packs | If you need 250 complete packs | “500 个完整套件”：单位拆分逻辑不变，只换口径数字 |
| 5 | section-1 末段 | A quotation for 500 boxes should show | A quotation for 250 boxes should show | “500 只箱”报价：同上 |
| 6 | section-2 首段 | Suppose your brand plans 180 silver necklaces, 120 gold necklaces and 100 pendant sets. All 400 selling units use the same agreed box configuration. Ordering 500 boxes leaves 100 beyond that planned allocation. | Suppose your brand plans 90 silver necklaces, 60 gold necklaces and 50 pendant sets. All 200 selling units use the same agreed box configuration. Ordering 250 boxes leaves 50 beyond that planned allocation. | 单一设计示例整组重算：90+60+50=200 计划量，250-200=50 剩余 |
| 7 | section-2 第 3 段 | The launch has 400 selling units but two packaging designs. On a 500 piece basis for each, the planning total becomes 1,000 boxes. | The launch has 200 selling units but two packaging designs. On a 250 piece basis for each, the planning total becomes 500 boxes. | 两设计示例重算：200 件分成 150 项链箱 + 50 吊坠盒，250x2=500 总订购量 |
| 8 | 工作表 necklace 行 | <td>One shared necklace box</td> / <td style="text-align:right">300</td> / <td style="text-align:right">500</td> / <td style="text-align:right">200</td> | <td>One shared necklace box</td> / <td style="text-align:right">150</td> / <td style="text-align:right">250</td> / <td style="text-align:right">100</td> | 工作表项链行：150 计划 / 250 订购 / 100 未分配（250-150=100） |
| 9 | 工作表 pendant 行 | <td>Deeper pendant set box</td> / <td style="text-align:right">100</td> / <td style="text-align:right">500</td> / <td style="text-align:right">400</td> | <td>Deeper pendant set box</td> / <td style="text-align:right">50</td> / <td style="text-align:right">250</td> / <td style="text-align:right">200</td> | 工作表吊坠行：50 计划 / 250 订购 / 200 未分配（250-50=200） |
| 10 | section-2 表后段 | Filling every blank with 500 would create | Filling every blank with 250 would create | “每格都填 500”反例同步为 250 |
| 11 | 图 1 alt | alt="Quantity table showing BOX-A 300 uses, 500 inquiry pieces and 200 remaining; BOX-B 100 uses, 500 pieces and 400 remaining." | alt="Quantity table comparing planned selling units, inquiry quantity and unallocated pieces for a shared necklace box and a deeper pendant set box." | 图 1 alt 含 2 处 500，整句改成不含数字的描述（位图仍是旧数字，见第八节残余项） |
| 12 | 图 2 alt | alt="Hypothetical MOQ diagram with two packaging designs at 500 pieces each" | alt="Hypothetical MOQ diagram showing two packaging designs planned on the same quantity basis" | 图 2 alt 改成不含数字的描述（同上） |
| 13 | project-comparison 正文 | the necklace row has 500 ordered pieces against 300 planned selling units, leaving 200. If 20 are reserved for photography and packing development and 30 for replacement packing, 150 remain for future sales. | the necklace row has 250 ordered pieces against 150 planned selling units, leaving 100. If 10 are reserved for photography and packing development and 20 for replacement packing, 70 remain for future sales. | 分配段重算：250 订 - 150 计划 = 100 剩余；100-10-20=70 远期销售 |
| 14 | project-comparison 分配表 | <tr><td>Planned selling units</td><td>300</td></tr><tr><td>Photography and packing development</td><td>20</td></tr><tr><td>Replacement packing</td><td>30</td></tr><tr><td>Future-sales stock</td><td>150</td></tr><tr><td>Total allocated</td><td>500</td></tr> | <tr><td>Planned selling units</td><td>150</td></tr><tr><td>Photography and packing development</td><td>10</td></tr><tr><td>Replacement packing</td><td>20</td></tr><tr><td>Future-sales stock</td><td>70</td></tr><tr><td>Total allocated</td><td>250</td></tr> | 分配表同步：150 / 10 / 20 / 70，合计 250 |
| 15 | project-comparison 校验句 | The row reconciles because 300 + 20 + 30 + 150 = 500. Reserves are part of the purchase, not an additional 50 pieces to add on top. | The row reconciles because 150 + 10 + 20 + 70 = 250. Reserves are part of the purchase, not an additional 30 pieces to add on top. | 合计校验句改为 150 + 10 + 20 + 70 = 250；储备量 50→30（摄影 10 + 替换 20） |
| 16 | 末段下载链接 | Download the custom packaging MOQ 500 pieces per design project record (.csv) | Download the custom packaging MOQ 250 pieces per design project record (.csv) | 下载链接文字里的起订量 500→250 |

## 四、post 508 · multi-sku-jewelry-packaging-fit-matrix

标题与 `_yoast_wpseo_title`（`Multi SKU Jewelry Packaging: Build a Fit Matrix`）、`_yoast_wpseo_metadesc`（1101）均不含 500，不改。

| # | 位置 | 原句 | 新句 | 说明 |
| --- | --- | --- | --- | --- |
| 1 | section-3 首段 | Packrivo&#8217;s starting MOQ is 500 pieces per product / design. | Packrivo&#8217;s starting MOQ is 250 pieces per product / design. | 口径陈述，仅改数字 |
| 2 | section-3 第 4 段链接文字 | 500-piece MOQ planning worksheet | 250-piece MOQ planning worksheet | 链到 448 的数字前缀同步；`href` 不变 |

## 五、post 1621 · retail-demonstrator-material-plan

标题与 `_yoast_wpseo_title`（`Retail Demonstrator Packaging: Count Material Use`）、`_yoast_wpseo_metadesc`（7587）均不含 500，不改。

| # | 位置 | 原句 | 新句 | 说明 |
| --- | --- | --- | --- | --- |
| 1 | 消耗表后第 2 段 | Packrivo's starting basis is 500 pieces per product/design | Packrivo's starting basis is 250 pieces per product/design | 口径陈述，仅改数字；该段用直引号，与库内一致 |
| 2 | 同段算例 | If a hypothetical order were 500 identical holders and all 416 planned uses occurred, 84 would remain | 不改 | 举例下单量，非起订量；416 来自本文消耗表，500-416=84 成立（选择：保留举例） |

## 六、举例算术验算（逐条）

| 篇 | 验算 | 结果 |
| --- | --- | --- |
| 448 | 单一设计：90 + 60 + 50 = 200（计划销量）；250 - 200 = 50（剩余） | 通过 |
| 448 | 两设计拆分：150（项链箱）+ 50（吊坠盒）= 200 = 计划销量 200 | 通过 |
| 448 | 两设计总订购：250 x 2 = 500 | 通过 |
| 448 | 项链行：250 - 150 = 100（未分配） | 通过 |
| 448 | 吊坠行：250 - 50 = 200（未分配） | 通过 |
| 448 | 分配表：150 + 10 + 20 + 70 = 250（等于订购量 250）；250 - 150 = 100 未分配；100 - 10 - 20 = 70 远期销售 | 通过 |
| 1621 | 保留 500 件举例：500 - 416 = 84（保留，未改举例） | 通过 |

## 七、post 448 完整新正文（HTML，原标签结构保留）

```html
<p>Plan your launch around Packrivo&#8217;s current project planning basis of 250 pieces per product / design, then build a quantity sheet for the whole launch. A total of 250 across several different designs doesn&#8217;t meet that planning basis. Identify which packaging stays identical, which changes, and how many finished pieces you need for each before asking for a project quotation.</p><details class="packrivo-article-toc"><summary>In this guide</summary><ul><li><a href="#section-1">Separate the product count from the packaging design count</a></li><li><a href="#section-2">Build a quantity plan with a visible surplus</a></li><li><a href="#section-3">Use this worksheet for your project</a></li><li><a href="#section-4">Compare simplification choices without guessing the price</a></li><li><a href="#section-5">Send the scenario you can explain</a></li><li><a href="#project-comparison">Keep reserves inside the same counted design row</a></li><li><a href="#section-7">Send the current configuration for review</a></li></ul></details>
<p>A small collection can contain several selling products but only one packaging design. It can also contain one selling product with several distinct packaging designs. Keeping those two counts separate makes an early inquiry easier to evaluate and keeps a launch plan from hiding a shortage behind its total.</p>
<h2 id="section-1">Separate the product count from the packaging design count</h2>
<p>Start with a row for every packaging configuration you want supplied. Give it a plain identifier, such as BOX-A or LABEL-B. Alongside it, record structure, dimensions, material, printed artwork, finish and included components. This is your working description of a design, subject to project confirmation.</p>
<p>Five necklace styles might use the same box and insert. If the delivered packaging is identical, those necklace styles can share one packaging row in your plan. A gift set with a deeper insert belongs in another row because the supplied configuration changes, even if the logo looks the same.</p>
<p>Artwork changes deserve their own flag. A blue logo and a gold logo on otherwise similar boxes may involve different production arrangements. Write the difference down and ask whether it creates a separate design for the quotation. Don&#8217;t merge it merely because both boxes have the same size.</p>
<p>Packrivo&#8217;s current project planning basis is 250 pieces per product / design. Confirm the exact grouping and commercial conditions for the proposed package before an order. The <a href="https://www.packrivo.com/2026/09/21/packaging-supplier-brief-small-brand/">supplier brief essentials</a> help gather the structural details that define those rows.</p>
<p>A printed box, an insert and a label also need clearly named units. If you need 250 complete packs, write the required count of each component. A quotation for 250 boxes should show whether the inserts and labels are included, supplied separately or still open.</p>

<h2 id="section-2">Build a quantity plan with a visible surplus</h2>
<p>Use an illustrative launch rather than an industry forecast. Suppose your brand plans 90 silver necklaces, 60 gold necklaces and 50 pendant sets. All 200 selling units use the same agreed box configuration. Ordering 250 boxes leaves 50 beyond that planned allocation.</p>
<p>That remaining quantity has a job only if you assign it one. You might allocate some to photography, handling trials or replacement packing, and keep the balance for future sales. Choose those amounts from your operation. A universal spare percentage would hide differences between hand packing, fulfilment and retail use.</p>
<p>Now change the example: the necklace products use one box, while the pendant set uses another size. The launch has 200 selling units but two packaging designs. On a 250 piece basis for each, the planning total becomes 500 boxes. The resulting surplus is a buying decision that should be visible before artwork is finished.</p>
<p>This worksheet separates demand from the inquiry quantity. The numbers are editorial examples, not Packrivo customer orders.</p>
<p class="packrivo-table-hint">Swipe the table to view all fields.</p>
<div class="packrivo-table-scroll" role="region" aria-label="Planning worksheet; scroll horizontally to view all fields" tabindex="0">
<table>
<thead>
<tr>
<th>Packaging row</th>
<th style="text-align:right">Planned selling units</th>
<th style="text-align:right">Inquiry quantity</th>
<th style="text-align:right">Unallocated pieces</th>
<th>Decision to make</th>
</tr>
</thead>
<tbody>
<tr>
<td>One shared necklace box</td>
<td style="text-align:right">150</td>
<td style="text-align:right">250</td>
<td style="text-align:right">100</td>
<td>Assign reserves and future use</td>
</tr>
<tr>
<td>Deeper pendant set box</td>
<td style="text-align:right">50</td>
<td style="text-align:right">250</td>
<td style="text-align:right">200</td>
<td>Keep, simplify or defer this design</td>
</tr>
<tr>
<td>Optional printed sleeve</td>
<td style="text-align:right">100</td>
<td style="text-align:right">To confirm</td>
<td style="text-align:right">To calculate</td>
<td>Confirm its separate quantity basis</td>
</tr>
</tbody>
</table>
</div>
<p>The sleeve row stays open because a component&#8217;s quantity needs its own confirmation. Filling every blank with 250 would create a complete-looking sheet without establishing what the project includes.</p>
<figure class="packrivo-teaching-figure"><a href="https://www.packrivo.com/wp-content/uploads/packrivo-project-notes-20261001/01-worksheet.png" target="_blank" rel="noopener noreferrer" aria-label="View worksheet diagram at full size"><img src="https://www.packrivo.com/wp-content/uploads/packrivo-project-notes-20261001/01-worksheet.webp" width="1200" height="900" alt="Quantity table comparing planned selling units, inquiry quantity and unallocated pieces for a shared necklace box and a deeper pendant set box." loading="lazy" decoding="async"></a><figcaption>Illustrative allocations. Give remaining pieces a purpose and confirm optional component quantities separately. <a href="https://www.packrivo.com/wp-content/uploads/packrivo-project-notes-20261001/01-worksheet.png" target="_blank" rel="noopener noreferrer">View full size</a></figcaption></figure>
<section class="packrivo-download-tool" aria-label="Editable planning worksheet">
<h2 id="section-3">Use this worksheet for your project</h2>
<p>Use one row per packaging design or separate component. Record its quantity unit and product allocation, then ask which proposed grouping the project quotation covers.</p>
<p><a class="button" href="https://www.packrivo.com/wp-content/uploads/packrivo-project-worksheets-20261001/packaging-moq-design-planning-worksheet.csv" download="packaging-moq-design-planning-worksheet.csv">Download the worksheet (.csv)</a></p>
<p class="helper">Open in Excel, Google Sheets or another spreadsheet app. Fill in your actual project details and keep the agreed file and sample versions with the record.</p>
</section>
<h2 id="section-4">Compare simplification choices without guessing the price</h2>
<p>A shared outer box with different information cards is one option to discuss. It can reduce the number of outer box configurations while preserving a product-specific message. The cards still need their own dimensions, artwork and quantities; treat the complete set as a defined assembly.</p>
<p>Another option is to launch fewer packaging variants first. Mark the deferred version and retain its product requirements so it can be evaluated later. A deferred design should disappear from the current quantity total, rather than remain as a half-funded promise in the same sheet.</p>
<p>You can also ask for the original separate designs to be quoted as planned. The worksheet may show that the surplus is acceptable given the brand&#8217;s storage space and expected use. That judgment needs the actual proposal, your available cash and a reasonable stock plan.</p>
<p>Record each option as a new scenario. Keep the same product demand underneath it, and change only the packaging configuration or inquiry quantity. This gives you a fair comparison between a shared design, separate designs and a staged launch.</p>
<p>Keep each artwork revision beside its packaging design identifier. When one variant changes, update that row and ask whether the proposed design grouping or quantity basis also changes.</p>
<p>Before comparing offers, use the <a href="https://www.packrivo.com/2026/09/28/custom-packaging-quote-comparison-checklist/">quote comparison checklist</a> to align the unit, included components and delivery scope. A shared box scenario and a box plus sleeve scenario may have different totals for sound reasons. Ask for those differences to be itemized.</p>
<h2 id="section-5">Send the scenario you can explain</h2>
<p>Give your inquiry one chosen scenario and name any alternatives you want evaluated. Include each design identifier, required pieces, product allocation, artwork status, components and destination. If two configurations might share a production basis, make that a written question beside the rows concerned.</p>
<p>Date the quantity sheet and identify its owner. Update it when a product launch changes, a design is deferred or a component is removed. Keeping old quantities in an email thread while sending new artwork is an easy way to create competing instructions.</p>

<h2 id="project-comparison">Keep reserves inside the same counted design row</h2><figure class="packrivo-teaching-figure"><div role="region" tabindex="0" aria-label="Scrollable teaching diagram" style="overflow-x:auto"><a href="https://www.packrivo.com/wp-content/uploads/blog-overhaul-20261016/a3fefc105332c20b/assets/b03-448-teaching.webp"><img src="https://www.packrivo.com/wp-content/uploads/blog-overhaul-20261016/a3fefc105332c20b/assets/b03-448-teaching.webp" width="1536" height="1024" loading="lazy" alt="Hypothetical MOQ diagram showing two packaging designs planned on the same quantity basis" style="min-width:760px;width:100%;height:auto"></a></div><figcaption>AI-assisted concept, not a physical sample or tested result. The diagram illustrates the relationships discussed below. <a href="https://www.packrivo.com/wp-content/uploads/blog-overhaul-20261016/a3fefc105332c20b/assets/b03-448-teaching.webp">View full-size diagram</a>.</figcaption></figure><p>Give every remaining piece a named allocation before adding another design. In the two-design example above, the necklace row has 250 ordered pieces against 150 planned selling units, leaving 100. If 10 are reserved for photography and packing development and 20 for replacement packing, 70 remain for future sales. All quantities here are hypothetical planning choices, not recommended reserve percentages.</p><div class="packrivo-table-scroll" role="region" tabindex="0" aria-label="Scrollable project comparison" style="overflow-x:auto"><table><thead><tr><th>Necklace box allocation, hypothetical</th><th>Pieces</th></tr></thead><tbody><tr><td>Planned selling units</td><td>150</td></tr><tr><td>Photography and packing development</td><td>10</td></tr><tr><td>Replacement packing</td><td>20</td></tr><tr><td>Future-sales stock</td><td>70</td></tr><tr><td>Total allocated</td><td>250</td></tr></tbody></table></div><p>The row reconciles because 150 + 10 + 20 + 70 = 250. Reserves are part of the purchase, not an additional 30 pieces to add on top. For the pendant-set box, calculate its own allocations instead of transferring necklace demand into a different configuration.</p><p>If an optional sleeve remains undecided, leave its order quantity open and identify the owner of the commercial question. A complete-looking total with an assumed component quantity can conceal a missing quotation. The <a href="https://www.packrivo.com/2026/10/05/packaging-set-quote-units/">component and set unit guide</a> separates those counts, while the <a href="https://www.packrivo.com/2026/10/05/sampling-production-order-lines/">sampling quantity record</a> keeps trial requests out of the production MOQ calculation.</p><p><a href="https://www.packrivo.com/wp-content/uploads/blog-overhaul-20261016/a3fefc105332c20b/downloads/b03-448-project-record.csv" download>Download the custom packaging MOQ 250 pieces per design project record (.csv)</a>. The file has named planning fields and blank project rows. It contains no preset acceptance limits or approved results.</p><h2 id="section-7">Send the current configuration for review</h2><p>Bring the completed record, actual product or component references, requested quantity by design and destination to <a href="https://www.packrivo.com/inquiry/">Packrivo's packaging inquiry</a>. Name the specific open decision above so the next sourcing discussion can address it. Packrivo coordinates sourcing questions; material, sample, price and delivery terms need project confirmation.</p>
```

## 八、残余项（本轮不改，需下一轮处理）

1. 448 的两张教学图位图里仍画着旧数字（`01-worksheet`：BOX-A 300/500/200、BOX-B 100/500/400；`b03-448-teaching`：two packaging designs at 500 pieces each）。本轮只把 `alt` 改成不含数字的描述，**位图未重出**；要彻底消除图内 500，需重出图并替换 URL。
2. 448 的下载文件 `b03-448-project-record.csv` 与 `packaging-moq-design-planning-worksheet.csv` 未核查内容是否含 500；本轮未下载、未改动。

## 九、验收自检（真实命令输出见 .tmp\32a-blog\accept-32ablog.log）

以下为 verify-32ablog.ps1 的真实输出（同份日志：`.tmp\32a-blog\accept-32ablog.log`）。

```
===== 32a-blog 验收 =====
时间 2026-11-05 04:31:19

--- 1. 起订量正则 grep（对 3 篇新文本，含 448 的新标题/meta）---
PASS  grep post_content 448  命中 0 次
PASS  grep post_content 508  命中 0 次
PASS  grep post_content 1621  命中 0 次
PASS  grep post_title 448  命中 0 次
PASS  grep _yoast_wpseo_title 448  命中 0 次
PASS  grep _yoast_wpseo_metadesc 448  命中 0 次
PASS  grep 合计  总命中 0

--- 2. 剩余 500 出现位置（逐条说明是否为算例）---
post 448 剩余 500 计数 = 1
   @3894  wo packaging designs. On a 250 piece basis for each, the planning total becomes 500 boxes. The resulting surplus is a buying decision that should be visible bef
post 508 剩余 500 计数 = 0
post 1621 剩余 500 计数 = 1
   @8142  nent grouping and terms confirmed for the project. If a hypothetical order were 500 identical holders and all 416 planned uses occurred, 84 would remain before 

--- 3. 红线词 grep（区分大小写，与指令 grep 同口径）---
PASS  red-line post_content 448  命中 0 次
PASS  red-line post_content 508  命中 0 次
PASS  red-line post_content 1621  命中 0 次
PASS  red-line post_title 448  命中 0 次
PASS  red-line _yoast_wpseo_title 448  命中 0 次
PASS  red-line _yoast_wpseo_metadesc 448  命中 0 次
PASS  red-line 合计  总命中 0
说明：忽略大小写的子串匹配共 10 处，全部落在 comparison / hypothetical 之类的普通单词里（例如 compar-ISO-n），不是资质或身份声明。

--- 4. CSV 精确匹配回放（old_exact 必须在库内原文中各出现恰好 1 次）---
CSV 行数 = 7
PASS  csv 第 1 行 old_exact 唯一命中 post 448/post_title  命中 1 次，长度 48
PASS  csv 第 2 行 old_exact 唯一命中 post 448/_yoast_wpseo_title  命中 1 次，长度 48
PASS  csv 第 3 行 old_exact 唯一命中 post 448/_yoast_wpseo_metadesc  命中 1 次，长度 159
PASS  csv 第 4 行 old_exact 唯一命中 post 448/post_content  命中 1 次，长度 12914
PASS  csv 第 5 行 old_exact 唯一命中 post 508/post_content  命中 1 次，长度 65
PASS  csv 第 6 行 old_exact 唯一命中 post 508/post_content  命中 1 次，长度 32
PASS  csv 第 7 行 old_exact 唯一命中 post 1621/post_content  命中 1 次，长度 58
PASS  csv 含 448 标题/meta/正文三行  448 行数 4

--- 4b. 标签结构一致性（orig vs new 逐标签计数，证明只改数字未动结构）---
PASS  结构 post 448（24 类标签计数）  全部一致
PASS  结构 post 508（24 类标签计数）  全部一致
PASS  结构 post 1621（24 类标签计数）  全部一致
--- 5. 新正文与 CSV 一致性 + 格式 ---
PASS  csv 448 post_content new_exact == new-448.html  逐字节比较
PASS  csv 448 post_title new_exact 长度  48 字符
PASS  csv 448 metadesc 长度 120-155  148 字符
PASS  md 无 BOM  首字节 35
PASS  md 无 CR  LF only
PASS  csv 有 BOM  EF BB BF
PASS  csv 行终止 CRLF  按 CRLF 切分

===== 合计 通过=32 失败=0 =====
```

结论：起订量正则命中 0；红线词（区分大小写）命中 0；CSV 全部 old_exact 在库内原文唯一命中；举例算术逐条成立。

