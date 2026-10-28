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
| 4 终态 | 见 `packrivo-backup` 最新提交 | 两处 header 溢出修复、142 页标题最终复验、重打上线包、本文件更新 |

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
