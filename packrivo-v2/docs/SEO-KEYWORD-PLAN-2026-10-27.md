# Packrivo 关键词 → 页面 规划 · 2026-10-27

数据来源：GSC 近 3 个月（213 个查询词，1,260 次展示，8 次点击，平均排名 33），GSC 网页报告，以及对线上 www.packrivo.com 页面 title / H1 / robots / 字数的实测。
执行对象：线上站点（父主题 + 存量页面）。子主题 packrivo-home-v4 的 P0（title 丢失）修好之前不要上线，否则本计划的 title 改动会被吞掉。

---

## 0. 规则（每个页面都要遵守）

1. **一个页面只主攻一个搜索意图。** 同义词和单复数可以在同一页出现（jewelry box / jewelry boxes / jewellery box），不同意图要分开做页面（"首饰盒尺寸"和"定制首饰盒"是两个意图）。
2. **主关键词只出现在这些位置**：title 开头、H1、正文前 100 词、一个 H2、主图 alt、meta description。其余正文写自然语句，**不要堆砌**。
3. **title 不超过 60 字符，meta description 不超过 155 字符**，必须包含主词和一个买家关心的数字（MOQ、价格、交期）。
4. **同一个意图只留一个页面。** 多余的页面做 301 或 noindex，详见第 2 节。
5. **多语言页面的 title 和 meta description 用当地人真实搜索的词重写**，不要直接机翻英文。
6. **硬规则：改任何英文 title 时，必须在同一次提交里更新所有语种字典的对应键。** 线上多语言插件（2026-10-24.1）是用英文页面的完整 `<title>` 精确匹配字典，查不到就保留英文。英文 title 一改，旧键全部失效，所有语种都会退回英文标题。（Codex 2026-10-27 核实）

---

**硬规则：英文页面的现有 title 每次变更时，必须在同一次提交中同步更新所有已支持语种字典中的对应 title 键与译文，并检查旧键及各语种实际输出；不得仅修改英文 title，否则多语言页面可能回退为英文。**

---

## 1. 关键词 → 目标页面

"现状"一栏是 2026-10-27 的线上实测；"页面排名"是 GSC 网页报告里该页面所有查询的平均排名。

### A. 首饰盒（主攻细分，优先级最高）

| # | 主关键词（+变体） | 目标页面 | 现状 | 改动 |
|---|---|---|---|---|
| A1 | **custom jewelry boxes**（jewelry boxes, jewellery boxes, jewelry boxes with logo） | `/jewelry-boxes/` | 页面排名 15.2；title 是 "Custom Jewelry Boxes: Rigid, Drawer, MOQ 500"；1,615 词，23 张图 | title 改为 `Custom Jewelry Boxes with Logo – MOQ 500 \| Packrivo`；H1 保留；正文首段加一次 "jewellery boxes"（英式拼写）；加一个价格区间表，按结构列出 500/1,000/2,500 件的价格；所有首饰相关文章用锚文本 "custom jewelry boxes" 链到这里 |
| A2 | **jewelry box dimensions**（jewelry box measurements, jewelry box sizes） | `/2026/09/21/jewelry-box-insert-fit-measurements/` | 页面排名 23；"jewelry box measurements" 这个词排 21.6 | H1 改为 `Jewelry Box Dimensions: Standard Size Chart (mm & inches)`；在首屏加尺寸表，覆盖戒指/耳环/项链/手链/手表/套装盒，同时给 mm 和英寸；加 3–5 条 FAQ（schema 用 FAQPage）；文末 CTA 链到 A1 |
| A3 | **magnetic jewelry box** | `/product-magnetic-jewelry-box/` | 页面排名 7.2（该页所有查询的平均值，可能被少数低竞争长尾词拉高；执行前在 GSC 用"网页 = 此 URL"筛选，看 "magnetic jewelry box" 本词的排名）；H1 是 "Custom Rigid Magnetic Jewelry Box Sourcing" | H1 改为 `Custom Magnetic Jewelry Box with Logo`（去掉 "Sourcing"，见第 3 节）；补实拍图或打样图，alt 写清楚；加规格表和阶梯价 |
| A4 | **jewelry box manufacturer**（jewelry box supplier） | `/jewelry-box-manufacturer/` | 676 词，3 张图 | 保留，作为"找供应商"意图的页面，和 A1 区分开；扩写到 1,200 词以上：流程、打样、交期、质检、MOQ；在正文中链到 A1 |
| A5 | **custom jewelry pouches**（jewelry pouch with logo） | `/textile-jewelry-pouch/` | 863 词；和 `/catalog-pouch-b/`（title 是 "Jewelry Pouches — Supplier Reference Catalogue"）抢同一个词 | H1 改为 `Custom Jewelry Pouches with Logo`；`/catalog-pouch-b/` 设为 noindex，或加 canonical 指向这里 |

### B. 礼盒

| # | 主关键词 | 目标页面 | 现状 | 改动 |
|---|---|---|---|---|
| B1 | **collapsible magnetic gift box** | `/product-collapsible-magnetic-gift-box/` | **两个页面重复**：`/collapsible-magnetic-gift-box/`（445 词）和 `/product-collapsible-magnetic-gift-box/`（996 词）；查询排名 51 | 保留内容更多的 product- 页，另一个 301 过来（先在 GSC 网页报告确认哪个展示更多，展示更多的那个 URL 留下）；title 改为 `Collapsible Magnetic Gift Box – Custom Logo, MOQ 500` |
| B2 | **foldable gift boxes**（folding gift box） | `/foldable-gift-boxes/` | 页面排名 11.5；**title 和 URL 不一致**：title 是 "Custom Gift Boxes & Retail Packaging"；页面同时覆盖硬盒、袋子、陈列包装 | 先决定这个页面的定位：(a) 只做折叠礼盒 → title/H1 改为 `Custom Foldable Gift Boxes – Flat-Pack, MOQ 500`，硬盒/袋子部分改为链接到各自页面；(b) 保持礼盒总分类页 → title 保持 "Custom Gift Boxes"，在页面里加一个 "Foldable gift boxes" 的 H2 小节。**两种都比现在 URL 和 title 不一致要好**，选哪个由站长决定 |
| B3 | **candle gift box**（candle box packaging） | `/candle-gift-set-box/` | 只有 474 词，太薄 | 扩写到 1,000 词以上：玻璃罐尺寸对照、内托、外套；"candle box inserts" 作为 H2 |

### C. 邮寄盒 / 内托

| # | 主关键词 | 目标页面 | 现状 | 改动 |
|---|---|---|---|---|
| C1 | **custom mailer boxes**（mailer box, printed mailer boxes） | `/mailer-boxes/` | 页面排名 39.7；**H1 是 "Shipping Boxes & Mailers"，和 title 不一致** | H1 改为 `Custom Printed Mailer Boxes`；"shipping boxes" 只在一个 H2 里出现 |
| C2 | **mailer box manufacturer** | `/mailer-box-manufacturer/` | 567 词 | 保留，和 C1 区分；扩写 |
| C3 | **custom molded pulp inserts**（molded fiber inserts, molded pulp end caps, presentation inserts） | `/molded-pulp-insert/` | 页面排名 71.2；有 10 多个长尾变体词在展示 | 这些变体属于同一个意图，**可以放在同一页**：每种用途各写一个 H2（end caps / presentation / subscription box / medical device）；链到 `/product-molded-pulp-end-support-pair/` |
| C4 | **chipboard partitions**（chipboard dividers manufacturer） | `/product-rigid-chipboard-divider/` | 页面排名 62.3 | title 改为 `Custom Chipboard Partitions & Dividers \| Packrivo` |

### D. 询价

| # | 主关键词 | 目标页面 | 现状 | 改动 |
|---|---|---|---|---|
| D1 | **custom packaging quote**（packaging quote, packaging quotation） | `/inquiry/`（报价器上线后改成 `/get-a-quote/`） | "packaging quote" 排 11 | 表单上方加 3 个真实报价示例，引用报价引擎算出来的价格；报价器上线时把 `/inquiry/` 301 到新页面，保留排名 |

### E. 小语种（竞争小，最有机会）

GSC 里已经有展示的当地词，用来改写对应语种页面的 title 和 meta description：

| 语种 | 已出现的词（排名） | 目标页面 | title 示例 |
|---|---|---|---|
| nl | sieradenbox (62), sieradendoos samenstellen (69), box pouch op maat (60) | `/nl/jewelry-boxes/` | `Sieradendoos op maat met logo – vanaf 500 stuks` |
| it | scatole per bigiotteria (65), confezionare gioielli (25.5) | `/it/jewelry-boxes/` | `Scatole per gioielli personalizzate con logo – da 500 pz` |
| fr | boite presentation bijoux (71) | `/fr/jewelry-boxes/` | `Boîtes à bijoux personnalisées avec logo – dès 500 pièces` |
| de | faltschachteln mit sichtfenster (36), Beutel-Einleger (43.7) | `/de/windowed-folding-carton/` | `Faltschachteln mit Sichtfenster – individuell ab 500 Stück` |

**先修全站问题**：多语言页面的 title 现在没有翻译。例如 `/fr/product-magnetic-jewelry-box/` 的 title 还是英文 "Magnetic Jewelry Box: US$0.27–0.98 at 500 pcs"。title 和 meta description 必须进 i18n 字典或独立字段。

---

## 2. 要合并或隐藏的页面（解决多个页面抢同一个词）

**GSC 复核结果（2026-10-27，近 3 个月，网页维度，共 374 个有展示的 URL）：**
- `/product-collapsible-magnetic-gift-box/`：11 次展示，平均排名 25.3；`/collapsible-magnetic-gift-box/`：**0 次展示**。结论：保留 product- 页，把另一个 301 过来，不需要再核实。
- `/catalog-jewelry-box/`、`/jewelry-box-manufacturer/`、全部 `catalog-*-o1cn…` 页：**0 次展示**。说明谷歌目前已经把 `/jewelry-boxes/`（23 次展示）当作首饰盒的主页面，这些页面**没有在抢展示**。所以下表中的 catalog 页处理**降为低优先级**：等图片来源追溯完成后再决定，不阻塞其他工作。
- 小语种首饰盒页面已有展示：`/nl/jewelry-boxes/` 10 次，`/it/jewelry-boxes/` 9 次，`/de/jewelry-boxes/` 5 次。这也说明多语言 title 翻译值得优先做。

| 页面 | 问题 | 处理 |
|---|---|---|
| `/catalog-jewelry-box/`（227 词） | 和 A1 抢 "jewelry boxes"，内容太薄 | 优先加 canonical 指向 A1；页面保留，并链到 A1 |
| `/catalog-gift-box-o1cn…/` 13 个、`/catalog-pouch-o1cn…/` 约 30 个 | slug 是 1688 的商品 ID，每页约 300 词，彼此高度相似；页面上的"供应商参考照片"来源和授权未核实 | **逐页核对，不要一刀切**：和某个分类页内容重复的 → canonical 指向该分类页；没有独立价值又无法合并的 → noindex。图片逐张追溯来源，没有授权记录的换掉或下线 |
| `/collapsible-magnetic-gift-box/` | 和 B1 重复 | 301 到 B1 |
| `/catalog-pouch-b/` | 和 A5 重复 | noindex，或加 canonical 指向 A5 |

## 3. 全站模板改动

1. **产品页 H1 和 title 去掉 "Sourcing"。** 现在 134 个产品页都是 "Custom X Sourcing"，但没有买家会搜 "sourcing"。改成 `Custom X with Logo`，或者 `Custom X – MOQ 500`；"我们是代采"放到正文首段说明。
2. **确认 160 个 noindex 页面是不是故意的。** 金属礼盒、地面陈列架、吸塑盒、封箱胶带这几类产品的所有语言版本（包括英文）都是 `noindex, follow`。如果这些产品在卖，改成 index；如果不卖，保持现状。
3. **文章页加 hreflang**，前提是文章有翻译版本；没有翻译就不用加。

## 3.5 内容来源要求

- 价格表：只用报价引擎（`lib/pricing.ts` → `pricing.json`）算出的数字，或者站长提供的真实报价，并标注 "Indicative"。
- 照片：现有产品主图标注为 "AI concept image"。"补实拍图"的前提是有真实打样或供应商授权的照片；没有就保留 AI 概念图并继续如实标注，**不要把概念图写成实拍**。
- 尺寸表：取自供应商规格书或行业通用尺寸，注明来源；没有来源的数字不写。
- 本计划里的 GSC 排名数字取自 2026-10-27，"近 3 个月"，Google 网页搜索，未加任何查询筛选；执行前在 GSC 里复核一遍。

## 4. 不做的事

- 不在页面里堆关键词，不在页脚放关键词列表，不写隐藏文字。
- 不再批量新发文章。先把以上约 15 个页面做好，6–8 周后看效果再决定。
- 不使用虚构的案例、评价、认证（packrivo-v2 里 FSC C168842、ISO 9001、SEDEX、四个客户案例都是虚构的，不能用）。

## 5. 验收与复查

改完后在 GSC 用「网址检查 → 请求编入索引」提交 A1–A3、B1、B2、C1、C3、D1。

| 检查项 | 怎么验 |
|---|---|
| title、H1 已更新 | `curl -sL <url> \| grep -oE "<title>[^<]*\|<h1[^>]*>[^<]*"` |
| 301 生效 | `curl -sI <旧url>` 返回 301，Location 指向目标页 |
| noindex 生效 | 页面里有 `<meta name="robots" content="noindex` |
| 多语言 title 已翻译 | 抽查 nl/it/fr/de 各 3 个页面 |

**4 周后和 8 周后**在 GSC「效果」里按页面筛选，对比 A1–A3、B1、B2、C1 的展示量和平均排名。对比时以"该页面主关键词本词的排名"为准，不用页面平均排名。目标：A3、B2 的主关键词进入前 20；A1 的主关键词进入前 30。
