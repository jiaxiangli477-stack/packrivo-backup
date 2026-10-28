# Codex 任务单 · 2026-10-28l · 修评审里的 P0 / P1（询盘表单、首饰页、报价页手机端、按钮去向）

**一轮做完。** 依据：`D:\Codex\packrivo-design-review-20261028\Packrivo-客户体验视觉评审.md`（截图和 diagnostics 在同目录）。Claude 已复现两个 P0：本地 `/inquiry/?product=pillow-jewelry-box` 里 `<form>` 为 0（生产为 1）；本地 `/jewelry-boxes/` 原始 HTML 里有 `#jewelry-supplier-references .supplier-reference-grid{…}` 裸露文字。

规则：
- 优先改子主题 `packrivo-home-v4` 和报价插件 `packrivo-quote`。
- 父主题、其他插件、mu-plugin 不改源码。确实需要改的，在 `packrivo-backup\deploy\` 里准备补丁并说明，不应用。
- 文案、价格和站长确认过的经营说法都不改。
- 生产只读，不部署。完成后同步到 `E:\破甲工作区\packrivo-backup` 并 push，结果写到 `docs\CODEX-RESULT-2026-10-28l.md`，附命令和原样输出。

## 1. P0 · 询盘页表单丢失

- 查清本地 `/inquiry/` 为什么没有 form / select / input。沿着页面内容、`the_content` 过滤链、子主题模板逐个排查。贴出原因。
  - 怀疑方向：子主题的 page 模板或 kses / wpautop 把表单剥掉了；
  - 也可能是本地数据库里的页面内容和生产不一样。
- 修复后：
  - 表单控件数量和生产一致；
  - `?product=pillow-jewelry-box` 能选中对应产品；
  - 375 / 768 / 1440 各截 1 张图；
  - 键盘能走完所有字段。
- **不要提交表单。**

## 2. P0 · 首饰页样式文字外露 + 手机横向溢出

- 找到 `#jewelry-supplier-references` 那段样式是怎么变成正文文字的，把它放回能生效的位置（例如子主题 CSS 文件）。
- `.comparison-table` 在 375 px 下宽 650 px：
  - 手机上改成纵向卡片，或者放进有明显滚动提示的横向容器；
  - 桌面表格不变；
  - 每一列的信息都不能丢。
- 验收：
  - 首饰页、首页、`/products/`、1 个产品页、报价页，375 px 下 `scrollWidth <= 375`；
  - ar 首饰页也一样；
  - 首屏没有代码文字。

## 3. P1 · 报价页（`packrivo-quote`）

- **手机端顺序：** 当前步骤的选项和"继续"按钮在前，价格摘要在后。摘要可以收成一行，点开看明细，但所有已选内容和价格都不能少。
- **价格文字对比度：** `.pkq-card .pkq-price` 现在约 1.6:1。深色卡片上的价格、数量、标签、说明逐项实测，大字不低于 3:1，正文不低于 4.5:1。贴出改前和改后的数值。
- 价格计算和引擎**一个字不改**。前后各跑一次 `priceQuote` 对照：rigid 500、mailer 500、sticker、shipping 4 组，结果相同。

## 4. P1 · 报价按钮去向统一

先列出所有报价类按钮：文字、所在位置、指向。包括导航 `Instant quote`、首页 `Build a quote`、产品卡 Configure、产品页底部 `Get a quote · MOQ 500`。

规则：
- **英文页：** 报价类按钮都进 `/get-a-quote/`；从产品来的带上 `?style=&qty=500&product=`，沿用 27c 的映射。底部按钮来自 mu-plugin `packrivo-wave1.php`。能在子主题里用过滤器或最小脚本改指向的，就在子主题改；不能的，准备 mu 补丁，不应用。
- **其他 11 个语种页：** 报价类按钮指向 `/{lang}/inquiry/?product=<slug>`。这是生产现在的做法，表单已有译文。不要指向 404 的 `/{lang}/get-a-quote/`。
- 按钮文字不改。改了就要重新翻译 11 种语言，不在本轮范围。

验收：表格列出 en / ar / de 三个语种 × 首页、产品页、`/products/` 的每个报价按钮，各自的 href 和 HTTP 状态，全部 200。

## 5. 不做

- P2 视觉调整（首屏露图、按钮统一 44 px、筛选样式）留到下一轮设计。
- 报价页多语言（84+ 条字符串）留给站长决定。

## 6. 结束

- 重新打包子主题和报价插件 zip，放进 `deploy\`。
- 更新 `deploy\README.md` 的阻断清单。
- 提交并 push。不部署，不改 www.packrivo.com。
