# CODEX-RESULT-2026-10-27j

执行日期：2026-10-28（America/Los_Angeles）。任务起点：备份仓库 main / dc3cf19。

任务要求的子主题修改、11 语种 H1 修复、漏译交接和本地截图已完成。生产没有部署，没有修改 www.packrivo.com。本轮没有翻译新文案或合并字典。

正式语言路径存在既有范围限制：文章与 `/get-a-quote/` 共 22 次返回 404。已通过 WordPress 原生查询地址只读加载同一实际文档补查，保留正式路径的 404 证据；本轮没有修路由或扩大到 MU / 路径配置。

## 1. 四处指定替换和范围核对

代码只修改 `E:\破甲工作区\packrivo-wp\packrivo-home-v4\front-page.php`，同步到备份仓库同名子主题。下面是第 1 节四处文案的完整 diff；第 3 节 H1 适配单独说明。回收率、Use 列、说明句，以及站长确认的全部经营说法均保留。

```diff
--- a/front-page.php
+++ b/front-page.php
@@ -57,7 +57,7 @@
     <div>
       <p class="pk-eyebrow">Custom packaging · MOQ 500 · 11 languages</p>
       <h1 class="pk-display">Custom packaging for <span class="pk-accent">jewelry, candles</span> &amp; gifts.</h1>
-      <p class="pk-lede">Sampling, production and freight handled end to end. Reference prices published, dielines included, and a person on the other end of the quote — not a form that goes nowhere.</p>
+      <p class="pk-lede">Sampling, production and freight handled end to end. Reference prices published, a cutting template with every quote, and a person on the other end of the quote — not a form that goes nowhere.</p>
       <div class="pk-hero-cta">
         <a class="pk-btn" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Build a quote <span aria-hidden="true">→</span></a>
         <a class="pk-btn-ghost" href="<?php echo esc_url(home_url('/samples/')); ?>">Order a sample kit</a>
@@ -194,9 +194,9 @@
     <table class="pk-table pk-table-plain">
       <thead><tr><th>Board</th><th>Recycled</th><th>Use</th></tr></thead>
       <tbody>
-        <tr><td>Kraft E-flute</td><td>70–90%</td><td>Mailers, shippers</td></tr>
-        <tr><td>White E-flute</td><td>60%</td><td>Printed retail cartons</td></tr>
-        <tr><td>Greyboard 2 mm</td><td>100%</td><td>Rigid, wrapped</td></tr>
+        <tr><td>Kraft corrugated</td><td>70–90%</td><td>Mailers, shippers</td></tr>
+        <tr><td>White corrugated</td><td>60%</td><td>Printed retail cartons</td></tr>
+        <tr><td>Rigid greyboard</td><td>100%</td><td>Rigid, wrapped</td></tr>
         <tr><td>Molded pulp</td><td>100%</td><td>Inserts, trays</td></tr>
       </tbody>
     </table>
```

源审计固定比较 dc3cf19 的 Git 基线，按原 Windows CRLF 工作副本重建，再仅应用四处替换和 H1 桥接。结果必须与当前工作文件逐字节相同；因此其余经营文字、数字、标点、图片标注均未改。英文 HTTP 响应也检查服务费、交期、100 个起、美欧 DDP/FOB、印张、每单材料声明和回收率等原句。

命令及原样输出：

```powershell
E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe E:\破甲工作区\packrivo-backup\scripts\homepage-27j\verify-source.py
```

```text
{"approved_phrase_replacements": 4, "H1_bridge": true, "other_source_bytes_unchanged": true, "synced_theme_files": 36, "zip_matching_files": 36, "zip_sha256": "d94407937960f52d0a7a4376dbda28ea0dcbafe5483c156eadd7db2bab242baa"}
PASS: only four approved phrases plus existing-dictionary H1 bridge; all other business copy unchanged; backup and ZIP match working child theme
```

```powershell
rg -n -i 'E-flute|\bmm\b|dieline' E:\破甲工作区\packrivo-wp\packrivo-home-v4\front-page.php
```

```text
186:      <?php if (isset($by_qty[500],$by_qty[5000]) && $by_qty[500] > 0) : ?><p class="pk-fine">Indicative, excludes freight. At 5,000 pieces the unit price is <?php echo esc_html(round(100 * (1 - $by_qty[5000]/$by_qty[500]))); ?>% lower than at 500. Final price confirmed after dieline review.</p><?php endif; ?>
```

剩余只有价格说明中的 `dieline review`。该句不属于表内四处，任务要求其他句子不动，因此保留。`E-flute`、独立尺寸单位 `mm` 均无命中；`cm` 按任务不计。

## 2. 浏览器漏译清单

交接文件：`E:\破甲工作区\packrivo-backup\dsh-in\untranslated-27j.json`。207 个去重英文键：block 82、text 88、attr 37。JSON 严格使用 `where / kind / missing` 三个字段；`missing` 仅含实际观察到仍英文的语种。同一句兼用于属性和节点时主清单 kind 优先 attr，完整位置类型见 `scan-occurrences.json`。

浏览器使用现有 Playwright + Chrome，仅访问 localhost:8081。以修改后的英文页面 DOM 为参照，在 DOMContentLoaded 后等待前端翻译和资源，再检查 1280/375px、FAQ 展开、导航展开状态。以具体文本节点和 alt/placeholder/aria-label 的源文精确匹配判断残留，未把所有拉丁字符当英文。排除文章标题、正文、摘要、文章目录原文和封面标题 alt；也排除纯品牌、价格数字、尺寸/单位、联系方式及本地语言同形词。文章分类、阅读时间、页面导航、CTA 属于界面，继续计入。详见脚本 README 和排除记录。

77 次正式语言 URL 检查中，55 次 200；其余 22 次 404。没有把错误页当成报价/文章页面清点。随后补查：

- 文章：`http://localhost:8081/{lang}/?p=2937`，断言实际 body 含 `postid-2937`。
- 报价页：`http://localhost:8081/{lang}/?page_id=3220`，断言实际 body 含 `page-id-3220`。

补查 22 次均 200，未改配置、文件、数据库；原始正式路径状态仍保留在统计 JSON 的 status 字段，fallback 字段记录实际补查 URL、状态和 body ID。下面两行的计数分别来自这些原生查询地址，不代表正式语言 URL 已修复。

统计按每个页面/语种内不同英文原文计数（跨视口、交互状态和重复位置去重）：

| 页面 | ar | de | es | fr | it | ja | ko | nl | pt | zh-CN | zh-TW |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| / | 84 | 85 | 84 | 87 | 85 | 84 | 84 | 86 | 85 | 84 | 84 |
| /products/ | 55 | 56 | 56 | 58 | 55 | 55 | 55 | 57 | 57 | 55 | 55 |
| /jewelry-boxes/ | 82 | 83 | 83 | 86 | 83 | 82 | 82 | 84 | 84 | 82 | 82 |
| /blog/ | 37 | 38 | 38 | 40 | 37 | 37 | 37 | 39 | 39 | 37 | 37 |
| /2026/10/26/jewelry-pouch-box-customer-handoff/ | 26 | 27 | 27 | 29 | 27 | 26 | 26 | 28 | 28 | 26 | 26 |
| /product-pillow-jewelry-box/ | 34 | 35 | 35 | 37 | 35 | 34 | 34 | 36 | 36 | 34 | 34 |
| /get-a-quote/ | 22 | 23 | 23 | 25 | 22 | 22 | 22 | 24 | 24 | 22 | 22 |

命令（按顺序执行；均只写本地证据文件）：

```powershell
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\scan.mjs
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\supplement.mjs
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\finalize-inventory.mjs
```

首轮日志结尾原样输出（初始清单包含需过滤的价格/单位及一项文章 alt；以下均已在最终清单中排除）：

```text
INVENTORY 217 VALID 55 UNAVAILABLE 22
SCREENSHOT home-en-375.png
SCREENSHOT home-en-768.png
SCREENSHOT home-en-1280.png
```

补查结尾原样输出（仍是最终数值/单位过滤前）：

```text
QUERY zh-TW /2026/10/26/jewelry-pouch-box-customer-handoff/ 200 postid-2937 29
QUERY zh-TW /get-a-quote/ 200 page-id-3220 23
FINAL INVENTORY 219 CANONICAL 200 55 CANONICAL 404 WITH VERIFIED QUERY FALLBACK 22
```

最终生成与审计原样输出：

```text
{"uniqueEnglishStrings":207,"pages":7,"languages":11,"canonical200":55,"canonical404":22,"verifiedNativeQuery200":22,"byKind":{"block":82,"text":88,"attr":37},"excludedOccurrences":243}
page,ar,de,es,fr,it,ja,ko,nl,pt,zh-CN,zh-TW
"/",84,85,84,87,85,84,84,86,85,84,84
"/products/",55,56,56,58,55,55,55,57,57,55,55
"/jewelry-boxes/",82,83,83,86,83,82,82,84,84,82,82
"/blog/",37,38,38,40,37,37,37,39,39,37,37
"/2026/10/26/jewelry-pouch-box-customer-handoff/",26,27,27,29,27,26,26,28,28,26,26
"/product-pillow-jewelry-box/",34,35,35,37,35,34,34,36,36,34,34
"/get-a-quote/",22,23,23,25,22,22,22,24,24,22,22
PASS: schema, 77 observed page/language surfaces, exact source inventory, no removed phrases or article titles
```

最终清单不含被替换的 hero 旧句和三项旧材料名称，不含文章标题。JSON/CSV/逐节点证据、正式路径状态、原生查询文档 ID、排除项及浏览器全量日志保存在 `deploy/verification-27j/`。

## 3. H1 行内 span 的原因和修复

实际 MU `packrivo-i18n.php` 的 285–301 行 lookup 只归一空白和 HTML 实体，不移除行内标签；449–479 行先按含内部 HTML 的块文本查字典，再按分散文本节点查找。原 H1 带 span，整体与字典完整纯文本键不相等，分片又无法命中完整句子。

只在子主题 H1 调用已有 `packrivo_i18n_lookup('Custom packaging for jewelry, candles & gifts.', ['block', 'text'])`。语言页存在译文时 esc_html 输出整句，译文不带强调 span；英文或没有可用字典函数时回到原有含强调 span 的原文。没有复制翻译，没有改 MU 或字典。

下面脚本对 en 和 11 个语言首页逐个执行 curl 读取原始响应、截取 H1，并对比现有字典；不是浏览器 DOM 的推测值。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\verify-home.mjs
```

```text
en <h1 class="pk-display">Custom packaging for <span class="pk-accent">jewelry, candles</span> &amp; gifts.</h1>
ar <h1 class="pk-display">تغليف مخصص للمجوهرات والشموع والهدايا.</h1>
de <h1 class="pk-display">Kundenspezifische Verpackungen für Schmuck, Kerzen &amp; Geschenke.</h1>
es <h1 class="pk-display">Embalaje personalizado para joyería, velas y regalos.</h1>
fr <h1 class="pk-display">Emballages personnalisés pour bijoux, bougies et cadeaux.</h1>
it <h1 class="pk-display">Imballaggi personalizzati per gioielli, candele e regali.</h1>
ja <h1 class="pk-display">ジュエリー、キャンドル、ギフト用のオーダーメイド包装。</h1>
ko <h1 class="pk-display">주얼리, 캔들 및 선물용 맞춤 제작 포장.</h1>
nl <h1 class="pk-display">Op maat gemaakte verpakkingen voor sieraden, kaarsen en geschenken.</h1>
pt <h1 class="pk-display">Embalagens personalizadas para joias, velas e presentes.</h1>
zh-CN <h1 class="pk-display">定制首饰、蜡烛和礼品包装。</h1>
zh-TW <h1 class="pk-display">珠寶、蠟燭與禮品客製包裝。</h1>
PASS: 11 existing H1 translations; English emphasis markup unchanged; confirmed claims preserved
```

## 4. 本地验收、范围保护和上线包

```powershell
D:\Codex\packrivo-review-2026-10-18\full-remediation\runtime\native\php-8.3.31\php.exe -l E:\破甲工作区\packrivo-wp\packrivo-home-v4\front-page.php
```

```text
No syntax errors detected in E:\破甲工作区\packrivo-wp\packrivo-home-v4\front-page.php
```

```powershell
E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe E:\破甲工作区\packrivo-backup\scripts\homepage-27j\verify-protected.py
```

```text
parent_plugin_MU_files=107 changed=0
dictionary_diff_from_dc3cf19_exit=0
```

英文截图均由真实页面 fullPage 直接取得，没有注入样式或隐藏经营文字：

- [375px](E:/破甲工作区/packrivo-backup/deploy/verification-27j/home-en-375.png)
- [768px](E:/破甲工作区/packrivo-backup/deploy/verification-27j/home-en-768.png)
- [1280px](E:/破甲工作区/packrivo-backup/deploy/verification-27j/home-en-1280.png)

已查看三个视口；保留原生 Cookie 提示。本地媒体副本不完整，截图仍可见部分原有产品图片未加载，未将截图声称为生产媒体完整性验收；本轮仅改指定四处和 H1。

当前子主题 ZIP：`E:\破甲工作区\packrivo-backup\deploy\packrivo-home-v4.zip`，36 个文件与工作源、备份源码一致。SHA-256：`d94407937960f52d0a7a4376dbda28ea0dcbafe5483c156eadd7db2bab242baa`。部署 README 已标记当前包及限制；没有上传服务器或执行包内的上线命令。

独立只读审查最初发现一项文章封面 alt 误收，已排除并复核：207 个键、77 个计数与逐节点记录一致，没有新的 Critical / Important 问题。回执见 `deploy/verification-27j/independent-review.txt`。

现有未完成项继续保留：首页 5/8 张卡片、生产报价重定向补丁、文章和报价页语言路径缺项。正式路径问题需要后续有对应范围的任务，不能用本次查询地址补查冒称修复。

## 提交与推送

主交付和推送回执在实际执行后补入；本段不代表已推送。
