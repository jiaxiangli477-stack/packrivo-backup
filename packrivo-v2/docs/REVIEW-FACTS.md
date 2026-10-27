# REVIEW-FACTS · INTEGRATION-BACKLOG.md / MIGRATION.md 事实核查

核查时间：2026-10-27（本地时钟 01:34 UTC+8 前后）
核查对象：`docs/INTEGRATION-BACKLOG.md`、`docs/MIGRATION.md`
核查面：本地真站 `http://localhost:8080`（docker 栈 `packrivo-live`，db=healthy）+ MariaDB 10.11 + 主题源码 + mu-plugin 源码 + 字典文件
核查方式：只读。命令全部实跑，输出为原始片段（仅做换行压平）。

## 结论速览

| 断言 | 结果 | 证据 |
|---|---|---|
| 1. 产品页 134 个（`_packrivo_remediation_product`） | **PASS** | `meta_key` 命中 134，且全部是 `publish` / `page`，无别的 post_type 混入 |
| 2. 文章 333 篇 | **PASS** | `post_type=post AND post_status=publish` = 333；`/blog/` 页头渲染 `Journal · 333 articles` |
| 2b. 分类 6 个 | **PASS** | `wp_term_taxonomy WHERE taxonomy='category'` = 6 行 |
| 2c. 分类计数与文档一致 | **FAIL** | MIGRATION 写五行 96/127/110/101/47；库里是六行 141/82/64/40/34/73，且第一行是 `未分类` |
| 3. `/blog/` 是文章页（`page_for_posts=53`）、页面模板无效、必须 `home.php` | **PASS** | `page_for_posts=53`；page 53 挂着 `_wp_page_template=template-journal-v4.php`；实际输出的 `<body class>` 为 `blog pk-body …`，**不含** `page-id-53` / `page-template-` |
| 4. `/quote-test/` 报价显示 `$2.01`（`.pkq-price`） | **PASS**（仅 JS 运行时可得） | headless Chrome 渲染后 `class="pkq-price">$2.01<`、`per unit · 1,000 units`；纯 `curl` 抓不到该节点 |
| 5. 首页品类卡硬编码在 `front-page.php` 的 `$categories` | **PASS** | `themes/packrivo-home-v4/front-page.php:10` `$categories = [`，五条字面量，无 `get_page_by_path` |
| 6. `own_production()` “三个条件：ABSPATH / WP_ENV / home_url+site_url” | **需修正** | 实际是 **4 组**判定：`DB_NAME` + `ABSPATH` + `wp_get_environment_type()` + `home_url()`&`site_url()`；不存在 `WP_ENV` 常量 |
| 6b. 该 mu-plugin “上线后会接管 `/blog/` 输出” | **FAIL** | 全 mu-plugins 无 `template_include`；`is_home()` 只出现在 `normal_public_view()` 判定里；插件用 `if (!own_production()) { return; }` 整体退出 |
| 7. 149 页面 | **FAIL**（对库） | `wp_posts` publish page = **392**；本地 `wp-sitemap-posts-page-1.xml` = 392；`page-sitemap.xml`（Yoast）本地 404 |
| 7b. 315 博客 | **FAIL**（对库） | publish post = **333**；本地 `wp-sitemap-posts-post-1.xml` = 333 |
| 7c. 11 语种 | **PASS** | `mu-plugins/packrivo-i18n.php:26-38` 恰 11 项；11 个 `/{lang}/` 本地全 404（字典目录未拉） |
| 7d. 6,169 键译文 | **需修正** | 盘上 `i18n/live`、`i18n/merged`、`i18n/dict3` 每语种 distinct **6171–6175**，取不到 6169；而 BACKLOG 点名的 `packrivo-handoff/data/dict/*.json` 每语种只有 **116–130** 键 |
| 8. BACKLOG 头部 “pages 391” | **FAIL** | 库内 392；391 = 392 − `/blog/` 页，未说明口径 |
| 8b. 工时/优先级自相矛盾 | **矛盾 3 处**（见下） | 执行顺序漏排 #8/#9/#11 共 8h；“不动译文”与 #6 键映射冲突；#11 是上线阻塞却列 P2 |
| 附加 A. #1 复用 `_packrivo_article_cover_url` | **需修正** | 134 个产品页里只有 **100** 个有该 meta，34 个没有 |
| 附加 B. #3 `/get-a-quote/` 可“替换该页内容” | **FAIL** | `/get-a-quote/` 返回 **301 → /inquiry/**；库内无该 slug 的 page |
| 附加 C. #2 的 `pkv4_skip`、#6 的 `pkv4_t()` | **FAIL** | 主题与 mu-plugins 全文检索 0 命中，函数/开关不存在 |

---

## 逐条证据

### 1) 产品页 134

```
$ cd E:\破甲工作区\packrivo-live
$ docker compose exec -T db mysql -uroot -plocalroot -N -e \
  "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_packrivo_remediation_product';
   SELECT COUNT(DISTINCT pm.post_id) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
     WHERE pm.meta_key='_packrivo_remediation_product' AND p.post_type='page' AND p.post_status='publish';
   SELECT p.post_type,p.post_status,COUNT(DISTINCT p.ID) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
     WHERE pm.meta_key='_packrivo_remediation_product' GROUP BY p.post_type,p.post_status;" wp
134
134
page	publish	134
```
抽样 URL：`curl -o NUL -w "%{http_code}" /product-magnetic-jewelry-box/` → `200`。

### 2) 文章 / 分类

```
docker compose exec -T db mysql -uroot -plocalroot -N -e "SELECT COUNT(*) FROM wp_posts WHERE post_type='post' AND post_status='publish';" wp
333

docker compose exec -T db mysql -uroot -plocalroot -N -e "SELECT t.term_id,t.name,t.slug,tt.count FROM wp_terms t JOIN wp_term_taxonomy tt ON tt.term_id=t.term_id WHERE tt.taxonomy='category' ORDER BY tt.term_id;" wp
1	未分类	uncategorized	141
9	Materials &amp; print	materials-print	82
10	Samples &amp; approval	samples-approval	64
11	Structures &amp; fit	structures-fit	40
12	Shipping &amp; rules	shipping-rules	34
13	Supplier briefs	supplier-briefs	73
```
`tt.count` 与真实关系表一致（`wp_term_relationships` 聚合结果同上）。`/blog/` 渲染的筛选条同样是这六行：

```
All 333 / 未分类 141 / Materials & print 82 / Supplier briefs 73 / Samples &amp; approval 64 /
Structures &amp; fit 40 / Shipping &amp; rules 34
```
→ 数量本身自洽；**与 MIGRATION 写的 96/127/110/101/47 完全不是一套数**。

### 3) `/blog/` 的文章页身份与模板

```
docker compose exec -T db mysql -uroot -plocalroot -N -e "SELECT option_name,option_value FROM wp_options WHERE option_name IN ('show_on_front','page_on_front','page_for_posts');" wp
show_on_front	page
page_on_front	7
page_for_posts	53

docker compose exec -T db mysql -uroot -plocalroot -N -e "SELECT ID,post_type,post_status,post_name,post_title FROM wp_posts WHERE ID=53;" wp
53	page	publish	blog	Blog

docker compose exec -T db mysql -uroot -plocalroot -N -e "SELECT meta_key,meta_value FROM wp_postmeta WHERE post_id=53 AND meta_key='_wp_page_template';" wp
_wp_page_template	template-journal-v4.php
```
`themes/packrivo-home-v4/home.php` 全文：

```php
/**
 * Journal index for the posts page (/blog/).
 * WordPress renders the "posts page" through home.php, not through a page template,
 * so this file reuses the same markup as template-journal-v4.php.
 */
require get_stylesheet_directory() . '/template-journal-v4.php';
```
渲染证据（页面模板确实没参与）：

```
GET /blog/ → HTTP/1.1 200 OK
<body class="blog pk-body pr-page-pad has-contact-dock">
contains page-template- : False
contains page-id-53     : False
```
`page-template-*` / `page-id-*` 类缺失 = WP 走 `home.php`（`is_home()`），page 53 上挂的 `template-journal-v4.php` 是死配置。断言成立。

### 4) `/quote-test/` 的 `$2.01`

纯 curl 无价格（组件是 JS 渲染）：

```
GET /quote-test/ → 200，53643 bytes
class="[^"]*pkq[^"]*" 命中：class="pkq-root"
data-style="mailer" data-qty="1000"
$ 开头的价格串：0 个
```
headless Chrome（真实 JS 执行）后：

```
chrome --headless=new --virtual-time-budget=6000 --dump-dom http://localhost:8080/quote-test/
class="pkq-price">$2.01<
class="pkq-note">per unit · 1,000 units<
```
算法侧同解（`plugins/packrivo-quote/assets/pkq-quote.js:64-86` + `includes/pricing.json`）：
`(1.42×1×sizeFactor(30,22,12)) + 0.18 + 0.06) × 0.93 + (220+120)/1000 = 2.012…` → `$2.01`。
注：该断言只能在带 JS 的运行时验证，`curl | grep .pkq-price` 一定为空——文档若要复现须写明用浏览器。

### 5) 首页品类卡硬编码

```
themes/packrivo-home-v4/front-page.php
 10: $categories = [
 11:     ['Jewelry boxes', '/jewelry-boxes/', 'Rigid magnetic and drawer formats …', 'jewelry'],
 …
 15:     ['Inserts', '/inserts/', 'Molded pulp, EVA foam or folded board …', 'inserts'],
 16: ];
 70:       <?php foreach ($categories as [$name, $path, $blurb, $key]) : ?>
```
全文 `get_page_by_path` 0 命中。渲染出的 5 张卡：`/jewelry-boxes/ /foldable-gift-boxes/ /mailer-boxes/ /labels/ /inserts/`，图取自 `assets/img/{key}.webp`（主题内静态图，不是媒体库）。五个目标页在库里都是 publish page（ID 9/15/21/27/33）。

### 6) `own_production()` 的真实条件与文件

不是“一个 mu-plugin”，而是 **10 个文件各有一份同命名空间内的副本**（`namespace Packrivo\BlogXX\…`，所以不冲突）。代表文件：

```
mu-plugins/packrivo-blogc100-published-native-ba962f158b89386c.php
  5: if (!defined('ABSPATH')) { return; }
  6: const PRODUCTION_HOME='https://www.packrivo.com';
  7: const PRODUCTION_ROOT='/var/www/html/www.packrivo.com/wordpress/';
 74: function own_production(): bool {
 75:     return defined('DB_NAME') && \DB_NAME === 'www_packrivo_com'
 76:         && \ABSPATH === PRODUCTION_ROOT && function_exists('wp_get_environment_type')
 77:         && function_exists('home_url') && function_exists('site_url')
 78:         && \wp_get_environment_type() === 'production'
 79:         && \home_url('') === PRODUCTION_HOME && \site_url('') === PRODUCTION_HOME;
 80: }
309: if (!own_production()) { return; }
```
行号分布（10 份副本，全部 namespace 内，互不冲突）：`74-80` ×1（`packrivo-blogc100-published-native-…`）、`75-79` ×2（`packrivo-blog2530-published-native-…`、`packrivo-blog3170-published-native-…`）、`84-89` ×7（3 个 `*-institutional` + 2 个 `*-private` + `blog26-published-65c8…` + `blog26-published-native-…`）。
条件实为 4 组（`DB_NAME` 也是硬门槛），且判定用的是 `wp_get_environment_type()`——**不存在 `WP_ENV` 常量**。本地 `home_url()` = `http://localhost:8080` ≠ `https://www.packrivo.com`，因此第 309 行直接 return，全部钩子不注册。

“接管 `/blog/` 输出”不成立：这 10 个文件注册的钩子是

```
310 wpseo_add_opengraph_additional_images   311 wpseo_enhanced_slack_data
312 template_redirect(begin_owned_frontend_buffer)  313 the_author
314-321 wpseo_meta_author / opengraph_image* / twitter_image / wpseo_schema_graph
322 body_class  323-326 wp_head / wp_footer 样式与 TOC
```
没有任何 `template_include`；`is_home()` 只出现在 `normal_public_view()`（`c100…php:245`），作用是放行缓冲，不是换模板。

### 7) 149 / 315 / 11 × 6169

```
docker compose exec -T db mysql -uroot -plocalroot -N -e "SELECT post_type,post_status,COUNT(*) FROM wp_posts GROUP BY post_type,post_status;" wp
page	publish	392        (另 draft 19)
post	publish	333        (另 draft 12 / future 8)
attachment	inherit	334

wp-sitemap-posts-page-1.xml: url_count=392
wp-sitemap-posts-post-1.xml: url_count=333
curl page-sitemap.xml → 404；sitemap_index.xml → 404；wp-sitemap.xml → 200
wp_yoast_indexable: post/page 409，post/post 355
```
→ 本地副本（“真站副本”）的库与 sitemap 都是 392/333；149/315 在本地无任何来源可复现（Yoast sitemap 本地未启用）。可以解释为线上 sitemap 的旧计数，但文档没有标注该口径，且同族文档又写 391/333。

11 语种 PASS：`packrivo-i18n.php:26-38` 列出 `de fr es it nl pt ar ja ko zh-CN zh-TW`；11 个路由本地全 404，因为 `packrivo_i18n_ready()` 要求的 `uploads/packrivo-i18n/{lang}-dict.json` 本地不存在（`ls: cannot access '/var/www/html/wp-content/uploads/packrivo-i18n/'`），与 BACKLOG #11“上传目录未拉”一致。

6169 键 需修正。盘上可数的字典（node 解析，四张表 `title/attr/block/text` 的 distinct 键数）：

```
i18n/live     ar 6172  de 6173  es 6175  fr 6175  it 6175  ja 6172  ko 6172
              nl 6175  pt 6175  zh-CN 6173  zh-TW 6172
i18n/merged   ar 6171  de 6172  es 6174  fr 6174  it 6174  ja 6171  ko 6171
              nl 6174  pt 6174  zh-CN 6172  zh-TW 6171
i18n/dict3    ar 6171  de 6171  es 6171  fr 6171  it 6171  ja 6171  ko 6171
              nl 6171  pt 6171  zh-CN 6172  zh-TW 6171
packrivo-handoff/data/dict/*-js.json   每语种 116 键（单文件 12–15 KB，界面串字典，不含页面正文）
```
6,169 落在 6,171–6,175 之外 2–6 个键，没有哪个目录等于 6169；BACKLOG #6 点名的 `packrivo-handoff/data/dict/*.json` 更只有百位数键。

### 8) 工时与优先级的内部一致性

- 逻辑上“P0 + 1h + 高风险”不构成矛盾：风险标的是影响面/回滚难度，不是人时。#2 的 1h 是“加常量开关”的合理量级。**但**它建立在错误机制上（见 6b：并不存在“接管 `/blog/` 输出”），因此 1h 的估法本身站不住。
- 真矛盾 1：`执行顺序` 只排到 #1–#7、#10，**#8(4h)/#9(3h)/#11(1h) 完全没进 6 天**。各项工时合计 35–39h，排进日程的只有 27–31h，差 8h。
- 真矛盾 2：`明确不做` 写“不动 11 语种的译文内容（只接线）”，而 #6 要求“6,169 条键要映射、`pkv4_t()` 包装、缺键回退英文”——映射键就是改译文内容，二者不能同时成立。
- 真矛盾 3：#11“上传目录本地未拉（1.3 GB），缩略图空白”是**上线阻塞**（图片/缩略图全空），却列在 P2；与 P0 定义“不做就不能上线”冲突。

### 附加核查（文档里被引用但站上不存在的对象）

- `_packrivo_article_cover_url` 在 134 个产品页中只有 100 个：`SELECT meta_key,COUNT(*) … IN ('_packrivo_description','_packrivo_article_cover_url') GROUP BY meta_key` → `_packrivo_description 134`、`_packrivo_article_cover_url 100`。按 #1 的改法会有 34 页无封面。
- `/get-a-quote/` 不是页面：

```
curl -s -o NUL -D - http://localhost:8080/get-a-quote/ | head -1
HTTP/1.1 301 Moved Permanently
# 跟随后与 /inquiry/ 字节完全相同（88508），h1 = "Get a packaging quote."
# 库内 post_name LIKE '%quote%' 只有 inquiry(49)、quote-test(3220)
# 301 来源：mu-plugins/packrivo-site-remediation-20261022.php:52
#   $legacy = ['contact','contact-us','get-a-quote','get-quote','quote','rfq','enquiry','request-a-quote'];
#   wp_safe_redirect(home_url('/inquiry/'), 301, 'Packrivo');
```
- `pkv4_skip` / `pkv4_t`：`Select-String -Path themes\packrivo-home-v4\*.php,mu-plugins\*.php -Pattern 'pkv4_t|pkv4_skip'` → 0 命中。

---

## 文档必须改的具体句子

### `docs/INTEGRATION-BACKLOG.md`

1. 第 3 行
   现：“`（docker 栈 packrivo-live，pages 391 / posts 333 / 产品页 **134**）`”
   改：“`（docker 栈 packrivo-live，publish page 392（Yoast sitemap 未在本地启用）/ publish post 333 / 其中产品页 134）`”
   理由：库内 392；391 是“392 减 /blog/ 页”的未标注口径。

2. 第 10 行（#1 改法）
   现：“`复用 /products/ 卡片的 _packrivo_description 与 _packrivo_article_cover_url`”
   改：“`复用 /products/ 卡片的 _packrivo_description（134/134 有值）与 _packrivo_article_cover_url（仅 100/134 有值，其余 34 页需回退到特色图或 _packrivo_card_summary）`”

3. 第 11 行（#2 现状与改法）
   现：“`mu-plugin 的 own_production() 要求 ABSPATH + WP_ENV=production + home_url() 三者都等于正式环境；上线后条件成立，会接管 /blog/ 输出。`”
   改：“`mu-plugin 的 own_production() 要四组全中：DB_NAME=www_packrivo_com、ABSPATH=/var/www/html/www.packrivo.com/wordpress/、wp_get_environment_type()=production、home_url()=site_url()=https://www.packrivo.com（见 10 个 *-published-native/institutional 文件，代表 packrivo-blogc100-published-native-ba962f158b89386c.php:74-80，钩子注册入口 :309）。它改的是单篇文章的 schema/OG/TOC 缓冲，不管 /blog/ 模板；/blog/ 的模板冲突来自主题层级（子主题 home.php），不是它。`”
   同时删掉“会接管 `/blog/` 输出”。

4. 第 11 行（#2 改法里的开关名）
   现：“`需要加 pkv4_skip 开关或改成白名单`”
   改：“`需要新增一个开关/白名单（现无 pkv4_skip：主题与 mu-plugins 全文 0 命中），落点建议在 own_production() 之后、add_filter 之前`”

5. 第 12 行（#3）
   现：“`把 [packrivo_quote] 放进 /get-a-quote/，导航 CTA 指向它` / `替换该页内容，保留原表单作备选`”
   改：“`/get-a-quote/ 现为 301 → /inquiry/（packrivo-site-remediation-20261022.php:52），无独立页面可替换；先决定是新建 /get-a-quote/ 页面并撤掉该 301，还是在 /inquiry/（page 49）里并入 [packrivo_quote] 并保留原 inquiry-form`”

6. 第 18 行（#4 现状）
   保持“`front-page.php 第 10 行 $categories 数组`”不变（已核实为 `themes/packrivo-home-v4/front-page.php:10`），可补“`五条字面量、无 get_page_by_path`”。

7. 第 20 行（#6）
   现：“`复用 packrivo-handoff/data/dict/*.json 的键，pkv4_t() 包装`”
   改：“`复用 i18n/live 或 i18n/merged 的 {lang}-dict.json（每语种 6,171–6,175 个 distinct 键；packrivo-handoff/data/dict/*.json 只有 116 键，不够用）。站点侧 final 目录是 uploads/packrivo-i18n/（本地未拉）。pkv4_t() 目前不存在，需新建并在主题内包装`”
   并把“`6,169 条键要映射`”改成“`6,171–6,175 条键要映射`”。

8. 第 21 行（#7 影响面）
   现：“`149 页`”
   改：“`156 个 publish page 带 _packrivo_json_ld（另有 329 篇 post）`”
   理由：149 是线上 sitemap 口径，与本地库不符；重复 schema 的真实面是 156 页。

9. 执行顺序第 35–40 行
   现：只排 #2 #3 #1 #4 #5 #7 #10 #6
   改：补 `第 7 天 #8 产品图 + #9 search/404/sitemap + #11 uploads`，或把 #11 提到第 1 天并注明“无 uploads 则产品图/缩略图验收不成立”。
   理由：三项共 8h 未进日程，且 #11 是上线阻塞。

10. “明确不做”第 51–52 行
    现：“`不动 11 语种的译文内容（只接线）`”
    改：“`不动已有译文文本，但允许新增/补齐缺失键（#6 的键映射必然写字典）`”
    理由：与 #6 自相矛盾。

11. P2 第 30 行（#11）
    现：列在 P2，工时 1h
    改：移入 P0 尾部，标注“部署阻塞：本地无 uploads，134 个产品页封面与全站缩略图空白”。

### `docs/MIGRATION.md`

12. 第 31 行、第 67 行、第 84 行、第 86 行、第 94 行、第 322–323 行的 `149 页面` / `315 博客`
    改：统一标注口径，例如“`线上 Yoast sitemap 计数 149 页 / 315 篇（2026-10-26 抓取）；本地库为 392 publish page / 333 publish post，本地 sitemap 为 392/333`”，并删掉“`134 个产品样式及既有类目入口`”与 149 混写造成的口径错觉。

13. 第 68 行
    现：“`11 语种字典各 6,169 键为交接包核实结果`”
    改：“`11 语种字典各 6,171–6,175 个 distinct 键（i18n/merged、i18n/dict3 实测；线上 uploads/packrivo-i18n/ 未在本地拉取）`”

14. 第 86 行
    现：“`记录 315 篇文章，五个主题数为 Products & Structures 96、Materials & Finishes 127、Pricing & Sourcing 110、Samples & Quality 101、Shipping & Compliance 47`”
    改：“`本地副本为 333 篇；六个分类的实际计数为 未分类 141 / Materials & print 82 / Supplier briefs 73 / Samples & approval 64 / Structures & fit 40 / Shipping & rules 34（线上五主题名与计数需另行标注为抓取快照，且与库内分类名不同）`”

15. 第 90 行附近“`现站 134 与复刻 18 尚无逐项映射`”保持不变（134 已核实）。

16. 第 78 行“`不要把 /samples/、/services/、/blog/、/terms/ 不在 144 条白名单内误判为需要删除的坏页`”——144 条在本地不可复现：`uploads/packrivo-i18n/paths.json` 未拉，`packrivo_i18n_paths()` 回退到 `packrivo-i18n.php:18-22` 的 16 条内置常量。建议补“`144 条为线上 paths.json 记录，本地回退白名单为 16 条`”。
