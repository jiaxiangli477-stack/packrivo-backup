# Codex 任务单 · 2026-10-27

直接按顺序执行，不需要再向我确认方案；遇到需要我决定的事，写进第 4 节的"待站长决定"，然后继续做下一项。

依据文件：
- `E:\破甲工作区\packrivo-v2\docs\REVIEW-RESULT-2026-10-26.md`（主题/插件审核）
- `E:\破甲工作区\packrivo-v2\docs\SEO-KEYWORD-PLAN-2026-10-27.md`（SEO 计划）

已完成、不用再做：footer 剥离、分组复数匹配、title-tag、poly 厚度、REST nonce、首页英雄价走引擎、卡片响应式网格（提交 1bd71c6、3238fb1）。

---

## 规则

1. **生产站只读。** 所有修改先在本地副本完成（`packrivo-wp\` 源码 + 你在 D 盘复制的 WordPress 副本）。部署到 www.packrivo.com 前必须停下来告诉我。
2. **不能起本地服务器时，不要卡住。** 改用 PHP 命令行直接调用函数验证（`php -r` 加载插件类，或者用 `wp eval`）；HTTP 层面的验证（301、页面输出）列进第 4 节，等我启动 Docker 之后再测。
3. **改任何英文 title，同一次提交里必须更新所有语种字典的对应键**（见 SEO 计划第 0 节第 6 条）。
4. **不编造内容。** 价格只用报价引擎算出的数字；没有实拍图就保留 AI 概念图并继续标注；尺寸数据要注明来源，没有来源就不写。
5. 每完成一组就提交一次，提交信息写清楚改了什么、怎么验证的。

---

## 1. 主题 / 插件剩余问题

### 1.1【P0】报价器没有正式入口
现状：首页三个按钮和 header 都指向 `/inquiry/`（旧表单）；`functions.php:80` 的回退菜单指向 `/get-a-quote/`；`[packrivo_quote]` 只在 `/quote-test/`。
做：
- 新建（或复用）页面 `/get-a-quote/`，内容为 `[packrivo_quote]`，并去掉它现在指向 `/inquiry/` 的 301。
- `front-page.php:65/176/298` 和 `header.php:7` 改为指向 `/get-a-quote/`。
- `/inquiry/` 先保留不动（它在 GSC 上有排名，"packaging quote" 排第 11）。等报价器在生产上验收通过后，再把 `/inquiry/` 301 到 `/get-a-quote/`，这一步先列进第 4 节。
验收：本地副本里首页三个按钮都打开报价器，并且五步都能走完。

### 1.2【P1】配置器材料说明里有未核实的认证
`pkq-quote.js:8,12` 和 `pricing.json:58,78` 写着 "FSC Recycled credit"、"BPI certified"。
做：改源头 `packrivo-v2/lib/pricing.ts` → 运行 `node packrivo-shared/build-shared.mjs` 重新生成。文案改为不带认证承诺的说法，例如 "Recycled board (FSC certificate on request)"、"Compostable stock (certificate on request)"。JS 里的本地回退常量也要同步改。

### 1.3【P2】前后端定价参数仍有写死的值
- `pkq-quote.js:64` 的参考尺寸 30×20×10 改为读 `cfg.sizeRef`（PHP 侧用 `wp_localize_script` 下发）。
- `pkq-quote.js:87-88` 和 `class-pkq-pricing.php:221-224` 的 2500/5000 改为读 `rules.freePrintSetupFrom` / `rules.freeDieFrom`。
- `class-pkq-pricing.php:62-79`：逐行校验 `factor` / `add` 是否为数字，有任何一行不合格就整体回退到常量表，并调用 `error_log()` 记录。
验收：用 PHP 命令行对 5 组配置分别跑 `PKQ_Pricing::price()`，再用 node 跑 JS 的 `price()`，两边单价逐一相等（贴出对照表）。

### 1.4【P2】清理
- 删除没有被使用的 `/price` REST 路由（`class-pkq-rest.php:20`）。
- tokens.css 只保留一个生成器：删除 `packrivo-v2/scripts/tokens.mjs`，或者让它直接调用 build-shared.mjs；然后重新生成，并确认子主题里的 tokens.css 与 `packrivo-shared/tokens.css` 完全一致。
- 产品索引中没有封面图的卡片（34 个）给一个统一的占位样式，保证卡片高度一致。

---

## 2. SEO（先做确定的、低风险的）

### 2.1 多语言 title 字典（最高优先级）
根因已查明：插件按英文完整 `<title>` 精确匹配字典。
做：在 nl / it / de / fr 字典中，为下列页面的**现有**英文 title 加入译文。译文使用 SEO 计划第 1 节 E 表里的当地搜索词，不要直译：
`/jewelry-boxes/`、`/product-magnetic-jewelry-box/`、`/product-collapsible-magnetic-gift-box/`、`/foldable-gift-boxes/`、`/mailer-boxes/`、`/molded-pulp-insert/`、`/windowed-folding-carton/`。
meta description 如果也是同样的匹配机制，一并补上。
验收：用 PHP 命令行调用插件的查找函数，4 个语种 × 7 个页面共 28 组，输入英文 title，输出都是译文（贴出结果表）。

### 2.2 重复页合并
`/collapsible-magnetic-gift-box/` 301 到 `/product-collapsible-magnetic-gift-box/`。GSC 已核实：前者近 3 个月 0 展示，后者 11 展示。不需要再核实。
做法：用站点现有的重定向机制；如果没有，就在 mu-plugin 里加 `template_redirect`。同时把前者从 sitemap 移除。

### 2.3 H1 / title 不一致
- `/mailer-boxes/`：H1 改为 `Custom Printed Mailer Boxes`。title 不改，所以字典不受影响。
- `/foldable-gift-boxes/`：已选 B 方案（礼盒总分类页）。title 不改；在现有折叠结构内容的位置合并出一个 H2 "Foldable gift boxes" 小节，不要重复已有内容。

### 2.4 产品页模板去掉 "Sourcing"
134 个产品页的 H1 是 "Custom X Sourcing"。
做：**只改 H1**，改为 "Custom X"；title 暂不动，避免触发全量字典更新。正文首段保留一句说明 Packrivo 提供代采服务。
先在本地副本改 3 个页面，把改前改后的对比给我看，再批量改。

### 2.5 首饰盒尺寸文章（A2）
`/2026/09/21/jewelry-box-insert-fit-measurements/`：H1 改为 `Jewelry Box Dimensions: Standard Size Chart (mm & inches)`；在首屏加尺寸表。
**尺寸数据必须来自站内已有的产品规格，或者供应商规格书，并注明来源。** 凑不出可靠来源的行就不写。加 3–5 条 FAQ（FAQPage schema）。

### 2.6 暂不做
catalog 页（0 展示，不抢流量）等图片来源追溯完成后再处理；B3、C2、A4 的扩写放到下一轮。

---

## 3. 交付

全部做完后，在 `E:\破甲工作区\packrivo-v2\docs\` 下写 `CODEX-RESULT-2026-10-27.md`，内容：
- 每一项的状态：完成 / 部分完成 / 未做，并写原因
- 提交号
- 验收证据：命令和输出
- 第 4 节的内容

## 4. 待站长决定 / 待 HTTP 验证（由 Codex 填写）

- [ ] 启动 Docker 后在 packrivo-live 上测：301、`/get-a-quote/` 报价器、mailer H1、多语言 title 实际输出
- [ ] `/inquiry/` 何时 301 到 `/get-a-quote/`
- [ ] 部署到生产

- [ ] `/get-a-quote/` 本地建页、清除现有 301 并完整走通五步；修改的四个入口尚未在 WordPress 环境验证。
- [ ] 多语言珠宝盒四条现有插件覆盖字典候选：决定是否修改覆盖逻辑；另外 28 条 meta description 需逐页准确翻译与核验。
- [ ] sitemap 移除旧磁吸礼盒地址并做 HTTP 验证；上线前复核缓存、目标及生产现状。
- [ ] 查看 3 个产品 H1 草稿；确认后再批量处理 134 页。页面草稿目前是公开 REST 渲染快照，不是原始 CMS 数据。
- [ ] 尺寸文章的标准尺寸、供应商来源及 FAQ/schema 尚缺可靠依据；提供规格书或确定可用来源后续做。
- [ ] 确认 `100% recycled board` 和 `Compostable mailer` 的材料名称是否有证据；已移除认证及家庭堆肥断言，名称本身未核实。
- [ ] 本地源码与共享目录不在 Git 仓库中，需确认统一版本管理位置；避免把 ZIP 旧包当作本次修改成果。
- [ ] 产品占位及导航改动需要桌面/移动浏览器检查；薄包装尺寸（如 0.1 cm）需额外复核前后端一致性。
