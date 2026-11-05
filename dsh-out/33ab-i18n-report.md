# 33a / 33b 新增英文字符串 · 11 语言译文（CSV 交付说明）

交付物：`dsh-out/33ab-i18n.csv`（UTF-8，无 BOM，无附加列）与本文档 `dsh-out/33ab-i18n-report.md`。本任务只新增这两个文件与 `dsh-out/33ab-tools/` 下的脚本，词典 JSON、容器、数据库均未改动。

## 1. 交付物与行数口径

| 项 | 值 |
| --- | --- |
| 文件 | `dsh-out/33ab-i18n.csv` |
| 编码 | UTF-8（无 BOM），LF 换行，`,` 分隔，双引号包住含逗号字段 |
| 列序 | `round, en, bucket, lang, value, origin` |
| 数据行 | 1364 |
| 表头 + 数据 | 1365 行 |
| 唯一英文串 | 124 |
| 语言 | `ar` `de` `es` `fr` `it` `ja` `ko` `nl` `pt` `zh-CN` `zh-TW`（顺序固定） |

**128 → 124 的口径差异。** 两个输入清单合计 43 + 85 = 128 条英文条目，其中 4 条的英文在两个轮次里完全相同：`DDP or FOB`、`Freight & duties quoted per destination`、`Quantity`、`/ unit`。英文相同的条目只出一份译文（保留首条出现，`round` 记为首次出现的轮次），124 × 11 = 1364 行；若按 128 × 11 计则为 1408 行。差额 44 行即这 4 条重复串的重复语言行。

**为什么英文相同也只出一份。** 词典查找是「整串精确匹配」，同一英文串在四个桶里命中的译文必须一致，出两份就会在下游合并时产生同一键两个值的冲突（`scripts/ui-28k/merge-i18n.mjs` 按桶键去重）。

## 2. 自检原始输出

`dsh-out/33ab-tools/build.py` 的完整 stdout，未经编辑：

```
build.py raw output
csv            E:\破甲工作区\packrivo-backup-33b\dsh-out\33ab-i18n.csv
rows           1364
unique en      124
new en written 106
lines (wc -l)  1365

per language   new / reused / total
  ar       106    18   124
  de       106    18   124
  es       106    18   124
  fr       106    18   124
  it       106    18   124
  ja       106    18   124
  ko       106    18   124
  nl       106    18   124
  pt       106    18   124
  zh-CN    106    18   124
  zh-TW    106    18   124

placeholder / number / entity / markup mismatches   0
empty values                                        0

bucket x origin
  attr   new        44
  attr   reused     11
  block  new        77
  text   new      1045
  text   reused    187
```

## 2.2 独立复核（`verify.py`，不复用 build.py 的计数器）

```
bytes           100651
bom             no
crlf            0
physical lines  1365 (incl header)
header          round,en,bucket,lang,value,origin
data rows       1364
langs           ar:124 de:124 es:124 fr:124 it:124 ja:124 ko:124 nl:124 pt:124 zh-CN:124 zh-TW:124
unique pairs    1364  duplicates 0
unique en       124
empty values    0
buckets         Counter({'text': 1232, 'block': 77, 'attr': 55})
origins         Counter({'new': 1166, 'reused': 198})
ph mismatches   0
percent mism    0
num mismatches  0
entity mism     0
markup mism     0
reused rows     198 (values taken verbatim from 32a dictionary)
token 250                  en rows  11
token 5–10                 en rows  22
token 7–30                 en rows  11
token 2 to 120             en rows  22
token hello@packrivo.com   en rows  11

PASS all checks
```

两点需要说明：`reused` 行的比对基准是 32a 词典原文件 （`packrivo-backup-32a/deploy/i18n-merged/<lang>-dict.json`），不是 `plan.json` 里的缓存值；`new` 行做反向检查——若某条被标 `new` 却在 32a 词典里有完全相同的值，脚本会报错，实测 0 例。

## 3. 每种语言的 reused / new 计数

| lang | new | reused | 合计 |
| --- | --- | --- | --- |
| ar | 106 | 18 | 124 |
| de | 106 | 18 | 124 |
| es | 106 | 18 | 124 |
| fr | 106 | 18 | 124 |
| it | 106 | 18 | 124 |
| ja | 106 | 18 | 124 |
| ko | 106 | 18 | 124 |
| nl | 106 | 18 | 124 |
| pt | 106 | 18 | 124 |
| zh-CN | 106 | 18 | 124 |
| zh-TW | 106 | 18 | 124 |
| **合计** | **1166** | **198** | **1364** |

`new` = 该语言下没有同桶同英文的现成译文，本轮新写；`reused` = 从 32a 词典原值直接搬用，未重译。

## 4. 桶判定（bucket）

### 4.1 规则

| bucket | 判据 |
| --- | --- |
| `title` | 文档 `<title>` / 页面主标题。本轮新增串中没有命中该桶的条目。 |
| `attr` | 出现在 HTML 属性值里：`aria-label`、`alt`、`placeholder`、`title`、`value`、`content`。 |
| `block` | 成段通告：含 2 个以上句末标点，或单句 ≥70 字符，或多句说明。 |
| `text` | 其余可见文本：标题、标签、表格单元格、kicker、短注释、按钮/链接文字、JS 运行时提示。 |

### 4.2 复用判定

同英文 **且** 同桶 → `reused`。另有一条来自 `scripts/ui-28k/merge-i18n.mjs:22-26` 的机械事实：

```js
const kinds = new Set(meta.kind === 'attr' ? ['attr'] : ['block', 'text']);
```

非 attr 条目同时写入 `block` 与 `text` 两个桶，因此这两桶互为兄弟桶，同一英文串在 `block` 命中即等价于在 `text` 也能命中，按 `reused` 处理；`attr` 与 `title` 按类型隔离，跨类型一律 `new`，不借译文。

### 4.3 逐串判定

`展示位置` 直接取自两个输入清单的原始列，桶由该元素周围的标签与句子形态推断。

| # | round | bucket | en | 展示位置 | 页面 |
| --- | --- | --- | --- | --- | --- |
| 1 | 33a | `text` | Samples %s days | 首页 Hero 事实卡 | / |
| 2 | 33a | `text` | DDP or FOB | 首页 Hero 事实卡 | / |
| 3 | 33a | `text` | Sourcing fee %s | 首页 Hero 事实卡 | / |
| 4 | 33a | `text` | Example quote · Rigid magnetic box | 首页 Hero 规格卡 | / |
| 5 | 33a | `text` | Size | 首页 Hero 规格卡 | / |
| 6 | 33a | `text` | Board | 首页 Hero 规格卡 | / |
| 7 | 33a | `text` | Rigid greyboard | 首页 Hero 规格卡 | / |
| 8 | 33a | `text` | %s pieces, sourcing fee included | 首页 Hero 规格卡 | / |
| 9 | 33a | `text` | Samples | 首页 Hero 规格卡 | / |
| 10 | 33a | `text` | Milestone: Brief | 首页四步流程 | / |
| 11 | 33a | `text` | Milestone: Sample | 首页四步流程 | / |
| 12 | 33a | `text` | Milestone: Produce | 首页四步流程 | / |
| 13 | 33a | `text` | Milestone: Deliver | 首页四步流程 | / |
| 14 | 33a | `text` | Freight & duties quoted per destination | 首页价格曲线 | / |
| 15 | 33a | `text` | Quantity | 首页价格曲线 | / |
| 16 | 33a | `text` | 2,500 · print setup waived | 首页价格曲线 | / |
| 17 | 33a | `text` | 5,000 · die setup waived | 首页价格曲线 | / |
| 18 | 33a | `text` | Goods | 首页价格曲线 | / |
| 19 | 33a | `text` | Sourcing fee | 首页价格曲线 | / |
| 20 | 33a | `text` | Unit price, sourcing fee included | 首页价格曲线 | / |
| 21 | 33a | `text` | Order total | 首页价格曲线 | / |
| 22 | 33a | `block` | Print setup is waived from %1$s pieces; die setup is waived from %2$s pieces. | 首页价格曲线 | / |
| 23 | 33a | `text` | Kraft corrugated · E/B/F/EB/BC | 首页材料表 | / |
| 24 | 33a | `text` | Rigid greyboard · %1$s coated paper over %2$s greyboard | 首页材料表 | / |
| 25 | 33a | `text` | %s recycled greyboard | 首页材料表 | / |
| 26 | 33a | `text` | Folding carton · %s | 首页材料表 | / |
| 27 | 33a | `text` | Folding boxes | 首页材料表 | / |
| 28 | 33a | `text` | Inserts, trays · on request, mould quoted separately | 首页材料表 | / |
| 29 | 33a | `text` | EVA / PU | 首页材料表 | / |
| 30 | 33a | `text` | Protective inserts | 首页材料表 | / |
| 31 | 33a | `text` | Multi-colour sheet-fed offset printing press in a packaging workshop | 首页流程图片替代文字 | / |
| 32 | 33a | `text` | Topics | 博客专题网格标题 | /blog/ |
| 33 | 33a | `text` | %s articles | 博客专题卡片文章数量 | /blog/ |
| 34 | 33a | `text` | Read | 博客文章卡片链接 | /blog/、/blog/topic/*/ |
| 35 | 33a | `text` | print setup waived | 首页价格曲线 | / |
| 36 | 33a | `text` | die setup waived | 首页价格曲线 | / |
| 37 | 33a | `text` | Product categories | 品类页筛选导航的无障碍标签 | 首页、/products/、品类页 |
| 38 | 33a | `text` | From %s units | 产品卡数量标签 | 首页、/products/、品类页 |
| 39 | 33a | `text` | / unit | 产品卡单价单位 | 首页、/products/、品类页 |
| 40 | 33a | `text` | Reference estimate | 产品卡参考价说明 | 首页、/products/、品类页 |
| 41 | 33a | `block` | Ex-works. Freight & duties quoted per destination. Setup amortised into the unit price. | 报价器费用说明 | /get-a-quote/ |
| 42 | 33a | `block` | Freight & duties quoted per destination. At 5,000 pieces the unit price is %s% lower than at 500. Final price confirmed after dieline review. | 首页价格阶梯对比 | / |
| 43 | 33a | `text` | %s days | 首页 Hero 规格卡 | / |
| 44 | 33b | `attr` | Breadcrumb | 结构化产品详情模板 | product-* |
| 45 | 33b | `text` | Home | 结构化产品详情模板 | product-* |
| 46 | 33b | `attr` | Product images | 结构化产品详情模板 | product-* |
| 47 | 33b | `attr` | View image %s | 结构化产品详情模板 | product-* |
| 48 | 33b | `text` | Samples 5–10 days | 结构化产品详情模板 | product-* |
| 49 | 33b | `text` | Dieline with every quote | 结构化产品详情模板 | product-* |
| 50 | 33b | `text` | Reference size | 结构化产品详情模板 | product-* |
| 51 | 33b | `text` | Estimate, sourcing fee included | 结构化产品详情模板 | product-* |
| 52 | 33b | `text` | 1,000 pieces | 结构化产品详情模板 | product-* |
| 53 | 33b | `text` | From 250 pieces | 结构化产品详情模板 | product-* |
| 54 | 33b | `block` | Reference estimate: unprinted, no finish; inserts are quoted on request. | 结构化产品详情模板 | product-* |
| 55 | 33b | `text` | Sample 5–10 days | 结构化产品详情模板 | product-* |
| 56 | 33b | `text` | Production 7–30 days after sample approval | 结构化产品详情模板 | product-* |
| 57 | 33b | `text` | Configure this box → | 结构化产品详情模板 | product-* |
| 58 | 33b | `text` | Get the dieline with your quote | 结构化产品详情模板 | product-* |
| 59 | 33b | `text` | Order a sample | 结构化产品详情模板 | product-* |
| 60 | 33b | `text` | Construction details to define | 结构化产品详情模板 | product-* |
| 61 | 33b | `text` | Estimate | 结构化产品详情模板 | product-* |
| 62 | 33b | `text` | Quantity and price | 结构化产品详情模板 | product-* |
| 63 | 33b | `block` | Prices include the sourcing fee. Freight & duties quoted per destination. | 结构化产品详情模板 | product-* |
| 64 | 33b | `attr` | Scrollable price table | 结构化产品详情模板 | product-* |
| 65 | 33b | `text` | Unit price, fee included | 结构化产品详情模板 | product-* |
| 66 | 33b | `text` | Savings against minimum | 结构化产品详情模板 | product-* |
| 67 | 33b | `text` | Setup | 结构化产品详情模板 | product-* |
| 68 | 33b | `text` | Goods total | 结构化产品详情模板 | product-* |
| 69 | 33b | `text` | Freight & duties | 结构化产品详情模板 | product-* |
| 70 | 33b | `text` | Waived | 结构化产品详情模板 | product-* |
| 71 | 33b | `text` | Quoted per destination | 结构化产品详情模板 | product-* |
| 72 | 33b | `text` | Select | 结构化产品详情模板 | product-* |
| 73 | 33b | `text` | Options | 结构化产品详情模板 | product-* |
| 74 | 33b | `text` | Customization options | 结构化产品详情模板 | product-* |
| 75 | 33b | `text` | Surface finishes | 结构化产品详情模板 | product-* |
| 76 | 33b | `text` | Logo finishes | 结构化产品详情模板 | product-* |
| 77 | 33b | `text` | Estimate at 1,000 pieces, sourcing fee included. | 结构化产品详情模板 | product-* |
| 78 | 33b | `text` | Inserts | 结构化产品详情模板 | product-* |
| 79 | 33b | `text` | EVA insert | 结构化产品详情模板 | product-* |
| 80 | 33b | `text` | PU insert | 结构化产品详情模板 | product-* |
| 81 | 33b | `text` | Paperboard insert | 结构化产品详情模板 | product-* |
| 82 | 33b | `text` | Corrugated insert | 结构化产品详情模板 | product-* |
| 83 | 33b | `text` | Molded pulp insert | 结构化产品详情模板 | product-* |
| 84 | 33b | `text` | Clear film frame | 结构化产品详情模板 | product-* |
| 85 | 33b | `text` | on request, mould quoted separately | 结构化产品详情模板 | product-* |
| 86 | 33b | `text` | quoted on request | 结构化产品详情模板 | product-* |
| 87 | 33b | `text` | Specification | 结构化产品详情模板 | product-* |
| 88 | 33b | `text` | Specifications and review | 结构化产品详情模板 | product-* |
| 89 | 33b | `text` | Structure and materials | 结构化产品详情模板 | product-* |
| 90 | 33b | `text` | Sampling and review | 结构化产品详情模板 | product-* |
| 91 | 33b | `text` | Dieline | 结构化产品详情模板 | product-* |
| 92 | 33b | `attr` | Illustrative flat layout | 结构化产品详情模板 | product-* |
| 93 | 33b | `text` | Illustrative only; not a production dieline. | 结构化产品详情模板 | product-* |
| 94 | 33b | `text` | A dieline comes with every quote | 结构化产品详情模板 | product-* |
| 95 | 33b | `text` | Delivery | 结构化产品详情模板 | product-* |
| 96 | 33b | `text` | Shipping options | 结构化产品详情模板 | product-* |
| 97 | 33b | `text` | Freight & duties quoted per destination. | 结构化产品详情模板 | product-* |
| 98 | 33b | `text` | DDP to door | 结构化产品详情模板 | product-* |
| 99 | 33b | `text` | Destination and terms are confirmed on the written quote. | 结构化产品详情模板 | product-* |
| 100 | 33b | `text` | FOB Shenzhen | 结构化产品详情模板 | product-* |
| 101 | 33b | `text` | FOB is available for shipment from Shenzhen. | 结构化产品详情模板 | product-* |
| 102 | 33b | `text` | Consolidation | 结构化产品详情模板 | product-* |
| 103 | 33b | `block` | Ask us to combine several items into one shipment; we confirm whether it works on the written quote. | 结构化产品详情模板 | product-* |
| 104 | 33b | `text` | Your brief | 结构化产品详情模板 | product-* |
| 105 | 33b | `text` | Build your quote | 结构化产品详情模板 | product-* |
| 106 | 33b | `text` | Confirm the dimensions and material for your project. | 结构化产品详情模板 | product-* |
| 107 | 33b | `text` | Questions | 结构化产品详情模板 | product-* |
| 108 | 33b | `text` | Frequently asked questions | 结构化产品详情模板 | product-* |
| 109 | 33b | `text` | Explore | 结构化产品详情模板 | product-* |
| 110 | 33b | `text` | Related products | 结构化产品详情模板 | product-* |
| 111 | 33b | `text` | Share the missing specifications for a tailored estimate. | 结构化产品详情模板 | product-* |
| 112 | 33b | `text` | MOQ | 结构化产品详情模板 | product-* |
| 113 | 33b | `text` | None | 结构化产品详情模板 | product-* |
| 114 | 33b | `text` | Matte varnish | 结构化产品详情模板 | product-* |
| 115 | 33b | `text` | Gloss varnish | 结构化产品详情模板 | product-* |
| 116 | 33b | `text` | Soft-touch lamination | 结构化产品详情模板 | product-* |
| 117 | 33b | `text` | Spot UV | 结构化产品详情模板 | product-* |
| 118 | 33b | `text` | Foil stamp | 结构化产品详情模板 | product-* |
| 119 | 33b | `text` | Blind emboss | 结构化产品详情模板 | product-* |
| 120 | 33b | `text` | Enter dimensions from 2 to 120 cm (one decimal place) and choose a material before viewing an estimate. | 尺寸与材料缺失提示 | 33b 产品详情及报价页 |
| 121 | 33b | `text` | Enter dimensions from 2 to 120 cm (one decimal place) before viewing an estimate. | 尺寸缺失提示 | 33b 产品详情及报价页 |
| 122 | 33b | `text` | Choose a material before viewing an estimate. | 材料缺失提示 | 33b 产品详情及报价页 |
| 123 | 33b | `block` | To request a quote without JavaScript, email hello@packrivo.com with your dimensions and material. | 无 JavaScript 询价提示 | 33b 产品详情及报价页 |
| 124 | 33b | `text` | Complete dimensions and material for an estimate. | 规格缺失时的价格阶梯说明 | product-* |

桶分布：`text` 112 串、`block` 7 串、`attr` 5 串、`title` 0 串。

## 5. 复用清单（18 串 × 11 = 198 行）

| # | 桶 | 命中桶 | en | ar 原值 |
| --- | --- | --- | --- | --- |
| 1 | `text` | `block` | Size | المقاس |
| 2 | `text` | `text` | Board | الورق المقوّى |
| 3 | `text` | `text` | Rigid greyboard | ورق مقوّى رمادي صلب |
| 4 | `text` | `text` | Samples | عينات |
| 5 | `text` | `block` | Quantity | الكمية |
| 6 | `text` | `text` | Sourcing fee | رسوم التوريد |
| 7 | `attr` | `attr` | Breadcrumb | مسار التنقل |
| 8 | `text` | `text` | Home | الرئيسية |
| 9 | `text` | `text` | Order a sample | اطلب عينة |
| 10 | `text` | `block` | Setup | الإعداد |
| 11 | `text` | `text` | Goods total | إجمالي البضائع |
| 12 | `text` | `text` | Inserts | الإدراجات |
| 13 | `text` | `block` | Paperboard insert | حشوة من الكرتون |
| 14 | `text` | `block` | Delivery | التسليم |
| 15 | `text` | `text` | Questions | الأسئلة |
| 16 | `text` | `block` | Frequently asked questions | الأسئلة الشائعة |
| 17 | `text` | `block` | Explore | استكشف |
| 18 | `text` | `block` | Blind emboss | نقش بارز بدون حبر |

其中 8 条的 `命中桶` 与分配桶不同，全部是 `block` → `text` 的兄弟桶命中（`Size`、`Quantity`、`Setup`、`Paperboard insert`、`Delivery`、`Frequently asked questions`、`Explore`、`Blind emboss`）；其余 10 条为同桶直接命中（`text` 9 条、`attr` 1 条）。

## 6. 新译回译核对（`new` 共 106 串）

回译用于无法直接阅读原文的语种：把落盘的译文再读回中文，与英文原意对齐。`zh-CN` 一栏的记录的是中文值本身的可读语义，用于确认没有为了通顺把事实改掉。

### 6.1 阿拉伯语（ar）

| # | en | 落盘译文 | 回译 |
| --- | --- | --- | --- |
| 1 | Samples %s days | عينات خلال %s أيام | 样品用 %s 天 |
| 2 | DDP or FOB | DDP أو FOB | DDP 或 FOB（贸易术语保留原文） |
| 3 | Sourcing fee %s | رسوم التوريد %s | 采购协调费 %s |
| 4 | Example quote · Rigid magnetic box | عرض سعر نموذجي · علبة مغناطيسية صلبة | 示例报价 · 硬质磁吸盒 |
| 5 | %s pieces, sourcing fee included | %s قطعة، شاملة رسوم التوريد | %s 件，含采购协调费 |
| 6 | Milestone: Brief | المرحلة: الموجز | 阶段：需求简报 |
| 7 | Milestone: Sample | المرحلة: العينة | 阶段：样品 |
| 8 | Milestone: Produce | المرحلة: الإنتاج | 阶段：生产 |
| 9 | Milestone: Deliver | المرحلة: التسليم | 阶段：交付 |
| 10 | Freight & duties quoted per destination | الشحن والرسوم الجمركية حسب الوجهة | 运费与关税按目的地计价 |
| 11 | 2,500 · print setup waived | 2,500 · إعفاء من إعداد الطباعة | 2,500 · 免印刷开机费 |
| 12 | 5,000 · die setup waived | 5,000 · إعفاء من إعداد قالب القطع | 5,000 · 免刀模开机费 |
| 13 | Goods | البضائع | 货物（货款） |
| 14 | Unit price, sourcing fee included | سعر الوحدة، شامل رسوم التوريد | 单价，含采购协调费 |
| 15 | Order total | إجمالي الطلب | 订单合计 |
| 16 | Print setup is waived from %1$s pieces; die setup is waived from %2$s pieces. | يُعفى من إعداد الطباعة بدءًا من %1$s قطعة، ويُعفى من إعداد قالب القطع بدءًا من %2$s قطعة. | 印刷设置自 %1$s 件起免收；刀模设置自 %2$s 件起免收。 |
| 17 | Kraft corrugated · E/B/F/EB/BC | كرافت مضلع · E/B/F/EB/BC | 牛皮瓦楞 · 楞型 E/B/F/EB/BC |
| 18 | Rigid greyboard · %1$s coated paper over %2$s greyboard | ورق مقوّى رمادي صلب · ورق مطلي %1$s على كرتون رمادي %2$s | 硬质灰板 · %1$s 铜版纸裱 %2$s 灰板 |
| 19 | %s recycled greyboard | ورق مقوّى رمادي معاد تدويره %s | %s 再生灰板 |
| 20 | Folding carton · %s | كرتون قابل للطي · %s | 折叠纸盒 · %s |
| 21 | Folding boxes | صناديق قابلة للطي | 折叠盒 |
| 22 | Inserts, trays · on request, mould quoted separately | إدراجات وصواني · عند الطلب، ويُسعّر القالب بشكل منفصل | 内衬、托盘 · 按需提供，模具单独报价 |
| 23 | EVA / PU | EVA / PU | EVA / PU（材质缩写，不译） |
| 24 | Protective inserts | إدراجات واقية | 保护内衬 |
| 25 | Multi-colour sheet-fed offset printing press in a packaging workshop | ماكينة طباعة أوفست متعددة الألوان بأفرخ في ورشة تعبئة | 包装车间内的多色单张纸胶印机 |
| 26 | Topics | الموضوعات | 专题（话题） |
| 27 | %s articles | %s مقالة | %s 篇文章 |
| 28 | Read | اقرأ | 阅读 |
| 29 | print setup waived | إعفاء من إعداد الطباعة | 免印刷开机费 |
| 30 | die setup waived | إعفاء من إعداد قالب القطع | 免刀模开机费 |
| 31 | Product categories | فئات المنتجات | 产品品类 |
| 32 | From %s units | من %s وحدة | %s 件起 |
| 33 | / unit | / للوحدة | / 每件 |
| 34 | Reference estimate | تقدير مرجعي | 参考估价 |
| 35 | Ex-works. Freight & duties quoted per destination. Setup amortised into the unit price. | تسليم من المصنع. تُحتسب أجرة الشحن والرسوم الجمركية حسب الوجهة. ويُوزّع الإعداد على سعر الوحدة. | 工厂交货。运费与关税按目的地计价。开机费已摊入单价。 |
| 36 | Freight & duties quoted per destination. At 5,000 pieces the unit price is %s% lower than at 500. Final price confirmed after dieline review. | تُحتسب أجرة الشحن والرسوم الجمركية حسب الوجهة. عند 5,000 قطعة يكون سعر الوحدة أقل بنسبة %s% مقارنة بـ 500 قطعة. ويُعتمد السعر النهائي بعد مراجعة مخطط القطع. | 运费与关税按目的地计价。5,000 件时单价比 500 件低 %s%。最终价格在刀模图确认后确定。 |
| 37 | %s days | %s أيام | %s 天 |
| 38 | Product images | صور المنتج | 产品图片 |
| 39 | View image %s | عرض الصورة %s | 查看图片 %s |
| 40 | Samples 5–10 days | عينات خلال 5–10 أيام | 样品 5–10 天 |
| 41 | Dieline with every quote | مخطط القطع مع كل عرض سعر | 每份报价附刀模图 |
| 42 | Reference size | المقاس المرجعي | 参考尺寸 |
| 43 | Estimate, sourcing fee included | تقدير، شامل رسوم التوريد | 估价，含采购协调费 |
| 44 | 1,000 pieces | 1,000 قطعة | 1,000 件 |
| 45 | From 250 pieces | من 250 قطعة | 250 件起 |
| 46 | Reference estimate: unprinted, no finish; inserts are quoted on request. | تقدير مرجعي: بدون طباعة وبدون تشطيب؛ وتُسعّر الإدراجات عند الطلب. | 参考估价：不印刷、不做表面处理；内衬按需单独报价。 |
| 47 | Sample 5–10 days | عينة خلال 5–10 أيام | 样品 5–10 天 |
| 48 | Production 7–30 days after sample approval | الإنتاج خلال 7–30 يومًا بعد اعتماد العينة | 样品确认后 7–30 天生产 |
| 49 | Configure this box → | اضبط هذا الصندوق ← | 配置这个盒子 →（RTL 下箭头左向） |
| 50 | Get the dieline with your quote | احصل على مخطط القطع مع عرض السعر | 报价同时获取刀模图 |
| 51 | Construction details to define | تفاصيل التركيب المطلوب تحديدها | 需要确定的结构细节 |
| 52 | Estimate | تقدير | 估价 |
| 53 | Quantity and price | الكمية والسعر | 数量与价格 |
| 54 | Prices include the sourcing fee. Freight & duties quoted per destination. | الأسعار شاملة رسوم التوريد. وتُحتسب أجرة الشحن والرسوم الجمركية حسب الوجهة. | 价格含采购协调费。运费与关税按目的地计价。 |
| 55 | Scrollable price table | جدول أسعار قابل للتمرير | 可滚动价格表 |
| 56 | Unit price, fee included | سعر الوحدة، شامل الرسوم | 单价，含费用 |
| 57 | Savings against minimum | الوفر مقارنة بالحد الأدنى | 相对起订量的节省额 |
| 58 | Freight & duties | الشحن والرسوم الجمركية | 运费与关税 |
| 59 | Waived | معفى | 免收 |
| 60 | Quoted per destination | يُسعّر حسب الوجهة | 按目的地报价 |
| 61 | Select | اختر | 选择 |
| 62 | Options | الخيارات | 选项 |
| 63 | Customization options | خيارات التخصيص | 定制选项 |
| 64 | Surface finishes | تشطيبات السطح | 表面处理 |
| 65 | Logo finishes | تشطيبات الشعار | 标志工艺 |
| 66 | Estimate at 1,000 pieces, sourcing fee included. | تقدير عند 1,000 قطعة، شامل رسوم التوريد. | 1,000 件估价，含采购协调费。 |
| 67 | EVA insert | إدراج EVA | EVA 内衬 |
| 68 | PU insert | إدراج PU | PU 内衬 |
| 69 | Corrugated insert | إدراج من الكرتون المضلع | 瓦楞内衬 |
| 70 | Molded pulp insert | إدراج من اللب المصبوب | 纸浆模塑内衬 |
| 71 | Clear film frame | إطار من فيلم شفاف | 透明胶片框 |
| 72 | on request, mould quoted separately | عند الطلب، ويُسعّر القالب بشكل منفصل | 按需提供，模具单独报价 |
| 73 | quoted on request | يُسعّر عند الطلب | 按需报价 |
| 74 | Specification | المواصفات | 规格 |
| 75 | Specifications and review | المواصفات والمراجعة | 规格与确认 |
| 76 | Structure and materials | التركيب والمواد | 结构与材料 |
| 77 | Sampling and review | العينات والمراجعة | 打样与确认 |
| 78 | Dieline | مخطط القطع | 刀模图 |
| 79 | Illustrative flat layout | تخطيط مسطح توضيحي | 示意展开图 |
| 80 | Illustrative only; not a production dieline. | لأغراض التوضيح فقط؛ ليس مخطط قطع للإنتاج. | 仅作示意；不是生产用刀模图。 |
| 81 | A dieline comes with every quote | مخطط القطع مرفق مع كل عرض سعر | 每份报价都附刀模图 |
| 82 | Shipping options | خيارات الشحن | 运输方式 |
| 83 | Freight & duties quoted per destination. | تُحتسب أجرة الشحن والرسوم الجمركية حسب الوجهة. | 运费与关税按目的地计价。 |
| 84 | DDP to door | DDP حتى باب العميل | DDP 门到门 |
| 85 | Destination and terms are confirmed on the written quote. | تُؤكَّد الوجهة والشروط في عرض السعر المكتوب. | 目的地与条款在书面报价中确认。 |
| 86 | FOB Shenzhen | FOB شنتشن | FOB 深圳 |
| 87 | FOB is available for shipment from Shenzhen. | يتوفر شرط FOB للشحن من شنتشن. | 深圳发货可选 FOB。 |
| 88 | Consolidation | تجميع الشحنات | 拼单发货 |
| 89 | Ask us to combine several items into one shipment; we confirm whether it works on the written quote. | اطلب منا دمج عدة أصناف في شحنة واحدة؛ ونؤكد إمكان ذلك في عرض السعر المكتوب. | 可要求把多件商品合并成一次发货；能否合并会在书面报价中确认。 |
| 90 | Your brief | موجزك | 你的需求简报 |
| 91 | Build your quote | أنشئ عرض السعر | 生成你的报价 |
| 92 | Confirm the dimensions and material for your project. | أكّد المقاسات والمواد لمشروعك. | 确认项目的尺寸与材料。 |
| 93 | Related products | منتجات ذات صلة | 相关产品 |
| 94 | Share the missing specifications for a tailored estimate. | شاركنا المواصفات الناقصة للحصول على تقدير مخصص. | 补充缺失规格，获取定制估价。 |
| 95 | MOQ | MOQ | MOQ（起订量，缩写保留） |
| 96 | None | لا شيء | 无 |
| 97 | Matte varnish | ورنيش مطفي | 哑光油 |
| 98 | Gloss varnish | ورنيش لامع | 亮光油 |
| 99 | Soft-touch lamination | تغليف بملمس ناعم | 触感膜 |
| 100 | Spot UV | UV موضعي | 局部 UV |
| 101 | Foil stamp | ختم بالرقائق المعدنية | 烫金 |
| 102 | Enter dimensions from 2 to 120 cm (one decimal place) and choose a material before viewing an estimate. | أدخل المقاسات من 2 إلى 120 سم (خانة عشرية واحدة) واختر مادة قبل عرض التقدير. | 输入 2 至 120 cm 的尺寸（可带小数）并选择材料后查看估价。 |
| 103 | Enter dimensions from 2 to 120 cm (one decimal place) before viewing an estimate. | أدخل المقاسات من 2 إلى 120 سم (خانة عشرية واحدة) قبل عرض التقدير. | 查看估价前，先输入 2 至 120 cm 的尺寸（可带小数）。 |
| 104 | Choose a material before viewing an estimate. | اختر مادة قبل عرض التقدير. | 查看估价前先选择材料。 |
| 105 | To request a quote without JavaScript, email hello@packrivo.com with your dimensions and material. | لطلب عرض سعر بدون JavaScript، راسلنا على hello@packrivo.com مع ذكر المقاسات والمواد. | 无 JavaScript 时，将尺寸与材料发至 hello@packrivo.com 索取报价。 |
| 106 | Complete dimensions and material for an estimate. | أكمل المقاسات والمواد للحصول على تقدير. | 补全尺寸与材料即可获得估价。 |

### 6.2 日语（ja）

| # | en | 落盘译文 | 回译 |
| --- | --- | --- | --- |
| 1 | Samples %s days | サンプル %s日 | 样品 %s 天 |
| 2 | DDP or FOB | DDP または FOB | DDP 或 FOB |
| 3 | Sourcing fee %s | 調達手数料 %s | 调达手续费 %s |
| 4 | Example quote · Rigid magnetic box | 見積例 · マグネット式硬質箱 | 见积例 · 磁吸式硬质盒 |
| 5 | %s pieces, sourcing fee included | %s個（調達手数料込み） | %s 个（含调达手续费） |
| 6 | Milestone: Brief | ステップ：要件整理 | 步骤：要件整理 |
| 7 | Milestone: Sample | ステップ：サンプル | 步骤：样品 |
| 8 | Milestone: Produce | ステップ：生産 | 步骤：生产 |
| 9 | Milestone: Deliver | ステップ：納品 | 步骤：交付 |
| 10 | Freight & duties quoted per destination | 輸送費・関税は仕向地ごとに算出 | 运费·关税按目的地核算 |
| 11 | 2,500 · print setup waived | 2,500 · 印刷セットアップ無料 | 2,500 · 印刷开机免费 |
| 12 | 5,000 · die setup waived | 5,000 · 抜き型セットアップ無料 | 5,000 · 抜型开机免费 |
| 13 | Goods | 商品 | 商品（货款） |
| 14 | Unit price, sourcing fee included | 単価（調達手数料込み） | 单价（含调达手续费） |
| 15 | Order total | ご注文合計 | 订单合计 |
| 16 | Print setup is waived from %1$s pieces; die setup is waived from %2$s pieces. | 印刷セットアップは%1$s個から無料、抜き型セットアップは%2$s個から無料です。 | 印刷开机自 %1$s 个起免费，抜型开机自 %2$s 个起免费。 |
| 17 | Kraft corrugated · E/B/F/EB/BC | クラフト段ボール · E/B/F/EB/BC | 牛皮瓦楞 · 楞型 E/B/F/EB/BC |
| 18 | Rigid greyboard · %1$s coated paper over %2$s greyboard | 硬質グレー板紙 · %1$s コート紙／%2$s グレー板紙 | 硬质灰板 · %1$s 铜版纸裱 %2$s 灰板 |
| 19 | %s recycled greyboard | %s 再生グレー板紙 | %s 再生灰板 |
| 20 | Folding carton · %s | 折り箱 · %s | 折叠盒 · %s |
| 21 | Folding boxes | 折り箱 | 折叠盒 |
| 22 | Inserts, trays · on request, mould quoted separately | インサート・トレー · ご要望ベース、型代は別途お見積もり | 内衬、托盘 · 按需，模具另行报价 |
| 23 | EVA / PU | EVA / PU | EVA / PU（材质缩写，不译） |
| 24 | Protective inserts | 保護用インサート | 保护用内衬 |
| 25 | Multi-colour sheet-fed offset printing press in a packaging workshop | 製函工房にある多色枚葉オフセット印刷機 | 制盒车间内的多色单张纸胶印机 |
| 26 | Topics | トピック | 主题（专题） |
| 27 | %s articles | %s件の記事 | %s 篇报道 |
| 28 | Read | 読む | 阅读 |
| 29 | print setup waived | 印刷セットアップ無料 | 印刷开机免费 |
| 30 | die setup waived | 抜き型セットアップ無料 | 抜型开机免费 |
| 31 | Product categories | 製品カテゴリー | 产品分类 |
| 32 | From %s units | %s個から | %s 个起 |
| 33 | / unit | / 個 | / 每个 |
| 34 | Reference estimate | 参考見積もり | 参考见积 |
| 35 | Ex-works. Freight & duties quoted per destination. Setup amortised into the unit price. | 工場渡し。輸送費・関税は仕向地ごとに算出。セットアップ費は単価に按分済み。 | 工厂交货。运费·关税按目的地核算。开机费已按分到单价。 |
| 36 | Freight & duties quoted per destination. At 5,000 pieces the unit price is %s% lower than at 500. Final price confirmed after dieline review. | 輸送費・関税は仕向地ごとに算出。5,000個では500個のときより単価が%s%低くなります。最終価格は抜き型図面の確認後に確定します。 | 运费·关税按目的地核算。5,000 个时单价比 500 个低 %s%。最终价格在抜型图确认后确定。 |
| 37 | %s days | %s日 | %s 天 |
| 38 | Product images | 製品画像 | 产品图片 |
| 39 | View image %s | 画像 %s を表示 | 查看图片 %s |
| 40 | Samples 5–10 days | サンプル 5–10日 | 样品 5–10 天 |
| 41 | Dieline with every quote | すべての見積もりに抜き型図面付き | 每份报价附抜型图 |
| 42 | Reference size | 参考サイズ | 参考尺寸 |
| 43 | Estimate, sourcing fee included | 概算（調達手数料込み） | 概算（含调达手续费） |
| 44 | 1,000 pieces | 1,000個 | 1,000 个 |
| 45 | From 250 pieces | 250個から | 250 个起 |
| 46 | Reference estimate: unprinted, no finish; inserts are quoted on request. | 参考見積もり：未印刷・加工なし。インサートはご要望時にお見積もりします。 | 参考见积：未印刷、无加工。内衬按需另行报价。 |
| 47 | Sample 5–10 days | サンプル 5–10日 | 样品 5–10 天 |
| 48 | Production 7–30 days after sample approval | サンプル承認後 7–30日で生産 | 样品确认后 7–30 天生产 |
| 49 | Configure this box → | この箱を設定する → | 配置这个盒子 → |
| 50 | Get the dieline with your quote | 見積もりと一緒に抜き型図面をお受け取りください | 与报价一同获取抜型图 |
| 51 | Construction details to define | 決めておきたい構造の詳細 | 需确定的结构细节 |
| 52 | Estimate | 概算 | 概算 |
| 53 | Quantity and price | 数量と価格 | 数量与价格 |
| 54 | Prices include the sourcing fee. Freight & duties quoted per destination. | 価格には調達手数料が含まれます。輸送費・関税は仕向地ごとに算出します。 | 价格含调达手续费。运费·关税按目的地核算。 |
| 55 | Scrollable price table | スクロール可能な価格表 | 可滚动的价格表 |
| 56 | Unit price, fee included | 単価（手数料込み） | 单价（含手续费） |
| 57 | Savings against minimum | 最小ロット比の削減額 | 相对最小批量的节省额 |
| 58 | Freight & duties | 輸送費・関税 | 运费·关税 |
| 59 | Waived | 無料 | 免费 |
| 60 | Quoted per destination | 仕向地ごとに算出 | 按目的地核算 |
| 61 | Select | 選択 | 选择 |
| 62 | Options | オプション | 选项 |
| 63 | Customization options | カスタマイズ項目 | 定制选项 |
| 64 | Surface finishes | 表面加工 | 表面加工 |
| 65 | Logo finishes | ロゴ加工 | 标志加工 |
| 66 | Estimate at 1,000 pieces, sourcing fee included. | 1,000個時の概算（調達手数料込み）。 | 1,000 个时概算（含调达手续费）。 |
| 67 | EVA insert | EVA製インサート | EVA 内衬 |
| 68 | PU insert | PU製インサート | PU 内衬 |
| 69 | Corrugated insert | 段ボール製インサート | 瓦楞内衬 |
| 70 | Molded pulp insert | パルプモールド製インサート | 纸浆模塑内衬 |
| 71 | Clear film frame | 透明フィルムフレーム | 透明胶片框 |
| 72 | on request, mould quoted separately | ご要望ベース、型代は別途 | 按需，模具另行报价 |
| 73 | quoted on request | ご要望時にお見積もり | 按需报价 |
| 74 | Specification | 仕様 | 规格 |
| 75 | Specifications and review | 仕様と確認 | 规格与确认 |
| 76 | Structure and materials | 構造と素材 | 结构与材料 |
| 77 | Sampling and review | サンプルと確認 | 打样与确认 |
| 78 | Dieline | 抜き型図面 | 抜型图 |
| 79 | Illustrative flat layout | 展開図（イメージ） | 展开图（示意图） |
| 80 | Illustrative only; not a production dieline. | イメージです。生産用の抜き型図面ではありません。 | 仅为示意。不是生产用抜型图。 |
| 81 | A dieline comes with every quote | すべての見積もりに抜き型図面が付きます | 每份报价都附抜型图 |
| 82 | Shipping options | 配送オプション | 配送方式 |
| 83 | Freight & duties quoted per destination. | 輸送費・関税は仕向地ごとに算出します。 | 运费·关税按目的地核算。 |
| 84 | DDP to door | DDP（お届け先まで） | DDP（送至门口） |
| 85 | Destination and terms are confirmed on the written quote. | 仕向地と条件は書面のお見積もりで確定します。 | 目的地与条件在书面报价中确定。 |
| 86 | FOB Shenzhen | FOB 深圳 | FOB 深圳 |
| 87 | FOB is available for shipment from Shenzhen. | 深圳からの出荷でFOBをご利用いただけます。 | 深圳出货可采用 FOB。 |
| 88 | Consolidation | 同梱 | 合并出货 |
| 89 | Ask us to combine several items into one shipment; we confirm whether it works on the written quote. | 複数の品目をまとめて出荷したい場合はご依頼ください。可能かどうかは書面のお見積もりでご確認いただけます。 | 如需把多件商品合并为一次发货请告知；可否合并会在书面报价中确认。 |
| 90 | Your brief | ご要件 | 您的需求 |
| 91 | Build your quote | 見積もりを作成 | 生成报价 |
| 92 | Confirm the dimensions and material for your project. | プロジェクトのサイズと素材を確定してください。 | 确定专案的尺寸与素材。 |
| 93 | Related products | 関連製品 | 相关产品 |
| 94 | Share the missing specifications for a tailored estimate. | 不足している仕様をお知らせいただければ、個別にお見積もりします。 | 告知缺失的规格，即可获得个别报价。 |
| 95 | MOQ | MOQ | MOQ（最小订量，缩写保留） |
| 96 | None | なし | 无 |
| 97 | Matte varnish | マットニス | 哑光油 |
| 98 | Gloss varnish | グロスニス | 亮光油 |
| 99 | Soft-touch lamination | ソフトタッチ加工 | 触感膜 |
| 100 | Spot UV | スポットUV | 局部 UV |
| 101 | Foil stamp | 箔押し | 烫金 |
| 102 | Enter dimensions from 2 to 120 cm (one decimal place) and choose a material before viewing an estimate. | 2〜120cm（小数点以下も可）でサイズを入力し、素材を選ぶと概算が表示されます。 | 以 2〜120cm（可带小数）输入尺寸并选择素材后显示概算。 |
| 103 | Enter dimensions from 2 to 120 cm (one decimal place) before viewing an estimate. | 概算を表示する前に、2〜120cm（小数点以下も可）でサイズを入力してください。 | 显示概算前，请先以 2〜120cm（可带小数）输入尺寸。 |
| 104 | Choose a material before viewing an estimate. | 概算を表示する前に素材を選択してください。 | 显示概算前请先选择素材。 |
| 105 | To request a quote without JavaScript, email hello@packrivo.com with your dimensions and material. | JavaScriptをご利用いただけない場合は、サイズと素材を記載のうえ hello@packrivo.com までメールでお問い合わせください。 | 无法使用 JavaScript 时，请将尺寸与素材发邮件至 hello@packrivo.com 索取报价。 |
| 106 | Complete dimensions and material for an estimate. | サイズと素材を入力すると概算が表示されます。 | 输入尺寸与素材后即显示概算。 |

### 6.3 简体中文（zh-CN）

| # | en | 落盘译文 | 回译 |
| --- | --- | --- | --- |
| 1 | Samples %s days | 样品 %s 天 | 样品 %s 天 |
| 2 | DDP or FOB | DDP 或 FOB | DDP 或 FOB |
| 3 | Sourcing fee %s | 采购协调费 %s | 采购协调费 %s |
| 4 | Example quote · Rigid magnetic box | 示例报价 · 硬质磁吸盒 | 示例报价 · 硬质磁吸盒 |
| 5 | %s pieces, sourcing fee included | %s 件，含采购协调费 | %s 件，含采购协调费 |
| 6 | Milestone: Brief | 阶段：需求简报 | 阶段：需求简报 |
| 7 | Milestone: Sample | 阶段：样品 | 阶段：样品 |
| 8 | Milestone: Produce | 阶段：生产 | 阶段：生产 |
| 9 | Milestone: Deliver | 阶段：交付 | 阶段：交付 |
| 10 | Freight & duties quoted per destination | 运费与关税按目的地核算 | 运费与关税按目的地核算 |
| 11 | 2,500 · print setup waived | 2,500 · 免印刷开机费 | 2,500 · 免印刷开机费 |
| 12 | 5,000 · die setup waived | 5,000 · 免刀模开机费 | 5,000 · 免刀模开机费 |
| 13 | Goods | 货款 | 货款 |
| 14 | Unit price, sourcing fee included | 单价，含采购协调费 | 单价，含采购协调费 |
| 15 | Order total | 订单合计 | 订单合计 |
| 16 | Print setup is waived from %1$s pieces; die setup is waived from %2$s pieces. | 印刷开机费 %1$s 件起免收；刀模开机费 %2$s 件起免收。 | 印刷开机费 %1$s 件起免收；刀模开机费 %2$s 件起免收。 |
| 17 | Kraft corrugated · E/B/F/EB/BC | 牛皮瓦楞 · E/B/F/EB/BC | 牛皮瓦楞 · E/B/F/EB/BC |
| 18 | Rigid greyboard · %1$s coated paper over %2$s greyboard | 硬质灰板 · %1$s 铜版纸裱 %2$s 灰板 | 硬质灰板 · %1$s 铜版纸裱 %2$s 灰板 |
| 19 | %s recycled greyboard | %s 再生灰板 | %s 再生灰板 |
| 20 | Folding carton · %s | 折叠纸盒 · %s | 折叠纸盒 · %s |
| 21 | Folding boxes | 折叠盒 | 折叠盒 |
| 22 | Inserts, trays · on request, mould quoted separately | 内衬、托盘 · 按需提供，模具单独报价 | 内衬、托盘 · 按需提供，模具单独报价 |
| 23 | EVA / PU | EVA / PU | EVA / PU |
| 24 | Protective inserts | 保护内衬 | 保护内衬 |
| 25 | Multi-colour sheet-fed offset printing press in a packaging workshop | 包装车间的多色单张纸胶印机 | 包装车间的多色单张纸胶印机 |
| 26 | Topics | 专题 | 专题 |
| 27 | %s articles | %s 篇文章 | %s 篇文章 |
| 28 | Read | 阅读 | 阅读 |
| 29 | print setup waived | 免印刷开机费 | 免印刷开机费 |
| 30 | die setup waived | 免刀模开机费 | 免刀模开机费 |
| 31 | Product categories | 产品品类 | 产品品类 |
| 32 | From %s units | %s 件起 | %s 件起 |
| 33 | / unit | / 件 | / 件 |
| 34 | Reference estimate | 参考估价 | 参考估价 |
| 35 | Ex-works. Freight & duties quoted per destination. Setup amortised into the unit price. | 工厂交货。运费与关税按目的地核算。开机费已摊入单价。 | 工厂交货。运费与关税按目的地核算。开机费已摊入单价。 |
| 36 | Freight & duties quoted per destination. At 5,000 pieces the unit price is %s% lower than at 500. Final price confirmed after dieline review. | 运费与关税按目的地核算。5,000 件时单价比 500 件低 %s%。最终价格在刀模图确认后确定。 | 运费与关税按目的地核算。5,000 件时单价比 500 件低 %s%。最终价格在刀模图确认后确定。 |
| 37 | %s days | %s 天 | %s 天 |
| 38 | Product images | 产品图片 | 产品图片 |
| 39 | View image %s | 查看图片 %s | 查看图片 %s |
| 40 | Samples 5–10 days | 样品 5–10 天 | 样品 5–10 天 |
| 41 | Dieline with every quote | 每份报价附刀模图 | 每份报价附刀模图 |
| 42 | Reference size | 参考尺寸 | 参考尺寸 |
| 43 | Estimate, sourcing fee included | 估价，含采购协调费 | 估价，含采购协调费 |
| 44 | 1,000 pieces | 1,000 件 | 1,000 件 |
| 45 | From 250 pieces | 250 件起 | 250 件起 |
| 46 | Reference estimate: unprinted, no finish; inserts are quoted on request. | 参考估价：不印刷、不做表面处理；内衬按需单独报价。 | 参考估价：不印刷、不做表面处理；内衬按需单独报价。 |
| 47 | Sample 5–10 days | 样品 5–10 天 | 样品 5–10 天 |
| 48 | Production 7–30 days after sample approval | 样品确认后 7–30 天生产 | 样品确认后 7–30 天生产 |
| 49 | Configure this box → | 配置这个盒子 → | 配置这个盒子 → |
| 50 | Get the dieline with your quote | 报价同时获取刀模图 | 报价同时获取刀模图 |
| 51 | Construction details to define | 需要确定的结构细节 | 需要确定的结构细节 |
| 52 | Estimate | 估价 | 估价 |
| 53 | Quantity and price | 数量与价格 | 数量与价格 |
| 54 | Prices include the sourcing fee. Freight & duties quoted per destination. | 价格含采购协调费。运费与关税按目的地核算。 | 价格含采购协调费。运费与关税按目的地核算。 |
| 55 | Scrollable price table | 可滚动价格表 | 可滚动价格表 |
| 56 | Unit price, fee included | 单价，含协调费 | 单价，含协调费 |
| 57 | Savings against minimum | 相对起订量的节省 | 相对起订量的节省 |
| 58 | Freight & duties | 运费与关税 | 运费与关税 |
| 59 | Waived | 免收 | 免收 |
| 60 | Quoted per destination | 按目的地报价 | 按目的地报价 |
| 61 | Select | 选择 | 选择 |
| 62 | Options | 选项 | 选项 |
| 63 | Customization options | 定制选项 | 定制选项 |
| 64 | Surface finishes | 表面处理 | 表面处理 |
| 65 | Logo finishes | 标志工艺 | 标志工艺 |
| 66 | Estimate at 1,000 pieces, sourcing fee included. | 1,000 件估价，含采购协调费。 | 1,000 件估价，含采购协调费。 |
| 67 | EVA insert | EVA 内衬 | EVA 内衬 |
| 68 | PU insert | PU 内衬 | PU 内衬 |
| 69 | Corrugated insert | 瓦楞内衬 | 瓦楞内衬 |
| 70 | Molded pulp insert | 纸浆模塑内衬 | 纸浆模塑内衬 |
| 71 | Clear film frame | 透明胶片框 | 透明胶片框 |
| 72 | on request, mould quoted separately | 按需提供，模具单独报价 | 按需提供，模具单独报价 |
| 73 | quoted on request | 按需报价 | 按需报价 |
| 74 | Specification | 规格 | 规格 |
| 75 | Specifications and review | 规格与确认 | 规格与确认 |
| 76 | Structure and materials | 结构与材料 | 结构与材料 |
| 77 | Sampling and review | 打样与确认 | 打样与确认 |
| 78 | Dieline | 刀模图 | 刀模图 |
| 79 | Illustrative flat layout | 示意展开图 | 示意展开图 |
| 80 | Illustrative only; not a production dieline. | 仅作示意，不是生产用刀模图。 | 仅作示意，不是生产用刀模图。 |
| 81 | A dieline comes with every quote | 每份报价都附刀模图 | 每份报价都附刀模图 |
| 82 | Shipping options | 运输方式 | 运输方式 |
| 83 | Freight & duties quoted per destination. | 运费与关税按目的地核算。 | 运费与关税按目的地核算。 |
| 84 | DDP to door | DDP 门到门 | DDP 门到门 |
| 85 | Destination and terms are confirmed on the written quote. | 目的地与条款在书面报价中确认。 | 目的地与条款在书面报价中确认。 |
| 86 | FOB Shenzhen | FOB 深圳 | FOB 深圳 |
| 87 | FOB is available for shipment from Shenzhen. | 深圳发货可选 FOB。 | 深圳发货可选 FOB。 |
| 88 | Consolidation | 拼单发货 | 拼单发货 |
| 89 | Ask us to combine several items into one shipment; we confirm whether it works on the written quote. | 可以要求把多件商品合并成一次发货；能否合并会在书面报价里确认。 | 可以要求把多件商品合并成一次发货；能否合并会在书面报价里确认。 |
| 90 | Your brief | 你的需求 | 你的需求 |
| 91 | Build your quote | 生成你的报价 | 生成你的报价 |
| 92 | Confirm the dimensions and material for your project. | 确认项目的尺寸与材料。 | 确认项目的尺寸与材料。 |
| 93 | Related products | 相关产品 | 相关产品 |
| 94 | Share the missing specifications for a tailored estimate. | 补充缺失规格，即可获取定制估价。 | 补充缺失规格，即可获取定制估价。 |
| 95 | MOQ | MOQ | MOQ（起订量） |
| 96 | None | 无 | 无 |
| 97 | Matte varnish | 哑光油 | 哑光油 |
| 98 | Gloss varnish | 亮光油 | 亮光油 |
| 99 | Soft-touch lamination | 触感膜 | 触感膜 |
| 100 | Spot UV | 局部UV | 局部UV |
| 101 | Foil stamp | 烫金 | 烫金 |
| 102 | Enter dimensions from 2 to 120 cm (one decimal place) and choose a material before viewing an estimate. | 输入 2 至 120 cm 的尺寸（保留一位小数）并选择材料后查看估价。 | 输入 2 至 120 cm 的尺寸（保留一位小数）并选择材料后查看估价。 |
| 103 | Enter dimensions from 2 to 120 cm (one decimal place) before viewing an estimate. | 查看估价前请先输入 2 至 120 cm 的尺寸（保留一位小数）。 | 查看估价前请先输入 2 至 120 cm 的尺寸（保留一位小数）。 |
| 104 | Choose a material before viewing an estimate. | 查看估价前请先选择材料。 | 查看估价前请先选择材料。 |
| 105 | To request a quote without JavaScript, email hello@packrivo.com with your dimensions and material. | 如需在无 JavaScript 环境下获取报价，请将尺寸与材料发邮件至 hello@packrivo.com。 | 如需在无 JavaScript 环境下获取报价，请将尺寸与材料发邮件至 hello@packrivo.com。 |
| 106 | Complete dimensions and material for an estimate. | 补全尺寸与材料即可获得估价。 | 补全尺寸与材料即可获得估价。 |

## 7. 抽样对照（10 条英文 × 11 语言）

| en | ar | de | es | fr | it | ja | ko | nl | pt | zh-CN | zh-TW |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Sourcing fee %s | رسوم التوريد %s | Beschaffungsgebühr %s | Comisión de abastecimiento %s | Frais d’approvisionnement %s | Commissione di approvvigionamento %s | 調達手数料 %s | 조달 수수료 %s | Inkoopvergoeding %s | Taxa de fornecimento %s | 采购协调费 %s | 採購協調費 %s |
| Product images | صور المنتج | Produktbilder | Imágenes del producto | Images du produit | Immagini del prodotto | 製品画像 | 제품 이미지 | Productafbeeldingen | Imagens do produto | 产品图片 | 產品圖片 |
| Scrollable price table | جدول أسعار قابل للتمرير | Scrollbare Preistabelle | Tabla de precios desplazable | Tableau de prix défilable | Tabella prezzi scorrevole | スクロール可能な価格表 | 스크롤 가능한 가격표 | Scrollbare prijstabel | Tabela de preços deslizável | 可滚动价格表 | 可捲動價格表 |
| Illustrative flat layout | تخطيط مسطح توضيحي | Schematische Abwicklung | Plano desplegado ilustrativo | Plan à plat illustratif | Sviluppo piano illustrativo | 展開図（イメージ） | 펼친 도면(예시) | Illustratieve uitslag | Planificado ilustrativo | 示意展开图 | 示意展開圖 |
| Illustrative only; not a production dieline. | لأغراض التوضيح فقط؛ ليس مخطط قطع للإنتاج. | Nur zur Veranschaulichung; keine produktionsfähige Stanzkontur. | Solo ilustrativo; no es una línea de troquel de producción. | Illustration uniquement ; ne constitue pas un gabarit de découpe de production. | Solo a scopo illustrativo; non è una linea di fustellatura di produzione. | イメージです。生産用の抜き型図面ではありません。 | 예시용이며 실제 생산용 다이 라인이 아닙니다. | Uitsluitend ter illustratie; geen productieklare stanslijn. | Apenas ilustrativo; não é uma linha de corte de produção. | 仅作示意，不是生产用刀模图。 | 僅示意用，非生產用刀模圖。 |
| Waived | معفى | entfällt | sin coste | offert | non addebitato | 無料 | 면제 | kosteloos | isento | 免收 | 免收 |
| Enter dimensions from 2 to 120 cm (one decimal place) before viewing an estimate. | أدخل المقاسات من 2 إلى 120 سم (خانة عشرية واحدة) قبل عرض التقدير. | Geben Sie die Maße von 2 bis 120 cm ein (eine Nachkommastelle), bevor Sie einen Richtwert sehen. | Introduce las medidas de 2 a 120 cm (un decimal) antes de ver la estimación. | Saisissez les dimensions de 2 à 120 cm (une décimale) avant d’afficher une estimation. | Inserisci le dimensioni da 2 a 120 cm (un decimale) prima di vedere una stima. | 概算を表示する前に、2〜120cm（小数点以下も可）でサイズを入力してください。 | 예상 견적을 보기 전에 2~120cm(소수점 한 자리)로 크기를 입력하세요. | Voer de afmetingen in van 2 tot 120 cm (één decimaal) voordat je een schatting ziet. | Introduza as medidas de 2 a 120 cm (uma casa decimal) antes de ver uma estimativa. | 查看估价前请先输入 2 至 120 cm 的尺寸（保留一位小数）。 | 檢視估價前請先輸入 2 至 120 cm 的尺寸（保留一位小數）。 |
| Freight & duties quoted per destination. | تُحتسب أجرة الشحن والرسوم الجمركية حسب الوجهة. | Fracht & Zölle je Bestimmungsland. | Transporte y aranceles según destino. | Transport et droits de douane selon la destination. | Trasporto e dazi in base alla destinazione. | 輸送費・関税は仕向地ごとに算出します。 | 운송비·관세는 목적지별로 산정됩니다. | Vracht & douane per bestemming. | Frete e direitos conforme o destino. | 运费与关税按目的地核算。 | 運費與關稅依目的地核算。 |
| Ask us to combine several items into one shipment; we confirm whether it works on the written quote. | اطلب منا دمج عدة أصناف في شحنة واحدة؛ ونؤكد إمكان ذلك في عرض السعر المكتوب. | Bitten Sie uns, mehrere Artikel zu einer Sendung zusammenzufassen; ob es geht, bestätigen wir im schriftlichen Angebot. | Pídenos combinar varios artículos en un solo envío; confirmamos si es posible en el presupuesto escrito. | Demandez-nous de regrouper plusieurs articles dans un seul envoi ; nous confirmons la faisabilité sur le devis écrit. | Chiedici di riunire più articoli in un’unica spedizione; confermiamo se è possibile nel preventivo scritto. | 複数の品目をまとめて出荷したい場合はご依頼ください。可能かどうかは書面のお見積もりでご確認いただけます。 | 여러 품목을 한 번의 배송으로 합쳐 달라고 요청하시면, 가능 여부를 서면 견적에서 확인해 드립니다. | Vraag ons om meerdere artikelen in één zending te combineren; of dat kan, bevestigen we in de schriftelijke offerte. | Peça-nos para combinar vários artigos numa só remessa; confirmamos se é possível no orçamento escrito. | 可以要求把多件商品合并成一次发货；能否合并会在书面报价里确认。 | 可要求將多項商品合併為一次出貨；是否可行會於書面報價中確認。 |
| Configure this box → | اضبط هذا الصندوق ← | Diesen Karton konfigurieren → | Configura esta caja → | Configurer cette boîte → | Configura questa scatola → | この箱を設定する → | 이 상자 구성하기 → | Deze doos samenstellen → | Configurar esta caixa → | 配置这个盒子 → | 配置這個盒子 → |

## 8. 术语沿用与偏差

### 8.1 32a 已定术语（直接沿用，未改）

| en | zh-CN | zh-TW | ja | de |
| --- | --- | --- | --- | --- |
| Sourcing fee | 采购协调费 | 採購協調費 | 調達手数料 | Beschaffungsgebühr |
| DDP or FOB | DDP 或 FOB | DDP 或 FOB | DDP または FOB | DDP oder FOB |
| MOQ | MOQ | MOQ | MOQ | MOQ |
| Dieline | 刀模图 | 刀模圖 | 抜き型図面 | Stanzkontur |
| die setup waived | 免刀模开机费 | 免刀模開機費 | 抜き型セットアップ無料 | Stanzeinrichtung entfällt |
| print setup waived | 免印刷开机费 | 免印刷開機費 | 印刷セットアップ無料 | Druckeinrichtung entfällt |

`Sourcing fee` 在 zh-CN / zh-TW 统一为「采购协调费 / 採購協調費」，与 32a 一致；`DDP`、`FOB`、`MOQ` 保留拉丁缩写，不展开注释；`EVA`、`PU` 同理。

### 8.2 已记录的偏差

1. **按钮 / 链接文字归 `text` 而非 `attr`。** 32a 词典的 `attr` 桶里只有属性值（`Close image`、`Product concept image`、`Choose language`、`you@example.com`、`e.g. United Kingdom`、`Next step`），而 CTA 文本（`Order a sample`、`Home`、`Explore`）落在 `block` / `text`。本轮按同一口径处理，证据：`packrivo-wp/packrivo-home-v4/inc/33a-category-cards.php:82` 的 `aria-label` 进 `attr`，同文件 68 行的按钮文案进 `text`。
2. **阿拉伯语箭头方向。** `Configure this box →` 在 ar 里写作 `اضبط هذا الصندوق ←`，RTL 排版下视觉指向一致；占位符与数字不受影响。
3. **日语小数提示。** 英文 `(one decimal place)` 若直译为「小数第1位」，会引入英文原串没有的数字 `1`，破坏数字不变量；ja 写作 `（小数点以下も可）`，保留「可输入小数」的语义而不新增数字。
4. **`%s` 位置不变。** 每种语言的占位符序列与英文逐字节相同（`%s`、`%1$s`、`%2$s` 顺序不变），`%` 字符计数相同，`&amp;` 等实体与内联标签原样保留。自检输出第 2 节已给出 0 差异。

## 9. 状态

- 词典 JSON：**未改动**（`git status` 只显示新增文件）。
- 容器 / 数据库：**未触碰**。
- 交付脚本：`dsh-out/33ab-tools/`（`probe.py`、`dict_probe.py`、`classify.py`、`plan.py`、`tr1..tr4.py`、`bt.py`、`build.py`、`report.py`）。
- 提交：待审查，本轮只提交上述新增文件，提交信息 `wip: dsh 33a/33b translations csv (not yet reviewed)`。
