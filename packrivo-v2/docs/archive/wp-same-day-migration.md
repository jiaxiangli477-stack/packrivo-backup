# packrivo.com · 当天可迁移到 WordPress 清单（wp-same-day-migration.md）

> 生成：2026-10-26 · 只读编制，未修改现站与复刻站源码。
> 依据（只读）：`comparison-matrix.md`（稳定编号 A01–I07）、`current-site-evidence.md`（现站抓取 2026-10-26）、`replica-source-inventory.md`（E:\破甲工作区\packrivo-v2 源码审计）、`wp-handoff-constraints.md`（E:\破甲工作区\packrivo-handoff 交接包核实）；并回查原始存档 `tasks/30/i295/pages/home.html` 与源码 `app/globals.css`、`app/page.tsx`、`lib/i18n.ts`、`data/products.ts`、`data/industries.ts`、`data/resources.ts`、`lib/pricing.ts` 核对原文。

## 0. 口径与边界（本清单的规则）

- **"当天"= 1 人 8 工作小时**。每项给出工时；§5 给出 8h 优先级计划，其余为同天级备选队列（顺延执行，无需跨日等待）。
- **只迁移两侧真实、且与现站业务口径不冲突的内容**。对照表结论 2 已划界：复刻侧可直接搬的 = 设计令牌、组件/页面结构、英文文案骨架、定价公式与参数表、SEO 模板组织方式、404/搜索字段映射、favicon/BoxRender SVG（favicon 因 logo 待核列入阻塞 §6）。
- **占位价格/虚构声明一律不进当天项**：复刻 FAQ 的 29 USD、rush +22% 对外承诺、setup 220+120 生效值、2,400+ brands、FSC C168842 等全部归「必须重做」清单（i319）或阻塞项（§6），本清单只搬其结构/逻辑。
- **定价公式**只迁移可复现计算逻辑（公式+参数表），所有基准数值标「报价口径待校准」，校准锚点 = 现站 G01 已确认口径（P01 $0.54@1,000 / $0.52@2,000、区间价、MOQ 500）。
- **本清单是执行规格，不是执行**：每条「迁移操作」为精确步骤描述；本任务不实际修改现站/源码。

## 0.1 全局前置依赖（所有条目的公共前提）

| # | 前置 | 状态与出处 |
|---|---|---|
| G0-1 | SSH 通道：`remote.py`（密钥 `E:\破甲工作区\.ssh\packrivo_ed25519`，主机 188.68.36.93:25251 root；容器路径 `/home/web/html/www.packrivo.com/wordpress/...`） | wp-handoff §1-14/§2；未实测连通（§10-7） |
| G0-2 | **CF 缓存清除凭据 `.cf.env`（CF_API_TOKEN+CF_ZONE_ID）当前缺失**（P0-2）。字典/页面类改动部署后需 `cf_purge.py` 才对外可见 | wp-handoff §6-9/§8；P0-2 |
| G0-3 | 数字口径管辖：`packrivo-site-remediation-20261022.php` 线上源码本包无（wp-handoff §1-15/§6-6）。**任何价格/MOQ/交期数字的迁移在取到该源码前为阻塞**（I07） | wp-handoff §10-5 |
| G0-4 | 主题文件（site.css/header/404.php）编辑通道未在交接包内文档化：需先 `remote.py get` 线上现状再改（服务器写权限属 §10-3 未核实项） | wp-handoff §10-3 |
| G0-5 | 业务模式决策（采购代理 8–15% vs 直营供应商，D06/B01/G02 冲突）未定前，涉及对外文案替换/价格展示的条目默认不执行 | comparison-matrix 结论 1 |
| G0-6 | uploads/ 下新增 JSON 需 chown 82:82（与 paths.json 同 UID:GID） | wp-handoff §2 |

---

## 1. 设计令牌（对照表依据：B01/B03/C02 视觉载体 + 复刻 globals.css 全套）

### D1 · 色彩令牌（12 色）→ site.css CSS 变量

- **对照表**：E01（复刻视觉体系）、B01（现站 hero 视觉）
- **复刻站证据**：`app/globals.css` L4–15 `@theme`：`--color-ink:#0c1411; forest:#0b2b20; moss:#0d4836; fern:#145c45; lime:#b89553; lime-soft:#d8bd8b; bone:#f3f0e7; sand:#e2ddd0; paper:#fdfcf8; rust:#a8401f; clay:#b9a88c; slate:#5b615b`（源码直读核实）
- **现站证据**：现站无公开令牌层，样式直写于 `https://www.packrivo.com/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/site.css?ver=1792937182`（home.html 存档 `link id='packrivo-site-css'`）——「原文片段」不适用：现站没有命名令牌，D1 是净新增抽象层。
- **WP 落地**：`wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/site.css`（文件尾追加 `/* packrivo-v2 tokens */` 区块）
- **迁移操作**：① `remote.py get` 现站 site.css 备份；② 把 12 色写成 `:root{--color-ink:…;…}`（CSS 自定义属性，与 Tailwind 无关，WP 直接可用）；③ 追加回 site.css；④ `remote.py put` 回传；⑤ 若 CF 缓存 HTML 页面需 purge（G0-2）。
- **工作量**：0.5h（12 行纯文本，证据已在手）
- **前置依赖**：G0-1、G0-4、G0-2（可见性）
- **验收**：线上 site.css 含 12 个 `--color-*` 变量且值逐一等于上表；现站视觉零变化（变量未被引用即不生效）

### D2 · 字体令牌（display=sans 现站已有，新增 Archivo + JetBrains Mono）

- **对照表**：B01（复刻 hero 排版）、E01（字体为原创资产，无侵权）
- **复刻站证据**：`app/globals.css` L17–19：`--font-display:"Archivo"…; --font-sans:"Inter Tight"…; --font-mono:"JetBrains Mono"`；L34–96 `@font-face` 引 `public/fonts/archivo-600/700/800.woff2`、`inter-tight-400/500/600/700.woff2`、`jbmono-400/500.woff2`（**instrument-serif-400/italic.woff2 为死文件，不搬**，replica-inventory §4）
- **现站证据**：现站已自托管 `…/packrivo-static/assets/packrivo-inter-latin-v3.woff2`（可变字重 100–900，home.html 内联 `#packrivo-w2-font` @font-face「Packrivo Inter」）→ **Inter Tight 不迁移，直接映射到现站 Inter**，只补 Archivo（display）与 JB Mono（mono）。
- **WP 落地**：woff2 五文件 → `wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/`；@font-face 追加进 site.css（或仿现站内联 style 注入 header，二选一，推荐 site.css）
- **迁移操作**：① 复制复刻 `public/fonts/archivo-600/700/800.woff2`、`jbmono-400/500.woff2`（5 个，共约 ≤300KB）；② site.css 追加 5 条 `@font-face`（font-display:swap）+ `:root{--font-display:"Archivo","Packrivo Inter",sans-serif; --font-mono:"JetBrains Mono",monospace}`；③ 上传 + purge。
- **工作量**：0.5h
- **前置依赖**：G0-1、G0-4、G0-2
- **验收**：`curl -I https://www.packrivo.com/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/archivo-700.woff2` 200；Font-family 声明存在

### D3 · 圆角/阴影/动效令牌

- **对照表**：B03/C04（卡片与详情页视觉）
- **复刻站证据**：`app/globals.css` L21–31：`--radius-xs:2px … xl:10px`；`--shadow-card: 0 1px 0 rgba(16,19,18,.04), 0 18px 40px -30px rgba(16,19,18,.35)`；`--shadow-pop: 0 32px 80px -48px rgba(16,19,18,.5)`；`--animate-marquee: marquee 38s linear infinite`；`--animate-rise: rise .7s cubic-bezier(.16,1,.3,1) both` + L98–116 keyframes
- **现站证据**：不适用（同 D1：现站无令牌层，圆角/阴影直接写在 site.css 具体选择器里）
- **WP 落地**：site.css 追加 `:root` 变量 + 两个 keyframes
- **迁移操作**：与 D1 同批次追加（同一文件、同一次 put）
- **工作量**：0.25h（并入 D1 批次）
- **前置依赖**：同 D1
- **验收**：site.css 含上述变量与 keyframes；无视觉变化

### D4 · 组件类（.wrap/.eyebrow/.display/.h-hero/.btn/.badge…）

- **对照表**：B01/B03/B06/C04（复刻组件视觉的类体系）；I05（类名是 CSS 不是文案，无字典影响）
- **复刻站证据**：`app/globals.css` 后半部组件层（源码直读核实存在）：`.wrap`(max-w-1320px)、`.eyebrow`(mono 10.5px+24px 横线)、`.display`、`.h-hero`(clamp 2.3–4rem/leading .94)、`.h-section`(clamp 1.9–3.1rem)、`.h-card`、`.lede`(1.02rem/1.65 slate)、`.btn/.btn-primary(bg-ink→hover moss)/.btn-lime/.btn-ghost/.btn-light`、`.badge`(mono 10px uppercase)、`.grain`、`.link-underline`、`.stripe`、`.field/.label`；h1–h3 letter-spacing -0.025em+text-wrap balance；`:focus-visible` outline 2px moss；prefers-reduced-motion 降级
- **现站证据**：不适用——现站用主题自有类名体系，**类名不冲突不替换**，仅新增 `pv2-` 前缀副本备用（避免与现站 .btn 等撞名）
- **WP 落地**：site.css 追加 `/* pv2 components */` 区块（全部类名加 `pv2-` 前缀，如 `.pv2-wrap`）
- **迁移操作**：① 从 globals.css 提取组件层规则并重命名加前缀；② 追加 site.css；③ 注明「仅备用，不在未改 HTML 前启用」
- **工作量**：1h（提取+改前缀+校验无全局选择器污染）
- **前置依赖**：同 D1
- **验收**：site.css 含 `.pv2-wrap/.pv2-eyebrow/.pv2-btn` 等；现站无视觉变化（未被引用）

### D5 · 产品/行业/案例逐条色板（数据色值）

- **对照表**：C02/B03（复刻每 SKU/行业/案例的 color+accent 是产品级视觉资产）
- **复刻站证据**：`data/products.ts` 每条 `color/accent`（如 ridge-roll-end-mailer `#8a5a2b`/`#d9f26b`，halo-magnetic-box `#1d3a2c`/`#d9f26b`，源码直读核实）；`data/industries.ts` 每条 `color`（beauty `#c4552d`、apparel `#1b5e43`、jewelry `#8a6a3b`…）
- **现站证据**：不适用——现站产品页无 per-SKU 色板体系
- **WP 落地**：`wp-content/uploads/packrivo-skeleton/palette.json`（18 SKU + 8 行业 slug→color/accent 映射，82:82）
- **迁移操作**：node 脚本从 data/*.ts 导出 JSON 校验后 `remote.py put`；与 C1 同批次
- **工作量**：0.5h（含导出脚本）
- **前置依赖**：G0-1、G0-6
- **验收**：palette.json 条目数 26 且色值逐一与源码一致

---

## 2. 文案骨架（对照表依据：C01–C04、D01–D03、A06）

> 口径说明：本清单迁移的是**字段化骨架**（结构+英文原文+字段 schema），作为后续填充现站页面的内容源。挂到现站 134 产品页/新页面的**填充动作**因 I05/I06 流水线与 C03 映射缺失（阻塞 §6-B1）不属当天范围；骨架 JSON 落地 + 内容模型文档属当天范围。

### C1 · 18 SKU 文案骨架（字段 schema + 英文原文）

- **对照表**：C01/C02/C03/C04（SKU 字段体系）
- **复刻站证据**：`data/products.ts` 字段：slug/name/series(6 系 Ridge·Halo·Fold·Seal·Loop·Mark)/style(9 型)/category(5 类)/tagline/summary/from/moq/lead/eco/badge/bullets/industries/sizes(3–4 档 label+l×w×h)/stocks/finishes。样本原文（源码直读 L27–57）：ridge-roll-end-mailer tagline "Folds in four seconds, survives the network"、bullets["E-flute corrugated, 32 ECT, ISTA 1A drop tested",…]；halo-magnetic-box（L149–178）tagline "The unboxing that gets filmed"
- **现站证据**：现站产品页为散文体（/product-magnetic-jewelry-box/：H1 "Custom Rigid Magnetic Jewelry Box Sourcing"、Quote Details 表含 MOQ/Common sizes/Construction/Finishes/Lead time/Reference price），无字段化数据层——复刻骨架是结构化增强，需与现站 134 页映射（C03 映射表缺失→阻塞 B1）
- **WP 落地**：`wp-content/uploads/packrivo-skeleton/products.json`（18 条全字段，**剔除 `from` 字段**——双口径冲突 G03，数值归 i319）+ 内容模型说明（哪些字段对应现站产品页哪些模块）
- **迁移操作**：node 脚本从 products.ts 导出（strip `from`）→ JSON 校验（18 条、字段齐全、无 HTML 标签）→ put → chown 82:82
- **工作量**：1h（导出+strip+校验）
- **前置依赖**：G0-1、G0-6；**不依赖 C03 映射**（骨架落地与映射解耦）
- **验收**：products.json 18 条；`from` 字段不存在；每 slug 的 name/series/category 与源码一致

### C2 · 8 行业文案骨架

- **对照表**：D01（行业页净新增）
- **复刻站证据**：`data/industries.ts` 字段 kicker/summary/stats×3(value+label)/challenges×2(title+body)/products×4/color；样本（源码直读 L14–36）：beauty kicker "Regulated, photographed, gifted"、summary "Serums ship in glass, get photographed under ring lights, and arrive in a parcel that has to survive a 40 °C van.…"、challenge "Glass in transit"/"Claims on pack"
- **现站证据**：无行业维度页面（D01 ➖），「现站原文」不适用：净新增内容
- **WP 落地**：`wp-content/uploads/packrivo-skeleton/industries.json`
- **迁移操作**：同 C1（stats 的 value 数字如 "1 in 3"/"26 wk"/"0.5 mm" 保留原文但**整条标记 `verify:true`**，不视为已核实声明）
- **工作量**：0.5h
- **前置依赖**：G0-1、G0-6
- **验收**：8 条齐全；每条 stats/challenges 带 verify 标记

### C3 · 10 指南内容骨架

- **对照表**：D02（指南）、F04（机构署名可合并）
- **复刻站证据**：`data/resources.ts` 字段 slug/title/excerpt/category(5 类)/readMins/date/author/sections(heading+paragraphs+list?)；样本（源码直读 L20–59）：choosing-a-box-style title "How to choose a box style without overpaying"、author "Packrivo studio"；正文含硬数字骨架（-15%、1.42 USD@500、盈亏点 480 g 等——**数字为待核声明，骨架 JSON 保留原文并整段标 verify**，不进任何对外页面）
- **现站证据**：/guides/ 现为 5 类 10 个工作表 + 3 长指南（D02）；复刻 10 长文是净新增内容源。现站原文片段（guides 页标题）："Packaging Guides & Buyer Worksheets"（evidence §7）
- **WP 落地**：`wp-content/uploads/packrivo-skeleton/resources.json`（全文骨架；导入现站博客属跨日任务，见 I06 工作量口径：新增键 N×11）
- **迁移操作**：同 C1 导出+校验（**sections 内数字与价格一律加 verify 标记；作者署名沿用 "Packrivo studio" 与现站 F04 机构口径一致，可直接用**）
- **工作量**：1h（10 篇全文导出校验量较大）
- **前置依赖**：G0-1、G0-6
- **验收**：10 条齐全；sections 结构完整；价格类数字带 verify 标记

### C4 · 案例叙事结构（不含客户名/metrics）

- **对照表**：D03（案例）、E02（虚构数据剔除）
- **复刻站证据**：`data/case-studies.ts` 结构：client·sector·year+headline+3 metrics 横条+The brief/What we changed/Outcome+Structures used（replica-inventory §5.4）；**客户名（Aurea 等）与 metrics 为 README §7 自认虚构 → 只搬"brief→what we changed→outcome"叙事模板，不搬任何客户名/数字**
- **现站证据**：/cases/ 4 例诚实口径（"…they are not completed client projects or measured production results."，evidence §8）——叙事模板可与之对齐，替代方案=现站案例结构本身已够用（D03 结论：现站结构可搬、复刻仅借鉴 section 命名）
- **WP 落地**：`wp-content/uploads/packrivo-skeleton/case-template.md`（叙事模板文档，字段：The brief/What we changed/Outcome/Structures used，标注"客户名与 metrics 禁用占位值"）
- **迁移操作**：手写模板文档（0.5 页）+ put
- **工作量**：0.25h
- **前置依赖**：G0-1
- **验收**：模板不含任何虚构客户名/百分比数字

### C5 · 404 页英文文案（复刻品牌文案）

- **对照表**：A06（404 文案）
- **复刻站证据**：`app/not-found.tsx`：「This page went out flat-packed」+ Browse structures / Build a quote
- **现站证据**：现站英文 404 文案**未抓取**（matrix 证据缺口 ③）→ 替换前必须先取现站 404 模板现状，属前置动作非阻塞（SSH get 即可当天解）
- **WP 落地**：`wp-content/themes/packrivo-static-bridge-1.2.38/404.php`（英文 404 文案区块；**不改白名单判定机制**——i18n 404 逻辑在 mu-plugin，与本条无交集）
- **迁移操作**：① `remote.py get` 现站 404.php；② 仅替换英文文案为复刻两句+两个按钮（按钮链接指向现站 /products/ 与 /inquiry/，**不指向 /quote/**——该路由在复刻站且价格未校准）；③ put 回传
- **工作量**：0.5h（含取件）
- **前置依赖**：G0-1、G0-4、G0-2
- **验收**：访问不存在的英文路径显示新文案；`/de/` 下不存在路径仍走专用 i18n 404（noindex,follow 不变）

### C6 · 导航/页脚结构差异映射表（作为后续改造规格，不直接改）

- **对照表**：A01/A02（导航页脚差异）
- **复刻站证据**：`components/SiteHeader.tsx` 6 项扁平导航+搜索+语言切换；`components/SiteFooter.tsx` 3 列+认证徽标（徽标占位→i319）
- **现站证据**：现站 header 10 项两层下拉（home.html 存档），页脚三栏含 WhatsApp +86 198 9984 6512 与 "Packrivo · Shenzhen, China"（evidence §1）
- **WP 落地**：`wp-content/uploads/packrivo-skeleton/nav-map.md`（A01/A02 逐项映射：现站入口 ↔ 复刻入口，标注"保留现站结构，复刻缺 Samples/Services/Blog/Get a Quote 入口"）
- **迁移操作**：文档 + put；**导航本身不动**（A01 结论：现站结构保留）
- **工作量**：0.5h
- **前置依赖**：G0-1
- **验收**：映射表覆盖 A01 全部 10 项与 A02 三栏

### C7 · Hero/眉标备选文案（业务决策前置，默认不进当天执行）

- **对照表**：B01（hero 文案）
- **复刻站证据**：`lib/i18n.ts` 英文原文（源码直读）：`hero.eyebrow:'Custom packaging · MOQ 500 · 11 languages'`（**"11 languages" 与实现不符 I01 → 若启用必须改词**）、`hero.title.a:'Packaging that earns'`、`hero.title.b:'its place in the parcel'`
- **现站证据**：现站 H1 "Custom packaging for jewelry, candles & gifts."（home.html 存档）——现站文案真实且在位，B01 结论为现站保留
- **WP 落地**：若启用：首页模板文案替换 → **触发 I05 流水线**（改英文键→11 语种重译→部署→CF purge）；落地文件 = 主题首页模板 + `data/source-v3.json` 重抽取 + `dict3/{lang}-dict.json` + uploads 部署
- **迁移操作**：本清单只记录备选文案与流水线路径，**不执行**（G0-5 业务决策未定；"11 languages" 词需先行修正）
- **工作量**：若执行约 2h（10 键×11 语种翻译+部署，依据 wp-handoff §7 口径：新增键 N×11、并发 ≤9）；**本清单工时不计入当天计划**
- **前置依赖**：G0-5 业务决策、G0-2 CF purge、I01 修正
- **验收**：英文与 11 语种首页标题同步更新且无英文残留（residual_en.py 校验）

---

## 3. SEO 结构（对照表依据：H01–H07）

> 口径：H01/H02/H04/H05 结论均为「现站体系保留」。当天可迁移的复刻 SEO 结构仅为不冲突的增量件。

### S1 · theme-color meta（视口主题色）

- **对照表**：H06（图标与 meta）、D1（moss 色与复刻主题一致）
- **复刻站证据**：`app/layout.tsx` viewport themeColor=`#0d4836`（= moss，replica-inventory §3）
- **现站证据**：home.html 存档 head 无 `<meta name="theme-color">`（grep 无命中）——现站缺失，净新增
- **WP 落地**：主题 `header.php` head 区追加一行 `<meta name="theme-color" content="#0d4836">`
- **迁移操作**：get header.php → 追加 → put
- **工作量**：0.25h
- **前置依赖**：G0-1、G0-4
- **验收**：首页 head 含该 meta

### S2 · Organization JSON-LD 的 areaServed/email/logo 增量（剔除 LinkedIn）

- **对照表**：H03（结构化数据）、F05（LinkedIn 待核）
- **复刻站证据**：`app/layout.tsx` Organization JSON-LD：name/url/logo(https://www.packrivo.com/favicon.svg)/email hello@packrivo.com/areaServed 8 国(US,CA,GB,DE,FR,NL,AU,JP)/sameAs LinkedIn（replica-inventory §3）
- **现站证据**：现站 Yoast schema-graph 已有 WebSite/SearchAction 节点（home.html 存档），但**无 Organization 节点的 areaServed 结构**——增量借鉴；现站邮箱已确认 hello@packrivo.com（页脚原文，evidence §1）
- **WP 落地**：Yoast 后台 Organization/Publisher 设置（`wp-admin` SEO→Settings）或 theme functions 挂 `wpseo_schema_organization`；**sameAs LinkedIn 一律不填**（F05 待核）；logo 用现站已有 `packrivo-logo-356.webp`（home.html 存档存在），不用复刻 favicon.svg（logo 待核→阻塞 B3）
- **迁移操作**：① Yoast 设置 Organization：name=Packrivo、email=hello@packrivo.com、logo=packrivo-logo-356.webp、areaServed=US/CA/GB/DE/FR/NL/AU/JP；② 验证 schema 输出
- **工作量**：0.5h
- **前置依赖**：G0-1（若走 functions 文件）、WP 后台访问
- **验收**：首页 schema-graph 出现 Organization 节点，areaServed 8 国；无 LinkedIn 字段

### S3 · 404 文案（=C5，SEO 角度：无 schema 影响，noindex,follow 机制不变）

- 同 C5，不重复计工时。

### S4 · 不迁移的复刻 SEO 件（显式否决 + 原因，供对照）

| 复刻件 | 处置 | 原因（对照表） |
|---|---|---|
| 复刻 title/description 模板「instant pricing / FSC-certified stocks」 | **不迁移** | H01：与现站 sourcing 定位冲突且含未核声明；现站 Yoast 模板保留 |
| 复刻 cookie 语言切换 | **不迁移** | H02/I03：URL 不分语种对 SEO 无效，现站路径多语言保留 |
| 复刻 sitemap 53 条/robots allow-all | **不迁移** | H04/H05：现站 Yoast sitemap 14 子图 + AI 爬虫白名单保留；复刻 disallow /quote/ 思路仅当 /quote/ 页上线时参考 |
| 复刻 Article JSON-LD（指南页） | 随 C3 骨架存档 | H03：待指南导入 WP 时使用（跨日） |

---

## 4. 定价公式（对照表依据：G02/G03/G01；只迁移可复现计算逻辑）

> **口径声明（G01/G02 结论）**：公式与参数表结构可搬；**一切基准数值属「报价口径待校准」**——校准锚点 = 现站已确认口径（README §0：P01 $0.54@1,000/$0.52@2,000、MOQ 500/款、现货 1–15 天；产品页原文 "Reference price US$0.27–0.98 per box… (October 2026)"，evidence §3.1）。校准动作须先取 remediation php（G0-3），不在当天范围。

### P1 · 定价公式 PHP 参考实现（仅内部函数，零前台输出）

- **对照表**：G02（公式可复现）、I07（数字口径受 remediation 管辖——本件不触碰任何线上数字）
- **复刻站证据**：`lib/pricing.ts`+`lib/catalog.ts`（源码直读核实）：
  ```
  unit = (styleBase × materialFactor × sizeFactor + printAdd + finishAdd) × tierFactor × rushFactor + setup/qty
  sizeFactor = (表面积/参考面积)^0.6；参考 = 2×(30×20+30×10+20×10) = 2200 cm²
  tierFactor: 500→1.00, 1000→0.93, 2500→0.85, 5000→0.78, 10000→0.72, 25000→0.66（档间线性插值；qty>25000→0.64；qty<500 按 500 夹取）
  setup = printed ? (qty≥2500?0:220)+(qty≥5000?0:120) : 0
  rushFactor = 1.22
  STYLE_BASE: mailer 1.42/product 1.18/shipping 1.02/rigid 3.40/carton 0.74/pouch 0.46/poly 0.62/bag 0.92/sticker 0.16
  MATERIALS.factor: kraft 1.00/white 1.06/recycled 1.10/corrugated 1.22/rigidboard 1.75/kraftpoly 0.92/compostable 1.38
  PRINTS.add: none 0/one-color .09/full-color .18/full-color-spot .29
  FINISHES.add: none 0/matte .06/gloss .05/soft-touch .13/spot-uv .16/foil .35/emboss .24
  ```
  复算锚点：mailer 30×22×12 kraft full-color matte qty=1000 → ≈2.01（replica-inventory §6 与 hero 卡一致）
- **现站证据**：现站无公开公式（G02），仅区间价原文（见上口径声明）——「原文片段」为校准锚点而非被迁移对象
- **WP 落地**：`wp-content/mu-plugins/packrivo-pricing-model.php`（新增自研 mu-plugin：纯函数 `packrivo_unit_price(array $input): float`，**无任何输出钩子**，不渲染前台）+ `wp-content/uploads/packrivo-pricing/params.json`（STYLE_BASE/MATERIALS/PRINTS/FINISHES/TIERS 全参数表，82:82）
- **迁移操作**：① 手写 PHP 函数移植公式（幂函数 pow、tier 线性插值、setup 逻辑）；② params.json 导出参数表；③ 内置单元测试：复现 mailer 30×22×12→2.01、与 tier 表五档、rush 1.22、setup 220+120 分支；④ put 两个文件 + chown
- **工作量**：2.5h（含单测；公式仅 ~60 行 PHP，依据：参数已全部在手）
- **前置依赖**：G0-1、G0-6
- **验收**：`php -r` 跑单测全过（mailer 例 ==2.01）；前台 HTML 无任何价格输出（curl 首页 diff 为零）；**所有 STYLE_BASE 基准在 params.json 内标注 `"status":"uncalibrated"`，并附校准锚点注释（P01 $0.54@1000）**

### P2 · 展示口径文档（fromPrice() 约定 + 双口径统一规则）

- **对照表**：G03（复刻双口径冲突必须修）、C04（详情页价卡借鉴）
- **复刻站证据**：`lib/catalog.ts`：`FROM_QTY=2500`；`fromPrice(p)=unitAt(p, max(2500,moq), 0)`；unitAt 用 style 默认料（rigid→rigidboard、pouch/poly→kraftpoly、carton/sticker→white、其余 kraft）+ full-color + matte + 最小尺寸档（replica-inventory §6）——即「from $X = 最小尺寸+默认料+full-color+matte+max(2500,MOQ)」；`data/products.ts` 的 `from` 手写价与 fromPrice() 并存不一致（源码事实 §9.4）
- **现站证据**：现站产品页 title 带区间价（"Magnetic Jewelry Box: US$0.27–0.98 at 500 pcs"，evidence §3.1）——**以现站区间价口径为准**（G01）
- **WP 落地**：`wp-content/uploads/packrivo-pricing/display-convention.md`（文档：展示口径定义、双口径废弃规则——`from` 手写字段作废、统一用模型+校准基准；生效须等 G0-3）
- **迁移操作**：写文档 + put
- **工作量**：0.5h
- **前置依赖**：G0-1
- **验收**：文档含公式口径、锚点、与现站区间价的对齐方式（不给出任何"已校准"数值）

### P3 · （并入 P1）参数 JSON 落地

- 已在 P1 内，不重复计。

### 定价域明确**不当天迁移**的项（防混淆）

- `data/products.ts` 的 `from` 手写价、/pricing 三卡数字（Setup 220+120、Freight 6–9% DDP、Payment 40%）、FAQ 29 USD、rush +22% 对外承诺、任何「From $X」前台展示 → 全部口径待校准或占位，归 i319 与阻塞项；当天只落地逻辑与参数表（标 uncalibrated）。

---

## 5. 当天执行计划（8h 预算，按优先级）

| 顺序 | 条目 | 工时 | 累计 |
|---|---|---|---|
| 1 | P1 定价公式 PHP + params.json + 单测（含 P3） | 2.5h | 2.5h |
| 2 | D1+D3+D4 设计令牌与组件类 → site.css | 1.75h | 4.25h |
| 3 | C1 18 SKU 骨架 JSON | 1h | 5.25h |
| 4 | D2 字体（5 个 woff2 + @font-face） | 0.5h | 5.75h |
| 5 | S2 Organization JSON-LD + S1 theme-color | 0.75h | 6.5h |
| 6 | C2+C3 行业/指南骨架（复用 C1 导出脚本） | 1.5h | 8h |

**备选队列（同天级工作量，顺延或次日，无跨日等待）**：D5（0.5h）、C4（0.25h）、C5 404 文案（0.5h）、C6 导航映射（0.5h）、P2 展示口径文档（0.5h）、C7 hero 备选（2h，仅业务决策+CF 凭据到位后）。

**当天计划总计 8h 的依据**：全部证据（令牌值/公式参数/SKU 字段）已在本地源码与文档中逐项核实，执行阶段为纯搬运/移植，无调研时间；翻译流水线仅在 C7 启用时占用（默认不启用）。

---

## 6. 阻塞项（证据不足或口径未定，不猜测、不当天做）

| # | 阻塞内容 | 出处 | 解锁条件 |
|---|---|---|---|
| B1 | 现站 134 ↔ 复刻 18 的逐项 SKU 映射表未建（C03 ⚠️） | comparison-matrix 证据缺口 ④ | 人工确认映射后才能把复刻 SKU 骨架挂到现站产品页 |
| B2 | `packrivo-site-remediation-20261022.php` 线上源码缺失（I07） | wp-handoff §10-5 | SSH 取线上源码核对数字口径后，任何价格/MOQ/交期数值方可迁移 |
| B3 | 复刻 logo（mark-dark/light、favicon.svg）真实性待核 | replica-inventory §7/§10 | 核真后才可用作 favicon/OG logo；S2 暂用现站 packrivo-logo-356.webp |
| B4 | 复刻 FAQ/样品条款中的 29 USD、+22%、Delta E≤3、3 年留存等数字为占位 | page.tsx L15–40 直读 | 归 i319 必须重做；当天只承认其结构 |
| B5 | 业务模式（代理 8–15% vs 直营）未决策 | comparison-matrix 结论 1 | 决策后 C7/定价前台展示方可推进 |
| B6 | 主题文件编辑通道（site.css/header/404.php 写权限与现状）未核实 | wp-handoff §10-3 | 当天首个动作：remote.py get 核对（0.25h，纳入 D1 前置） |
| B7 | CF purge 凭据 `.cf.env` 缺失（P0-2） | wp-handoff §8 | 补齐凭据后字典/页面改动才对外可见；不阻塞本地/服务器侧落地 |
| B8 | 现站英文 404 文案未抓取（matrix 缺口 ③） | comparison-matrix | C5 执行前 get 404.php 现状（0.25h，可当天解，非硬阻塞） |

---

## 7. 验收总则

- 任何落地动作以「现站前台 diff 为零或仅计划内元数据变化」为底线：设计令牌/组件类/定价函数/骨架 JSON 均不得改变现站渲染输出（D1–D5、P1–P3）；SEO 件仅 S1 meta 与 S2 schema 增量可见。
- 全部 uploads/ 新增文件 chown 82:82；字典/流水线类（C7）部署后跑 `residual_en.py` 与 deploy_v2.py 质量闸（6169/6169、去HTML=0、text/block 一致）。
- 本清单未执行的任何占位价格/虚构声明，在后续清单（i319/i320）与校准动作完成前不得进入前台。
