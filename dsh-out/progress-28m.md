# dsh 进度 · 2026-10-28m

任务单：`E:\破甲工作区\packrivo-v2\docs\DSH-TASK-2026-10-28m.md`
本地站：`http://localhost:8081`（容器 `packrivo-live-wp-1` 已在跑）
Docker：`D:\Docker\App\resources\bin\docker.exe`

**挂载事实（本段核对过，会影响改哪里）：** 容器的子主题和报价插件不是从 `packrivo-live\themes` 读，而是直接 bind 到源码目录：
- `E:\破甲工作区\packrivo-wp\packrivo-home-v4` → `/var/www/html/wp-content/themes/packrivo-home-v4`
- `E:\破甲工作区\packrivo-wp\packrivo-quote` → `/var/www/html/wp-content/plugins/packrivo-quote`
- `E:\破甲工作区\packrivo-backup\deploy\i18n-merged\*` → `/var/www/html/wp-content/uploads/packrivo-i18n/*`（容器内只读，改宿主机文件即生效）

**任务单说的「28l 用到的几个 overlay 文件」在 28l 文档里找不到**：`CODEX-RESULT-2026-10-28l.md` 只有 `docker exec` / `docker cp` 用法，没有 compose overlay 段。当前容器已按上面的 bind 在跑，本轮沿用现状，没有重启容器。

---

## 第 1 段 · 首页 8 张产品卡 —— 已完成

### 1.1 改前盘点（`$home_specs` 7 项逐项缺什么）

命令（脚本拷进容器跑，避免 PowerShell 管道吞行）：

```powershell
& D:\Docker\App\resources\bin\docker.exe cp diag-home-cards.php packrivo-live-wp-1:/tmp/diag-home-cards.php
$o = & D:\Docker\App\resources\bin\docker.exe exec packrivo-live-wp-1 php /tmp/diag-home-cards.php 2>&1
Set-Content -LiteralPath diag-home-cards.json -Value $o -Encoding utf8
```

原样输出（提取字段）：

| slug | 页面 | 封面 | 引擎风格 | 引擎价格 | 缺什么 |
|---|---|---|---|---|---|
| product-drawer-jewelry-box | publish | 有 | rigid | 2.83 | 无 |
| product-magnetic-jewelry-box | publish | **无** | rigid | 2.83 | 封面 |
| product-lid-and-base-jewelry-box | publish | 有 | rigid | 2.83 | 无 |
| product-jewelry-box-with-insert | publish | 有 | rigid | 2.83 | 无 |
| product-window-lid-rigid-gift-box | publish | 有 | rigid | 2.83 | 无 |
| product-corrugated-roll-end-hinged-lid | publish | 有 | mailer | 2.48 | 无 |
| product-sheet-labels | publish | **无** | sticker | 0.96 | 封面 |

所以改前只出 **5 张**：磁吸首饰盒和标签卡都卡在「没有封面」。页面全部存在、全部 publish，没有「页面不存在」的情况。

全站 134 个产品页的分档（`verification-28m/home-specs-before.txt`）：**封面+价格都齐 20 个 / 有封面缺价格 83 个 / 有价格缺封面 14 个**。第 8 张是从「都齐的 20 个」里按品类挑的，不是新造数据。

### 1.2 改了什么

**`packrivo-wp\packrivo-home-v4\front-page.php`**

1. `$home_specs` 从 7 项 `[slug, group, label]` 改成 8 项 `[slug, group]`；卡片标题改为取页面自己的已审买家标题（`get_the_title`），不再用模板里手写的短标签。例如原来写死 `Custom Drawer Jewelry Boxes`，页面已审标题是 `Custom Drawer Jewelry Boxes with Logo`，现在以页面为准。
2. 8 项最终清单：

| # | slug | 分组 | 卡片标题（= 页面已审标题） | From |
|---|---|---|---|---|
| 1 | product-drawer-jewelry-box | Jewelry boxes | Custom Drawer Jewelry Boxes with Logo | $2.83 |
| 2 | product-lid-and-base-jewelry-box | Jewelry boxes | Custom Lid-and-Base Jewelry Boxes | $2.83 |
| 3 | product-jewelry-box-with-insert | Jewelry boxes | Custom Jewelry Boxes with Fitted Inserts | $2.83 |
| 4 | product-window-lid-rigid-gift-box | Gift boxes | Custom Window Lid Rigid Boxes | $2.83 |
| 5 | product-hinged-nonmagnetic-rigid-box | Gift boxes | Custom Hinged Rigid Gift Boxes | $2.83 |
| 6 | product-corrugated-roll-end-hinged-lid | Mailer boxes | Custom Hinged Lid Boxes with Logo | $2.48 |
| 7 | product-euro-tote-paper-carrier | Paper bags | Custom Paper Gift Bags with Logo | $1.63 |
| 8 | product-sos-paper-gift-bag | Paper bags | Custom SOS Paper Bags Bulk | $1.63 |

**`packrivo-wp\packrivo-quote\packrivo-quote.php`**：`render_shortcode()` 在带 `product` 时向主题取「该结构的参考规格」，渲染成 `data-length/width/height/material/print/finish` 挂在 `.pkq-root` 上（新过滤器 `pkq_reference_spec`）。没有 `product` 参数时行为不变。

**`packrivo-wp\packrivo-home-v4\functions.php`**：新增 `pk_quote_reference_spec()` 挂上该过滤器，用现成的 `pk_card_reference()`（rigid 9.5×9.5×4 rigidboard、mailer 30×22×12 kraft、bag 18×8×22 kraft …）供规格。

**`packrivo-wp\packrivo-quote\assets\pkq-quote.js`**：初始状态读上面那几个 data 属性；没给才退回原来的 30×22×12 / kraft（并保留 rigid→rigidboard、pouch/poly→kraftpoly 的老兜底）。

**为什么动配置器：** 不改之前卡片写 `from $2.83`，点进 `?style=rigid&qty=500&product=…` 页面却按自己的 30×22×12 默认算出 **$7.45**，是同一张卡自相矛盾。改的是「打开时用哪套规格」，`includes/class-pkq-pricing.php` 和 JS 里的价格公式一行没动（`git diff --stat` 见下）。

### 1.3 命令与原始输出

```
$ node check-home.mjs http://localhost:8081/
{ "status": 200, "h1": "Custom packaging for jewelry, candles & gifts.",
  "heroFrom": "$1.63 / unit", "cardCount": 8, ... }
```

```
$ node verify-card-prices.mjs
{ "cardCount": 8, "heroFrom": 1.63, "minCardPrice": 1.63, "heroMatchesMin": true,
  "rows": [ 8 条，cardPrice 与 quotePrice 全部 same: true ] }
PASS 8/8 卡片价与报价页实算一致
```

改前同一脚本的结果（留证，说明改动必要）：

```
Custom Drawer Jewelry Boxes with Logo        cardPrice 2.83  quotePrice 7.45  same false
Custom Lid-and-Base Jewelry Boxes            cardPrice 2.83  quotePrice 7.45  same false
Custom Jewelry Boxes with Fitted Inserts     cardPrice 2.83  quotePrice 7.45  same false
Custom Window Lid Rigid Boxes                cardPrice 2.83  quotePrice 7.45  same false
Custom Hinged Rigid Gift Boxes               cardPrice 2.83  quotePrice 7.45  same false
Custom Hinged Lid Boxes with Logo            cardPrice 2.48  quotePrice 2.48  same true
Custom Paper Gift Bags with Logo             cardPrice 1.63  quotePrice 1.93  same false
Custom SOS Paper Bags Bulk                   cardPrice 1.63  quotePrice 1.93  same false
FAIL 存在不一致
```

```
$ node shot-home.mjs after
[ { width: 375,  cards: 8, scrollWidth: 375,  innerWidth: 375,  heroFrom: "From · 500 pcs$1.63 / unit …" },
  { width: 768,  cards: 8, scrollWidth: 768,  innerWidth: 768,  heroFrom: "From · 500 pcs$1.63 / unit …" },
  { width: 1280, cards: 8, scrollWidth: 1280, innerWidth: 1280, heroFrom: "From · 500 pcs$1.63 / unit …" } ]
```

截图：`deploy/verification-28m/home-after-375.png`、`-768.png`、`-1280.png`（另各有一张 `-full.png` 整页图）。

### 1.4 没做到、按要求跳过并记录（不猜）

1. **标签（Labels）类没有卡。** 5 个 label 相关页面 `product-sheet-labels / product-coated-labels / product-circle-roll-labels / product-square-roll-labels / product-rectangle-roll-labels` 全部**没有封面**；只有 `product-sheet-labels` 有 sticker 引擎价 $0.96。给它们配封面要写数据库（本段授权只到子主题/插件/字典/路径表），媒体库里跟 label 沾边的只有 3 张博客题图（`B013-preprinted-label-cover.png`、`B017-tapered-label-cover.png`、`B017-tapered-label-instruction.webp`），拿博客题图当产品实拍会变成没有来源的图，**跳过**。
2. **首饰袋（jewelry pouch）类没有卡。** `textile-jewelry-pouch`、`jewelry-pouch-and-box-set` 无封面无价；有封面的 `product-drawstring-fabric-gift-bag`、`product-zippered-fabric-gift-pouch`、`product-fold-over-flap-fabric-pouch` 都因为 `pk_engine_style_for()` 第 155 行明确排除 `velvet|fabric|textile|…|pouch` 而拿不到价格。引擎确有 `pouch` 档，但它的参考规格是 16×6×23 kraftpoly（立式袋），拿它给布质首饰袋报「from」价等于用错材料族定价，属于「拿不准」，**不加映射、跳过**。
3. 结果：8 张覆盖到首饰盒、礼盒、邮寄盒、纸袋 4 类；标签和首饰袋两类空着，等站长定封面素材或确认 pouch 定价口径后补第 9/10 张候选。

### 1.5 还剩什么

- 第 2 段已完成（见下）。第 3/4 段未开始。

---

## 第 2 段 · 报价页 11 语种 —— 已完成

### 2.1 先把字符串从源码列全（`dsh-in/quote-strings-28m.json`）

**不是点页面抄的，是从 5 个源码文件抽字面量再和人工清单交叉核对。** 抽取脚本 `extract-quote-strings.mjs`：

- 源文件：`packrivo-quote.php`、`assets/pkq-quote.js`、`includes/pricing.json`、`includes/class-pkq-rest.php`、`includes/class-pkq-pricing.php`
- 抽出源码字面量 147 条；人工清单 107 个 id（99 个不同英文串，`Size`/`Quantity`/`Material` 等在步骤条和侧栏各出现一次）
- 交叉结果：清单里 0 条在源码中找不到；源码里不在清单的条目全是标识符、CSS 类、字段名、`__()` 文案键、邮件正文行（`Name:`/`Email:`/`Admin:` 等只发给站长，不在买家界面）

覆盖面（按组）：chrome 3 / 步骤条 5 / 第1步 2 / 第2步 5 / 盒型 9 / 材质 14 / 印刷 4 / 表面处理 7 / 第4步 5 / 第5步 7 / 表单 7 / 导航 2 / 侧栏 9 / noscript 1 / 报错 14 / JS 兜底 8。

关键几条容易漏的：`from {price}`（盒型价格前缀）、`per unit · {qty} units`、`Rush production (+{percent}%)`、`Request this quote · {total}`、`We reply within one business day with a proof timeline. Reference {ref}.`、REST 的 13 条错误、`pricing.json` 里 9 个盒型 + 7 材质 + 4 印刷 + 7 表面处理的 label 与 7 条 note。

### 2.2 11 份译文（`dsh-out/quote-strings-28m.json`）

格式按要求 `{"英文": {"ar": …, "de": …, …}}`：99 个键 × 11 语种 = **1089 格**，脚本 `merge-quote-i18n.mjs` 逐格校验：

- 11 语种全部齐全，无缺 id、无未知 id、无空值
- 占位符 `{price}/{qty}/{percent}/{total}/{ref}/{min}/{max}` 在译文里原样保留（逐条比对占位符集合）
- 除下列真实同形词外，没有一条译文等于英文：`Material`（de/es/pt）、`Total`（es/fr/pt）、`Website`（de/nl）——这 6 个在各自语言里本来就是这么写，已在脚本里列为白名单并在此记录

术语口径：报价＝Angebot/devis/presupuesto/preventivo/prijsopgave/orçamento/見積もり/견적/报价/報價/عرض سعر；盒型按品类落地（邮寄盒 Versandschachtel、精装盒 Hartschachtel/貼り箱/精装盒、折叠纸盒 Faltschachtel/化粧箱/折叠纸盒 …）；材质按行业写法（`Kraftwellpappe (E-Welle)`、`複両面段ボール`、`이중 골판지`、`双瓦楞纸板`、`كرتون مموج مزدوج`）。

**一处要请站长确认（未自行改动）：** 英文源串本身带技术词——`Kraft E-flute`/`White E-flute`/`Rigid greyboard 2 mm`/`An engineer confirms the dieline…`。任务口径写「买家看得到的标题、按钮、卡片不放 E-flute / SBS / GSM / mm / dieline」，但本段要求是翻译、且「英文不变」，所以译文按原义翻（`E-Welle`、`2 mm`、`Stanzvorlage`），没有自行删词。要不要换掉英文源串，留给站长定。

### 2.3 插件按语种取词（不靠前端扫描替换）

页面级翻译是 mu-plugin 用输出缓冲做的 HTML 替换，**管不到 JS 渲染出来的节点**——这就是必须在插件里做一份的原因。

- 新增 `includes/quote-i18n/<lang>.json`（11 份，共 59,829 B）：`{ 英文源串: 译文 }`
- `PKQ_Plugin::lang()` 读 `packrivo_lang()`；`PKQ_Plugin::strings()` 只加载当前语种那份；`PKQ_Plugin::t()` 查表、查不到回英文
- `wp_localize_script` 新增 `strings`（当前语种整张表）与 `lang`
- `assets/pkq-quote.js`：新增 `t()` / `tf()`（模板占位替换）/ `serverText()`（把后端英文报错映射回当前语种，含 `Quantity must be between N and M.` 的数字模式）；所有可见字面量都过 `t()`，`normaliseConfig()` 把 `pricing.json` 覆盖进来的 label 也过一遍 `t()`
- `render_shortcode()` 的 noscript 文案与 `i18n.*` 六条也走 `t()`
- `ar` 时 `.pkq-root` 输出 `dir="rtl"`（与 `<html dir="rtl">` 一致）

**RTL 溢出修掉一处真问题：** `.pkq-hp`（蜜罐字段）原来用 `left:-9999px`，RTL 下把滚动区顶到 **10374 px**。改成 `clip`/`clip-path` 隐藏（不占坐标），ar 375px 下 `scrollWidth` 回到 375。

**本地路径表：** `deploy/i18n-merged/paths.json` 加入 `/get-a-quote/`（146 条）。这是本地副本，生产用同一份文件，第 4 段随包交付，不需要另写补丁。

**本地字典：** 11 份 `*-dict.json` 补了报价页标题 —— `title`/`text` 加 `"Get a Quote"`，`title`/`attr` 加 `"Get a Quote – Packrivo"`（实体解码后 en dash 两侧有空格，第一版键少了个空格，已删掉重写）。es/fr/nl/pt 四份字典本来就有 `Get a Quote` 的译文，直接沿用（`Solicitar cotización`/`Obtenir un devis`/`Vraag een offerte aan`/`Solicite uma Cotação`），避免同页两种说法。

### 2.4 语种页的报价按钮改去配置器

`assets/cta-28l.js` + `functions.php`：

- `functions.php` 新增 `pkv4_style_map()`：`slug → 引擎风格`，只取带 `_packrivo_engine_style` 的已发布页面，结果放 1 小时 transient；作为 `pkv4QuoteTargets.styles` 传给前端（列表页的链接本身不带 style，只能靠这张表）
- `cta-28l.js`：有 product 且能查到 style 且当前是语种页 → `/{lang}/get-a-quote/?style=<style>&qty=500&product=<完整 slug>`；查不到 style → 保持 `/{lang}/inquiry/?product=<去前缀 slug>`；英文页逻辑原样不动

实测（`deploy/verification-28m/cta-28m.txt`，5 个语种 × 4 类页面，全部 200）：

| 页面 | 英文 | de | ja | zh-CN | ar |
|---|---|---|---|---|---|
| `/products/` 有引擎价 5 张卡 | `/get-a-quote/?style=rigid&qty=500&product=…` | `/de/get-a-quote/?style=rigid&qty=500&product=…` | 同左 ja | 同左 zh-CN | 同左 ar |
| `/products/` 无引擎价 20 张卡 | `/get-a-quote/?style=&qty=500&product=…` | `/de/inquiry/?product=auto-lock-bottom-carton` | `/ja/inquiry/…` | `/zh-CN/inquiry/…` | `/ar/inquiry/…` |
| 产品页 sticky | 不变 | `/de/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | 同左 | 同左 | 同左 |
| 页头「Get a Quote」 | `/get-a-quote/` | `/de/inquiry/` | `/ja/inquiry/` | `/zh-CN/inquiry/` | `/ar/inquiry/` |

页头那个不带 product 的按钮**保持 28l 的去向（inquiry）没有动**：它没有 product，也就没有引擎映射，任务口径里「没有引擎映射的保持 /{lang}/inquiry/」直接适用；要改成配置器属于另一个决定，写在这里由站长定。

### 2.5 验收原始数据

**11 语种 × 5 步可见英文 = 0**（`quote-11-summary.json`，判定方式：把每个文本节点和字典英文键比对，若与英文键相同而该语种确有不同译文就算泄漏）：

```
ar 0  de 0  es 0  fr 0  it 0  ja 0  ko 0  nl 0  pt 0  zh-CN 0  zh-TW 0
```

**四组价格与英文完全一致**（实际跑了全部 11 个语种，比要求的四组更严）：

```
englishPrice $2.83  englishTotal $1,417.43
ar/de/es/fr/it/ja/ko/nl/pt/zh-CN/zh-TW → $2.83 / $1,417.43   allPricesEqual true
```

**ar RTL 与溢出**：`htmlDir=rtl rootDir=rtl`，5 步 `scrollWidth` 全部 375（视口 375），无任何元素越界；截图 `quote-ar-375.png`。

**RTL/de 截图**：`quote-ar-375.png`、`quote-de-375.png`；英文 1280 对照 `quote-en-1280.png`。

**价格计算一行没改**：`includes/class-pkq-pricing.php`、`includes/pricing.json`、`includes/class-pkq-rest.php` 三个文件与段 1 结束时的备份副本 **SHA256 完全一致**：

```
class-pkq-pricing.php  same=True  DC41149880EC…
pricing.json           same=True  6D1E7CA3709D…
class-pkq-rest.php     same=True  CB2D92E82BA5…
```

**英文页回归**：`/get-a-quote/` 仍返回 200，`PKQ.lang` 为空串，`i18n` 六条仍是英文原文，价格 `$2.83 / $1,417.43` 与改前一致。


### 2.5b 段 2 验收补齐（补 2.5 里没做的三张表）

**4 组价格 × en / ar / de / ja 数字完全一致**（`deploy/verification-28m/quote-4groups.json`，`?style=<style>&qty=500&product=<slug>`，读 `.pkq-price` 与 `.pkq-specs` 的「Total」或「إجمالي الطلب」）：

| 组 | style / product | unit（4 语种） | total（4 语种） | identical |
|---|---|---|---|---|
| rigid 500 | `rigid` / `product-drawer-jewelry-box` | `$2.83` | `$1,417.43` | true |
| mailer 500 | `mailer` / `product-corrugated-roll-end-hinged-lid` | `$2.48` | `$1,239.04` | true |
| sticker | `sticker` / `product-sheet-labels` | `$0.96` | `$479.63` | true |
| shipping | `shipping` / `product-regular-slotted-shipping-carton` | `$2.31` | `$1,156.69` | true |

**ar 375 px 两张图**：`quote-ar-375-step1.png`（第 1 步，步骤条 + 选项）、`quote-ar-375-summary.png`（`.pkq-aside` 价格摘要，`scrollY=1184`，摘要 350 px 高）。第二张里读到的原文即译文：`الشكل`／`المقاس 9.5 × 9.5 × 4 cm`／`المادة كرتون رمادي صلب 2 مم`／`إجمالي الطلب $1,417.43`，`document.dir=rtl`，`scrollWidth=375`。

**en / ar / de × 首页 / 产品页 / `/products/` 的报价按钮 href 全部 200**（`deploy/verification-28m/quote-buttons-200.txt`，105 条去重链接，0 条异常；全程只 GET，未提交任何表单或报价）。

### 2.6 还剩什么 / 没做的

- `/product-textile-jewelry-pouch/` 英文页本身 404（路径表里有、页面取不到），所以「无引擎映射」的对照改用 `product-auto-lock-bottom-carton`。这是既有问题，本轮没动。
- 第 3/4 段未开始。


---

## 第 3 段 · 评审里的 P2 视觉 —— 已完成

### 3.1 手机首页首屏：标题 + 主按钮 + 一张实物图

改的是结构，不是压缩字号：

- `front-page.php` 的 hero 从「一个 text div 里塞 h1/lede/cta/stats + 一个 figure」拆成 **copy / media / stats 三个 grid 子元素**，用 `grid-template-areas: "copy media" "stats media"` 在桌面还原原来的左右分栏（copy 与 stats 在左列、media 跨两行），手机改成 `"copy" "media" "stats"`——**图片天然落在按钮与数据之间**，不用 `order` 硬搬、也不动 DOM 顺序里的标题。
- 桌面几何没变：copy 154–730，media 786–1286（并排），stats 154–730 在 copy 下方；`sideBySide=true`、`statsBelowCopy=true`。
- 375 px 首屏（视口 812）：

| 量 | 改前 | 改后 |
|---|---|---|
| h1 | 147–299（152 高） | **127–223**（96 高） |
| 主按钮 | 500–538，**38 高** | **338–390，52 高** |
| 产品实物图 | 顶 798（首屏只剩 14 px） | **408–653，245 px 全在首屏内** |
| 4 项数据 | 624–766，142 高，纵向 4 条 | **687–798，111 高，2×2 紧凑分组** |
| 数据项数 | 4 | **4（一项没删：From $1.63 / MOQ 500 / 7–30 days / 8–15%）** |

`heroImageVisibleInFirstScreen=true`、`ctaAboveFold=true`。

### 3.2 /products/ 手机端密度 + 桌面搜索排序同行

- **筛选入口紧凑化**：`.pk-filterbar` 手机改单列网格；`.pk-chips` 从换行铺成 4 行改成 **一行横向滚动**（`overflow-x:auto`、隐藏滚动条），实测 `clientWidth=327 / scrollWidth=794`、`overflowX=auto`，页面本身不溢出。
- **搜索与排序同一行**：`.pk-sort` 手机 `flex-wrap`，搜索框 `flex:1 1 150px`、下拉 `flex:1 1 130px`、按钮 `auto`；两者 `top` 都是 476（同一行），桌面 1440 两者 `top` 都是 492（同一行）。
- **说明文字压缩**：目录页 hero 的 lede 手机 `font-size:13.5px; line-height:1.4`，claims 那行改 `.pk-strip` 等宽小字；主 `.pk-lede` 全局手机 4 行截断（含「Filter by family, sort by newest or name…」这段重复说明）。
- 结果：

| 量（375 px） | 改前 | 改后 |
|---|---|---|
| 筛选栏高度 | 445–896 = **451 px** | 310–512 = **202 px** |
| 第一张商品卡顶点 | **944 px** | **560 px**（最终态复测；中间态为 651） |
| 桌面第一张卡顶点 | 619 | 619（未受影响） |

### 3.3 一套控件系统

同高度 / 同边框 / 同字重 / 同焦点环，落在 `assets/site.css` 追加段与 `assets/pkq-quote.css` 追加段：

- 主题侧：`.pk-btn` / `.pk-btn-ghost` / `.pk-btn-light` `min-height:44px`、`border-radius:6px`、`font-weight:600`；`.pk-chip` 由 35 px pill 改 44 px、`border-radius:6px`；`.pk-sort input[type=search]` / `select` / `button` 全部 44 px、同边框、同圆角；`.pk-actions .pk-cta` 也补到 44。
- 报价插件侧：`.pkq-btn` / `.pkq-btn-ghost` / `.pkq-submit` / `.pkq-chip` / `.pkq-input` / `.pkq-step` 统一 `min-height:44px`、`border-radius:6px`、`font-weight:600`，手机 `.pkq-submit` 48 px。
- 焦点环统一 `outline: 2px solid` + `outline-offset: 2px`（主题用 `--pk-moss`，插件用 `#0c1411`），覆盖按钮、筛选、输入框、下拉、搜索按钮、报价步骤。
- **产品页底部固定按钮配色**：`.pr-sticky .pr-sticky-main` 原本是橙色渐变（`#F97316→#E04E15`、`color:#111`），父主题还在页脚 `@media(max-width:767px)` 里重声明了一次。子主题用 `body .pr-sticky .pr-sticky-main{...!important}` 覆盖成首页主按钮那套：`background: rgb(12,20,17)`（= `--pk-ink`）、`color: rgb(253,252,248)`（= `--pk-paper`）、`border` 同色。实测 375 / 1440 都是 `samePaletteAsHomePrimary=true`。
- **WhatsApp 悬浮按钮不挡底部按钮和内容**：`.pr-sticky`（65 px 高，底 747–812）与 `.contact-dock`（56 px，底 740–796）原本在手机上是叠着的，且悬浮按钮压住产品图库第三张「Structure」。处理：手机（≤900 px）在**带固定条的页面把悬浮按钮隐藏**（固定条自身已经带 WhatsApp 图标，父主题 `pr-sticky` 里就是 `Get a quote · MOQ 500` + `WA` 两个控件），其余页面 `body{padding-bottom:84px}` 给它留位。实测 `overlap=false`、`dockCoversBottomHits=[]`（改前是 `["Structure"]`）。

控件实测表（`deploy/verification-28m/s3-controls-after.txt`，取每个页面第一个命中元素）：

| 控件 | 改前 375 | 改后 375 | 改后 1440 |
|---|---|---|---|
| `.pk-btn` 主按钮 | 38×154 | **52×159**（hero）/ **44×123** | 44×154 |
| `.pk-btn-ghost` 次按钮 | 38×168 | 52×159 / 44×162 | 44×168 |
| `.pk-chip` 筛选 | 35×41 | **44×47** | 44×47 |
| `#pk-search` 搜索框 | 51×327（原生） | **44×170** | 44×211 |
| `#sort` 排序下拉 | 48×327（原生） | **44×150** | 44×170 |
| `.pk-sort button` 搜索按钮 | 无样式 | **44×79** | 44×79 |
| `.pk-cta` 页头 CTA | 43×127 | 44×127（手机隐藏） | 44×127 |
| `.pkq-btn` 报价主按钮 | 44×94 | 44×94 | 44×94 |
| `.pkq-step` 步骤 | 44×73 | 44×76 | 44×76 |

### 3.4 验收

- 截图 16 张，`deploy/verification-28m/`：`before-{home,products,product,quote}-{375,1440}.png` 与 `after-{同}-{同}.png`；另有 `s3-{before,after}-product-sticky-{375,1440}.png` 看底部固定条。
- **6 个页面 × en/ar，375 px `scrollWidth` 全部 375**（`s3-controls-after.txt` 尾部，判定时已排除横向滚动容器内部的元素，offenders 全为空）：

```
en/home=375  ar/home=375      en/products=375  ar/products=375
en/product=375 ar/product=375 en/quote=375     ar/quote=375
en/blog=375  ar/blog=375      en/inquiry=375   ar/inquiry=375
```

- 报价页 5 个步骤在 375 下排成 2 行（`01–04` 一行、`05` 第二行），`scrollWidth=375`，不影响流程。

### 3.5 注意 / 没做的

- **「改前」基线不是纯原始状态**：抓基线时 `/products/` 的 `pk-catalog-hero` / `pk-strip` 那套样式已经在树里（本会话更早写的），所以 3.2 的对照是「catalogue hero 已改 + 其余未改」→「全部改完」；首页、产品页、报价页的 before 是干净的。
- 底部固定条与 WhatsApp 悬浮按钮都是**父主题 `packrivo-static-bridge` 和 mu-plugin `packrivo-wave1.php` 注入的**，父主题与 mu 插件一个字节没动，全部改动落在子主题 CSS 覆盖；若以后父主题改类名，这两条覆盖要跟着改。
- cookie 同意条（`#pr-consent`）在测量脚本里只是临时 `display:none` 后才量最终态，页面本身没改它的行为。

## 第 4 段 · 总回归 + 上线包 —— 已完成

### 4.0 过程与两处回归修复

第一轮总回归（commit `2fe9f9c` / `0b36b5e`）发现并修复两处 header 横向溢出，修复只改 `packrivo-wp\packrivo-home-v4\assets\site.css`（容器 bind 该目录，改动即时生效）：

1. **375 px 首页 de/es/nl**：`.pk-more` 链接 `white-space:nowrap` 在不可换行的 `.pk-section-head` flex 行把页面最小内容宽顶出 375（实测 sw=411/378/418，移动 Chrome 随之把布局视口扩宽；`#pr-consent` 只是随视口变宽的受害者）。修复：`.pk-section-head` 加 `flex-wrap: wrap`（一行）。
2. **桌面 1440 px header de/es/nl（连带 fr/it/pt）**：header `.pk-actions` 行（`flex:0 0 auto`）里 `.pk-cta` 按钮 `white-space:nowrap`，德/西/荷译文更长把整行顶出视口（sw=1494/1450/1466）。在回归环境拦截外部字体、用兜底字体的最坏度量下溢出带为 1351–1491，fr/it/pt 也在带内先后溢出。修复：site.css 追加 6 条媒体查询，按语种只在各自放不下的宽度区间隐藏次要 `.pk-text-link`（Sample kit / Instant quote）：it 1351–1500、pt 1351–1540、fr 1351–1550、de 1351–1575、es 1351–1595、nl 1351–1655；en/ar/ja/ko/zh 与 ≤1350 px 不受影响，`.pk-cta` 主按钮始终保留。

### 4.1 142 页 <title>（最终态复验，本轮完成被中断的复验）

命令：`.tmp\packrivo-28m\check-titles-28m-v3.mjs`（产品页标题 = 已审标题 + ` | Packrivo`，主页面与 `dsh-out\hubs-27i.json` 8 条精确一致）：

```
合计 142 页（产品页 134 + 主页面 8）
非 200 或 <title> 数量≠1：0
与已审清单不一致：0
含 Sourcing 的标题：0
重复标题：0
最长标题：57 字符
```

证据：`deploy/verification-28m/titles-28m.csv` / `titles-28m.json`（本轮重跑覆盖）。注意：中间草稿脚本 `check-titles-final.mjs` 按严格相等比较会误报 136 条「不一致」——实际是它没实现「已审标题 + ` | Packrivo` 后缀」判定规则，已弃用，以 v3 为准。

### 4.2 可见英文节点（77 组合，最终态复跑）

7 页面 × 11 语种 = 77 组合，全部 0（报价页 5 个步骤另算，0 泄漏）。证据：`deploy/verification-28m/node-counts-28m.csv` / `.json`。

### 4.3 横向溢出最终复验

- **375 px：12 语种 × 7 路径 = 84 组合 `scrollWidth` 全部 375、0 溢出**（命令 `node scripts\28m-verify-overflow-all.mjs` → `dsh-out\overflow-28m-fixed.csv`，已同步 `deploy/verification-28m/`）。文章页非 en 语种按 `/?p=2937` 回退路径测量；`/ar/<文章路径>/` 的 404 及 404 页缺 viewport 问题仍在「还剩什么」（补丁未应用）。
- **1440 px header：`deploy/verification-28m/header-1440-12langs.txt`**——A) 1440 px 拦截字体，12 语种 sw 全 =1440（bad=0）；B) 1440 px 字体加载，12 语种全 =1440（bad=0）；C) 各语种隐藏区间顶 +1 px，链接重新出现且仍不溢出（bad=0）。
- after 截图：`home-de-375-after.png` / `home-es-375-after.png` / `home-nl-375-after.png`。

### 4.4 询盘页 / 4.5 4 组价格

同 DSH-RESULT 第 4.4 / 4.5 节：表单 14 个可见控件、`?product=` 能预选、Review 期间 0 条 POST；4 组价格与 27c 记录完全一致。证据：`inquiry-28m.json`、`inquiry-review-28m.json`、`quote-4groups.json`。

### 4.6 上线包与 README（本轮重打）

- `robocopy /E /MIR` 镜像 `packrivo-wp\packrivo-home-v4`、`packrivo-wp\packrivo-quote` → `packrivo-backup\packrivo-wp\`（镜像后 site.css SHA-256 与工作源相同）。
- 重打 `deploy\packrivo-home-v4.zip`：38 文件、788,171 B、SHA-256 `17ac86204236beedd066ab1efb24ce8160231ed38a2ea65106482c6cb02295d0`；`deploy\packrivo-quote.zip`：23 文件、57 KB、SHA-256 `3600ac5fc817d9414376949d534688952b0ba425f06810cc5373cf6b75fd0370`（插件未变，哈希与第一版相同）。
- `packrivo-wp\*.zip` 与 `packrivo-backup\packrivo-wp\*.zip` 同步为新包字节。
- `deploy/README.md` 28m 段哈希表已更新为最终包，并注明子主题包相对第一版只多了两处 header 修复的 site.css 改动；六步上线顺序与回滚、7 条剩余阻断照旧。

### 4.7 结果与提交

- `packrivo-v2\docs\DSH-RESULT-2026-10-28m.md` 已补记两处 header 修复与最终复验，同步到 `packrivo-backup\dsh-out\DSH-RESULT-2026-10-28m.md`。
- 提交（packrivo-backup）：13c7f1f 最终证据 + 重打上线包 + README 哈希 + 结果/进度更新；其前 \e9fd9a8\/\ed2c30b\ 为 72 行溢出证据与结果文档补记。

### 还剩什么（同 DSH-RESULT 结尾 7 条，照实）

1. 生产 `/get-a-quote/` 的 301 未处理（27e 补丁未执行）。
2. 生产字典/路径表必须以生产基线重新合并，不能直接覆盖。
3. MU 404 viewport 补丁未应用（`deploy/patches-28m/packrivo-i18n-404-viewport.patch`）。
4. `/ar/<文章路径>/` 仍是 404（缺 path 白名单与译文）。
5. `/contact/` 与 `/inquiry/` 标题重复，待站长定。
6. 英文源串里 `E-flute` / `2 mm` / `dieline` 仍在，待站长定口径。
7. 页头「Get a Quote」在语种页仍去 `/{lang}/inquiry/`，是否改配置器待定。

## 28n 收尾（2026-10-28）

任务书 `packrivo-v2/docs/DSH-TASK-2026-10-28n.md`，6 项。只在本地，没有部署、没有碰生产站、没有碰任何密码。

- 第 1 项：未提交改动已由 `13c7f1f` 解决，本轮未重做。
- 第 2 项：`assets/pkq-quote.css` 的 ≤900px 媒体块加 `.pkq-head{padding-inline:20px}`。375 px 实测：en 小标题 x 0→20、说明段 0/375→20/335；ar 小标题右缘 375→355、说明段 20→355；页面 scrollWidth 仍 375。
- 第 3 项：`pkq-quote.js` 与 `pricing.json` 的三个 label、`packrivo-quote.php:175` 的句子、11 份 `quote-i18n` 四键改完（键名同步改，译文取 28k 已审术语；es/fr/it 冠词已按名词性数修正）。插件目录 `E-flute`/`greyboard 2 mm`/`dieline` 各 0 命中，`cutting template` 13 命中。4 组价格 × 4 语种 = 16 格改前改后完全一致（`verification-28n/price-diff.json`）。
- 第 4 项：`header.php` 的 `$quote_url` 加语种前缀；`assets/cta-28l.js` 让报价类链接在语种页面去 `/{lang}/get-a-quote/`（含父主题注入的页脚 `.pr-quote-cta`，按类名处理，未改 MU）；11 份主字典补 `Instant quote`。12 前缀 × 2 页 = 24 组审计：可见英文 0、href 不在本语种报价页 0。
- 第 5 项：README 第 5 步写明「先报价重定向补丁、再 404 viewport 补丁，各自独立回滚」；本轮未应用。
- 第 6 项：README 末尾新增「上线后再做」，记录语种文章 404 与 `/contact/` 301。
- 重打包：子主题 38 文件 788,720 B `e7041551…`；插件 19 文件 54,581 B `6c225718…`（`demo/` 不进包）。解压后与工作源逐字节相同。
- 额外发现 → 已处理：页头 `Sample kit` 原无译文（1351 px 以上在语种页面显示英文，不属报价按钮）；最终收尾阶段已按 11 份主字典补齐译文（每份 `text` 加一键）随最终提交入库。
- 最终收尾（commit `45e2616` + 文档提交）：item4 终验产物 `verification-28n/buttons-28n-item4.{txt,json}`（16 组 `englishLeak=0`/`wrongHref=0`）；`packrivo-backup/packrivo-wp/` 镜像 `robocopy /MIR` 同步、`packrivo-wp/*.zip` 与镜像 zip 副本同步为新包字节；重跑 `pack-zips.ps1` 两个 zip 哈希不变（`e7041551…`/`6c225718…`），解压后与工作源逐字节相同（主题 38 / 插件 19，`demo/` 0 进包）；`pricing.json` 与 28n 前提交版逐路径 diff 仅 3 个 `label`、33 个数值字段 0 变化，4 组价格数字完全一致；除并行 28o 会话未跟踪的 `deploy/verification-28o/` 外，工作树干净。

## 28o 上线前最后 4 项（2026-10-28）

- 第 1 项：两个 zip 改用 Python zipfile 重打（arcname 正斜杠），容器里 `unzip -Z1` 反斜杠 0；PHP ZipArchive 解压后与工作源逐字节一致（19/19、38/38）；容器里另建独立 WP 根 `/tmp/wp2` 跑 `wp plugin/theme install --force` 均成功，装完 diff 只剩 demo。
  - 教训：先在本机 bind-mount 站点上跑同一组命令，`--force` 删不掉挂载点 → 旧目录被清空、安装失败；已用快照整目录恢复，站点与 4 组价格正常。RUNBOOK 第 4/5 步已写明必须有目录级备份。
- 第 2 项：两份源码镜像进备份仓库，容器里 `diff -r` 双向 0 差异（demo 两侧都在）。
- 第 3 项：11 份字典的 `Sample kit` 统一为短名词（es/fr/it/nl/pt 首字母大写）；1440 px 下 de/fr/nl 无溢出（该宽度副链接本就被隐藏）；1700 px 的 nl 因译文变长多出 31 px，按实测把 it/pt/fr/es/nl 五条媒体查询阈值改为 1620/1680/1640/1760/1780，重扫 13 宽度 × 12 语种 = 156 组，0 溢出。
- 第 4 项：新建 `deploy/RUNBOOK-launch.md`（九步 + 每步机器/命令/判据/回滚/耗时 + 空档期说明 + 已知问题附录）；新增 `deploy/tools/merge-i18n.php`（生产优先合并 + 冲突清单 + paths 并集，`--self-test` 7 条全过）；README 开头加「上线以 RUNBOOK 为准」，28m 第 6 步「跳过内容」改为指向 RUNBOOK 第 3 步。
  - 顺手修掉坏补丁：原 404 viewport 补丁 `git apply` 报 `corrupt patch at :21`，重出 15 行版本，`git apply --check`/apply/php -l 全过。
- 包：子主题 38 文件 788,881 B `05de19e1…`；插件 19 文件 54,512 B `8e62865e…`。
- 已知未修：页头 768 px（de/nl）与 1024 px（7 语种）横向溢出，需改布局与视觉确认。
