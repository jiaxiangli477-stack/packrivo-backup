# 33b 本地验收报告

从 `503b9c50aee58031256b518c114179d9d2e412de` 新建 33b 独立 worktree 和实例，未合并、部署或访问生产。参考稿原文件已被系统清理；用户确认不再查找，本轮按 A–K 的顺序和 33a 视觉执行，因此没有伪称与参考图比对。

**未完成项：** 抽屉款材料未知、折叠款尺寸和材料未知，按“缺了就空着”不输出价格或 AggregateOffer；结构化数据验收 F 因此有 2 个未满足项。C 全站历史内容仍有 ISO、ISTA、ΔE，零命中未通过；用户此前选择保留存量，本轮也不改写旧正文。其他新英文及三款试点数据中上述禁词均为 0。

开工时曾在 33a 工作区执行 `git status`、`git log` 和 `git worktree list` 核对提交；未编辑其工作文件，未操作 32a/33a 容器。之后仅通过独立 helper **只读**复制 33a 三个命名卷到 33b 独立卷，源复制前后和目标文件树 SHA256 一致。新库 40 张表检查正常、391 已发布页面、344 已发布博客、2050 修订，报价设置为 250 / 15% / $150；实例内网、禁 WP Cron、拦截邮件。详见 `33b-evidence/copy-checks.log`、`environment-check.log`。

最终核对 16 个运行中的 33b 源码和图片与待提交 Git 索引逐字节一致；`page.php` 使用与 Git 及前测一致的 LF 换行，避免 Windows 拷贝引入 HTML 换行差异。

因克隆的 Yoast 派生缓存仍指向 :8086，仅在新 33b 库备份后将 735 个缓存 permalink/hash 改成 :8087；未改原 33a。五个非试点 HTML 在本地地址修正后、启用新模板前各抓两次作为基线。原缓存备份留在 `.rehearsal-33b/33b-yoast-before.json`。

## A. 试点三视口、阿拉伯语

[三款截图索引](33b-comparison.html)；每款含 1440、768、375，另有阿拉伯语旧版 375。概念图沿用现有站点图片并保留 AI-assisted 说明；抽屉盒另有泰艺目录样本照片，不能被当成同一 SKU。阿拉伯语按要求继续旧渲染，没有加载英语新模板。

```text
product-magnetic-jewelry-box 1440 HTTP 200 WIDTH 1440/1440 BUTTON_OVERFLOW 0 IMAGES 2 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Magnetic Jewelry Boxes with Logo" JS_ERRORS 0
product-drawer-jewelry-box 1440 HTTP 200 WIDTH 1440/1440 BUTTON_OVERFLOW 0 IMAGES 3 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Drawer Jewelry Boxes with Logo" JS_ERRORS 0
product-collapsible-magnetic-gift-box 1440 HTTP 200 WIDTH 1440/1440 BUTTON_OVERFLOW 0 IMAGES 2 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Collapsible Magnetic Gift Boxes" JS_ERRORS 0
product-magnetic-jewelry-box 768 HTTP 200 WIDTH 768/768 BUTTON_OVERFLOW 0 IMAGES 2 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Magnetic Jewelry Boxes with Logo" JS_ERRORS 0
product-drawer-jewelry-box 768 HTTP 200 WIDTH 768/768 BUTTON_OVERFLOW 0 IMAGES 3 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Drawer Jewelry Boxes with Logo" JS_ERRORS 0
product-collapsible-magnetic-gift-box 768 HTTP 200 WIDTH 768/768 BUTTON_OVERFLOW 0 IMAGES 2 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Collapsible Magnetic Gift Boxes" JS_ERRORS 0
product-magnetic-jewelry-box 375 HTTP 200 WIDTH 375/375 BUTTON_OVERFLOW 0 IMAGES 2 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Magnetic Jewelry Boxes with Logo" JS_ERRORS 0
product-drawer-jewelry-box 375 HTTP 200 WIDTH 375/375 BUTTON_OVERFLOW 0 IMAGES 3 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Drawer Jewelry Boxes with Logo" JS_ERRORS 0
product-collapsible-magnetic-gift-box 375 HTTP 200 WIDTH 375/375 BUTTON_OVERFLOW 0 IMAGES 2 BROKEN 0 FAQ 2 ROWS 7 H1 "Custom Collapsible Magnetic Gift Boxes" JS_ERRORS 0
AR_LEGACY 200 WIDTH 375/375 DIR rtl NEW_TEMPLATE false H1 "علب مجوهرات مغناطيسية مخصصة مع شعار"
exit=0
```

阿拉伯语试点沿用原模板，仅对有结构化数据的试点在旧价格条给数量和尺寸加 LTR 隔离；当前显示顺序为 `250`、`9.5×9.5×4`，宽度 375。旧式全彩哑光价与其旧报价器仍一致。详见 [ar-dimensions.log](33b-evidence/ar-dimensions.log) 和 [ar-legacy-quote.log](33b-evidence/ar-legacy-quote.log)。

## B. 引擎价格

磁吸盒的 95×95×40 mm 是单次已核实报价样例。当前估算模型明确标注未印刷、无表面工艺、内托另询，并非把含 EVA/烫金的供应商报价当网站实时价格。七档、英雄区、工艺增量、报价器样式预览及相关卡片与 PHP 引擎逐项核对；其余两款的表格保留数量和“按目的地报价”，未知单价/货值/起订费以 `—` 显示，不编造价格。相关卡片对有效结构化产品按同一规格算价，缺规格不报默认价；排序与显示一致。

```text
ROW product-magnetic-jewelry-box QTY 250 UNIT $3.41 GOODS $702.76 SETUP Waived PASS
ROW product-magnetic-jewelry-box QTY 500 UNIT $2.21 GOODS $957.43 SETUP Waived PASS
ROW product-magnetic-jewelry-box QTY 1000 UNIT $2.05 GOODS $1,780.83 SETUP Waived PASS
ROW product-magnetic-jewelry-box QTY 2500 UNIT $1.87 GOODS $4,069.09 SETUP Waived PASS
ROW product-magnetic-jewelry-box QTY 5000 UNIT $1.72 GOODS $7,467.98 SETUP Waived PASS
ROW product-magnetic-jewelry-box QTY 10000 UNIT $1.59 GOODS $13,787.03 SETUP Waived PASS
ROW product-magnetic-jewelry-box QTY 25000 UNIT $1.45 GOODS $31,595.29 SETUP Waived PASS
HERO product-magnetic-jewelry-box DISPLAY ["$2.05 / unit","$3.41 / unit"] ENGINE $2.05/$3.41 PASS
FINISH_DELTA foil DISPLAY +$0.76 / unit ENGINE $0.76 PASS
WIDGET_TILE mailer DISPLAY 1.1 ENGINE 1.1 PASS
WIDGET_TILE product DISPLAY 0.96 ENGINE 0.96 PASS
WIDGET_TILE shipping DISPLAY 0.87 ENGINE 0.87 PASS
WIDGET_TILE rigid DISPLAY 2.21 ENGINE 2.21 PASS
WIDGET_TILE carton DISPLAY 0.72 ENGINE 0.72 PASS
WIDGET_TILE pouch DISPLAY 0.56 ENGINE 0.56 PASS
WIDGET_TILE bag DISPLAY 0.82 ENGINE 0.82 PASS
WIDGET_TILE sticker DISPLAY 0.39 ENGINE 0.39 PASS
WIDGET_TILE poly DISPLAY 0.65 ENGINE 0.65 PASS
RELATED_CARD product-drawer-jewelry-box DISPLAY null ENGINE unavailable PASS
RELATED_CARD product-collapsible-magnetic-gift-box DISPLAY null ENGINE unavailable PASS
RESULT product-magnetic-jewelry-box ROWS 7 ENGINE_ROWS 7
ROW product-drawer-jewelry-box QTY 250 UNCONFIRMED_PLACEHOLDER PASS
ROW product-drawer-jewelry-box QTY 500 UNCONFIRMED_PLACEHOLDER PASS
ROW product-drawer-jewelry-box QTY 1000 UNCONFIRMED_PLACEHOLDER PASS
ROW product-drawer-jewelry-box QTY 2500 UNCONFIRMED_PLACEHOLDER PASS
ROW product-drawer-jewelry-box QTY 5000 UNCONFIRMED_PLACEHOLDER PASS
ROW product-drawer-jewelry-box QTY 10000 UNCONFIRMED_PLACEHOLDER PASS
ROW product-drawer-jewelry-box QTY 25000 UNCONFIRMED_PLACEHOLDER PASS
INITIAL_WIDGET_PRICE product-drawer-jewelry-box NONE_PASS
RELATED_CARD product-magnetic-jewelry-box DISPLAY 3.41 ENGINE 3.41 PASS
RESULT product-drawer-jewelry-box ROWS 7 ENGINE_ROWS 0
ROW product-collapsible-magnetic-gift-box QTY 250 UNCONFIRMED_PLACEHOLDER PASS
ROW product-collapsible-magnetic-gift-box QTY 500 UNCONFIRMED_PLACEHOLDER PASS
ROW product-collapsible-magnetic-gift-box QTY 1000 UNCONFIRMED_PLACEHOLDER PASS
ROW product-collapsible-magnetic-gift-box QTY 2500 UNCONFIRMED_PLACEHOLDER PASS
ROW product-collapsible-magnetic-gift-box QTY 5000 UNCONFIRMED_PLACEHOLDER PASS
ROW product-collapsible-magnetic-gift-box QTY 10000 UNCONFIRMED_PLACEHOLDER PASS
ROW product-collapsible-magnetic-gift-box QTY 25000 UNCONFIRMED_PLACEHOLDER PASS
INITIAL_WIDGET_PRICE product-collapsible-magnetic-gift-box NONE_PASS
RELATED_CARD product-magnetic-jewelry-box DISPLAY 3.41 ENGINE 3.41 PASS
RESULT product-collapsible-magnetic-gift-box ROWS 7 ENGINE_ROWS 0
TOTAL_PRICE_FAILURES 0
exit=0
```

磁吸款阶梯中 Select 5,000 件的实际跳转预填了 5,000 与 9.5×9.5×4 cm，报价器显示 $1.72；原始记录见 [select-flow.log](33b-evidence/select-flow.log)。

价格对比脚本为 [price-compare.cjs](33b-evidence/price-compare.cjs)，其 PHP 端为 [33b-price-fixture.php](../tests/33b-price-fixture.php)；复跑前将后者复制到 33b 容器 `/tmp/33b-price-fixture.php`，脚本只访问本地 33b。

尺寸切换及旧入口的保护验证：

```text
SELECT_SIZE_URL http://localhost:8087/product-drawer-jewelry-box/?size=1
DRAWER_CONFIGURE http://localhost:8087/get-a-quote/?product=product-drawer-jewelry-box&style=rigid&qty=250&size=1&require_material=1 {"length":"7","width":"9","height":"3.5","qty":"250","material":"","requireMaterial":"1","prices":0}
COLLAPSIBLE_GUARD Enter dimensions from 2 to 120 cm (one decimal place) and choose a material before viewing an estimate.
COLLAPSIBLE_BLANK_DIMS ["","",""]
exit=0
```

阿拉伯语旧版的 250 件参考价和跳转后的报价均为 $5.20（全彩、哑光）；英语新页的 $3.41 是无印刷、无工艺的报价示例，两者配置不同。

```text
AR_LEGACY_BAR ابتداءً من 5.20$ للوحدة
من 250 قطعة · 9.5×9.5×4 سم · طباعة كاملة الألوان، مطفي
اختر المواصفات → HREF /ar/get-a-quote/?style=rigid&qty=250&product=product-magnetic-jewelry-box
AR_QUOTE_URL http://127.0.0.1:8087/ar/get-a-quote/?style=rigid&qty=250&product=product-magnetic-jewelry-box PRINT full-color FINISH matte GUARD null PRICE $5.20
exit=0
```

## C. 全站 grep：存量命中未通过

范围为 735 个已发布英文页/文章、146 个语言路径 × 11 加专题，去重 2,347 路径；只请求本地 :8087，不跟随外站。2,346 个 200、一个既有 301。HTML 先做实体解码后按指定字符串搜索，ISO 子串计数包括 COMPARISON 等；ISTA 和 ΔE 也可出现在原有文章引用、科普中。全部新英文键和试点数据中 12 项均 0，未为消除存量命中而改写旧页面。

```text
PUBLISHED_ENGLISH 735 LANGUAGE_PATHS 146 TOTAL_UNIQUE 2347
PROGRESS 300 / 2347 NON200 0
PROGRESS 600 / 2347 NON200 0
PROGRESS 900 / 2347 NON200 1
PROGRESS 1200 / 2347 NON200 1
PROGRESS 1500 / 2347 NON200 1
PROGRESS 1800 / 2347 NON200 1
PROGRESS 2100 / 2347 NON200 1
STATUSES {200: 2346, 301: 1}
PATTERN ISO MATCHING_URLS 39 HTML_MATCHES 111
PATTERN ISTA MATCHING_URLS 47 HTML_MATCHES 69
PATTERN SGS MATCHING_URLS 0 HTML_MATCHES 0
PATTERN FSC® MATCHING_URLS 0 HTML_MATCHES 0
PATTERN C147321 MATCHING_URLS 0 HTML_MATCHES 0
PATTERN N52 MATCHING_URLS 0 HTML_MATCHES 0
PATTERN ΔE MATCHING_URLS 12 HTML_MATCHES 37
PATTERN Prop 65 MATCHING_URLS 0 HTML_MATCHES 0
PATTERN $19 MATCHING_URLS 0 HTML_MATCHES 0
PATTERN PK-R0 MATCHING_URLS 0 HTML_MATCHES 0
PATTERN tailwindcss MATCHING_URLS 0 HTML_MATCHES 0
PATTERN unsplash MATCHING_URLS 0 HTML_MATCHES 0
NON200 1
exit=0
```

[逐 URL 命中清单](33b-grep-hits.csv) · [非 200 清单](33b-grep-non200.csv)。

## D. 禁用 JavaScript

图片数量含主图与缩略图；图集的原图链接仍可打开。三页表格及 FAQ 可见；两款无报价证据时表格数字栏为 `—`。

```text
product-magnetic-jewelry-box HTTP 200 IMAGES_LOADED_VISIBLE 2 THUMBNAILS 1 PRICE_ROWS 7 SPEC_ROWS 8 FAQ_OPEN 2 LINKS_FORMS_VISIBLE 19 OVERFLOW false
product-drawer-jewelry-box HTTP 200 IMAGES_LOADED_VISIBLE 3 THUMBNAILS 2 PRICE_ROWS 7 SPEC_ROWS 4 FAQ_OPEN 2 LINKS_FORMS_VISIBLE 21 OVERFLOW false
product-collapsible-magnetic-gift-box HTTP 200 IMAGES_LOADED_VISIBLE 2 THUMBNAILS 1 PRICE_ROWS 7 SPEC_ROWS 4 FAQ_OPEN 2 LINKS_FORMS_VISIBLE 16 OVERFLOW false
exit=0
```

## E. 本地测试询价

首次提交因 33b 专用测试代理漏转 `X-PKQ-Nonce` 返回 403，确认没有记录；修复本地 fast proxy 与 daemon 的请求头转发后，只重试一次并成功落库 1 条。以下是成功回执和库字段；邮件由 33b 本地拦截，不发送外部邮件。

```text
PREFLIGHT_OK {"style":"rigid","product":"product-magnetic-jewelry-box","qty":"250","length":"9.5","width":"9.5","height":"4","material":"rigidboard","print":"none","finish":"none"} JS_ERRORS []
POST_COUNT 1 HTTP 200 RESPONSE {"ok":true,"ref":"PKQ-NMSIW","unit":3.41,"total":852.76,"goods_total":702.76,"fee":150,"quantity":250}
REQUEST_SPEC {"style":"rigid","product":"product-magnetic-jewelry-box","material":"rigidboard","print":"none","finish":"none","quantity":250,"length":9.5,"width":9.5,"height":4}
VISIBLE_RESULT quested PKQ-NMSIW We reply within one business day with a proof timeline. Reference PKQ-NMSIW. LIVE ESTIMATE $3.41 per unit · 250 units Style Rigid box Size 9.5 × 9.5 × 4 cm Material Rigid greyboard Print Unprinted Finish None Order total $852.76 Goods $702.76 · Sourcing fee $150 (15%, min. $150) · Total $852.76 Instant quotes include the sourcing fee at 15%; your final rate is confirmed on the written quote. Ex-works. Freight & duties quoted per destination. Setup amortised into the unit price.
JS_ERRORS []
exit=0
```

```text
MATCHING_REFS=1
ORDER_ID=3678 STATUS=publish
pkq_ref=PKQ-NMSIW
pkq_product=product-magnetic-jewelry-box
pkq_style=rigid
pkq_material=rigidboard
pkq_print=none
pkq_finish=none
pkq_quantity=250
pkq_length=9.5
pkq_width=9.5
pkq_height=4
pkq_goods_total=702.76
pkq_fee=150
pkq_unit=3.41
pkq_total=852.76
```

```text
[Thu Nov 05 12:33:18.487080 2026] [php:notice] [pid 61:tid 61] [client 127.0.0.1:41270] PACKRIVO_33B_MAIL_INTERCEPTED
```

实际落库发生在最终预填保护加强之前；加强后，为避免产生第二条真实测试记录，只在浏览器拦截最终表单 POST 并模拟响应，核对其产品、尺寸、材料与数量均与已落库请求一致。真实记录与模拟验证分别保留，不将模拟称为再次落库。

```text
FINAL_GUARDED_WIDGET_MOCK_NOT_SENT {"product":"product-magnetic-jewelry-box","style":"rigid","material":"rigidboard","quantity":250,"length":9.5,"width":9.5,"height":4,"print":"none","finish":"none"} PASS true
exit=0
```

补充回归：结构化产品已知规格默认启用保护；重复点击相同盒型不会替换尺寸，带 product 而省略 style 的旧入口会按该产品已确认映射补全。详见 [confirmed-entry.log](33b-evidence/confirmed-entry.log)。

## F. schema 本地校验：部分未通过

下列是本地 schema 检查脚本结果，不冒称 Google Rich Results Test。三个页面 Product、BreadcrumbList、FAQPage 各一份，JSON 可解析，面包屑及 FAQ 对应可见内容；原有 Yoast Service 各一份。只有磁吸款有完整报价输入与 AggregateOffer；另外两款没有可证实输入，缺 AggregateOffer 按验收严格记为 2 个错误。

```text
product-magnetic-jewelry-box HTTP 200 JSON_BLOCKS 3 PRODUCT 1 BREADCRUMB 1 FAQ 1 SERVICE 1 OFFER 1 OFFER_DETAIL USD low=1.45 high=3.41 expected=1.45/3.41 tiers=7 ERRORS 0
product-drawer-jewelry-box HTTP 200 JSON_BLOCKS 3 PRODUCT 1 BREADCRUMB 1 FAQ 1 SERVICE 1 OFFER 0 OFFER_DETAIL none ERRORS 1
  FAIL AggregateOffer missing; source dimensions/material may be incomplete
product-collapsible-magnetic-gift-box HTTP 200 JSON_BLOCKS 3 PRODUCT 1 BREADCRUMB 1 FAQ 1 SERVICE 1 OFFER 0 OFFER_DETAIL none ERRORS 1
  FAIL AggregateOffer missing; source dimensions/material may be incomplete
TOTAL_PAGES 3 ERRORS 2
exit=1
```

校验脚本：[33b-schema-test.py](../tests/33b-schema-test.py)。

## G. 非试点旧页原始 HTML

未填 `pk_product` 的五个随机产品页，改造后与 33b 本地基线逐字节一致：

```text
COMPARE product-auto-bottom-display-carton HTTP 200 BYTES_BEFORE 96197 BYTES_AFTER 96197 SHA256 6995a45557c883bcf4d3ec157cc7d90d924f319adb22c3f49764656d0c2abe81 BYTE_EQUAL true
COMPARE product-bookend-folding-carton HTTP 200 BYTES_BEFORE 96355 BYTES_AFTER 96355 SHA256 2075899424108142b7ddccf79070ab8b9ffe51765d015d2b2e92e4ae6cbb5e5a BYTE_EQUAL true
COMPARE product-molded-pulp-corner-cradle HTTP 200 BYTES_BEFORE 86358 BYTES_AFTER 86358 SHA256 9fcc7aa7426bbaf73ba63ad9d45ad89ae7aac50ce5dae4e5eb6b4d5a2c50fb56 BYTE_EQUAL true
COMPARE product-slide-in-card-blister HTTP 200 BYTES_BEFORE 95393 BYTES_AFTER 95393 SHA256 e7c614ee49bbf5fe8edb04166b923ccaecc40d19b17a04b2fffb8de34a6299b0 BYTE_EQUAL true
COMPARE product-six-corner-pre-glued-carton HTTP 200 BYTES_BEFORE 96221 BYTES_AFTER 96221 SHA256 f2e1795bad314d7be241bb0feaacb165e0aed693363dd18d79d3008993de72a0 BYTE_EQUAL true
TOTAL 5 FAILURES 0
exit=0
```

## H. 导入器与固定英文

真实 WP-CLI 预检无效混合批次未写入，首个正式三页导入 `updated=3 unchanged=0`，随即第二次 `updated=0 unchanged=3`；随后重新运行两次：

```text
RUN 1 exit=0
Success: updated=0 unchanged=3

RUN 2 exit=0
Success: updated=0 unchanged=3
```

`film-frame` 已通过导入校验及本地临时显示测试，页面显示 `Clear film frame`、`quoted on request`；同次临时测试验证 source 为空的规格未渲染，输出 `FILM_FRAME_RENDER True EMPTY_SOURCE_HIDDEN True META_RESTORED True`，测试后原 meta 哈希恢复一致。22 项字段校验、8 项事务桩、13 项 shortcode 检查、25 项报价保护浏览器检查通过；旧报价器 9 个 DOM 状态逐字相等。数据导入器：[33b-product-data.php](../packrivo-wp/packrivo-home-v4/inc/33b-product-data.php)，试点 JSON：[33b-pilot-products.json](../deploy/33b-pilot-products.json)。

[固定英文清单：85 条](33b-new-strings.csv)，其中 4 条属于报价插件；尚未由 dsh 翻译，也没有改现有字典。试点产品独有文字在 JSON 中，待后续翻译。图片、尺寸和事实的逐项来源见 [33b-pilot-sources.md](33b-pilot-sources.md)。
