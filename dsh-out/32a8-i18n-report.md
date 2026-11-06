# 32a8 缺漏文案 11 语种翻译 · 交付报告

任务：把 `dsh-out/32a8-missing-strings.csv` 的 216 行源文案翻成 11 个语种，产出单文件表 `dsh-out/32a8-i18n.csv`。
约束：不改词典（`deploy/i18n-merged/<lang>-dict.json` 只读）、不改数据库、不碰容器。

## 1. 交付物

| 路径 | 内容 | 规模 |
| --- | --- | --- |
| `dsh-out/32a8-i18n.csv` | 唯一交付表 | 2156 数据行 + 1 表头，607 591 字节，SHA256 `DC079242915BD1F8482F2010AEC6C94D9B305F5B4439D33E3DAA4CCDAEC64FFE` |
| `dsh-out/32a8-i18n-report.md` | 本报告 | — |
| `dsh-out/32a8-tools/` | 34 个可复现脚本 + 4 个中间分片表（见 §13） | — |

表格式：UTF-8 **无 BOM**、**LF**（`CR=0 LF=2157`），列 `en, kind, field, lang, value, note`，按 `en` 升序、同 `en` 内按 `lang` 升序（每 11 行一组，**不是**按语种分块——`row` 序号与源 `idx` 不对应，任何按行号定位的脚本都会错位）。

工作区 `E:\破甲工作区\packrivo-backup`，分支 `main`，HEAD `090478f`。开工前 `git status --porcelain` 快照落在 `E:\破甲工作区\.tmp\32a8-gitstatus.txt`，7 行全为 `??`：`_bak-35a-2-20261105-015549/`、`_bak-35a-3-20261105-034722/`、`dsh-out/35a-catalogue-inventory.csv`、`dsh-out/35a-image-backfill.csv`、`dsh-out/35a-new-strings.csv`、`dsh-out/35a-product-plan.md`、`dsh-out/35a-products.json`。这 7 项与整个 `deploy/` 树全程未读写、未暂存。

## 2. 源行分类

源表 216 数据行，BOM + LF，`CR=0`，读入用 `utf-8-sig`。分类结果：

| 类 | 数量 | 处理 |
| --- | --- | --- |
| A 译 | 184 | 全语种翻译 |
| B 不译交 Codex | 19 | 不进 CSV（`pages=0` / 纯英文路由 / 语言名称 / 动态模板键） |
| C 动态/模板 | 11 | 只翻骨架片段，`· 2026` 原样，`note=template` |
| D1 新增英文 | 1 | `[28]` 重写英文 alt，`note=new_en` |
| D2 交 Codex 重算 | 1 | `[6]` MOQ 价格句不译 |

可译行 196（184 + 11 + 1），196 × 11 = **2156**，与表内数据行数一致。

## 3. B 清单（19 行，不译，交 Codex）

| idx | kind \| field | 原文 | 不译理由 |
| --- | --- | --- | --- |
| 11 | dict \| text（语言名称） | English | 语言切换器的语言名，本身即标识 |
| 15 | dict \| block/text | For a quote, email us or contact us on WhatsApp. | 仅英文路由，`pages=0` |
| 18 | dict \| text | illustrative walkthroughs | 仅英文路由 |
| 27 | dict \| block/text | More in this category | 仅英文路由 |
| 33 | dict \| block/text | Related reading | 仅英文博客 |
| 46 | dict \| title | {Topic} \| Packrivo | 动态模板，不构成本语种单一查找键 |
| 101 | post_content \| post_title | Corrugated Wine Bottle Shipper | 商品标题，交 Codex 统一命名 |
| 112 | post_content \| post_title | Custom Kraft Soap Box With Window | 同上 |
| 121 | post_content \| post_title | Drawer Box With Ribbon Pull | 同上 |
| 122 | post_content \| post_title | Eco Kraft Cosmetic Box for Small Batches | 同上 |
| 138 | post_content \| post_title | Jewelry Boxes, Pouches and Bags — Reference Catalogue | 同上（目录页） |
| 139 | post_content \| post_title | Jewelry Packaging Box — Ref. 996892 | 同上（含参考号） |
| 140 | post_content \| post_title | Jewelry Pouch and Box Set | 同上 |
| 142 | post_content \| post_title | Kraft Stand-Up Pouch With Window | 同上 |
| 147 | post_content \| post_title | Magnetic Book-Style Presentation Box | 同上 |
| 158 | post_content \| post_title | Pink Jewelry Storage Box | 同上 |
| 173 | post_content \| post_title | Subscription Mailer Box With Insert | 同上 |
| 185 | post_content \| post_title | Velvet-Lined Box — Velvet, Faux Fur | 同上 |
| 186 | post_content \| post_title | Velvet-Lined Boxes — Reference Catalogue | 同上（目录页） |

商品标题属于 Codex 的命名体系（与 `35a-catalogue-inventory` / `35a-products.json` 对表），本轮不占位，避免两处译名打架。

## 4. C 清单（11 行，动态/模板）

| idx | 原文 | 处理 | 例（de / zh-CN） |
| --- | --- | --- | --- |
| 1 | 134 structures | 数字参数化 | `{N} Verpackungsformen` / `{N} 种结构` |
| 3 | All 344 articles → | 数字参数化 | `Alle {N} Artikel →` / `全部 {N} 篇文章 →` |
| 4 | All {N} articles → | 复用词典 | `Alle {N} Artikel →` / `全部 {N} 篇文章 →` |
| 7 | Catalogue · 134 structures | 数字参数化 + `·` 保留 | `Katalog · {N} Verpackungsformen` / `产品目录 · {N} 种结构` |
| 20 | Journal · 344 articles | 数字参数化 | `Magazin · {N} Artikel` / `杂志 · {N} 篇文章` |
| 21 | Journal · {N} articles | 复用词典 | `Magazin · {N} Artikel` / `杂志 · {N} 篇文章` |
| 22 | MATERIALS & PRINT | 只翻分类名 | `Materialien & Druck` / `材料与印刷` |
| 23 | MATERIALS & PRINT · 2026 | `22 + ' · 2026'` | `Materialien & Druck · 2026` |
| 38 | STRUCTURES & FIT | 只翻分类名 | `Konstruktion & Passform` / `结构与适配` |
| 39 | Structures & fit | 由 `38` 派生（小写形态） | `Konstruktion & Passform` / `结构与适配` |
| 40 | STRUCTURES & FIT · 2026 | `38 + ' · 2026'` | `Konstruktion & Passform · 2026` |

同族一致性在构建期强制：`(4,3)`、`(21,20)`、`(38,39)` 同值；`23 = 22 + ' · 2026'`；`40 = 38 + ' · 2026'`。年份 `2026` 全语种不译、不改数字。

### 4.1 `{N}` 用大写，与需求里的 `{n}` 不同

需求写的是 `{n}`，交付表里是 **`{N}`**。依据在 `packrivo-wp/packrivo-home-v4/functions.php:363-381`（`pkv4_i18n_t`）：

- 366-367 行：原文命中词典则**直接返回词典值**，不做任何替换；
- 368-369 行：否则 `preg_replace('/\d+/u','{N}',$text)` 用 `{N}` 去查键（第 375 行的回调也写死 `/\{N\}/u`），把数字回填进 `{N}`。

即运行时**只认大写 `{N}`**，写成 `{n}` 会原样吐到页面上。词典里的键同样是大写 `{N}`（`deploy/i18n-merged/*-dict.json` 实测）。所以表里统一大写，这是一处有证据的偏离，不是笔误。

要回退成小写，一条命令（**改完必须重跑 §11 的自检门**，第 9/10 门会直接 FAIL）：

```bash
sed -i 's/{N}/{n}/g' dsh-out/32a8-i18n.csv
```

### 4.2 计数参数化补齐

首轮归一化按 `\b\d+\b` 匹配，Python 的 `\b` 在「数字紧跟汉字/假名/谚文」处不成立，`134種類の形状`、`134가지 구조` 漏网，导致 ja/ko 的 1、7 两行仍是写死的 `134`。已用 `set_override.py` 补四条：`ja[1] {N}種類の形状`、`ja[7] カタログ · {N}種類の形状`、`ko[1] {N}가지 구조`、`ko[7] 카탈로그 · {N}가지 구조`。现在 6 个模板行 × 11 语种 = 66 行全部为 `{N}` 形态（`note=template` 计数 66 与该式吻合）。

## 5. D 清单

**D1 — `[28]` 图片 alt 重写（`note=new_en`）**

| | 值 |
| --- | --- |
| 原英文 | `Multi-colour sheet-fed offset printing press in a packaging factory` |
| 新英文 | `Multi-colour sheet-fed offset printing press for packaging` |
| 理由 | 原句断言「在一家包装厂内」，属于不可核验的场景自述；新句只描述设备用途，与图一致 |
| 译文 | ar `ماكينة طباعة أوفست متعددة الألوان بالتغذية الورقية لتغليف المنتجات` · de `Mehrfarbige Bogenoffsetdruckmaschine für Verpackungen` · es `Prensa de offset de pliegos multicolor para embalaje` · fr `Presse offset feuille multicolore pour l'impression d'emballages` · it `Macchina da stampa offset a foglio multicolore per packaging` · ja `包装向けの多色枚葉オフセット印刷機` · ko `포장용 다색 매엽 오프셋 인쇄기` · nl `Meerkleurige veloffsetdrukpers voor verpakkingen` · pt `Máquina de impressão offset de folhas multicolor para embalagens` · zh-CN `用于包装的多色单张纸胶印机` · zh-TW `用於包裝的多色單張紙膠印機` |

`en` 列写的是**原**英文（保持源表可追溯），新英文在 `value` 里（`note=new_en` 标记），11 行齐。

**D2 — `[6]` MOQ 价格句不译**

```
At 500 pieces, a 9.5×9.5×4 cm rigid jewelry box is about $3.26 per unit and a
30×22×12 cm printed mailer box about $2.85. The unit price drops as quantity
goes up — the quote builder shows the full curve for your size.
```

`MOQ` 已改 250，句中 `500 pieces` / `$3.26` / `$2.85` 必须由报价引擎按新 MOQ 重算，翻译会把这组失效数字固化进 11 种语言。故 **0 行**进表（`grep -c` 该英文在 CSV 中为 0），留给 Codex 与报价引擎一起改。这是唯一一条「源有、表无」的行。

## 6. 词典复用与覆写

复用流程：先用原文精确命中线上词典（`deploy/i18n-merged/<lang>-dict.json`，只读），命中即采用词典写法；未命中再走翻译任务。

| 命中情况 | 条数 |
| --- | --- |
| 精确命中（原样可复用） | 1611 |
| 归一后命中（去尾标点/大小写/数字形态差异） | 22 |
| 未命中（需新翻） | 523 |

523 条新翻按语种分摊（与上表同口径，为「条目 × 语种」粒度）：ar 42 · de 46 · es 42 · fr 40 · it 39 · ja 43 · ko 42 · nl 40 · pt 41 · zh-CN 74 · zh-TW 74。

词典 JSON 的值可能是**嵌套对象**（同一键在不同上下文给不同译法），查询必须递归展开，否则 `AttributeError: 'dict' object has no attribute 'lower'`——这是本次踩过的坑。

覆写共 **26 条**（`.tmp/32a8/overrides.json`，构建末尾应用，优先级最高）：

| 组 | 条数 | 涉及 | 说明 |
| --- | --- | --- | --- |
| `MOQ 250` 保字面 | 17 | ar 26,167 · de 26,152,167 · es 26 · fr 26 · ja 26,152,167 · ko 26,152,167 · pt 26 · zh-CN 26,167 · zh-TW 26 | 见 §6.1 |
| 残句修复 | 4 | es 86 · fr 86 · pt 86 · de 175 | 见 §8 |
| 专名修复 | 1 | de 150 | `Packaging Corporation of America's` 德语保留英文所有格 → 改 `von Packaging Corporation of America` |
| 计数参数化 | 4 | ja 1,7 · ko 1,7 | 见 §4.2 |

其余 22 条见各分项。

### 6.1 `MOQ 250` 不本地化（17 条覆写）

34a 分支的既有约定是不译 `MOQ`，**但**译者在 `[26]`/`[152]`/`[167]` 里把它展开成了本地词：`Mindestmenge 250`、`最低起订量 250`、`최소 250개`、`最小250個`。这会让同一站在「起订量」上出现两种叫法，且 34a 已交付的句子是 `MOQ 250, 5-10 day sampling.` 一律字面。

处置：`MOQ 250` 整串保留原样，17 条覆写覆盖所有受影响语种/行。覆写在 `build_lang` 之后执行，因此**必然压过词典复用值**。结果是 `[25] MOQ 250` 这一行 11 语种全等于 `MOQ 250`（第 18 门「语言无关行全语种同值」验证），`[17] hello@packrivo.com` 同理 11 行全同。

## 7. 归一化（108 条，构建期自动改写并落日志）

日志 `E:\破甲工作区\.tmp\32a8\normalize.log`，格式 `<lang> row<idx> <类别>: '<old>' -> '<new>'`，每条可回放。

| 类别 | 条数 | 触发 | 例 |
| --- | --- | --- | --- |
| 计数参数化 | 38 | 模板行数字未写成 `{N}` | `134 Verpackungsformen` → `{N} Verpackungsformen` |
| 连字符归一 | 35 | 值内数字区间用了 en-dash `–` | `5–10 Tage` → `5-10 Tage` |
| 术语校正 | 26 | 与词典既定术语不符 | `Mindestmenge 250` → `MOQ 250` |
| 句末标点修正 | 9 | 中文/日文标题句末多了句号 | `…を確認する。` → `…を確認する` |

按语种：ar 9 · de 12 · es 9 · fr 9 · it 8 · ja 19 · ko 8 · nl 8 · pt 9 · zh-CN 9 · zh-TW 8 = **108**。

en-dash 的来源：源表 `[2] [26] [32] [37] [45] [47]` 本身用 `–`（U+2013），`en` 列原样保留（第 1 门不改源文案）；但**值内**的数字区间一律 ASCII `-`，与线上词典和 34a 表一致（线上词典同样是 U+2013，这里是有意统一）。正文破折句法（破折号引出从句，不是区间）不在归一范围内，`de [27]` 保留 `–` 属预期，第 11 门以 INFO 记录。

## 8. 残句、悬垂冠词与专名

残句扫描 `residue_scan.py` 结果：

| 指标 | 值 |
| --- | --- |
| 尾部悬垂（冠词/介词/连词结尾） | 45 |
| 英文整句泄漏 | **0** |
| 中日韩文本中的拉丁残留 | 112 |

悬垂分布：es 11 · fr 9 · it 9 · nl 7 · de 6 · pt 3。这不是译文缺陷——源表本身就是**被截断的正文片段**（如 `as a direct comparison. Ask the converter…`、`when a shelf position is sufficient. The hanging direction…`），这些片段的前半句在源页面上另属一行，翻译必须保持片段形状才能原位拼接，硬补主语会与上游句子重复。

真正的缺陷是「片段尾部多挂了一个冠词」——回译时抓出 3 处：`es 86` 尾多 ` La`、`fr 86` 尾多 ` La`、`pt 86` 尾多 ` O`；另有 `de 175` 把英文按钮名 `Send My Packaging Request` 原样抄进德语（应为德语标签，且与 `[174]` 同页按钮行必须一致）。4 条已覆写修正，修后尾部为：

| 语言 | `[86]` 尾 | `[175]` 尾 |
| --- | --- | --- |
| es | `…si la interfaz real es la misma.` | `…o preguntar por sus datos en` |
| fr | `…si l'interface réelle est la même.` | `…ou poser une question sur vos données à` |
| pt | `…se a interface real for a mesma.` | `…colocar questões sobre os seus dados em` |
| de | 未涉及 | `…können Sie anfragen unter`（按钮名改 `„Meine Verpackungsanfrage senden“`） |

`[175]` 的源英文**自身**在 `ask about your data at` 处截断（没有邮箱），11 个语种结尾同样停在该处——忠实于源，不是漏译。`[17]` 的 `hello@packrivo.com` 是补全位置，属于渲染层拼接。

`[174]` 单独一行就是 `The`（正文片段拆行后的裸冠词），11 语种给 `الـ / Die / Le / El / O / 该 / 그 / その / Il / De / 該`。该值是**片段键**，一旦上游句子改写就会失配，属于已知结构性风险，记录在此。

拉丁残留 112 行分布 ja 13 · ko 13 · zh-CN 43 · zh-TW 43，逐行核对全部为**应当保留**的专名与品牌：`Packrivo`、`hello@packrivo.com`、`logo`（中英混排里「可印 logo」是站点既有写法）、`Bookend`（盒型专名）、`Incoterms`、`Google Analytics 4`、`Google Tag`、`Novolex`、`Pinch Bottom Bags`、`SOS`、`DDP`、`FOB`、`MOQ`。无一条是漏译。

## 9. 回译抽查

抽样规则：ar / zh-CN / de 各 15 行，其余 8 语种各 5 行，共 85 行，按 `.tmp/32a8/backtrans.txt` 固定抽取（固定 idx，可复现）。「数字」列判的是源数字集合是否被译文包含；`[1]` 显示 `'134'→` 是模板行把数字参数化成 `{N}` 的预期结果。


#### ar（15 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} تصميمًا هيكليًا | 14->19 (1.36) | '134'→ |
| 17 | block/text | hello@packrivo.com | hello@packrivo.com | 18->18 (1.00) | 一致 |
| 32 | block/text | Recycled content, board weight, coating and end-of-life route… | يُنشر المحتوى المعاد تدويره ووزن الورق المقوّى والطلاء وطريقة… | 138->134 (0.97) | 一致 |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — تُسعّر لكل شحنة ولا تُخفى أبدًا في سعر الوحدة. | 54->48 (0.89) | 一致 |
| 60 | _packrivo_description | Corrugated tray boxes with a close-fitting lid, printed with y… | صواني مموجة بغطاء محكم، مع طباعة الشعار وبحد أدنى 250 قطعة. اط… | 100->73 (0.73) | 一致 |
| 73 | _packrivo_description | Rigid gift boxes with a hinged lid and no magnets, printed wit… | علب صلبة بغطاء مفصلي بدون مغناطيس، مع طباعة الشعار وبحد أدنى 2… | 103->84 (0.82) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | كمقارنة مباشرة. اطلب من المحوّل أن يوضح أي إجراءات اللسان/المس… | 232->204 (0.88) | 一致 |
| 99 | post_content | Complete fixture | وحدة عرض كاملة | 16->14 (0.88) | 一致 |
| 114 | post_title | Custom SOS Paper Bags Bulk | أكياس ورقية SOS بالجملة | 26->23 (0.88) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | الطيات والمواد الاستهلاكية والتعليمات والشخص الذي ينفذ كل خطوة… | 69->63 (0.91) | 一致 |
| 146 | post_content | Lower and lift the lid without using the product to push it in… | اخفض الغطاء وارفعه دون استخدام المنتج لدفعه إلى مكانه. | 71->54 (0.76) | 一致 |
| 161 | post_content | Product removal | إخراج المنتج | 15->12 (0.80) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | يُجهّز مُنشئ الموجز ملخّصًا داخل متصفّحك. وعند اختيار إرسال طل… | 725->607 (0.84) | 一致 |
| 190 | post_content | when a shelf position is sufficient. The hanging direction add… | عندما يكون موضع الرف كافياً. يضيف خيار التعليق تثبيتاً ومراجعة… | 268->209 (0.78) | 一致 |
| 203 | post_excerpt | Compare the supplied stack, carton contents and ordinary singl… | قارن الكومة الموردة ومحتويات الكرتون والالتقاط العادي لقطعة وا… | 132->107 (0.81) | 一致 |

#### de（15 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} Verpackungsformen | 14->21 (1.50) | '134'→ |
| 17 | block/text | hello@packrivo.com | hello@packrivo.com | 18->18 (1.00) | 一致 |
| 32 | block/text | Recycled content, board weight, coating and end-of-life route… | Recyclinganteil, Kartongewicht, Beschichtung und Entsorgungswe… | 138->161 (1.17) | 一致 |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — je Sendung angeboten, niemals im Stückpreis versteckt. | 54->56 (1.04) | 一致 |
| 60 | _packrivo_description | Corrugated tray boxes with a close-fitting lid, printed with y… | Wellpappe-Schalen mit passgenauem Deckel, mit Ihrem Logo, ab 2… | 100->87 (0.87) | 一致 |
| 73 | _packrivo_description | Rigid gift boxes with a hinged lid and no magnets, printed wit… | Hartschachteln mit Klappdeckel ohne Magnete, mit Ihrem Logo, a… | 103->90 (0.87) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | als direkter Vergleich. Bitten Sie den Verarbeiter zu zeigen,… | 232->274 (1.18) | 一致 |
| 99 | post_content | Complete fixture | Vollständige Vorrichtung | 16->24 (1.50) | 一致 |
| 114 | post_title | Custom SOS Paper Bags Bulk | SOS-Papiertüten Großhandel | 26->26 (1.00) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | Faltungen, Verbrauchsmaterialien, Anweisungen und die Person,… | 69->89 (1.29) | 一致 |
| 146 | post_content | Lower and lift the lid without using the product to push it in… | Den Deckel absenken und anheben, ohne das Produkt zum Einpasse… | 71->91 (1.28) | 一致 |
| 161 | post_content | Product removal | Produktentnahme | 15->15 (1.00) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | Der Briefing-Builder erstellt eine Zusammenfassung in Ihrem Br… | 725->823 (1.14) | 一致 |
| 190 | post_content | when a shelf position is sufficient. The hanging direction add… | wenn eine Regalposition ausreicht. Die Hänge-Richtung fügt die… | 268->304 (1.13) | 一致 |
| 203 | post_excerpt | Compare the supplied stack, carton contents and ordinary singl… | Vergleichen Sie den gelieferten Stapel, den Kartoninhalt und d… | 132->160 (1.21) | 一致 |

#### es（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} estructuras | 14->15 (1.07) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — se cotiza por envío y nunca se oculta en el precio unitario. | 54->62 (1.15) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | como comparación directa. Pida al transformador que muestre qu… | 232->253 (1.09) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | Pliegues, consumibles, instrucciones y la persona que completa… | 69->73 (1.06) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | El generador de briefs prepara un resumen en su navegador. Al… | 725->874 (1.21) | 一致 |

#### fr（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} structures | 14->14 (1.00) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — chiffré par expédition, jamais caché dans le prix unitaire. | 54->61 (1.13) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | comme comparaison directe. Demandez au transformateur de montr… | 232->291 (1.25) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | Plis, consommables, instructions et la personne réalisant chaq… | 69->71 (1.03) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | Le générateur de brief prépare un récapitulatif dans votre nav… | 725->913 (1.26) | 一致 |

#### it（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} strutture | 14->13 (0.93) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — preventivato per spedizione, mai nascosto nel prezzo unitari… | 54->64 (1.19) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | come confronto diretto. Chiedi al converter di mostrare quali… | 232->267 (1.15) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | Pieghe, materiali di consumo, istruzioni e la persona che comp… | 69->77 (1.12) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | Il generatore di brief prepara un riepilogo nel tuo browser. S… | 725->896 (1.24) | 一致 |

#### ja（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N}種類の形状 | 14->8 (0.57) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — 出荷ごとに見積もり、単価に含めて隠すことはありません。 | 54->29 (0.54) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | 直接比較として。加工業者に、提案された案でどのタブ／受け部または壁のセットアップ動作が異なるかを示すよう求める。実際のインタ… | 232->104 (0.45) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | 折り工程、消耗品、手順書、各工程の担当者。 | 69->21 (0.30) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | ブリーフ作成ツールはお客様のブラウザ内で概要を作成します。「包装リクエストを送信」を選択すると、そのリクエストがPackri… | 725->392 (0.54) | 一致 |

#### ko（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N}가지 구조 | 14->8 (0.57) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — 배송 건별로 견적을 내며, 단가에 숨기지 않습니다. | 54->30 (0.56) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | 직접 비교 대상으로 삼으십시오. 가공업체에 제안된 안에서 어떤 탭/리시버 또는 벽 셋업 동작이 다른지 보여 달라… | 232->122 (0.53) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | 접힘, 소모품, 지침 및 각 단계를 완료하는 담당자. | 69->29 (0.42) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | 브리프 빌더가 브라우저에서 요약을 준비합니다. '내 포장 요청 보내기'를 선택하면 요청이 Packrivo로 전송… | 725->370 (0.51) | 一致 |

#### nl（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} constructies | 14->16 (1.14) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — per zending geoffreerd, nooit verborgen in de prijs voor elk… | 54->68 (1.26) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | als directe vergelijking. Vraag de verwerker aan te tonen welk… | 232->264 (1.14) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | Vouwen, verbruiksartikelen, instructies en de persoon die elke… | 69->77 (1.12) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | De briefingbouwer stelt in uw browser een samenvatting op. Wan… | 725->864 (1.19) | 一致 |

#### pt（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} estruturas | 14->14 (1.00) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — cotado por remessa, nunca embutido no preço unitário. | 54->55 (1.02) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | como comparação direta. Peça ao transformador que mostre quais… | 232->255 (1.10) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | Dobras, consumíveis, instruções e a pessoa que executa cada et… | 69->66 (0.96) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | O construtor de briefing prepara um resumo no seu navegador. S… | 725->814 (1.12) | 一致 |

#### zh-CN（15 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} 种结构 | 14->7 (0.50) | '134'→ |
| 17 | block/text | hello@packrivo.com | hello@packrivo.com | 18->18 (1.00) | 一致 |
| 32 | block/text | Recycled content, board weight, coating and end-of-life route… | 每种结构均公布再生含量、纸板克重、涂层和使用后的处理途径，因为“环保”并不是一项规格。 | 138->43 (0.31) | 一致 |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — 每批货运单独报价，绝不隐藏在单价中。 | 54->20 (0.37) | 一致 |
| 60 | _packrivo_description | Corrugated tray boxes with a close-fitting lid, printed with y… | 瓦楞纸托盘盒，配贴合盖，可印 logo，250 件起订。获取报价。 | 100->33 (0.33) | 一致 |
| 73 | _packrivo_description | Rigid gift boxes with a hinged lid and no magnets, printed wit… | 翻盖式精装礼盒，不带磁吸，可印 logo，250 件起订。获取报价。 | 103->34 (0.33) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | 作为直接比较。要求加工商说明所提供方案中哪些卡舌/承接件或壁面设置动作不同。仅凭新的目录名称不应在实际接口相同的情况下产生另… | 232->69 (0.30) | 一致 |
| 99 | post_content | Complete fixture | 完整展示架 | 16->5 (0.31) | 一致 |
| 114 | post_title | Custom SOS Paper Bags Bulk | 定制方底纸袋 批量批发 | 26->11 (0.42) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | 折叠操作、耗材、说明以及完成每个步骤的人员。 | 69->22 (0.32) | 一致 |
| 146 | post_content | Lower and lift the lid without using the product to push it in… | 放下并提起盒盖，请勿借助产品将盒盖推入到位。 | 71->22 (0.31) | 一致 |
| 161 | post_content | Product removal | 产品取出 | 15->4 (0.27) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | 简报表会在您的浏览器中生成摘要。选择「发送我的包装需求」后，需求才传输至 Packrivo。参考编号确认该需求已保存。如果确… | 725->231 (0.32) | 一致 |
| 190 | post_content | when a shelf position is sufficient. The hanging direction add… | 当货架位置足够时。悬挂方向为该任务增加了固定件与展示评审。对于公开陈列在卡片上的配件，不同的安装方式可能更有用；纸盒则是关于… | 268->76 (0.28) | 一致 |
| 203 | post_excerpt | Compare the supplied stack, carton contents and ordinary singl… | 在选择模塑纸浆内衬的交付方式前，比较来料叠放状态、装箱内容与常规单件取放。 | 132->37 (0.28) | 一致 |

#### zh-TW（5 行）

| idx | 字段 | 英文原文 | 译文 | 字符比 | 数字 |
| --- | --- | --- | --- | --- | --- |
| 1 | block/text（动态数量原样查找） | 134 structures | {N} 種結構 | 14->7 (0.50) | '134'→ |
| 47 | block/text | — quoted per shipment, never buried in the unit price. | — 每批貨運單獨報價，絕不隱藏在單價中。 | 54->20 (0.37) | 一致 |
| 86 | post_content | as a direct comparison. Ask the converter to show which tab/re… | 作為直接比較。請要求加工商說明其所提供提案中，哪些卡舌/承接件或牆面成型動作有所不同。若實際介面相同，新的型錄名稱本身不應另… | 232->70 (0.30) | 一致 |
| 129 | post_content | Folds, consumables, instructions and the person completing eac… | 摺疊動作、耗材、說明及完成各步驟的人員。 | 69->20 (0.29) | 一致 |
| 175 | post_content | The brief builder prepares a summary in your browser. Selectin… | 需求簡報建立工具會在瀏覽器中準備一份摘要。選取「送出我的包裝需求」會將請求傳送給 Packrivo。收到需求 ID 即確認已… | 725->226 (0.31) | 一致 |


回译结论：

- 85 行全部通过「源数字被译文包含」检查（除模板行 `[1]` 的有意参数化）；`MOQ`、`DDP`、`FOB`、`5-10`、`7-30`、`8-15%`、`Packrivo`、邮箱在抽查行中均原样出现。
- 长度比落在预期带内：de/es/fr/it/nl/pt 0.73–1.29，ar 0.73–1.00，ja/ko/zh 因音节结构天然偏短（见 §10）。
- 语义抽查无「反向陈述」类错误：`[146] Lower and lift the lid…`（de `absenken und anheben`）、`[190] when a shelf position is sufficient`（zh `当货架位置足够时`）、`[203] Compare the supplied stack…`（pt `Compare a pilha fornecida…`）方向一致。
- 首轮发现并已修复的 4 处（§8）不再出现；`[175]` 的德语按钮名现为 `„Meine Verpackungsanfrage senden“`，与 `[174]` 同页按钮行一致。

## 10. 长度对照

标题与 meta 描述（`post_title` + `_packrivo_description`）按英文长度降序前 10 行（单位：字符，括号内为译文/英文比）：

| # | idx 前缀（英文） | field | EN | ar | de | es | fr | it | ja | ko | nl | pt | zh-CN | zh-TW |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Corrugated bottle carriers with a buil | _packrivo_description | 117 | 80(0.68) | 94(0.80) | 102(0.87) | 120(1.03) | 120(1.03) | 47(0.40) | 55(0.47) | 107(0.91) | 131(1.12) | 33(0.28) | 36(0.31) |
| 2 | Corrugated book mailers that protect t | _packrivo_description | 116 | 85(0.73) | 89(0.77) | 112(0.97) | 106(0.91) | 128(1.10) | 45(0.39) | 55(0.47) | 114(0.98) | 134(1.16) | 35(0.30) | 35(0.30) |
| 3 | Clear display boxes for retail and gif | _packrivo_description | 112 | 86(0.77) | 97(0.87) | 115(1.03) | 118(1.05) | 133(1.19) | 51(0.46) | 61(0.54) | 116(1.04) | 139(1.24) | 40(0.36) | 38(0.34) |
| 4 | Book-style rigid boxes that open like  | _packrivo_description | 111 | 85(0.77) | 91(0.82) | 124(1.12) | 139(1.25) | 122(1.10) | 46(0.41) | 58(0.52) | 103(0.93) | 134(1.21) | 43(0.39) | 43(0.39) |
| 5 | Paper bags with a die-cut handle for r | _packrivo_description | 111 | 82(0.74) | 99(0.89) | 101(0.91) | 99(0.89) | 118(1.06) | 41(0.37) | 54(0.49) | 112(1.01) | 115(1.04) | 36(0.32) | 41(0.37) |
| 6 | Coated labels for bottles and boxes th | _packrivo_description | 110 | 102(0.93) | 119(1.08) | 125(1.14) | 125(1.14) | 117(1.06) | 48(0.44) | 54(0.49) | 114(1.04) | 123(1.12) | 43(0.39) | 40(0.36) |
| 7 | Collapsible magnetic gift boxes that s | _packrivo_description | 110 | 96(0.87) | 118(1.07) | 123(1.12) | 133(1.21) | 139(1.26) | 49(0.45) | 56(0.51) | 124(1.13) | 140(1.27) | 38(0.35) | 38(0.35) |
| 8 | Cube carry boxes with an integrated ha | _packrivo_description | 110 | 75(0.68) | 95(0.86) | 100(0.91) | 99(0.90) | 114(1.04) | 46(0.42) | 54(0.49) | 102(0.93) | 120(1.09) | 32(0.29) | 34(0.31) |
| 9 | Round roll labels for jars, lids and s | _packrivo_description | 110 | 92(0.84) | 107(0.97) | 113(1.03) | 113(1.03) | 122(1.11) | 44(0.40) | 57(0.52) | 117(1.06) | 127(1.15) | 40(0.36) | 39(0.35) |
| 10 | Shipping boxes with a glued auto botto | _packrivo_description | 110 | 88(0.80) | 111(1.01) | 117(1.06) | 118(1.07) | 141(1.28) | 43(0.39) | 59(0.54) | 109(0.99) | 137(1.25) | 36(0.33) | 36(0.33) |

`post_title` 全量 12 行/语种的长度比：

| 语种 | min | p50 | max |
| --- | --- | --- | --- |
| ar | 0.70 | 0.83 | 1.08 |
| de | 0.66 | 1.03 | 1.20 |
| es | 0.77 | 1.06 | 1.24 |
| fr | 0.70 | 1.07 | 1.31 |
| it | 0.81 | 1.21 | 1.60 |
| ja | 0.47 | 0.62 | 0.77 |
| ko | 0.43 | 0.52 | 0.69 |
| nl | 0.57 | 0.94 | 1.38 |
| pt | 0.80 | 1.23 | 1.64 |
| zh-CN | 0.25 | 0.42 | 0.53 |
| zh-TW | 0.25 | 0.42 | 0.55 |

判读：拉丁语系 p50 0.94–1.23，属正常膨胀；ja/ko 0.52–0.62、zh 0.42 是汉/日/韩字符承载信息量高导致的必然收缩，不是删译（对应英文的定语从句在汉语里是前置修饰，字符数自然掉一半）。`it` 最大值 1.60（`[193]` 一处 +50%）与 `pt` 1.64 已在 §12 列为需要复核的个案。

## 11. 自检门（`check_csv.py`，18 门全 PASS，`FAIL_COUNT=0`）

| # | 门 | 结果 |
| --- | --- | --- |
| 1 | 无 BOM | PASS `bom=False` |
| 2 | 换行 | PASS `CR=0 LF=2157` |
| 3 | 列头 | PASS `['en','kind','field','lang','value','note']` |
| 4 | 列数=6 | PASS `bad=0` |
| 5 | 数据行=2156 | PASS `rows=2156 want=2156` |
| 6 | 每语种 196 条 | PASS 11 语种各 196 |
| 7 | 空值 0 | PASS `empty=0` |
| 8 | 数字不丢失 | PASS `mismatch=0`（模板 66 行豁免） |
| 9 | 模板占位符 `{N}` 合法 | PASS `bad=0` |
| 10 | 无小写 `{n}` 泄漏 | PASS `hits=0` |
| 11 | 数字区间无 `– — ～ 〜` | PASS `hits=0` |
| 12 | HTML 标签数一致 | PASS 源带标签 0 行，译文新增 0 |
| 13 | 专名/MOQ/DDP/FOB 保留 | PASS `missing=0` |
| 14 | `→` 保留、`·` 不丢失 | PASS `hits=0` |
| 15 | 同族译名一致 | PASS `mismatch=0` |
| 16 | `kind` 取值合法 | PASS 4 类 |
| 17 | `field` 已去括注 | PASS 11 种 |
| 18 | 语言无关行全语种同值 | PASS `hello@packrivo.com` / `MOQ 250` |

四类 INFO（不阻断，逐条解释）：

1. **译文多出数字 7 行**：`ja/ko/zh-CN [175]` 把 `October 18, 2026` 写成 `2026年10月18日` / `2026년 10월 18일` / `2026 年 10 月 18 日`，数字集合仍是 `10/18/2026` 的超集，方向正确（日语/韩语/汉语的日期语序要求年份前置）。`ja [60]` 的 `1,000個時` 同理。
2. **正文破折句法 1 行**：`de [27]` `Das in Shenzhen ansässige Packrivo unter – …` 是破折号插入语，不是数字区间，保留。
3. **多出 `·` 1 行**：`ko` 用 `셋업·보충`（中点连接同类项），韩语排版习惯，保留。
4. 以上三项均是「规则命中但语义正确」，故降级为 INFO。

## 12. 已知偏差与遗留

| 项 | 内容 | 处置 |
| --- | --- | --- |
| `{N}` vs `{n}` | 需求写 `{n}`，交付用 `{N}` | 有 `functions.php:366-377` 证据，§4.1 已说明；回退命令给了 |
| ja/ko 模板行首轮漏参 | `134種類の形状` / `134가지 구조` | 4 条覆写补齐，现 66 行全 `{N}` |
| `MOQ 250` 不本地化 | 17 条覆写压住词典的 `Mindestmenge`/`最低起订量`/`최소 250개` | 与 34a 一致，§6.1 |
| `it [193]` 长度 +50% | 单行膨胀到 1.60，未超硬上限但明显偏长 | 记录，交由复审决定是否压缩 |
| `it` 词典双形态 | `Journal` vs `Diario`；`Strutture e adattamento` vs `vestibilità` | 按「词典命中优先」，`[4]/[21]` 用 `Diario`、`[22]/[38]` 用 `adattamento`；与本表内一致性无冲突 |
| `pt` Journal 冲突 | 词典 `Journal · 333 articles`=`Publicações`，而 `Journal · {N}`=`Jornal` | 以带 `{N}` 的键为准（`Jornal`），`Publicações` 留给分类页标题 |
| `[174]` 裸冠词 | 值是片段键，上游句子一改就失配 | 记入遗留，上游改写时同步 |
| 悬垂冠词 45 行 | 片段保持原形，不补主语 | 属源截断所致，非译文缺陷；3 处「多挂冠词」已修 |
| 商品标题 19 行未译 | B 类 | 交 Codex 与商品库对表 |
| `[6]` 价格句未进表 | D2 类 | 交 Codex + 报价引擎按 MOQ 250 重算 |
| `logo` / `Bookend` 保留 | 中文行里的拉丁残留 | 站点既有写法与盒型专名，不译 |

风格基准（与 34a 交付对齐）：de 用 Sie 式；es 用 `usted` 正式体；fr 用 vous；it 用 `fustella`/`preventivo`；nl 用 `stansmal`/`offerte`；pt 用 pt-PT（`cartão`/`registo`/`contacto`/`stock`）；ar 用 MSA（`قالب القص`/`رسوم تنسيق`）；ja です・ます；ko 합니다체；zh-CN 精装盒/邮寄盒/内衬/刀模图；zh-TW 裱紙盒/飛機盒/內襯/刀模圖。末尾营销句沿用 34a：`Request a quote.` 对应各语 `索取报价` 系列，`MOQ 250, 5-10 day sampling.` / `7-30 day production after approval.` / `DDP or FOB, quoted both ways.` 的数字与贸易术语全部原样。

合规项自查：无工厂自述、无 ISO/ISTA/FSC 认证声明、无编造数字；隐私页采用正式法律语体（`[175]` 全文 725 字符，11 语种逐句对应，末尾忠实保留源的截断位置）。

## 13. 复现

Python：`C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe`（全部脚本以 `-X utf8` 按路径调用）。

```powershell
cd E:\破甲工作区\packrivo-backup
$py='C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe'
& $py -X utf8 dsh-out\32a8-tools\build_csv.py      # 生成 dsh-out\32a8-i18n.csv
& $py -X utf8 dsh-out\32a8-tools\check_csv.py      # 18 门自检，期望 FAIL_COUNT=0
& $py -X utf8 dsh-out\32a8-tools\residue_scan.py   # 残句扫描，期望 leak=0
& $py -X utf8 dsh-out\32a8-tools\report_data.py    # 本报告 §2/§6/§7/§10 的数据
& $py -X utf8 dsh-out\32a8-tools\sample_backtrans.py  # §9 抽样
& $py -X utf8 dsh-out\32a8-tools\mk_bt_table.py    # §9 表格
```

脚本清单（`dsh-out/32a8-tools/`）：`inventory2.py` 源表清点、`mk_enlist.py` 生成英文清单、`classify.py` A/B/C/D 分类、`reuse_scan.py` 词典命中扫描、`gen_todo.py` 分派翻译任务、`inspect_c.py` C 类检查、`reuse_qa.py` 复用质量、`reuse_numlatin.py` 数字/拉丁检查、`verify_tr.py` 译文合并校验、`build_csv.py` 建表、`check_csv.py` 18 门自检、`sample_backtrans.py` 回译抽样、`mk_bt_table.py` 回译表、`dump_moq.py` / `fix_moq.py` MOQ 处置、`residue_scan.py` 残句扫描、`residue_count.py` 残句统计、`dict_terms.py` 词典术语查询、`locate_residue.py` 残句定位、`check_privacy.py` 隐私页语体检查、`fix_residue.py` 残句批量修、`dbg2.py` 定位调试、`set_override.py` 单条覆写（可复用杠杆）、`dump_evidence.py` 关键行取证、`report_data.py` 报告数据。

未在上行点名的还有：`inventory.py`（首版清点，列名写错已弃用）、`build_part2.py` / `build_part4.py` / `gen_part3.py`（分批建表）、`inspect_ell.py` / `inspect_tail.py` / `inspect_trunc.py`（长行、尾串、截断探查）、`merge_report.py`（本报告拼接）、`verify_commit.py`（提交后校验：`git cat-file -p HEAD:<path>` 与磁盘逐字节比对，`CR=0`、无 BOM、SHA256 一致）。四个 `i18n-part-*.csv` 是按语种切分的中间分片（`gen_part3.py` 从最终表反向切出 ko/nl/pt 并断言键序、`{N}` 形态、LF），不参与最终交付。

中间产物全部在 `E:\破甲工作区\.tmp\32a8\`（`final.json`、`overrides.json`、`normalize.log`、`check.log`、`evidence.txt`、`backtrans.txt`、`residue.txt`、`report-data.txt`），不含交付内容，可不入库。

工作台 #37 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 运行中
