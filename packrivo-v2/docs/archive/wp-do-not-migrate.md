# packrivo.com → WordPress 迁移 · 不建议迁移清单（Do-Not-Migrate）

> 生成：2026-10-26 · 只读审阅，未修改现站与源码。
> 依据：`tasks/30/comparison-matrix.md`（A01–I07 稳定编号）、`current-site-evidence.md`（现站抓取 2026-10-26，原始存档 `i295/pages/`）、`replica-source-inventory.md`（packrivo-v2 源码审计）、`wp-handoff-constraints.md`（交接包核实）。本清单写作前对复刻站 `lib/locales.ts`、`components/SiteHeader.tsx`、`components/SiteFooter.tsx`、`app/robots.ts`、`components/AdminLogin.tsx` 及现站存档 `home.html`/`about.html`/`services.html`/`cases.html` 做了回查复核。
> 编号规则：**N=复刻站应排除不搬项**（N01–N08）｜**R=现站应保留、不被复刻站覆盖项**（R01–R09）。每行引用对照表稳定编号。
> 结论一句话：**复刻站唯一"整体可搬"的是设计令牌、文案骨架与公式（归当天清单）；其 i18n 机制、后台/API、即时报价模块族、导航、联系页、SEO 载体与 18-SKU 主目录结构一律不搬；现站业务定位、价格/MOQ/交期口径、认证口径、案例与披露体系、134 目录、315 博客、11 语种资产一律保留、不被复刻覆盖。**

---

## 与"必须重做"清单的划界总则（先读）

- **必须重做**（`wp-mandatory-rework.md`）= 替换真实材料后【可用且会被搬】的项：全部位图素材与重复改名组、虚构客户案例/评价/经营数字、FSC C168842 等认证编号、价格基准与 MOQ/交期数值、setup/freight/payment 数字、CET/LinkedIn/回复承诺等联系方式数值。
- **不建议迁移**（本清单）= 替换与否都【不搬】的项：与 WP 平台/现站架构冲突的机制（cookie i18n、node:sqlite 后台、Next.js 专属载体）、与现站商业模式冲突的模块（即时报价族、"直营供应商"叙事）、会覆盖现站确认口径的结构（18-SKU 主目录、差异化 MOQ、认证徽标展示块）。
- 本清单只做"排除/保留"决策；凡条目承载的具体数值/素材替换，一律指向重做清单并在各条【划界】字段注明，不重复罗列。

---

## 第一部分 · 复刻站内容/结构：应排除、不搬

### N01｜cookie 多语言体系（整项不搬）
- **对照表**：I01、I02、I03、I04、H02
- **对象**：`lib/locales.ts`（LOCALES 仅 7 语种 en/de/es/fr/ja/zh-CN/ar，已复核）+ `components/SiteHeader.tsx` 语言下拉（写 cookie `locale=`，`max-age=31536000`）+ `app/layout.tsx` 按 cookie 决定 html lang/dir
- **现站证据**：https://www.packrivo.com/ 页脚 H2 "Choose your language"（可搜索/alias/免责 "Translations may contain errors. Verify names and technical specifications against the English source."）；每页 13 条 hreflang（en+11+x-default）；路径前缀 `/{lang}/path`；11 语种 × 6169 全量译文（已核实 11/11 均 6169 键命中，wp-handoff §1.5/§1.6）
- **可核验差异**：复刻 URL 不分语种、无 hreflang 无 canonical 交替；仅 7 语种且只翻译站点 chrome、正文全英文；宣称 "11 languages" 与实际不符
- **不建议迁移原因 + WP 约束**：现站 WP 多语言由 mu-plugin `packrivo-i18n.php` 路径前缀路由实现（语言前缀正则、`site_lang` cookie 接管、不在 paths.json 的路径 404，wp-handoff §4）。复刻 `locale=` cookie 切换与这套机制直接冲突且翻译页对搜索引擎不可见；两套 i18n 不能并存，搬入即造成 SEO 退化与 6169 键字典链路破坏
- **处置方式**：**整项不搬**——`lib/locales.ts`、语言下拉、cookie 逻辑全部排除；保留现站 mu-plugin + 选择器 1.0.7 + 11 语种字典（见 R08）
- **适用范围**：复刻全部 i18n 实现与语言切换 UI
- **划界**：hero/about/marquee 中 "11 languages" 失实宣称的文案修正归**必须重做**；本行只管语言机制不搬

### N02｜/admin 后台 + /api/* + SQLite 数据层（排除路由与模块）
- **对照表**：A03、H05
- **对象**：`app/admin/`（AdminConsole/AdminLogin，默认口令 `packrivo-demo-2026` 直接显示在登录 UI，已复核）、`app/api/`（/api/quotes、/api/leads、/api/admin/*、/api/health）、`lib/db.ts` node:sqlite 三表 quotes/leads/events
- **现站证据**：无 /admin；询盘走 https://www.packrivo.com/inquiry/ 多步表单（原文 "The website request is saved only when you select Send My Packaging Request and receive a request ID."），buyer-data 142 询盘选项（wp-handoff §3.4），兜底 WhatsApp/Email
- **可核验差异**：复刻自建后台+数据库+API 套件；现站是 WP 原生 wp-admin + 表单询盘链路（询盘验收脚本 verify_inquiry_runtime.js 为已验收资产，wp-handoff §1.13）
- **不建议迁移原因 + WP 约束**：node:sqlite 与 Next.js route handlers 无法落地 WordPress；复刻自身 robots.ts 也 disallow `/admin`、`/api/`（不公开内容）；现站询盘链路无需替换
- **处置方式**：**排除路由与模块**——/admin、/api/*、SQLite 数据层三件套整项不搬；保留现站 /inquiry/ 表单链路
- **适用范围**：后台、API、数据层全体
- **划界**：无（纯排除项，不涉及重做）

### N03｜即时报价模块族（现阶段整族不搬）
- **对照表**：A04、B01、B04、B06、B08、D05、D06、G02、G03、G06
- **对象**：`/pricing`（18 结构×500/2,500/10,000 全价表 + "Setup 220+120 / Freight 6–9% DDP / Payment 40% deposit" 三卡）、`/quote` 五步配置器（QuoteBuilder，POST /api/quotes 服务端重算入库、返回报价单 id）、首页 hero「Live configurator preview」卡、四步流程 Configure/Proof/Produce/Ship
- **现站证据**：首页六步流程 "From brief to shipping."（Brief→Supplier matching→Quotation→Sample→Production & inspection→Shipping，每步标参与方）；/inquiry/ "You may enter “not sure” for the packaging option, approximate dimensions, quantity or destination."；/services/ "Our service fee range is 8–15%. Agree the fee calculation basis, included work, any exceptions and payment terms in writing for your project before paid work starts."
- **可核验差异**：复刻=直营供应商即时报价（"All prices in USD, ex-works, valid 30 days"）；现站=采购代理逐项询盘（参考价区间+供应商确认）
- **不建议迁移原因 + WP 约束**：①业务模式未决（对照表汇总结论 1）：即时报价仅直营模式成立，与现站 8–15% 代理口径冲突；②WP 无对应服务端定价重算/入库 API，QuoteBuilder 依赖 Next.js 路由处理器，无法平移；③价格数字口径受 `packrivo-site-remediation-20261022.php` 管辖（I07，wp-handoff §6.6），即时价展示会制造代理模式承担不了的承诺风险
- **处置方式**：**现阶段整族不搬**——排除 /pricing、/quote、/quote/[id] 路由与 Live configurator、四步流程；保留现站 /inquiry/ + 六步流程 + Get a Quote CTA 链路；是否远期引入待业务模式决策
- **适用范围**：即时报价相关页面/组件/API 全体
- **划界**：定价公式本身（lib/pricing.ts 可复现计算逻辑）归**当天可迁移**清单（只迁公式不迁数字）；价格基准、setup/freight/payment/MOQ/lead 数值替换归**必须重做**清单——本行只排除模块与路由，不重复罗列数值

### N04｜SiteHeader 导航结构（不搬，保留现站导航）
- **对照表**：A01
- **对象**：`components/SiteHeader.tsx` 导航 6 项扁平（Products/Industries/Sustainability/Resources/Work/About）+ 搜索 + 语言切换 + 移动抽屉（已复核）
- **现站证据**：https://www.packrivo.com/ header：logo；Products 下拉（All packaging products、Jewelry boxes、Foldable gift boxes、Mailer boxes、Labels、Inserts）；Samples；Customization；Services；Blog；Resources 下拉（Guides、Packaging examples）；About；Get a Quote 按钮；移动端 "Menu"
- **可核验差异**：复刻缺 **Samples / Services / Blog / Get a Quote** 四个关键获客入口，且无 Products 二级类目下拉
- **不建议迁移原因 + WP 约束**：直接替换导航会砍掉现站核心服务入口与全站主 CTA；WP 侧无硬约束，属纯内容/转化决策。复刻紧凑布局仅可借鉴
- **处置方式**：**不搬**复刻导航结构；保留现站 header（含 Get a Quote 按钮与 WhatsApp 预填浮动 CTA）
- **适用范围**：全站 header
- **划界**：无（结构决策，非数值重做）

### N05｜/contact 联系页（整项不搬）
- **对照表**：A03、F05
- **对象**：`app/contact/page.tsx`：hello@packrivo.com / Mon–Fri 09:00–18:00 CET / 1 business day 回复承诺 / ContactForm（POST /api/leads）；layout Organization JSON-LD sameAs LinkedIn
- **现站证据**：无独立 /contact/ 页；联系方式在页脚 "Contact Packrivo（hello@packrivo.com、WhatsApp +86 198 9984 6512）" 与 /about/ "In English by email or WhatsApp, from Shenzhen, China (UTC+8)."
- **可核验差异**：CET 营业时间暗示欧洲运营 vs Shenzhen UTC+8；LinkedIn 未核实；现站无回复时效承诺
- **不建议迁移原因 + WP 约束**：联系口径冲突，以现站为准（F05）；复刻表单依赖 /api/leads（同 N02 不可平移）
- **处置方式**：**整项不搬** /contact 路由与表单；保留现站页脚 + About 联系块
- **适用范围**：/contact 页及其联系信息展示块
- **划界**：CET/回复承诺/LinkedIn 数值修正归**必须重做**（仅当未来决定保留该信息块时才需重做；本行结论是该块整体不搬）

### N06｜Next.js 专属 SEO/404 技术载体（不搬文件与机制）
- **对照表**：A06、H02、H04、H05、H07
- **对象**：`app/layout.tsx` metadata API（title template/default description/openGraph/Organization JSON-LD）、`app/sitemap.ts`（53 条）、`app/robots.ts`（disallow /admin /api/ /quote/，已复核）、`app/not-found.tsx`（「This page went out flat-packed」）
- **现站证据**：WP + Yoast SEO v28.5 + mu-plugin 输出：13 条 hreflang、Yoast schema-graph（WebPage/FAQPage/Product）、sitemap_index.xml 14 子图、robots AI 爬虫白名单；路径不在白名单 → 专用 404 页（noindex,follow，不跳英文，wp-handoff §4）
- **可核验差异**：载体不同——Next.js metadata export vs WP/Yoast/mu-plugin；复刻产物逻辑（无 hreflang、无 canonical、cookie 语言）在 SEO 上劣于现站（H02）
- **不建议迁移原因 + WP 约束**：这些 Next.js 文件不可落地 WP；WP 侧必须继续由 Yoast + mu-plugin 输出 SEO 与 404 机制。复刻 robots 的 disallow 思路可借鉴但 /quote 本就不搬（N03）
- **处置方式**：**不搬文件与机制**（metadata/sitemap.ts/robots.ts/not-found.tsx 均排除）；保留现站 Yoast+mu-plugin 体系与白名单 404 机制；复刻 metadata 模板组织仅作参考
- **适用范围**：复刻全部 SEO/404 实现文件
- **划界**：无（载体差异，非内容重做）

### N07｜复刻独有路由逐页处置（汇总）
- **对照表**：A03
- **对象/处置**（复刻独有 6 页 + 法律页）：

| 复刻路由 | 处置 | 依据 |
|---|---|---|
| /pricing、/quote、/quote/[id] | 现阶段**排除**（整族不搬） | N03、D06、G02/G03/G06 |
| /admin、/api/* | **排除**（后台/数据层） | N02 |
| /contact | **整项不搬** | N05、F05 |
| /sustainability | **不搬整页**：统计（94% FSC or recycled / 26 wk / 0 PVC / 100% SKUs）与四认证卡归必须重做；8 基材对照矩阵结构可借鉴归当天清单 | E04、F01、F02 |
| /search | **不搬**（robots index:false 不贡献 SEO；WP 原生搜索 + Yoast SearchAction `?s=` 已够；其字段映射仅参考） | A05、H03 |
| /legal/[slug]×3 | **不搬**：现站已有 /privacy/、/terms/；复刻条款含自拟数值（Delta E≤3、±3mm 公差、14 天索赔期）未与现站法律文本对齐 | A02、current-site-evidence §14 |

- **WP 约束**：任何复刻独有页若要进 WP，均需 paths.json +1 → 抽取 → 11 语种翻译（I06，wp-handoff §6.2/§7），新增成本=新增键 N×11 语种——这是"不建议新增"的量化理由；上表已排除的页面不产生该成本
- **划界**：/sustainability 与 /legal 中承载的具体数值/徽标替换归**必须重做**；本行只管路由级排除决策

### N08｜复刻分类体系与 18-SKU 目录（不作为主结构）
- **对照表**：C01、C02、C03
- **对象**：`data/products.ts` 18 SKU 的 category 5 类（Ecommerce/Luxury/Retail/Food & drink/Branding）+ series 6 系 + style 9 型；CatalogFilter 按类目/系列筛选；`app/products/page.tsx` H1「Every structure we actually run」且 meta description 写「Twelve packaging structures」（站内 12/18 自相矛盾）
- **现站证据**：https://www.packrivo.com/products/ "[FORM] 134 packaging options · Page 1 of 6"、title "Custom Packaging: 134 Styles, MOQ 500 | Packrivo"；分类眉标 Jewelry boxes / Foldable gift boxes / Mailer boxes / Bags & pouches / Labels（+Inserts）；6 个类目页 /jewelry-boxes/ 等
- **可核验差异**：134 vs 18；终端品类（买家心智）vs 业务场景/工厂语言（series/style）；命名体系完全不同（P01 "Rigid Magnetic Jewelry Box" vs halo-magnetic-box）；134↔18 映射表不存在
- **不建议迁移原因 + WP 约束**：现站 134 目录是产品页 SEO 主力资产（119 个产品页在 i18n 白名单，wp-handoff §1.8）；以 18 SKU 替换将损失 100+ 页面并破坏买家心智分类。两套分类维度不可互换
- **处置方式**：**不作为主结构**——复刻目录/分类不替换现站 134 目录与 6 类目；保留现站类目页；series/style 仅可作产品页二级属性/筛选项（需先建 134↔18 映射表，属前置工作）
- **适用范围**：复刻目录数据与分类体系整体
- **划界**：18 SKU 的真实文案字段（name/tagline/summary/bullets 等）迁移归**当天清单**；其价格/MOQ 数值归**必须重做**；本行只管"不作为主目录结构"

---

## 第二部分 · 现站内容/结构：保留、不被复刻站覆盖

### R01｜业务定位与获客链路（采购代理口径）
- **对照表**：D06、B04、D05、B06
- **现站证据**：/about/ "**Independent suppliers manufacture the packaging**; the project quotation identifies the contracting seller and agreed scope."；/services/ meta "Coordination fee 8–15% of production value, agreed in writing before work starts."（services.html 已复核）；首页 FAQ "What does Packrivo do? … We help small brands prepare specifications, compare supplier quotations and review samples."
- **复刻侧冲突点**：`app/about/page.tsx` H1「A packaging supplier that shows its arithmetic」+「2,400+ brands since 2019」；/pricing "All prices in USD, ex-works, valid 30 days"
- **可核验差异**：采购代理（8–15% 协调费）vs 直营供应商（自报价）；复刻"直营"叙事与现站全站一致口径冲突（current-site-evidence §15.2）
- **不建议覆盖原因 + WP 约束**：业务定位决定全部内容口径（对照表汇总结论 1），迁移默认以现站为准；改英文定位文案会触发 6169 键字典失配回退（I05，wp-handoff §6.1），成本极高
- **处置方式**：**保留现站**定位文案与获客链路（六步流程、/inquiry/、Get a Quote CTA、WhatsApp 预填）；复刻"直营供应商"叙事不搬
- **适用范围**：全站定位类文案与 CTA 体系
- **划界**：复刻经营数字（2,400+ 等）归**必须重做**；本行只管现站口径保留

### R02｜价格口径与展示体系
- **对照表**：G01、G03、G06
- **现站证据**：/product-magnetic-jewelry-box/ "**Reference price US$0.27–0.98 per box. Example: a 9.5×9.5×4 cm flip-lid box is about US$0.54 at 1,000 pcs and US$0.52 at 2,000 pcs. Stock styles, ex-works China, before logo printing, freight and duties (October 2026).**"；"This is a packaging concept, not a production offer."；"Request the box or pouch, insert where needed, decoration, sampling, packing and delivery as separate cost items."
- **复刻侧冲突点**：`data/products.ts` `from` 手写价与 `fromPrice()` 模型价两套并存（G03）；产品页「From $X」价卡与阶梯价表
- **可核验差异**：现站=参考区间+example+免责+逐项报价；复刻=即时价展示（数字自建模型产物）
- **不建议覆盖原因 + WP 约束**：现站区间价是站长确认口径（README §0：P01 $0.54@1000/$0.52@2000）；数字口径受 `packrivo-site-remediation-20261022.php` 管辖（I07，wp-handoff §6.6）；即时价展示在代理模式下制造承诺风险
- **处置方式**：**保留现站**产品页价格展示（区间+example+免责+逐项报价口径）；复刻「From $X」即时价 UI 与 /pricing 不覆盖现站价格展示
- **适用范围**：全站产品页价格块与询盘价格口径
- **划界**：定价公式迁移归**当天清单**（只迁可复现计算逻辑、明确报价口径待校准）；基准与 tier 系数替换归**必须重做**；本行=现站价格展示保留

### R03｜MOQ 与交期口径
- **对照表**：G04、G05
- **现站证据**：首页 facts 条 "500 pieces MOQ per design"（home.html 已复核）；产品页 "MOQ 500 pieces per design."；"Stock styles typically ship in 1–15 days; printed custom orders are scheduled after sample approval."；/compare/ Packrivo 行 "Stock jewelry boxes 1–15 days; custom printed boxes about 5–30 days after approval"
- **复刻侧冲突点**：per-SKU MOQ 500/1000/5000（mark-roll-label 5000、ridge-poly-mailer 1000 等）；lead 7–10 至 15–20 d + rush +22%；marquee "10–14 day production"
- **可核验差异**：复刻差异化 MOQ 与现站 500 口径直接冲突且未经核实（G04 ⚠️ 证据缺口：现站 poly mailer 页 MOQ 未抽查）；rush 概念现站无对应
- **不建议覆盖原因 + WP 约束**：现站 500 件/design 为站长确认口径、全站一致（current-site-evidence §15.2）；数字改动受 remediation 插件管辖（I07）
- **处置方式**：**保留现站** MOQ/交期表述；复刻 per-SKU MOQ、rush、固定 lead 宣称不采用
- **适用范围**：全站 MOQ/交期表述
- **划界**：若未来逐 SKU 核真后要采用复刻差异化 MOQ，属**必须重做**（逐 SKU 核实+改口径）；本行=现站口径保留

### R04｜认证口径（零背书 + 证书核验教育）
- **对照表**：E04、F01、F02
- **现站证据**：全站不宣称任何认证（current-site-evidence §15.5）；产品页统一 "Supplier confirmation needed"；博客《Packaging Supplier Certificates Need a Site and Scope Check》教买家按 entity/site/scope/status 核验证书
- **复刻侧冲突点**：`components/SiteFooter.tsx` 徽标数组 `['FSC® C168842', 'ISO 9001', 'BPI compostable', 'SEDEX member']`（已复核）+ /sustainability marquee + 四认证卡 + 产品 bullet（FDA 21 CFR、EU 10/2011、ASTM D6400、ISTA）
- **可核验差异**：现站零背书=合规现状；复刻密集徽标且编号为 README §7 自认占位
- **不建议覆盖原因 + WP 约束**：直接搬徽标=上线虚假认证宣称；现站"教买家核验证书"的内容立场是差异化资产。WP 侧：询盘验收脚本保护词白名单含 FSC 等认证词（wp-handoff §6.7），认证属敏感口径
- **处置方式**：**保留现站**零认证背书口径与证书核验教育内容；复刻认证徽标/编号展示块整体不搬
- **适用范围**：全站认证相关内容
- **划界**：徽标与编号的核真替换归**必须重做**（仅当取得真实证书后才另行决定是否展示）；本行=现站口径保留+展示块不搬

### R05｜案例口径与 AI/概念图披露体系
- **对照表**：D03、E01、E02、E03
- **现站证据**：/cases/ "Concept visuals. They explain packaging decisions and sample questions; **they are not completed client projects or measured production results.**"（cases.html 已复核）；每卡 "AI-assisted concept visualization. Not a physical sample."；产品卡 "AI-assisted concept · Not a physical sample"；/about/ "AI tools assist with drafting, editing and concept diagrams… illustrative examples are not records of customer orders."
- **复刻侧冲突点**：`app/work/` 6 案例带 metrics（0.0% breakage、−71% launch cost 等）+ 首页 3 条署名评价（README §7 自认占位虚构）
- **可核验差异**：现站诚实披露 vs 复刻无披露+虚构数据
- **不建议覆盖原因 + WP 约束**：现站披露体系是信任与合规资产；复刻案例/评价模块承载虚构数据。WP 侧：披露文案改动触发字典失配回退（I05）
- **处置方式**：**保留现站**案例呈现（brief→questions→sample plan 结构）与全站逐图标注披露；复刻 /work 与首页评价区块不搬
- **适用范围**：全站案例/评价/概念图标注
- **划界**：虚构客户名/metrics/评价的替换归**必须重做**（仅当获得真实案例与授权评价后另行决策）；本行=现站口径保留+模块不搬

### R06｜目录规模与分类体系（134 vs 18）
- **对照表**：C01、C02
- **现站证据**：/products/ title "Custom Packaging: 134 Styles, MOQ 500 | Packrivo"；6 页分页 25 条/页；6 类目页（jewelry-boxes/foldable-gift-boxes/mailer-boxes/labels/inserts/bags-pouches）
- **复刻侧冲突点**：18 SKU 客户端筛选 + 5 类目（见 N08）
- **可核验差异**：134 vs 18；终端品类 vs 业务场景分类
- **不建议覆盖原因 + WP 约束**：134 目录含 119 个 i18n 白名单产品页（wp-handoff §1.8），是现站最大 SEO 资产；复刻 meta "Twelve packaging structures" 自相矛盾，不能作为规模口径
- **处置方式**：**保留现站** 134 目录与类目体系；复刻 18-SKU 目录不作为主结构（镜像 N08）
- **适用范围**：全站产品目录
- **划界**：134↔18 映射表为前置工作（证据缺口④，本清单未据推测映射任何 SKU）

### R07｜现站独有 SEO/内容资产（整组保留）
- **对照表**：A04、D02、D07、D08、H01、H02、H03、H04、H05、H06
- **现站证据**（逐项）：/blog/ 315 篇（5 主题 96/127/110/101/47）；/compare/ 5 家对比表 + 4 个 packrivo-vs-* 子页（Sources "checked on their own pages on October 22, 2026"）；/guides/ 10 个 CSV 工作表下载 + 3 篇长指南；/services/、/samples/、/customization/；每页 13 条 hreflang + Yoast schema-graph + sitemap_index 14 子图；robots AI 爬虫白名单（"AI search and assistant crawlers are welcome."）；msvalidate.01 7699F361E356E1EE4A52E684980ED9A0
- **复刻侧对应**：无博客（10 指南替代）、无 /compare/、无 worksheet、无多语言 sitemap（D08/A04/D02 ➖ 或缺失）
- **不建议覆盖原因 + WP 约束**：这些是现站真实资产，复刻无对应物，迁移时不得以复刻页面结构删除或替换。注意：/samples/、/services/、/blog/、/terms/ 不在 i18n 144 白名单（current-site-evidence §0）——它们英文正常、无翻译版，是现状而非缺陷，不要误删或误"补"
- **处置方式**：**保留现站**整组；AI 爬虫白名单、msvalidate、hreflang/schema 为已验证资产，继续由 Yoast+mu-plugin 输出
- **适用范围**：全站
- **划界**：无（纯保留项）

### R08｜多语言资产与路由机制
- **对照表**：I01、I02、I03、I04、H02
- **现站证据**：11 语种 × 6169 全量译文（wp-handoff §1.5/§1.6 已核实 11/11 命中）；144 条路径白名单 + 16 条常量回退；13 条 hreflang；语言选择器 1.0.7（搜索/alias/免责）；ar→rtl；每语种 sitemap
- **复刻侧对应**：7 语种 cookie i18n 仅 chrome（N01）
- **不建议覆盖原因 + WP 约束**：现站路径多语言是 SEO 正确解；复刻 cookie 方案不可覆盖（见 N01）。新增语种需改 mu-plugin 枚举+投 6169 键字典（wp-handoff §6.8），维护成本高，因此现站 11 语种结构必须原样保留
- **处置方式**：**保留现站** mu-plugin 路径多语言全套；复刻 i18n 不搬（镜像 N01）
- **适用范围**：全部语言路由/字典/选择器
- **划界**：复刻 "11 languages" 失实宣称修正归**必须重做**

### R09｜页脚与联系方式
- **对照表**：A02、F05
- **现站证据**：页脚三栏——Explore（Jewelry boxes/Gift boxes/Mailer boxes/Labels/Inserts/Bags & pouches）；Contact Packrivo（hello@packrivo.com、WhatsApp +86 198 9984 6512、About、Services、Get a Quote）；法律栏（Privacy & inquiry data、Terms of service、Compare suppliers、Cookie settings、Packaging guides）；品牌行 "Packrivo · Shenzhen, China"、"Custom packaging sourcing. Clear specifications. Project coordination."
- **复刻侧冲突点**：SiteFooter 3 列 + 认证徽标（N01/R04）+ newsletter 表单（POST /api/leads）+ Admin 链接
- **可核验差异**：现站页脚无认证徽标、有 WhatsApp/Compare/Cookie 链接；复刻有徽标、newsletter、Admin 链接
- **不建议覆盖原因 + WP 约束**：现站页脚真实合规（A02 判断"保留"）；复刻徽标占位、newsletter 依赖不可平移的 /api/leads、Admin 链接属排除项
- **处置方式**：**保留现站**页脚三栏与品牌行；复刻页脚不覆盖（徽标不搬、newsletter 不搬、Admin 链接排除）
- **适用范围**：全站页脚
- **划界**：徽标核真归**必须重做**；newsletter 若未来启用需配套隐私政策，非本清单决策

---

## 第三部分 · 划界确认与证据缺口

**三清单分工**（与 i318/i319 产出对齐，防止重复）：
| 清单 | 判定标准 | 本清单对应关系 |
|---|---|---|
| 当天可迁移 | 原样可搬（设计令牌/文案骨架/SEO 组织/定价公式） | N06/N08 的"仅参考"部分指向该清单 |
| 必须重做 | 替换真实材料后可用且会被搬 | N03/N05/N07/R02/R04/R05 的数值与素材 |
| 不建议迁移（本清单） | 替换与否都不搬 / 现站保留不被覆盖 | N01–N08、R01–R09 |

**证据缺口**（对照表 §5，本清单未据缺口推测任何条目，均按"现站口径为准/不搬"保守处置）：
1. 复刻首页 FAQ 原文未记录（B05 ⚠️）→ 不影响本清单任何排除/保留决策
2. 现站 poly mailer 页 MOQ 未抽查（G04 ⚠️）→ R03 以全站一致的 "MOQ 500 pieces per design" 口径为准
3. 现站英文 404 文案未抓取（A06 ⚠️）→ N06 仅排除复刻 404 机制，不涉及现站文案搬运
4. 134↔18 SKU 映射表缺失（C03 ⚠️）→ N08/R06 因此对复刻目录只做"不作为主结构"的排除，不做任何映射假设
5. `packrivo-site-remediation-20261022.php` 线上源码不在包内（I07 ⚠️）→ N03/R02 的数字决策以 README §0 已断言口径（MOQ 500、P01 $0.54@1000/$0.52@2000、1–15 天）为准
6. 现站独立搜索结果页未存档（A05 ⚠️）→ N07 /search 排除理由不依赖该证据
