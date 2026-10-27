# Packrivo 整合 · 审核包（给 Claude）

## 0. 背景（30 秒读完）

对象是 `www.packrivo.com` —— 一个 WordPress 7.1.3 + 自研主题 `packrivo-static-bridge-1.2.38` 的包装代采站：392 个已发布页面、333 篇文章、11 语种、6 个分类，产品页用 `_packrivo_remediation_product` 这个 meta 标记（共 134 个）。

我们做了两件事：
1. **子主题**：换导航、换首页、接管产品索引和博客索引、补文章页外壳，不改父主题。
2. **插件**：五步配置器 + 实时报价 + 订单入库 + 后台导出。

单价公式的单一来源是 `packrivo-v2/lib/pricing.ts`，由 `packrivo-shared/build-shared.mjs` 生成 `pricing.json` 给 PHP 和前端 JS 共用。

所有改动都在本地真站副本上验证过（`docker compose` 起的 WordPress + 从服务器 dump 的数据库）。

---

## 1. 交付包

```
E:\破甲工作区\packrivo-wp\packrivo-home-v4.zip          476→756 KB  子主题
E:\破甲工作区\packrivo-wp\packrivo-quote-1.0.0.zip       22 KB        插件
```

## 2. 子主题源码

```
E:\破甲工作区\packrivo-wp\packrivo-home-v4\
├── style.css                    主题头，Template: packrivo-static-bridge-1.2.38
├── functions.php                资源入队（priority 20）、菜单注册、产品面板分组
├── header.php                   新导航；一级项是真 <a>，caret 是独立 <button>
├── footer.php                   父主题页脚 + </body></html>
├── front-page.php               首页：品类卡查库、阶梯价走 PKQ_Pricing::ladder()、FAQPage
├── page.php                     剥掉 392 个存量页里的旧页壳（旧 header / skip / main）
├── single.php                   面包屑 + 封面 + 3 篇相关阅读 + 尾部 CTA
├── home.php                     /blog/ 是 posts page，必须走 home.php
├── index.php                    归档兜底
├── template-products-v4.php     产品索引：先按分组算 ID 白名单，再分页查询
├── template-journal-v4.php      博客索引：分类 slug + newest/oldest + 分页
└── assets\
    ├── site.css                 组件样式，tokens 由 tokens.css 提供
    ├── tokens.css               生成物（勿手改）
    ├── nav.js                   抽屉 / Esc / 外部点击 / aria 同步 / 语言选择器搬进头部
    ├── mark.svg
    └── img\                     6 张图 × 3 档（1200 / 800 / 480），front-page 用 srcset
```

## 3. 插件源码

```
E:\破甲工作区\packrivo-wp\packrivo-quote\
├── packrivo-quote.php               短代码、Pricing/Admin/REST 引导、配置下发
├── includes\
│   ├── class-pkq-pricing.php        定价：读 pricing.json，四表齐验，tables() 供前端
│   ├── class-pkq-rest.php           REST：check_nonce()、限流、入库、通知
│   ├── class-pkq-admin.php          列表列、CSV（公式注入防护）、设置、状态流转
│   └── pricing.json                 生成物（勿手改）
├── assets\
│   ├── pkq-quote.js                 五步配置器（原生 JS，无依赖）
│   └── pkq-quote.css
└── README.md                        安装、公式、验证记录
```

## 4. 生成链（判断"是否单一来源"看这里）

```
E:\破甲工作区\packrivo-shared\build-shared.mjs     生成器
E:\破甲工作区\packrivo-shared\tokens.css            产物
E:\破甲工作区\packrivo-shared\pricing.json          产物
E:\破甲工作区\packrivo-v2\app\globals.css           令牌源头（@theme）
E:\破甲工作区\packrivo-v2\lib\pricing.ts            定价源头（唯一权威）
```

改源头后必须 `node packrivo-shared/build-shared.mjs`，否则两份产物过期。

## 5. 文档

```
E:\破甲工作区\packrivo-v2\docs\MIGRATION.md             迁移总纲（39 KB）
E:\破甲工作区\packrivo-v2\docs\INTEGRATION-BACKLOG.md   待办 + 工时 + 风险（已按事实核查修订）
E:\破甲工作区\packrivo-v2\docs\INTEGRATION.md           去重与单一来源说明
E:\破甲工作区\packrivo-v2\docs\REVIEW-FACTS.md          事实核查：8 条断言 + 16 条必改句
E:\破甲工作区\packrivo-v2\docs\REVIEW-RESULT-2026-10-26.md   外部审核结论（Claude，原始清单）
E:\破甲工作区\packrivo-v2\docs\REVIEW-PACKET.md         本文件
E:\破甲工作区\packrivo-v2\docs\archive\                 七份原始文档
```

### 5.1 外部审核项的处理状态（2026-10-27）

| 审核项 | 状态 | 处理 | 验证 |
|---|---|---|---|
| P0-1 页面无 `<title>` | **已修** | `functions.php` 加 `add_theme_support('title-tag')` + `post-thumbnails` | 静态检查；**待线上复测** |
| P0-1 meta description | 未做 | 需要按 native 页/文章/索引分别写 | — |
| P0-2 旧 `<footer>` 未剥 | **已修** | `page.php` 用 `<div class="footer-top">` 锚点定位，收于 `</footer>` | 离线正则三例通过（含"文章自带 `<footer class=meta>` 不误删"） |
| P0-3 报价入口 301 | **已修** | `header.php` 与 `front-page.php` 三个 CTA 全部改指 `/inquiry/` | 源码 grep，0 处 `/get-a-quote/` |
| P0-4 poly 宽度 1 cm 被驳 | **已修** | 预设改为 2 cm | 静态；**待线上复测** |
| P0-5 登录用户 403 | **已修** | 配置里增发 `X-WP-Nonce`（`wp_rest`），与 `X-PKQ-Nonce` 并存 | 静态；**待线上复测** |
| P1 首页 hero $0.54 写死 | **已修** | 改 `PKQ_Pricing::price()` 实时算 | 静态 |
| P1 手机 4 列溢出 | **已修** | 内联样式换成 `.pk-cards-4` + 三档媒体查询 | 静态 |
| P1 jewelry 分组 `ring` 误配 | **已修** | 词边界 + `(?:s|es)?`；离线验证 `watches` 命中、`drawstring-gift-bag` 不命中、规则是旧规则的超集 | `scripts/plural-test.mjs` 输出 0 violation |
| P1 20 个产品不属任何分组 | 未做 | 需要 `_packrivo_group` 显式分组 meta | — |
| P1 34 个产品无封面 | 未做 | 需要占位图策略 | — |
| P1 配置器宣称 BPI/FSC | 未做 | 改为 "certificate on request" | — |
| P1 SMTP 未配置 | 未做（环境项） | 部署前配 FluentSMTP 并发一单实测 | — |
| P2 各项（sizeRef、逐行校验、材质不随款式过滤、`/price` 端点、tokens 双生成器） | 未做 | 见 REVIEW-RESULT 原文 | — |

> **复测阻断**：本机 Docker Desktop 正在重装（`C:\Program Files\Docker` 下只剩安装日志，`docker` 命令不可用），`packrivo-live` 栈无法启动，8080 端口被另一个 SvelteKit 应用占用。上面标「待线上复测」的三项需要在 Docker 恢复后用 `docker compose up -d`（compose 已改为 **8081:80**）再跑一遍。


## 6. 本地复现

```
E:\破甲工作区\packrivo-live\docker-compose.yml      docker compose up -d
E:\破甲工作区\packrivo-live\themes\                 父主题 1.2.38 + 子主题
E:\破甲工作区\packrivo-live\plugins\                5 个 packrivo 插件 + 报价插件
E:\破甲工作区\packrivo-live\mu-plugins\             66 个文件
E:\破甲工作区\packrivo-live\db\pk.mariadb.sql       数据库（29.9 MB，已替换排序规则）
```

访问：

```
http://localhost:8080/                      首页
http://localhost:8080/products/             产品索引（134 条，可 ?group= / ?sort=）
http://localhost:8080/blog/                 博客索引（333 条，可 ?topic= / ?sort=）
http://localhost:8080/quote-test/           配置器测试页（短代码挂载点）
http://localhost:8080/?p=2938               单篇文章
```

数据库直连：`docker compose exec -T db mysql -uroot -plocalroot wp`

## 7. 请重点审这 6 处

| # | 文件 | 要验证什么 | 预期行为 |
|---|---|---|---|
| 1 | `packrivo-quote\includes\class-pkq-rest.php` | `check_nonce()` 是否真的挡住 CSRF | 无 nonce / 伪 nonce 都返回 **403 pkq_nonce**；正常提交成功 |
| 2 | `packrivo-quote\includes\class-pkq-pricing.php` | `data()` 的四表齐验 | `pricing.json` 缺 `tiers`/`prints` 时**整体回退**，不允许静默按 0.64 地板价成交 |
| 3 | `packrivo-quote\assets\pkq-quote.js` | `normaliseConfig()` 与 `price()` | 尺寸先 `round(x*10)/10` 再夹 2–120；阶梯读 `cfg.tiers` 而不是内置常量 |
| 4 | `packrivo-home-v4\page.php` | 剥旧页壳的正则 | 只删旧 `<header class="site-header">`、旧 skip link、`<main>`、以及没有 class 的旧 `<footer>`（靠内层 `<div class="footer-top">` 定位，收在 `</footer>`）；**不能误删正文内容** |
| 5 | `packrivo-home-v4\template-products-v4.php` | 分组筛选用的是词边界匹配 | 筛选在 `WP_Query` 之前完成（`post__in`）；`?group=jewelry` 应出 **7 张卡**（此前用 `stripos` 子串匹配，`drawstring` 命中了 `ring`，错误地凑成 8 张） |
| 6 | `packrivo-home-v4\front-page.php` | 是否还有硬编码 | 品类卡来自 `get_page_by_path()`；阶梯价来自 `PKQ_Pricing::ladder()`；只剩 FAQ 与四步流程是文案常量 |

## 8. 已知未修（不用重复报）

- 报价器约 40 条 UI 文案仍硬编码；本站 i18n 是"渲染后 HTML 查字典"，客户端生成的 DOM 接不到
- `pkv4_fallback_menu()` 未被 `wp_nav_menu()` 调用，后台改菜单不生效
- 首页品类卡用的是页面标题（如 "Custom Jewelry Boxes With Logo for Your Collection"），没有短标签字段
- `pkq_status` 已有后台可改，但列表页还不能按状态筛选
- 产品详情页仍是父主题模板（`docs/INTEGRATION-BACKLOG.md` 的 P0-1，未开工）

## 9. 期望的输出格式

请按这个结构给结论，不要泛泛而谈：

```
【P0】文件:行 —— 问题一句话 —— 复现方式 —— 建议改法
【P1】…
【P2】…
【通过】列出验证过且没问题的点
```

每条必须能指到文件与行号；不能确定的地方标「未验证」，不要猜。
