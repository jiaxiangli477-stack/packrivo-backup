# 33a 本地验收报告

本轮从 `7ae9bc50cd28e824ec1473baef410386da0c3ec6` 建立独立 `33a` worktree。未合并、部署、访问生产或修改 32a/dsh 工作区、32a 容器、词典、allow 表和既有扫描脚本；未修改产品详情模板、URL、title、meta、H1。没有引入新外部字体、Tailwind 或参考稿图片。原主题已有字体请求，彩排测量时外部 HTTPS 均阻断。

33a 独立实例 `packrivo-33a-wp`、`packrivo-33a-db` 使用独立网络及命名卷；代码和 uploads 在容器命名卷，无 Windows 目录绑定。数据库来自经哈希核验的本地 32a5 前备份（SHA256 `7f8da71967eea8085556e030670964663dc91cd686dcd8dc0726d9e0de4af30c`），在新库重放 32a-5 博客迁移，`changed=318`，第二次 `changed=0`。没有读取 32a 容器。11 语种沿用隔离快照，字典本身未修改。

本地媒体快照只包含词典，缺少历史产品和博客 uploads；本地缺图使用占位，生产图片 URL 保留。主题本身的 Hero 图与 31b 四步照片可用。不能以占位截图证明生产真实图片的裁切效果；生产未访问。新英文清单 43 条，其中一条属于 quote-i18n，仍需 dsh 翻译；本轮未替用户翻译。

## A. 三视口截图及参考对照

[截图对照索引](33a-comparison.html) 将参考和本地结果放在一起，包含 1440、768、375 七类页面、首页分区、品类/专题卡片区和阿拉伯语截图。参考截图仅为用户提供参考稿的记录，不作为真实业务事实或网站素材。截图中较长的专题页与 375 品类页使用首屏加单独卡片区截图，其他标 `full` 的是整页；未做拼接伪造。

```text
home 1440 HTTP 200 WIDTH 1440/1440 HEIGHT 6754 MODE full H1 "Custom packaging for jewelry, candles & gifts." ERRORS []
products 1440 HTTP 200 WIDTH 1440/1440 HEIGHT 4142 MODE full H1 "Every structure we can source" ERRORS []
jewelry 1440 HTTP 200 WIDTH 1440/1440 HEIGHT 13748 MODE full H1 "Custom Jewelry Boxes With Logo for Your Collection" ERRORS []
blog 1440 HTTP 200 WIDTH 1440/1440 HEIGHT 6178 MODE full H1 "Packaging notes from the floor" ERRORS []
topic 1440 HTTP 200 WIDTH 1440/1440 HEIGHT 28233 MODE viewport H1 "Materials & print" ERRORS []
article 1440 HTTP 200 WIDTH 1440/1440 HEIGHT 7377 MODE full H1 "A Custom Packaging Supplier Brief That Gets Specific Replies" ERRORS []
product 1440 HTTP 200 WIDTH 1440/1440 HEIGHT 3933 MODE full H1 "Custom Window Lid Rigid Boxes" ERRORS []
home 768 HTTP 200 WIDTH 768/768 HEIGHT 11038 MODE full H1 "Custom packaging for jewelry, candles & gifts." ERRORS []
products 768 HTTP 200 WIDTH 768/768 HEIGHT 6960 MODE full H1 "Every structure we can source" ERRORS []
jewelry 768 HTTP 200 WIDTH 768/768 HEIGHT 14734 MODE full H1 "Custom Jewelry Boxes With Logo for Your Collection" ERRORS []
blog 768 HTTP 200 WIDTH 768/768 HEIGHT 5839 MODE full H1 "Packaging notes from the floor" ERRORS []
topic 768 HTTP 200 WIDTH 768/768 HEIGHT 22683 MODE viewport H1 "Materials & print" ERRORS []
article 768 HTTP 200 WIDTH 768/768 HEIGHT 7435 MODE full H1 "A Custom Packaging Supplier Brief That Gets Specific Replies" ERRORS []
product 768 HTTP 200 WIDTH 768/768 HEIGHT 3973 MODE full H1 "Custom Window Lid Rigid Boxes" ERRORS []
home 375 HTTP 200 WIDTH 375/375 HEIGHT 12998 MODE full H1 "Custom packaging for jewelry, candles & gifts." ERRORS []
products 375 HTTP 200 WIDTH 375/375 HEIGHT 12817 MODE full H1 "Every structure we can source" ERRORS []
jewelry 375 HTTP 200 WIDTH 375/375 HEIGHT 20595 MODE viewport H1 "Custom Jewelry Boxes With Logo for Your Collection" ERRORS []
blog 375 HTTP 200 WIDTH 375/375 HEIGHT 10118 MODE full H1 "Packaging notes from the floor" ERRORS []
topic 375 HTTP 200 WIDTH 375/375 HEIGHT 39464 MODE viewport H1 "Materials & print" ERRORS []
article 375 HTTP 200 WIDTH 375/375 HEIGHT 9418 MODE full H1 "A Custom Packaging Supplier Brief That Gets Specific Replies" ERRORS []
product 375 HTTP 200 WIDTH 375/375 HEIGHT 5603 MODE full H1 "Custom Window Lid Rigid Boxes" ERRORS []
exit=0
```

## B. 价格与链接

五个实际产品卡片均与 `PKQ_Pricing::price` 的 250 件结果一致；报价卡 Configure 在 JS 运行和禁用时均保留 qty=250，并在本地实际点击进入报价器得到同一规格及单价。商品目录按 250 件排序。134 个既有产品中 34 个有可用的引擎映射，100 个没有；后者明确显示人工询价，不虚构即时价格。已确认的产品级尺寸/材料为空，按要求省略，不把通用报价参考规格冒充实际产品参数。

```text
SLIDER_JS_PHP_COMPARE 40 MISMATCHES 0 ROUNDING_DIFFERENCES [{"qty":1250,"cents":1},{"qty":2000,"cents":1},{"qty":5250,"cents":1},{"qty":6000,"cents":1},{"qty":6500,"cents":1},{"qty":7750,"cents":1},{"qty":8000,"cents":-1}]
FIRST_MISMATCHES []
PRODUCT product-window-lid-rigid-gift-box CARD 5.20 ENGINE 5.20 STYLE rigid QTY 250 PASS
PRODUCT product-hinged-nonmagnetic-rigid-box CARD 5.20 ENGINE 5.20 STYLE rigid QTY 250 PASS
PRODUCT product-corrugated-roll-end-hinged-lid CARD 4.60 ENGINE 4.60 STYLE mailer QTY 250 PASS
PRODUCT product-euro-tote-paper-carrier CARD 3.36 ENGINE 3.36 STYLE bag QTY 250 PASS
PRODUCT product-sos-paper-gift-bag CARD 3.36 ENGINE 3.36 STYLE bag QTY 250 PASS
PRODUCT_FAILURES 0
exit=0
```

```text
price-asc ROWS 24 PRICES 2.36,2.84,2.84,2.84,2.84,2.84,2.84,2.84,2.84,2.84,2.84,3.36,3.36,3.36,3.36,4.36,4.36,4.36,4.6,4.6,4.6,4.6,4.6,5.2 MONOTONIC true
price-desc ROWS 24 PRICES 5.2,5.2,5.2,5.2,5.2,5.2,5.2,5.2,5.2,5.2,5.2,4.6,4.6,4.6,4.6,4.6,4.36,4.36,4.36,3.36,3.36,3.36,3.36,2.84 MONOTONIC true
exit=0
```

报价入口真实点击原始输出：

```text
CONFIGURE_URL http://127.0.0.1:8086/get-a-quote/?style=rigid&qty=250&product=product-window-lid-rigid-gift-box
ASIDE LIVE ESTIMATE

$5.20

per unit · 250 units

Style
Rigid box
Size
9.5 × 9.5 × 4 cm
Material
Rigid greyboard
Print
Full-colour CMYK
Finish
Matte varnish
Order total
$1,300.46

Goods $1,130.84 · Sourcing fee $169.63 (15%, min. $150) · Total $1,300.46

Instant quotes include the sourcing fee at 15%; your final rate is confirmed on the written quote.

Ex-works. Freight & duties quoted per destination. Setup amortised into the unit price.
exit=0
```

原引擎分别四舍五入货值、费用与总价，40 档中 7 档显示的两项相加与总价相差 1 美分；JS 与 PHP 各字段仍逐项一致。本轮未改已有引擎规则。首页 2,500 件免印刷起订费、5,000 件免模切起订费，阈值从引擎读取。六品类页面的现有卡片逐一比对源正文链接：

```text
bags-pouches ENHANCED_CARDS 13 PILLS 1 ORIGINAL_HREFS_LOST 0
foldable-gift-boxes ENHANCED_CARDS 56 PILLS 1 ORIGINAL_HREFS_LOST 0
inserts ENHANCED_CARDS 22 PILLS 1 ORIGINAL_HREFS_LOST 0
jewelry-boxes ENHANCED_CARDS 17 PILLS 1 ORIGINAL_HREFS_LOST 0
labels ENHANCED_CARDS 5 PILLS 1 ORIGINAL_HREFS_LOST 0
mailer-boxes ENHANCED_CARDS 34 PILLS 1 ORIGINAL_HREFS_LOST 0
TOTAL_CARDS 147 ORIGINAL_HREFS_LOST 0
```

博客及专题页面额外的分页、分类计数和无 JS 明细见 [blog-qa.log](33a-evidence/blog-qa.log)。

## C. 全站原始 HTML grep：未通过零命中

按照用户确认，只清理本轮新增内容；已有标准引用和供应链说明不改。范围为 735 个已发布英文 page/post、146 条语言路径 × 11、六个专题，去重 2,347 路径；请求仅到本地实例，不跟随外站重定向。2,346 返回 200，`/collapsible-magnetic-gift-box/` 返回既有 301，其目标在扫描范围。按原始 HTML 字符串计数而非只数可见文本：`factory` 包括每页路由数据中已有的 `pakfactory` URL；`ISO` 的原始子串命中也包括 `COMPARISON`。源数据库单词边界只读预检分别为 ISO 60、factory 46。用户选择保留存量，因此这两项明确 **FAIL**，不能称全站通过。

```text
PUBLISHED_ENGLISH 735 LANGUAGE_PATHS 146 TOTAL_UNIQUE 2347
PROGRESS 200 / 2347 NON200 0
PROGRESS 400 / 2347 NON200 0
PROGRESS 600 / 2347 NON200 0
PROGRESS 800 / 2347 NON200 1
PROGRESS 1000 / 2347 NON200 1
PROGRESS 1200 / 2347 NON200 1
PROGRESS 1400 / 2347 NON200 1
PROGRESS 1600 / 2347 NON200 1
PROGRESS 1800 / 2347 NON200 1
PROGRESS 2000 / 2347 NON200 1
PROGRESS 2200 / 2347 NON200 1
STATUSES {200: 2346, 301: 1}
PATTERN ISO MATCHING_URLS 39 RAW_MATCHES 111
PATTERN C147321 MATCHING_URLS 0 RAW_MATCHES 0
PATTERN $19 MATCHING_URLS 0 RAW_MATCHES 0
PATTERN Unsplash MATCHING_URLS 0 RAW_MATCHES 0
PATTERN tailwindcss MATCHING_URLS 0 RAW_MATCHES 0
PATTERN PK-[A-Z][0-9] MATCHING_URLS 0 RAW_MATCHES 0
PATTERN factory MATCHING_URLS 2346 RAW_MATCHES 6057
HITS_CSV E:\破甲工作区\packrivo-backup-33a\dsh-out\33a-grep-hits.csv NON200 1
exit=0
```

逐 URL 原始命中及上下文为 [33a-grep-hits.csv](33a-grep-hits.csv)，非 200 为 [33a-grep-non200.csv](33a-grep-non200.csv)。对 43 条新增英文键分别检查，上述七种模式均为 0；7 个验收页面的可见文本单词边界 ISO/factory 均为 0。

## D. 禁用 JavaScript

首页仍有服务端默认报价与价格表，滑块保持禁用；博客和产品页的正文与主要链接可见。

```text
home HTTP 200 VISIBLE_LINKS 27 MAIN_CHARS 5496 H1 "Custom packaging for jewelry, candles & gifts." SLIDER_DISABLED true
blog HTTP 200 VISIBLE_LINKS 59 MAIN_CHARS 5510 H1 "Packaging notes from the floor" SLIDER_DISABLED undefined
product HTTP 200 VISIBLE_LINKS 13 MAIN_CHARS 4059 H1 "Custom Window Lid Rigid Boxes" SLIDER_DISABLED undefined
exit=0
```

## E. 同实例 Lighthouse 移动端

以同一 33a 容器、同一数据库和 Lighthouse 12.8.2 测试；前测将主题及插件从 Git `7ae9bc5` 精确还原，四个关键文件的容器哈希与 Git 一致，旧受污染前测作废。两次都阻断外部 HTTPS，使用相同 Chrome 模拟及网络配置；这只说明隔离环境的相对结果，不代表线上体验或含历史媒体的成绩。

```text
before_7ae LCP_ms=2761.153850 CLS=0 score=0.93 warnings=[]
after_33a LCP_ms=2601.701675 CLS=0 score=0.94 warnings=[]
LCP_NOT_WORSE=True CLS_NOT_WORSE=True
```

[前测完整 JSON](33a-evidence/lighthouse-before-7ae.json) · [后测完整 JSON](33a-evidence/lighthouse-after-final.json)。本地原始测量命令与脚本位于 `.rehearsal-33a/`。

## F. 11 语言与 RTL

11 种语言首页各 1 页，375px 视口；所有页面一份 FAQPage，JSON 未作为可见正文，滑块可用，未观察到横向溢出或 JS 页面错误。`dir="-"` 表示页面未显式设置 dir，不代表阿语；阿语实际 `rtl`。新增字符串翻译尚未交付，页面会回退英文；合成阿语模板测试验证 8 个占位模板且数字 LTR，未修改真实词典。

```text
ar HTTP 200 WIDTH 375/375 DIR rtl FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
de HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
es HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
fr HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
it HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
ja HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
ko HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
nl HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
pt HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
zh-CN HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
zh-TW HTTP 200 WIDTH 375/375 DIR - FAQ 1 VISIBLE_JSON false SLIDER true ERRORS 0
exit=0
```

[阿拉伯语首页截图](33a-shots/home-ar-375.png) · [阿拉伯语整页](33a-shots/home-ar-375-full.png)。整页宽度 375，无横向溢出；刻度为 250 / 2,500 / 5,000 / 10,000。

## G. 新增英文

[33a-new-strings.csv](33a-new-strings.csv)：43 个唯一键，含 1 个报价插件 `quote-i18n` 键。报价插件资源版本更新为 `1.0.1-33a`，主题版本更新为 `4.0.7-33a`，避免旧 JS 覆盖 250 数量或使滑块不可用。
