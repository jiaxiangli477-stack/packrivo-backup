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

- 第 2/3/4 段未开始。
