# 融合待办（INTEGRATION BACKLOG）· 修订版

依据：本地真站副本 `http://localhost:8080`（docker 栈 `packrivo-live`）+ 子主题 `packrivo-home-v4` + 插件 `packrivo-quote` 实测。
**本版已按 `docs/REVIEW-FACTS.md` 的事实核查逐条修正**，凡标注「未验证」的，不要在施工时当结论用。

## 口径（先统一，之前文档三处互相打架）

| 项 | 数值 | 来源 |
|---|---|---|
| 已发布页面 | **392** | `SELECT COUNT(*) FROM wp_posts WHERE post_type='page' AND post_status='publish'` |
| 已发布文章 | **333** | 同上，`post_type='post'` |
| 产品页 | **134** | `meta_key='_packrivo_remediation_product'` |
| 分类 | 6 个：未分类 141 / Materials & print 82 / Supplier briefs 73 / Samples & approval 64 / Structures & fit 40 / Shipping & rules 34 | `wp_term_taxonomy` |

> 旧文档里的「149 页面 / 315 博客 / 11 语种 × 6,169 译文」来自线上 sitemap 与字典文件的口径，和本地库对不上；6169 在盘上各份字典里是 6171–6175，**没有一个文件等于 6169**。写客案时以库为准。

## P0（上线阻塞）

| # | 项 | 影响面 | 现状 | 改法 | 工时 | 风险 |
|---|---|---|---|---|---|---|
| 1 | 产品详情页模板 | 134 页 | 未接 | 新建 `page-product.php`，按 `_packrivo_remediation_product` 判定；`_packrivo_description` 覆盖 134/134，封面 `_packrivo_article_cover_url` **只有 100/134**，其余 34 页要有降级图 | 8–10 h | 中 |
| 2 | `/blog/` 接管风险 | 333 篇入口 | 本地已生效，线上**未验证** | 线上有 10 份 `own_production()` 副本，条件是 `DB_NAME='www_packrivo_com'` + `ABSPATH` + `WP_ENV=production` + `home_url()===site_url()===PRODUCTION_HOME`。**没有 `template_include` 钩子，所以"一定被覆盖"不成立**；必须在服务器上实测 `/blog/` 是否命中我们的 `home.php` 再决定要不要加开关 | 0.5 h 验证 + 1 h 处置 | 高（未验证） |
| 3 | 报价入口落位 | 1 页 | 只在 `/quote-test/` | 目标页不是 `/get-a-quote/`：它 301 到 **`/inquiry/`**（`packrivo-site-remediation-20261022.php` 的 `$legacy` 列表）。应把短代码放进 `/inquiry/`，或新建独立报价页再改导航 | 2 h | 中 |
| 4 | uploads 缺失 | 全站图片 | 本地未拉（1.3 GB） | 上线前必须确认 `/wp-content/uploads` 可写且已同步；本地预览的空白缩略图就是它 | 1 h | **高（阻塞）** |

## P1（转化与一致性）

| # | 项 | 影响面 | 现状 | 改法 | 工时 |
|---|---|---|---|---|---|
| 5 | 首页品类卡 | 5 张 | 硬编码（`front-page.php:10` 的 `$categories`） | 改用 `get_page_by_path()` 取 5 个分类页，封面取 `_packrivo_article_cover_url`，计数查子页 | 3 h |
| 6 | 页脚 | 全站 | 父主题 | 子主题 `footer.php` 拼 CTA 带 + `packrivo_shared_footer()` | 3 h |
| 7 | 结构化数据去重 | 全站 | 重复 | 首页只留 FAQPage，Organization 交给 Yoast（父主题另有 `_packrivo_json_ld`） | 2 h |
| 8 | 11 语种接线 | 全站 | 未接 | `packrivo-handoff/data/dict/*.json` **每语种只有 116 个键**（不是 6,169），可先接这 116 个 chrome 文案；整站 6,000+ 条的映射是另一个量级的项目 | 6 h（116 键） |

## P2（体验）

| # | 项 | 现状 | 改法 | 工时 |
|---|---|---|---|---|
| 9 | 产品图风格 | 等轴测概念图 vs 影棚实拍两套 | 统一，卡片用现有概念图 | 4 h |
| 10 | 搜索 / 404 | 父主题旧模板 | 补 `search.php`、`404.php` | 3 h |
| 11 | 报价器文案 | 英文硬编码在 `pkq-quote.js` | 走 `wp_localize` 注入 | 2 h |

## 术语纠正（之前文档里写了不存在的东西）

- **`pkv4_skip`、`pkv4_t()` 在主题和 mu-plugins 全文 0 命中** —— 那是我的命名，不是现成开关。要加就得自己写。
- `/get-a-quote/` 不是页面，是 301 跳 `/inquiry/`。

## 排期（总工时 35–39 h，排 6 天）

```
D1   #4 uploads 同步 + #2 线上验证 /blog/
D2-3 #1 产品详情页（134 页）
D4   #3 报价入口 + #5 首页品类卡
D5   #6 页脚 + #7 schema 去重
D6   #8 116 键语种接线；#9/#10/#11 视时间顺延
```

## 验收口径

- 页面改动：`curl` 命中 `pk-header` / `pk-filterbar`，0 pageerror
- 产品详情：134 页抽 10 页，标题、价格区间、正文都在
- 报价：`$2.01`（JS 运行时才算得出来，curl 只能拿到 `<div class="pkq-root">`）
- 定价一致性：`node parity-check.mjs` 偏差 0
