# CODEX-RESULT-2026-10-28l

执行日期：2026-10-28（America/Los_Angeles）。备份仓库起点 `main` / `cb24981`。本轮只改子主题和报价插件，未修改父主题、其他插件或 MU；未提交询盘，没有部署或修改 www.packrivo.com。生产仅用于 GET 只读对照。

## 1. P0：本地询盘控件

**根因。** 本地数据库中 inquiry 原始 `post_content` 就已没有 form、select、input；`get_the_content` 和 `the_content` 对照都没有这些标签。子主题 native 页直接输出该内容，模板不是本次标签消失的起点。诊断中 `wp_kses_post` 对样本会剥除 form/select/input/style、留下 option 文字、textarea 和样式正文；当前环境存在相应保存过滤器，现象与先前保存时被过滤一致，但没有历史调用日志，不能断言当时具体是谁执行了过滤。

```powershell
Get-Content -LiteralPath E:\破甲工作区\packrivo-backup\scripts\ui-28l\inspect-content.php -Raw | & D:\Docker\App\resources\bin\docker.exe exec -i packrivo-live-wp-1 php
Get-Content -LiteralPath E:\破甲工作区\packrivo-backup\scripts\ui-28l\inspect-kses.php -Raw | & D:\Docker\App\resources\bin\docker.exe exec -i packrivo-live-wp-1 php
```

```text
{"slug":"inquiry","id":49,"native":"1","stored":{"bytes":7088,"form":0,"select":0,"input":0,"option":0,"style":0,"table":0,"css_marker":0},"get_the_content":{"bytes":7088,"form":0,"select":0,"input":0,"option":0,"style":0,"table":0,"css_marker":0},"filtered":{"bytes":7163,"form":0,"select":0,"input":0,"option":0,"style":0,"table":0,"css_marker":0}}
{"slug":"jewelry-boxes","id":9,"native":"1","stored":{"bytes":35569,"form":0,"select":0,"input":0,"option":0,"style":0,"table":1,"css_marker":5},"get_the_content":{"bytes":35569,"form":0,"select":0,"input":0,"option":0,"style":0,"table":1,"css_marker":5},"filtered":{"bytes":37175,"form":0,"select":0,"input":0,"option":0,"style":0,"table":1,"css_marker":5}}
{"input":"<form><select><option>One</option></select><input type=\"text\"><textarea>Note</textarea><style>.sample{color:red}</style></form>","wp_kses_post":"One<textarea>Note</textarea>.sample{color:red}","content_save_filter":10,"content_filtered_filter":10}
```

**修复。** 子主题 `page.php` 在已知本地缺损形态下，从已有父主题只读资产 `packrivo-static/assets/inquiry-controls.html` 恢复控件标记和现有控件文本；不修改数据库。已存在完整表单或参考文件缺失时保持原内容。旧正文标准化后 3,046/3,046 字节按原次序保留，新增的是恢复缺失的生产参考控件和现有选项，没有改写原有句子。

```powershell
Get-Content -LiteralPath E:\破甲工作区\packrivo-backup\scripts\ui-28l\test-repair.php -Raw | & D:\Docker\App\resources\bin\docker.exe exec -i packrivo-live-wp-1 php
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-inquiry.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-inquiry-keyboard.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\compare-options.mjs
```

原样输出：

```text
{"original_text_bytes_preserved":3046,"original_text_bytes_total":3046,"intact_form_unchanged":true,"missing_reference_noop":true,"repaired_counts":{"form":1,"select":1,"input":11,"textarea":1},"pass":true}
[{"name":"production","status":200,"forms":1,"selects":1,"inputs":12,"textareas":1,"options":354,"selected":"pillow-jewelry-box"},{"name":"local","status":200,"forms":1,"selects":1,"inputs":12,"textareas":1,"options":162,"selected":"pillow-jewelry-box"}]
PASS control parity and product preselection; option counts separately reported
width=375 fields=14 keyboard_missing=0 product=pillow-jewelry-box status=200
width=768 fields=14 keyboard_missing=0 product=pillow-jewelry-box status=200
width=1440 fields=14 keyboard_missing=0 product=pillow-jewelry-box status=200
PASS 3 viewports, keyboard reached every visible form field, no form submission
local status=200 options=162 uniqueValues=162
production status=200 options=354 uniqueValues=354
unique local_only=0 production_only=192
```

form/select/input/textarea 控件计数与生产一致，`pillow-jewelry-box` 被选中；三种视口以键盘打开可选区域后，14 个可见字段均可通过 Tab 到达。**真实数据范围例外：**生产 354 个 option，本地 162 个；本地值均属于生产值，生产多 192 个。没有虚构或批量导入产品，不能称为选项数一致；详见 `option-comparison.json`。没有发送表单。

截图：[375px](E:/破甲工作区/packrivo-backup/deploy/verification-28l/inquiry-375.png)、[768px](E:/破甲工作区/packrivo-backup/deploy/verification-28l/inquiry-768.png)、[1440px](E:/破甲工作区/packrivo-backup/deploy/verification-28l/inquiry-1440.png)。

## 2. P0：首饰页裸露样式与移动布局

本地 native `post_content` 中存在两段无 style 标签的 CSS：`#jewelry-supplier-references ...` 与 `.packrivo-table-wrap ...`；第二段已包含原有的手机纵向卡片样式。子主题只在确认的片段上恢复 `<style>` 标签，不改原 CSS 文本、不改数据库；已在 style 内的片段不嵌套重复标签。恢复后的手机表格按原规则分卡，桌面保持 table。移动卡片 `data-label` 复用现有 11 语种字典，不新增或改写译文。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-layout.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-table.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-languages.mjs
```

原样输出：

```text
/ {"status":200,"scrollWidth":375,"viewport":375,"leakedCss":false,"heading":"Custom packaging for jewelry, candles & gifts.","tableCells":0,"pass":true}
/products/ {"status":200,"scrollWidth":375,"viewport":375,"leakedCss":false,"heading":"Every structure we can source","tableCells":0,"pass":true}
/jewelry-boxes/ {"status":200,"scrollWidth":375,"viewport":375,"leakedCss":false,"heading":"Custom Jewelry Boxes With Logo for Your Collection","tableCells":18,"pass":true}
/ar/jewelry-boxes/ {"status":200,"scrollWidth":375,"viewport":375,"leakedCss":false,"heading":"علب مجوهرات مخصصة بشعارك لمجموعتك","tableCells":18,"pass":true}
/product-pillow-jewelry-box/ {"status":200,"scrollWidth":375,"viewport":375,"leakedCss":false,"heading":"Custom Pillow Jewelry Boxes","tableCells":0,"pass":true}
/get-a-quote/ {"status":200,"scrollWidth":375,"viewport":375,"leakedCss":false,"heading":"","tableCells":0,"pass":true}
PASS all 6 pages
{"width":375,"display":"block","cells":19,"allCellTextUnchanged":true,"scrollWidth":375}
{"width":768,"display":"table","cells":19,"allCellTextUnchanged":true,"scrollWidth":768}
{"width":1440,"display":"table","cells":19,"allCellTextUnchanged":true,"scrollWidth":1440}
ar data-label=["الاستخدام والمدخلات","سؤال العينة"]
PASS: all table cell text preserved, desktop remains table, Arabic card labels use existing translations
ar inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
de inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
es inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
fr inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
it inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
ja inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
ko inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
nl inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
pt inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
zh-CN inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
zh-TW inquiry=200 form=1 selected=pillow-jewelry-box jewelry=200 localized_labels=2
PASS 11 languages, no form submission
```

改前英文和阿语首饰页分别为 650px；最终六页 375px 均无页面级横向溢出及裸露 CSS，且核对首饰页标题和表格存在，避免空页面被误判为通过。19 个 caption/表头/单元格文字逐一与改前相同；375px 显示为块状，768/1440px 保持表格。完整改前/改后输出见 `layout-before.txt`、`layout-after.txt`。

## 3. P1：报价页手机端和价格

手机端当前步骤及适用的 Continue 按钮先于完整摘要，摘要保留全部原有字段和价格。深色卡片价格显式使用浅色，说明文字对比度提高；页面文案不变。对没有 27c style 映射、但带 product 的入口，不再默认选邮寄盒并显示不相干价格：先不选结构并隐藏估价、禁用后续步骤；买家明确选款后恢复摘要。通用入口和已映射入口原行为保持。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\quote-ui-test.mjs after
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\quote-prices.mjs after
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-engine.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-unmapped.mjs
```

改前逐项输出：

```text
step=1 main_top=790 continue_bottom=1637 summary_top=420 min_contrast=1.599
step=2 main_top=790 continue_bottom=1321 summary_top=420 min_contrast=1.599
step=3 main_top=790 continue_bottom=1588 summary_top=420 min_contrast=1.599
step=4 main_top=790 continue_bottom=1302 summary_top=420 min_contrast=1.599
step=5 main_top=790 continue_bottom=1638 summary_top=420 min_contrast=1.599
step1 card measurements: [{"tag":"P","class":"pkq-label","text":"LIVE ESTIMATE","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":10,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"P","class":"pkq-price","text":"$7.45","color":"rgb(84, 96, 89)","background":"rgb(13, 72, 54)","fontSize":33.6,"bold":true,"ratio":1.599,"threshold":3},{"tag":"P","class":"pkq-note","text":"per unit · 500 units","color":"rgba(253, 252, 248, 0.7)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":5.9,"threshold":4.5},{"tag":"DT","class":"","text":"Style","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Rigid box","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Size","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"30 × 22 × 12 cm","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Material","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Rigid greyboard 2 mm","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Print","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Full-colour CMYK","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Finish","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Matte varnish","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Order total","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"$3,724.3","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"P","class":"pkq-fine","text":"Ex-works. Freight quoted separately. Setup amortised into the unit price.","color":"rgba(253, 252, 248, 0.55)","background":"rgb(13, 72, 54)","fontSize":11,"bold":false,"ratio":4.277,"threshold":4.5}]
failures=15
FAIL step1: choices/continue bottom=1637 exceeds summary top=420
FAIL step1: P.pkq-price 1.599:1 < 3:1 text=$7.45
FAIL step1: P.pkq-fine 4.277:1 < 4.5:1 text=Ex-works. Freight quoted separately. Setup amortised into the unit price.
FAIL step2: choices/continue bottom=1321 exceeds summary top=420
FAIL step2: P.pkq-price 1.599:1 < 3:1 text=$7.45
FAIL step2: P.pkq-fine 4.277:1 < 4.5:1 text=Ex-works. Freight quoted separately. Setup amortised into the unit price.
FAIL step3: choices/continue bottom=1588 exceeds summary top=420
FAIL step3: P.pkq-price 1.599:1 < 3:1 text=$7.45
FAIL step3: P.pkq-fine 4.277:1 < 4.5:1 text=Ex-works. Freight quoted separately. Setup amortised into the unit price.
FAIL step4: choices/continue bottom=1302 exceeds summary top=420
FAIL step4: P.pkq-price 1.599:1 < 3:1 text=$7.45
FAIL step4: P.pkq-fine 4.277:1 < 4.5:1 text=Ex-works. Freight quoted separately. Setup amortised into the unit price.
FAIL step5: choices/continue bottom=1638 exceeds summary top=420
FAIL step5: P.pkq-price 1.599:1 < 3:1 text=$7.45
FAIL step5: P.pkq-fine 4.277:1 < 4.5:1 text=Ex-works. Freight quoted separately. Setup amortised into the unit price.
```

改后逐项输出：

```text
step=1 main_top=420 continue_bottom=1267 summary_top=1310 min_contrast=4.779
step=2 main_top=420 continue_bottom=952 summary_top=995 min_contrast=4.779
step=3 main_top=420 continue_bottom=1218 summary_top=1261 min_contrast=4.779
step=4 main_top=420 continue_bottom=932 summary_top=975 min_contrast=4.779
step=5 main_top=420 continue_bottom=1268 summary_top=1311 min_contrast=4.779
step1 card measurements: [{"tag":"P","class":"pkq-label","text":"LIVE ESTIMATE","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":10,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"P","class":"pkq-price","text":"$7.45","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":33.6,"bold":true,"ratio":10.242,"threshold":3},{"tag":"P","class":"pkq-note","text":"per unit · 500 units","color":"rgba(253, 252, 248, 0.7)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":5.9,"threshold":4.5},{"tag":"DT","class":"","text":"Style","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Rigid box","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Size","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"30 × 22 × 12 cm","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Material","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Rigid greyboard 2 mm","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Print","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Full-colour CMYK","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Finish","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"Matte varnish","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"DT","class":"","text":"Order total","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":4.779,"threshold":4.5},{"tag":"DD","class":"","text":"$3,724.3","color":"rgb(253, 252, 248)","background":"rgb(13, 72, 54)","fontSize":12.5,"bold":false,"ratio":10.242,"threshold":4.5},{"tag":"P","class":"pkq-fine","text":"Ex-works. Freight quoted separately. Setup amortised into the unit price.","color":"rgba(253, 252, 248, 0.6)","background":"rgb(13, 72, 54)","fontSize":11,"bold":false,"ratio":4.779,"threshold":4.5}]
failures=0
PASS mobile order and contrast, five steps
(node:8740) [MODULE_TYPELESS_PACKAGE_JSON] Warning: Module type of file:///E:/%E7%A0%B4%E7%94%B2%E5%B7%A5%E4%BD%9C%E5%8C%BA/packrivo-v2/lib/pricing.ts is not specified and it doesn't parse as CommonJS.
Reparsing as ES module because module syntax was detected. This incurs a performance overhead.
To eliminate this warning, add "type": "module" to \\?\E:\破甲工作区\packrivo-v2\package.json.
(Use `node --trace-warnings ...` to show where the warning was created)
rigid qty=500 TS=2.83/1417.43 PHP=2.83/1417.43 PASS
mailer qty=500 TS=2.48/1239.04 PHP=2.48/1239.04 PASS
sticker qty=500 TS=0.98/488.77 PHP=0.98/488.77 PASS
shipping qty=500 TS=2.31/1156.69 PHP=2.31/1156.69 PASS
PASS 4 cases: TS priceQuote equals PHP pricing engine
{"browserPriceFunctionSHA256":"503f53649217efeffdd1490c6ea40a3347a46da30661b4f9cdf684ae8f5ba108","priceCasesUnchanged":4,"engineSourceFilesUnchanged":3,"knownSummaryStepsUnchanged":5}
PASS price calculation byte-identical, four TS/PHP results and five known-style summaries unchanged
before_choice {"href":"/get-a-quote/?style=&qty=500&product=product-shipping-document-window-pouch","selected":null,"asideVisible":false,"continueDisabled":true,"product":"product-shipping-document-window-pouch"}
after_choice {"selected":"Mailer box","asideVisible":true,"continueDisabled":false}
PASS unmapped product requires explicit buyer style choice; no form submission
```

价格文字对比度从 1.599:1 到 10.242:1，卡片普通文字最小 4.779:1；五步摘要文字逐字相同。四组价格前后和 TS/PHP 一致，浏览器 `price()` 计算函数 1,099 字节保持不变，TS/PHP/定价 JSON 文件哈希不变。JS 全文件因新增未选款保护而变化，不声称其全文件未变。插件版本仅由 1.0.0 升至 1.0.1 以更新 CSS/JS 缓存版本，价格与页面文字不变：

```text
css_v1_0_1=True js_v1_0_1=True
```

## 4. P1：报价按钮去向

子主题注入现有 27c style 映射，脚本仅改链接 href，不改文字或价格；英文指向 `/get-a-quote/`，有产品时带 style、qty=500、product；11 语种指向各自 `/inquiry/` 并保留产品 slug。无映射 style 保持空值，报价器要求买家选款，不猜测。9 个不含 `product-` 前缀的旧产品使用现有产品 meta 识别。运行时动态 sticky 也覆盖；脚本未运行时原服务器 HTML 仍保留旧 href，这是此方案的适用边界。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-inventory.mjs after
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-validate.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-eleven.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-legacy.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-quote-flow.mjs
```

原样输出：

```text
{"pages":9,"links":159,"labelsUnchanged":159,"productCardLinks":87,"non200":0}
PASS: en quote path; ar/de localized inquiry; product context, style, quantity and existing button text checked
ar /ar/inquiry/?product=pillow-jewelry-box 200
de /de/inquiry/?product=pillow-jewelry-box 200
es /es/inquiry/?product=pillow-jewelry-box 200
fr /fr/inquiry/?product=pillow-jewelry-box 200
it /it/inquiry/?product=pillow-jewelry-box 200
ja /ja/inquiry/?product=pillow-jewelry-box 200
ko /ko/inquiry/?product=pillow-jewelry-box 200
nl /nl/inquiry/?product=pillow-jewelry-box 200
pt /pt/inquiry/?product=pillow-jewelry-box 200
zh-CN /zh-CN/inquiry/?product=pillow-jewelry-box 200
zh-TW /zh-TW/inquiry/?product=pillow-jewelry-box 200
PASS 11 language sticky quote targets /inquiry and local HTTP 200
en /get-a-quote/?style=&qty=500&product=clear-display-box 200
ar /ar/inquiry/?product=clear-display-box 200
de /de/inquiry/?product=clear-display-box 200
PASS legacy non-prefix product retains product context, unknown style not guessed
{"href":"/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box","status":200,"selected":"Folding carton","quote":"LIVE ESTIMATE $1.73 per unit · 500 units Style Folding carton Size 30 × 22 × 12 cm Material Kraft E-flute Print Full-colour CMYK Finish Matte varnish Order total $865.98 Ex-works. Freight quoted separately. Setup amortised into the unit price.","product":"product-pillow-jewelry-box","qty":"500","style":"carton"}
PASS product CTA opens quote with carton / 500 / product
```

以下为 en/ar/de × 首页、产品页、产品目录的全部 159 个相关链接，含按钮原文、位置、改前/改后 href 和最终 HTTP：

| 语言 | 页面 | 位置 | 按钮原文/译文 | 改前 href | 改后 href | 最终 HTTP |
| --- | --- | --- | --- | --- | --- | ---: |
| en | / | header | Get a quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | header | Build a quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | page | Build a quote → | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | 200 |
| en | / | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-lid-and-base-jewelry-box` | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-lid-and-base-jewelry-box` | 200 |
| en | / | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-jewelry-box-with-insert` | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-jewelry-box-with-insert` | 200 |
| en | / | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-window-lid-rigid-gift-box` | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-window-lid-rigid-gift-box` | 200 |
| en | / | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=mailer&qty=500&product=product-corrugated-roll-end-hinged-lid` | `http://localhost:8081/get-a-quote/?style=mailer&qty=500&product=product-corrugated-roll-end-hinged-lid` | 200 |
| en | / | page | Open the quote builder | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | page | Ask a question | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | page | Ask a specific question → | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | page | Build a quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | / | footer | Get a Quote | `http://localhost:8081/inquiry/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | /product-pillow-jewelry-box/ | header | Get a quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /product-pillow-jewelry-box/ | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /product-pillow-jewelry-box/ | header | Build a quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /product-pillow-jewelry-box/ | page | Get a Quote | `https://www.packrivo.com/inquiry/?product=pillow-jewelry-box` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /product-pillow-jewelry-box/ | page | Get a Quote | `https://www.packrivo.com/inquiry/?product=pillow-jewelry-box` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /product-pillow-jewelry-box/ | page | Get a Quote | `https://www.packrivo.com/inquiry/?product=pillow-jewelry-box` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /product-pillow-jewelry-box/ | footer | Get a Quote | `http://localhost:8081/inquiry/` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /product-pillow-jewelry-box/ | sticky | Get a quote · MOQ 500 | `http://localhost:8081/inquiry/?product=pillow-jewelry-box` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-pillow-jewelry-box` | 200 |
| en | /products/ | header | Get a quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | /products/ | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | /products/ | header | Build a quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | /products/ | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=shipping&qty=500&product=product-auto-bottom-slotted-outer-carton` | `http://localhost:8081/get-a-quote/?style=shipping&qty=500&product=product-auto-bottom-slotted-outer-carton` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-auto-lock-bottom-carton` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-auto-lock-bottom-carton` | 200 |
| en | /products/ | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-book-style-rigid-presentation-box` | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-book-style-rigid-presentation-box` | 200 |
| en | /products/ | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-bookend-folding-carton` | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-bookend-folding-carton` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-corrugated-bottle-gift-carrier` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-corrugated-bottle-gift-carrier` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-cube-carry-carton` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-cube-carry-carton` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-rigid-chipboard-divider` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-rigid-chipboard-divider` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-circle-roll-labels` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-circle-roll-labels` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=clear-display-box` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=clear-display-box` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-coated-labels` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-coated-labels` | 200 |
| en | /products/ | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-collapsible-magnetic-gift-box` | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-collapsible-magnetic-gift-box` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-corrugated-book-wrap-mailer` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-corrugated-book-wrap-mailer` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-corrugated-divider` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-corrugated-divider` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-paired-corrugated-end-caps` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-paired-corrugated-end-caps` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-corrugated-gusset-folder` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-corrugated-gusset-folder` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-double-wall-tray-lid` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-double-wall-tray-lid` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-counter-display-carton` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-counter-display-carton` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-die-cut-insert` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-die-cut-insert` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-die-cut-handle-paper-bag` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-die-cut-handle-paper-bag` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-flip-out-dispenser-carton` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-flip-out-dispenser-carton` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-four-corner-display-tray` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-four-corner-display-tray` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-shipping-document-window-pouch` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-shipping-document-window-pouch` | 200 |
| en | /products/ | product-card | Get a price → | `http://localhost:8081/get-a-quote/?product=product-double-wall-frame-tray-lid` | `http://localhost:8081/get-a-quote/?style=&qty=500&product=product-double-wall-frame-tray-lid` | 200 |
| en | /products/ | product-card | Configure → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | 200 |
| en | /products/ | page | Send a brief | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | /products/ | page | Start from a price | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | /products/ | footer | Get a Quote | `http://localhost:8081/inquiry/` | `http://localhost:8081/get-a-quote/` | 200 |
| en | /products/ | sticky | Get a quote · MOQ 500 | `http://localhost:8081/inquiry/` | `http://localhost:8081/get-a-quote/` | 200 |
| ar | / | header | اطلب عرض سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | header | أنشئ عرض سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | page | أنشئ عرض سعر → | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | `http://localhost:8081/ar/inquiry/?product=drawer-jewelry-box` | 200 |
| ar | / | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-lid-and-base-jewelry-box` | `http://localhost:8081/ar/inquiry/?product=lid-and-base-jewelry-box` | 200 |
| ar | / | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-jewelry-box-with-insert` | `http://localhost:8081/ar/inquiry/?product=jewelry-box-with-insert` | 200 |
| ar | / | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-window-lid-rigid-gift-box` | `http://localhost:8081/ar/inquiry/?product=window-lid-rigid-gift-box` | 200 |
| ar | / | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=mailer&qty=500&product=product-corrugated-roll-end-hinged-lid` | `http://localhost:8081/ar/inquiry/?product=corrugated-roll-end-hinged-lid` | 200 |
| ar | / | page | افتح أداة إعداد عرض السعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | page | اسأل عن تفصيل | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | page | اسأل سؤالًا محددًا → | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | page | أنشئ عرض سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | / | footer | احصل على عرض سعر | `http://localhost:8081/inquiry/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | /product-pillow-jewelry-box/ | header | اطلب عرض سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /product-pillow-jewelry-box/ | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /product-pillow-jewelry-box/ | header | أنشئ عرض سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /product-pillow-jewelry-box/ | page | احصل على عرض سعر | `https://www.packrivo.com/ar/inquiry/?product=product-pillow-jewelry-box` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /product-pillow-jewelry-box/ | page | احصل على عرض سعر | `https://www.packrivo.com/ar/inquiry/` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /product-pillow-jewelry-box/ | page | احصل على عرض سعر | `https://www.packrivo.com/ar/inquiry/?product=product-pillow-jewelry-box` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /product-pillow-jewelry-box/ | footer | احصل على عرض سعر | `http://localhost:8081/inquiry/` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /product-pillow-jewelry-box/ | sticky | اطلب عرض سعر · الحد الأدنى 500 | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | `http://localhost:8081/ar/inquiry/?product=pillow-jewelry-box` | 200 |
| ar | /products/ | header | اطلب عرض سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | /products/ | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | /products/ | header | أنشئ عرض سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | /products/ | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=shipping&qty=500&product=product-auto-bottom-slotted-outer-carton` | `http://localhost:8081/ar/inquiry/?product=auto-bottom-slotted-outer-carton` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-auto-lock-bottom-carton` | `http://localhost:8081/ar/inquiry/?product=auto-lock-bottom-carton` | 200 |
| ar | /products/ | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-book-style-rigid-presentation-box` | `http://localhost:8081/ar/inquiry/?product=book-style-rigid-presentation-box` | 200 |
| ar | /products/ | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-bookend-folding-carton` | `http://localhost:8081/ar/inquiry/?product=bookend-folding-carton` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-corrugated-bottle-gift-carrier` | `http://localhost:8081/ar/inquiry/?product=corrugated-bottle-gift-carrier` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-cube-carry-carton` | `http://localhost:8081/ar/inquiry/?product=cube-carry-carton` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-rigid-chipboard-divider` | `http://localhost:8081/ar/inquiry/?product=rigid-chipboard-divider` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-circle-roll-labels` | `http://localhost:8081/ar/inquiry/?product=circle-roll-labels` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=clear-display-box` | `http://localhost:8081/ar/inquiry/?product=clear-display-box` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-coated-labels` | `http://localhost:8081/ar/inquiry/?product=coated-labels` | 200 |
| ar | /products/ | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-collapsible-magnetic-gift-box` | `http://localhost:8081/ar/inquiry/?product=collapsible-magnetic-gift-box` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-corrugated-book-wrap-mailer` | `http://localhost:8081/ar/inquiry/?product=corrugated-book-wrap-mailer` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-corrugated-divider` | `http://localhost:8081/ar/inquiry/?product=corrugated-divider` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-paired-corrugated-end-caps` | `http://localhost:8081/ar/inquiry/?product=paired-corrugated-end-caps` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-corrugated-gusset-folder` | `http://localhost:8081/ar/inquiry/?product=corrugated-gusset-folder` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-double-wall-tray-lid` | `http://localhost:8081/ar/inquiry/?product=double-wall-tray-lid` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-counter-display-carton` | `http://localhost:8081/ar/inquiry/?product=counter-display-carton` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-die-cut-insert` | `http://localhost:8081/ar/inquiry/?product=die-cut-insert` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-die-cut-handle-paper-bag` | `http://localhost:8081/ar/inquiry/?product=die-cut-handle-paper-bag` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-flip-out-dispenser-carton` | `http://localhost:8081/ar/inquiry/?product=flip-out-dispenser-carton` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-four-corner-display-tray` | `http://localhost:8081/ar/inquiry/?product=four-corner-display-tray` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-shipping-document-window-pouch` | `http://localhost:8081/ar/inquiry/?product=shipping-document-window-pouch` | 200 |
| ar | /products/ | product-card | اطلب عرض سعر → | `http://localhost:8081/get-a-quote/?product=product-double-wall-frame-tray-lid` | `http://localhost:8081/ar/inquiry/?product=double-wall-frame-tray-lid` | 200 |
| ar | /products/ | product-card | اختر المواصفات → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | `http://localhost:8081/ar/inquiry/?product=drawer-jewelry-box` | 200 |
| ar | /products/ | page | أرسل الطلب | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | /products/ | page | ابدأ من سعر | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | /products/ | footer | احصل على عرض سعر | `http://localhost:8081/inquiry/` | `http://localhost:8081/ar/inquiry/` | 200 |
| ar | /products/ | sticky | اطلب عرض سعر · الحد الأدنى 500 | `http://localhost:8081/ar/inquiry/` | `http://localhost:8081/ar/inquiry/` | 200 |
| de | / | header | Angebot einholen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | header | Angebot erstellen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | page | Angebot erstellen → | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | `http://localhost:8081/de/inquiry/?product=drawer-jewelry-box` | 200 |
| de | / | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-lid-and-base-jewelry-box` | `http://localhost:8081/de/inquiry/?product=lid-and-base-jewelry-box` | 200 |
| de | / | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-jewelry-box-with-insert` | `http://localhost:8081/de/inquiry/?product=jewelry-box-with-insert` | 200 |
| de | / | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-window-lid-rigid-gift-box` | `http://localhost:8081/de/inquiry/?product=window-lid-rigid-gift-box` | 200 |
| de | / | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=mailer&qty=500&product=product-corrugated-roll-end-hinged-lid` | `http://localhost:8081/de/inquiry/?product=corrugated-roll-end-hinged-lid` | 200 |
| de | / | page | Angebotsrechner öffnen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | page | Frage stellen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | page | Konkrete Frage stellen → | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | page | Angebot erstellen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | / | footer | Angebot anfordern | `http://localhost:8081/inquiry/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | /product-pillow-jewelry-box/ | header | Angebot einholen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /product-pillow-jewelry-box/ | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /product-pillow-jewelry-box/ | header | Angebot erstellen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /product-pillow-jewelry-box/ | page | Angebot anfordern | `https://www.packrivo.com/de/inquiry/?product=product-pillow-jewelry-box` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /product-pillow-jewelry-box/ | page | Angebot anfordern | `https://www.packrivo.com/de/inquiry/` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /product-pillow-jewelry-box/ | page | Angebot anfordern | `https://www.packrivo.com/de/inquiry/?product=product-pillow-jewelry-box` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /product-pillow-jewelry-box/ | footer | Angebot anfordern | `http://localhost:8081/inquiry/` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /product-pillow-jewelry-box/ | sticky | Angebot anfragen · Mindestmenge 500 | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | `http://localhost:8081/de/inquiry/?product=pillow-jewelry-box` | 200 |
| de | /products/ | header | Angebot einholen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | /products/ | header | Instant quote | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | /products/ | header | Angebot erstellen | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | /products/ | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=shipping&qty=500&product=product-auto-bottom-slotted-outer-carton` | `http://localhost:8081/de/inquiry/?product=auto-bottom-slotted-outer-carton` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-auto-lock-bottom-carton` | `http://localhost:8081/de/inquiry/?product=auto-lock-bottom-carton` | 200 |
| de | /products/ | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-book-style-rigid-presentation-box` | `http://localhost:8081/de/inquiry/?product=book-style-rigid-presentation-box` | 200 |
| de | /products/ | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=carton&qty=500&product=product-bookend-folding-carton` | `http://localhost:8081/de/inquiry/?product=bookend-folding-carton` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-corrugated-bottle-gift-carrier` | `http://localhost:8081/de/inquiry/?product=corrugated-bottle-gift-carrier` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-cube-carry-carton` | `http://localhost:8081/de/inquiry/?product=cube-carry-carton` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-rigid-chipboard-divider` | `http://localhost:8081/de/inquiry/?product=rigid-chipboard-divider` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-circle-roll-labels` | `http://localhost:8081/de/inquiry/?product=circle-roll-labels` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=clear-display-box` | `http://localhost:8081/de/inquiry/?product=clear-display-box` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-coated-labels` | `http://localhost:8081/de/inquiry/?product=coated-labels` | 200 |
| de | /products/ | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-collapsible-magnetic-gift-box` | `http://localhost:8081/de/inquiry/?product=collapsible-magnetic-gift-box` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-corrugated-book-wrap-mailer` | `http://localhost:8081/de/inquiry/?product=corrugated-book-wrap-mailer` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-corrugated-divider` | `http://localhost:8081/de/inquiry/?product=corrugated-divider` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-paired-corrugated-end-caps` | `http://localhost:8081/de/inquiry/?product=paired-corrugated-end-caps` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-corrugated-gusset-folder` | `http://localhost:8081/de/inquiry/?product=corrugated-gusset-folder` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-double-wall-tray-lid` | `http://localhost:8081/de/inquiry/?product=double-wall-tray-lid` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-counter-display-carton` | `http://localhost:8081/de/inquiry/?product=counter-display-carton` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-die-cut-insert` | `http://localhost:8081/de/inquiry/?product=die-cut-insert` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-die-cut-handle-paper-bag` | `http://localhost:8081/de/inquiry/?product=die-cut-handle-paper-bag` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-flip-out-dispenser-carton` | `http://localhost:8081/de/inquiry/?product=flip-out-dispenser-carton` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-four-corner-display-tray` | `http://localhost:8081/de/inquiry/?product=four-corner-display-tray` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-shipping-document-window-pouch` | `http://localhost:8081/de/inquiry/?product=shipping-document-window-pouch` | 200 |
| de | /products/ | product-card | Preis anfragen → | `http://localhost:8081/get-a-quote/?product=product-double-wall-frame-tray-lid` | `http://localhost:8081/de/inquiry/?product=double-wall-frame-tray-lid` | 200 |
| de | /products/ | product-card | Konfigurieren → | `http://localhost:8081/get-a-quote/?style=rigid&qty=500&product=product-drawer-jewelry-box` | `http://localhost:8081/de/inquiry/?product=drawer-jewelry-box` | 200 |
| de | /products/ | page | Briefing senden | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | /products/ | page | Mit einem Preis starten | `http://localhost:8081/get-a-quote/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | /products/ | footer | Angebot anfordern | `http://localhost:8081/inquiry/` | `http://localhost:8081/de/inquiry/` | 200 |
| de | /products/ | sticky | Angebot anfragen · Mindestmenge 500 | `http://localhost:8081/de/inquiry/` | `http://localhost:8081/de/inquiry/` | 200 |

本地英文 200 依赖早前存在的 27c 测试 MU 覆盖，本轮没有应用新 MU 变更。生产旧报价重定向仍须以后在授权下核验并决定是否应用现有 `deploy/27e-quote-redirect.patch`；不得将本地 200 冒称生产已修复。

## 5. 范围保护与交付包

```powershell
E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-protected.py
E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe E:\破甲工作区\packrivo-backup\scripts\ui-28l\build-zips.py
```

```text
parent_plugin_MU_files=107 changed=0
paths_unchanged=True approved_translation_sha256=edcadc0b117fa3a564c18c25c5b52b96d1917cf72e9777a6f80cd831d2792b3f
packrivo-home-v4 files=38 sha256=e8781440d1ed6de01a6df1336834dec49987cb4f576dd1ab7866fbb57b823c2e source_backup_zip=identical demo_excluded=False
packrivo-quote files=8 sha256=e1e09498f439108fe9db2323b6a013d36c58d6de64617a763175995ca1aa1e60 source_backup_zip=identical demo_excluded=True
PASS packages checked byte-for-byte; quote demo and its local data excluded
```

保护检查：父主题、其他插件和 MU 的 107 个受保护文件未变；路径字典和已审翻译不变。子主题包包含 38 个文件；报价插件包包含 8 个运行文件，排除了 demo 及其本地数据。两个包均与工作源和备份源逐文件核对。`deploy/README.md` 已更新阻断项和上线前事项。没有部署、上传服务器或提交询盘。

独立只读审查在无映射产品的默认估价上发现风险；上述显式选择保护已修正并复测，未发现剩余 Critical / Important 问题。完整复核见 `verification-28l/independent-review.txt`。首屏露图、统一 44px 和筛选外观仍留待后续 P2，不在本轮改动。

提交前 `git diff --cached --check` 对原样保存的改前 `price-function-before.txt` 提示末尾空行；该空行是 1,099 字节原始函数快照的一部分，未删改。排除该原样证据后其余文件检查为 0，再以仅忽略 EOF 空行的规则检查全部暂存文件也为 0。
## 提交与推送

主交付提交 `0aedec2`。以下为首次推送原样输出；本回执随后作为文档提交同步推送。

```text
To https://github.com/jiaxiangli477-stack/packrivo-backup.git
   cb24981..0aedec2  main -> main
```
