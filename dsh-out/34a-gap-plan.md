# 34a 第 2 段 · 关键词缺口与铺设计划


> 交付形态：只出本 MD。本轮不写站、不改词典、不发布。所有搜索量为 Semrush KMT 宽泛匹配实测值，标注 `Semrush KMT`；`-` 表示该词在本账号下没有拉到数据，一律留空不作估算。

## 0. 数据基线与方法

| 项 | 实测 | 来源 |
|---|---|---|
| 站内页面 | 141 | `page-sitemap.xml` 359 条 `<loc>` − 208 条 `/catalog-*` − `/privacy/` − `/terms/`，去重 8 条重复 `<loc>` |
| 已抓页面元数据 | 141/141 | `dsh-out/34a-raw/pages/pages.csv`（status 200） |
| 博客文章 | 356 | WP REST `/wp-json/wp/v2/posts`，`dsh-out/34a-raw/wp-posts.jsonl` |
| GSC 3 个月页面数据 | 73 条有数据页面，其中 33 条为文章路径 | `dsh-out/34a-raw/gsc-browser/gsc-pages-3m.txt` |
| 词库 | 6,101 行，其中 588 行带真实搜索量 | `dsh-out/34a-keyword-universe.csv` |

**偏差 1（必须记录）**：任务书写「现有博客约 344 篇」，实测 356 篇；站内没有 post sitemap，只能走 REST 分页（100+100+100+56，第 5 页返回 HTTP 400 即末页）。本段所有「去重」都对着这 356 篇做。

**偏差 2**：356 篇里只有 33 篇在 GSC 有数据。所以「跳过已有排名」这条对 ~323 篇是自动成立的，真正需要判断的是这 33 篇。GSC 曝光/点击不当搜索量用。

**偏差 3**：主词只取真实 Semrush KMT 数据行。KMT 是宽泛匹配、按搜索量取前 100 的导出，所以「珠宝盒」这一簇的 total 被 `jewelry box` 60,500 拉高，本段只用其 B2B 子集，不用簇总量做判断。

## 1. 需要新建的落地页（8 个）

判据：该主词有真实搜索量、商业意图、且站内现有 141 个页面里没有任何一个能用 H1 自然接住它。「接不住」指的是现页 H1/盒型已经被另一种结构或另一种场景锁死，硬塞会造成两页抢同一主词。

| # | 建议 URL | 簇 | 主关键词 | 搜索量 | KD | 意图 | 现最接近的页 | 为什么现页接不住 |
|---|---|---|---|---|---|---|---|---|
| 1 | `/custom-candle-boxes/` | candle-box | **custom candle boxes** | 1,000（Semrush KMT） | 21 | I/T | `/candle-gift-set-box/` | 现页是「蜡烛礼盒套装」（杯+配件+盒），H1 与正文按 gift set 写；蜡烛单品盒（pillar/tin/votive/tealight 的直径-高度档、贴标位、纸托）无处承接。同簇 `personalized candle box` 260 / `candle packaging boxes wholesale` 260 / `custom candle packaging` 210 也全无归属。 |
| 2 | `/custom-rigid-boxes/` | rigid-box | **custom rigid boxes** | 1,300（Semrush KMT） | 31 | C | `/product-book-style-rigid-presentation-box/` | 90 个 `/product-*` 是单盒型长尾页，刚性盒总类（书型/天地盖/抽屉/磁扣的选型矩阵）没有入口。`rigid boxes` 1,600 KD33 也落在单页上。这是本簇第二大词，浪费不起。 |
| 3 | `/custom-paper-bags/` | paper-bag | **custom paper bags** | 1,900（Semrush KMT） | 28 | C | `/bags-pouches/` | 现页把纸袋、无纺布袋、织物袋混在一页，H1 是 bags & pouches；纸袋专属参数（克重/绳型/覆膜/侧宽）与 `paper bags with logo` 590 KD23、`custom printed paper bags` 720 KD24 都没有专属落点。 |
| 4 | `/pr-boxes/` | pr-box | **pr box** | 880（Semrush KMT） | 7 | C/I | `/mailer-boxes/` | PR box 是 influencer 投放场景（预算线、开箱序列、寄送名单、复用包装），mailer box 页按运输结构写。同簇 `how to get pr boxes` 210、`what are pr boxes` 170、`pr boxes for influencers` 140 是纯信息词，站内 0 篇承接。 |
| 5 | `/custom-folding-cartons/` | folding-carton | **custom folding cartons** | 480（Semrush KMT） | 27 | C | `/product-folding-carton/` | 现页是单盒型页（straight tuck 结构、单一尺寸档），`folding cartons` 880 KD29 与 `folding carton packaging` 480 KD31 是品类词。品类词喂单盒型页，后续每加一个盒型都要再抢一次。 |
| 6 | `/packaging-for-small-business/` | packaging-supply-general | **custom packaging for small business** | 1,000（Semrush KMT） | 44 | C | `/products/` | `/products/` 是目录页，没有场景词。小批量入口（250 起、按 SKU 分档、先打样后放量）是独立落地角度，否则 `custom packaging` 6,600 与小批量需求全挤目录页。 |
| 7 | `/custom-display-boxes/` | display-box | **custom display packaging boxes** | 110（Semrush KMT） | 31 | C | `/product-shelf-counter-display/` | 现页只覆盖货架/柜台一种形态，`display boxes for products` 170 KD23 覆盖 PDQ/端架/落地架/展示台，需一页做形态选择。量小但意图明确，且能补 display 簇的唯一缺口。 |
| 8 | `/custom-cosmetic-boxes/` | cosmetic-box | **custom cosmetic boxes** | 480（Semrush KMT） | 22 | C | `/eco-kraft-cosmetic-box/` | 现页被 kraft 环保材质锁死，`custom serum boxes` 480 KD10、`custom lipstick boxes` 260 KD1、`custom lip gloss boxes` 210、`custom skincare boxes` 170 等细分全无归属；`custom cosmetic packaging` 590 KD26 需要一层总类页。 |

**每页固定结构（8 页同一套，避免每页重新设计）**

1. H1 = 主关键词原形（不加 best/custom 之外的修饰）；title ≤60，meta 120–155。
2. H2 四段：盒型与规格怎么选 → 材质与印后 → MOQ 250 与打样 5–10 天 → 报价口径（含 dieline）与交期 7–30 天。
3. 内链：2 个最接近的现有盒型页 + `/get-a-quote/` + `/samples/`；从 `/products/` 与对应品类页各回链一次。
4. 不写厂（写 sourcing team / supplier），不写 FSC/ISO/ISTA/客户数/出货量。

**不新建的（已有归属，勿重复建页）**：`magnetic closure gift boxes` 1,900 → `/magnetic-book-style-box/`；`custom tissue paper` 2,900 → `/printed-tissue-paper/`；`foil stamping` 1,600 → 印后内容；`custom kraft paper bags` 260、`printed mailer boxes` 210、`custom subscription boxes` 720 均已有专属页。

## 2. 需要新建的博客选题（34 条）

**去重方法（可复现）**：把拟写标题与现有 356 篇标题都做去停用词 token 集合，算 Jaccard 相似度；同时检查主关键词是否已整串出现在任一现有标题里。两条任一触发即判重复、换词。实测结果：34 条全部 Jaccard < 0.40（最高 0.33，Confirm Barcode Data Before Sending the Artwork），主词整串命中 1 条（`dieline` 命中现有 `/2026/10/01/packaging-dieline-change-control/` 的标题；该 URL 在 GSC 3 个月里无曝光、也无 dieline 查询，故不冻结它，改由第 1 行差异列写死两篇分工：现文讲变更审批、新文讲四字段定义）。

**本轮按复核意见 G1/G2 的调整**：删掉与落地页抢词或彼此过近的 8 条——原 #7 `custom tissue paper with logo` 880（撞 `/printed-tissue-paper/`）、#16 `custom rigid boxes wholesale` 170（撞 `/custom-rigid-boxes/`）、#20 `paper bags with logo` 590（撞 `/custom-paper-bags/`）、#22 `cheapest custom mailer boxes` 720、#24 `custom mailer` 480（均撞 `/mailer-boxes/`）、#30 `custom serum boxes` 480、#31 `custom lipstick boxes` 260、#32 `custom lip balm boxes` 210（均撞新建 `/custom-cosmetic-boxes/`）；原 #2/#3/#5 三个 dieline 词义辨析篇并入第 1 行，主词换成 `dieline` 3,600，`dieline packaging` 390 独立成第 2 行做交付清单。另按 G2 回填 5 条库内有量词（`custom presentation boxes` 260、`how to get pr boxes` 210、`drawer box with handle` 140、`kitting mailers` 140、`gold foil stamping` 210）。净结果 34 条：宁少勿滥，不凑 40。

另加约束：这 34 条的主词必须是词库里带真实搜索量的行，且避开纯消费型头部词——`jewelry box` 60,500 这类不新开文章（它已在 §4 作为现有文章的标题主词使用），而由已有文章提位置；新文章只吃 B2B 与信息型长尾。

| # | 拟写标题 | 主关键词 | 搜索量 | KD | 簇 | 意图 | 内链目标 | 与现有文章的差异 |
|---|---|---|---|---|---|---|---|---|
| 1 | Dieline: The Four Fields to Confirm Before Artwork | **dieline** | 3,600（Semrush KMT） | 36 | dieline-dimensions | I | `/product-folding-carton/` | 合并原 #1/#2/#3/#5 四个词义辨析选题为一篇：只讲 cut / crease / bleed / dimension 四字段与报价附件；`what is a dieline` 210、`dieline meaning` 210、`what are dielines` 140、`packaging and dielines` 170、`what is a dieline in packaging` 140 全部收在这一篇里，不再单开 4 篇薄文。与现有 `/2026/10/01/packaging-dieline-change-control/` 分工：那篇只讲变更审批与版本影响（GSC 该 URL 无曝光、无 dieline 查询），这篇只讲四字段定义，两文互链，不共用主词 |
| 2 | Dieline Packaging Handoff: What to Send | **dieline packaging** | 390（Semrush KMT） | 24 | dieline-dimensions | I | `/get-a-quote/` | 交付物角度：发给供应商的文件清单 |
| 3 | Branded Tissue Paper: Logo Size on a Sheet | **branded tissue paper** | 480（Semrush KMT） | 28 | tissue-paper | C | `/printed-tissue-paper/` | 落点是 logo 在薄纸上的最小可印尺寸，不是选纸 |
| 4 | Tissue Paper Printing: Ink on a Thin Sheet | **tissue paper printing** | 260（Semrush KMT） | 25 | tissue-paper | C/I | `/product-printed-tissue-sheet/` | 工艺向：薄纸的吸墨与透背 |
| 5 | Personalized Tissue Paper: Name or Logo | **personalized tissue paper** | 320（Semrush KMT） | 19 | tissue-paper | C | `/printed-tissue-paper/` | 落点是个人名 vs 品牌标的取舍与排版位 |
| 6 | Magnetic Box Closure: Structure and Fit Choices | **magnetic box** | 1,000（Semrush KMT） | 12 | magnetic-box | C | `/magnetic-book-style-box/` | 只谈盒盖结构（翻盖 / 书本式 / 磁吸抽屉）与内衬贴合；不写磁铁等级或拉力实测 |
| 7 | Magnetic Boxes for Gifts: Size by Content | **magnetic boxes for gifts** | 140（Semrush KMT） | 3 | magnetic-box | C | `/magnetic-book-style-box/` | 按内容物反推盒尺寸序列 |
| 8 | Custom Apparel Boxes: Folding or Rigid First | **custom apparel boxes** | 320（Semrush KMT） | 26 | apparel-box | C | `/custom-rigid-boxes/` | 服饰盒整簇只有 2 个词、站内 0 篇，先把形态选择讲清 |
| 9 | Rigid Boxes With Lids: Depth and Wall Thickness | **rigid boxes with lids** | 90（Semrush KMT） | 10 | rigid-box | C | `/custom-rigid-boxes/` | 盖深与壁厚的换算，接新落地页 |
| 10 | Rigid Box Packaging Supplier: What to Ask | **rigid box packaging supplier** | 320（Semrush KMT） | 25 | rigid-box | C/I | `/custom-rigid-boxes/` | 提问清单式，与现有「一供 vs 多供」那篇互补 |
| 11 | Custom Luxury Rigid Boxes: Wrap and Corner | **custom luxury rigid boxes** | 140（Semrush KMT） | 33 | rigid-box | C | `/product-book-style-rigid-presentation-box/` | 只谈包角与裹纸，与现有裹缝文分工 |
| 12 | Custom Retail Shopping Bags: Handle and Gusset | **custom retail shopping bags** | 390（Semrush KMT） | 33 | paper-bag | C | `/custom-paper-bags/` | 提手形式与风琴位选择；不写承重实测数字 |
| 13 | Personalized Paper Bags: Where the Name Goes | **personalized paper bags** | 320（Semrush KMT） | 19 | paper-bag | C | `/custom-paper-bags/` | 名字位与版面分区 |
| 14 | Custom Printed Gift Bags: Ribbon or Flat Handle | **custom printed gift bags** | 390（Semrush KMT） | 18 | paper-bag | C | `/bags-pouches/` | 提手二选一的承载与成本差 |
| 15 | Personalized Shipping Boxes: Print Area Reality | **personalized shipping boxes** | 1,000（Semrush KMT） | 32 | mailer-box | C | `/mailer-boxes/` | 运输箱可印区被胶带/面单吃掉的实际面积 |
| 16 | Custom Delivery Boxes: The Return Leg | **custom delivery boxes** | 480（Semrush KMT） | 33 | mailer-box | C | `/mailer-boxes/` | 退货环节对盒型的要求，现站无退货向文章 |
| 17 | Corrugated Packaging Boxes: Flute and Stack | **corrugated packaging boxes** | 2,900（Semrush KMT） | 33 | corrugated-carton | C | `/product-regular-slotted-shipping-carton/` | 楞型与堆码的关系，区别于现有抗压文 |
| 18 | Corrugated Carton Box: Board Grade Choice | **corrugated carton box** | 2,400（Semrush KMT） | 33 | corrugated-carton | C/I | `/product-regular-slotted-shipping-carton/` | 纸板等级选择表 |
| 19 | Custom Cardboard Boxes: When Kraft Beats Rigid | **custom cardboard boxes** | 1,600（Semrush KMT） | 37 | paperboard-stock | C | `/product-folding-carton/` | 卡纸 vs 刚性盒的分界线 |
| 20 | Custom Packaging Inserts: Cavity or Folded | **custom packaging inserts** | 260（Semrush KMT） | 18 | insert | C | `/inserts/` | 挖腔与折纸两种内衬的分工 |
| 21 | Custom Inserts for Packaging: What to Send a Supplier | **custom inserts for packaging** | 110（Semrush KMT） | 22 | insert | C | `/molded-pulp-insert/` | 内衬需求清单（件数 / 净空 / 取件方式 / 表面）；不写公差预算数值 |
| 22 | Custom Skincare Boxes: Set or Single Unit | **custom skincare boxes** | 170（Semrush KMT） | 15 | cosmetic-box | C/I | `/eco-kraft-cosmetic-box/` | 套装盒 vs 单品盒的装法 |
| 23 | Custom Printed Candle Boxes: Label and Window | **custom printed candle boxes** | 170（Semrush KMT） | 0 | candle-box | C | `/custom-candle-boxes/` | 贴标位与开窗的冲突，接新落地页 |
| 24 | Custom Boxes for Candles: Tin or Pillar | **custom boxes for candles** | 110（Semrush KMT） | 21 | candle-box | I | `/custom-candle-boxes/` | 罐装与柱蜡的盒型差 |
| 25 | Personalized Candle Box: Gift Pair Layout | **personalized candle box** | 260（Semrush KMT） | 27 | candle-box | I/T | `/custom-candle-boxes/` | 双支礼盒的排布 |
| 26 | Suede Pouch: Lining and Drawstring Choice | **suede pouch** | 140（Semrush KMT） | 25 | jewelry-pouch | I | `/textile-jewelry-pouch/` | 绒面内里与抽绳结构；与现有勾丝文分工在材料本体而非外挂件 |
| 27 | Jewelry Pouches Wholesale: MOQ and Size Range | **jewelry pouches wholesale** | 110（Semrush KMT） | 14 | jewelry-pouch | C | `/textile-jewelry-pouch/` | 只谈批量档与尺寸档；与现有「盒还是袋」选型文互补 |
| 28 | Gable Boxes: Handle and Window Choice | **gable boxes** | 1,900（Semrush KMT） | 27 | gable-box | I | `/product-gable-top-carry-carton/` | 手提孔与开窗组合，1,900 搜索量站内 0 篇 |
| 29 | What Are PR Boxes? Campaign Budget Lines | **what are pr boxes** | 170（Semrush KMT） | 12 | pr-box | I | `/pr-boxes/` | 信息词，喂新 PR 落地页 |
| 30 | Custom Presentation Boxes: Lid Depth and Interior | **custom presentation boxes** | 260（Semrush KMT） | 27 | gift-box | C | `/custom-rigid-boxes/` | 盖深与内衬的配合；落地页讲选型与报价，这篇只讲一个盒子的内部尺寸怎么定 |
| 31 | How to Get PR Boxes: Brief, Sample, Run | **how to get pr boxes** | 210（Semrush KMT） | 8 | pr-box | I | `/pr-boxes/` | 获取路径（询价 → 打样 → 批量）；与第 33 行「什么是 PR 盒」分工在流程 vs 定义 |
| 32 | Drawer Box With Handle: Pull and Clearance | **drawer box with handle** | 140（Semrush KMT） | 6 | rigid-box | C | `/drawer-box-with-ribbon/` | 拉手形式与抽拉间隙；与抽屉盒页（丝带款）分工在拉手形式，不重复落地页主词 |
| 33 | Kitting Mailers: Multi-Item Packing Sequence | **kitting mailers** | 140（Semrush KMT） | 0 | mailer-box | C/I | `/mailer-boxes/` | 多件装配顺序与空隙分配；现有 mailer 文讲选型，这篇讲装盒顺序 |
| 34 | Gold Foil Stamping: Area, Stock and Registration | **gold foil stamping** | 210（Semrush KMT） | 19 | print-finish | I | `/2026/09/21/foil-embossing-spot-uv-packaging/` | 金属箔与承印物、烫印面积与套位；与现有烫压/局部 UV 文分工在金属箔本身 |

### 2.1 信息型词归位（G2：不新建页，只改指路）

复核点名的低量信息词，站内已有文章能吃下的，一律不新建文章，只把词写进对应文章的次词/H2；词库外的词不硬造主词（避免再造库外主词，与 M4 冲突）。全部量/KD 来自 Semrush（KMT 标 KMT，Organic 标 Organic，无数据留空）。

| 信息词 | 量 / KD | 归属（现站已有文章） | 处理 |
|---|---|---|---|
| `foil stamping` | 1,600 / 9（Semrush KMT） | `/2026/09/21/foil-embossing-spot-uv-packaging/` | 该文已在映射表内（inmap=Y）；把 `foil stamping` 写进它的次词，不新建页 |
| `box dimensions` | 2,900 / 25（Semrush Organic） | `/2026/10/01/dimensions-acceptance-method/` | 归位为次词；KMT 无此词，标注数据源为 Organic |
| `how to measure a box for shipping` | 1,300 / 8（Semrush Organic） | `/2026/10/01/dimensions-acceptance-method/` | 同上，作为该文 H2 与次词 |
| `jewelry box measurements` | 无 KMT 数据 | `/2026/09/21/jewelry-box-insert-fit-measurements/` | **标题不动**，只加次词（该文 GSC 有排名，标题冻结） |
| `dieline` | 3,600 / 36（Semrush KMT） | §2 第 1 行新文 | 吸收 `what is a dieline` 210/10、`dieline meaning` 210/28、`what are dielines` 140/9、`packaging and dielines` 170/19、`what is a dieline in packaging` 140/16 |
| `dieline packaging` | 390 / 24（Semrush KMT） | §2 第 2 行新文 | 只做交付清单（发给供应商的文件），与第 1 行词义辨析分工 |
| `cmyk vs rgb`（`rgb and cmyk` 2,400/13、`color cmyk rgb` 590/47，Semrush Organic） | 2,400 / 13（Organic） | `/2026/10/12/rgb-colour-printing-gamut/` | 词库无此词，不造主词；写进该文次词与首段 |
| `what is a pr package` | 1,300 / 18（Semrush KMT，词库外） | `/pr-boxes/` | 不新建文章；只作 `/pr-boxes/` 的 H2 与次词，避免再造库外主词 |
| `discreet packaging` | 1,900 / 23（词库内有行、无量）；`stealth packaging` 170 / 18 | — | **跳过**：无匹配 I 簇、无可核验表述可写，不为凑词建页 |

## 3. 现有博客锚文本修复（60 条）

来源：356 篇正文里带链接的 `<p>/<li>/<br>` 块，用产品名词表（`PH`，50 条短语）匹配句中短语，同一篇最多 2 条、同一目标页最多 6 条，过滤样板句（`Download the` / `See the` / `Related` 等）。命中 60 条，覆盖 58 篇文章、21 个目标页。每行 = 在原句里把**加粗**的那段改成锚文本（若该处尚未成链）或把已有链接的文字改成锚文本（若已是链接但文字是 `this guide` 之类的空锚）。

**同一目标页的锚文本互不重复**：`锚文本` 列是替换进加粗处的文字。首次用原短语，重复时用同一名词的限定形式（`jewelry box` → `empty jewelry box` / `jewelry box structure`，`gift bag` → `a gift bag`），必要时补 1 个词让句子通顺，原句结构不动；不做 6 篇同一锚文本的精确匹配堆叠。

| # | 文章 URL | 原句（加粗处为待加/待改锚文本） | 锚文本 | 目标 URL |
|---|---|---|---|---|
| 1 | `/2026/09/28/tissue-gift-bag-set-checklist/` | Specify a **gift bag** and tissue as two line items, even when they will look like one set. Measure the boxed product first, then set the bag’s usable opening, gusset and handle direction. Give tissue its own sheet size, fold and print brief. Ask whether one supplier can coordinate the components; do not assume a bundled offer. | `gift bag` | `/bags-pouches/` |
| 2 | `/2026/10/01/packaging-color-approval/` | When a package has several surfaces, name each one. A lid wrap, tissue sheet, insert and **paper bag** may need different approval decisions. Decide whether the aim is close visual coordination or a distinct contrast, then record that intent. This gives the reviewer something more useful than “all parts must match.” | `paper bag` | `/bags-pouches/` |
| 3 | `/2026/10/05/carrier-handle-retailer/` | Use structure-specific checks during handoff For a **paper bag**, locate its usable mouth, base and attached handle anchors. For a gable or integral-handle carton, identify the formed head/handle panels and base setup. For a box-bottom tote, locate the boxed seams, lining and handle attachment relative to the gift. | `a paper bag` | `/bags-pouches/` |
| 4 | `/2026/10/05/gift-bag-loading-corner-clearance/` | Send the mouth question with the contents state The existing **gift bag** and tissue method handles the broader static contents and carrier plan. Compare the box-bottom fabric tote through its formed base and handle fields when that structure is proposed. | `a gift bag` | `/bags-pouches/` |
| 5 | `/2026/10/07/gift-ready-ecommerce-packaging/` | If a tissue-and-bag arrangement is part of the service, use the Packrivo tissue and **gift bag** specification guide to develop the component details. Then decide whether the bag is part of the presentation on arrival or is supplied flat for a later handover. | `gift bag specification` | `/bags-pouches/` |
| 6 | `/2026/10/12/gift-bag-load-position-balance/` | A gift can fit inside a **paper bag** yet produce a different hanging arrangement when it is placed away from the centre. Fitting through the mouth and balancing while suspended are different questions. | `paper bag specification` | `/bags-pouches/` |
| 7 | `/2026/09/28/jewelry-pouch-specifications/` | Try the actual loading motion in a sample. If a chain catches on the seam, a larger body may not solve the problem; the opening or item orientation may need to change. If the item slides around, a card or **box insert** may be more useful than simply shrinking the pouch. | `box insert` | `/inserts/` |
| 8 | `/2026/10/30/cufflink-gift-box-pair-backings-set/` | Use the jewelry packaging measurement guide to distinguish the product envelope, proposed support and finished usable interior. Those measurements support the choice; the source photograph of an empty **jewelry box** in this article demonstrates only an enclosure direction. It is not an approved cufflink holder. | `jewelry box` | `/jewelry-boxes/` |
| 9 | `/2026/10/30/earring-packaging-retail-display-gift-orders/` | The **jewelry box** structure comparison addresses opening relationships. Select that structure after deciding whether the purchase is primarily a display item, a gift enclosure or a combination. | `empty jewelry box` | `/jewelry-boxes/` |
| 10 | `/2026/10/31/brooch-packaging-mounting-method/` | An attractive empty **jewelry box** leaves this decision unresolved. The decorative face may fit while the clasp sits against the base, or a mounted brooch may need rear access that disappears once its pad is installed. Establish the mounting route before selecting the enclosure by its exterior alone. | `jewelry box structure` | `/jewelry-boxes/` |
| 11 | `/2026/11/04/cotton-filled-jewelry-boxes-filling-specification/` | Separate the trade name from the supplied fiber A cotton-filled **jewelry box** listing does not, by itself, establish that its insert is pure cotton. Read the named product’s material declaration and ask the proposed supplier to identify the exact filling it would supply. | `jewelry box specification` | `/jewelry-boxes/` |
| 12 | `/2026/11/04/jewelry-polishing-cloth-packaging-formats/` | Where the accessory joins a **jewelry box**, add it to the identified complete arrangement rather than checking an empty cavity. The jewelry insert measurement guide helps describe the product arrangement. The cloth packet is an additional component with its own entry, resting and removal questions. | `jewelry box options` | `/jewelry-boxes/` |
| 13 | `/2026/11/04/led-ring-boxes-lighting-holder-choice/` | Use the complete ring, including its setting and any projecting details. A tall setting can occupy a different position relative to a lid-mounted source than a low band. The **jewelry box** measurement guide explains why the packed product and finished interior need separate references. | `custom jewelry box` | `/jewelry-boxes/` |
| 14 | `/2026/09/21/folding-gift-box-specifications/` | A folding carton and a collapsible magnetic gift box are useful directions to compare. Keep their drawings and forming steps distinct. A **magnetic closure** doesn't establish that every wall collapses or that the box can be delivered in the desired state. | `magnetic closure` | `/magnetic-book-style-box/` |
| 15 | `/2026/10/05/collapsible-box-first-assembly/` | If a collapsible magnetic box is under consideration, keep its particular forming and closure details in the trial. A **magnetic closure** does not describe every other assembly operation. | `magnetic closure box` | `/magnetic-book-style-box/` |
| 16 | `/2026/10/07/accessible-gift-packaging-limited-dexterity/` | Compare actions rather than box names Calling a design “drawer,” “magnetic” or “lift-off” says little about its actual opening demands. A drawer may be difficult to restrain while pulling. A lift-off lid may offer too little edge to hold. A **magnetic closure** can feel different depending on the complete construction. | `magnetic closure option` | `/magnetic-book-style-box/` |
| 17 | `/2026/09/28/mailer-box-with-insert-fit-brief/` | A **mailer box** with insert needs one brief for the complete packed arrangement. Record the product, accessories, support and card positions before asking for box and insert dimensions. Check the assembled sample for loading, closure and removal, while keeping any transport-protection requirement separate. | `mailer box` | `/mailer-boxes/` |
| 18 | `/2026/10/11/cushion-curve-static-loading-bearing-area/` | Packrivo’s guide to **mailer box** protection addresses the separate task of reviewing the assembled shipping package. The packaging insert materials guide explains why fit and material form must be defined rather than inferred from a material name. | `mailer box specification` | `/mailer-boxes/` |
| 19 | `/2026/11/01/pillar-candle-packaging-bands-wraps-cartons/` | The parcel used to deliver an order has a separate job. Use the **candle box**, insert and outer-pack brief when discussing the complete shipment. Neither the carton silhouette nor the presence of a wrap proves shipping performance. | `candle box` | `/candle-gift-set-box/` |
| 20 | `/2026/09/28/candle-packaging-box-insert-outer-pack/` | Describe surfaces that need care: a **printed label**, coated glass, a lid finish or another feature identified by the product owner. Ask for the proposed contact material and sample method. Do not assume that a soft-looking surface has established compatibility. | `printed label` | `/labels/` |
| 21 | `/2026/10/05/master-carton-label-reconciliation/` | Identify what the **printed label** is counting The official GS1 logistic-label guideline distinguishes logistic-unit identity and applicable contents/count fields. Use that context only where the actual identifiers apply. This original reconciliation record does not create a GS1 code or imply a retailer-compliance result. | `printed label specification` | `/labels/` |
| 22 | `/2026/10/10/fsc-packaging-label-permission/` | Separate the **packaging label** from its promotion FSC’s trademark-use guidance describes on-product labeling by certificate holders and promotional use by organizations with a promotional licence. It does not give every purchaser unrestricted permission to reproduce the marks. | `packaging label` | `/labels/` |
| 23 | `/2026/10/12/nfc-tags-metal-foil-packaging/` | That experiment demonstrates a real detuning mechanism. It does not measure a **packaging label**, predict the behaviour of thin decorative foil or establish the reading distance of a passive tag inside a gift box. Its particular circuit adjustments are not instructions for a packaging buyer. | `packaging label format` | `/labels/` |
| 24 | `/2026/10/12/care-card-paper-opacity-show-through/` | For an Informational **insert card**, send both printed faces, the proposed paper grade and the actual backing used during the review. The product concept does not establish opacity or remove the need to compare the finished two-sided proof. | `insert card` | `/paper-insert-card/` |
| 25 | `/2026/10/15/buyer-supplied-component-cost/` | If the buyer-supplied part is an Informational **insert card**, identify its finished version, available quantity and receiving condition before comparing assembly offers. Buying fewer new cards does not remove transport, receiving or matching work. | `printed insert card` | `/paper-insert-card/` |
| 26 | `/2026/10/15/consolidated-print-run-inventory/` | When consolidating an Informational **insert card** print run, identify the artwork version and dates through which that wording remains usable. Compare repeated setup with early stock commitment; a common card size does not make different artwork one production run. | `insert card specification` | `/paper-insert-card/` |
| 27 | `/2026/10/15/local-personalisation-print-workload/` | For a personalised Informational **insert card**, specify the common stock, final printed area and physical matching step independently of the name data. The product concept does not establish printer compatibility, an approved data-transfer route or a personalisation service. | `insert card options` | `/paper-insert-card/` |
| 28 | `/2026/10/05/shipping-label-seam-map/` | For the returnable **poly mailer** direction, also retain the future return strip and label arrangement. The original outbound label placement should not hide the separate opening/return action. Review both planning states without claiming that one diagram proves repeated journey performance. | `poly mailer` | `/poly-mailer-bag/` |
| 29 | `/2026/10/05/neck-display-height/` | Set the product’s displayed position before confirming the neck in a **rigid box**. Record the base, holder, product and neck as a height stack, then review the lid in the closed state. A taller visible neck doesn’t by itself define where the product sits or how much of it the customer sees. | `rigid box` | `/product-book-style-rigid-presentation-box/` |
| 30 | `/2026/10/05/rigid-wrap-seam-placement/` | A **rigid box** can be turned over while being opened, or held at an angle that exposes a side seam. A front rendering is therefore only one view in the placement decision. Record the others before the wrapping layout becomes fixed. | `a rigid box` | `/product-book-style-rigid-presentation-box/` |
| 31 | `/2026/10/07/seasonal-packaging-sleeves/` | Think about the visual result as a pair. A slim paper sleeve against a substantial **rigid box** may look deliberate or mismatched depending on the design. A wide sleeve might hide the part of the base box that makes it recognizable. Use an assembled concept with the real proportions to judge the combination. | `rigid box structure` | `/product-book-style-rigid-presentation-box/` |
| 32 | `/2026/10/20/paper-book-cloth-box-covers/` | Paper and book cloth describe different constructions In a paper-covered **rigid box**, the cover sheet wraps around a separate structural board. The cover’s finish might be smooth, embossed or coated. An embossed textile pattern remains a paper surface; its appearance alone doesn’t make it woven cloth. | `rigid box specification` | `/product-book-style-rigid-presentation-box/` |
| 33 | `/2026/10/26/jewelry-pouch-box-customer-handoff/` | The brand must decide whether the jewelry is received loose, attached to an agreed display card, or accompanied by another defined support. A card may still serve product identity or presentation even when there is no **rigid box**. | `rigid box packaging` | `/product-book-style-rigid-presentation-box/` |
| 34 | `/2026/11/04/jewelry-polishing-cloth-packaging-formats/` | The Kingswood Company’s polishing-cloth range gives a concrete manufacturer example: its smaller cloth is supplied in a clear envelope and its larger cloth in an acrylic box. This illustrates how a cloth range can use different packs. It does not establish a size threshold at which other brands must move from paper to a **rigid box**. | `rigid box style` | `/product-book-style-rigid-presentation-box/` |
| 35 | `/2026/10/12/corrugated-board-bending-direction/` | Rotating a **corrugated divider** on the cutting sheet can change its bending response even when the board grade, thickness and weight stay the same. The important relationship is between the board’s structure and the direction in which the finished piece spans, bends and carries load. | `corrugated divider` | `/product-corrugated-divider/` |
| 36 | `/2026/10/12/packaging-window-transmission-haze-clarity/` | Use the Windowed **Folding Carton** to identify the viewing opening, patch and product position that the sample must represent. The concept does not establish light transmission, haze or visibility of the detail required by your buyer. | `folding carton` | `/product-folding-carton/` |
| 37 | `/2026/11/01/wax-melt-packaging-product-pack-gift-box/` | This distinction changes what you're ordering. A clear formed pack, a reusable production mold and a printed **folding carton** can all appear in a wax melt project. Give each a separate name, then ask which parts the seller actually supplies. | `folding carton structure` | `/product-folding-carton/` |
| 38 | `/2026/10/05/belly-band-retained-product-identity/` | Map which product identity, care and reference information remains when a **belly band** is removed. Assign each required supplied field to a retained product, card or box surface and review that removal state. A removable band can introduce the gift, while the customer still needs an identifiable item after the band is discarded. | `belly band` | `/product-paper-belly-band/` |
| 39 | `/2026/10/01/packaging-color-approval/` | When a package has several surfaces, name each one. A lid wrap, **tissue sheet**, insert and paper bag may need different approval decisions. Decide whether the aim is close visual coordination or a distinct contrast, then record that intent. This gives the reviewer something more useful than “all parts must match.” | `tissue sheet` | `/product-printed-tissue-sheet/` |
| 40 | `/2026/10/07/how-to-personalize-gift-packaging/` | Identify Included Gift Accessories Before Disposal Give every included object a confirmed function Consider a hypothetical jewelry gift containing a cleaning cloth approved by the product owner, a paper display holder and a protective **tissue sheet**. The cloth’s intended use comes from that approval, not from the fact it is fabric. | `printed tissue sheet` | `/product-printed-tissue-sheet/` |
| 41 | `/2026/10/11/packaging-colour-metamerism-lighting/` | Different Light Can Break an Apparent Colour Match A box wrap and a **tissue sheet** can produce similar perceived colours under one light even though their underlying spectral properties differ. When the illumination changes, those differences may become visible. | `tissue sheet specification` | `/product-printed-tissue-sheet/` |
| 42 | `/2026/10/05/label-roll-receipt-inspection/` | Preserve the **roll label** and any actual splice information supplied with it. A matching artwork name alone does not establish that the liner, adhesive or web arrangement is the expected proposal. Ask the converter to identify those components when the receipt record leaves them unclear. | `roll label` | `/product-rectangle-roll-labels/` |
| 43 | `/2026/10/11/pallet-column-vs-interlocking-carton-stacking/` | This alignment matters because a corrugated **shipping carton** is not a solid block. Its erected walls, corners, panels and closures form a structure that responds to the loads applied to it. | `shipping carton` | `/product-regular-slotted-shipping-carton/` |
| 44 | `/2026/10/12/case-gtin-vs-logistic-unit-sscc/` | It does not follow that every parcel, decorative gift box or courier **shipping carton** must receive an SSCC. The requirement described here belongs to the applicable GS1 logistic-identification process, not to all packaging in commerce. | `shipping carton specification` | `/product-regular-slotted-shipping-carton/` |
| 45 | `/2026/10/12/cushion-shock-vibration-response/` | Match the evidence to the assembled configuration The public scope of ASTM D3580-22 concerns vibration evaluation of unpackaged products. That is a different specimen boundary from a complete jar, support, retail box and **shipping carton**. The public scope isn't the full paid procedure or a package-specific acceptance requirement. | `shipping carton options` | `/product-regular-slotted-shipping-carton/` |
| 46 | `/2026/10/12/inflatable-packaging-pressure-temperature/` | When a trapped gas warms, its absolute pressure increases if its volume is fixed. If its absolute pressure stays constant and it can expand freely, its volume increases instead. A flexible air pillow inside a **shipping carton** may follow neither limiting case exactly. | `shipping carton structure` | `/product-regular-slotted-shipping-carton/` |
| 47 | `/2026/10/12/shipping-carton-creep-under-constant-load/` | A **shipping carton** can continue to deform even when the weight pressing down on it stays the same. Its first response to compression does not necessarily describe what happens during a longer period of stacked storage. | `shipping carton box` | `/product-regular-slotted-shipping-carton/` |
| 48 | `/2026/10/15/carton-opening-retail-surfaces/` | Review a **shipping carton**’s opening action against the retail packs immediately beneath it. The top seam, inner-unit positions and receiving method share an interface. A complete sealing specification does not establish that opening the carton will leave the presentation surfaces inside undisturbed. | `shipping carton insert` | `/product-regular-slotted-shipping-carton/` |
| 49 | `/2026/09/28/roll-or-sheet-labels-application-format/` | Compare roll labels vs **sheet label**s through the application task. Identify who applies the label, how it leaves its backing and which finished surface receives it. A roll and a sheet are supply formats; neither format alone establishes speed, adhesive suitability or compatibility with an applicator. | `sheet label` | `/product-sheet-labels/` |
| 50 | `/2026/10/05/label-roll-changeover-splice-record/` | The existing roll-or-**sheet label** guide helps select the application and roll format. A changeover event adds a different record: which roll leaves, which roll enters and how any splice or uncertain label section is identified. | `printed sheet label` | `/product-sheet-labels/` |
| 51 | `/2026/10/10/preprinted-label-second-pass/` | Use Roll Labels vs **Sheet Label**s: Choose by Application to identify the actual supply and application route before planning a second printing pass. The receiving surface, backing, equipment and variable-print area still need their own compatibility review. | `sheet label format` | `/product-sheet-labels/` |
| 52 | `/2026/10/05/display-refill-pattern/` | Work through a hypothetical card-pack refill Suppose a **counter display** holds two rows of gift-card packs. The front row is empty while the rear row remains. The brand wants staff to refill the front without removing the rear stock. | `counter display` | `/product-shelf-counter-display/` |
| 53 | `/2026/10/12/counter-display-tipping-loaded-balance/` | A **counter display** standing as intended when full has not, through that observation alone, established its behavior with a different remaining stock arrangement. Removing or repositioning products can change the loaded centre of mass and the way a disturbance acts on the display. | `counter display unit` | `/product-shelf-counter-display/` |
| 54 | `/2026/10/05/drawer-stop-removal-scope/` | Record where the user supports the **sleeve and tray**. A sample action performed with two convenient extra hands may not represent the intended customer use. Describe the actual grip instead of claiming universal ease of opening. | `sleeve and tray` | `/product-tuck-end-sleeve-carton/` |
| 55 | `/2026/10/11/packaging-print-ink-setoff-vs-abrasion/` | The converter can review the **sleeve and tray** geometry. The printer can assess whether the visible change concerns the ink film, surface finish or another material layer. | `sleeve and tray box` | `/product-tuck-end-sleeve-carton/` |
| 56 | `/2026/10/12/static-kinetic-friction-sliding-box/` | The tray has a ribbon pull, and the finished **sleeve and tray** are assembled in their intended orientation. | `tray and sleeve` | `/product-tuck-end-sleeve-carton/` |
| 57 | `/2026/10/11/paper-packaging-humidity-curl/` | A printed care card may have different coverage on each face. A sleeve may have a coating on one side. Paperboard used in a **rigid gift box** may be combined with a decorative wrap, adhesive or laminate. | `rigid gift box` | `/product-window-lid-rigid-gift-box/` |
| 58 | `/2026/10/12/free-shrink-restrained-wrapping-force/` | A Hypothetical Two-Candle Gift Set Has Several Constraints Imagine a brand considering a shrink-film proposal around a **rigid gift box** containing two glass candle jars. The material information identifies free-shrink characteristics, but the brand is also concerned about interaction with the box corners and the loaded presentation. | `window-lid rigid gift box` | `/product-window-lid-rigid-gift-box/` |
| 59 | `/2026/10/12/jewellery-pouch-yarn-snagging/` | Packrivo's **jewelry pouch** specifications guide defines the opening, complete contents and construction. The necklace chain-route guide follows retained chain positions and release actions. The textile-yarn interaction adds a more specific contact question to those arrangements. | `jewelry pouch` | `/textile-jewelry-pouch/` |
| 60 | `/2026/11/04/jewelry-travel-rolls-piece-layout/` | For a single piece handed over in a simple bag, the **jewelry pouch** specification guide may be the more direct starting point. A multi-compartment roll becomes relevant when the buyer needs to allocate several distinct pieces or functions within one folding format. | `jewelry pouch specification` | `/textile-jewelry-pouch/` |


## 4. 现有博客标题改写（14 条）

入选规则：先排除 GSC 有排名（avg position ≤20）的 33 篇文章路径；再在剩余文章里只挑满足其一者——① 现标题 >60 字符且能对上真实搜索量词；② 主关键词不在标题前 25 字符内；③ 标题完全没有主关键词。对不上的不硬改（例如 AQL 那篇对不上 `custom jewelry boxes`，低视力排版与木质支撑两篇只有泛词，一律弃用，保留原标题）。

**本轮按 G4 收紧**：删掉 4 条只为凑量的珠宝盒改写（原 #1 棉填充、#2 擦拭布、#16 LED 环盒、#17 礼品卡）与 2 条只有 140 搜索量的 `custom gift boxes wholesale` 改写（原 #14 袖扣、#15 折叠结构），#21 多 SKU 适配矩阵（与 #8 共用 `custom packaging design` 且靠角度硬分）也删。剩 14 条，每条都能对上真实量。第 5 行（MOQ 篇）保留但**依赖 32a**：正文与现标题写 500，32a 上线口径是 250，改标题必须同步改正文数字。

| # | 文章 URL | 现标题 | 目标簇 | 主词 + 搜索量 | 新标题（字符数） | 新 H1 | 建议新增 H2 | 备注 |
|---|---|---|---|---|---|---|---|---|
| 1 | `/2026/09/21/mailer-box-protection-concept-image/` | Mailer Box Protection Starts With the Complete Packed System | mailer-box | mailer box（5,400，Semrush KMT，KD27） | Mailer Box Protection: Review the Packed System（47） | Mailer Box Protection: Review the Packed System | Separate a photo concept from a tested pack | — |
| 2 | `/2026/09/28/tissue-gift-bag-set-checklist/` | Custom Gift Bags and Tissue Paper: Specification Checklist | tissue-paper | custom tissue paper（2,900，Semrush KMT，KD26） | Custom Tissue Paper and Gift Bag: Set Checklist（47） | Custom Tissue Paper and Gift Bag: Set Checklist | Reconcile sheet count against bag count | 与 `/printed-tissue-paper/` 落地页同词，博客只能占信息意图，不抢商业意图 |
| 3 | `/2026/10/20/paper-book-cloth-box-covers/` | Paper or Book Cloth Covers for a Rigid Jewelry Gift Box | jewelry-box | jewelry gift box（1,900，Semrush KMT，KD26） | Jewelry Gift Box Covers: Paper or Book Cloth（44） | Jewelry Gift Box Covers: Paper or Book Cloth | Match the logo process to the cover material | — |
| 4 | `/2026/10/30/message-card-jewelry-packaging-fixed-removable/` | Message Card Jewelry Packaging Keeps the Sentiment With the Piece | jewelry-box | jewelry packaging（1,000，Semrush KMT，KD12） | Jewelry Packaging Message Card: Fixed or Loose（46） | Jewelry Packaging Message Card: Fixed or Loose | Keep the recipient's first read in view | — |
| 5 | `/2026/10/01/packaging-moq-design-planning/` | Custom Packaging MOQ: Plan 500 Pieces per Design | packaging-supply-general | custom packaging design（1,000，Semrush KMT，KD47） | Custom Packaging Design MOQ: 250 per Design（43） | Custom Packaging Design MOQ: 250 per Design | Separate design count from unit count | **冲突**：正文与现标题写 500，32a 上线口径是 250；改标题就必须同步改正文数字，否则前后不一致 |
| 6 | `/2026/09/21/ai-assisted-packaging-concepts/` | Turn AI Packaging Designs Into a Supplier-Ready Brief | packaging-supply-general | custom packaging design（1,000，Semrush KMT，KD47） | Custom Packaging Design Brief From an AI Concept（48） | Custom Packaging Design Brief From an AI Concept | Turn a rendered concept into a dieline request | — |
| 7 | `/2026/10/05/foam-fabrication-layer-proposal/` | Foam Packaging Inserts: Fabrication and Layer Scope | insert | packaging inserts（720，Semrush KMT，KD21） | Packaging Inserts in Foam: Fabrication Layers（45） | Packaging Inserts in Foam: Fabrication Layers | Assign one identity per foam layer | — |
| 8 | `/2026/10/15/pop-up-packaging-refill-plan/` | Pop-Up Shop Packaging Supplies and Refill Quantities | packaging-supply-general | custom packaging supplies（590，Semrush KMT，KD37） | Custom Packaging Supplies for a Pop-Up Shop（43） | Custom Packaging Supplies for a Pop-Up Shop | Count unused materials back in after the event | — |
| 9 | `/2026/10/20/packaging-supplier-certificate-check/` | Packaging Supplier Certificates Need a Site and Scope Check | packaging-supply-general | custom packaging supplier（390，Semrush KMT，KD56） | Custom Packaging Supplier: Check Site and Scope（47） | Custom Packaging Supplier: Check Site and Scope | Separate a site mismatch from a product approval | — |
| 10 | `/2026/10/15/backup-supply-demand-coverage/` | Backup Packaging Supplier Plan: Check Stock Coverage | packaging-supply-general | custom packaging supplier（390，Semrush KMT，KD56） | Backup Custom Packaging Supplier: Coverage Plan（47） | Backup Custom Packaging Supplier: Coverage Plan | Count coverage for the complete requirement | — |
| 11 | `/2026/10/07/shipping-box-sizes-small-business/` | Shipping Box Sizes for Small Business: Plan the Range | mailer-box | custom printed shipping box（210，Semrush KMT，KD31） | Custom Printed Shipping Box Sizes: Plan a Range（47） | Custom Printed Shipping Box Sizes: Plan a Range | Group orders before grouping box sizes | — |
| 12 | `/2026/10/28/necklace-earring-set-box-options/` | Jewelry Set Packaging: One Shared Box or Separate Item Boxes | jewelry-box | jewelry box（60,500，Semrush KMT，KD35） | Jewelry Box Set Layout: Shared or Separate（42） | Jewelry Box Set Layout: One Box or Separate Boxes | Reuse single-item packaging only when it serves the set | — |
| 13 | `/2026/10/12/shipping-carton-creep-under-constant-load/` | A Carton Can Keep Deforming While Its Applied Load Stays Constant | corrugated-carton | corrugated box（2,900，Semrush KMT，KD24） | Corrugated Box Creep Under a Constant Load（42） | Corrugated Box Creep Under a Constant Load | Put elapsed time on the deformation axis | — |
| 14 | `/2026/10/31/brooch-packaging-mounting-method/` | Brooch Packaging Starts With the Mounting Method | jewelry-box | jewelry packaging（1,000，Semrush KMT，KD12） | Jewelry Packaging for a Brooch: Mounting First（46） | Jewelry Packaging for a Brooch: Mounting First | Choose the mounting route around the actual fastening | — |

**主词重复的处理（本轮刻意保留）**：博客之间允许共用主词，但必须角度互补；落地页之间不允许（见 §1，8 页 8 个不同主词）。G4 删行后共用主词从 5 个降到 3 个：

| 共用主词 | 篇数 | 在 §4 的行号与分工 |
|---|---|---|
| `jewelry packaging` 1,000 | 2 | 第 4 行信息卡固定方式；第 14 行胸针固定 |
| `custom packaging design` 1,000 | 2 | 第 5 行只谈 MOQ 拆算（依赖 32a）；第 6 行只谈 AI 概念转交付文件 |
| `custom packaging supplier` 390 | 2 | 第 9 行证书与厂址核实；第 10 行备援供应覆盖 |

**标题改写字数校核**：14 条新标题全部 ≤48 字符（脚本实测 max 48），无「Packrivo」，无 best/#1/客户数/出货量。入选规则的实际分布（脚本逐行验过）：② 主词不在标题前 25 字符 2 条（第 3、4 行）、③ 标题完全无主词 10 条（第 2、5、6、8、9、10、11、12、13、14 行）、余 2 条（第 1、7 行）属长度收口——主词已在前段但现标题 60/51 字符、尾巴是描述从句，统一压到 47/45 字符并把主词固定在字符 1；① 现标题 >60 且靠改标题就能对量的，实际 0 条（两条 65 字符的现标题连主词都没有，已归入 ②/③）。全站 356 篇里 >60 字符的 84 条，§4 只消掉第 4、13 行这两条，改完余 82 条。

## 5. 执行顺序与验收

| 阶段 | 动作 | 验收判据 |
|---|---|---|
| S0 | 前置依赖：34a 的 15 处新标题/MOQ 口径带 “(MOQ 250)”，必须等 32a 上线后再落站 | 站内不出现 500 与 250 并存；本表 S1–S5 均以 32a 已上线为前提 |
| S1 | 建 8 个落地页（§1），先只发 4 个必选：`/custom-candle-boxes/` `/custom-rigid-boxes/` `/custom-paper-bags/` `/pr-boxes/` | 每页 title ≤60 / meta 120–155 / H1 = 主词 / 4 个 H2 / 2 条盒型内链 + `/get-a-quote/` |
| S2 | 修 60 条锚文本（§3） | 逐行改完，锚文本 = §3 的 `锚文本` 列原文；同一目标页锚文本不重复；改完该页内链数不减 |
| S3 | 改 14 条标题（§4） + H1 + 新增 H2 | 新 title ≤60 且含主词前 12 字符内；H1 与标题不重复堆词；新增 H2 内容真实存在对应段落 |
| S4 | 发 34 条选题（§2） | 每篇 800 词以上、2 条内链到指定目标、主词出现在 title/H1/首段 |
| S5 | 补 4 个剩余落地页（§1 第 5–8） | 同 S1 判据 |

**全局验收**：改完重抓 141 页 + 356 篇，跑本段同一套脚本复核——0 页缺 title/H1，meta 长度全在 120–155，两页同主词为 0，§4 的 14 条新标题 ≤48 字符且主词在前 12 字符内。标题 >60 字符的基线是 356 篇里 84 条（脚本实测，最长 95），§4 的 14 条里只有第 4、13 行属 >60，改完应为 82 条；验收只盯「§4 改过的 14 条全部达标」，不承诺全站从 84 归零，余下 82 条另开清单。

**未执行**：本段只出本 MD 与前一份 `34a-keyword-map.csv`，站内改动一行未落；以上 S1–S5 是待执行清单，不是已完成记录。

**G1–G4 处置摘要**：G1 删 8 条抢词选题 + 3 条 dieline 薄文并入 1 篇 → §2 由 40 降到 34；G2 9 个信息词归位（表 §2.1），其中 2 个词库外词只做次词不造主词；G3 三条会逼出编造数字的角度已改写（§2 第 10、17、29 行），第 16 行已按 G1 删除故无需改；G4 §4 由 21 降到 14，MOQ 篇标注依赖 32a。
