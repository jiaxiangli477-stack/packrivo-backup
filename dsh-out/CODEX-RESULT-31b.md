# 31b 本地彩排及复核

- 工作区：`E:\破甲工作区\packrivo-backup-31b`，分支 `31b`，起点 `74e759c3055869de9e6fbe1c95e88d0ceb5ae203`（31a）。不合并、不部署，未修改生产或清理 Cloudflare。
- 独立环境：`packrivo-31b-wp`、`packrivo-31b-db`、`packrivo-31b-db` 数据卷和内部网络；本机 `http://localhost:8082` 经本地转发访问。数据库基于 2026-10-30 17:45:26 洛杉矶时间的 29y 生产导出，本地测试库已将默认分类从 `uncategorized` 改为 `Guides`/`guides`。上线前须用最新导出完整重演，不能把本地数据库变化当作生产已变。
- 数据导入的隔离失误：初始化时只读挂载并读取了 main 工作区内被忽略的 `deploy/verification-29y/export/29y-prod.sql`，这触及了用户要求避开的 dsh 工作区路径；源文件没有写入。完成全量验收后已重新创建仅使用 `packrivo-31b-db` 独立数据卷的数据库容器，撤掉该只读挂载；重建后复核 40 张表、Guides 分类和测试记录均保留。
- 语言基线：从 main 的 Git 对象 `0a3615ddbb206feb458d103ab2ea6ed3ac2da85d` 只读导出 11 份词典与 `paths.json` 到独立彩排卷，12/12 SHA-256 相符，见 `31b-i18n-sha256.txt`。未从 dsh 未提交工作文件复制。
- 为模拟语言 sitemap 的生产域名，本地实例仅在语言 sitemap 请求时通过未提交的测试夹具将 Yoast 的本地域名映射到插件硬编码的生产域名；验收脚本将 sitemap 中的生产地址映射回 localhost。该夹具不纳入提交。缺少此夹具时，由于本地/生产域名不匹配，语言 sitemap 会是空的。
- 浏览器预览隔离失误：本地首页包含已有的生产域名图片绝对链接，浏览器自动向 `www.packrivo.com` 发出了至少 5 个图片 GET；还请求了 Google Fonts。未有生产写入、部署或清理操作。发现后关闭了预览标签，之后的站点 HTTP 验收通过 localhost 映射执行。
- 浏览器预览同时发现独立本地快照缺少若干既有博客封面，出现本地资源 404；这不是线上缺图的证据。本轮 4 张新流程图已在浏览器中加载。
- main 工作区在任务期间出现并行状态变化（含 dsh 阿拉伯语相关改动与证据），不能声称其 `git status` 前后相同；31b 的文件修改与提交只在本 worktree 中进行。
- 生产前需要对接 29y 的语言插件基线；本地 CSS/JS 与 mu-plugin 安装基于独立快照，不能证明与未来合并版本完全一致。公开发布和部署均须另行授权。

## 九页判断

| 页面 | 处理 | 理由 |
| --- | --- | --- |
| `/drawer-box-with-ribbon/` | 珠宝盒类目加入标题链接 | 以丝带拉手与滑动间隙为具体场景；不替换主产品页的链接。 |
| `/corrugated-wine-shipper/` | 邮寄盒类目加入标题链接 | 侧重瓶体承载与运输保护。 |
| `/eco-kraft-cosmetic-box/` | 礼盒类目加入标题链接 | 侧重化妆品小批量与未涂布牛皮纸。 |
| `/jewelry-pouch-and-box-set/` | 袋与袋类目加入标题链接 | 关注袋盒组合与配色协调。 |
| `/kraft-stand-up-pouch/` | 袋与袋类目加入标题链接 | 关注带窗自立袋及阻隔结构。 |
| `/magnetic-book-style-box/` | 礼盒类目加入标题链接 | 关注磁吸翻盖与书脊；更宽泛书型产品页不限定磁吸。 |
| `/printed-tissue-paper/` | 内托类目加入标题链接 | 关注纸重、印刷和尺寸，与结构型产品页侧重点不同。 |
| `/soap-box-with-window/` | 礼盒类目加入标题链接 | 关注带窗皂盒和防油要求。 |
| `/subscription-mailer-with-insert/` | 邮寄盒类目加入标题链接 | 关注订阅盒多件定位与内托组合。 |

九页均按独立价值处理；新增区块及标题见 `31b-new-strings.csv`。为达到本轮英文 sitemap 零孤岛的要求，31a 因 30a 未落地而暂缓处理的 5 个 catalog 页面也从对应英文品类页建立了可见链接；其页面和索引状态未修改。30a 最终合并时应重新检查这些链接和索引策略。

## 验收记录

### 1. 路由原始输出

```text
/de/about 301 http://localhost:8082/de/about/
/de/about/ 200
/zh-CN/about/ 200
/PRIVACY 301 http://localhost:8082/privacy/
/category/uncategorized/ 301 http://localhost:8082/category/guides/
/this-should-404-xyz123 404
```

### 2–4. 无 JS 遍历、sitemap 与英文内容

```text
SITEMAP_CHILD_COUNTS [["/post-sitemap.xml", 326], ["/page-sitemap.xml", 360], ["/de-sitemap.xml", 124], ["/fr-sitemap.xml", 124], ["/es-sitemap.xml", 124], ["/it-sitemap.xml", 124], ["/nl-sitemap.xml", 124], ["/pt-sitemap.xml", 124], ["/ar-sitemap.xml", 124], ["/ja-sitemap.xml", 124], ["/ko-sitemap.xml", 124], ["/zh-CN-sitemap.xml", 124], ["/zh-TW-sitemap.xml", 124]]
SITEMAP_ENGLISH 674 SITEMAP_LOCALIZED 1364 SITEMAP_TOTAL 2038
SITEMAP_CHECKED 100
SITEMAP_CHECKED 200
SITEMAP_CHECKED 300
SITEMAP_CHECKED 400
SITEMAP_CHECKED 500
SITEMAP_CHECKED 600
SITEMAP_CHECKED 700
SITEMAP_CHECKED 800
SITEMAP_CHECKED 900
SITEMAP_CHECKED 1000
SITEMAP_CHECKED 1100
SITEMAP_CHECKED 1200
SITEMAP_CHECKED 1300
SITEMAP_CHECKED 1400
SITEMAP_CHECKED 1500
SITEMAP_CHECKED 1600
SITEMAP_CHECKED 1700
SITEMAP_CHECKED 1800
SITEMAP_CHECKED 1900
SITEMAP_CHECKED 2000
SITEMAP_ANOMALIES 0
LANG_HOME_LINKS 11 ar,de,es,fr,it,ja,ko,nl,pt,zh-CN,zh-TW
BFS_VISITED 36 FETCHING 7
BFS_VISITED 560 FETCHING 68
BFS_VISITED 779 FETCHING 30
BFS_VISITED 800 FETCHING 21
BFS_VISITED 809 FETCHING 9
BFS_VISITED 817 FETCHING 8
BFS_VISITED 821 FETCHING 4
BFS_VISITED 825 FETCHING 4
BFS_VISITED 826 FETCHING 1
EN_ORPHANS 0
EN_DEPTH_GT_3 0 MAX_DEPTH 3
EN_UNCATEGORIZED_CHINESE_PAGES 0
RELATED_LINK_VIOLATIONS 0
ENGLISH_VISITED 826
RESULT PASS
```

```text
QUOTE_H1_COUNT 1
```

```text
POSTS_CHECKED 100
POSTS_CHECKED 200
POSTS_CHECKED 300
PUBLISHED_POSTS 344 RELATED_OR_CHINESE_VIOLATIONS 0
RESULT PASS
```

### 5. 独立实例询价

```text
POST /wp-json/packrivo/v1/order HTTP 200
{"ok":true,"ref":"PKQ-BXRDG","unit":2.83,"total":1417.43,"quantity":500}
3649	pkq_order	publish	PKQ-BXRDG · 31b local rehearsal
```

测试专用 mu 插件拦截了 `wp_mail`；请求和数据库均为独立本地实例。

### 6. 语言关闭/开启对比

```text
on CAPTURED 11
on CAPTURED 22
on CAPTURED 33
off CAPTURED 11
off CAPTURED 22
off CAPTURED 33
/de/ off=200 on=200 diff_bytes=0 PASS
/de/about/ off=200 on=200 diff_bytes=0 PASS
/de/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/fr/ off=200 on=200 diff_bytes=0 PASS
/fr/about/ off=200 on=200 diff_bytes=0 PASS
/fr/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/es/ off=200 on=200 diff_bytes=0 PASS
/es/about/ off=200 on=200 diff_bytes=0 PASS
/es/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/it/ off=200 on=200 diff_bytes=0 PASS
/it/about/ off=200 on=200 diff_bytes=0 PASS
/it/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/nl/ off=200 on=200 diff_bytes=0 PASS
/nl/about/ off=200 on=200 diff_bytes=0 PASS
/nl/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/pt/ off=200 on=200 diff_bytes=0 PASS
/pt/about/ off=200 on=200 diff_bytes=0 PASS
/pt/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/ar/ off=200 on=200 diff_bytes=0 PASS
/ar/about/ off=200 on=200 diff_bytes=0 PASS
/ar/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/ja/ off=200 on=200 diff_bytes=0 PASS
/ja/about/ off=200 on=200 diff_bytes=0 PASS
/ja/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/ko/ off=200 on=200 diff_bytes=0 PASS
/ko/about/ off=200 on=200 diff_bytes=0 PASS
/ko/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/zh-CN/ off=200 on=200 diff_bytes=0 PASS
/zh-CN/about/ off=200 on=200 diff_bytes=0 PASS
/zh-CN/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
/zh-TW/ off=200 on=200 diff_bytes=0 PASS
/zh-TW/about/ off=200 on=200 diff_bytes=0 PASS
/zh-TW/jewelry-boxes/ off=200 on=200 diff_bytes=0 PASS
FILTERS: CRLF; language selector list; footer language links; added process figures and permission note; selector CSS mtime and JS version
TOTAL 33 FAIL 0
```

对照过滤的范围是允许的切换器列表、页脚语言行、已登记的流程图及说明；另外统一 CRLF 与切换器静态资源版本。原始 HTML 与 diff 位于 `.rehearsal-31b/language-comparison/`。两组使用同一数据库，包括已在本地更名的 Guides 分类。

### 7. 首页图片

```text
ALT Supplier sample room with shelves of rigid gift boxes and jewelry boxes
SRCSET http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/brief-480.webp 480w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/brief-960.webp 960w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/brief-1600.webp 1600w
FILE brief-480.webp BYTES 16070 SIZE 480x360
FILE brief-960.webp BYTES 41826 SIZE 960x720
FILE brief-1600.webp BYTES 83264 SIZE 1600x1200
WIDTH_HEIGHT 1600 1200 LOADING lazy
ALT Operator feeding a printed proof sheet into a wide-format digital proofing printer
SRCSET http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/sample-480.webp 480w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/sample-960.webp 960w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/sample-1600.webp 1600w
FILE sample-480.webp BYTES 19394 SIZE 480x360
FILE sample-960.webp BYTES 48048 SIZE 960x720
FILE sample-1600.webp BYTES 92542 SIZE 1600x1200
WIDTH_HEIGHT 1600 1200 LOADING lazy
ALT Multi-colour sheet-fed offset printing press in a packaging factory
SRCSET http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/produce-480.webp 480w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/produce-960.webp 960w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/produce-1600.webp 1600w
FILE produce-480.webp BYTES 20782 SIZE 480x360
FILE produce-960.webp BYTES 50988 SIZE 960x720
FILE produce-1600.webp BYTES 95892 SIZE 1600x1200
WIDTH_HEIGHT 1600 1200 LOADING lazy
ALT Finished jewelry boxes packed in export cartons before shipping
SRCSET http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/deliver-480.webp 480w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/deliver-960.webp 960w, http://localhost:8082/wp-content/themes/packrivo-home-v4/assets/img/31b-process/deliver-1040.webp 1040w
FILE deliver-480.webp BYTES 14812 SIZE 480x360
FILE deliver-960.webp BYTES 37486 SIZE 960x720
FILE deliver-1040.webp BYTES 43878 SIZE 1040x780
WIDTH_HEIGHT 1040 780 LOADING lazy
PHOTOS 4 RESULT PASS
```

桌面截图：`.rehearsal-31b/31b-home-final-live.png`；移动截图：`.rehearsal-31b/31b-home-mobile-final-live.png`。第四张源图最大宽度为 1040，遵照此前不放大要求，最终一档为 1040w 而非任务单要求的 1600w。

### 其他检查

```text
/blog/topic/guides/ HTTP 200 DB_POSTS 141 LINKS 141 CANONICAL http://localhost:8082/blog/topic/guides/ PASS
/blog/topic/materials-print/ HTTP 200 DB_POSTS 85 LINKS 85 CANONICAL http://localhost:8082/blog/topic/materials-print/ PASS
/blog/topic/samples-approval/ HTTP 200 DB_POSTS 64 LINKS 64 CANONICAL http://localhost:8082/blog/topic/samples-approval/ PASS
/blog/topic/shipping-rules/ HTTP 200 DB_POSTS 34 LINKS 34 CANONICAL http://localhost:8082/blog/topic/shipping-rules/ PASS
/blog/topic/structures-fit/ HTTP 200 DB_POSTS 48 LINKS 48 CANONICAL http://localhost:8082/blog/topic/structures-fit/ PASS
/blog/topic/supplier-briefs/ HTTP 200 DB_POSTS 73 LINKS 73 CANONICAL http://localhost:8082/blog/topic/supplier-briefs/ PASS
TOPICS 6 RESULT PASS
```

```text
de HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
fr HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
es HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
it HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
nl HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
pt HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
ar HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
ja HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
ko HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
zh-CN HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
zh-TW HTTP 200 URLS 124 ONLY_PRIVACY_REMOVED TRUE
LOCALE_SITEMAPS 11 RESULT PASS
```

31b 定向测试 9 项通过；31a 关键路由测试 6 项通过。全量 sitemap 检查期间，仅发生了流程图片样式改为行内样式、语言行从页脚外移入 footer 的结构性调整；链接目标、状态及 robots 逻辑未改变，之后又以最终代码重跑了 33 对语言差异及定向页脚测试。扫描结束后还修正了英文专题页的文档标题，并对全部 6 个专题重跑标题、文章数和 canonical 检查；专题页不在 sitemap 中。语言切换器脚本更新了缓存版本并移除旧的 cookie 与跳转处理，保留弹窗及搜索；最终代码已重新完成 33 对语言 HTML 比对和阻断外网的浏览器交互检查。

### 新增及复用到新位置的英文文字

完整可交付 CSV：`dsh-out/31b-new-strings.csv`。

```text
英文原文,出现位置,涉及页面
More in this category,品类页相关链接区标题,"/jewelry-boxes/; /mailer-boxes/; /foldable-gift-boxes/; /bags-pouches/; /inserts/"
Related reading,文章末尾相关链接区标题,所有英文博客文章
{Topic} | Packrivo,专题页文档标题模板（专题名称复用现有分类）,英文专题页
Drawer Box With Ribbon Pull,品类页相关链接标题,/jewelry-boxes/
Corrugated Wine Bottle Shipper,品类页相关链接标题,/mailer-boxes/
Subscription Mailer Box With Insert,品类页相关链接标题,/mailer-boxes/
Eco Kraft Cosmetic Box for Small Batches,品类页相关链接标题,/foldable-gift-boxes/
Magnetic Book-Style Presentation Box,品类页相关链接标题,/foldable-gift-boxes/
Custom Kraft Soap Box With Window,品类页相关链接标题,/foldable-gift-boxes/
Jewelry Pouch and Box Set,品类页相关链接标题,/bags-pouches/
Kraft Stand-Up Pouch With Window,品类页相关链接标题,/bags-pouches/
"Jewelry Boxes, Pouches and Bags — Reference Catalogue",品类页相关链接标题,/bags-pouches/
Pink Jewelry Storage Box,品类页相关链接标题,/bags-pouches/
Jewelry Packaging Box — Ref. 996892,品类页相关链接标题,/bags-pouches/
Velvet-Lined Boxes — Reference Catalogue,品类页相关链接标题,/jewelry-boxes/
"Velvet-Lined Box — Velvet, Faux Fur",品类页相关链接标题,/jewelry-boxes/
Custom Printed Tissue Paper,品类页相关链接标题,/inserts/
"For a quote, email us or contact us on WhatsApp.",无 JavaScript 兜底,/get-a-quote/
Guides,默认分类名称与专题页标题,/blog/; /blog/topic/guides/; 博客面包屑
Bags & pouches,首页产品区链接（复用现有名称）,/
Packaging examples,页脚案例链接（复用现有名称）,全站页脚
illustrative walkthroughs,About 正文链接（原文已有文字）,/about/
English,语言切换器链接（复用现有语言名称）,全站语言切换器
Materials & print,专题页标题及入口（复用现有分类名称）,/blog/topic/materials-print/; /blog/; 导航
Supplier briefs,专题页标题及入口（复用现有分类名称）,/blog/topic/supplier-briefs/; /blog/; 导航
Samples & approval,专题页标题及入口（复用现有分类名称）,/blog/topic/samples-approval/; /blog/; 导航
Structures & fit,专题页标题及入口（复用现有分类名称）,/blog/topic/structures-fit/; /blog/; 导航
Shipping & rules,专题页标题及入口（复用现有分类名称）,/blog/topic/shipping-rules/; /blog/; 导航
Supplier sample room with shelves of rigid gift boxes and jewelry boxes,首页照片 alt,/
"Supplier sample room, Dongguan. Structures are chosen from real samples before artwork starts.",首页照片图注,/
Operator feeding a printed proof sheet into a wide-format digital proofing printer,首页照片 alt,/
Digital proof printing at a supplier in Dongguan.,首页照片图注,/
Multi-colour sheet-fed offset printing press in a packaging factory,首页照片 alt,/
Sheet-fed offset press used for production runs.,首页照片图注,/
Finished jewelry boxes packed in export cartons before shipping,首页照片 alt,/
"Finished boxes packed into cartons, Shantou.",首页照片图注,/
"Supplier photos, used with permission.",首页照片统一图注,/
```
