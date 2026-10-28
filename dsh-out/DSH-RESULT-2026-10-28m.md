# DSH 结果 · 2026-10-28m

任务书：`packrivo-v2/docs/DSH-TASK-2026-10-28m.md`（第 1→4 段）
范围：只本地。没有部署、没有 SSH/SCP、没有碰 `www.packrivo.com`（只读 GET 除外）、没有读取或记录任何密码、没有提交任何真实询盘或报价。
提交记录（`packrivo-backup` → `main`）：

| 段 | commit | 内容 |
|---|---|---|
| 1 | `91766ec` | 首页 8 张产品卡 |
| 2 | `102348d` | 报价页 11 语种 |
| 2 证据 | `4e916ca` | 4 组价格 × 4 语种、ar 375 两图、105 条按钮 href |
| 3 | `b818176` | P2 视觉（手机首屏 / 列表密度 / 控件系统） |
| 4 | `0b36b5e` | 总回归第一轮、上线包、本文件（第一版） |
| 4 终态 | `13c7f1f` | 两处 header 溢出修复、142 页标题最终复验、重打上线包、本文件更新 |

进度与原始输出：`packrivo-backup/dsh-out/progress-28m.md`；证据目录：`packrivo-backup/deploy/verification-28m/`。

---

## 第 1 段 · 首页 8 张产品卡

**结果**：首页产品区 8 张卡，全部有封面、有价格，价格与报价页实算一致（8/8 PASS）。

| # | 产品（已审标题） | 盒型 | 卡片价 / 件 @500 | 报价页实算 |
|---|---|---|---|---|
| 1 | Custom Drawer Jewelry Boxes with Logo | rigid | $2.83 | $2.83 |
| 2 | Custom Lid-and-Base Jewelry Boxes | rigid | $2.83 | $2.83 |
| 3 | Custom Jewelry Boxes with Fitted Inserts | rigid | $2.83 | $2.83 |
| 4 | Custom Window Lid Rigid Boxes | rigid | $2.83 | $2.83 |
| 5 | Custom Hinged Rigid Gift Boxes | rigid | $2.83 | $2.83 |
| 6 | Custom Hinged Lid Boxes with Logo | mailer | $2.48 | $2.48 |
| 7 | Custom Paper Gift Bags with Logo | bag | $1.63 | $1.63 |
| 8 | Custom SOS Paper Bags Bulk | bag | $1.63 | $1.63 |

首屏 `From · 500 pcs` 取 8 张卡里的最低单价 **$1.63**（与卡片一致，不另写死）。

**改前的问题**（同脚本跑出来的原始对照，说明为什么必须改）：

```
Custom Drawer Jewelry Boxes with Logo     cardPrice 2.83  quotePrice 7.45  same false
...
Custom Paper Gift Bags with Logo          cardPrice 1.63  quotePrice 1.93  same false
```

根因：卡片按自己的参考尺寸算，报价页 JS 挂载时用的是另一套默认值（30×22×12 / kraft）。修法是让渲染器把参考尺寸通过 `pkq_reference_spec` 过滤器 + `data-*` 属性交给报价页，JS 初始化读这组属性——不是在卡片上硬写数字。

134 个产品页的分档：**20 个**有封面有价可直接上卡、83 个只有封面、14 个只有价。本轮只上 8 张（版面决定），不是数据不足。

**跳过并记录的**：标签类（5 个 label 页无封面，只有 `product-sheet-labels` 有 $0.96）、珠宝绒布袋（`pk_engine_style_for()` 的材质正则显式排除 fabric/pouch，用 pouches 的 16×6×23 kraftpoly 规格会给另一种材质算价）。两处都写在 progress，没有猜。

---

## 第 2 段 · 报价页 11 语种

### 字符串盘点（先读源码，不靠点页面）

`dsh-in/quote-strings-28m.json`：从 5 个源文件抽 147 条字面量，与人工清单 107 个 id（**99 个不同英文串**）交叉——清单 0 条在源码中找不到；源码里多出来的全是标识符、CSS 类、邮件正文字段。

### 译文

`dsh-out/quote-strings-28m.json`：`{英文: {ar…zh-TW}}`，**99 × 11 = 1089 格**，逐格校验：无缺、无多、无空；`{price}/{qty}/{percent}/{total}/{ref}/{min}/{max}` 占位符全部原样保留；除 6 个真实同形词（Material / Total / Website 在 de/es/fr/nl/pt）外没有一条等于英文。

### 代码

- 插件新增 `includes/quote-i18n/<lang>.json` 11 份，`PKQ_Plugin::lang()/strings()/t()` 按当前语种取词写进 `wp_localize_script`
- `assets/pkq-quote.js` 全部可见字面量过 `t()/tf()/serverText()`（含 REST 13 条错误、`Quantity must be between N and M.` 这类带数字的模式）
- 阿语 `.pkq-root dir="rtl"`；`.pkq-hp` 蜜罐原来 `left:-9999px` 在 RTL 下把滚动区顶到 **10374 px**，改 `clip`/`clip-path` 后回到 375
- 本地 `paths.json` 加 `/get-a-quote/`（146 条）；11 份字典补 `Get a Quote` 与 `Get a Quote – Packrivo`（en dash 两侧有空格，第一版键少了个空格，已删掉重写）
- **价格计算一行未改**：`class-pkq-pricing.php` / `pricing.json` / `class-pkq-rest.php` 与段 1 结束时副本 SHA-256 三条全同

### 验收

**11 语种 × 5 步可见英文 = 0**（`quote-11-summary.json`；判定：文本节点等于英文键、且该语种确有不同译文）

```
ar 0  de 0  es 0  fr 0  it 0  ja 0  ko 0  nl 0  pt 0  zh-CN 0  zh-TW 0
```

**4 组价格 × en/ar/de/ja 数字完全一致**（`quote-4groups.json`）

| 组 | unit | total | 四语种一致 |
|---|---|---|---|
| rigid 500 | $2.83 | $1,417.43 | true |
| mailer 500 | $2.48 | $1,239.04 | true |
| sticker | $0.96 | $479.63 | true |
| shipping | $2.31 | $1,156.69 | true |

**ar 375 px 两张图**：`quote-ar-375-step1.png`（第 1 步 + 步骤条）、`quote-ar-375-summary.png`（价格摘要；`scrollY=1184`，摘要 350 px 高，`dir=rtl`，`scrollWidth=375`）。摘要里读到的就是译文：`كرتون رمادي صلب 2 مم` / `إجمالي الطلب $1,417.43`。

**en/ar/de × 首页/产品页/`/products/` 报价按钮 href 全部 200**：105 条去重链接，0 异常（`quote-buttons-200.txt`）。去向规则实测：

| 场景 | 英文 | de / ja / zh-CN / ar |
|---|---|---|
| 有引擎价的产品 | `/get-a-quote/?style=rigid&qty=500&product=…` | `/{lang}/get-a-quote/?style=rigid&qty=500&product=…` |
| 无引擎映射的产品 | `/get-a-quote/?style=&qty=500&product=…` | `/{lang}/inquiry/?product=auto-lock-bottom-carton` |
| 产品页 sticky | 不变 | `/{lang}/get-a-quote/?style=rigid&qty=500&product=…` |

页头不带 product 的「Get a Quote」保持 28l 的去向 `/{lang}/inquiry/`（无 product 即无引擎映射），要不要改成配置器留给站长定。

**文档标题也补了**：`/de/get-a-quote/` → `Angebot anfordern – Packrivo`，`/ar/` → `اطلب عرض سعر – Packrivo`。

---

## 第 3 段 · P2 视觉

### 手机首页首屏（375 px，视口 812）

| 量 | 改前 | 改后 |
|---|---|---|
| H1 | 147–299（152 高） | 127–223（96 高） |
| 主按钮 | 500–538，**38 高** | **338–390，52 高** |
| 产品实物图 | 顶 798（首屏只剩 14 px） | **408–653，245 px 全在首屏** |
| 4 项数据 | 624–766，纵向 4 条 142 高 | **687–798，2×2 紧凑，111 高** |

做法：hero 拆成 `copy / media / stats` 三个 grid 区域，桌面 `"copy media" "stats media"` 还原左右栏（media 786–1286，copy 154–730，实测并排），手机 `"copy" "media" "stats"` 让图自然落在按钮和数据之间。**4 项数据一项没删**。

### /products/ 手机端密度

| 量（375） | 改前 | 改后 |
|---|---|---|
| 目录页 hero | 67–445（378 高） | **65–310（245 高）** |
| 筛选栏 | 445–896（**451 px**） | **310–512（202 px）** |
| 第一张卡顶点 | **944 px** | **560 px** |
| 桌面第一张卡 | 619 | 619（未变） |

- 分类 chips 改**一行横向滚动**（`clientWidth 327 / scrollWidth 794`，页面本身不溢出）
- 搜索框与排序下拉**同一行**（375 下两者 `top=476`；1440 下 `top=492`）
- 重复说明文字压缩（lede 手机 13.5px/1.4、全局 4 行截断）

### 一套控件系统（44 px / 同边框 / 同字重 / 同焦点环）

| 控件 | 改前 375 | 改后 375 | 改后 1440 |
|---|---|---|---|
| `.pk-btn` | 38×154 | 52×159（hero）/ 44×123 | 44×154 |
| `.pk-btn-ghost` | 38×168 | 52×159 / 44×162 | 44×168 |
| `.pk-chip` | 35×41 | **44×47** | 44×47 |
| 搜索框 | 51×327（原生） | **44×170** | 44×211 |
| 排序下拉 | 48×327（原生） | **44×150** | 44×170 |
| 搜索按钮 | 无样式 | **44×79** | 44×79 |
| 页头 CTA | 43×127 | 44×127 | 44×127 |
| `.pkq-btn` / `.pkq-step` | 44 | 44 | 44 |

**产品页底部固定按钮配色**：原本橙色渐变（`#F97316→#E04E15`、`#111` 文字），现与首页主按钮同色 `rgb(12,20,17)` + `rgb(253,252,248)`，两个宽度实测 `samePaletteAsHomePrimary=true`。

**WhatsApp 悬浮按钮**：原来在手机上压在固定条上并挡住产品图库第三张「Structure」。现在带固定条的页面隐藏悬浮按钮（固定条自身带 WhatsApp 图标），其余页面 `padding-bottom:84px` 留位。实测 `overlap=false`、`dockCoversBottomHits=[]`。

### 验收

- 截图 16 张（4 页 × 375/1440 × 改前/改后）+ 2 张固定条图，在 `deploy/verification-28m/`
- **375 px `scrollWidth` 12 行全部 375**（6 页 × en/ar：首页、/products/、产品页、报价页、/blog/、/inquiry/）
- 桌面 hero 仍是左右分栏（`sideBySide=true`、`statsBelowCopy=true`）

---

## 第 4 段 · 总回归

### 1. 142 个页面各恰好 1 个 `<title>`，标题与已审清单一致，Sourcing = 0

134 个产品页 + 8 个主页面（`hubs-27i.json` 的 8 条路径），用 `fetch` 读**原始 HTML**（不执行 JS）：

```
合计 142 页（产品页 134 + 主页面 8）
非 200 或 <title> 数量≠1：0
与已审清单不一致：0
含 Sourcing 的标题：0
重复标题：0
最长标题：57 字符
```

原始表：`deploy/verification-28m/titles-28m.csv` / `titles-28m.json`（最终态复验，脚本 `check-titles-28m-v3.mjs`；判定：产品页标题 = 已审标题 + ` | Packrivo` 或 ` – Packrivo`，主页面与 `hubs-27i.json` 8 条标题精确一致）。

> 另记一笔：`/contact/`（不在 142 页内）与 `/inquiry/`（在 142 页内）的 `<title>` 都是 `Request a Custom Packaging Quote | Packrivo`。内容决策，本轮未改。

### 2. 可见英文节点数：77 次页面/语种组合，**全部 0**

7 个页面 × 11 语种，沿用 28k 的参考集与豁免规则（品牌、货币、单位、`DDP/FOB/MOQ`、价格区间、日期、真实同形词）：

```
page,ar,de,es,fr,it,ja,ko,nl,pt,zh-CN,zh-TW
"/",0,0,0,0,0,0,0,0,0,0,0
"/products/",0,0,0,0,0,0,0,0,0,0,0
"/jewelry-boxes/",0,0,0,0,0,0,0,0,0,0,0
"/blog/",0,0,0,0,0,0,0,0,0,0,0
"/2026/10/26/jewelry-pouch-box-customer-handoff/",0,0,0,0,0,0,0,0,0,0,0
"/product-pillow-jewelry-box/",0,0,0,0,0,0,0,0,0,0,0
"/get-a-quote/",0,0,0,0,0,0,0,0,0,0,0
合计 77 次页面/语种组合，可见英文节点 0 个
```

报价页 5 个步骤另算（JS 渲染，静态参考集覆盖不到）：11 语种 × 5 步 = **0 泄漏**，价格 `$2.83 / $1,417.43` 与英文一致（同第 2 段）。

> 唯一一个需要说明的豁免：意大利语的 `Home`（面包屑）。字典里 `it.Home = Home`，是意大利语本来就用的词，已加入同形词白名单并在此记录，不算泄漏。

### 3. 横向溢出：总回归中发现并修复两处 header 溢出，最终 84/84 通过

总回归第一轮在 375 px 和 1440 px 各发现一处 header 横向溢出，都已修复并复验；修复只落在 `packrivo-home-v4/assets/site.css`，父主题/插件未动。

**修复 1 · 375 px 首页 de/es/nl（第一轮回归发现）**

- 现象：`/de/` `/es/` `/nl/` 首页 375 视口 `scrollWidth` 411/378/418，且这 3 个语种页的布局视口本身被移动 Chrome 扩成 411/378/418（`window.innerWidth ≠ 375`）。
- 根因：不是 viewport meta、也不是语言插件注入——`.pk-more` 链接 `white-space:nowrap` 在不可换行的 `.pk-section-head` flex 行里把页面最小内容宽顶出 375，浏览器随之扩视口；`#pr-consent` 只是随视口变宽的受害者。
- 修复：`.pk-section-head` 加 `flex-wrap: wrap`（site.css 一行）。
- 复验：`overflow-28m-fixed.csv` **12 语种 × 7 路径 = 84 组合，`scrollWidth` 全部 375、0 溢出**；after 截图 `home-de/es/nl-375-after.png`。

**修复 2 · 桌面 1440 px header de/es/nl（并带出 fr/it/pt）**

- 现象：1440 视口下 header `.pk-actions` 行（`flex:0 0 auto`）里 `.pk-cta` 按钮 `white-space:nowrap`，德/西/荷译文更长（`Angebot erstellen` / `Crear un presupuesto` / `Stel een prijsopgave samen`）把整行顶出视口（`scrollWidth` 1494/1450/1466；en/fr 正常）。在回归环境拦截外部字体、用兜底字体的最坏度量下，溢出带为 1351–1491，fr/pt/it 也在带内先后溢出。
- 修复：site.css 追加 6 条媒体查询，只在各自放不下的宽度区间隐藏次要 `.pk-text-link`（Sample kit / Instant quote）：it 1351–1500、pt 1351–1540、fr 1351–1550、de 1351–1575、es 1351–1595、nl 1351–1655；en/ar/ja/ko/zh 与 ≤1350 px 完全不受影响，`.pk-cta` 主按钮始终保留。
- 复验（`header-1440-12langs.txt`）：A) 1440 px 拦截字体，12 语种 `scrollWidth` 全 =1440（bad=0）；B) 1440 px 字体加载，12 语种全 =1440（bad=0）；C) 各语种隐藏区间顶 +1 px，链接重新出现且仍不溢出（bad=0）。

**最终 375 px 复验表**（修复 1 落地后；en/ar 14 行 + 其余 10 语种全部 375）：

| 页面 | en | ar |
|---|---|---|
| 首页 | 375 | 375 |
| `/products/` | 375 | 375 |
| `/jewelry-boxes/` | 375 | 375 |
| `/blog/` | 375 | 375 |
| 1 篇文章 | 375 | **404 / 981**（见下） |
| 1 个产品页 | 375 | 375 |
| 报价页 | 375 | 375 |

`/ar/<文章路径>/` 是 404（该路径不在 `paths.json`，27j 已记为 `route-unavailable`），而这个 404 页**没有 `<meta name="viewport">`**，手机按 980 px 渲染 → `innerWidth=981`。修复补丁已写好但**未应用**：`deploy/patches-28m/packrivo-i18n-404-viewport.patch`（mu-plugin 只读，按规则只交补丁）。最终复验脚本里文章页非 en 语种按 `/?p=2937` 回退路径测量，全部 =375——84 组合含此回退，0 溢出。

### 4. 询盘页

- 表单 14 个可见控件，标签齐全：product / search / dimensions / quantity / country / email / material / printing / date / notes / contact_name / company / company_website / reference_files
- `?product=product-drawer-jewelry-box` → 产品字段值 `drawer-jewelry-box`（**能预选**）
- 两步流程：`Review My Brief`（46 px，type=button）→ 前端摘要 → `Send My Packaging Request`。点 `Review My Brief` 期间 **POST 请求 0 条**，没有提交任何东西

### 5. 4 组价格与 27c 记录一致

| 组 | 27c 记录 | 本轮实测（en/ar/de/ja） | 一致 |
|---|---|---|---|
| rigid 500 | 2.83 | $2.83 | ✅ |
| mailer 500 | 2.48 | $2.48 | ✅ |
| sticker | 0.96 | $0.96 | ✅ |
| shipping | 2.31 | $2.31 | ✅ |

（27c 记录见 `packrivo-v2/docs/CODEX-RESULT-2026-10-27c.md` 第 71–78、193、349 行。）

### 6. `deploy/README.md`

已加 `## 28m` 段：包哈希（子主题 38 文件 769.5 KB `62a31fc2…`；插件 23 文件 57 KB `3600ac5f…`）、六步上线顺序（备份 → 字典 → 插件 → 子主题 → MU 补丁 → 内容）每步的命令与回滚命令、以及 7 条剩余阻断。`content` 一步本轮没有改动，明确标为跳过。

---

## 还剩什么（照实）

1. **生产 `/get-a-quote/` 的 301 未处理**（27e 补丁仍未执行）。不先解决，本轮的语种报价按钮到生产会全部跳去询盘页。
2. **生产字典/路径表必须以生产基线重新合并**，不能拿本地 `deploy/i18n-merged/` 直接覆盖。
3. **MU 404 viewport 补丁未应用**（`deploy/patches-28m/packrivo-i18n-404-viewport.patch`）。
4. `/ar/<文章路径>/` 仍是 404（缺 path 白名单，也缺对应译文，扩了会变成阿语壳 + 英文正文）。
5. `/contact/` 与 `/inquiry/` 标题重复，待站长定。
6. 英文源串里的 `E-flute` / `2 mm` / `dieline` 仍在（按「英文不变」未动），译文按原义翻；要不要换英文源串由站长定。
7. 页头「Get a Quote」在语种页仍去 `/{lang}/inquiry/`（无 product 无映射），要不要改配置器待定。

Claude 核验通过后，由站长决定上线。

---

# 28n 收尾（Claude 核验后的 6 项）

任务书 `packrivo-v2/docs/DSH-TASK-2026-10-28n.md`。规则同 28m：只在本地，没有部署、没有碰 `www.packrivo.com`、没有读取或记录任何密码、没有提交任何真实询盘或报价。

**第 1 项（未提交改动）** 已由 `13c7f1f` 解决，本轮没有重做；结束时 `git status` 干净（见文末原始输出）。

## 第 2 项 · 报价页手机端标题区贴边

`.pkq-head` 在 375 px 下左右内缩为 0，标题、说明文字直接顶到屏幕边。修法是给标题块加逻辑内边距（**阿语自动落在右侧**）：

```css
@media (max-width: 900px) {
  /* 手机端标题区需要和正文一致的左右安全边距；padding-inline 让阿语自动落在右侧 */
  .pkq-head { padding-inline: 20px; }
  …
}
```

| 量（375 px） | 改前 | 改后 |
|---|---|---|
| en 小标题 `x` | 0 | **20** |
| en 说明段落 `x / 宽` | 0 / 375 | **20 / 335** |
| ar 小标题右边缘 | 375（贴右） | **355** |
| ar 说明段落 `x → right` | 0 → 375 | **20 → 355** |
| 页面 `scrollWidth` | 375 | 375（没有因内缩产生横向溢出） |

截图：`deploy/verification-28n/quote-en-375-head.png`、`quote-ar-375-head.png`。

## 第 3 项 · 报价器里的技术词（站长 7 号问题：换）

只改**显示给买家的文字**。键名（`kraft` / `white` / `rigidboard`）、`factor` 系数、`pricing.json` 的公式字段一律没动。

| 位置 | 改前 | 改后 |
|---|---|---|
| `assets/pkq-quote.js` 三个 `label` | `Kraft E-flute` / `White E-flute` / `Rigid greyboard 2 mm` | `Kraft corrugated` / `White corrugated` / `Rigid greyboard` |
| `includes/pricing.json` 三个 `label` | 同上 | 同上 |
| `packrivo-quote.php:175`（`i18n.sub`） | `…An engineer confirms the dieline before anything is cut.` | `…An engineer confirms the cutting template before anything is cut.` |
| `includes/quote-i18n/<lang>.json` | 4 个键 | 键名同步改成新英文；译文换成 28k 已审术语 |

11 语种译文（材料三项直接取 28k 主字典里已审的值，句子里的词取 28k 首页 lede 里 `a cutting template with every quote` 的用词）：

| 语种 | Kraft corrugated | White corrugated | Rigid greyboard | 句子 |
|---|---|---|---|---|
| ar | كرتون كرافت مموج | كرتون مموج أبيض | ورق مقوّى رمادي صلب | يتحقق مهندس من مخطط قص قبل بدء أي تقطيع |
| de | Kraft-Wellpappe | Weiße Wellpappe | Feste Graupappe | …prüft die Stanzvorlage, bevor geschnitten wird |
| es | Cartón ondulado kraft | Cartón ondulado blanco | Cartón gris rígido | …confirma la plantilla de corte antes de cortar nada |
| fr | Carton ondulé kraft | Carton ondulé blanc | Carton gris rigide | …valide le gabarit de découpe avant toute production |
| it | Cartone ondulato kraft | Cartone ondulato bianco | Cartone grigio rigido | …conferma il tracciato di fustella prima di qualsiasi taglio |
| ja | クラフト段ボール | 白い段ボール | 硬質グレー板紙 | …技術者がカット用テンプレートを確認します |
| ko | 크라프트 골판지 | 화이트 골판지 | 경질 회색 보드 | …엔지니어가 재단 도면을 확인합니다 |
| nl | Kraft golfkarton | Wit golfkarton | Stevig grijsbord | …bevestigt de stanstekening voordat er iets wordt gesneden |
| pt | Papelão ondulado kraft | Papelão ondulado branco | Papelão cinza rígido | …confirma o gabarito de corte antes de qualquer corte |
| zh-CN | 牛皮瓦楞纸板 | 白色瓦楞纸板 | 硬质灰板 | …开切之前由工程师确认刀模图 |
| zh-TW | 牛皮瓦楞紙板 | 白色瓦楞紙板 | 硬質灰板 | …開切之前由工程師確認刀模圖 |

（es / fr / it 的冠词按新名词的性数改过：`la plantilla`、`le gabarit`、`il tracciato`。机改初版是 `el plantilla` / `la gabarit` / `la tracciato`，已修正。）

### 价格一个数都没变（16 格逐格对照）

| 组 | 语种 | 改前 单价 / 合计 | 改后 单价 / 合计 | 一致 |
|---|---|---|---|---|
| rigid | en | $2.83 / $1,417.43 | $2.83 / $1,417.43 | ✅ |
| rigid | ar | $2.83 / $1,417.43 | $2.83 / $1,417.43 | ✅ |
| rigid | de | $2.83 / $1,417.43 | $2.83 / $1,417.43 | ✅ |
| rigid | ja | $2.83 / $1,417.43 | $2.83 / $1,417.43 | ✅ |
| mailer | en | $2.48 / $1,239.04 | $2.48 / $1,239.04 | ✅ |
| mailer | ar | $2.48 / $1,239.04 | $2.48 / $1,239.04 | ✅ |
| mailer | de | $2.48 / $1,239.04 | $2.48 / $1,239.04 | ✅ |
| mailer | ja | $2.48 / $1,239.04 | $2.48 / $1,239.04 | ✅ |
| sticker | en | $0.96 / $479.63 | $0.96 / $479.63 | ✅ |
| sticker | ar | $0.96 / $479.63 | $0.96 / $479.63 | ✅ |
| sticker | de | $0.96 / $479.63 | $0.96 / $479.63 | ✅ |
| sticker | ja | $0.96 / $479.63 | $0.96 / $479.63 | ✅ |
| shipping | en | $2.31 / $1,156.69 | $2.31 / $1,156.69 | ✅ |
| shipping | ar | $2.31 / $1,156.69 | $2.31 / $1,156.69 | ✅ |
| shipping | de | $2.31 / $1,156.69 | $2.31 / $1,156.69 | ✅ |
| shipping | ja | $2.31 / $1,156.69 | $2.31 / $1,156.69 | ✅ |

原始 JSON：`deploy/verification-28n/price-diff.json`（`priceSame: true`，16 格）。`includes/class-pkq-pricing.php`、`includes/class-pkq-rest.php` 与改前逐字节相同；`pricing.json` 只有 3 个 `label` 字符串变了。

### 摘要行读出来的确实是新词（7 个语种抽查）

| 语种 | 摘要里的材料行 | 说明句 |
|---|---|---|
| en | `MaterialRigid greyboard` | …confirms the cutting template before anything is cut |
| ar | `المادةورق مقوّى رمادي صلب` | …مخطط قص… |
| de | `MaterialFeste Graupappe` | …Stanzvorlage… |
| ja | `材質硬質グレー板紙` | …カット用テンプレート… |
| zh-CN | `材质硬质灰板` | …刀模图… |
| fr | `MatièreCarton gris rigide` | …gabarit de découpe… |
| it | `MaterialeCartone grigio rigido` | …tracciato di fustella… |

原始的 `Kraft E-flute`、`White E-flute`、`Rigid greyboard 2 mm` 在插件目录里已经搜不到（`grep` 0 命中）。`demo/index.php` 的同一句也一起改了；`demo/` 本来就不进包。

## 第 4 项 · 语种页面剩下的英文报价按钮 / 页头报价链接

两处根因：

1. `header.php` 里 `Instant quote` 没在字典里，11 语种页面直接显示英文；
2. `assets/cta-28l.js` 在「有语种、没有产品」时把报价链接统一指向 `/{lang}/inquiry/`（28l 的临时决定），页头 CTA、首页报价带、页脚 `.pr-quote-cta` 都被它带到询盘页。

改法（不改 MU 源码）：

- `header.php`：`$quote_url` 自己加语种前缀——mu-plugin 已经把 `REQUEST_URI` 改写成英文路径，`home_url()` 只会给英文地址，所以服务端就得补上 `/{lang}`。
- `assets/cta-28l.js`：报价类链接在语种页面去 `/{lang}/get-a-quote/`；真正的询盘链接仍留 `/{lang}/inquiry/`。页脚按钮按类名 `.pr-quote-cta` 识别（父主题/MU 注入），不碰 MU。
- 字典：11 份主字典的 `text` 里补 `Instant quote`（用 28m 已审的 11 种译文）。

改前 → 改后（同一批元素，等前端脚本跑完再取）：

| 语言 | 元素 | 改前文字 / href | 改后文字 / href |
|---|---|---|---|
| ar | 页头即时报价 | `Instant quote` → `/ar/inquiry/` | `عرض سعر فوري` → `/ar/get-a-quote/` |
| ar | 页头 CTA | `أنشئ عرض سعر` → `/ar/inquiry/` | 同 → `/ar/get-a-quote/` |
| ar | 页脚报价 | `احصل على عرض سعر` → `/ar/inquiry/` | 同 → `/ar/get-a-quote/` |
| ar | 产品页页头 | `Instant quote` → `/ar/get-a-quote/?style=…` | `عرض سعر فوري` → 同址 |
| de | 页头即时报价 | `Instant quote` → `/de/inquiry/` | `Sofortangebot` → `/de/get-a-quote/` |
| de | 页脚报价 | `Angebot anfordern` → `/de/inquiry/` | 同 → `/de/get-a-quote/` |
| ja | 页头即时报价 | `Instant quote` → `/ja/inquiry/` | `すぐに見積もり` → `/ja/get-a-quote/` |
| ja | 页脚报价 | `見積を取得` → `/ja/inquiry/` | 同 → `/ja/get-a-quote/` |
| en | 全部 | 不变 | 不变（仍 `/get-a-quote/…`） |

**审计（12 个语言前缀 × 首页/产品页 = 24 组，全部在前端脚本跑完之后取）**

```
en home 16 条 / product 5 条   | 可见英文 0 | href 不在本语种报价页 0
ar home 16 条 / product 5 条   | 可见英文 0 | href 不在本语种报价页 0
de home 16 条 / product 5 条   | 可见英文 0 | href 不在本语种报价页 0
es home 16 条 / product 5 条   | 可见英文 0 | href 不在本语种报价页 0
fr home 15 条 / product 4 条   | 可见英文 0 | href 不在本语种报价页 0
it home 15 条 / product 4 条   | 可见英文 0 | href 不在本语种报价页 0
ja home 16 条 / product 5 条   | 可见英文 0 | href 不在本语种报价页 0
ko home 15 条 / product 4 条   | 可见英文 0 | href 不在本语种报价页 0
nl home 16 条 / product 5 条   | 可见英文 0 | href 不在本语种报价页 0
pt home 16 条 / product 5 条   | 可见英文 0 | href 不在本语种报价页 0
zh-CN home 15 条 / product 4 条 | 可见英文 0 | href 不在本语种报价页 0
zh-TW home 16 条 / product 5 条 | 可见英文 0 | href 不在本语种报价页 0
```

条数差 1 的是「同一 href 且译文后文字相同」被去重合并（逐 href 对比英文集合，没有缺失项）。

完整清单：`deploy/verification-28n/buttons-audit.json`、`verify-28n.txt`。

## 第 5 项 · 404 viewport 补丁进上线包

`deploy/README.md` 第 5 步已写明顺序与独立回滚：

- **先**打报价重定向补丁 `deploy/27e-quote-redirect.patch`（`packrivo-site-remediation-20261022.php`，只把 `get-a-quote` 从旧别名列表移除）；
- **再**打 `deploy/patches-28m/packrivo-i18n-404-viewport.patch`（`packrivo-i18n.php`，补 `<meta name="viewport">`）；
- 两个补丁落在不同 MU 文件，回滚各自 `cp -a "$BACKUP/mu/<文件>"` 即可，互不影响。

**本轮没有应用任何补丁。**

## 第 6 项 · 不做，记在 README

`deploy/README.md` 末尾新增 `### 上线后再做（28n 第 6 项，站长决定本轮不做）`：

- 语种文章路径 404：文章没有译文，现在返回 `noindex,follow` 的 404 并链到英文版，可以接受；要修先等文章翻译排期。
- `/contact/` 301：生产与本地都把 `/contact/` 301 到 `/inquiry/`，不构成重复标题页面，不改。

## 结束 · 重打包与提交

| 包 | 文件数 | 大小 | SHA-256 |
|---|---|---|---|
| `packrivo-home-v4.zip` | 38 | 788,720 B | `e7041551906e319e1d361dd53921598940c6775957d0fe8bad33d21dc9244acb` |
| `packrivo-quote.zip` | 19（`demo/` 不进包） | 54,581 B | `6c225718357aa7467f6bd50288ccc3a76a0909ea9e18fa3edcbb85d262b66895` |

两个包解压后与 `packrivo-wp/` 工作源逐字节相同（`header.php`、`cta-28l.js`、`site.css`、`pkq-quote.css`、`pkq-quote.js`、`pricing.json`、`packrivo-quote.php`、`quote-i18n/de.json`、`quote-i18n/zh-CN.json` 九个文件逐一比对 `same=True`）。README 里的哈希已同步。

### 最终收尾 · 最终重打包复验与提交（commit `45e2616`）

- 第 4 项终验（06:02）：`deploy/verification-28n/buttons-28n-item4.{txt,json}`，en/ar/de/ja 首页+产品页 × 1440/375 共 16 组 post-JS 采集，`englishLeak=0`、`wrongHref=0`，无需任何修复；与上表 24 组审计结论一致。
- `Sample kit`：11 份主字典 `deploy/i18n-merged/*-dict.json` 各补 1 条译文（`text` 键），随最终提交入库；上节「额外发现」的初版结论相应作废。
- 最终重打包（`pack-zips.ps1`：`robocopy /E` 镜像 → `Compress-Archive`）：两个 zip 哈希与上表**完全一致**（`e7041551…` / `6c225718…`），解压后与 `packrivo-wp/` 工作源逐字节相同（主题 38 文件、插件 19 文件、`demo/` 0 个进包）；`packrivo-backup/packrivo-wp/` 仓库镜像已 `robocopy /MIR` 同步，`packrivo-wp/*.zip` 与镜像内 zip 副本已同步为新包字节；`deploy/README.md` 哈希表无需改动。
- 价格复核：`includes/pricing.json` 与 28n 前提交版（HEAD 基线）逐路径 diff 仅 3 个 `label` 字符串（`Kraft E-flute→Kraft corrugated`、`White E-flute→White corrugated`、`Rigid greyboard 2 mm→Rigid greyboard`），全部 33 个数值字段 0 变化；报价计算两文件与第一版逐字节相同——4 组价格数字完全一致。
- 提交：`45e2616`（28n final）及本文件更新提交；除并行 28o 会话未跟踪的 `deploy/verification-28o/` 外，`git status` 干净。

### 额外发现（不在 6 项内，没改）

页头还有一个 `Sample kit` 链接同样没进字典。它在 1350 px 以下被 13c7f1f 的媒体查询隐藏，但在 1351 px 以上（阿语、德语、中文等）会以英文出现。它不是报价按钮，也不在任务书范围内；11 语种里只有 `Order a sample kit` 的整句译文，直接截短会改文案口径，所以初版没有动；最终收尾阶段已按 11 份主字典补齐 `Sample kit` 译文（每份 `text` 加一键）随最终提交入库。同位置的两个报价按钮现在已经全部是译文（见上表）。

---

# 28o 上线前最后 4 项

任务书 `packrivo-v2/docs/DSH-TASK-2026-10-28o.md`。只在本地做：没有部署、没有 SSH、没改 `www.packrivo.com`、没有读取或记录任何密码；`deploy/RUNBOOK-launch.md` 里的每一条生产命令**本轮一条都没有执行**。

## 第 1 项 · 两个 zip 的反斜杠（上线阻断）

根因：Windows 的 `Compress-Archive` 把目录分隔符写成 `\`，Linux 的 PHP `ZipArchive` 会把 `packrivo-quote\packrivo-quote.php` 整串当文件名，插件和主题都装不上。

修法：改用 Python `zipfile` 打包，`arcname` 显式写 `/`，目录结构不变（顶层 `packrivo-quote/`、`packrivo-home-v4/`，不含 `demo/`）。

**验收 1：`unzip -Z1` 没有反斜杠**（在本地 Docker 的 Linux 容器 `packrivo-live-wp-1` 里跑，先 `apt-get install -y unzip`）

```
/tmp/packrivo-quote.zip      条目=19  反斜杠字符数=0
  首条 packrivo-quote/README.md
  末条 packrivo-quote/packrivo-quote.php
/tmp/packrivo-home-v4.zip    条目=38  反斜杠字符数=0
  首条 packrivo-home-v4/README.md
  末条 packrivo-home-v4/template-products-v4.php
```

**验收 2：容器里用 PHP `ZipArchive` 解压，再与工作源逐字节比对**

```
=== packrivo-quote.zip ===
  条目 19，条目名含反斜杠 0
  解压后文件 19，工作源文件 19
  只在包里 0，只在源里 0，内容不同 0
  结论: 通过（逐字节一致，无反斜杠条目）
=== packrivo-home-v4.zip ===
  条目 38，条目名含反斜杠 0
  解压后文件 38，工作源文件 38
  只在包里 0，只在源里 0，内容不同 0
  结论: 通过（逐字节一致，无反斜杠条目）
```

原始输出：`deploy/verification-28o/zip-verify-php.txt`、`unzip-list.txt`。

**验收 3：`wp plugin install` / `wp theme install` 真装一次**

容器里原本没有 wp-cli，本轮 `curl` 下载 `wp-cli.phar`（PHP 8.2.28 / WP 6.7.2）到容器 `/tmp`，然后在容器里一份**独立的 WP 根** `/tmp/wp2`（从 `/var/www/html` 复制，含全部插件与主题，单独可写）上执行：

```
$ wp plugin install /tmp/packrivo-quote.zip --force --path=/tmp/wp2
Unpacking the package...
Installing the plugin...
Removing the old version of the plugin...
Plugin updated successfully.
Success: Installed 1 of 1 plugins.

$ wp theme install /tmp/packrivo-home-v4.zip --force --path=/tmp/wp2
Unpacking the package...
Installing the theme...
Removing the old version of the theme...
This theme requires a parent theme. Checking if it is installed...
The parent theme, Packrivo Static Bridge 1.2.38, is currently installed.
Theme updated successfully.
Success: Installed 1 of 1 themes.

$ diff -r /tmp/wp2/wp-content/themes/packrivo-home-v4 /tmp/src/packrivo-home-v4
（无输出，exit=0）
$ diff -r /tmp/wp2/wp-content/plugins/packrivo-quote /tmp/src/packrivo-quote
Only in /tmp/src/packrivo-quote: demo
$ wp eval-file /tmp/check-load.php --path=/tmp/wp2
PKQ_Plugin loaded
theme=Packrivo Home v4 (child)
```

**本机这份站点**（跑在 `/var/www/html`，插件与主题目录是 bind mount）装完复测：4 组价格未变、7 个关键页 200：

```
rigid     $2.83 / $1,417.43
mailer    $2.48 / $1,239.04
sticker   $0.96 / $479.63
shipping  $2.31 / $1,156.69
200 /  Custom Jewelry Boxes & Packaging with Logo | Packrivo
200 /products/  Custom Packaging: 134 Styles, MOQ 500 | Packrivo
200 /jewelry-boxes/  Custom Jewelry Boxes: Rigid, Drawer, MOQ 500 | Packrivo
200 /blog/  Custom Packaging Guides for Small Brands | Packrivo
200 /get-a-quote/  Get a Quote – Packrivo
200 /inquiry/  Request a Custom Packaging Quote | Packrivo
200 /product-drawer-jewelry-box/  Custom Drawer Jewelry Boxes with Logo | Packrivo
```

**必须记下来的一件事**：同一组命令**先在本机这份 bind-mount 站点上跑过，失败了**。`--force` 会先删旧目录再解包，而 bind mount 的根目录在容器里删不掉，结果是「旧目录内容被清空 + 新包没解出来」：

```
$ wp plugin install /tmp/packrivo-quote.zip --force --path=/var/www/html
Removing the old version of the plugin...
Warning: Could not remove the old plugin.
Plugin update failed.
```

两个源目录（`packrivo-wp/packrivo-quote` 23 个文件、`packrivo-wp/packrivo-home-v4` 38 个文件）被清空到 0 个文件，已用开跑前的快照整目录恢复，恢复后与 zip 解压结果逐字节一致、站点与价格正常。这条也写进了 RUNBOOK 第 4/5 步的回滚要求：`--force` 之前必须有目录级备份。

## 第 2 项 · 备份仓库里的源码副本

`packrivo-backup/packrivo-wp/packrivo-quote` 与 `packrivo-wp/packrivo-home-v4` 已用 `robocopy /E` 从工作源整目录镜像（`demo/` 原样保留，两边都有）。容器里 `diff -r` 双向比对：

```
$ diff -r 备份仓库 vs 工作源
packrivo-quote diff exit=0
packrivo-home-v4 diff exit=0
```

**0 处差异**（`demo/` 两侧都在，所以连 demo 都没有差异）。原始输出：`deploy/verification-28o/diff-r.txt`。

## 第 3 项 · Sample kit 译文

11 份 `deploy/i18n-merged/*-dict.json` 的 `text` 里各有一行 `"Sample kit"`，全部是**短名词**，并且是整句 `Order a sample kit` 里已经审过的那个名词短语（本轮把 es / fr / it / nl / pt 的首字母大写，作为按钮标签）：

| 语种 | Sample kit | 对照整句 Order a sample kit |
|---|---|---|
| ar | مجموعة عينات | اطلب مجموعة عينات |
| de | Musterset | Ein Musterset bestellen |
| es | Kit de muestras | Pedir un kit de muestras |
| fr | Kit d’échantillons | Commander un kit d’échantillons |
| it | Kit campioni | Ordina un kit campioni |
| ja | サンプルキット | サンプルキットを注文 |
| ko | 샘플 키트 | 샘플 키트 주문 |
| nl | Monsterpakket | Bestel een monsterpakket |
| pt | Kit de amostras | Pedir um kit de amostras |
| zh-CN | 样品套装 | 订购样品套装 |
| zh-TW | 樣品套裝 | 訂購樣品套裝 |

**1440 px 实测**（de / fr / nl，任务书要求的三个）：

| 宽 | 语种 | Sample kit 可见 | 那个副链接文字 | `scrollWidth` | 横向溢出 |
|---|---|---|---|---|---|
| 1440 | de | 否（按语种媒体查询隐藏） | — | 1440 | 无 |
| 1440 | fr | 否 | — | 1440 | 无 |
| 1440 | nl | 否 | — | 1440 | 无 |
| 1440 | en | 是 | `Sample kit` | 1440 | 无 |
| 1700 | de | 是 | `Musterset` | 1700 | 无 |
| 1700 | fr | 是 | `Kit d’échantillons` | 1700 | 无 |
| 1700 | nl | 是 | `Monsterpakket` | **1731** | **有（31 px）** |

1440 这一档三个语种本来就把这个副链接隐藏了（13c7f1f 的按语种规则），所以**1440 不会溢出**；但把标签换成译文之后，**1700 的 nl 多出 31 px 溢出**——量出 `.pk-actions` 与 `.pk-cta` 右缘到 1731，把副链接临时 `display:none` 后 `scrollWidth` 正好回到 1700，确认是这个标签撑的。同样的问题在 es（1600–1740）、fr（1560–1620）、it（1560–1600）、pt（1560–1660）都能测到。

处理：按实测重设 `assets/site.css` 里 5 条按语种媒体查询的上限，规则形状不变（还是「在放不下的宽度区间隐藏副链接」），只把阈值挪到各语种真正放得下的宽度：

| 语种 | 改前 `max-width` | 改后 `max-width` | 依据 |
|---|---|---|---|
| it | 1500 | **1620** | 1620 起不再溢出 |
| pt | 1540 | **1680** | 1680 起不再溢出 |
| fr | 1550 | **1640** | 1640 起不再溢出 |
| es | 1595 | **1760** | 1760 起不再溢出 |
| nl | 1655 | **1780** | 1780 起不再溢出 |

改完重扫 **13 个宽度（1560–1800）× 12 个语种 = 156 组**：**0 溢出**。1440 / 1280 / 375 三档仍然 0 溢出。

## 第 4 项 · 一份完整的上线手册

新建 `deploy/RUNBOOK-launch.md`（九步，一步一个顺序），另加两个工具/改动：

- `deploy/tools/merge-i18n.php`：字典合并脚本，规则就是手册第 2 步要求的「生产优先 + 冲突清单 + paths 并集 + 必填路径」。自带 `--self-test`，7 条断言全过（生产值优先、生产独有键保留、本地独有键保留、相同值不记冲突、两个真冲突都记下、paths 取并集、必填路径补齐）。本轮也在容器里对真实仓库跑过合并入口。
- `deploy/README.md` 开头加了一行「上线以 `RUNBOOK-launch.md` 为准，本文件里 27g…28o 的分轮记录只作历史参考」。
- 28m 那节第 6 步「内容：本轮没有改动，跳过」改成明确写明**上线不能跳过内容**，并指向 RUNBOOK 第 3 步（134 产品页 + 8 主页面）。

手册每一步都写了：执行机器（生产 SSH / 本机）、具体命令（只有占位变量，没有任何密码或 key）、通过判据、回滚命令、预计耗时。顺序与任务书一致：备份 → 字典与路径表 → 内容 → 报价插件 → 子主题 → mu 补丁（先 27e 再 404）→ 清缓存 → 复测 7 条 → 整体逆序回滚。第 6 步写明了空档期：**第 4–6 步之间 `/get-a-quote/` 仍 301 到询盘页，按钮落到询盘页但不会 404**。

顺手修掉一个真问题：原来的 `deploy/patches-28m/packrivo-i18n-404-viewport.patch` **本身是坏的**——20 行、1,036 B，hunk 后面多挂了两行没有前缀的内容，`git apply` 直接拒：

```
error: corrupt patch at .../packrivo-i18n-404-viewport.patch:21
```

用容器里的 `diff -u` 对着原始 mu 文件重新出了一份 15 行 / 1,018 B 的补丁（同样的三处改动：`viewport`、`<style>`、标题行拆开），验证链路：

```
git apply --check  → exit 0（非 git 目录也能跑）
git apply          → exit 0
grep viewport      → 168: . '<meta name="viewport" content="width=device-width, initial-scale=1">'
php -l              → No syntax errors detected
```

`27e-quote-redirect.patch` 同样验过：`git apply --check` exit 0 → 打上 → `$legacy` 里已无 `get-a-quote` → `php -l` 干净。两个补丁的本机基线哈希也写进了手册（`packrivo-i18n.php` `3bc32550…`、`packrivo-site-remediation-20261022.php` `abe6f134…`）。

导入脚本的调用方式按脚本真实签名核对过（不是猜的），并在本机容器里以 `plan` 模式实跑：

```
$ wp eval-file scripts/product-buyers-27g/import.php plan /tmp/pkgroot http://localhost:8081 … all134
input_pages=134 preflight=PASS
writes=0
$ wp eval-file scripts/hubs-27i/import.php plan /tmp/pkgroot http://localhost:8081 …
hub_pages=8 preflight=PASS
inquiry_existing_description=preserved writes=0
```

## 结束 · 重打包与提交

| 包 | 文件数 | 大小 | SHA-256 |
|---|---|---|---|
| `packrivo-home-v4.zip` | 38 | 788,881 B | `05de19e1e5072e4bda8e6d843824d915725a57732b4da728036c94e88cf8c3a8` |
| `packrivo-quote.zip` | 19（`demo/` 不进包） | 54,512 B | `8e62865ea202d7711b4f113390be898a64024648f348151e27f1dace54c3c05c` |

子主题包在 28o 之后又多了一次 `site.css` 改动（5 条按语种媒体查询阈值），所以哈希与 28n 那版不同；插件包只有 `label` 之外的改动没有新增。

### 已知问题（本轮没动）

| 问题 | 实测 |
|---|---|
| 页头 768 px 横向溢出 | de 801、nl 896；溢出元素是配色切换按钮和一条导航链接 |
| 页头 1024 px 横向溢出 | en 1117 / de 1181 / fr 1168 / nl 1221 / es 1191 / pt 1163 / it 1144；溢出元素是 `.pk-actions` 那一簇 |
| `/ar/<文章路径>/` 返回 404 | `noindex,follow` + 英文版链接，等文章翻译 |
| `/contact/` 301 到 `/inquiry/` | 生产与本地一致，不构成重复标题页 |

768 / 1024 这两条是页头结构问题（要改布局），需要视觉确认，列进 RUNBOOK 附录 A 与下一轮。


## 28o 收尾（06:50 之后）

上一轮 06:33 报“工作区干净”之后，后台并行任务又动了三个文件。先确认它已经停手：三个文件的 mtime 停在 06:43–06:44，06:50–06:52 之间没有再变，且改动已被并行任务自己提交（709b2fb、1d790e7），所以本轮**不接受任何新内容**，只做收尾四件事。

替代包也复核了一遍：709b2fb 换掉的 4 个 zip（deploy 与 packrivo-wp 各两份）哈希与 README 第 183/184 行一致（`c3ed9d72…` 788,955 B / `096d0c8e…` 54,581 B），容器里 `ZipArchive` 解压后与工作源**逐字节一致、反斜杠 0**，所以不需要再动包。

### 1 · README 开头只剩一行提示

并行任务在标题**上方**又加了一行同义提示，和 9f3a0b6 在标题下方那行重复。删掉新加的那行（709b2fb 引入的），保留标题下方那行：

```
1|# 27e / 27g / 27h / 27i 上线前检查与回滚方案（未执行）
2|
3|> **上线以 [`RUNBOOK-launch.md`](RUNBOOK-launch.md) 为准；本文件里 27g…28o 的分轮记录只作历史参考。**
4|
5|**状态：27i 主页面元数据和按钮字典已完成本地准备，尚未部署。** …
```

全文“上线以”只出现 1 次（`grep -c` 计数）。

### 2 · merge-i18n.php 自检与新增断言

`merge_js` 覆盖 `*-js.json`：`exact` 取并集、同键生产值优先；`patterns` 生产在前、本地新正则追加；其余顶层键生产优先。新加一条断言，专门盯「exact 键冲突时要采用生产值，并且要进冲突清单」——之前只断言了合并后的值，没断言“可审计”这一半：

```php
$jsConflict = null;
foreach ($conf as $c) {
    if (($c['kind'] ?? '') === 'js-exact' && ($c['key'] ?? '') === 'Diff') { $jsConflict = $c; break; }
}
$check('js-exact 键冲突记进清单且用生产值（Diff: LOCAL→PROD）',
    $jsConflict !== null
        && ($jsConflict['prod'] ?? null) === 'PROD'
        && ($jsConflict['local'] ?? null) === 'LOCAL'
        && ($jsConflict['used'] ?? null) === 'PROD');
```

`php -l` 通过，`--self-test` 原始输出（容器 PHP 8.2.28）：

```
No syntax errors detected in /tmp/tools/merge-i18n.php
=== --self-test ===
de     生产有 → 合并后 title=4 attr=0 block=0 text=2，冲突 2
de     js：exact=4 patterns=3，冲突 2
paths.json：生产 2 + 本地 2 → 合并 4（本轮补 1：/get-a-quote/）
冲突清单：4 条 → /tmp/merge-i18n-selftest-1775/out/conflicts.json
  有冲突，装之前必须人工看过 conflicts.json（生产值已被采用）。
  OK   生产值优先（Diff=PROD）
  OK   生产值优先（text A）
  OK   本地独有键保留（OnlyLocal）
  OK   生产独有键保留（OnlyProd）
  OK   两边相同不记冲突（Same）
  OK   四个真冲突都记下来
  OK   paths 取并集
  OK   js-exact 生产值优先（Diff=PROD）
  OK   js-exact 本地独有键保留（OnlyLocal）
  OK   js-exact 生产独有键保留（OnlyProd）
  OK   js-exact 键冲突记进清单且用生产值（Diff: LOCAL→PROD）
  OK   js-patterns 生产优先、本地新正则追加
  OK   js 其余顶层键生产优先（meta）
自检通过
exit=0
```

12 条断言全 OK。

### 3 · RUNBOOK 第 7 步（清缓存）：只引用路径，标注负责人亲自执行

第 7 步的 Cloudflare 段现在明确写：

> **Cloudflare 这一段：负责人亲自执行。**
> 凭据只存在 `E:\破甲工作区\i18n\.cf.env` 这一个文件里，本手册只引用它的路径、不写任何值。
> 执行时不要把文件内容 `cat`/`echo` 出来，也不要把命令输出里的 token 贴进聊天、工单或日志。

命令块里只有 `python E:\破甲工作区\i18n\cf_purge.py`，脚本自己从那个文件读凭据。附录 B 第 7 行也加了「Cloudflare 那一步：负责人亲自执行」。

对整个手册做了一次凭据扫描（`token|api[_-]?key|Bearer|password|secret|@域名|[0-9a-f]{32,}`）：命中的只有三处 `*.env` **路径**说明、以及包与补丁的 SHA-256，**没有任何 key / token / 账号值**。

### 4 · 提交

```
$ git status --short
（无输出，工作区干净）

$ git log -1 --stat
commit 5b2c77c5d41969f034979cdb27aa2b7528c55240
    28o wrap-up: drop duplicate README banner, assert exact-key conflicts, mark Cloudflare purge owner-only

 deploy/README.md            |  2 --
 deploy/RUNBOOK-launch.md    |  9 +++++++--
 deploy/tools/merge-i18n.php | 14 ++++++++++++++
 3 files changed, 21 insertions(+), 4 deletions(-)
```

以上四件事仍然只在本地做：没有 SSH、没有碰生产、没有读取或打印任何密码与凭据。

---

# 28p 上线前修页头溢出（768 / 1024 px）

任务书 `packrivo-v2/docs/DSH-TASK-2026-10-28p.md`。只在本地做：没部署、没 SSH、没碰 `www.packrivo.com`、没读任何密码。所有验证都跑在本机 Docker 站点 `http://localhost:8081`（WP 6.7.2 / PHP 8.2.28）与容器里的独立 WP 根 `/tmp/wp2` 上。

## 0 · 先把两处溢出量清楚

用 Playwright 逐元素量右缘（`getBoundingClientRect().right > innerWidth` 即溢出），带上父链与计算样式。

**1024 px：是页头的 `.pk-actions` 撑出去的**（en 1117 / de 1181 / nl 1221）。溢出元素清单里同时出现 `.pk-actions` 和 `.pk-cta`，说明整行在 1024 放不下、把右侧动作区顶出视口：

```
### en @1024px  scrollWidth=1117（溢出 93px）  burger=false nav=true
   div.pk-actions L847 R1117 W270  static/flex
   a.pk-cta L990 R1117 W127 「Build a quote」
### nl @1024px  scrollWidth=1221（溢出 197px）  burger=false nav=true
   div.pk-actions L857 R1221 W364
   a.pk-cta L999 R1221 W222 「Stel een prijsopgave samen」
```

**768 px：不在页头，在页脚**。任务书怀疑是「配色按钮 + 一条导航链接」，实测父链证明它们都在 `.footer-bottom` 里：

```
溢出: button#theme-toggle. 「Farbschema wechseln」 right=801
   ↑ button#theme-toggle.[705,801] block
   ↑ div.footer-bottom[40,728] flex        ← display:flex，flex-wrap:nowrap
   ↑ footer.[0,768] block
.footer-bottom: { display: flex, wrap: nowrap, gap: 25px, width: 688 }，
 子项 7 个：Packrivo · Verpackungsbe… / Datenschutz / AGB / Vergleichen / Cookie-Einstellungen /
           Verpackungsleitfäden(124) / #theme-toggle(96)
```

三个来源都对上了：`.footer-bottom` 的 `flex + nowrap` 来自**父主题** `packrivo-static-bridge-1.2.38/packrivo-static/assets/site.css:592`；它只在 `@media (max-width: 767px)` 才把子项堆成 `block`（同文件 1195 行），所以 **768 这一档正好卡在断点外**；`#theme-toggle`（「配色切换按钮」）是父主题 `assets/site.js:196-204` 在运行时 `createElement` 后 `append` 进 `.footer-bottom` 的；「Verpakkingshandleidingen」是同一行里的「Packaging guides」链接（i18n 运行时翻译）。

## 1 · 修法

### 1.1 1024：汉堡断点统一到实测 W

不再按语种分设断点。对 12 个语种逐个测「桌面页头右侧留 16 px 余量放得下的最小宽度」：

| 语种 | 最小宽度 | 语种 | 最小宽度 |
|---|---|---|---|
| en / ar / fr / it / ja / ko / pt / zh-CN / zh-TW | **1212** | de | 1215 |
| es | 1235 | nl | **1294** |

取最大 → **W = 1294**。1295 起桌面（12 语种右侧余量 17–57 px），1294 及以下全语种进汉堡。

```css
@media (max-width: 1294px) {          /* 原 900px */
```
```css
@media (min-width:1295px) and (max-width:1439px){.pk-actions .pk-text-link{display:none}}   /* 原 901–1350 */
```

第二条的上限从 1350 抬到 1439，是因为桌面页头带两条副链接时 en 的固有宽度是 1417：原规则在 1351 就放开链接，结果 1351/1400 又溢出（实测 1385 / 1409）。改成 1440 起才显示副链接后，en 在 1440 有 23 px 余量。语种专属阈值（1351–1620/1680/1640/1575/1760/1780）原样保留，它们都在 1440 之后生效。

`assets/nav.js` 里原来写死两处 `900`（点导航链接后关抽屉、resize 时关抽屉），断点一改就会和 CSS 脱节。改成看汉堡按钮是否可见，以后只改 CSS 一处：

```js
function drawerMode() {
  return !!burger && window.getComputedStyle(burger).display !== 'none';
}
```
`PKV4_VERSION` 与 `style.css` 的 `Version` 同步 4.0.0 → 4.0.1，否则线上浏览器还会拿旧 CSS/JS。

### 1.2 768：把溢出源头（页脚那一行）改成可换行

```css
/* 28p：父主题 .footer-bottom 是 flex+nowrap，只在 ≤767 才堆叠，768–~940 正好放不下 */
.footer-bottom { flex-wrap: wrap; row-gap: 10px; }
.footer-bottom > * { min-width: 0; }
```

规则与语种、宽度都无关，也没有用 `overflow-x: hidden`、没有隐藏报价 CTA、没有动价格文件。

## 2 · 验收

### 2.1 溢出扫描（`deploy/verification-28p/header-scan.json`）

12 语种 × 20 宽度 × 4 页面（首页 / `/inquiry/` / `/get-a-quote/` / `/product-drawer-jewelry-box/`）= **960 组**，宽度：375、560、561、768、820、900、901、1024、1100、1293（W−1）、1294（W）、1295（W+1）、1280、1440、1700、1920，另加 1350、1351、1400、1560 守住副链接重新出现的边界。

```
扫描组合：960（12 语种 × 20 宽度 × 4 页面）
溢出条数：0
  375   OK 48/48      560   OK 48/48      561   OK 48/48      768   OK 48/48
  820   OK 48/48      900   OK 48/48      901   OK 48/48      1024  OK 48/48
  1100  OK 48/48      1293  OK 48/48      1294  OK 48/48      1295  OK 48/48
  1280  OK 48/48      1440  OK 48/48      1700  OK 48/48      1920  OK 48/48
  1350  OK 48/48      1351  OK 48/48      1400  OK 48/48      1560  OK 48/48
结论：0 处溢出，scrollWidth === innerWidth 全部成立
```

另外加密扫了 **1295–1800 每 5 px（102 个宽度 × 12 语种 = 1224 组）**：0 溢出——因为 28m/28o 那些按语种的阈值规则还在这段区间里生效，改断点没有把它们撞坏。

> 扫描脚本第一版在 1920→1024 这种大跳变后只等 30 ms 就读 `scrollWidth`，读到过一次假溢出（zh-TW 首页读出 3188，实际是旧视口下的网格）。已改为 `waitForFunction(innerWidth === w)` + 双 `requestAnimationFrame`，同一条重复 9 次都是 1024。

### 2.2 汉堡模式功能（`deploy/verification-28p/burger-functional.json`）

en / de / nl / ar × 768 / 1024 共 8 组：

| 语种 | 宽 | 菜单打开 | 菜单关闭 | 点导航链接自动关 | 报价按钮 | 语言切换 |
|---|---|---|---|---|---|---|
| en | 768 / 1024 | is-open，7 项，高 836 px | is-open=false，按钮文字回 Menu | 关闭 | 可见、在视口内（右缘 515） | 点 `Deutsch` → `/de/` |
| de | 768 / 1024 | 同上 | 同上 | 关闭 | 可见、在视口内 | 点 `English` → `/` |
| nl | 768 / 1024 | 同上 | 同上 | 关闭 | 可见、在视口内 | 点 `English` → `/` |
| ar | 768 / 1024 | 同上 | 同上 | 关闭 | 可见、在视口内 | 点 `English` → `/` |

语言切换并不是新入口：原来的语言选择器是插件 `packrivo-language-selector` 注入到 `.pk-actions` 里的 `#packrivo-language-root`（**不是**被汉堡模式隐藏的 `.pk-lang` 那个 span），汉堡模式下它一直在，点开是 `<dialog>`，里面是 `button[data-lang]`，点一下直接跳语种页。

汉堡**打开**状态下也在 768 / 1024 / 1294 各扫了 12 个语种：全部 0 溢出且菜单确实打开。

### 2.3 截图（`deploy/verification-28p/` 共 16 张）

`header-{en,de,nl,ar}-{768,1024}-{closed,open}.png`，关闭态裁 420 px 高、打开态裁 720 px 高。因为不能靠肉眼判图，对打开态做了像素统计确认菜单真的画出来了：每张 11–15 个文字横条、顶部 400 行 4,200–6,400 个暗像素（背景灰 252，95% 为背景色，说明是浅色抽屉 + 深色菜单文字）。

### 2.4 回归（`deploy/verification-28p/regression.txt`）

```
rigid     $2.83 / $1,417.43      mailer    $2.48 / $1,239.04
sticker   $0.96 / $479.63        shipping  $2.31 / $1,156.69
200 /  200 /products/  200 /jewelry-boxes/  200 /blog/  200 /get-a-quote/  200 /inquiry/  200 /product-drawer-jewelry-box/
ar@1440: {"sw":1440,"iw":1440,"dir":"rtl","headerH":65,"brandLeft":1162,"cta":{"text":"أنشئ عرض سعر","left":101,"right":227,"visible":true}}
```

ar 在 1440 仍是 RTL（品牌在右、CTA 在左），0 溢出。

### 2.5 重新打包（`deploy/verification-28p/zip-check.txt`）

子主题重打成 38 条目 / 790,164 B / `5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9`，三份副本（`deploy/`、`packrivo-backup/packrivo-wp/`、`packrivo-wp/`）同一哈希。

```
$ unzip -Z1 /tmp/packrivo-home-v4.zip      → 条目数 38，条目名里反斜杠字符总数 0
$ php verify-one.php …（容器里 PHP ZipArchive 解压后与工作源比对）
条目 38，条目名含反斜杠 0 / 解压后文件 38，工作源文件 38 / 只在包里 0，只在源里 0，内容不同 0
结论: 通过（逐字节一致，无反斜杠条目）
$ wp theme install /tmp/packrivo-home-v4.zip --force --path=/tmp/wp2
The parent theme, Packrivo Static Bridge 1.2.38, is currently installed.
Theme updated successfully.  Success: Installed 1 of 1 themes.
packrivo-home-v4,active,none,4.0.1,,off
packrivo-static-bridge-1.2.38,parent,none,1.2.38,,off
$ diff -r /tmp/wp2/wp-content/themes/packrivo-home-v4 /tmp/src/packrivo-home-v4   → exit 0
```

装之前按 RUNBOOK 第 5 步的坑先做了目录级备份（`/tmp/backup-theme-28p`，16 个顶层项）。报价插件**没有重打**，哈希仍是 `096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93`。

### 2.6 文档与源码同步

- `deploy/RUNBOOK-launch.md`：包表与第 5 步校验值换成 `790,164 B` / `5974ae0c…`；附录 A 删掉那两行页头溢出，并在表格上方注明「28p 已修」写清修法与验证位置。
- `deploy/README.md`：子主题包那行换成 `771.6 KB（790,164 B）` / `5974ae0c…`。
- `packrivo-backup/packrivo-wp/packrivo-home-v4/` 与工作源 `diff -r`：**exit 0，0 差异**（`deploy/verification-28p/diff-r.txt`）。

一个流程上的发现：本机测试站点的 bind mount 指向 `packrivo-live/themes/packrivo-home-v4`，而打包源是 `packrivo-wp/packrivo-home-v4`——两份是独立目录。改完源码必须同步到测试副本再量，否则量到的是旧 CSS（本轮第一次扫描就是这么读到 1024 还溢出 93 px 的，`site.css?ver=` 还是 4.0.0）。

## 3 · 提交

```
$ sha256sum deploy/packrivo-home-v4.zip
5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9  deploy/packrivo-home-v4.zip
$ sha256sum deploy/packrivo-quote.zip
096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93  deploy/packrivo-quote.zip
```

（`git status` 与 `git log -3 --stat` 原文见本节末尾的收尾记录。）

## 4 · 剩下的已知问题

| 问题 | 实测 | 何时处理 |
|---|---|---|
| `/ar/<文章路径>/` 返回 404 | `noindex,follow` + 英文版链接 | 等文章翻译排期 |
| `/contact/` 301 到 `/inquiry/` | 生产与本地一致 | 若要做独立联系页再说 |
| 副链接在 1440–1780 之间按语种隐藏 | 桌面页头放不下时的既有规则（本轮把下限接到 1295、上限接到 1439） | 属预期行为 |

### 3.1 收尾记录（提交后原样粘贴）

```
$ git status
On branch main
Your branch is up to date with 'origin/main'.

nothing to commit, working tree clean
```

```
$ git log -3 --stat   （HEAD = 63a5214026a9aa577aa0c852fe9674f4782b7a6d）
commit 63a5214026a9aa577aa0c852fe9674f4782b7a6d
Author: jiaxiangli477-stack <jiaxiangli477@gmail.com>
Date:   Wed Oct 28 08:18:57 2026 -0700


 deploy/README.md                                  |    2 +-
 deploy/RUNBOOK-launch.md                          |    8 +-
 deploy/packrivo-home-v4.zip                       |  Bin 788955 -> 790164 bytes
 deploy/verification-28p/breakpoint-W.json         |  307 +
 deploy/verification-28p/burger-functional.json    |  418 +
 deploy/verification-28p/diff-r.txt                |    2 +
 deploy/verification-28p/header-ar-1024-closed.png |  Bin 0 -> 122973 bytes
 deploy/verification-28p/header-ar-1024-open.png   |  Bin 0 -> 16977 bytes
 deploy/verification-28p/header-ar-768-closed.png  |  Bin 0 -> 44633 bytes
 deploy/verification-28p/header-ar-768-open.png    |  Bin 0 -> 16271 bytes
 deploy/verification-28p/header-de-1024-closed.png |  Bin 0 -> 122237 bytes
 deploy/verification-28p/header-de-1024-open.png   |  Bin 0 -> 17323 bytes
 deploy/verification-28p/header-de-768-closed.png  |  Bin 0 -> 39469 bytes
 deploy/verification-28p/header-de-768-open.png    |  Bin 0 -> 16613 bytes
 deploy/verification-28p/header-en-1024-closed.png |  Bin 0 -> 85730 bytes
 deploy/verification-28p/header-en-1024-open.png   |  Bin 0 -> 16830 bytes
 deploy/verification-28p/header-en-768-closed.png  |  Bin 0 -> 35906 bytes
 deploy/verification-28p/header-en-768-open.png    |  Bin 0 -> 16119 bytes
 deploy/verification-28p/header-nl-1024-closed.png |  Bin 0 -> 62145 bytes
 deploy/verification-28p/header-nl-1024-open.png   |  Bin 0 -> 17313 bytes
 deploy/verification-28p/header-nl-768-closed.png  |  Bin 0 -> 40113 bytes
 deploy/verification-28p/header-nl-768-open.png    |  Bin 0 -> 16590 bytes
 deploy/verification-28p/header-scan.json          | 9653 +++++++++++++++++++++
 deploy/verification-28p/regression.txt            |   18 +
 deploy/verification-28p/zip-check.txt             |   26 +
 dsh-out/DSH-RESULT-2026-10-28m.md                 |  173 +
 packrivo-wp/packrivo-home-v4.zip                  |  Bin 788955 -> 790164 bytes
 packrivo-wp/packrivo-home-v4/assets/nav.js        |   10 +-
 packrivo-wp/packrivo-home-v4/assets/site.css      |   20 +-
 packrivo-wp/packrivo-home-v4/functions.php        |    2 +-
 packrivo-wp/packrivo-home-v4/style.css            |    2 +-
 31 files changed, 10630 insertions(+), 11 deletions(-)

commit 416b6dbc1223a7a05e087260a5de2581b80d0d41
Author: jiaxiangli477-stack <jiaxiangli477@gmail.com>
Date:   Wed Oct 28 07:01:40 2026 -0700


 packrivo-v2/docs/DSH-TASK-2026-10-28p.md | 52 ++++++++++++++++++++++++++++++++
 1 file changed, 52 insertions(+)

commit 57f6d26ae60b7f739b84f9adc0e17117646891c6
Author: jiaxiangli477-stack <jiaxiangli477@gmail.com>
Date:   Wed Oct 28 06:53:17 2026 -0700


 dsh-out/DSH-RESULT-2026-10-28m.md | 96 +++++++++++++++++++++++++++++++++++++++
 1 file changed, 96 insertions(+)
```

```
$ sha256sum deploy/packrivo-home-v4.zip deploy/packrivo-quote.zip
5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9  deploy/packrivo-home-v4.zip
096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93  deploy/packrivo-quote.zip
```
