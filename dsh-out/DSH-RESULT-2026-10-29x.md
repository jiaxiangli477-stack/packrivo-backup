# DSH 结果 · 2026-10-29x

任务单：`docs/DSH-TASK-2026-10-29x.md`（基线提交 `8009905`）
执行方式：单线程；只改本地与演练环境，未 SSH、未碰密码、未打开 `.cf.env`；`#56` 与 Task 55 保持 paused。

## 0. 一句话结论

6 项交付全部完成；子主题重新打包为 `49ddbe6f1848419d2add0d801a0bf41817dbbab891b4747bcd629fdfc3a0599e`（38 文件 / 796,514 B / 44 条目含 6 个目录项），并按 RUNBOOK 在演练环境完整跑了一轮：正向 11 步 **82 条判据 0 FAIL**，第 9 步回滚 **11 条判据 0 FAIL**，回滚后快照与基线**逐字节一致**，3.0 守卫反向自检拦住他站 JSON；浏览器判据 **0 FAIL**（20 页 × 4 宽度 + 博客 4 档 + 首页/产品页/报价页）。

## 1. 博客列表改横向卡片

改动只在 `template-journal-v4.php` 与 `assets/site.css`，父主题、文章内容、数据库均未动。

- 结构：`article.pk-jcard` 单列；左图 `.pk-jcard-media`（卡片宽 40%，`aspect-ratio:16/10`，圆角 14px），右下压标签 `.pk-jcard-badge` = `分类 · 年份`（如 `STRUCTURES & FIT · 2026`）；右栏依次为小号大写分类 `.pk-jcard-kicker`、H2 链接标题、两行截断摘要（`-webkit-line-clamp:2`）、灰色 `.pk-jcard-meta`（`N min read · 日期`）。
- 去掉精选/置顶大图：模板里原来的 `$featured` sticky 区块整段删除，`ignore_sticky_posts => true`。
- 默认分类不进 chips：用 `get_option('default_category')` 过滤 `uncategorized`（term 1，141 篇），chips 只剩 5 个真实品类，计数取各自 `count`，"All" 取 `wp_count_posts('post')->publish`。
- 保留：分类 chips、newest/oldest 排序表单、每页 12 篇、`paginate_links` 分页。
- `<768px`：`grid-template-columns: 1fr`，图在上字在下（`@media (max-width:767px)`）。
- 11 语种共用同一模板：`min read` 与日期先查 i18n 词典（`pkv4_i18n_t()` / `pkv4_i18n_date()`），查不到再用模板内的语言表（11 语种的月份与版式）兜底。实测 `/de/blog/` → `7 Min. Lesezeit · 26. Okt 2026`，`/ja/blog/`、`/zh-CN/blog/`、`/ar/blog/`、`/fr/blog/` 同样取到本地化月份与日期版式。
- 卡片上每个字段都来自文章本身（`get_the_title` / `get_the_excerpt` / `get_the_date` / `get_the_category` / 精选图或 `_packrivo_article_cover_url`），阅读时长 = `ceil(str_word_count/230)`；没有编造的数字，也没有事实栏。

## 2. 原生页宽度限制

- `page.php` 原生分支把内容包进 `<div class="pk-page-body">`，CSS 为 `max-width: var(--pk-wrap)`（1180px）+ `padding: 0 24px` + `margin: 0 auto`——与页头 `.pk-hd-inner` 完全同一套盒子，所以 1440 下正文左边缘与 logo 左边缘同为 154px。
- 通栏白名单：`.pk-page-body > .pk-bleed | .pk-full | [data-full-bleed] | .pr-remediation-hero` 用 `width:100vw + margin-left:calc(50% - 50vw)` 放行，并按同一套 1180+24 规则补回内边距，放行后不贴边。
- 非原生分支（如 `/samples/` 走 `page-default`）原来内联 `padding:56px 0 72px` 把左右内边距抹成 0，导致 375 下 H1 贴到 x=0；改为 `padding:56px 24px 72px`。
- 另修 3 处窄屏真实溢出（浏览器判据暴露出来的）：
  1. 宽表：原生内容里的 `.comparison-table` 自带 `min-width:650px`。`page.php` 里把 `<table>…</table>` 统一包进 `.pk-table-scroll`（`max-width:100%; overflow-x:auto`），表格自己横滚。
  2. 德语复合词：`.split-heading` 的网格项默认 `min-width:auto`，`VERPACKUNGS-BESCHAFFUNG…` 把列撑到 426px；加 `.split-heading, .split-heading > * { min-width:0 }` 与长词 `overflow-wrap:anywhere`。
  3. `.page-scroll` 不裁剪，把 `documentElement.scrollWidth` 顶到 533；`@media (max-width:767px)` 下给 `.pk-page-body`/`.pk-wrap` 加 `overflow-x: clip`（实测 375 收回，表格容器内部仍可横滚 327/650）。
  4. 顺带修：非原生页 `article.article` 是 800px 居中阅读栏，正文左边缘与 logo 差 190px；改为 `.pk-wrap > article.article { margin-left:0 }`，保持 800px 行宽但左对齐。
- 验证：20 个页面 × 4 宽度 = 80 条，**溢出 0、左边距 0、1440 对齐 0**；每个页面在 1440 下各存 1 张截图（`deploy/verification-29x/evidence/screens/*-1440.png`）。
  页面清单：`/services/ /about/ /customization/ /samples/ /jewelry-boxes/ /products/ /inserts/ /inquiry/ /packaging-samples/ /guide-jewelry-insert-fit/ /product-magnetic-jewelry-box/ /product-drawer-jewelry-box/ /product-lid-and-base-jewelry-box/ /product-paperboard-insert/ /product-die-cut-insert/ /thermoformed-insert-tray/ /molded-pulp-insert/ /product-mailer-with-insert/ /de/services/ /ja/inserts/`。

## 3. 首页 hero 3 张真实供应商照片候选

全部取自既有供应商实拍图库 `audit-parkrivo/codex-output/`，中心方形裁切 + LANCZOS 缩放，q82 WebP，三档 1200/800/480，落在 `deploy/verification-29x/hero-candidates/`，**未替换现有首页大图**。

| # | 1200 档文件 | 源文件 |
|---|---|---|
| 1 | `hero-candidate-1-rigid-magnetic-1200.webp`（101,592 B，磁吸翻盖硬盒，磁扣可见） | `audit-parkrivo/codex-output/12-other/jewelry-packaging-box-200835-1200.webp` |
| 2 | `hero-candidate-2-magnetic-gift-1200.webp`（70,910 B，磁吸礼盒，开合两态） | `audit-parkrivo/codex-output/05-gift-box/gift-box-O1CN018jZH-1200.webp` |
| 3 | `hero-candidate-3-rigid-lid-base-1200.webp`（95,096 B，天地盖硬盒整组） | `audit-parkrivo/codex-output/09-rigid-lid-base/rigid-lid-and-base-box-212049-1200.webp` |

9 个文件的字节数与 SHA256 记在 `hero-candidates/sources.csv`（含逐行素材来源），推荐理由见 `hero-candidates/HERO-CANDIDATES.md`。

## 4. 141 篇未分类文章 → 映射表

`deploy/verification-29x/csv/uncategorized-mapping.csv`：141 行，字段 `ID, Title, Suggested category, Evidence keywords`。

分布：Structures & fit 51 ／ Materials & print 36 ／ Samples & approval 25 ／ Supplier briefs 18 ／ Shipping & rules 11（合计 141）。
只写文件，**未写数据库**；明细与判词在 `uncategorized-mapping-detail.json`，核验记录在 `csv/CSV-VERIFY-2026-10-29x.md`（CSV 的 141 个 ID 与库中 `uncategorized` 计数逐一对齐，差异 0/0；重跑脚本输出与提交文件 SHA256 逐字节一致）。

## 5. fictional / hypothetical 等字眼清单

`deploy/verification-29x/csv/fictional-terms.csv`：2,054 行（覆盖 324 篇文章，全站 333 篇已发布文章为扫描输入），字段 `ID, Title, Field, Matched terms, Sentence`。

按行统计：仅 hypothetical 1,354 行；仅 invented 346；仅 fictional 198；fictional+hypothetical 28；hypothetical+invented 124；三个词都有 2；imaginary 1；fictional+no measured reduction 1。按词累计：hypothetical 1,509、invented 472、fictional 229、imaginary 1、"no measured reduction" 1。摘要字段命中 76 行。**文章内容一行未改**。

## 6. Services / Inserts / Get a Quote 标题的 11 语种

`deploy/verification-29x/csv/i18n-title-additions.csv`：33 行 = 3 页 × 11 语种。

- `services`、`inserts` 的 `<title>` 在 11 个语种里原来都是英文 → 已补词典（22 条，落到 `deploy/i18n-merged/*-dict.json` 与演练站 `wp-content/uploads/packrivo-i18n/`）。
- `inquiry`（Get a Quote，页面 ID 49）的标题在 11 个语种里本来就有译文 → 11 行标记"原文已是译文，未改"。
- 渲染核验（演练站）：`/de/services` → `Verpackungsbeschaffung: 8–15 % Gebühr, schriftlicher Umfang | Packrivo`；`/zh-CN/inserts` → `包装内衬：泡棉、纸板、配合检查 | Packrivo`；11 个语种里"仍是英文标题"的页面数 = 0。
- 博客页 11 语种逐语言核验（`evidence/i18n-blog-29x/i828-browser.json`，24 行 = 12 语种 × 正常/空态）：`/{lang}/blog/` 全部 200、每页 12 卡，`<title>`/eyebrow/H1/lede/日期/min read 均本地化——`/de/` `Verpackungsratgeber für kleine Marken | Packrivo` · `Magazin · 333 Artikel` · `7 Min. Lesezeit · 26. Okt`，`/fr/` eyebrow `Le Journal · 333 articles`，`/ja/` `読了 7分 · 2026年10月26日`，`/ar/ /zh-CN/ /zh-TW/` 等同样为译文；en 对照行正常。
- 博客空态：11 非英语种 `/blog/?topic=zz-nonexistent` HTML 复核（`evidence/i18n-blog-29x/empty-html-29x/empty-html-29x.json` + 11 份 `*.blog.empty.html`）11/11 `localizedEmptyState=true / hasEnglishFallback=false / cardCount=0 / ok=true`；en 对照显示英文空态文案（`i828-browser.json` 中 `emptyState:"EN-empty-state"`）。

## 7. 重新打包与哈希

- `deploy/packrivo-home-v4.zip`：44 条目（38 文件 + 6 目录项），796,514 B，SHA256 `49ddbe6f1848419d2add0d801a0bf41817dbbab891b4747bcd629fdfc3a0599e`。
- 安装后 `assets/site.css`：35199 B，SHA256 `A5EF20760FD2F50497F657AACABB6C0B7BD0DC6C8B4F4A805806BF82C79C7E4E`；zip 内条目、`packrivo-wp` mirror 树、演练安装后三处逐字节一致。
- 与本轮之前那版（`a0e82203…` / 796,199 B）相比，文件集合不变（38/38），只有 `page.php`、`assets/site.css` 内容变化——即第 2 节里的宽表包裹、窄屏裁剪、非原生页左对齐三项。
- RUNBOOK 表（第 145 行）与第 5 步哈希注释（第 481 行）已同步成新哈希；`doc` 里其它历史哈希未改。

## 8. 演练环境完整跑一轮

只换子主题包（其余步骤逐字照 RUNBOOK），两段执行：

段 1（`2026-10-29T20:42:27Z` → `21:09:35Z`）：复位 → 基线快照 → 正向 0–8。

| 步 | 判据 | 结果 |
|---|---|---|
| step0 | 10 | PASS=10 FAIL=0 |
| step1 | 10 | PASS=10 FAIL=0 |
| step2 | 8 | PASS=8 FAIL=0 |
| step3 | 11 | PASS=11 FAIL=0 |
| step4 | 3 | PASS=3 FAIL=0 |
| step45 | 5 | PASS=5 FAIL=0 |
| step5 | 4 | PASS=4 FAIL=0 |
| step46 | 9 | PASS=9 FAIL=0 |
| step6 | 12 | PASS=12 FAIL=0 |
| step7 | 1 | PASS=1 FAIL=0 |
| step8 | 9 | PASS=9 FAIL=0 |

段 2（`21:22:55Z` → `21:34:47Z` 结束，容器 EXIT=0）：第 9 步回滚 → 3.0 守卫反向自检。

- step9：PASS=11 FAIL=0（含 9.3 两份 rollback JSON 的 site 校验、9a before-mu 全 OK、9b 主题回父主题、9c 子主题目录已删、9d 报价插件已移除、9e 首页 200、9f `/get-a-quote/` 回 301、9g 报价页计数 0、9h 页面 8 模板 meta 已还原）。
- 快照：基线 `01c1c448743c7d797d4925e3af0903e1805391bbb431159062b278ca846599b7`（ext `91db54e0…`）；正向 `8a011aea9cd43d6bd5aeeae4c03e5f5a6d263c7c425e97c585d6b332499f4b5b`（ext `a2c2e2bd…`）；**回滚后 = 基线（逐字节一致）**；反向自检后仍 = 基线。
- 3.0 反向自检：往 `/pkg/rollback/` 塞一个 `site=http://wrong-site.invalid` 的 JSON，守卫打印"非本轮产物…先清空 /pkg/rollback"，整步非零退出、apply 未执行、站点未变（negtest 退出码 0）。
- 正向与基线的 ext 差异只有预期项：`packrivo-i18n.php`、`packrivo-site-remediation-20261022.php` 两个 mu 更新，11 份 dict + 11 份 js + `paths.json` 合并，主题切到 `packrivo-home-v4 4.0.2`，报价插件上装，页面 7010 `get-a-quote` 新建，页面 8 `page_template=template-products-v4.php`。
- 本轮日志与快照已归档到 `deploy/verification-29x/logs/round4/`（含 `step9.log`、`negtest.log`、`negtest-step3.log`、正向/回滚/反向自检三组快照，以及 `run29x-rb.log` 完整尾部：`29x 段2 结束 2026-10-29T21:34:47Z` + `EXIT=0`）。

## 9. 浏览器判据（真实 Chrome + 本地代理，Host 原样带给 8091）

`verify: node deploy/verification-29x/scripts/29x-browser.mjs deploy/verification-29x/evidence`

- 原生页 20 × 4 宽度 = 80 条：**溢出 FAIL 0、左边距(<16px) FAIL 0、1440 与 logo 对齐(>4px) FAIL 0**。
- 博客 `/blog/` 375/768/1024/1440：**4 档均无横向溢出**（`scrollWidth == innerWidth`、越界元素 0），**每档 12 张卡片、12 张都带封面 `<img src>`**。
- 首页 `/`、产品页 `/products/`（`page-template-template-products-v4` 生效、H1 `Every structure we can source`）、`/get-a-quote/`：均 200、无溢出、模板未受影响。
- 20 张 1440 截图：`deploy/verification-29x/evidence/screens/`；判定原始数据：`deploy/verification-29x/evidence/rehearsal-29x-browser.json`。
- 一条环境事实（不是产品缺陷）：演练站的 `wp-content/uploads` 只带了 27 个文件，193 张精选图原图不在库里，卡片 `<img>` 指向真实 URL 但演练环境 404（`cardsDecoded=0`）。已核实同一 URL 在生产 `https://www.packrivo.com/wp-content/uploads/2026/10/pulp-cover-1536-a514ea0682cd-1024x683.webp` 返回 200 / image/webp / 50,000 B。所以判据取"卡片是否输出封面 src"，不以本机能否解码为准。

## 10. 复现命令

```bash
# 打包（保留目录项约定）
python E:/破甲工作区/.tmp/29x/repack-keep-dirs.py
# 演练段 1 / 段 2
docker run -d --name r29x-runner --network r28q-net \
  -v "E:/破甲工作区/.tmp/28q/rehearsal/home/web:/home/web" \
  -v "E:/破甲工作区/packrivo-backup/deploy/verification-29x/scripts:/opt/x29x:ro" \
  -v "/var/run/docker.sock:/var/run/docker.sock" --entrypoint bash r28q-host:11 /opt/x29x/runner-entry.sh fwd
docker run -d --name r29x-runner ... /opt/x29x/runner-entry.sh rb
# 浏览器判据
node deploy/verification-29x/scripts/29x-browser.mjs deploy/verification-29x/evidence
```

## 11. 本轮产物清单

```
deploy/packrivo-home-v4.zip                                   49ddbe6f… / 796,514 B
deploy/RUNBOOK-launch.md                                      第 145、481 行同步新哈希
packrivo-wp/packrivo-home-v4/{page.php,assets/site.css}        与 zip 内容一致（mirror）
deploy/verification-29x/scripts/29x-browser.mjs               浏览器判据脚本
deploy/verification-29x/scripts/29x-*.sh  reset/snapshot/rehearsal/run     演练驱动
deploy/verification-29x/evidence/screens/*-1440.png           20 张
deploy/verification-29x/evidence/rehearsal-29x-browser.json    判定原始数据
deploy/verification-29x/logs/round4/                          本轮 20 份日志 + 3 份快照
deploy/verification-29x/csv/                                 3 份 CSV + 核验结论
deploy/verification-29x/hero-candidates/                      9 张图 + sources.csv + 说明
```

## 12. 边界与未做

- 生产环境一个字节未动：没有 SSH、没碰密码、没打开 `.cf.env`；`#56` 与 Task 55 仍 paused。
- hero 候选只交付图片与来源，**未替换**首页大图，也未改 `front-page.php`。
- 未写数据库：141 篇未分类的映射只出 CSV；fictional 清单只出 CSV，文章正文未改。
- 演练站 uploads 缺精选图原图这一条，属演练环境资产不全，不是主题问题（生产同 URL 200）。
