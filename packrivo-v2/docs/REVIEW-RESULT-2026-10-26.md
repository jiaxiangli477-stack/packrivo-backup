# 审核结论 · 2026-10-26（Claude）

对象：`packrivo-home-v4` 子主题 + `packrivo-quote` 插件，环境 `packrivo-live` docker（8080），对照 `packrivo-v2`（3101）。
两个 zip 与源码目录逐文件一致（插件源码多一个 `demo\`，未打包，正常）。

## P0 —— 上线前必须修

【P0】header.php:13（及 functions.php 无 `title-tag`）—— 除 382 个 native 页外，所有页面没有 `<title>` 和 meta description：/products/、/blog/、全部 333 篇文章、/quote-test/ 都是空标题 —— `curl -sL "http://localhost:8080/?p=2938" | grep -c "<title"` 输出 0；/products/ 的 `document.title === ""` —— 子主题 `add_theme_support('title-tag')`，再用 `pre_get_document_title` 过滤：native 页面返回空（父主题 `packrivo_output_page_metadata()` 已输出标题，避免重复），其余页面用 `wp_get_document_title()`；文章/索引页补 `<meta name="description">`（文章用摘要，索引页写固定文案）。

【P0】page.php:18-27 —— 只剥了旧 header，没剥旧 `<footer>`，382 个 native 页面出现两个页脚 —— 打开 http://localhost:8080/product-magnetic-jewelry-box/ ，`document.querySelectorAll('footer').length === 2`；DB 统计 382/382 个 native 页含 `<footer` —— 旧页脚无 class，格式为 `<footer>\n<div class="footer-top">…</footer>`，在正则数组里加 `~<footer\b[^>]*>\s*<div class="footer-top">[\s\S]*?</footer>~i`；同时对 `preg_replace` 返回 `null` 做兜底（回退原内容），最大页面 262 KB。

【P0】front-page.php:65 + 整站 —— 新的五步报价器没有接入任何正式入口：首页 "Build a quote" 指向 `/get-a-quote/`，它 301 到旧的 `/inquiry/` 表单；`[packrivo_quote]` 只出现在 `/quote-test/` —— `curl -s -o /dev/null -w "%{redirect_url}" http://localhost:8080/get-a-quote/` → `/inquiry/` —— 决定一个正式 URL（建议就用 `/get-a-quote/`），去掉 301，页面内容放短代码；导航 "Instant quote" 同步指过去。

【P0】pkq-quote.js:42 + class-pkq-pricing.php:207 —— Poly mailer 的预设尺寸宽度是 1 cm，服务端要求 ≥2，**所有 Poly mailer 订单提交都是 422** —— `POST /wp-json/packrivo/v1/price` 带有效 nonce、`{"style":"poly",...,"width":1}` → `pkq_dims 422` —— 前后端统一尺寸规则：袋类/贴纸的"厚度"维度允许 0.05–120，长宽 2–120；JS `dim()` 按维度分别夹取，并在提交前把夹取后的值写回 payload（现在提交的是未夹取的 `state.width`）。

【P0】packrivo-quote.php:119 + class-pkq-rest.php:32 —— 已登录用户（你自己测试时）提交必定 403；带页面缓存时访客也会过期 403 —— 原因：WP 核心 `rest_cookie_check_errors()` 在没有 `X-WP-Nonce` 时把当前用户置 0（wp-includes/rest-api.php:1120），而 `pkq_order` nonce 是按登录用户生成的；另外 nonce 有效期 12–24 h，Cloudflare/页面缓存若缓存了 /get-a-quote/ 会把过期 nonce 发给所有人 —— 前端同时发 `X-WP-Nonce: wp_create_nonce('wp_rest')`；nonce 改为在挂载时用一个不缓存的 GET 端点获取（或对报价页设 `Cache-Control: no-store` 并在 CF 排除该路径）。「登录态 403」依据核心代码推断，未在浏览器登录实测。

## P1 —— 上线前应修

【P1】front-page.php:76-79 —— 首页英雄卡写死 "Rigid magnetic box 9.5×9.5×4 cm · $0.54 @1,000"，同一配置报价引擎算出 $2.34（rigidboard）/ $1.58（kraft）；FAQ 第 51 行 "$0.27 small rigid jewelry box" 同样与引擎矛盾 —— 访客首页看到 $0.54，进配置器看到 $2.34，信任直接崩 —— 英雄卡改为调用 `PKQ_Pricing::price()` 计算并展示，FAQ 价格改成引用阶梯表或去掉具体数字。

【P1】template-products-v4.php:106 —— 内联 `grid-template-columns:repeat(4,…)` 压过 site.css:521/586/593 的媒体查询，手机上 4 列每列 70 px，页面横向溢出（scrollWidth 451 > 375）—— 375 宽打开 /products/ —— 删掉内联样式，用 class 控制列数。

【P1】template-products-v4.php:14 —— `ring` 子串匹配把 `product-drawstring-fabric-gift-bag` 归进了 Jewelry；审核包"?group=jewelry 应出 8 张卡"的预期本身是错的，真实首饰只有 7 个 —— 改为按 `-` 切词后整词匹配，或给 134 个产品页写一个 `_packrivo_group` meta 显式分组。

【P1】template-products-v4.php:13-20 —— 134 个产品里 20 个不属于任何分组，只能在 "All" 下看到；34 个没有封面图，卡片高度不齐 —— 补分组规则/meta；无图卡片给统一占位图。

【P1】pkq-quote.js:12 / pricing.json:78 —— 配置器材料说明写 "BPI certified"、"FSC Recycled credit"。作为代采商，这是供应商的认证，需要有对应工厂证书才能写 —— 拿不到证书就改成 "compostable stock (certificate on request)" 这类表述。

【P1】class-pkq-rest.php:214 —— 订单通知依赖 `wp_mail`，而线上 FluentSMTP 未配置（见交接文档）。订单能入库但很可能收不到邮件 —— 上线前先配 SMTP 并发一单实测；未验证。

## P2

【P2】pkq-quote.js:62 —— `sizeFactor()` 写死参考尺寸 30×20×10，没读 `pricing.json` 的 `sizeRef`；setup 门槛 2500/5000 在 JS:85 和 PHP:221 都写死，没读 `rules.freePrintSetupFrom/freeDieFrom` —— 改源头会静默漂移。
【P2】class-pkq-pricing.php:62-79 —— 四表齐验只验表存在，不验行：某行缺 `factor` 会变成 0 → 该材料价格归零 —— 逐行校验，非数字则整体回退。
【P2】class-pkq-pricing.php:55 —— 回退到常量表时没有任何提示 —— 加 `error_log` 或后台 admin notice。
【P2】配置器 —— 材料列表不随款式过滤：贴纸、袋子也能选 "Kraft E-flute"、"Double-wall corrugated"。
【P2】class-pkq-rest.php:216 —— 若开启 `notify_customer`，任何人可让站点给任意邮箱发信（每 IP 20 次/小时），可被滥用 —— 保持关闭或只在人工确认后发送。
【P2】生成链 —— tokens.css 存在两个生成器（`packrivo-v2/scripts/tokens.mjs` 与 `packrivo-shared/build-shared.mjs`），子主题里的是前者的产物，与 `packrivo-shared/tokens.css` 头部不同 —— 删掉一个，否则"单一来源"不成立。
【P2】/price 端点前端没有调用，可删掉以缩小攻击面。

## 通过（已验证）

- check_nonce()：无 nonce、伪造 nonce 均返回 403 `pkq_nonce`；有效 nonce 正常进入定价（匿名访客）。
- `pkq_order` 为 `public => false`，`create_posts => do_not_allow`，订单不会出现在前台。
- `client_ip()` 默认不信任 XFF，限流不会被伪造头绕过。
- 产品索引：分组筛选确实在 WP_Query 之前（`post__in`），分页正确；134 个产品全部有 `_packrivo_description`。
- 首页阶梯表走 `PKQ_Pricing::ladder()`，品类卡走 `get_page_by_path()`。
- 新导航、单一 `<main>`、单一 `<header>` 在 native 页面上成立。
- v2 里的虚构内容（FSC C168842、ISO 9001、SEDEX、4 个客户案例、"our own presses"）没有带进 WP 交付物。

## 关于 3101（packrivo-v2）产品页 —— 该借什么、不能借什么

**值得搬进 WP 产品索引的交互**：每张卡直接显示"from $x / unit at 2,500"、MOQ、交期标签；搜索框；按价格排序；按行业（Ecommerce/Retail/Luxury/Food/Branding）筛选；卡片上的 Configure 按钮直达报价器并预选款式。价格要由 `PKQ_Pricing::price()` 实时算，不要写死。

**不能搬的内容**（与"不造假"原则冲突，v2 上线前也要删）：
- 页脚 `FSC® C168842 / ISO 9001 / BPI / SEDEX member`（components/SiteFooter.tsx:52）
- 四个客户案例 Aurea / Northwind / Loop Athletics / Sable & Stone（data/case-studies.ts）
- "Every structure we actually run" / "formats on our own presses"（app/products/page.tsx:21-25）—— Packrivo 是代采，不是工厂
- "Ships in 10 days"（data/products.ts:41）等无依据的交期承诺
- 虚构的产品系列名（Ridge/Halo/Fold/Seal/Loop/Mark）—— 品牌化可以保留，但别暗示是自有产线
