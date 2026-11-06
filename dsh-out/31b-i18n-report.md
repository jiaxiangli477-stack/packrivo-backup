# 任务 31b-缺译 · 缺失英文到 11 语言的补译交付报告

- 工作区：`E:\破甲工作区\packrivo-backup`，分支 `main`，HEAD `276f8d2`
- 交付物：`dsh-out/31b-i18n.csv`（308 行）、本报告、`dsh-out/31b-tools/`（脚本与中间产物）
- 输入：`dsh-out/31b-new-strings.csv`（37 条）、只读参考 `deploy/i18n-merged/<lang>-dict.json`
- 本任务未改词典、未碰容器与数据库、未改任何已有文件

## 1. 结论

37 条待补英文里，**28 条在 11 个语言的词典中全部缺失**，**9 条已有**（不动）。产出 28 × 11 = **308 行** CSV，自检 10 项全过、0 错误。

缺失判定按「该英文在 ar/de/es/fr/it/ja/ko/nl/pt/zh-CN/zh-TW 任一语言的 title/attr/block/text 四桶中存在即算已有」的口径执行，未发现只缺部分语言的条目（28 条全部是 11 语言全缺）。

## 2. 28 条缺失清单

`bucket` 列是本次 CSV 使用的归类值，规则见第 3 节。

| # | en | bucket | 出现位置 | 页面 |
|---|---|---|---|---|
| 1 | `More in this category` | text | 品类页相关链接区标题 | `/jewelry-boxes/`、`/mailer-boxes/`、`/foldable-gift-boxes/`、`/bags-pouches/`、`/inserts/` |
| 2 | `Related reading` | text | 文章末尾相关链接区标题 | 所有英文博客文章 |
| 3 | `{Topic} \| Packrivo` | title | 专题页文档标题模板 | 英文专题页 |
| 4 | `Drawer Box With Ribbon Pull` | text | 品类页相关链接标题 | `/jewelry-boxes/` |
| 5 | `Corrugated Wine Bottle Shipper` | text | 品类页相关链接标题 | `/mailer-boxes/` |
| 6 | `Subscription Mailer Box With Insert` | text | 品类页相关链接标题 | `/mailer-boxes/` |
| 7 | `Eco Kraft Cosmetic Box for Small Batches` | text | 品类页相关链接标题 | `/foldable-gift-boxes/` |
| 8 | `Magnetic Book-Style Presentation Box` | text | 品类页相关链接标题 | `/foldable-gift-boxes/` |
| 9 | `Custom Kraft Soap Box With Window` | text | 品类页相关链接标题 | `/foldable-gift-boxes/` |
| 10 | `Jewelry Pouch and Box Set` | text | 品类页相关链接标题 | `/bags-pouches/` |
| 11 | `Kraft Stand-Up Pouch With Window` | text | 品类页相关链接标题 | `/bags-pouches/` |
| 12 | `Jewelry Boxes, Pouches and Bags — Reference Catalogue` | text | 品类页相关链接标题 | `/bags-pouches/` |
| 13 | `Pink Jewelry Storage Box` | text | 品类页相关链接标题 | `/bags-pouches/` |
| 14 | `Jewelry Packaging Box — Ref. 996892` | text | 品类页相关链接标题 | `/bags-pouches/` |
| 15 | `Velvet-Lined Boxes — Reference Catalogue` | text | 品类页相关链接标题 | `/jewelry-boxes/` |
| 16 | `Velvet-Lined Box — Velvet, Faux Fur` | text | 品类页相关链接标题 | `/jewelry-boxes/` |
| 17 | `For a quote, email us or contact us on WhatsApp.` | text | 无 JavaScript 兜底 | `/get-a-quote/` |
| 18 | `illustrative walkthroughs` | text | About 正文链接 | `/about/` |
| 19 | `English` | attr | 语言切换器链接 | 全站语言切换器 |
| 20 | `Supplier sample room with shelves of rigid gift boxes and jewelry boxes` | attr | 首页照片 alt | `/` |
| 21 | `Supplier sample room, Dongguan. Structures are chosen from real samples before artwork starts.` | text | 首页照片图注 | `/` |
| 22 | `Operator feeding a printed proof sheet into a wide-format digital proofing printer` | attr | 首页照片 alt | `/` |
| 23 | `Digital proof printing at a supplier in Dongguan.` | text | 首页照片图注 | `/` |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | attr | 首页照片 alt | `/` |
| 25 | `Sheet-fed offset press used for production runs.` | text | 首页照片图注 | `/` |
| 26 | `Finished jewelry boxes packed in export cartons before shipping` | attr | 首页照片 alt | `/` |
| 27 | `Finished boxes packed into cartons, Shantou.` | text | 首页照片图注 | `/` |
| 28 | `Supplier photos, used with permission.` | text | 首页照片统一图注 | `/` |

## 3. 桶归类依据与偏差说明

任务规则：页面 `<title>` → `title`；按钮 / alt / aria → `attr`；其余 → `text`。据此：

- `title` **1 条**：第 3 条 `{Topic} | Packrivo`，是 `<title>` 模板，` | Packrivo` 品牌后缀在所有已有词典标题里都未翻译，本次同样不动。
- `attr` **5 条**：第 19 条 `English`（语言切换器 `<a>` 条目名）、第 20/22/24/26 条（首页 `<img alt>`）。
- `text` **22 条**：其余全部，含 4 条图注（21/23/25/27/28 中前四条＋第 28 条统一图注）、8 条相关链接标题、2 条区块标题、1 条 CTA 兜底、1 条正文链接文字、以及 6 条品类名链接。

与现有词典 `block` 桶的偏差：现有词典的 `block` 桶装长段正文，本次 28 条没有长段正文，故 `block` 桶为空（0 行）。第 21/23/25/27/28 条是图注短句，按任务规则归 `text`，未归 `block`。若后续要并入现有词典，这 5 条建议落在 `text` 而非 `block`。

## 4. `English` 的处理

**取值：11 个语言全部保持 `English`。**

依据是切换器的实现，不是惯例猜测：

- `packrivo-wp/packrivo-language-selector/packrivo-language-selector.php:26` — `en` 行的元数据是 `'label' => 'English', 'english' => 'English'`。
- 同文件第 67 行把 `$meta['label']` 直接写进每个 `<a data-lang>` 的锚文本；`label` 存的是**该语言自己的自称**（endonym），`english` 字段只被 `selector-v1.0.7.js` 拿去拼搜索索引（`[row.code, row.label, row.english, row.aliases]`），不上屏。

所以「English 这一条在目标语言界面里显示什么」的答案由 `label` 决定，而 `label` 就是 `English`。德语界面里语言列表显示 `English` 而不是 `Englisch`，与日文界面显示 `日本語`、中文界面显示 `中文` 同一套逻辑。

另一种可读法（若该串其实用于正文而非切换器条目）会得到 `Englisch / inglés / anglais / inglese / 英語 / 영어 / Engels / inglês / الإنجليزية / 英文`，本 CSV 未采用；评审若要改成这一读法，只需替换第 19 条的 11 个值，其余 27 条不受影响。

## 5. 地名处理

任务要求「地名（Dongguan、Shantou）照抄」。执行口径：**同一地名，不换名、不改指代**；拉丁字母语言原样照抄，汉字圈语言使用该地名的固有写法（東莞/东莞、汕頭/汕头 是同一个地名的本地字形，不是改名），阿拉伯语与韩语用通行音译。逐语言的取值表（自检 C6 打印）：

```text
Dongguan  ar=دونغقوان, de=Dongguan, es=Dongguan, fr=Dongguan, it=Dongguan, ja=東莞, ko=둥관, nl=Dongguan, pt=Dongguan, zh-CN=东莞, zh-TW=東莞
Shantou   ar=شانتو, de=Shantou, es=Shantou, fr=Shantou, it=Shantou, ja=汕頭, ko=산터우, nl=Shantou, pt=Shantou, zh-CN=汕头, zh-TW=汕頭
```

评审注意点：若要求汉字圈也严格保留拉丁写法（`zh-CN` 写成「供应商样品间，Dongguan。」），只需改第 21/23/27 三条的 `ja/ko/zh-CN/zh-TW/ar` 值，共 15 行。

## 6. `Ref.` 与编号

编号 `996892` 原样保留（自检 C5 数字比对 0 错误）。`Ref.` 这个词按现有词典惯例逐语言本地化，不是自创：

| 语言 | 采用 | 参考词典里的同类写法 |
|---|---|---|
| ar | `مرجع` | `مرجع 996892` 类目里的 مرجع |
| de / es / nl / pt | `Ref.` | 保持拉丁缩写 |
| fr | `Réf.` | `Réf. 0123AB` |
| it | `Rif.` | `Rif. 0123AB` |
| ja | `Ref.` | 现有 ja 条目沿用拉丁缩写 |
| ko | `참조 번호` | `참조 번호 0123AB` |
| zh-CN | `编号` | `编号 015LH8` |
| zh-TW | `編號` | `編號 015LH8` |

## 7. 供应商表述约束的落实

三条约束逐条落到自检上，都是硬检查、都是 0 命中：

1. **「供应商」不得变成「我们的工厂」**：第 20/21/28 条（`Supplier …` 开头的三条）在 11 个语言里都必须含该语言的 supplier 词根，检查项 C7。取值 `المورد / Lieferant / proveedor / fournisseur / fornitore / サプライヤー / 공급업체 / leverancier / fornecedor / 供应商 / 供應商`。
2. **`used with permission` 必须活下来**：第 28 条在 11 个语言里都必须含许可词，检查项 C8。取值 `بإذن / Erlaubnis / permiso / autorisation / autorizzazione / 使用許諾 / 허가 / toestemming / autorização / 许可 / 許可`。日文用「サプライヤー提供の写真（使用許諾済み）。」，把「谁提供」和「已获许可」都留在句内。
3. **不得自我描述成工厂 / 制造商**：全 308 行扫 `our factory / unsere Fabrik / notre usine / 当社の工場 / 우리 공장 / مصنعنا / 我们的工厂 …` 共 21 个短语，检查项 C9，命中 0。

需要说明的一处：第 24 条原文 `… in a packaging factory` 里的 factory 是**照片拍摄地点**（包装厂里的印刷机），不是站点自称工厂。忠实译出（如 zh-CN「包装厂内的多色单张纸胶印机」）符合约束，与禁止的「我们是工厂」不是同一件事。

## 8. 术语对齐

优先复用现有词典里已有的译法，不自创。关键锚定（取自已加载的 11 份词典）：

| 英文概念 | de | es | fr | it |
|---|---|---|---|---|
| mailer box | Versandschachtel | caja para envíos | boîte d'expédition | scatola a busta |
| corrugated | Wellpappe | cartón ondulado | carton ondulé | cartone ondulato |
| supplier | Lieferant | proveedor | fournisseur | fornitore |
| sample | Muster | muestra | échantillon | campione |
| quote (CTA) | Angebot anfordern | Solicitar cotización | Obtenir un devis | Richiedi un preventivo |
| velvet-lined | samtausgekleidet | forrada de terciopelo | doublée velours | rivestita in velluto |
| faux fur | Kunstfell | piel artificial | fausse fourrure | pelliccia sintetica |
| reference catalogue | Referenzkatalog | Catálogo de referencia | Catalogue de référence | Catalogo di riferimento |

| 英文概念 | nl | pt | ja | ko | zh-CN | zh-TW | ar |
|---|---|---|---|---|---|---|---|
| mailer box | mailerbox | caixa mailer | メーラーボックス | 메일러 박스 | 邮寄盒 | 郵寄盒 | صندوق بريد |
| corrugated | golfkarton | ondulado | 段ボール | 골판지 | 瓦楞 | 瓦楞 | الكرتون المضلع |
| supplier | leverancier | fornecedor | サプライヤー | 공급업체 | 供应商 | 供應商 | المورد |
| sample | monster | amostra | サンプル | 샘플 | 样品 | 樣品 | عينة |
| quote (CTA) | prijsopgave | cotação | 見積もり | 견적 | 报价 | 報價 | عرض سعر |
| velvet-lined | met fluweelvoering | forrada a veludo | ベルベット張り | 벨벳 안감 | 天鹅绒内衬 | 絨布內襯 | مبطّنة بالمخمل |
| faux fur | kunstbont | pelo sintético | フェイクファー | 페이크 퍼 | 仿毛 | 仿毛 | فرو صناعي |
| reference catalogue | referentiecatalogus | catálogo de referências | リファレンスカタログ | 레퍼런스 카탈로그 | 参考目录 | 參考目錄 | كتالوج مرجعي |

`pouch` 一词各语言取向不同（ja ポーチ、ko 파우치、nl zakje、zh-CN 袋、zh-TW 軟袋），本次在第 10/11/12 条按词典已有取向处理：第 11 条「自立袋/スタンドパウチ/스탠드업 파우치」跟随词典的 `stand-up pouch` 写法，第 12 条用「包装袋 / 袋類」对应 `Bags`，避免与第 10 条「首饰袋」撞词。

## 9. 回译：阿拉伯语（28 条全量）

| # | en | ar 值 | 回译（中文） |
|---|---|---|---|
| 1 | `More in this category` | المزيد في هذا التصنيف | 本分类下的更多内容 |
| 2 | `Related reading` | قراءات ذات صلة | 相关阅读 |
| 3 | `{Topic} \| Packrivo` | {Topic} \| Packrivo | 模板未动，专题名与品牌后缀原样 |
| 4 | `Drawer Box With Ribbon Pull` | علبة بدرج مع مقبض شريطي | 带条带状拉手的抽屉盒 |
| 5 | `Corrugated Wine Bottle Shipper` | صندوق شحن من الكرتون المضلع لزجاجة نبيذ | 瓦楞纸板葡萄酒瓶运输箱 |
| 6 | `Subscription Mailer Box With Insert` | صندوق بريد للاشتراكات مع حشوة داخلية | 带内衬的订阅邮寄盒 |
| 7 | `Eco Kraft Cosmetic Box for Small Batches` | علبة مستحضرات تجميل كرافت صديقة للبيئة للدفعات الصغيرة | 小批量用环保牛皮纸化妆品盒 |
| 8 | `Magnetic Book-Style Presentation Box` | علبة عرض مغناطيسية بشكل كتاب | 磁吸书本式展示盒 |
| 9 | `Custom Kraft Soap Box With Window` | علبة صابون كرافت مخصصة بنافذة | 定制牛皮纸开窗皂盒 |
| 10 | `Jewelry Pouch and Box Set` | طقم كيس وعلبة مجوهرات | 首饰袋与首饰盒套装 |
| 11 | `Kraft Stand-Up Pouch With Window` | كيس كرافت قائم بنافذة | 牛皮纸开窗自立袋 |
| 12 | `Jewelry Boxes, Pouches and Bags — Reference Catalogue` | علب المجوهرات والأكياس والعبوات — كتالوج مرجعي | 首饰盒、袋与包装袋 — 参考目录 |
| 13 | `Pink Jewelry Storage Box` | علبة تخزين مجوهرات وردية | 粉色首饰收纳盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | علبة تغليف مجوهرات — مرجع 996892 | 首饰包装盒 — 编号 996892 |
| 15 | `Velvet-Lined Boxes — Reference Catalogue` | علب مبطّنة بالمخمل — كتالوج مرجعي | 天鹅绒内衬盒 — 参考目录 |
| 16 | `Velvet-Lined Box — Velvet, Faux Fur` | علبة مبطّنة بالمخمل — مخمل، فرو صناعي | 天鹅绒内衬盒 — 天鹅绒、仿毛 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | للحصول على عرض سعر، راسلنا بالبريد الإلكتروني أو تواصل معنا على WhatsApp. | 如需报价，请给我们发邮件，或在 WhatsApp 上联系我们。 |
| 18 | `illustrative walkthroughs` | شروحات توضيحية خطوة بخطوة | 分步骤的说明性讲解（图文走查） |
| 19 | `English` | English | 切换器条目名，保留英文自称 |
| 20 | `Supplier sample room with shelves of rigid gift boxes and jewelry boxes` | غرفة عينات المورد مع أرفف تحمل علب هدايا صلبة وعلب مجوهرات | 供应商样品间，货架上放着硬质礼盒和首饰盒 |
| 21 | `Supplier sample room, Dongguan. Structures are chosen from real samples before artwork starts.` | غرفة عينات المورد، دونغقوان. تُختار الهياكل من عينات حقيقية قبل بدء العمل الفني. | 供应商样品间，东莞。结构在开始设计稿之前从实物样品中选定。 |
| 22 | `Operator feeding a printed proof sheet into a wide-format digital proofing printer` | مشغّل يُدخل ورقة إثبات مطبوعة في طابعة إثبات رقمية بعرض كبير | 操作员把已打印的样张送入大幅面数码打样机 |
| 23 | `Digital proof printing at a supplier in Dongguan.` | طباعة إثبات رقمية عند مورد في دونغقوان. | 东莞一家供应商处的数码打样。 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | مطبعة أوفست متعددة الألوان بتغذية ورقية داخل مصنع تغليف | 包装厂内的多色单张纸胶印机 |
| 25 | `Sheet-fed offset press used for production runs.` | مطبعة أوفست بتغذية ورقية تُستخدم لدفعات الإنتاج. | 用于量产批次的单张纸胶印机。 |
| 26 | `Finished jewelry boxes packed in export cartons before shipping` | علب مجوهرات جاهزة تُعبّأ في كراتين تصدير قبل الشحن | 成品首饰盒在发货前装入出口纸箱 |
| 27 | `Finished boxes packed into cartons, Shantou.` | علب جاهزة تُعبّأ في كراتين، شانتو. | 成品盒装箱，汕头。 |
| 28 | `Supplier photos, used with permission.` | صور المورد، مستخدمة بإذن. | 供应商照片，经许可使用。 |

## 10. 回译：日语（28 条全量）

| # | en | ja 值 | 回译（中文） |
|---|---|---|---|
| 1 | `More in this category` | このカテゴリのその他の項目 | 本分类下的其他条目 |
| 2 | `Related reading` | 関連記事 | 相关文章 |
| 3 | `{Topic} \| Packrivo` | {Topic} \| Packrivo | 模板未动 |
| 4 | `Drawer Box With Ribbon Pull` | リボンプル付きドロワーボックス | 带缎带拉手的抽屉盒 |
| 5 | `Corrugated Wine Bottle Shipper` | 段ボール製ワインボトル輸送箱 | 瓦楞纸葡萄酒瓶运输箱 |
| 6 | `Subscription Mailer Box With Insert` | インサート付きサブスクリプションメーラーボックス | 带内托的订阅邮寄盒 |
| 7 | `Eco Kraft Cosmetic Box for Small Batches` | 小ロット向けエコクラフト化粧品ボックス | 面向小批量的环保牛皮纸化妆品盒 |
| 8 | `Magnetic Book-Style Presentation Box` | マグネット式ブックスタイルプレゼンテーションボックス | 磁吸书本式展示盒 |
| 9 | `Custom Kraft Soap Box With Window` | オーダーメイドの窓付きクラフト紙製ソープボックス | 定制开窗牛皮纸皂盒 |
| 10 | `Jewelry Pouch and Box Set` | ジュエリーポーチ＆ボックスセット | 首饰袋与首饰盒套装 |
| 11 | `Kraft Stand-Up Pouch With Window` | 窓付きクラフトスタンドパウチ | 带开窗的牛皮纸自立袋 |
| 12 | `Jewelry Boxes, Pouches and Bags — Reference Catalogue` | ジュエリーボックス、ポーチ、バッグ — リファレンスカタログ | 首饰盒、首饰袋、手提袋 — 参考目录 |
| 13 | `Pink Jewelry Storage Box` | ピンクのジュエリー収納ボックス | 粉色首饰收纳盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | ジュエリー包装ボックス — Ref. 996892 | 首饰包装盒 — Ref. 996892（编号原样） |
| 15 | `Velvet-Lined Boxes — Reference Catalogue` | ベルベット張りのボックス — リファレンスカタログ | 天鹅绒贴面盒 — 参考目录 |
| 16 | `Velvet-Lined Box — Velvet, Faux Fur` | ベルベット張りのボックス — ベルベット、フェイクファー | 天鹅绒贴面盒 — 天鹅绒、仿毛 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | お見積もりについては、メールでご連絡いただくか、WhatsApp でお問い合わせください。 | 关于报价，请通过邮件联系我们，或通过 WhatsApp 咨询。 |
| 18 | `illustrative walkthroughs` | 図解付きの手順解説 | 带图解的手顺说明 |
| 19 | `English` | English | 切换器条目名，保留英文自称 |
| 20 | `Supplier sample room with shelves of rigid gift boxes and jewelry boxes` | 硬質ギフトボックスとジュエリーボックスが棚に並ぶサプライヤーのサンプルルーム | 货架上摆着硬质礼盒和首饰盒的供应商样品间 |
| 21 | `Supplier sample room, Dongguan. Structures are chosen from real samples before artwork starts.` | サプライヤーのサンプルルーム、東莞。構造はアートワーク着手前に実サンプルから選定します。 | 供应商样品间，东莞。结构在着手制作图稿之前从实物样品中选定。 |
| 22 | `Operator feeding a printed proof sheet into a wide-format digital proofing printer` | 印刷した校正紙を大判デジタル校正プリンターに送り込むオペレーター | 把印好的校样纸送入大幅面数码打样机的操作员 |
| 23 | `Digital proof printing at a supplier in Dongguan.` | 東莞のサプライヤーでのデジタル校正印刷。 | 东莞某供应商处的数码打样。 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | 包装工場にある多色枚葉オフセット印刷機 | 包装工厂里的多色单张纸胶印机 |
| 25 | `Sheet-fed offset press used for production runs.` | 量産に使用される枚葉オフセット印刷機。 | 用于量产的张页胶印机。 |
| 26 | `Finished jewelry boxes packed in export cartons before shipping` | 出荷前に輸出用カートンに梱包された完成ジュエリーボックス | 出货前被装入出口纸箱的成品首饰盒 |
| 27 | `Finished boxes packed into cartons, Shantou.` | 完成したボックスをカートンに梱包、汕頭。 | 成品盒装箱，汕头。 |
| 28 | `Supplier photos, used with permission.` | サプライヤー提供の写真（使用許諾済み）。 | 供应商提供的照片（已获使用许可）。 |

## 11. 回译：简体中文（28 条全量）

zh-CN 是中文目标语，回译即值本身，故本表不再重复抄值，改列「语义对应与用词依据」，供逐条核对是否走意、是否踩到供应商表述。

| # | en | zh-CN 值 | 语义对应 / 用词依据 |
|---|---|---|---|
| 1 | `More in this category` | 该分类下的更多内容 | 与词典既有 `category` 译法一致；不用「更多商品」以免暗示列表页 |
| 2 | `Related reading` | 相关阅读 | 版块标题，4 字，与已有中文版块标题长度一致 |
| 3 | `{Topic} \| Packrivo` | {Topic} \| Packrivo | 占位符与品牌后缀原样 |
| 4 | `Drawer Box With Ribbon Pull` | 带丝带拉手的抽屉盒 | 「抽屉盒」为现有硬盒品类名；`ribbon` 词典取「丝带」 |
| 5 | `Corrugated Wine Bottle Shipper` | 瓦楞葡萄酒瓶运输箱 | `shipper` 译「运输箱」而非「邮寄盒」，因为它是外箱 |
| 6 | `Subscription Mailer Box With Insert` | 带内托的订阅邮寄盒 | 「内托」是包装业对 insert 的标准叫法 |
| 7 | `Eco Kraft Cosmetic Box for Small Batches` | 小批量环保牛皮纸化妆品盒 | 与词典里 `Eco Kraft … (MOQ 250)` 条目同构，去掉 MOQ |
| 8 | `Magnetic Book-Style Presentation Box` | 磁吸书本式展示盒 | `presentation box` 沿用词典「展示盒」 |
| 9 | `Custom Kraft Soap Box With Window` | 定制牛皮纸开窗皂盒 | 与词典 `Custom Kraft Soap Box with Window (MOQ 250)` 同构 |
| 10 | `Jewelry Pouch and Box Set` | 珠宝袋与首饰盒套装 | `jewelry` 在「袋」语境取「珠宝袋」、在「盒」语境取「首饰盒」，跟随词典两处既有取法 |
| 11 | `Kraft Stand-Up Pouch With Window` | 牛皮纸开窗自立袋 | `stand-up pouch` 词典作「自立袋」 |
| 12 | `Jewelry Boxes, Pouches and Bags — Reference Catalogue` | 首饰盒、首饰袋与包装袋 — 参考目录 | 「包装袋」用于 `Bags`，与第 10 条「珠宝袋」区分 |
| 13 | `Pink Jewelry Storage Box` | 粉色首饰收纳盒 | `storage box` 译「收纳盒」，与「包装盒」区分用途 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | 首饰包装盒 — 编号 996892 | `Ref.` 按词典中文习惯作「编号」，数字原样 |
| 15 | `Velvet-Lined Boxes — Reference Catalogue` | 天鹅绒内衬盒 — 参考目录 | `velvet-lined` 词典作「天鹅绒内衬」 |
| 16 | `Velvet-Lined Box — Velvet, Faux Fur` | 天鹅绒内衬盒 — 天鹅绒、仿毛 | `faux fur` 词典作「仿毛」，两个材质并列 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | 如需报价，请发邮件给我们，或通过 WhatsApp 联系我们。 | 保留品牌名；`quote` 作「报价」，与词典 CTA「获取报价」同源 |
| 18 | `illustrative walkthroughs` | 示例性流程讲解 | 原文是句中链接文字（小写开头），中文无需处理大小写；「讲解」对应 walkthrough |
| 19 | `English` | English | 见第 4 节 |
| 20 | `Supplier sample room with shelves of rigid gift boxes and jewelry boxes` | 供应商样品间，货架上摆放着硬质礼盒和首饰盒 | 保留「供应商」，未改成「我们」；`rigid` 译「硬质」 |
| 21 | `Supplier sample room, Dongguan. Structures are chosen from real samples before artwork starts.` | 供应商样品间，东莞。结构在开始设计稿之前就从实物样品中选定。 | 「供应商」保留；`artwork` 译「设计稿」，`structures` 译「结构」 |
| 22 | `Operator feeding a printed proof sheet into a wide-format digital proofing printer` | 操作员将打好的样张送入大幅面数码打样机 | 印刷业标准说法：proof sheet 样张、proofing printer 打样机 |
| 23 | `Digital proof printing at a supplier in Dongguan.` | 东莞一家供应商的数码打样。 | 「一家供应商」保留第三方身份，未写成自家工厂 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | 包装厂内的多色单张纸胶印机 | `sheet-fed offset` 标准译「单张纸胶印」；factory 为拍摄地点 |
| 25 | `Sheet-fed offset press used for production runs.` | 用于量产的单张纸胶印机。 | `production runs` 译「量产」 |
| 26 | `Finished jewelry boxes packed in export cartons before shipping` | 成品首饰盒在发货前装入出口纸箱 | `export cartons` 译「出口纸箱」 |
| 27 | `Finished boxes packed into cartons, Shantou.` | 成品盒装箱，汕头。 | 地名「汕头」；句式为并列短语，保留逗号结构 |
| 28 | `Supplier photos, used with permission.` | 供应商照片，经许可使用。 | 「经许可使用」直接对应 `used with permission`，未弱化为「感谢提供」 |

## 12. 抽样回译：其余 8 个语言（每语言 5 条）

抽样固定 5 条：第 4（缎带拉手抽屉盒，考 `ribbon`）、第 14（`Ref.` + 编号）、第 28（许可表述）、第 24（`attr` + 工厂地点）、第 17（CTA + 品牌名）。

### de

| # | en | de 值 | 回译（中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | Schubladenbox mit Bandgriff | 带条带状把手的抽屉盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | Schmuckverpackungsbox — Ref. 996892 | 首饰包装盒 — Ref. 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | Mehrfarbige Bogenoffsetdruckmaschine in einer Verpackungsfabrik | 包装厂里的多色单张纸胶印机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | Für ein Angebot schreiben Sie uns eine E-Mail oder kontaktieren Sie uns über WhatsApp. | 如需报价，请给我们写邮件，或通过 WhatsApp 联系我们。 |
| 28 | `Supplier photos, used with permission.` | Lieferantenfotos, mit Erlaubnis verwendet. | 供应商照片，经许可使用。 |

### es

| # | en | es 值 | 回译（中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | Caja con cajón y tirador de cinta | 带抽屉和缎带拉手的盒子 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | Caja de embalaje para joyería — Ref. 996892 | 首饰包装盒 — Ref. 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | Prensa de offset multicolor de pliego en una fábrica de embalajes | 包装厂里的多色单张纸胶印机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | Para solicitar una cotización, escríbanos por correo electrónico o contáctenos por WhatsApp. | 如需报价，请通过电子邮件给我们写信，或通过 WhatsApp 联系我们。 |
| 28 | `Supplier photos, used with permission.` | Fotos del proveedor, utilizadas con permiso. | 供应商照片，经许可使用。 |

### fr

| # | en | fr 值 | 回译（中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | Boîte à tiroir avec tirette en ruban | 带缎带拉手的抽屉盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | Boîte d’emballage pour bijoux — Réf. 996892 | 首饰包装盒 — Réf. 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | Presse offset feuille multicolore dans une usine d’emballage | 包装厂里的多色单张纸胶印机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | Pour obtenir un devis, écrivez-nous par e-mail ou contactez-nous sur WhatsApp. | 如需获取报价，请给我们写电子邮件，或在 WhatsApp 上联系我们。 |
| 28 | `Supplier photos, used with permission.` | Photos du fournisseur, utilisées avec autorisation. | 供应商照片，经授权使用。 |

### it

| # | en | it 值 | 回译（中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | Scatola a cassetto con maniglia in nastro | 带缎带拉手的抽屉盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | Scatola per packaging gioielli — Rif. 996892 | 首饰包装盒 — Rif. 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | Macchina da stampa offset a foglio multicolore in una fabbrica di packaging | 包装厂里的多色单张纸胶印机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | Per un preventivo, scrivici un’e-mail o contattaci su WhatsApp. | 如需报价，请给我们写邮件，或在 WhatsApp 上联系我们。 |
| 28 | `Supplier photos, used with permission.` | Foto del fornitore, utilizzate su autorizzazione. | 供应商照片，经授权使用。 |

### ko

| # | en | ko 值 | 回译（中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | 리본 풀 손잡이 서랍 박스 | 带缎带拉手把手的抽屉盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | 주얼리 포장 박스 — 참조 번호 996892 | 首饰包装盒 — 参考编号 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | 포장 공장의 다색 매엽 오프셋 인쇄기 | 包装厂里的多色单张纸胶印机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | 견적이 필요하시면 이메일을 보내시거나 WhatsApp으로 문의해 주십시오. | 如需报价，请发邮件或通过 WhatsApp 咨询。 |
| 28 | `Supplier photos, used with permission.` | 공급업체 사진, 사용 허가를 받았습니다. | 供应商照片，已获使用许可。 |

### nl

| # | en | nl 值 | 回译（中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | Ladedoos met linttrekker | 带条带拉手的抽屉盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | Sieradendoos voor verpakking — Ref. 996892 | 首饰包装盒 — Ref. 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | Meerkleurenveloffsetpers in een verpakkingsfabriek | 包装厂里的多色单张纸胶印机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | Voor een prijsopgave kunt u ons e-mailen of contact opnemen via WhatsApp. | 如需报价，您可以给我们发邮件，或通过 WhatsApp 联系我们。 |
| 28 | `Supplier photos, used with permission.` | Foto’s van de leverancier, gebruikt met toestemming. | 供应商的照片，经允许使用。 |

### pt

| # | en | pt 值 | 回译（中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | Caixa com gaveta e puxador de fita | 带抽屉和缎带拉手的盒子 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | Caixa de embalagem de joias — Ref. 996892 | 首饰包装盒 — Ref. 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | Máquina de impressão offset multicolor de folhas numa fábrica de embalagens | 包装厂里的多色单张纸胶印机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | Para uma cotação, envie-nos um e-mail ou contacte-nos pelo WhatsApp. | 如需报价，请给我们发邮件，或通过 WhatsApp 联系我们。 |
| 28 | `Supplier photos, used with permission.` | Fotografias do fornecedor, utilizadas com autorização. | 供应商照片，经授权使用。 |

### zh-TW

| # | en | zh-TW 值 | 回译（简体中文） |
|---|---|---|---|
| 4 | `Drawer Box With Ribbon Pull` | 附緞帶拉手的抽屜盒 | 带缎带拉手的抽屉盒 |
| 14 | `Jewelry Packaging Box — Ref. 996892` | 珠寶包裝盒 — 編號 996892 | 首饰包装盒 — 编号 996892 |
| 24 | `Multi-colour sheet-fed offset printing press in a packaging factory` | 包裝廠內的多色單張紙平版印刷機 | 包装厂内的多色单张纸平版印刷机 |
| 17 | `For a quote, email us or contact us on WhatsApp.` | 如需報價，請寄電子郵件給我們，或透過 WhatsApp 與我們聯絡。 | 如需报价，请寄电子邮件给我们，或通过 WhatsApp 与我们联系。 |
| 28 | `Supplier photos, used with permission.` | 供應商照片，經許可使用。 | 供应商照片，经许可使用。 |

## 13. 自检原始输出

复现命令：

```powershell
$py='C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe'
& $py -X utf8 E:\破甲工作区\packrivo-backup\dsh-out\31b-tools\build_csv.py
& $py -X utf8 E:\破甲工作区\packrivo-backup\dsh-out\31b-tools\check_csv.py
& $py -X utf8 E:\破甲工作区\packrivo-backup\dsh-out\31b-tools\check_report.py
```

构建输出（原样）：

```text
strings   : 28
data rows : 308
languages : 11
bom       : absent
bytes     : 32846
path      : E:\破甲工作区\packrivo-backup\dsh-out\31b-i18n.csv
```

自检输出（原样）：

```text
C1 strings=28 rows=308 langs=11
C3 placeholder diffs=0
C4 empty values=0
C5 digit/token errors=0
C6 place-name errors=0
C6 place forms used:
   Dongguan  ar=دونغقوان, de=Dongguan, es=Dongguan, fr=Dongguan, it=Dongguan, ja=東莞, ko=둥관, nl=Dongguan, pt=Dongguan, zh-CN=东莞, zh-TW=東莞
   Shantou   ar=شانتو, de=Shantou, es=Shantou, fr=Shantou, it=Shantou, ja=汕頭, ko=산터우, nl=Shantou, pt=Shantou, zh-CN=汕头, zh-TW=汕頭
C7 supplier rows=3
C7 supplier token errors=0
C8 permission token errors=0
C9 factory self-description hits=0
C10 missing.json rows=28
C10 already-present strings inside CSV=0
X  English values=['English']
X  bucket distribution (rows): {'text': 242, 'title': 11, 'attr': 55}
X  strings per bucket: {'title': 1, 'attr': 5, 'block': 0, 'text': 22}

=== RESULT ===
PASS  0 errors across C1-C10
```

检查项定义：

| 项 | 判据 | 结果 |
|---|---|---|
| C1 | 行数 308 = 28×11，每语言 28 行，每条英文 11 行且桶唯一 | 0 错误 |
| C2 | 表头 `en,bucket,lang,value,origin(new)`、bucket 在词表内、origin 全为 `new` | 0 错误 |
| C3 | 每条占位符集合（`{Topic}`）与英文原文一致 | 0 错误 |
| C4 | 空值数 | 0 |
| C5 | 数字序列逐条与英文原文完全一致；`Packrivo`、`WhatsApp` 出现次数一致 | 0 错误 |
| C6 | 含地名的条目在对应语言里出现该地名（原样或本地写法） | 0 错误 |
| C7 | 3 条 `Supplier …` 条目各语言均含 supplier 词根 | 0 错误 |
| C8 | 第 28 条各语言均含许可词 | 0 错误 |
| C9 | 208 行全量扫 21 个「自称工厂」短语 | 0 命中 |
| C10 | 与 `missing.json` 双向对齐（28 条全在、无多余、桶一致），9 条已有串未混入 | 0 错误 |

### 13.1 报告与 CSV 的一致性校验

回译表是手写进本报告的，存在抄错的可能，所以另加一道机检：`check_report.py` 解析本报告里所有 4 列回译表，按所在小节判定语言，逐行取出 `(en, lang, value)` 与 CSV 原值做**字节级比对**，并要求 ar/ja/zh-CN 各 28 行、其余 8 语言各 5 行。

原样输出：

```text
report tables matched against CSV:
  ar     28 rows (expected 28)  ok
  ja     28 rows (expected 28)  ok
  zh-CN  28 rows (expected 28)  ok
  de      5 rows (expected  5)  ok
  es      5 rows (expected  5)  ok
  fr      5 rows (expected  5)  ok
  it      5 rows (expected  5)  ok
  ko      5 rows (expected  5)  ok
  nl      5 rows (expected  5)  ok
  pt      5 rows (expected  5)  ok
  zh-TW   5 rows (expected  5)  ok
  total matched rows: 124

=== RESULT ===
PASS  report tables are byte-identical to the CSV values
```

124 = 28×3（ar/ja/zh-CN 全量）+ 5×8（抽样）。报告里 124 个值全部与 CSV 逐字节一致，无转写漂移。

## 14. 待评审点

1. 第 21/23/27 条汉字圈与阿拉伯语的地名写法（第 5 节），若要求严格保留拉丁写法需改 15 行。
2. 第 19 条 `English`（第 4 节），若该串实际用途不是切换器条目需改 11 行。
3. 第 12 条 `Bags` 的中文取词（「包装袋」vs「手提袋」），取决于该链接指向的真实品类页。
4. 第 4 条 `Ribbon Pull` 的德语 `Bandgriff`、荷语 `linttrekker` 是直译式复合词，若客户方有人名化品类词可替换。

## 15. 文件清单

| 路径 | 说明 |
|---|---|
| `dsh-out/31b-i18n.csv` | 交付物，308 行，UTF-8 无 BOM，LF |
| `dsh-out/31b-i18n-report.md` | 本报告 |
| `dsh-out/31b-tools/find_missing.py` | 缺失判定脚本（四桶全语言比对） |
| `dsh-out/31b-tools/missing.json` | 28 条缺失清单（含出现位置与页面） |
| `dsh-out/31b-tools/translations.py` | 28×11 译值数据源 |
| `dsh-out/31b-tools/build_csv.py` | 生成 CSV |
| `dsh-out/31b-tools/check_csv.py` | C1–C10 自检 |
| `dsh-out/31b-tools/check_report.py` | 本报告回译表与 CSV 的逐字节比对 |
| `dsh-out/31b-tools/patch_values.py` | 五处译值的定向修订（nl 三处、pt 两处），已执行；重跑会因断言不匹配而中止，属预期 |
| `dsh-out/31b-tools/probe_terms.py`、`probe_terms2.py` | 术语收集脚本（只读，产物在 `E:\破甲工作区\.tmp\31b\`） |

未执行：`git push`（任务要求不推）。本任务未改动 `deploy/i18n-merged/` 下任何词典文件，也未触碰 `dsh-out/35a-*`、`_bak-35a-*`、`dsh-out/36a-landing/`。
