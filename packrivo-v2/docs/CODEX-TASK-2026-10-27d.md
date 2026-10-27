# Codex 任务单 · 2026-10-27d · 223 个首饰包装 SKU 新图上新（SEO / GEO）

直接按顺序执行，不需要再向我确认方案。遇到需要我决定的事，写进结果文件的"待站长决定"，然后继续做下一项。

**图片还在分批交付。** 本任务做成**可重复运行**的批处理脚本：

- 每次只处理当时已经交付、并且通过质检的 SKU。
- 下一批图出来后再跑一次，已经处理过的 SKU 不重复处理。

## 0. 开工前

1. 读 `E:\破甲工作区\packrivo-v2\agent.md`，第 1–8 条照做。生产站只读。
2. 读 `docs\SEO-KEYWORD-PLAN-2026-10-27.md` 和 `docs\INTEGRATION-PLAN-images-visual-seo-2026-10-27.md`，后者主要看第 0、1、3 节。
3. 如果 27c 任务单已经在做：本任务和它共用 `pk_engine_style_for` / `pk_from_price` 和 `/get-a-quote/?product=` 预填。27c 没做完的话，本任务**不要自己另写一套**，在 SKU 页上先只放 `Get a price →` 链接。
4. 输入：
   - 图片目录：`E:\破甲工作区\audit-parkrivo\codex-output\`
   - 索引：`交付索引.csv`，223 行。按列名读取，不要按位置；文件编码是 UTF-8 BOM。
   - 原图：`D:\Codex\packrivo-image-work\sources\catalog-<slug>.webp`
   - 图片进度：`D:\Codex\packrivo-image-work\handoff-2026-10-27.md`
   - 买家词库：`E:\破甲工作区\audit-parkrivo\keywords-plan.md`（1707 个买家意向词，第 6、7 节是续词公式和排除词）
5. 现状（Claude 已核实）：
   - 线上 `/catalog-<slug>/` 这批 SKU 页**有的存在**，例如 `catalog-ring-box-O1CN012Vjh` 返回 200；**有的是 404**，例如 `catalog-jewelry-packaging-box-218077`。
   - 现有页面约 230 词，没有 Product schema。
   - 现有 meta description 写着 `Historical 1688 reference: ¥0.39`，**暴露了供应商进价，而且不是报价引擎的价格**。
   - 索引里 223 个 SKU 只有 158 个不同的产品名，例如 `Drawer Box` 重复 10 次，所以 title 大量重复。
6. 先在本地副本里导出所有 `catalog-*` 页面：slug、状态、title、meta description、robots、字数。把这份清单和 CSV 对齐，在结果里写清楚三类各有多少：
   - 线上有、CSV 也有
   - 只在 CSV 里
   - 只在线上

## 1. 图片质检（每个 SKU 必过，是上新的前提）

建一个 `catalog-qa.csv`，放在 `codex-output\` 旁边，一个 SKU 一行。

逐个 SKU 把交付主图（以及有的话，angle / detail 图）和原图放在一起比对，检查四项：

1. 产品结构、颜色、材质、开合状态、配件和原图一致。
2. **产品本身的压纹、烫金、印花没有被当成文字误删。** 已知 `drawer-box-033975` 的玫瑰压纹丢了，判不通过。
3. 没有残留文字、水印、角标，也没有第三方品牌。
4. 没有修图瑕疵：结构变形、边缘糊掉、多出或缺少部件。

规则：
- 每一项填 pass / fail 和原因。
- 结论只有 `通过` / `不通过` / `需人工看`。
- 索引里的例外 SKU 直接判不通过，不做处理：原图缺失的 1 个，以及 Hello Kitty / Mickey 等版权形象的 2 个。
- angle / detail 图**单独判**。主图通过、变体不通过的，页面只用主图。
- `需人工看` 的，拼一张"原图 | 新图"的对比图，放到 `codex-output\_qa-review\`，给我看。

验收：
- 贴出 `catalog-qa.csv` 的统计（各结论多少个）。
- 列出不通过和需人工看的清单。

## 2. 图片标注

- 图片下方小字统一写：`Supplier product photo, retouched (text and watermarks removed)`。
- alt：写产品本身，例如 `White lid-and-base ring box with foam slot insert`。不要堆关键词。
- schema 里的 ImageObject：`caption` 和上面的小字相同，`creditText` 写 `Packrivo`。

## 3. 关键词 + SKU 页（只处理质检通过的；关键词是本任务最重要的部分）

目标是带来**询盘和点击**，不是技术词排名。所有 title、H1、meta、导语、FAQ、alt 都按下面的规则写。

### 3.0 关键词规则

**分工，避免自己和自己抢词：**
- 子品类聚合页吃**头部买家词**。例如：
  - `custom ring boxes`、`ring boxes wholesale`
  - `custom jewelry pouches with logo`、`velvet jewelry boxes wholesale`
  - `necklace boxes with logo`、`drawer jewelry boxes`
  - 以 INTEGRATION-PLAN 第 1 节表格的主词为准。
- SKU 页吃**长尾买家词**。公式：`看得见的特征（颜色 / 材质 / 结构 / 内托） + 品类根 + 买家修饰词`。
  - 买家修饰词从这里选：`custom`、`with logo`、`wholesale`、`bulk`、`private label`、`for small business`、`low minimum`、`MOQ 500`、`with insert`。
  - 例子：
    - `Custom White Ring Box with Logo — Lid-and-Base`
    - `Wholesale Black Velvet Necklace Box with Insert`
    - `Kraft Paper Jewelry Box with Logo for Small Business`
    - `Pink Drawer Jewelry Box with Ribbon — Bulk Custom`
  - 同一子品类里，修饰词要**轮换着用**，不要 38 个戒指盒都是 `Custom … with Logo`。
- 每个 SKU 页定一个主长尾词，写进结果文件的映射表：slug → 主长尾词 → 用了哪个修饰词。

**禁用的词**（keywords-plan.md 第 5、7 节）：
- 本地意图：`near me`、城市名、`in delhi`、`surat` 等
- 平台词：`1688`、`alibaba`
- DIY 类：`diy`、`how to make`
- 消费者自用意图：`for women`、`for girls`、`for sale`
- 技术参数词：`E-flute`、`SBS 400`、`greyboard 2 mm`、`GSM`、`Delta E`、`D50`、`ISTA`、`ASTM`、`dieline` 等。title、H1、meta 里一个都不放。正文规格表里需要的话可以出现，但不要当标题词。

**多语言：** 用当地买家搜索词，不要直译。例如：
- 德语：`Ringschachtel mit Logo`、`Schmuckschachteln Großhandel`
- 法语：`écrin bague personnalisé`、`boîte à bijoux personnalisée en gros`
- 以 SEO 计划第 1 节 E 表为准，表里没有的，参照它的写法。

### 3.1 页面内容

**Title / H1：**
- 按 3.0 的长尾公式写，title 再加 ` | Packrivo`，总长不超过 60 字符。H1 可以比 title 长一点，但主长尾词要完整出现。
- 颜色、材质、结构、内托等特征**只取图上看得见的，或者原名里已经有的**。
- 223 个 SKU 里，title 和 H1 不许重复。
- 现有标题如果已经是买家长尾词，而且唯一，就保留；笼统的（例如 `Drawer Box`）和纯描述、没有买家修饰词的，都改。

**多语言字典：**
- 改了的英文 title / H1，同一次提交里要在 11 个语种的字典中加上对应译文。
- 译文用 SEO 计划第 1 节 E 表里的当地搜索词，例如德语 Ringschachtel、法语 écrin。
- 这是硬规则。做不完就少改几个标题，**不能只改英文**。

**meta description：**
- 删掉所有 `Historical 1688 reference: ¥…`，以及任何人民币价格和供应商信息。
- 新写法：主长尾词 + 适合放什么首饰 + 一个买家关心的点（`with your logo`、`wholesale from 500 pcs`、`samples available`）+ 行动号召（`Get a price today.`），不超过 155 字符，每页唯一。
- 例：`Custom white ring boxes with your logo, lid-and-base with foam slot. Wholesale from 500 pcs, samples available. Get a price today.`

**正文结构（从上到下）：**
1. 主图画廊，加上第 2 节的标注。
2. 导语：40–80 词，**每个 SKU 单独写**，第一句就出现主长尾词。从买家角度写：
   - 适合什么品牌、放什么首饰，例如 `for handmade jewelry brands and Etsy sellers` 这种买家画像
   - 能印 logo
   - 能做多大批量
   - 只描述看得见的事实：颜色、材质观感、开合、内托
   - 不写没有来源的尺寸、克重、交期，也不堆技术参数
3. Key facts 列表（GEO 用，短句、事实、可被引用）：
   - `Minimum order: 500 pieces per design`
   - `Ships from: Shenzhen, China (DDP or FOB)`
   - `Customisation: logo, colour, size and insert on request`
   - `Packrivo is a sourcing agent: we coordinate sampling, production and inspection with vetted suppliers.`
   - 这几句逐字使用，不要加别的承诺。
4. 价格：
   - 有引擎映射的（27c 的 `pk_from_price`），显示 `From $X / unit at 500 pcs`。
   - 没有映射的，不显示价格。
   - **任何情况都不出现人民币、1688 和"参考价"。**
5. CTA：`Get a price for this box` 指向 `/get-a-quote/?product=<slug>`（有映射时再带 `&style=`），`Order a sample` 指向 `/samples/`。
6. 面包屑：Home › Jewelry boxes › <子品类> › <SKU>。子品类按索引的「分类目录」对应到 INTEGRATION-PLAN 第 1 节的 9 个子品类 URL。还没建的子品类，面包屑先停在 Jewelry boxes。
7. `More <子品类名>`：同子品类 4 个 SKU，只链到已经通过质检的。

**Schema：**
- `Product`：name、image（ImageObject 数组）、description、sku 用 slug、category。
- **不写 `brand`**，因为这不是 Packrivo 的品牌产品。
- 只有引擎能算出价格的才写 `offers`，币种用 USD。
- 再加 `BreadcrumbList`。

### 3.2 收录规则

| 情况 | 处理 |
|---|---|
| 质检通过，title 唯一，导语满 40 词 | `index,follow`，进 sitemap |
| 线上已有页面，但质检不通过或需人工看 | 页面保留，图片维持原样，`noindex,follow`，移出 sitemap，原因写进结果 |
| 线上 404，质检又不通过 | 不新建 |
| 线上 404，质检通过 | 在本地新建页面，URL 用索引里的 slug |

- 现有页面 URL **不改**，只改内容。
- canonical 指向自己的小写 URL。

### 3.3 图片文件

- 用交付的 1200 / 480 webp 两档做 srcset。
- 先进本地媒体库，目录名和 README 里的 `catalog-final-20261026` 保持一致，方便以后原样上传。
- **不上传服务器。**

## 4. 子品类聚合页（ItemList）

- 已经存在的首饰子品类页（27b 做的前 2 个，加上之后建的）要列出本子品类所有质检通过的 SKU 卡片：图、标题、一句话、`Get a price →`。
- 输出 `ItemList` schema，按 SKU 的 URL 排列。
- 还没建的子品类页，不在本任务里建。在结果里列出每个子品类有多少个通过质检的 SKU，供下一轮使用。
- `04-storage-box` 不单独建页，放进 `/jewelry-boxes/` 页面的一个区块，原因见 INTEGRATION-PLAN 第 1 节。

## 5. GEO 补充

- 每个子品类页都要有 3–5 条问答式 FAQ（加 FAQPage schema）。
  - **问题用买家真实会搜、会问 AI 的句子**，例如：
    - `How much do custom ring boxes cost at 500 pieces?`
    - `Can I get jewelry pouches with my logo?`
    - `What is the minimum order for custom necklace boxes?`
    - `Can I order a sample before buying wholesale?`
    - `Do you ship custom jewelry boxes to the US and UK?`
  - 答案第一句直接回答，便于被 AI 引用。
  - 价格只引用报价引擎的数字，或者链接到报价器。
  - 其他内容只用可证实的事实：MOQ 500、深圳、DDP/FOB、代采模式、找报价器和样品的路径。不写交期、认证、材料百分比。
- 如果线上有 `/llms.txt`（v2 里有 `app/llms.txt` 可以参考），在本地副本里加一个 `Jewelry packaging catalogue` 小节，只列子品类聚合页的 URL 和一句话说明。没有就列进"待站长决定"。

## 6. 验收（结果里贴命令和原样输出）

1. `catalog-qa.csv` 的统计。
2. 本地全部 `catalog-*` 页面的 title 去重检查：重复数为 0。
   - 关键词映射表（slug → 主长尾词 → 修饰词）。每个修饰词在各子品类里的使用次数也要统计。
   - 用脚本扫描 title、H1、meta 里的禁用词（3.0 节列出的本地词、平台词、DIY 词、消费者词、技术参数词）：命中 0 次。
3. 全站搜 `1688`、`¥`、`Historical`、`reference:`：在 catalog 页面的输出里命中 0 次。
4. 改过的英文 title：在 11 个语种的字典里都有对应键。贴出一张表：语种 × 新增键数。
5. 任选 3 个 SKU 页，用 `wp eval` 或 curl 本地 HTML，贴出：
   - title、meta、robots、canonical
   - H1
   - JSON-LD（Product + BreadcrumbList）
   - 图片标注那一行
6. index 和 noindex 各多少个页面；sitemap 里 catalog URL 的数量。
7. 待 HTTP 验证：用 Rich Results Test 测 3 个页面，手机 375 px 截图。

## 7. 交付和停点

- 脚本放在 `E:\破甲工作区\packrivo-backup\scripts\catalog-refresh\`，必须能重复运行。
- 改动提交到 `E:\破甲工作区\packrivo-backup`（私人仓库 `jiaxiangli477-stack/packrivo-backup`），然后 `git push`。
- **图片原文件不要提交进仓库**，只提交 `catalog-qa.csv`、脚本和导出的内容清单。
- 准备一个上线包，但**不执行**：
  - 图片目录
  - wp-cli 导入 / 更新命令
  - 11 语种字典的增量
  - 回滚办法（导出改动前的 title / meta / content）
- 结果写到 `E:\破甲工作区\packrivo-v2\docs\CODEX-RESULT-2026-10-27d.md`。
- **到此为止，不上传服务器，不改 www.packrivo.com，等站长确认。**
