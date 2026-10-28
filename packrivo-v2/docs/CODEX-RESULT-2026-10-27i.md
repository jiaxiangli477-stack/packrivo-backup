# Codex 结果 · 2026-10-27i

## 结论与边界

已完成八个 hub 的本地元数据导入、11 语种主字典及运行时按钮键合并、本地 curl 验证、阿语按钮截图、执行/回滚命令及子主题包准备。没有改写 dsh 定稿，H1、post_title、正文及 URL 均未变；没有修改父主题、插件或 MU 源码，没有部署或修改 www.packrivo.com。

**严格 dsh 一致性存在 5 个保留例外，不能称为 32/32 逐字一致：** 按任务规定保留现有 inquiry 英文描述，以及生产已有的不同 title 译文。32 个响应均为一个 title、一个 description；27 个响应与 dsh 完全一致，5 个保留项及实际值见下表和 `verification-27i/comparison.json`。

## 0. 前置检查：通过

文件 `dsh-out/hubs-27i.json` 存在且可解析，8 个 path × 11 语种、1 条 ui 齐全。README 有 `## 27i`。非 inquiry 的英文与当前 DSH 任务表逐字比对通过。

```text
$ node E:\破甲工作区\.tmp\packrivo-27i\validate.mjs
PASS: pages=8 languages=11 title_checks=88 description_checks=88 ui_translations=11
PASS: approved English unchanged; brand format; <=60 titles; no sourcing terms; numbers preserved; UTF-8 without BOM

$ node E:\破甲工作区\.tmp\packrivo-27i\preflight-import.mjs
Section 0 PASS: 8 paths x 11 languages + 1 UI; approved English exact=7/7; README 27i present
```

前一次行内比对命令因 PowerShell 引号导致表格解析为 0 行，是校验命令错误，不是输入数据不合格；修正为文件脚本后只重跑前置检查，直到通过才开始导入，没有绕过第 0 节。

## 1–2. 每页来源与本地导入

实际来源在导入前通过 WP CLI 字段检查及 curl 原始 HTML 对照，完整数据保存于 `deploy/verification-27i/metadata-sources.json`、`curl-before.json`。

| path | ID | 改前 title 的实际来源 | 改前 description 的实际来源 / 缺口 |
| --- | ---: | --- | --- |
| `/` | 7 | 父主题 `packrivo_output_page_metadata` 读取 `_packrivo_document_title` | 同 callback 读取 `_packrivo_description` |
| `/products/` | 8 | 核心 title 经父主题 `pre_get_document_title` 过滤读取 `_yoast_wpseo_title` | 两个描述字段都存在，但产品工作区移除父 metadata callback；它自己的 fallback 不为 ID 8 输出描述 |
| `/jewelry-boxes/` | 9 | 同上，读取 `_yoast_wpseo_title` | 同上，描述存库但未输出 |
| `/mailer-boxes/` | 21 | 父主题 native metadata callback 读取 `_packrivo_document_title` | 同 callback 读取 `_packrivo_description` |
| `/samples/` | 2884 | 核心 title 经过滤读取 `_yoast_wpseo_title` | 非 native 页面，父 native metadata callback 不输出；字段存在但 HTML 缺描述 |
| `/blog/` | 53 | WP 核心 posts-page 的 post_title，输出 Blog – Packrivo | is_home 归档不进入父主题 singular-page metadata callback；字段存在但未读取 |
| `/about/` | 39 | 父 native metadata callback 读取 `_packrivo_document_title` | 同 callback 读取 `_packrivo_description` |
| `/inquiry/` | 49 | 父 native metadata callback 读取 `_packrivo_document_title` | 同 callback 的实际 `_packrivo_description`，保留原值 |

有关只读源码：父主题 `functions.php:529` 的 `packrivo_output_page_metadata`；父主题 `remediation-functions.php:352` 的 title filter；产品工作区 `class-product-renderer.php` 的 prepare/fallback_metadata。没有把这些文件或 native-pages.json 改掉。当前子主题路径下的八页使用数据库字段，不需要改父主题/插件/JSON 中的定稿字面量；native JSON 不是这些当前输出的权威来源。

为使数据库元数据在归档和描述缺口页生效，仅在**子主题 functions.php** 添加八页范围的字段读取：title filter 返回导入的 document title；没有 Yoast/父 metadata owner 时输出已有 description 字段。没有硬编码营销文案，没有重写 H1/正文，也没有给其他产品页改元数据。

### inquiry 保留例外

实际现有英文比 dsh 短版多一句附件说明：

```text
Request a packaging quotation with your product, dimensions, quantity and destination. MOQ 500 per design. Add artwork or reference files for Packrivo review.
```

按“保留现有英文”明确例外，原句不变；其生产基线中已有的 11 语种完整译文也保留。dsh 文件本身的短描述与译文没有被改写，仍写入提案增量；没有把长英文键错误映射成短版译文。

### 幂等导入与备份

脚本：`scripts/hubs-27i/import.php`。先独占创建 `deploy/rollback-27i.json`，再在事务内只更新四个 SEO meta 字段；expected-site、输入哈希、全部 post 字段及其他 meta 均有保护。备份不覆盖。

运行目录 `E:\破甲工作区\packrivo-live`，完整本地命令：

```powershell
$env:PATH='D:\Docker\App\resources\bin;'+$env:PATH
docker compose -f docker-compose.yml -f 'E:\破甲工作区\.tmp\packrivo-27c-compose.yml' -f 'E:\破甲工作区\.tmp\packrivo-27g-i18n-compose.yml' -f 'E:\破甲工作区\.tmp\packrivo-27i-data-compose.yml' run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup:/package' cli wp eval-file /package/scripts/hubs-27i/import.php apply /package http://localhost:8081
```

首次成功业务输出：

```text
hub_pages=8 preflight=PASS
backup=reused rows=8
apply_updated=7 already_current=1 H1_body_URL_check=PASS
```

备份创建后的最初尝试因 PHP 关联数组键顺序的严格比较误报读回失败，事务已回滚；修正比较所用键序后复用同一原始备份，成功更新七页。inquiry 原值已与保留目标一致，故跳过一页；没有删备份重建。

重复运行：

```text
hub_pages=8 preflight=PASS
backup=reused rows=8
apply_updated=0 already_current=8 H1_body_URL_check=PASS
```

将 apply 替换为 verify：

```text
hub_pages=8 preflight=PASS
backup=reused rows=8
verified_hubs=8 metadata_mismatches=0 H1_body_URL_unchanged=8/8 inquiry_description_preserved=yes
```

只备份/回滚检查输出（没有执行 rollback）：

```text
hub_pages=8 preflight=PASS
backup=reused rows=8
backup_only rows=8 writes=0
hub_pages=8 preflight=PASS
backup=reused rows=8
rollback_eligible=7 already_restored=1 writes=0
backup_unchanged: True
```

## 3. 字典及动态按钮

主字典以 27e 的生产只读基线为底；runtime 的 11 个 `*-js.json` 及 paths.json 本轮经只读 SCP 取得，不向生产写文件。源哈希及新增路径记录在 `deploy/i18n-baseline-27i.json`。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\hubs-27i\merge-i18n.mjs --root E:\破甲工作区\packrivo-backup --base E:\破甲工作区\.tmp\packrivo-27e-dictionaries --js-base E:\破甲工作区\.tmp\packrivo-hubs-27i\js-baseline --paths-base E:\破甲工作区\.tmp\packrivo-hubs-27i\paths-baseline.json
```

原样输出：

```text
language added_bucket_keys added_english_keys server_conflicts runtime_added runtime_conflicts
ar 816 473 5 1 0
de 811 472 4 1 0
es 812 473 4 1 0
fr 811 472 3 1 0
it 811 472 3 1 0
ja 816 473 1 1 0
ko 816 473 1 1 0
nl 811 472 3 1 0
pt 812 473 3 1 0
zh-CN 812 473 4 1 0
zh-TW 816 473 2 1 0
translation paths added: ["/blog/"]
existing inquiry English and complete production translations retained
```

server_conflicts 为当前整包累计冲突（含 27g 既有 19 项，本次新增 hub title 冲突 14 项，共 33）；runtime 无冲突，每语言新增一个按钮 exact 键。分区与 27g 相同，title 用完整英文键、description 放 block/attr；动态按钮另放 text/block 以及 runtime exact。

多语言插件 `packrivo-i18n.php:517–543` 已有 exact/patterns、DOM 初次遍历与 MutationObserver，能处理 textContent。底部按钮来自 `packrivo-wave1.php:152`，其源码完全不改。只加 runtime 字典键即可；没有新增子主题按钮脚本。原 1,299 个 exact 值和所有 patterns 均保留。

当前生产路径表 144 条且无 `/blog/`，因此该语言路径改前返回 404。包内仅补 `/blog/` 供任务要求的博客语言版验证，其他路径顺序和值保持不变；没有改路由插件源码或创建新的 WordPress 页面。

独立数据审计：

```text
{"approvedInputUnchanged":true,"hubTranslationChecks":264,"productionServerValuesPreserved":71593,"productionRuntimeValuesPreserved":1299,"runtimeButtonLanguages":11,"pathsBefore":144,"pathsAfter":145}
PASS: approved hub translations, baseline values, runtime patterns and path whitelist preserved
```

重复生成：

```text
dictionary regeneration files=38 unchanged=true
```

## 4. curl 原始 HTML 验收

命令：

```powershell
node E:\破甲工作区\packrivo-backup\scripts\hubs-27i\curl-hubs.mjs after
node E:\破甲工作区\packrivo-backup\scripts\hubs-27i\verify-output.mjs
```

原样摘要：

```text
responses=32 title_count_issues=0 missing_description=0
curl responses=32 single_title=32 single_description=32 preservation_policy_mismatches=0
dsh_exact_rows=27 preserved_exception_rows=5 English_H1_unchanged=8/8
banned_title_hits=0
```

以下全部为 HTTP 200、title 数 1、description 数 1，内容取自 curl 原始响应，不以浏览器 DOM 代替源 HTML。标记“保留”的五行不是严格 dsh 逐字一致，原因已明确列出：

| path | 语种 | 实际 title | 实际 meta description | 比对 |
| --- | --- | --- | --- | --- |
| / | en | Custom Jewelry Boxes & Packaging with Logo \| Packrivo | Custom jewelry boxes, pouches and gift boxes printed with your logo, from 500 pieces. Order a sample first and see live prices in the quote builder. | dsh 一致 |
| / | ar | علب مجوهرات وتغليف مخصص بالشعار \| Packrivo | علب مجوهرات وأكياس وعلب هدايا مطبوعة بشعارك، من 500 قطعة. اطلب عينة أولًا واطّلع على الأسعار المباشرة في أداة إعداد عرض السعر. | dsh 一致 |
| / | de | Schmuckschachteln & Verpackungen mit Logo \| Packrivo | Schmuckschachteln, Beutel und Geschenkboxen mit Ihrem Logo, ab 500 Stück. Erst ein Muster bestellen und Live-Preise im Angebotsrechner sehen. | dsh 一致 |
| / | ja | ロゴ入りジュエリーボックス・オリジナル包装 \| Packrivo | ジュエリーボックス、ポーチ、ギフトボックスにロゴ印刷、500個から。まずサンプルを注文し、見積もり作成ツールでリアルタイムの価格をご確認ください。 | dsh 一致 |
| /products/ | en | Custom Packaging: 134 Styles, MOQ 500 \| Packrivo | Browse 134 custom packaging styles, from jewelry boxes and pouches to mailers and gift boxes, printed with your logo from 500 pieces. Get a price. | dsh 一致 |
| /products/ | ar | تغليف مخصص: 134 تصميمًا، حد أدنى 500 \| Packrivo | تصفح 134 نمطًا من التغليف المخصص، من علب المجوهرات والأكياس إلى علب البريد والهدايا، مطبوعة بشعارك من 500 قطعة. اطلب سعرًا. | dsh 一致 |
| /products/ | de | Verpackungen nach Maß: 134 Arten, MOQ 500 \| Packrivo | 134 Verpackungsvarianten nach Maß entdecken: Schmuckschachteln, Beutel, Versand- und Geschenkboxen, mit Ihrem Logo ab 500 Stück. Preis anfragen. | dsh 一致 |
| /products/ | ja | オリジナル包装134種類・最低注文数500個 \| Packrivo | ジュエリーボックスやポーチから宅配箱、ギフトボックスまで、134種類のオリジナル包装をご覧ください。ロゴ印刷は500個から。価格をお問い合わせください。 | dsh 一致 |
| /jewelry-boxes/ | en | Custom Jewelry Boxes: Rigid, Drawer, MOQ 500 \| Packrivo | Custom jewelry boxes with your logo: rigid, magnetic, drawer and pillow styles for rings, necklaces and bracelets, from 500 pieces. Get a price. | dsh 一致 |
| /jewelry-boxes/ | ar | علب مجوهرات مخصصة: صلبة وبدرج، من 500 \| Packrivo | علب مجوهرات بشعارك: صلبة ومغناطيسية وبدرج وبشكل وسادة، للخواتم والقلائد والأساور، من 500 قطعة. اطلب سعرًا. | dsh 一致 |
| /jewelry-boxes/ | de | Schmuckschachteln mit Logo – ab 500 Stück | Schmuckschachteln mit Ihrem Logo: Hartboxen, Magnet-, Schubladen- und Kissenschachteln für Ringe, Ketten und Armbänder, ab 500 Stück. Preis anfragen. | 保留例外 |
| /jewelry-boxes/ | ja | ジュエリーボックス：貼り箱・引き出し式・500個から \| Packrivo | ロゴ入りジュエリーボックスを500個から。リング、ネックレス、ブレスレット向けに、貼り箱、マグネット式、引き出し式、ピローボックスをご用意。価格をお問い合わせください。 | dsh 一致 |
| /mailer-boxes/ | en | Custom Mailer Boxes & Shipping Boxes with Logo \| Packrivo | Custom printed mailer boxes and shipping boxes for ecommerce brands, with your logo from 500 pieces. Get a price. | dsh 一致 |
| /mailer-boxes/ | ar | علب بريد وشحن مخصصة بالشعار \| Packrivo | علب بريد وشحن مطبوعة حسب الطلب لعلامات التجارة الإلكترونية، بشعارك من 500 قطعة. اطلب سعرًا. | dsh 一致 |
| /mailer-boxes/ | de | Versandschachteln & Versandkartons mit Logo \| Packrivo | Individuell bedruckte Versandschachteln und Versandkartons für E-Commerce-Marken, mit Ihrem Logo ab 500 Stück. Preis anfragen. | dsh 一致 |
| /mailer-boxes/ | ja | ロゴ入りメーラーボックス・配送箱 \| Packrivo | ECブランド向けに、メーラーボックスや配送箱をオリジナル印刷。ロゴ入りで500個から。価格をお問い合わせください。 | dsh 一致 |
| /samples/ | en | Custom Packaging Samples Before You Order \| Packrivo | Order a sample of your custom box or pouch to check size, print and finish before your 500-piece order. Get a price. | dsh 一致 |
| /samples/ | ar | عينات تغليف مخصص قبل الطلب \| Packrivo | اطلب عينة من علبتك أو كيسك المخصص لفحص المقاس والطباعة والتشطيب قبل طلب 500 قطعة. اطلب سعرًا. | dsh 一致 |
| /samples/ | de | Individuelle Verpackungsmuster vor Bestellung \| Packrivo | Bestellen Sie ein Muster Ihrer individuellen Schachtel oder Ihres Beutels. Prüfen Sie Größe, Druck und Veredelung vor Ihrer Bestellung über 500 Stück. Preis anfragen. | dsh 一致 |
| /samples/ | ja | 注文前に確認するオリジナル包装サンプル \| Packrivo | オーダーメイドの箱やポーチのサンプルを注文し、500個の注文前にサイズ、印刷、仕上がりをご確認ください。価格をお問い合わせください。 | dsh 一致 |
| /blog/ | en | Custom Packaging Guides for Small Brands \| Packrivo | Practical guides for small brands on jewelry boxes, mailers and gift packaging: sizes, materials, costs and how to order. | dsh 一致 |
| /blog/ | ar | أدلة تغليف مخصص للعلامات الصغيرة \| Packrivo | أدلة عملية للعلامات الصغيرة عن علب المجوهرات والبريد وتغليف الهدايا: المقاسات والمواد والتكاليف وكيفية الطلب. | dsh 一致 |
| /blog/ | de | Verpackungsratgeber für kleine Marken \| Packrivo | Praktische Ratgeber für kleine Marken zu Schmuckschachteln, Versandboxen und Geschenkverpackungen: Größen, Materialien, Kosten und Bestellung. | dsh 一致 |
| /blog/ | ja | 小規模ブランド向けオリジナル包装ガイド \| Packrivo | 小規模ブランド向けに、ジュエリーボックス、宅配箱、ギフト包装を解説。サイズ、素材、費用、注文方法がわかる実用ガイドです。 | dsh 一致 |
| /about/ | en | About Packrivo \| Custom Packaging from Shenzhen | Packrivo helps small brands order custom packaging from Shenzhen, working with independent suppliers on samples, printing and shipping. | dsh 一致 |
| /about/ | ar | عن Packrivo \| تغليف مخصص من شنتشن | تساعد Packrivo العلامات الصغيرة على طلب تغليف مخصص من شنتشن، بالتعاون مع موردين مستقلين في العينات والطباعة والشحن. | dsh 一致 |
| /about/ | de | Über Packrivo \| Verpackungen nach Maß aus Shenzhen | Packrivo hilft kleinen Marken, individuelle Verpackungen aus Shenzhen zu bestellen, und arbeitet bei Mustern, Druck und Versand mit unabhängigen Lieferanten. | dsh 一致 |
| /about/ | ja | Packrivoについて \| 深圳発のオリジナル包装 | Packrivoは、小規模ブランドによる深圳からのオリジナル包装の注文を支援します。独立したサプライヤーと連携し、サンプル、印刷、発送を進めます。 | dsh 一致 |
| /inquiry/ | en | Request a Custom Packaging Quote \| Packrivo | Request a packaging quotation with your product, dimensions, quantity and destination. MOQ 500 per design. Add artwork or reference files for Packrivo review. | 保留例外 |
| /inquiry/ | ar | اطلب عرض سعر لتغليف مخصص \| Packrivo | اطلب عرض سعر للتغليف مع منتجك وأبعاده والكمية والوجهة. MOQ (الحد الأدنى للطلب) 500 لكل تصميم. أضف ملفات التصميم أو الملفات المرجعية لمراجعة Packrivo. | 保留例外 |
| /inquiry/ | de | Kundenspezifisches Verpackungsangebot anfordern \| Packrivo | Ein Verpackungsangebot mit Ihrem Produkt, den Abmessungen, der Menge und dem Bestimmungsort anfordern. MOQ 500 pro Design. Druckdaten oder Referenzdateien zur Prüfung durch Packrivo hinzufügen. | 保留例外 |
| /inquiry/ | ja | カスタム包装の見積を依頼 \| Packrivo | 製品、寸法、数量、仕向地を添えて包装見積を依頼する。デザインあたり MOQ 500。Packrivo のレビュー用にアートワークまたは参照ファイルを追加する。 | 保留例外 |

五个保留例外：jewelry de 的 title；inquiry en 的既有完整描述；inquiry ar/de/ja 的既有生产 title 及完整描述。此处理遵循生产优先和 inquiry 英文保留规则，尚不能将这些行声称为严格 dsh 一致。完整差异见 comparison.json，供 Claude 复核；未私自覆盖生产译法或删除原英文句子。

首页 H1 保持：

```text
Custom packaging for jewelry, candles & gifts.
```

其他七页英文 H1 也与改前一致；数据库的八页 post 内容和非目标字段签名同样保持原值。

### 动态按钮和 375px

```powershell
node E:\破甲工作区\packrivo-backup\scripts\hubs-27i\verify-sticky.cjs
```

```text
{"status":200,"button":"اطلب عرض سعر · الحد الأدنى 500","expected":"اطلب عرض سعر · الحد الأدنى 500","postload_textContent_translated":true,"runtimeInjected":true,"viewport":375,"scrollWidth":375,"direction":"rtl"}
```

浏览器还模拟了再次把按钮 textContent 设回英文，现有 Observer 随即恢复已审阿语，证明不是仅在初次加载时碰巧翻译。截图 `deploy/verification-27i/ar-sticky-cta-375.png` 使用默认视口直接截取，没有裁切或注入样式。

## 上线包和范围保护

- `deploy/README.md` 已加入 27i 的 backup/plan/apply/verify/rollback-check/rollback 命令；字典步骤使用当前整包生成器，连同 runtime exact 和路径表安装/回滚。
- 生产须先备份实际原值到生产独立 `rollback-27i.json`，不能使用仓库中的 localhost 备份。生成字典时可用 `--metadata-backup` 指向该原值，以保留实际 inquiry 描述。
- 当前子主题 ZIP 因元数据字段投影重打，SHA-256 为 `1446420DE7263EAFA962859FC0CE9B6B245AF403B540663FD3BBD499DD177F33`。父主题、插件和 MU 源码不变：`parent_plugin_MU_protected_files=107 changed=0`。
- 原 134 个产品页的内容/meta 指纹仍为 `76e6c833d36af862f7daee6d1de1237357135ffc98c535ba36691ea0ee5db7f1`，未受 hub 导入影响。
- 首页仍 5/8 卡片，报价重定向补丁仍待获授权上线执行，没有删除或伪称完成。

独立只读代码审查未发现可证实的 Critical / Important 问题，复核了备份/幂等/回滚、字段投影边界、基线值保留、32 行验收记录及例外。审查者没有改文件、操作数据库或访问网站。

本轮没有执行生产内容导入、字典安装或回滚命令；对生产仅只读下载 runtime 字典和路径文件，没有修改 www.packrivo.com。

## 提交与推送回执

```text
[main 77c9b9a] Import approved hub metadata and translate runtime quote CTA
 63 files changed, 15634 insertions(+), 83 deletions(-)
To https://github.com/jiaxiangli477-stack/packrivo-backup.git
   1a373d3..77c9b9a  main -> main
```

主交付提交 `77c9b9a`。本回执随后作为文档提交同步推送；没有执行生产部署。
