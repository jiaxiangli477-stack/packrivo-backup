# Codex 任务单 · 2026-10-27c · 把 v2 的转化路径、产品筛选和博客版式搬进子主题

直接按顺序执行，不需要再向我确认方案。遇到需要我决定的事，写进结果文件的"待站长决定"，然后继续做下一项。

## 0. 开工前

1. 读完 `E:\破甲工作区\packrivo-v2\agent.md`，第 1–8 条照做。
2. 上一轮（27b）的任务是：起本地环境、建 `/get-a-quote/` 页面、批量去掉 H1 里的 Sourcing、做前 2 个首饰子品类页。
   - 如果 `docs\CODEX-RESULT-2026-10-27b.md` 已经存在：以它的结果为准，在它的基础上改，不要回退。
   - 如果还不存在：先检查这几项在本地是否已经做了。做了的不要重做；没做的，`/get-a-quote/` 页面属于本轮前提，先把它补上，其余的不在本轮范围。在结果文件里写清楚你看到的状态。
3. 范围：只改
   - `E:\破甲工作区\packrivo-wp\packrivo-home-v4\`（子主题）
   - `E:\破甲工作区\packrivo-wp\packrivo-quote\`（报价插件）
   - 本地 WordPress 副本 `E:\破甲工作区\packrivo-live` 的数据库
4. 本地环境：
   - `docker.exe` 在 `D:\Docker\App` 下面，可能不在 PATH 里，用完整路径调用。
   - 在 `E:\破甲工作区\packrivo-live` 里运行 `docker compose up -d`。端口以 compose 文件为准（8080 或 8081），先在结果里写明实际端口。
   - 起不来就按 agent.md 第 6 条，用 `wp eval` / `php -r` 验证，HTTP 项标为"待 HTTP 验证"。
5. **生产站只读，部署前停下。**

## 硬规则（这一轮特别容易踩的）

- 不搬以下 v2 内容：
  - 2,400+、10–14 d、6–8 天加急交期
  - 案例和三条客户评价
  - FSC / ISTA / BPI / GRS / 94% / 26 wk
  - Ridge / Halo / Fold / Seal / Loop / Mark 系列名
  - "actually run / own presses / we can cut it"
  - sample kit 的"8 structures / 29 USD / free above 500/month"
  - "dieline 归客户 + 保留 3 年"、"Delta E 3 免费重印"
  - "two days 出结构草图"
- 所有价格都用 `PKQ_Pricing::price()` / `::ladder()` 实时算，PHP 里不写任何价格数字。算不出来就不显示价格，不要编一个回退价。
- 面向客户的英文文案：本任务单给了原文的，逐字使用；没给的，不要自己写营销句子，先列进"待站长决定"。
- 改了任何英文 title / H1，或者改了被多语言字典收录的字符串，同一次提交里要更新 11 个语种的字典。先查清楚字典收录哪些字符串（只有 title/H1，还是也包括正文），把结论写进结果文件。
- 带筛选、搜索、排序参数的页面（`?group=` `?sub=` `?q=` `?sort=` `?topic=` `?pg=`）要输出 `<link rel="canonical">` 指向不带参数的 URL。带 `q=` 的页面再加 `noindex,follow`。

---

## A. 报价插件：URL 预填（P0，B/C/D 都依赖它）

现状：`packrivo-quote.php:150` 的 `[packrivo_quote]` 只认短代码属性 `style` / `qty`，不读 URL。所以"Configure →"跳过去之后什么都没选上。

做：
- 短代码属性为空时，读 `$_GET['style']`、`$_GET['qty']`、`$_GET['product']`。
  - `style` 必须在 `PKQ_Plugin::settings()['styles']` 的 key 白名单里，不在就忽略。
  - `qty` 夹在 minQty 和 maxQty 之间。
  - `product` 用 `sanitize_title`，作为 `data-product` 输出。
- JS 提交订单时，把 `product` 一起带上，写进订单 / 邮件，方便知道询价来自哪个产品页。REST 端要 sanitize。
- 不改任何定价逻辑。

验收：
- `wp eval` 渲染三个短代码，贴出输出的 `data-style` / `data-qty` / `data-product`：
  - `style=rigid&qty=1000&product=product-drawer-jewelry-box` → 正确输出
  - `style=hack` → 被忽略
  - `qty=99999999` → 被夹到 maxQty
- 待 HTTP 验证：打开 `/get-a-quote/?style=rigid&qty=1000` 时，第一步已经选中 rigid，数量是 1000。

## B. 共用产品卡片 + 产品页到报价引擎的映射（P0）

1. 新建 `template-parts/product-card.php`，首页、产品页、文章页三处共用。版式参照 v2 `components/ProductCard.tsx`：
   - 4:3 封面，左下角 `MOQ 500` 标签
   - 分组小标题 + 标题 + 两行简介
   - 底部左侧：`from $X / unit` 和 `at 500 pcs · L×W×H cm`
   - 底部右侧：`Configure →`
   - 整张卡片链接到产品页。`Configure →` 是**单独的**链接，指向 `/get-a-quote/?style=<style>&qty=500&product=<slug>`。不要在 `<a>` 里面再套 `<a>`，用卡片内两个兄弟链接，或者用 stretched-link 的做法。
   - 没有映射的产品：不显示价格，也不显示 Configure，底部改成 `Get a price →`，指向 `/get-a-quote/?product=<slug>`。
2. 在 `functions.php` 加 `pk_engine_style_for( $post_id )`：
   - 优先读 post meta `_packrivo_engine_style`。
   - 没有 meta 时按 slug 关键词映射，**只在引擎的 style 真正描述了这种结构时才映射**。
   - 天鹅绒 / 布袋、木盒、PU 皮盒，以及引擎里没有对应结构的，一律返回 `null`。注意引擎的 `pouch` 是立式阻隔袋，不是首饰绒布袋。
3. 加 `pk_from_price( $post_id )`：用 v2 `lib/catalog.ts` 的 `fromPrice` 和 `defaultInput(style)` 同一组默认尺寸、材料、印刷、工艺，按 500 件算。结果用 transient 缓存 12 小时，pricing.json 变化时清缓存。

验收：
- 结果文件里贴出**全部产品页**的映射表（slug → style 或 null → from 价），给我逐行复核。
- 任选 3 个产品，用 `wp eval` 算的 from 价，和 v2 `node -e` 调 `fromPrice` 的结果逐一相等，贴出对照。

## C. 首页 `front-page.php`

1. **英雄区统计**：在现有三项前面加一项 `From` / `$X / unit`，X 取首页产品卡片 from 价的最小值（引擎算）。现有三项不动。检查 375 px 下 4 项的排版。
2. **产品区**：把 5 张分类卡片换成 8 张 B 节的产品卡片。
   - 选卡原则：优先首饰（戒指、项链、抽屉、磁吸首饰盒），然后礼盒、mailer、标签。必须有映射 + 有封面。选不满 8 张就有几张放几张，并在结果里说明。
   - eyebrow 保留 `What we make`。H2 改为 `Start from a structure`。
   - 右侧保留 `All products →`。
3. **价格透明区**：
   - 现有 `<ul>` 换成三条，前半句加粗，逐字使用：
     - **Setup is itemised** — and waived above {freePrintSetupFrom} printed pieces.
     - **Freight is separate** — quoted per shipment, never buried in the unit price.
     - **No tooling surprise** — one-time plate and die cost, shown before you submit.
   - 其中 {freePrintSetupFrom} 读 `pricing.json` 的 rules，不写死。
   - 表格下面的小字改为：`Indicative, excludes freight. At 5,000 pieces the unit price is {N}% lower than at 500. Final price confirmed after dieline review.`，N 由 ladder 里 500 件和 5000 件的单价现算。
4. **样品区（新增）**：放在 FAQ 前。版式参照 v2 首页的 Sample kit 深色块（左文右 2×2 小格）。
   - 文案逐字使用：eyebrow `Samples`；H2 `Hold the board before you approve the board.`；正文 `See the structure, board and finish in hand before you commit to a run.`
   - 右侧 4 格：`Rigid / Greyboard 2 mm`、`Mailer / Kraft E-flute`、`Drawer / Rigid board`、`Pouch / Fabric`。
   - 按钮：`Order a sample` 指向 `/samples/`；`Ask a question` 指向现有联系页（先在本地库确认 URL 存在，不存在就指向 `/get-a-quote/`）。
5. **FAQ**：
   - 改成 `<details>/<summary>` 手风琴。左栏 H2 保持 `Before you ask`，下面加 `Ask a specific question →` 链到联系页。
   - **问答内容一字不改**（还是现有 5 条），FAQPage schema 保留并与页面一致。
6. 其余区块（How it works、Materials、Journal、底部 CTA）不动。

验收：
- `php -l` 通过。
- `wp eval` 渲染首页，用 grep 证明三件事，贴出输出：
  - 没有出现 `2,400`、`10–14`、`FSC®`、`ISTA`、`Ridge`、`Halo`
  - 8 个 Configure 链接都带 `style=`
  - FAQ 条数是 5，和 schema 一致
- 待 HTTP 验证：375 / 768 / 1280 三种宽度截图，没有横向滚动。

## D. 产品页 `template-products-v4.php`

1. 头部 H1 和导语不动。H1 下面加一行标签：`MOQ 500` · `Dielines included` · `Reference prices on every page`。
2. 筛选栏（服务端 GET，不用 JS 也能用）：
   - **第一排**：现有 6 个大组 chip，不变。
   - **第二排**：只在 `group=jewelry` 时出现，参数 `sub=`。chip 只列**本地库里已经存在**的首饰子品类页，取 `INTEGRATION-PLAN-images-visual-seo-2026-10-27.md` 第 1 节的 9 个；不存在的不显示，并把清单写进结果。
   - **搜索框**：参数 `q=`，匹配标题 + `_packrivo_description`。
   - **排序**：在原有基础上加 `Price: low to high` / `Price: high to low`，按 `pk_from_price`；没有价格的排在最后。
   - 结果行写成 `N structures` 或 `N structures matching "q"`。
3. 卡片换成 B 节的共用卡片。
4. **空结果**：虚线框，写 `Nothing matches that yet.` / `Describe what you need and we will source it.`，按钮 `Describe your pack` 指向 `/get-a-quote/`。
5. **列表底部新增**：
   - H2 `Need something not on this page?`
   - 正文 `Counter displays, rigid trays, book-style boxes — send a photo of the product and a rough volume, and we will come back with a structure and a price range.`
   - 按钮 `Send a brief` 指向联系页，`Start from a price` 指向 `/get-a-quote/`。
6. 分页改用 `paginate_links()`，显示首页、尾页、当前页 ±2，去掉 `min($pages, 12)` 上限。

验收：
- `wp eval` 分别渲染下面几种参数，贴出每种的数量和前 3 个标题：
  - `group=jewelry`
  - `group=jewelry&sub=<一个存在的>`
  - `q=drawer`
  - `sort=price-asc`
- 确认带参数的页面有 canonical；`q=` 页面有 noindex。

## E. 博客 `template-journal-v4.php` + `single.php`

1. **精选大卡**：只在第 1 页、没选 topic 时显示，取最新一篇（有置顶就取置顶）。版式参照 v2 `app/resources/page.tsx` 的 lead：
   - 左边：`Featured` 标签 · 分类 · `N min read` / 标题 / 摘要 / `Read the guide →`
   - 右边：**封面图**（v2 没有图，这里用文章的封面），下面一栏 `In this guide`，列正文前 5 个 H2（`wp_strip_all_tags`）。
   - 精选这篇不在下面的网格里重复出现。
2. **网格卡片**：保留封面。meta 改为 `分类 · N min · 日期`，N 等于 ceil(字数 ÷ 230)。摘要截 3 行。
3. **分页 bug**：现在 `min(max_num_pages, 20)`，333 篇 ÷ 12 = 28 页，第 21–28 页点不到。改成 `paginate_links()`（首页、尾页、当前页 ±2）。
4. **导语**改为 `Supplier briefs, material notes and pricing explainers for small brands sourcing custom packaging.`；**H1 不改**。如果导语被字典收录，11 个语种一起更新。
5. **`single.php` 文末**：
   - 现有 CTA 带改为两个按钮：`Build a quote` 指向 `/get-a-quote/`（现在是 `/inquiry/`，一并改），`Order a sample` 指向 `/samples/`。
   - 标题 `Put it to work on a real pack`，正文 `The quote builder applies these numbers to your size and quantity. We confirm the dieline before anything is produced.`
   - 在相关文章上面新增 `Related structures`：3 张共用产品卡片。用文章的分类名、标签和标题，去匹配 D 节 6 个大组的关键词，取命中组里有价格的前 3 个；都不命中就取 jewelry 组。

验收：
- 贴出 `wp eval` 输出，分别证明：
  - 第 28 页有文章
  - 精选卡的 `In this guide` 有内容
  - 任选 3 篇文章的 Related structures 标题，以及各自命中的组
- `grep -n "/inquiry/" packrivo-home-v4 -r` 返回空。

## F. 新产品图（只做卡片封面这一部分）

- 来源：`E:\破甲工作区\audit-parkrivo\codex-output\`，索引 `交付索引.csv`，原图在 `D:\Codex\packrivo-image-work\sources`。
- 只给 B 节**没有封面**、或封面明显不是该产品的卡片补图，每张卡一张，按分类对应。
- **每张都要和原图对比**：产品本身的压纹或印字被误删的不能用（已知 drawer-box-033975 的玫瑰压纹丢了）。结果里列出用了哪些、对比结论、排除了哪些。
- 图片 alt 写产品描述。图片 `<figcaption>`（或卡片下方小字）的标注统一用 27d 任务单第 2 节的写法：`Image enhanced from the supplier's product photo (text and watermarks removed). Colours and details may vary slightly.`
- 上传进本地媒体库，转成 webp，宽度 800 和 1200 各一份。
- 9 个子品类页的批量做图不在本轮，按 INTEGRATION-PLAN 的分批计划另做。

## G. 提交和结果

- `E:\破甲工作区\packrivo-wp` 本身**不是 git 仓库**，不要在里面 `git init`。
- 改动前的快照已经备份到 `E:\破甲工作区\packrivo-backup`，这是一个 git 仓库，远端是私人仓库 `jiaxiangli477-stack/packrivo-backup`，`main` 分支，提交 `8081453`。
- 每完成一节（A、B、C、D、E、F）：
  1. 把 `packrivo-wp\` 下改过的文件同步到 `packrivo-backup\packrivo-wp\`。
  2. 在 `packrivo-backup` 里提交一次，提交信息写清楚改了什么、怎么验证的。
  3. 全部完成后 `git push`。这样 Claude 可以按提交逐节核验。
- 结果写到 `E:\破甲工作区\packrivo-v2\docs\CODEX-RESULT-2026-10-27c.md`，内容包括：
  - 每节的状态：完成 / 部分 / 待 HTTP 验证 / 没做及原因
  - 各节验收命令和原样输出
  - B 节的映射表
  - F 节的图片对比表
  - 字典收录范围的结论
  - 待站长决定
- **到此为止，不部署生产，不改 www.packrivo.com。**
