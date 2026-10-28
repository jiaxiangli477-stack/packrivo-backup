# Codex 任务单 · 2026-10-27j · 首页去掉未确认的说法 + 找出子主题没翻译的文字

**一轮做完。** 只改子主题 `E:\破甲工作区\packrivo-wp\packrivo-home-v4`，不改父主题、插件、mu-plugin。生产只读，不部署。完成后同步到 `E:\破甲工作区\packrivo-backup` 并 push，结果写到 `docs\CODEX-RESULT-2026-10-27j.md`，附命令和原样输出。

## 为什么

Claude 在本地 `/ar/` 首页数到 **83 段可见英文**：H1、步骤、FAQ、价格说明、博客区标题都没翻译。生产的阿语首页 H1 是阿语，所以这个子主题现在上线，多语言首页会**倒退**。

另外，首页有一批从 v2 带过来、**站长没有确认过**的经营说法。规则是"只带事实部分"，下面先按保守版处理。

## 1. 首页文案（`front-page.php`）

按下表逐条改。**表里没列的句子不动。** 引擎算出来的价格和百分比（`$faq_price_answer`、`freePrintSetupFrom`、`$by_qty` 那几句）都保留。

| 位置 | 现在 | 改成 |
| --- | --- | --- |
| hero lede（约 60 行） | `Sampling, production and freight handled end to end. Reference prices published, dielines included, and a person on the other end of the quote — not a form that goes nowhere.` | `Sampling, production and freight handled end to end, with live prices in the quote builder and a real person replying to every quote.` |
| hero 数据（约 68–69 行） | `Production 7–30 days`、`Sourcing fee 8–15%` | **删掉这两项**，只留 MOQ 和起价。布局在 375 / 768 / 1280 px 下不能出现空位。 |
| 步骤 02 Sample | `…produced in 5–10 days. Nothing is committed before you hold it.` | `A physical sample in your artwork. Nothing is committed before you hold it.` |
| 步骤 03 Produce | `Artwork goes to plate, the run is scheduled, and we send press sheets before packing.` | `Once you approve the sample, the run is scheduled and checked before packing.` |
| 步骤 04 Deliver | `DDP or FOB, palletised, with photos and the material declaration per order.` | `Shipped from Shenzhen to your door or port, with freight quoted per shipment.` |
| FAQ 最低起订量 | `…Unprinted stock ships from 100 pieces.` | 删掉这一句，只留 `MOQ 500 pieces per design for printed boxes.` |
| FAQ 生产周期 | `Stock styles ship in 1–15 days. Printed custom orders … usually 7–30 days …` | `Timing depends on the structure and finishing. We confirm it with your quote, before you pay for anything.` |
| FAQ 整条供应链 | `…with vetted suppliers, and charge an 8–15% sourcing fee on the order value.` | `We coordinate sampling, production and freight with independent suppliers, and you see the full price before you order.` |
| FAQ 从哪里发货 | `Shenzhen, China. DDP to the US and EU is standard; FOB is available if you consolidate.` | `Shenzhen, China. Freight to your country is quoted per shipment.` |
| 材料区（约 189–202 行，"Say what the box is made of, in numbers" 和 Board / Recycled 表） | 回收率 70–90% / 60% / 100%、`Kraft E-flute`、`Greyboard 2 mm` | **整块删掉**，连同它的导航锚点或链接。没有来源的环保百分比不能写，技术词也不进首页。 |

- FAQ 改了文字，首页的 FAQPage JSON-LD 也要同步（如果有）。
- 改完全文搜一遍：`days`、`%`（引擎算的除外）、`E-flute`、`mm`、`DDP`、`FOB`、`vetted`、`press sheet`，贴出剩余命中并说明。

## 2. 找出没翻译的文字

- 用浏览器（等前端翻译脚本跑完）打开下面页面的 11 个语种，收集**可见的、还是英文的**文字节点，以及 alt / placeholder / aria-label：
  - 首页、`/products/`、`/jewelry-boxes/`、`/blog/`、任意 1 篇文章、任意 1 个产品页、`/get-a-quote/`
- 排除：品牌名 Packrivo、价格数字、单位、文章标题和正文（这一轮不翻文章）。
- 去重后写到 `E:\破甲工作区\packrivo-backup\dsh-in\untranslated-27j.json`，格式：

```json
{ "英文原文": { "where": ["/", "/products/"], "kind": "block|text|attr", "missing": ["ar","de",…] } }
```

- 第 1 节改完以后再收集，删掉的句子不要进清单。
- 贴出统计：页面 × 语种 × 未翻译条数。

## 3. H1 带 `<span>` 翻译不上

- 首页 H1 是 `Custom packaging for <span class="pk-accent">jewelry, candles</span> &amp; gifts.`，生产字典里已有这句的阿语等译文，但带 span 后匹配不上。
- 查清多语言插件对行内标签的处理方式，用**子主题里最小的改动**让它能命中现有译文，并且保留强调样式（例如译文里没有 span 就不强调也可以，但不能出现半句英文）。
- 验收：首页 × 11 语种 H1 都是译文，贴 curl 原始 HTML 里的 H1。

## 4. 验收

- 首页 en：第 1 节每条都已改（贴 diff）；375 / 768 / 1280 截图各 1 张。
- `dsh-in\untranslated-27j.json` 存在，统计表贴出。
- 首页 × 11 语种 H1 是译文。
- 提交并 push。不部署，不改 www.packrivo.com。

下一步：dsh 翻译这份清单，你再合并字典。这一轮不需要翻译。
