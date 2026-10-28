# Codex 任务单 · 2026-10-27j · 首页换掉技术词 + 找出子主题没翻译的文字

**一轮做完。** 只改子主题 `E:\破甲工作区\packrivo-wp\packrivo-home-v4`，不改父主题、插件、mu-plugin。生产只读，不部署。完成后同步到 `E:\破甲工作区\packrivo-backup` 并 push，结果写到 `docs\CODEX-RESULT-2026-10-27j.md`，附命令和原样输出。

## 为什么

Claude 在本地 `/ar/` 首页数到 **83 段可见英文**：H1、步骤、FAQ、价格说明、博客区标题都没翻译。生产的阿语首页 H1 是阿语，所以这个子主题现在上线，多语言首页会**倒退**。

首页上从 v2 带过来的经营说法，**站长已于 2026-10-27 逐条确认全部属实**：服务费 8–15%、生产 7–30 天、现货 1–15 天、打样 5–10 天、无印现货 100 个起、美欧 DDP 为标准且可 FOB、包装前寄印张、每单附照片和材料声明、回收率表、报价附刀模、供应商经过审核、流程含验货。**这些说法都保留，不要删。**

## 1. 首页文案（`front-page.php`）：只换掉技术词

规则：H2、按钮、卡片和表格里不放买家看不懂的技术词。只改下表几处，**其他句子和数字一个字都不动**。

| 位置 | 现在 | 改成 |
| --- | --- | --- |
| hero lede（约 60 行） | `…Reference prices published, dielines included, and a person…` | `…Reference prices published, a cutting template with every quote, and a person…`（只换 `dielines included` 这几个词） |
| 材料表（约 197–199 行） | `Kraft E-flute` | `Kraft corrugated` |
| 同上 | `White E-flute` | `White corrugated` |
| 同上 | `Greyboard 2 mm` | `Rigid greyboard` |

- 回收率百分比、Use 列、标题和说明句都保留。
- 改完全文搜一遍 `E-flute`、`mm`（尺寸 `cm` 不算）、`dieline`，贴出剩余命中并说明。

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

- 首页 en：第 1 节 4 处已改，其余经营说法原样保留（贴 diff）；375 / 768 / 1280 截图各 1 张。
- `dsh-in\untranslated-27j.json` 存在，统计表贴出。
- 首页 × 11 语种 H1 是译文。
- 提交并 push。不部署，不改 www.packrivo.com。

下一步：dsh 翻译这份清单，你再合并字典。这一轮不需要翻译。
