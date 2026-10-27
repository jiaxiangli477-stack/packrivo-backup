# 融合方案：新图片 + v2 视觉 + SEO/GEO 新页面 · 2026-10-27

目的：把三样东西放进线上 WordPress 站，**不覆盖、不打乱现有 392 个页面**：
1. 新优化的产品图片（`E:\破甲工作区\audit-parkrivo\codex-output\`，223 个首饰包装 SKU）
2. packrivo-v2（:3101）产品页的视觉和交互
3. 买家词新页面（SEO + GEO）

依据：`audit-parkrivo\keywords-plan.md`（26 页词表）、`audit-parkrivo\packrivo-UI-SEO-GEO整改清单.md`、`docs\SEO-KEYWORD-PLAN-2026-10-27.md`、`docs\REVIEW-RESULT-2026-10-26.md`。

---

## 0. 先说清楚的三件事

### 0.1 图片怎么标注（重要）
交付索引的"备注"列写的是 **"图生图优化：清除所有可见文字、标识与水印，改善背景和光线"**，生成工具是 Codex 的 image_gen。也就是说，这些是**以供应商原图为参考、由 AI 重新生成的图**，不是相机直接拍出来的照片。

**2026-10-27 抽查 3 组（原图 vs 交付图）：**
- `rigid-lid-and-base-box-251101`：只去掉了底部的中文卖点横幅、背景调亮了，产品完全一致 ✅
- `ring-box-O1CN01aHeS`：产品一致，摆放位置有变化、倒影被去掉 ✅
- `drawer-box-033975`：**盒盖上的玫瑰压纹和 "EVERY PIECE OF GIFT TELLS A STORY" 压印字也被一起去掉了**，变成了素白盒盖 ⚠️。压纹是产品工艺本身，不是水印；买家看到的图和供应商的实物不一样。

结论：大部分图忠实于原图，可以作为"供应商产品图（已修图）"使用；但 AI 会把**产品上的印刷、压纹、烫金文字**当成水印一起清掉，所以每张图都必须和原图对比一遍。
- 标注：`Supplier product photo, retouched (text and watermarks removed)`。站长确认图片来自供应商授权，产品本身没有改动的，可以这样写。
- 上线前逐张核对：只允许去掉**图片上叠加的**文字、logo、水印、卖点横幅；产品本身的压纹、印字、图案、五金件必须保留。被误删的，用原图重做，或者在页面上写明 "shown blank; emboss/print optional"（素盒是可选款时）。
- 核对结果写进交付索引新增的一列"原图一致性"：一致 / 误删工艺 / 不可用。
- ✅ 供应商授权记录（聊天截图、授权书）存档到 `audit-parkrivo\授权记录\`，按 SKU 对应。
- 上线前每张图和原图（索引里的"原图参考"列）并排抽查，结构、颜色、开合方式不一致的不上线。

### 0.2 "藏词"不能做
把关键词写成白字、隐藏 div、`display:none`、塞进页脚，都属于 Google 明确处罚的 hidden text / keyword stuffing，会让整个域名被降权。
能做而且有效的是**看得见的买家词**：
- 品类页标题、H1、首段、图片 alt、规格表、FAQ
- Schema 结构化数据：Product、ItemList、FAQPage、BreadcrumbList（这是给搜索引擎和 AI 看的正当"隐藏"信息）
- GEO：每页开头一段 40–60 词的"直接答案"、带数字的规格表、`llms.txt` 列出品类页。AI 搜索最常引用这种内容。

### 0.3 图片和你的主攻方向正好对上
223 个 SKU **全部是首饰包装**，和 SEO 计划选的主攻细分（首饰盒）一致。这就是"页面太少"的解法：**每个图片分类做成一个买家词品类页**，用真实 SKU 撑起页面内容，而不是再批量写文章。

---

## 1. 新页面：12 个图片分类 → 9 个首饰子品类页

| 新页面 URL | 主关键词（待查搜索量） | 图片分类（已完成 / 总数） | 说明 |
|---|---|---|---|
| `/jewelry-boxes/ring-boxes/` | custom ring boxes | 01-ring-box（29/38） | 数量最多，先做 |
| `/jewelry-boxes/velvet-jewelry-boxes/` | velvet jewelry boxes | 02-velvet-box（27/29） | |
| `/jewelry-boxes/necklace-boxes/` | necklace boxes / necklace packaging | 03-necklace-box（16/22） | |
| `/jewelry-boxes/jewelry-pouches/` | custom jewelry pouches with logo | 06-pouch（27/31） | 和现有 `/textile-jewelry-pouch/` 合并方向，见第 3 节 |
| `/jewelry-boxes/paper-jewelry-boxes/` | paper jewelry boxes / kraft jewelry boxes | 07-paper-box（14/22） | |
| `/jewelry-boxes/drawer-jewelry-boxes/` | drawer jewelry box packaging | 10-drawer（16/16） | 现有 `/product-drawer-jewelry-box/` 作为子页 |
| `/jewelry-boxes/lid-and-base-jewelry-boxes/` | lid and base jewelry box | 09-rigid-lid-base（15/15） | |
| `/jewelry-boxes/jewelry-gift-boxes/` | jewelry gift boxes wholesale | 05-gift-box（2/12） | 图片完成后再上 |
| `/jewelry-boxes/leather-jewelry-boxes/` | PU leather jewelry box packaging | 11-pu-leather（4/4）+ 08-wooden（0/1） | SKU 少，先合并成一页 |

不单独建页的分类：
- **04-storage-box（首饰收纳盒）**：搜这类词的多是买收纳盒自用的消费者，不是品牌采购。先放进 A1 `/jewelry-boxes/` 页面的一个区块，不单独抢词。
- **12-other**：图片分到最接近的子品类。

**主关键词的搜索量**我没有可靠数据，上表的词是按买家意图拟的。建议先用 Semrush 或 Google Keyword Planner 查一遍再定稿：同义词里选量大的作主词，其余放进 H2 和 FAQ。

每个子品类页的结构（照搬 v2 产品页的交互，内容用真实数据）：
1. H1 + 40–60 词直接答案（这是什么、MOQ、价格区间、交期）
2. SKU 卡片网格：每张卡片放 1 张主图 + 名称 + "from $x at 1,000 pcs"（价格由报价引擎算） + MOQ + Configure 按钮（直达 `/get-a-quote/?style=…`）
3. 筛选：材质（绒布 / 纸 / PU / 硬纸板）、开合方式（翻盖 / 抽屉 / 天地盖 / 磁吸）、排序（价格 / 名称）
4. 规格表：常见内尺寸、可选内衬、可选工艺（烫金、丝印、压纹）
5. FAQ 4–6 条（FAQPage schema）
6. 面包屑 `Home › Jewelry Boxes › Ring Boxes`（BreadcrumbList schema）+ ItemList schema
7. 图片 alt 写清楚："Black velvet ring box with slot insert, open"，不要堆砌关键词

**单个 SKU 页面**（现有 `catalog-*-o1cn…` 约 43 个，加上新 SKU）：先设为 noindex，作为品类页里卡片的详情页。等某个 SKU 有了独立规格、价格和问答，再改成 index。原因：每个 SKU 页只有约 300 词、彼此高度相似，GSC 显示它们 0 展示，放开收录只会稀释品类页。

**多语言**：每个子品类页的 title、H1、meta 在同一次提交里写入 nl / it / de / fr 字典（插件按英文原文精确匹配）。小语种已经有首饰盒展示，这是最容易拿到排名的地方。

---

## 2. v2 视觉怎么搬，才不会覆盖和打乱

原则：**只新增模板，不改现有页面的内容**。

| 搬什么 | 放在哪 | 影响范围 |
|---|---|---|
| 产品卡片样式（价格、MOQ、标签、Configure 按钮） | 子主题新建 `template-jewelry-category.php` + `assets/site.css` 新增 `.pk-sku-*` 样式 | 只作用于第 1 节的 9 个新页面 |
| 搜索、筛选、排序 | 同上，原生 JS，筛选状态写在 URL 参数里，可分享 | 同上 |
| 产品索引页的卡片升级 | `template-products-v4.php` | 只作用于 `/products/` |
| 设计令牌（颜色、字体） | 已经由 `build-shared.mjs` 从 v2 同步生成 | 无新增影响 |

**不搬**的内容（v2 里虚构或者不适用）：
- FSC C168842 / ISO 9001 / SEDEX / BPI 徽章
- 4 个客户案例
- "our own presses"
- "Ships in 10 days"
- Ridge / Halo 等系列名

现有 392 个页面继续用 `page.php`，不受影响。新模板只在 WP 后台为新页面选择"Jewelry category (v4)"时才生效。回滚方式：把这 9 个页面的模板改回默认，或者直接停用子主题。

---

## 3. 和现有页面的关系（避免自己和自己抢词）

| 现有页面 | 处理 |
|---|---|
| `/jewelry-boxes/` | 升级为首饰盒总品类页（A1）：顶部放 9 个子品类入口卡片，每个配一张代表图；保留现有内容 |
| `/product-magnetic-jewelry-box/`、`/product-drawer-jewelry-box/` 等 7 个首饰产品页 | 保留，挂到对应子品类下，换上新主图；面包屑改为三级 |
| `/textile-jewelry-pouch/`、`/catalog-pouch-b/` | 内容并入 `/jewelry-boxes/jewelry-pouches/`，两个旧 URL 301 过去（先查 GSC 展示数，有展示的保留 URL，改为子品类页） |
| `/catalog-jewelry-box/` | 301 到 `/jewelry-boxes/` |
| `catalog-*-o1cn…` | 换新图后作为 SKU 详情页保留，设为 noindex，并在页内链回所属子品类 |

---

## 4. 执行顺序

**前置（不完成就不能上线新模板）**
1. 子主题审核 P0 已修（title-tag 等），还剩报价器 `/get-a-quote/` 页面要建好，并走通五步流程。
2. 本地测试环境：Docker 装到 D 盘（等站长确认），或用 staging。

**第 1 批：先做 2 个子品类页，验证效果**
- ring-boxes（29 张图）、drawer-jewelry-boxes（16 张，全部完成）
- 图片上传前逐张和原图对比；按 0.1 的写法标注
- 本地验收通过后上线，在 GSC 提交编入索引

**第 2 批（第 1 批上线 2 周后，确认已被收录）**：其余 7 个子品类页 + `/jewelry-boxes/` 升级 + 合并 301。

**第 3 批**：`/products/` 索引页换新卡片；4 个语种的字典。

**不做**：批量新写文章；把 223 个 SKU 页全部放开收录；隐藏文字。

## 5. 验收

- 每个新页面：唯一 H1；title 不超过 60 字符且含主关键词；有 ItemList、FAQPage、BreadcrumbList schema 并通过 Rich Results Test；手机 375 px 下没有横向滚动；图片带 alt 和标注；LCP 小于 2.5 s。
- 现有页面零影响：上线前后抓取 20 个旧页面，对比 title、H1 和页脚数量，必须完全一致。
- 4 周后看 GSC：按"网页包含 `/jewelry-boxes/`"筛选，对比展示量。
