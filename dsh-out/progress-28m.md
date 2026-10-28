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

### 2.6 还剩什么 / 没做的

- `/product-textile-jewelry-pouch/` 英文页本身 404（路径表里有、页面取不到），所以「无引擎映射」的对照改用 `product-auto-lock-bottom-carton`。这是既有问题，本轮没动。
- 第 3/4 段未开始。

