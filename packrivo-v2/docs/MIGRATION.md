# Packrivo 迁移总纲：守入口、强评估、慎决策、闭成交

> 合并编制：2026-10-26。本文是迁移文档的唯一合并入口，不代替业务核验，也不表示任何线上改动已执行。

## 来源索引

| 编号 | 来源文档（下一阶段归档后的文件名） | 本文采用的贡献 |
|---|---|---|
| S1 | [competitive-teardown.md](archive/competitive-teardown.md) | 六站配置、单价、样品、结构与材料体验；解释复刻站中段能力的来源 |
| S2 | [comparison-matrix.md](archive/comparison-matrix.md) | A01–I07 双侧对照、业务定位冲突、可迁移边界及证据缺口 |
| S3 | [current-site-evidence.md](archive/current-site-evidence.md) | 2026-10-26 现站只读抓取的状态码、URL 与页面原文；是本文网页证据的主记录 |
| S4 | [migration-plan.md](archive/migration-plan.md) | 四段转化优先级、后续报价插件交付说明与 GEO/SEO 增量方向 |
| S5 | [wp-same-day-migration.md](archive/wp-same-day-migration.md) | 设计令牌、内容骨架、定价公式的可复用规格及工时；不代表这些改动已上线 |
| S6 | [wp-mandatory-rework.md](archive/wp-mandatory-rework.md) | 图片、案例、认证、经营数字、商业条款的重做条件、责任输入与验收口径 |
| S7 | [wp-do-not-migrate.md](archive/wp-do-not-migrate.md) | N01–N08 排除项与 R01–R09 现站保护清单；多语言、询盘和法务边界 |

**索引状态：**七份原件在合并阶段保持原路径、原文件名与原内容，未移入 `archive/`。上表链接供下一阶段将七份原件原文件名归档后使用；归档时应恰好包含这七份文件。`docs/INTEGRATION.md` 是后续的融合说明，不属于本次七份来源；`docs/wp-migration/` 和 `docs/huashu/` 也不另增来源计数。

**证据规则：**S3 的 URL、状态码和页面原文记录的是该次只读抓取，不是 2026-10-26 之后持续在线的承诺。复刻站字段、路由、公式与占位声明的出处是源码审计及七份文档，不把文件路径写成已上线的网站 URL。每个主题的证据只在一个编号条目下展开；其他位置以 `E` 编号回引，避免重复粘贴。S4 较后期描述的插件交付状态与早期“不迁报价模块”路线并列保留，二者适用条件不同，不做静默覆盖。

---

## 一页摘要

### 结论与路线

现站不是需要被复刻站替换的旧壳，复刻站也不是可以原样上线的成品。正确路线是：**保留现站的获客与信任底座，选择性地吸收复刻站的结构化评估能力；先解决真实性与业务口径，再把报价、订单与付款接成一条可验收链路。**

| 现站必须守住的强项 | 复刻站值得吸收的强项 |
|---|---|
| 149 个页面、315 篇博客、134 个产品样式及既有类目入口，构成内容与搜索覆盖；见 E1、E2、E4 | 18 SKU 字段化骨架、结构/系列/材料/工艺维度，以及行业“风险点”内容组织；见 E2、E6 |
| 路径化 11 语种、6,169 键译文、hreflang、分语种 sitemap 与语言选择器；见 E1 | 可复算价格公式、阶梯价展示思路、参数化 SVG 预览与配置交互；见 E5、E6 |
| 采购代理定位一致：独立供应商制造、8–15% 协调费、逐项报价与六步流程；见 E7 | “结构—尺寸—材料—数量—明细”的评估体验，以及永久报价单、订单记录的后续产品思路；见 E5、E7 |
| 诚实披露：AI 概念图逐图标注，案例不冒称客户项目，不展示未核实认证；见 E3、E9 | 设计令牌、字体/组件规范、内容模板、FAQ/HowTo 等可引用答案结构；见 E2、E5、E6 |
| 真实联系与转化兜底：`/inquiry/` 人工审核 brief、request ID、WhatsApp/Email；见 E7 | 紧凑导航和搜索字段映射可作体验参考，但不得删除现站 Samples、Services、Blog、Get a Quote 等入口；见 E1 |

### 三处必须修改的结论

1. **先修真实性与授权，再谈上线。**复刻站的竞品位图、重复改名图片、虚构客户案例/评价、占位认证编号和经营数字不能进入生产站。无真实授权材料时，默认删除；概念图与案例则沿用现站明确披露。主证据在 E9，重做责任见 E11。
2. **再修商业模式与数字口径。**现站“采购代理、8–15% 协调费、供应商确认”与复刻站“直营、即时价”不是文案风格差异，而是承诺主体不同。保留现站默认口径；若未来决定启用自助报价，必须先完成 SKU 映射、基准/MOQ/交期/附加费核验与字典联动，不能让演示数字直接成为报价。主证据在 E5、E7。
3. **最后修成交闭环，但不能绕过前两道闸门。**S4 记录了后续 `packrivo-quote` 插件的订单落库、CSV、通知开关与报价单链接思路；这些解决“订单可查”的中段，不自动等于“已经收款”。成交验收要到 Woo 草稿订单、付款链接/定金尾款规则、状态通知及异常回退，且不得替换现站可用询盘兜底。主证据在 E7，前置条件在 E8。

### 合并后的取舍总表

| 范围 | 决策 | 完成标志 |
|---|---|---|
| 现站内容、分类、博客、指南、对标页、路径多语言、SEO 输出 | 保留，不用复刻站整站替换 | URL、字典、索引与询盘回归通过；见 E1、E2、E10 |
| 设计令牌、字段骨架、材料/结构比较方法、公式逻辑 | 作为增量复用，剥离未核数字和声明 | 有单一来源、可复算、未核值不对外；见 E5、E6 |
| 报价器、独立价格页、永久报价单与订单插件 | 条件化增强，不是整站切换 | 业务口径已决，数字已核，表单/订单/通知端到端验收；见 E7 |
| 图片、客户背书、认证、经营与法务数字 | 必须重做或删除 | 来源/授权/证书可追溯；未核项零出现；见 E9、E11 |
| Next.js 后台、SQLite、cookie i18n、复刻法务及独占 SEO 机制 | 不迁入 WP | 现站 WP/Yoast/mu-plugin 与询盘机制保持；见 E10 |

**阶段边界：**七份来源中的“当天可迁移”是 1 人 8 小时的执行规格，S4 的“已交付”也是其当时对插件的记录；本文编制只读文件并生成合并稿，没有实施任何代码、插件或线上迁移。执行排序建议为：内容与素材保全 → 真实口径核验 → 内部评估模型与映射 → 报价/订单闭环 → 多语言、SEO 与回退验收。早期 R01–R09 的“保留”红线在任一路线下均有效。

---

## 入口层

**目标：让已有搜索、内容、多语言与导航入口完整保留，再增量提高可检索性；不以 18 个精选 SKU 或一套 Next.js 路由替换现站内容资产。**

### E1 · 多语言、索引与导航属于保留底座

**来源与证据（S3 §0、§1；S2 A01–A03/H01–H07/I01–I07；S7 N01/N04/N06/R07–R09）：**

- 现站首页：<https://www.packrivo.com/>；**抓取状态 200，23,532 字节**。H1 原文：“Custom packaging for jewelry, candles & gifts.”；定位为“SHENZHEN · PACKAGING SOURCING”。
- robots：<https://www.packrivo.com/robots.txt>；**抓取状态 200，252 字节**。原文：“AI search and assistant crawlers are welcome.”；包含 GPTBot、ClaudeBot、PerplexityBot 等 Allow 与 sitemap 指向。
- sitemap：<https://www.packrivo.com/sitemap_index.xml>；**抓取状态 200**。共 14 个子图：`post-sitemap.xml` 315 条、`page-sitemap.xml` 149 条、11 个语种子图；`sitemap.xml` 与 `wp-sitemap.xml` 当时均 301 至此索引。德语子图抽查 131 条，说明翻译覆盖并非所有页面都等量，不能凭英文数推断每个语种。
- 德语首页：<https://www.packrivo.com/de/>；**抓取状态 200**。canonical 指向自身；导航和页脚德文化，Blog 标为“(English)”。每页 head 记录 13 条 hreflang（en、11 个语言版本与 x-default）；WP 路径白名单记录 144 条，11 语种字典各 6,169 键为交接包核实结果。
- 现站导航保留 Products 下拉、Samples、Customization、Services、Blog、Resources、About、Get a Quote；页脚保留邮箱、WhatsApp、Compare、Cookie 与法务入口。复刻站六项目扁平导航与 cookie `locale=` 只作参考，不能覆盖上述机制。

**合并决策：**

1. 现站 WP + Yoast + mu-plugin 继续负责 canonical、hreflang、schema、sitemap、robots、语言路由与未翻译路径 404；不迁复刻站 `metadata`、`sitemap.ts`、`robots.ts`、cookie 语言切换器。
2. 新页面先决定英文最终文案，再进入路径白名单、键抽取、11 语种翻译、部署与缓存刷新。改英文原文会使以原文为键的译文失配，不能边上线边随意改词。
3. 复刻站的站内搜索字段映射、404 品牌文案可以借鉴；404 按钮应回到现站 `/products/` 与 `/inquiry/`，不能在未启用报价路由时制造死入口。
4. 现站主题、令牌已有资源先复用。S5 记录 12 色、Archivo/JetBrains Mono、圆角阴影和组件类可作为增量；未启用的新组件类需加前缀避免污染现有页面。字体 logo 来源待核的项不得默认采用。

**验收：**抽样比较英文和各语种 URL、状态、canonical/hreflang、导航入口与 404；sitemap 不因复刻站 53 条路由清单而缩减；语言切换不回退成仅 cookie 生效。不要把 `/samples/`、`/services/`、`/blog/`、`/terms/` 不在 144 条白名单内误判为需要删除的坏页，它们是来源记录的当前覆盖边界。

### E2 · 内容广度和分类按买家心智保留，新增骨架只做增量

**来源与证据（S3 §2、§6、§12、§13；S2 C01–C04/D01–D04；S1 §2–§4；S5 C1–C6；S7 N08/R06/R07）：**

- 产品目录：<https://www.packrivo.com/products/>；**抓取状态 200，74,133 字节**。title 原文：“Custom Packaging: 134 Styles, MOQ 500 | Packrivo”；筛选区原文：“134 packaging options · Page 1 of 6”。保留六类产品入口与既有分页，不能换成复刻站 18 SKU 主目录。
- 指南：<https://www.packrivo.com/guides/>；**抓取状态 200，22,601 字节**。H1 原文：“Better packaging starts with better answers.”；有 5 类 10 个空白 CSV 工作表及 3 篇长指南。
- 博客：<https://www.packrivo.com/blog/>；**抓取状态 200，130,726 字节**。记录 315 篇文章，五个主题数为 Products & Structures 96、Materials & Finishes 127、Pricing & Sourcing 110、Samples & Quality 101、Shipping & Compliance 47。主题计数是页面栏目计数，不将其与 sitemap 总数强行相加。
- 类目示例：<https://www.packrivo.com/jewelry-boxes/>；**抓取状态 200，26,616 字节**。H1 原文：“Custom Jewelry Boxes With Logo for Your Collection”；含 17 张方向卡、结构/适配比较、logo 准备与询盘入口。
- 对标页：<https://www.packrivo.com/compare/>；**抓取状态 200，20,628 字节**。保留 5 家供应商对比和 4 个子页；原文要求“Terms change; confirm current minimums, prices and timing with each supplier before ordering.”，后续维护需带核查日期。

**复刻侧增量：**S1 说明其结构/系列思路受 Packlane、Arka、Packhelp、noissue、PakFactory、PackagingBee 的体验启发，并非授权内容来源。可复用的是 18 SKU 的 `name/tagline/summary/bullets/sizes/stocks/finishes` 字段化骨架、8 行业问题结构、10 指南写作框架、参数化 SVG，以及“系列+结构”作为二级筛选的可能；不可复用的是竞品图片、虚构背书与未核数字。现站 134 与复刻 18 尚无逐项映射，不做“P01 必然等于某 SKU”的全量推定。

**执行规格去重：**S5 的 C1/C2/C3 建议先形成 `products.json`、`industries.json`、`resources.json` 等内部骨架，剔除手写 `from`，对行业统计、指南价格与性能数字标记 `verify:true`。这是内容准备，不等于可把 JSON 直接公开；映射、英文定稿、真实性检查与翻译完成后再挂到现有页面。6 案例仅保留叙事结构，客户名与指标按 E9 处置。

**验收：**现有 134 样式、类目、315 博客、10 工作表和对标页入口均在；新增结构不会形成 12/18 等数量自相矛盾的元数据；任何新增 SKU 都有人工确认映射、来源标记及去重检查。

---

## 评估层

**目标：把“看产品”升级为“能比较规格、材料、结构、样品和参考成本”，但透明计算不等于真实报价。先复用方法，再校准数据。**

### E3 · 现站的规格解释、样品与诚实可视化必须留在评估主路径

**来源与证据（S3 §1、§3、§5、§8、§9；S2 B03/C04/D03/D04/D07/E01–E03；S6 §1/§2/§5.6；S7 R05）：**

- 定制工作室：<https://www.packrivo.com/customization/>；**抓取状态 200，22,155 字节**。原文：“Selections describe a reference, not a live production configurator. No price or availability is implied.”；材料与效果参考库保留，不用“实时配置”名义夸大其能力。
- 案例：<https://www.packrivo.com/cases/>；**抓取状态 200，20,522 字节**。原文：“they are not completed client projects or measured production results.”；四个概念案例保留 brief → supplier questions → sample plan 的教育结构。
- 样品：<https://www.packrivo.com/samples/>；**抓取状态 200，19,800 字节**。原文：“Sample cost, timing and any credit toward an order are confirmed for the selected project.”；保留未印刷结构样与印刷/工艺样的分类及项目化确认。
- 现站产品、案例和定制图均有“AI-assisted concept”“Concept render”“Not a physical sample”等标注。复刻站 `public/media/` 的竞品位图和重复改名文件不能迁入；自产 `BoxRender` 参数化 SVG 是可保留的原创交互，但仍不能由预览推导 closure strength、运输保护或实物颜色已获确认。

**决策与验收：**结构选型、规格定义、样品评估应优先于伪造社会证明。样品页可以吸收复刻站“盒里有什么”的清单组织，但不能照搬“29 USD / 3 business days / 500 units monthly free”等未核承诺。任何概念图要逐图标明性质；真实案例必须有客户授权与原始数据，不能由好听的百分比补足可信度。

### E4 · 产品证据统一为“规格—影响因素—供应确认—样品验收”

**来源与证据（S3 §3；S2 C04/G01/G04/G05；S5 C1/P1；S6 §5；S7 R02/R03）：**

- 磁吸珠宝盒：<https://www.packrivo.com/product-magnetic-jewelry-box/>；**抓取状态 200，24,295 字节**。页首含“Supplier confirmation required”，规格表含结构、材料、工艺、MOQ、尺寸和交期。
- 参考价原文：“US$0.27–0.98 per box. Example: a 9.5×9.5×4 cm flip-lid box is about US$0.54 at 1,000 pcs and US$0.52 at 2,000 pcs. Stock styles, ex-works China, before logo printing, freight and duties (October 2026).”这是带来源、条件和日期的参考价锚点，不能与其他 SKU 或复刻模型价直接等同。
- 交期原文：“Stock styles typically ship in 1–15 days; printed custom orders are scheduled after sample approval.”；运输盒示例：<https://www.packrivo.com/product-front-tuck-mailer/>，**抓取状态 200，24,448 字节**，原文：“Production usually takes 7–30 days after artwork approval, depending on print and quantity.”。
- 抽屉盒示例：<https://www.packrivo.com/product-drawer-jewelry-box/>，**抓取状态 200，24,821 字节**，参考区间原文：“US$0.10–0.33 per box. Stock styles, ex-works China, before logo printing, freight and duties (October 2026).”此价格仅属于本条目，不扩散成全站统一价。

**统一模板：**保留现站 Quote Details/Product specifications 和 What affects the price、Supplier confirmation needed、What to approve on a sample、FAQ 等解释模块；复刻站 stocks/finishes/sizes 三栏与阶梯价 UI 只作为布局参考。134↔18 映射完成前，不用复刻 SKU 数据批量覆盖现站。对外 MOQ 默认保持现站“500 pieces per design”；复刻的 500/1,000/5,000 差异、`10–14 day` 与 `rush +22%` 都需要逐 SKU 核实。现站 poly mailer 的 MOQ 未抽查也仍是明确缺口。

**验收：**价格、MOQ、交期、材料性能、印刷工艺各自显示适用条件；样品确认不被图片或计算器替代；同一页面的 title、正文、配置器、schema 不输出互相冲突的数值。

### E5 · 价格公式可以复用，演示基准和附加费不能直接发布

**来源与证据（S1 “价格怎么对齐”；S2 G01–G06；S4 §1B/§2；S5 §4 P1–P3；S6 §5；S7 N03/R02）：**

S5/S2 记录的复刻公式为：

```text
unit = (styleBase × materialFactor × sizeFactor + printAdd + finishAdd)
       × tierFactor × rushFactor + setup / qty
sizeFactor = (表面积 / 参考面积)^0.6
```

模型含 9 类结构基准、材料/印刷/工艺因子、500–25,000 六档系数、rush 与 setup 分支；文档复算示例 mailer 30×22×12 cm、kraft、full-color、matte、1,000 件约为 2.01。该数字证明“公式可复算”，**不证明它是 Packrivo 的真实售价**。S1 也明确说明其价格未将打样和设计服务摊入单价，运费与税另计，因此公开竞品锚点与自建模型不是同一贸易口径。

**合并决策：**

1. 公式、参数表结构、单测思路可以内部复用；`from` 手写展示价与 `fromPrice()` 模型价不得双轨。S4 后期提到 PHP/TS 一致与共享定价数据，属于实现层演进；仍须遵守 E8 的口径闸门。
2. 现站参考区间、示例、ex-works、印刷/运费/关税排除项与供应商确认必须保持完整。任何 `AggregateOffer` 只能在真实区间和适用条件稳定后生成，不能为了让 schema 有 Offer 而编价。
3. 复刻的 Setup 220+120、免除阈值、Freight 6–9% DDP、40% deposit、30 天有效、29 USD 样品等统一视为待核演示值；未获书面确认就删除。现站要求 box/pouch、insert、decoration、sampling、packing、delivery 分项确认。
4. 未校准时，内部参数需标 `uncalibrated`，前台不展示计算价格；不得用“from $X”或倒计时/进度条制造报价已确定的错觉。
5. S1 的六站拆解只用于理解“单价前置、即时计算、阶梯比较”的体验。它不构成 Packrivo 的价格、MOQ、交期或认证证据，也不要求制作 400 个薄内容品类页。

**验收：**同一输入在选用的前后端实现得到一致结果，边界数量、MOQ、未选工艺、尺寸零值与超档数量有测试；业务确认值有出处和生效日期；在数字未核前，线上仍走 E7 的询盘而非自动承诺。

### E6 · 材料、结构与 GEO 增量必须可引用、可复核

**来源与证据（S1 §1–§2；S2 D01/D02/D07/H03；S4 §2–§3；S5 C2/C3/S1/S2；S6 §4/§6.6；S7 R04/R07）：**

- 现站证书核验教育页：<https://www.packrivo.com/2026/10/20/packaging-supplier-certificate-check/>；S3 §12 的抓取记录标题原文：“Packaging Supplier Certificates Need a Site and Scope Check”，方法为核查 entity、site、scope、current status。S3 将该页作为 200 的 `/blog/` 列表内文章证据，本条目不另造该文章独立响应状态。
- 可新增的行业页应回答“玻璃运输、标签合规、内衬适配、油品阻隔”等具体问题；材料矩阵应比较基材、再生含量、适用场景、限制与代价，而非只放认证 logo。
- FAQ/HowTo 应围绕真实配置与样品步骤；Product/Offer、BreadcrumbList、Organization、Article 等结构化数据与页面可见内容一致。现站 Yoast 体系继续输出，未核 LinkedIn、areaServed、认证或价格不得进入 JSON-LD。
- S4 建议的答案前置、`llms.txt`/`llms-full.txt`、更新日期、内链与图片命名是后续增量，不得覆盖 robots 与 sitemap 既有能力，也不把 llms 文件当作排名保证。
- S5 的 Organization 八国 `areaServed` 是“待核后可选”的字段，不是已确认市场覆盖；默认不填 LinkedIn。现站邮箱与真实 logo 可用，复刻 logo 待来源核验。

**验收：**每一个材料性能、测试标准、法规、环保比例与行业统计都能指向有效证据；未核项不带徽章、不进入正文或 schema；内容有负责方、更新日期与英文定稿后的翻译流程。

---

## 决策层

**目标：帮助买家决定是否继续、向谁提交什么、以什么条件继续。默认保留人工询盘与样品确认；自动化报价是业务口径确认后的可选增强，不是强制替换。**

### E7 · 业务定位、询盘证据与后续成交路线统一到一个决策门

**来源与证据（S3 §4、§7、§10；S2 B04/B06/D05/D06；S4 §0/§1C/§1D/§4；S6 §6.7/§7；S7 N02/N03/R01）：**

- 关于页：<https://www.packrivo.com/about/>；**抓取状态 200，20,414 字节**。原文：“Independent suppliers manufacture the packaging; the project quotation identifies the contracting seller and agreed scope.”；联系口径为 Shenzhen, China (UTC+8)，并披露 AI 辅助与示例非客户订单。
- 服务页：<https://www.packrivo.com/services/>；**抓取状态 200，21,417 字节**。原文：“Our service fee range is 8–15%. Agree the fee calculation basis, included work, any exceptions and payment terms in writing for your project before paid work starts. The percentage alone is not a complete quotation.”
- 询盘页的 URL、状态码、表单原文与 request ID/人工兜底证据集中保存在 **E10 询盘主条目**；本段仅回引 E10，不重复粘贴。决策层自身的直接网页证据仍为上列关于页与服务页。

**默认决策：**现站六步 Brief → Supplier matching → Quotation → Sample → Production & inspection → Shipping 保留；复刻 Configure → Proof → Produce → Ship 是直营假设，不直接替换。`Get a Quote` 默认仍到 `/inquiry/`，不能用 `/quote` 或 `/contact` 破坏已验证表单与 request ID。复刻 CET 营业时间、一个工作日回复、未核 LinkedIn 不进入决策页；真实邮箱、WhatsApp 与 UTC+8 以现站为准。

**后续路线的时间关系：**S2/S7 的早期清单在业务未定、WP 无对应 API 时把 `/pricing`、`/quote`、`/quote/[id]`、SQLite 后台等整族排除；S4 后续则记录 `packrivo-quote` PHP 定价类、`[packrivo_quote]`、`pkq_order` 与 CSV 已交付，并提出 Woo 草稿订单和付款链接。两者不是互相证明上线成功：**不迁 Next.js 专属模块仍成立；若选择新 WP 插件路线，必须单独验收其真实配置、权限、数据和业务流程。**本合并稿不把源码路径当作可访问 URL，也不把“已交付”扩大成“已部署/已收款”。

**决策门前必须回答：**

| 问题 | 默认/通过条件 |
|---|---|
| 谁是合同卖方，Packrivo 是协调方还是直营供应商？ | 默认按现站采购协调；变更需业务书面决策并全站一致改口径 |
| 展示的是参考区间、内部估算还是可接受报价？ | 未核数字只作内部估算；参考价完整带条件；正式报价需有效期与责任方 |
| 是否允许不完整 brief？ | 允许“not sure”，保留人工补全；不要为了配置器完整度提高提交门槛 |
| 样品、模具、印刷、运费、税费和付款如何分项？ | 逐项书面确认，不沿用复刻固定演示值 |
| 提交后客户得到什么？ | 默认 request ID；新路线还应给可回看/打印记录、状态与联系人 |

**验收：**业务定位、费用、联系时区、CTA 和 schema 一致；表单未提交不生成“已保存”假象；自动路径失败时可回到现有询盘、Email/WhatsApp；模式变更引发的新英文文本先定稿再走翻译。

### E8 · 上线前的映射、口径、国际化与验收闸门

**来源与证据（S2 I05–I07/证据缺口；S5 G0-1–G0-6/§6；S6 §7–§8；S7 第三部分；S4 §4）：**

- 现站参考价页的 URL、状态与完整原文只在 E4 保存。本条目只把它作为数字口径闸门的核验锚点，不复制证据段。
- **SKU 映射闸门：**134 现站样式 ↔ 18 复刻 SKU 没有完整映射，需人工确认名称、结构、材料、尺寸、单位和 MOQ。不能只按相似 slug 或相同图片批量合并。
- **数字闸门：**来源清单要求取得线上 `packrivo-site-remediation-20261022.php` 的实际口径；本地交接包未含其源码。未取得前，价格/MOQ/交期的最终上线数字仍为阻塞项，而不是由文档作者猜定。
- **文案与路由闸门：**英文修改影响 6,169 键字典匹配；新增 WP 页面需白名单、抽取、翻译、部署与缓存可见性验证。翻译并发上限、部署账号、文件属主、CF 凭据等来自 S5 的执行前置，需在实际实施环境逐项确认，本文不宣称已完成。
- **素材与事实闸门：**图片授权/概念标注、客户案例授权、证书主体与范围、公司数字、法务条款、联系方式和 logo 来源按 E9/E11 清理。
- **技术闸门：**S4 提及定价一致性、配置器落库、后台 CSV 与通知开关；验收必须覆盖服务端重算、数量与字段校验、重复提交、无效/越权报价 ID、订单导出、通知失败和回退。这里列出的是验收要求，不是本阶段新测试结果。
- **证据缺口保留：**复刻首页 FAQ 全文、现站英文 404 文案、独立搜索结果页、poly mailer MOQ、FSC/LinkedIn/logo 真实性都不能由相邻资料推断。任何一项未核，在对应条目标“待核”，不变成事实。

**顺序建议：**先冻结现站基线与可回退副本，再完成映射和真实性清理；随后内部测试价格模型/配置器，最后才接订单、Woo 草稿、付款和通知。S5 的 8h 清单可作为资料准备参考，不应在业务决策、映射或凭据未决时机械执行。S4 的“两周”顺序同样以这些闸门通过为前提。

---

## 成交层

**目标：让确认后的规格、价格、责任方与样品结论成为可追踪订单，并保留人工兜底。订单记录、付款状态和客户通知是不同验收点，不能互相替代。**

### E9 · 真实素材、案例和认证是成交信任的前置，不是装饰

**来源与证据（S2 E01–F05；S3 §1/§5/§15；S6 §1–§4/§6；S7 R04/R05/R09；S1 §4）：**

- 关于页的 URL、披露与业务口径已在 E7 保存；本条目只回引其“illustrative examples are not records of customer orders”原则，不重复粘贴状态与原文段。
- 复刻站 `public/media/` 在 S6 复验记录中为 72 个位图、14 组 MD5 重复、涉及 31 个文件；来源脚本指向六竞品。存在产品图和“客户案例”图同文件的情况。S2 较早记录为 60 文件，S6 后续全树复核为 72，**本文采用后一个带复验说明的范围并保留版本差异**，不混算成 132。
- 位图处置：默认不迁。替换来源只能是自有实拍、可追溯授权素材或自产且明确标注的 AI 概念图；同图不得改名冒充不同 SKU、样品或案例。`BoxRender` SVG 可保留；`gen/` 缓存不是独立真实素材；logo 来源待核。
- 六个演示客户案例及 0.0% 破损、−71% 成本等指标和三条署名评价是占位内容。无授权时改为现站式概念走查并删除客户名/metrics；若使用真实案例，需合同/订单等原始数据及客户书面披露授权。
- FSC C168842、ISO 9001、BPI、SEDEX、GRS、ASTM D6400、ISTA、UN、FDA/EU 等徽标或性能声明均须逐条核真。保留展示结构不等于保留声明；无 Packrivo 对应实体、场地、范围与有效期证据时默认删除。
- 经营数字如 2,400+ brands、94% FSC/recycled、26 wk、0 PVC、100% declarations、公司时间线、DDP、10–14 d 等没有佐证即不发布。“11 languages”只在 WP 实际覆盖与页面范围相符时使用，不能把复刻 7 语种 chrome 包装成全站 11 语种。
- 联系与法务：不搬复刻 CET 时间/回复承诺、未核 LinkedIn、硬编码 `Delta E≤3`、`±3mm`、14 天索赔等条款；现站隐私与条款页见 E10。所有成交页保持真实联系人、责任方和概念图披露。

**信任验收：**以上线产物做来源清单与演示关键词检查，不只审源文档；位图做哈希去重，案例做授权抽查，认证逐项核 entity/site/scope/status，数字可回溯。缺少材料时的通过方式是删除对应声明，而不是把它缩小或移到页脚。

### E10 · 订单、Woo、付款、通知和隐私必须按一条链路验收

**来源与证据（S3 §10/§14；S4 §1D/§4/§6；S5 §0.1；S7 N02/N03/N05/N07；S2 D05/D06/G06）：**

- **E10 询盘主条目（本段直接 URL 证据；E7 及其他位置只回引本条）：**现站询盘页 <https://www.packrivo.com/inquiry/>；S3 §10 记录**抓取状态 200，25,377 字节**，title 原文：“Request a Custom Packaging Quote | Packrivo”；H1 原文：“Get a packaging quote.”。
- 该主条目的页面原文：“You may enter ‘not sure’ for the packaging option, approximate dimensions, quantity or destination. Review the summary before you choose to send it; a request ID appears only after Packrivo saves your submission.”提交前提供 Copy brief / Download .txt / Edit details / Continue on WhatsApp / Email；保存边界原文：“The website request is saved only when you select Send My Packaging Request and receive a request ID.”因此 request ID 与人工联络是当前成交链的可验证兜底，不能被演示订单页替代。
- 隐私与条款页的 URL、抓取状态和原文集中保存在 N08。本处只要求新订单/报价流程沿用这两份正式政策，复刻 `legal/[slug]` 不覆盖它们；新增 shipping 或付款/退款条款需业务与法务确认。
- S4 的后续实现记录：`pkq_order` 用于订单落库，后台可查、可导出 CSV；`notify_customer` 为通知开关；报价单可用引用号只读回看；下一步是订单 → Woo 草稿订单 → 付款链接。定金/尾款仍需业务确认，特别是现站 8–15% 协调费模式不能自动套用复刻 40% deposit。

**成交链验收矩阵：**

| 环节 | 必须通过的行为 | 不得误判为完成的情况 |
|---|---|---|
| brief/配置提交 | 服务端校验数量、MOQ、规格、联系人和必填同意；失败有明确错误 | 前端显示价格或进度 100%，但无持久记录 |
| 报价/订单记录 | 返回唯一 ID/ref；详情可回看、打印，未授权用户不能枚举他人记录 | 仅有静态 `/quote/[id]` 页面或本地浏览器状态 |
| 后台与导出 | 授权人员可查询订单，CSV 与详情一致，含规格、口径、时间与状态 | 只存在数据库表或管理 UI 截图 |
| Woo 草稿 | 已确认订单映射到正确草稿、金额、币种、分项与买家；重复提交不重复建单 | 插件“已交付”或安装了 Woo，不代表草稿自动生成 |
| 付款 | 付款链接与订单对应；定金/尾款、协调费、运费、税费、有效期和退款责任明确 | 复刻的 40%、6–9%、220+120 等演示值被直接当正式条款 |
| 通知与回退 | 客户/内部通知包含 ref 与下一步；失败重试、垃圾邮件、退信和人工联络有记录 | 打开 `notify_customer` 开关就宣称邮件/短信已送达 |
| 隐私与索引 | 询盘/报价数据按现站政策处理；个人报价、后台、API 不进入公开索引 | 用公开永久链接暴露联系人、价格或订单明细 |

**上线判定：**只有所选业务模式下的“提交—服务端复核—记录—授权查看—草稿/付款（若启用）—通知—人工回退”走通，才能称成交闭环。未启用 Woo/付款时，应明确交付边界为“询盘/订单记录已通”，不以文案掩盖末段未完成。

### E11 · 责任输入、增量交付与最终上线清单

**来源与证据（S4 §4；S5 §5–§6；S6 §7；S7 第三部分；S1 §5）：**

- 业务/站长：确认合同卖方、协调费或直营价、MOQ、交期、附加费、样品与付款政策；负责最终对外口径。
- 供应商/合规：提供规格、材料、测试、认证与履行能力的真实证明；证据不足则不写性能或认证。
- 设计/摄影与内容：完成素材替换、版权/授权记录、概念标注、SKU 映射、行业与指南事实核验；S6 估算素材 4–6 人日、案例改写 2–3 人日等是规划估算，不是已完成工时。
- WP/迁移实施：只在冻结英文口径后接入内容模型、翻译和 SEO；保留现有表单与回退，验证缓存、权限、文件属主与部署可见性。
- 验收责任：按 E1/E2 守入口，E3–E6 核评估，E7/E8 过决策闸门，E9/E10 验成交。每个数字、素材、客户与证书有 owner 和来源。

**建议交付切片（不覆盖上述阻塞条件）：**

1. **保全切片：**冻结现站入口、字典、分类、询盘、法务与披露基线；建立 URL/字典/素材清单。
2. **真实性切片：**清理图片、案例、评价、认证、经营数字、时区和法务占位；这是可上线的前置，而非视觉美化尾声。
3. **评估切片：**完成 134↔18 映射、字段骨架、材料/结构比较与内部公式测试；未核值保持内部或不显示。
4. **决策切片：**确认业务模式、参考价与报价边界，接通选定配置器到人工审核；保留 request ID 与联系兜底。
5. **成交切片：**按 E10 验收订单、CSV、Woo、付款与通知；未完成项明确留在后续，不回填成“已闭环”。
6. **增长切片：**在真实内容稳定后做内链、FAQ/HowTo、有效 Offer、日期更新与 llms 文件，避免以 SEO 输出放大未核声明。

**最终不迁/保留对照：**复刻 Next.js `/admin`、`/api/*`、node:sqlite、cookie i18n、独立 `/contact`、硬编码法务和 Next SEO 技术载体不迁；现站导航页脚、134 目录、博客/指南/对标、路径多语言、Yoast/mu-plugin、询盘/request ID、概念披露和正式法务保留。复刻公式、字段骨架、设计令牌和 SVG 是**可复用资产**，不是整站替换许可证。

---

## 不可迁清单

**目标：把“无论怎么包装都不应直接进入 WP”的项目集中封住，并与“需重做后可用”区分。本节是排除与保留清单；前文已有的 URL、状态码和页面原文不再复制，仅保留本段 N08 独有的法务证据。**

### N01 · 复刻 cookie 多语言与 7 语种 chrome 机制

不迁 `lib/locales.ts` 的 cookie 方案、仅 chrome 的语言下拉与 URL 不分语种机制；不把“11 languages”演示文案当作覆盖证明。保留现站路径前缀、11 语种字典、hreflang、语言选择器、RTL、分语种 sitemap 和白名单 404。新增语言或页面必须走现有字典与路由流程。对应证据：E1。

### N02 · Next.js `/admin`、`/api/*` 与 node:sqlite 数据层

不迁复刻后台、演示登录、route handlers、quotes/leads/events SQLite 三表及相关 API；不能把它们当 WP 插件直接平移，也不保留公开演示口令或 Admin 链接。若采用 S4 所述后续 WP 插件，应视为独立实现并按 E10 重新验收，而非宣称旧后台已迁移。现站 `/inquiry/` 与 WP 管理权限继续作为基线。对应证据：E7、E10。

### N03 · 未决业务模式下的即时报价模块族

不直接迁 `/pricing`、`/quote`、`/quote/[id]`、Live configurator 和 Configure/Proof/Produce/Ship 直营流程，不展示 from/fromPrice 双轨价、演示基准、统一 10–14 天、rush、setup/freight/deposit 等固定数字。公式可内部复用，永久链接和订单思路可在 WP 新路线评估，但在 E7/E8 闸门前不能替换现站询盘、六步流程和参考价口径。对应证据：E4、E5、E7。

### N04 · 复刻六项目扁平导航、认证页脚和联系页

不以其 SiteHeader 替换现站导航，不删除 Samples、Services、Blog、Resources、Get a Quote 或产品下拉；不迁带占位徽标、newsletter/API 和 Admin 的页脚；不迁 `/contact` 的 CET 时间、一个工作日承诺、未核 LinkedIn 与 `/api/leads` 表单。保留现站联系、WhatsApp、Compare、Cookie、法务与真实时区。对应证据：E1、E7、E9。

### N05 · Next.js 独占 SEO、搜索与 404 技术实现

不迁 `app/layout.tsx` metadata 作为 WP 输出机制，不用复刻 53 条 sitemap 或 robots 替代现站；不把 cookie 语言与无 canonical/hreflang 的方案带入 WP。保留 Yoast、mu-plugin、AI 爬虫策略和现有索引。搜索字段、主题色、Organization 的少量真实字段、404 文案仅作为可选增量；未核 logo/LinkedIn/areaServed 不输出。对应证据：E1、E6。

### N06 · 以 18 SKU、6 系列或 9 结构替换现站主目录

不删除现站 134 样式、六类买家入口、分页、产品 SEO 页面或既有 slug；不在映射缺失时把 Ridge/Halo/Fold 等系列、Ecommerce/Luxury 等场景类别当成同级替代，也不复制复刻“12 structures / 18 SKU”“4 cases / 6 cases”的元数据矛盾。字段骨架和筛选维度只在人工映射、核真后作二级增强。对应证据：E2、E4。

### N07 · 竞品位图、重复改名素材、虚构案例与未核背书

六站抓取图片、14 组重复改名位图、虚构客户名/评价/指标、占位证书编号、环保/测试/经营比例、虚构公司时间线一律不迁。没有授权或证明时的处理是删除或改成明确的概念内容，不是保留原值并附小字说明。原创 SVG、真实授权素材和可追溯案例可在 E9 验收后使用。竞品拆解仅作内部体验参考，不复制其文案、图片、代码或模板。对应证据：E3、E6、E9。

### N08 · 复刻法务、未经确认的商务承诺和敏感数字口径

**来源与证据（S3 §14；S2 A02/A03；S6 §6.5；S7 N07/R09）：**

- 隐私：<https://www.packrivo.com/privacy/>；**抓取状态 200，21,250 字节**，页面原文标题为“Privacy & inquiry data”。
- 条款：<https://www.packrivo.com/terms/>；**抓取状态 200，20,349 字节**，页面原文标题为“Terms of service”。

不用复刻 terms/privacy/shipping 覆盖现站正式法务；不带入 Delta E、±3mm、14 天索赔、29 USD 样品、3 天出样、DDP 比例、40% 定金、30 天有效、公司史、回复承诺等未核内容。价格/MOQ/交期未过 E8 数字闸门前不对外；法务与付款条款未审定前不进入订单或 schema。对应证据：E5、E8、E10。

### R01–R09 · 现站保护项（永远不被复刻覆盖）

| 保护项 | 保留内容 | 回引 |
|---|---|---|
| R01 业务与获客 | 采购协调定位、独立供应商制造、六步流程、真实 CTA 与询盘 | E7 |
| R02 价格展示 | 参考区间、示例、ex-works 与排除项、供应商确认、分项报价 | E4、E5 |
| R03 MOQ/交期 | 现站已确认口径与条件化区间；差异值须重新核实 | E4、E8 |
| R04 认证立场 | 无证书不背书，保留证书核验教育 | E6、E9 |
| R05 案例与披露 | 概念图逐图标注、案例非客户项目、AI 辅助披露 | E3、E9 |
| R06 目录分类 | 134 样式、六类产品入口与现有 URL | E2 |
| R07 内容/SEO | 315 博客、工作表、对标、robots/sitemap/hreflang/schema | E1、E2 |
| R08 多语言 | 路径路由、11 语种字典、选择器、RTL、白名单与 404 | E1、E8 |
| R09 页脚联系/法务 | 邮箱、WhatsApp、Shenzhen UTC+8、Compare/Cookie、正式隐私和条款 | E7、E10 |

**不可迁清单的完成判据：**不是把这些词从文档中删掉，而是上线路由、模板、数据、媒体、结构化数据与外部承诺中均无对应未核项；同时现站保护项的 URL、内容、翻译、询盘和回退仍可用。归档七份来源之后，本文继续作为单一决策入口，新增证据应更新相应 `E` 条目而不是另贴一份重复证据。
