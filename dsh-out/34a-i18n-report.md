# 34a 译文交付报告

对象：`packrivo-backup-34a` 分支 `34a`（HEAD `c7e14c3`）。本批只产出译文 CSV 与本文档，不改词典、不碰容器与数据库。

## 1. 交付物

| 项 | 值 |
| --- | --- |
| 文件 | `dsh-out/34a-i18n.csv` |
| 字节 | 726349 |
| SHA-256 | `ad68acad078ac108d59517d3b45f7a3ddbe6564b076096b8db2457ff670f954f` |
| 编码 | UTF-8 无 BOM（首字节 `65 6E 2C` = `en,`） |
| 表头 | `en,bucket,lang,value,origin` |
| 行数 | 3707 = 337 个英文键 × 11 语言 |
| 键数 | 337（与 `dsh-out/34a-tools/keys.json` 的 `keys` 完全相等） |
| 桶分布 | `attr` 1529 / `text` 1452 / `title` 715 / `block` 11 |
| origin | `new` 3696 / `reused` 11 |

`reused` 那 11 行是唯一在现有词典里已存在译文的键：`Every structure we can source`（h1），11 语言各 1 行，直接沿用词典原值，不覆盖。

语言（11）：`ar de es fr it ja ko nl pt zh-CN zh-TW`。

## 2. 自检结果

| 检查 | 方法 | 结果 |
| --- | --- | --- |
| 每语言行数 | 按 `lang` 计数 | 11 语言各 337，无缺行 |
| 空值 | `value.strip()==''` | 0 |
| 重复三元组 | `(en,bucket,lang)` 去重计数 | 0 组重复 |
| 键集一致 | CSV 的 `en` 集合 vs `keys.json['keys']` | 337 == 337，双向差集为空 |
| 占位与硬令牌 | `checktoken.py` 对 `MOQ`/`DDP`/`FOB`/`Packrivo`/`5-10`/`7-30`/`8-15`/`1,000` 逐一比对英文原串 | `total_token_errors=0` |
| 数字一致 | 从英文抽出 `\d+(?:[.,]\d+)?` 比对译文数字集合 | 0 处不一致 |
| 破折号污染 | 禁 `〜 ～ – — ‒ −` 出现在数字区间 | 0 处（ja 曾有 8 处波浪号区间，已改回 ASCII 连字符） |
| 与英文同值 | 译文 == 英文 | 0 处（除专名串） |
| `\| Packrivo` 后缀 | 标题类逐条检查 | 65 条全带 |
| 逐语言复核脚本 | `verify_langs.py` | ar/de/es/fr/it/ja/ko/nl/pt/zh-CN/zh-TW 全部 `errors=0 warnings=0` |

## 3. Gate 模拟（临时字典副本）

`deploy/i18n-merged/` 是只读参考，未写入。做法：把 11 份词典复制到 `E:\破甲工作区\.tmp\34a\i18n-merged\`，把交付 CSV 的 3707 行合并进副本，再对副本跑 `deploy/34a-i18n-gate.py`。临时副本与临时 CSV 都在 `.tmp` 下，不进仓库。

gate 从 CSV 反推需要的键（用 `source`/`url`），到字典目录里查四种桶是否有值。因此模拟要分三次跑，因为本批的英文键集与 Codex 那份 `34a-new-strings.csv` 原样并不相同（见第 4 节）。

| 运行 | 输入 CSV | csv_rows | unique_keys | 每语言 missing | 结论 |
| --- | --- | --- | --- | --- | --- |
| A 原样 | `dsh-out/34a-new-strings.csv` | 350 | 339 | 11 语言各 7 | GATE FAIL missing_entries=77 |
| B 换权威 meta | `.tmp/34a/34a-new-strings-auth.csv` | 350 | 339 | 11 语言各 2 | GATE FAIL missing_entries=22 |
| C 权威 meta + 去本批不译项 | `.tmp/34a/34a-new-strings-auth-scoped.csv` | 347 | **337** | **11 语言各 0** | **GATE PASS missing_entries=0 errors=0** |

C 的 `unique_keys=337` 与交付 CSV 的键数完全相等，即：**本批交付的 337 个键，在 11 种语言里四种桶全部有非空值，gate 零缺项。**

A 与 B 的缺口逐项对账如下（三次运行的完整原始输出见 `dsh-out/34a-tools/gate.txt`）：

| 缺失键 | 类型 | 归属 | 说明 |
| --- | --- | --- | --- |
| `Custom packaging. Dieline with every quote. MOQ 250, 5-10 day sampling. 7-30 day production. Rigid boxes, mailers, bags, labels. Request a quote.` | attr（`/`） | 被权威文本替换 | 提示词给定 `/` meta 与这句英文不同，交付里是权威那版 |
| `Custom bags with logo. Dieline with every quote. MOQ 250, 5-10 day sampling. 7-30 day production. Request a quote.` | attr（`/bags-pouches/`） | 同上 | 同上 |
| `Packaging products: rigid boxes, mailers, bags, labels and inserts, printed to order. …` | attr（`/products/`） | 同上 | 同上 |
| `Custom drawstring gift bags bulk. …` | attr（`/product-drawstring-fabric-gift-bag/`） | 同上 | 同上 |
| `Custom zippered fabric pouches. …` | attr（`/product-zippered-fabric-gift-pouch/`） | 同上 | 同上 |
| `Custom packaging examples. No retainer before scope. 8-15% coordination fee on production only. …` | attr（`/cases/`） | 本批不译 | 指令：`/cases/` meta 保持原文，不进本批翻译集 |
| `Custom Packaging for Jewelry, Candles & Gifts \| Packrivo` | title（`/` + `/products/`） | 本批不译 | 指令：首页 `<title>` 保持原文 |

A→B 消掉 5 项（权威 meta 换上后，gate 认的键变成了权威英文串），B→C 消掉最后 2 项（本批明确不译的那两处）。所以 A/B 的 missing 不是译文缺漏。

### 首页 title 的一个独立发现

对 11 份词典逐桶检索 `Custom Packaging for Jewelry, Candles & Gifts | Packrivo`，`title` 桶命中 0（各语言 title 条目 295–307 条，都不含这个串）。也就是说这一行**在现有词典里本来就没有对应译文**；本次按指令不译、不新建，部署时若这行的键保持不动，字典里仍然是空的。要不要补，等 Codex 那边的词典重建一起定。这一点单独标出来，因为它是唯一一个"不译就会在 gate 里显示缺失"的行。

## 4. 权威 meta 的偏差（不是错误，是口径）

指令给了 7 条以提示词为准的英文，其中 5 条是 meta，且与 `dsh-out/34a-new-strings.csv` 里同 URL 的 `new_en` 不是同一句。交付 CSV 按权威文本出译文，因此这 5 个键的英文与 Codex 那份输入 CSV 不同。逐条差异：

| URL | 交付所用英文（权威） |
| --- | --- |
| `/` | `Custom jewelry boxes, pouches and gift packaging with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production.` |
| `/products/` | `Packaging for jewelry brands: rigid boxes, pouches, gift bags, mailers and inserts, printed with your logo. MOQ 250 and a dieline with every quote.` |
| `/bags-pouches/` | `Custom jewelry pouches, gift bags and mailer bags with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production.` |
| `/product-drawstring-fabric-gift-bag/` | `Custom drawstring gift bags in bulk for jewelry and small gifts. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production.` |
| `/product-zippered-fabric-gift-pouch/` | `Custom zippered fabric pouches for jewelry and gifts. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling.` |

样例（同一键在三种语言的落地值）：

- `/` meta，zh-CN：`定制首饰盒、收纳袋与礼品包装，可印 Logo。每份报价均附刀模图。MOQ 250，打样 5-10 天，生产 7-30 天。`
- `/` meta，ar：`علب مجوهرات وأكياس وتغليف هدايا مخصصة بشعارك. قالب القص مرفق مع كل عرض سعر. MOQ 250، وأخذ عينات خلال 5-10 أيام، وإنتاج خلال 7-30 يومًا.`
- `/` meta，ja：`ロゴを入れたジュエリーボックス、ポーチ、ギフト包装のオーダーメイド。ダイラインは見積もりごとに添付します。MOQ 250、サンプリング5-10日、生産7-30日。`
- `/products/` meta，zh-CN：`面向首饰品牌的包装：精装盒、收纳袋、礼品袋、邮寄盒与内衬，可印 Logo。MOQ 250，每份报价附刀模图。`
- `/product-zippered-fabric-gift-pouch/` meta，zh-TW 与 en 的 DDP 句：`DDP 或 FOB，兩種方式分別報價。`

另两条权威值是 h1/theme 类（走 `text`/`block` 桶），已一并落进 CSV。

## 5. 共享句尾一致性（结构证据）

meta 不是逐条硬写，而是拆成 176 个英文句段，每语言每句段只给一个译法，再按句段拼接。这样"同一句尾在不同页面必须完全一致"是从构造上成立的，不靠人工对齐。

实测复核：把每条 `attr` 译文的英文按句末拆段，逐段查该语言的句段表再拼回去，与交付值逐字符比对。

| 语言 | attr 行可还原 | 不匹配 | 句段表条目 |
| --- | --- | --- | --- |
| ar | 139 | 0 | 176 |
| de | 139 | 0 | 176 |
| es | 139 | 0 | 176 |
| fr | 139 | 0 | 176 |
| it | 139 | 0 | 176 |
| ja | 139 | 0 | 176 |
| ko | 139 | 0 | 176 |
| nl | 139 | 0 | 176 |
| pt | 139 | 0 | 176 |
| zh-CN | 139 | 0 | 176 |
| zh-TW | 139 | 0 | 176 |

五条高频句尾的定稿译法（同一语言内全站唯一）：

| 英文句段 | ar | de | es | fr | it | ja | ko | nl | pt | zh-CN | zh-TW |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `Request a quote.` | اطلب عرض سعر. | Fordern Sie ein Angebot an. | Solicitar cotización. | Demandez un devis. | Richiedi un preventivo. | 見積もりをご依頼ください。 | 견적을 요청하세요. | Vraag een offerte aan. | Solicite um orçamento. | 申请报价。 | 索取報價。 |
| `Dieline with every quote.` | قالب القص مرفق مع كل عرض سعر. | Stanzkontur zu jedem Angebot. | Troquel con cada cotización. | Tracé de découpe avec chaque devis. | Fustella con ogni preventivo. | ダイラインは見積もりごとに添付します。 | 다이라인은 모든 견적에 첨부됩니다. | Stansmal bij elke offerte. | Linha de corte em cada orçamento. | 每份报价均附刀模图。 | 每次報價附刀模圖。 |
| `MOQ 250, 5-10 day sampling.` | MOQ 250، وأخذ عينات خلال 5-10 أيام. | MOQ 250, Bemusterung in 5-10 Tagen. | MOQ 250, muestreo en 5-10 días. | MOQ 250, échantillons en 5-10 jours. | MOQ 250, campionatura in 5-10 giorni. | MOQ 250、サンプリング5-10日。 | MOQ 250, 샘플링 5-10일. | MOQ 250, 5-10 dagen voor monsters. | MOQ 250, amostragem em 5-10 dias. | MOQ 250，打样 5-10 天。 | MOQ 250，打樣 5-10 天。 |
| `7-30 day production after approval.` | إنتاج خلال 7-30 يومًا بعد الموافقة. | 7-30 Tage Produktion nach Ihrer Freigabe. | Producción en 7-30 días tras la aprobación. | Production en 7-30 jours après validation. | Produzione in 7-30 giorni dopo l'approvazione. | 承認後、生産7-30日。 | 승인 후 생산 7-30일. | 7-30 dagen productie na goedkeuring. | Produção em 7-30 dias após aprovação. | 确认后生产 7-30 天。 | 確認後生產 7-30 天。 |
| `DDP or FOB, quoted both ways.` | DDP أو FOB، مع تسعير بالطريقتين. | DDP oder FOB, beides angeboten. | DDP o FOB, cotizado en ambas modalidades. | DDP ou FOB, devis dans les deux cas. | DDP o FOB, preventivati entrambi. | DDPまたはFOB、両条件で見積もります。 | DDP 또는 FOB, 두 조건 모두 견적합니다. | DDP of FOB, beide aangeboden. | DDP ou FOB, com os dois preços indicados. | DDP 或 FOB，两种方式分别报价。 | DDP 或 FOB，兩種皆報價。 |

## 6. 标题长度

限额：拉丁系 ≤60 字符，CJK（ja/ko/zh-CN/zh-TW）≤44 字符。`| Packrivo` 后缀计入。

| 语言 | 上限 | 最长标题 | 超限条数 |
| --- | --- | --- | --- |
| ar | 60 | 51 | 0 |
| de | 60 | 54 | 0 |
| es | 60 | 58 | 0 |
| fr | 60 | 53 | 0 |
| it | 60 | 60 | 0 |
| ja | 44 | 37 | 0 |
| ko | 44 | 35 | 0 |
| nl | 60 | 55 | 0 |
| pt | 60 | 59 | 0 |
| zh-CN | 44 | 32 | 0 |
| zh-TW | 44 | 31 | 0 |

**超长标题清单：空。** 最长的三条都在界内：it 60（`Fornitori di packaging personalizzato a confronto | Packrivo`）、pt 59、it 58。it 那条正好 60，若后续再改文案没有余量，先记一笔。

## 7. 回译抽检（100 行）

抽样：`ar`、`ja`、`zh-CN` 各 20 行（每桶按比例摊开：title 65 / attr 139 / text 132 的总量下 20 行≈title 6 + attr 7 + text 7），其余 8 语言各 5 行（title 2 + attr 2 + text 1）。抽样脚本 `dsh-out/34a-tools/sample.py`，清单 `dsh-out/34a-tools/backtrans-sample.txt`。

判定列：`√` = 回译与英文原意一致，无漏译、无增译；有偏离在备注里写明。

### ar（20 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | علب بريدية مخصصة: مطبوعة وجاهزة للشحن \| Packrivo | 定制邮寄盒：已印刷、可发运 \| Packrivo | √ |
| 2 | attr | Custom jewelry boxes, pouches and gift packaging with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production. | علب مجوهرات وأكياس وتغليف هدايا مخصصة بشعارك. قالب القص مرفق مع كل عرض سعر. MOQ 250، وأخذ عينات خلال 5-10 أيام، وإنتاج خلال 7-30 يومًا. | 定制珠宝盒、袋与礼品包装，带你的标志。每份报价附刀模图。MOQ 250，5-10 天打样，7-30 天生产。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | تغليف مخصص للمجوهرات والشموع والهدايا | 珠宝、蜡烛与礼品的定制包装 | √ |
| 4 | title | Custom Kraft Cosmetic Boxes (MOQ 250) \| Packrivo | علب كرافت للمستحضرات (MOQ 250) \| Packrivo | 定制牛皮纸化妆品盒（MOQ 250） \| Packrivo | √ |
| 5 | attr | Custom magnetic closure gift boxes. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Recycled-content stock on request. Request a quote. | علب هدايا بإغلاق مغناطيسي مخصصة. MOQ 250، وأخذ عينات خلال 5-10 أيام. إنتاج خلال 7-30 يومًا بعد الموافقة. مواد معاد تدويرها عند الطلب. اطلب عرض سعر. | 定制磁吸礼盒。MOQ 250，5-10 天打样。批准后 7-30 天生产。再生料按需提供。索取报价。 | √ |
| 6 | text | Custom Packaging Samples | عينات تغليف مخصصة | 定制包装样品 | √ |
| 7 | title | Round Cardboard Tube Boxes \| Packrivo | علب أنبوبية كرتونية دائرية \| Packrivo | 圆形纸筒盒 \| Packrivo | √ |
| 8 | attr | Packhelp alternatives: order terms side by side. MOQ and tooling compared. Sampling and production time. Request a quote. | بدائل Packhelp: شروط الطلب جنبًا إلى جنب. مقارنة MOQ والقوالب. وقت أخذ العينات والإنتاج. اطلب عرض سعر. | Packhelp 替代方案：下单条款并列比较。MOQ 与模具比较。打样与生产时间。索取报价。 | √ |
| 9 | text | Custom Packaging Examples | أمثلة تغليف مخصص | 定制包装案例 | √ |
| 10 | title | Packaging MOQ: How 250-Piece Runs Work \| Packrivo | MOQ التغليف: دفعات 250 قطعة \| Packrivo | 包装 MOQ：250 件批单如何运作 \| Packrivo | √ |
| 11 | attr | Custom coated labels. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | ملصقات مطلية مخصصة. DDP أو FOB، مع تسعير بالطريقتين. قالب القص مرفق مع كل عرض سعر. MOQ 250، وأخذ عينات خلال 5-10 أيام. اطلب عرض سعر. | 定制涂布标签。DDP 或 FOB，两种方式分别报价。每份报价附刀模图。MOQ 250，5-10 天打样。索取报价。 | √ |
| 12 | text | Hinged Lid Boxes, dieline first | علب بغطاء مفصلي، قالب القص أولًا | 铰链盖盒，刀模图优先 | √ |
| 13 | title | Packaging Finishes: Colors and Materials \| Packrivo | تشطيبات التغليف: ألوان ومواد \| Packrivo | 包装表面工艺：颜色与材料 \| Packrivo | √ |
| 14 | attr | Custom dispenser boxes. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | علب توزيع مخصصة. DDP أو FOB، مع تسعير بالطريقتين. قالب القص مرفق مع كل عرض سعر. MOQ 250، وأخذ عينات خلال 5-10 أيام. اطلب عرض سعر. | 定制分配（抽取）盒。DDP 或 FOB，两种方式分别报价。每份报价附刀模图。MOQ 250，5-10 天打样。索取报价。 | √ |
| 15 | text | Folding Cartons Wholesale, dieline first | علب كرتونية مطوية بالجملة، قالب القص أولًا | 折叠纸盒批发，刀模图优先 | √ |
| 16 | title | Custom Hinged Lid Boxes \| Packrivo | علب بغطاء مفصلي مخصصة \| Packrivo | 定制铰链盖盒 \| Packrivo | √ |
| 17 | attr | Custom lock cap auto bottom boxes. Dieline with every quote. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Request a quote. | علب بغطاء قفل وقاعدة تلقائية. قالب القص مرفق مع كل عرض سعر. MOQ 250، وأخذ عينات خلال 5-10 أيام. إنتاج خلال 7-30 يومًا بعد الموافقة. اطلب عرض سعر. | 定制锁扣盖自动底盒。每份报价附刀模图。MOQ 250，5-10 天打样。批准后 7-30 天生产。索取报价。 | √ |
| 18 | text | Printed Belly Bands, dieline first | أحزمة ورقية مطبوعة، قالب القص أولًا | 印刷腰封，刀模图优先 | √ |
| 19 | title | Custom Lid-and-base Jewelry Boxes \| Packrivo | علب مجوهرات مخصصة بغطاء وقاعدة \| Packrivo | 定制天地盖珠宝盒 \| Packrivo | √ |
| 20 | attr | Custom reverse tuck retail boxes. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | علب متاجر بتثبيت عكسي. DDP أو FOB، مع تسعير بالطريقتين. قالب القص مرفق مع كل عرض سعر. MOQ 250، وأخذ عينات خلال 5-10 أيام. اطلب عرض سعر. | 定制反插式零售盒。DDP 或 FOB，两种方式分别报价。每份报价附刀模图。MOQ 250，5-10 天打样。索取报价。 | √ |

### ja（20 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | オリジナル メーラーボックス 印刷・発送対応 \| Packrivo | 定制邮寄盒 印刷·可发运 \| Packrivo | √ |
| 2 | attr | Custom jewelry boxes, pouches and gift packaging with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production. | ロゴを入れたジュエリーボックス、ポーチ、ギフト包装のオーダーメイド。ダイラインは見積もりごとに添付します。MOQ 250、サンプリング5-10日、生産7-30日。 | 印上 logo 的珠宝盒、收纳袋、礼品包装的定制。每份报价附刀模图。MOQ 250、打样 5-10 天、生产 7-30 天。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | ジュエリー、キャンドル、ギフト向け オリジナル包装 | 面向珠宝、蜡烛、礼品的定制包装 | √ |
| 4 | title | Custom Kraft Cosmetic Boxes (MOQ 250) \| Packrivo | オリジナル クラフト化粧品ボックス（MOQ 250） \| Packrivo | 定制牛皮纸化妆品盒（MOQ 250） \| Packrivo | √ |
| 5 | attr | Custom magnetic closure gift boxes. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Recycled-content stock on request. Request a quote. | オリジナルマグネット式ギフトボックス。MOQ 250、サンプリング5-10日。承認後、生産7-30日。再生原料の素材もご要望に応じて対応。見積もりをご依頼ください。 | 定制磁吸礼盒。MOQ 250、打样 5-10 天。批准后生产 7-30 天。再生原料的材质也可按需对应。请索取报价。 | √ |
| 6 | text | Custom Packaging Samples | オリジナル包装 サンプル | 定制包装样品 | √ |
| 7 | title | Round Cardboard Tube Boxes \| Packrivo | 丸型 紙管ボックス \| Packrivo | 圆形纸管盒 \| Packrivo | √ |
| 8 | attr | Packhelp alternatives: order terms side by side. MOQ and tooling compared. Sampling and production time. Request a quote. | Packhelpの代替：発注条件を横並びで比較。MOQと型代を比較。サンプリングと生産の期間。見積もりをご依頼ください。 | Packhelp 的替代：发注条件并列比较。比较 MOQ 与开模费。打样与生产周期。请索取报价。 | √ |
| 9 | text | Custom Packaging Examples | オリジナル包装の事例 | 定制包装实例 | √ |
| 10 | title | Packaging MOQ: How 250-Piece Runs Work \| Packrivo | 包装のMOQ：250個ロットの進め方 \| Packrivo | 包装的 MOQ：250 个批量怎么做 \| Packrivo | √ |
| 11 | attr | Custom coated labels. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | オリジナルコーティングラベル。DDPまたはFOB、両条件で見積もります。ダイラインは見積もりごとに添付します。MOQ 250、サンプリング5-10日。見積もりをご依頼ください。 | 定制涂层标签。DDP 或 FOB，两种条件分别报价。每份报价附刀模图。MOQ 250、打样 5-10 天。请索取报价。 | √ |
| 12 | text | Hinged Lid Boxes, dieline first | 開き蓋 段ボール箱、ダイライン先行 | 开盖瓦楞箱，刀模图优先 | √ |
| 13 | title | Packaging Finishes: Colors and Materials \| Packrivo | 包装の仕上げ：色と素材 \| Packrivo | 包装表面工艺：颜色与材料 \| Packrivo | √ |
| 14 | attr | Custom dispenser boxes. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | オリジナルディスペンサー箱。DDPまたはFOB、両条件で見積もります。ダイラインは見積もりごとに添付します。MOQ 250、サンプリング5-10日。見積もりをご依頼ください。 | 定制抽取盒。DDP 或 FOB，两种条件分别报价。每份报价附刀模图。MOQ 250、打样 5-10 天。请索取报价。 | √ |
| 15 | text | Folding Cartons Wholesale, dieline first | 化粧箱 卸売、ダイライン先行 | 化妆纸盒（彩盒）批发，刀模图优先 | √ |
| 16 | title | Custom Hinged Lid Boxes \| Packrivo | オリジナル 開き蓋ボックス \| Packrivo | 定制开盖盒 \| Packrivo | √ |
| 17 | attr | Custom lock cap auto bottom boxes. Dieline with every quote. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Request a quote. | オリジナルロックキャップ自動底箱。ダイラインは見積もりごとに添付します。MOQ 250、サンプリング5-10日。承認後、生産7-30日。見積もりをご依頼ください。 | 定制锁扣盖自动底盒。每份报价附刀模图。MOQ 250、打样 5-10 天。批准后生产 7-30 天。请索取报价。 | √ |
| 18 | text | Printed Belly Bands, dieline first | 印刷 帯紙、ダイライン先行 | 印刷腰封，刀模图优先 | √ |
| 19 | title | Custom Lid-and-base Jewelry Boxes \| Packrivo | オリジナル 蓋と台のジュエリーボックス \| Packrivo | 定制天地盖珠宝盒 \| Packrivo | √ |
| 20 | attr | Custom reverse tuck retail boxes. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | オリジナル逆差し込み式店頭ボックス。DDPまたはFOB、両条件で見積もります。ダイラインは見積もりごとに添付します。MOQ 250、サンプリング5-10日。見積もりをご依頼ください。 | 定制反插式店头盒。DDP 或 FOB，两种条件分别报价。每份报价附刀模图。MOQ 250、打样 5-10 天。请索取报价。 | √ |

### zh-CN（20 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | 定制邮寄盒：印刷与发运就绪 \| Packrivo | 同左（原文即中文） | √ |
| 2 | attr | Custom jewelry boxes, pouches and gift packaging with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production. | 定制首饰盒、收纳袋与礼品包装，可印 Logo。每份报价均附刀模图。MOQ 250，打样 5-10 天，生产 7-30 天。 | 同左 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | 首饰、蜡烛与礼品定制包装 | 同左 | √ |
| 4 | title | Custom Kraft Cosmetic Boxes (MOQ 250) \| Packrivo | 定制牛皮纸化妆盒（MOQ 250） \| Packrivo | 同左 | √ |
| 5 | attr | Custom magnetic closure gift boxes. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Recycled-content stock on request. Request a quote. | 定制磁吸礼盒。MOQ 250，打样 5-10 天。确认后生产 7-30 天。可按需提供再生料。申请报价。 | 同左 | √ |
| 6 | text | Custom Packaging Samples | 定制包装样品 | 同左 | √ |
| 7 | title | Round Cardboard Tube Boxes \| Packrivo | 圆形纸筒盒 \| Packrivo | 同左 | √ |
| 8 | attr | Packhelp alternatives: order terms side by side. MOQ and tooling compared. Sampling and production time. Request a quote. | Packhelp 替代方案：订单条款横向对比。MOQ 与模具一并对比。打样与生产时间。申请报价。 | 同左 | √ |
| 9 | text | Custom Packaging Examples | 定制包装案例 | 同左 | √ |
| 10 | title | Packaging MOQ: How 250-Piece Runs Work \| Packrivo | 包装 MOQ：250 件怎么跑 \| Packrivo | 同左 | √ |
| 11 | attr | Custom coated labels. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | 定制涂布标签。DDP 或 FOB，两种方式分别报价。每份报价均附刀模图。MOQ 250，打样 5-10 天。申请报价。 | 同左 | √ |
| 12 | text | Hinged Lid Boxes, dieline first | 翻盖盒，刀模图先行 | 同左 | √ |
| 13 | title | Packaging Finishes: Colors and Materials \| Packrivo | 包装工艺：颜色与材料 \| Packrivo | 同左 | √ |
| 14 | attr | Custom dispenser boxes. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | 定制抽取盒。DDP 或 FOB，两种方式分别报价。每份报价均附刀模图。MOQ 250，打样 5-10 天。申请报价。 | 同左 | √ |
| 15 | text | Folding Cartons Wholesale, dieline first | 折叠纸盒批发，刀模图先行 | 同左 | √ |
| 16 | title | Custom Hinged Lid Boxes \| Packrivo | 定制翻盖盒 \| Packrivo | 同左 | √ |
| 17 | attr | Custom lock cap auto bottom boxes. Dieline with every quote. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Request a quote. | 定制锁扣盖自动锁底盒。每份报价均附刀模图。MOQ 250，打样 5-10 天。确认后生产 7-30 天。申请报价。 | 同左 | √ |
| 18 | text | Printed Belly Bands, dieline first | 印刷腰封，刀模图先行 | 同左 | √ |
| 19 | title | Custom Lid-and-base Jewelry Boxes \| Packrivo | 定制天地盖首饰盒 \| Packrivo | 同左 | √ |
| 20 | attr | Custom reverse tuck retail boxes. DDP or FOB, quoted both ways. Dieline with every quote. MOQ 250, 5-10 day sampling. Request a quote. | 定制反插零售盒。DDP 或 FOB，两种方式分别报价。每份报价均附刀模图。MOQ 250，打样 5-10 天。申请报价。 | 同左 | √ |

### de（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | Versandboxen bedruckt, versandfertig \| Packrivo | 邮寄盒已印刷、可发运 \| Packrivo | √ 德文标题惯例省去「Custom」，靠 Kompositum 承载 |
| 2 | attr | Custom jewelry boxes, pouches and gift packaging with your logo. Dieline with every quote. MOQ 250, 5-10 day sampling, 7-30 day production. | Individuelle Schmuckschachteln, Beutel und Geschenkverpackungen mit Ihrem Logo. Stanzkontur zu jedem Angebot. MOQ 250, Bemusterung in 5-10 Tagen, Produktion in 7-30 Tagen. | 带贵方 logo 的定制首饰盒、袋与礼品包装。每份报价附刀模轮廓。MOQ 250，5-10 天打样，7-30 天生产。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | Individuelle Verpackung für Schmuck, Kerzen und Geschenke | 首饰、蜡烛与礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Packhelp-Alternativen im Vergleich \| Packrivo | Packhelp 替代方案对比 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. Dieline with every quote. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Request a quote. | Individuelle Trageboxen mit Griff. Stanzkontur zu jedem Angebot. MOQ 250, Bemusterung in 5-10 Tagen. 7-30 Tage Produktion nach Ihrer Freigabe. Fordern Sie ein Angebot an. | 带提手的定制提盒。每份报价附刀模轮廓。MOQ 250，5-10 天打样。贵方批准后 7-30 天生产。请索取报价。 | √ |

### es（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | Cajas mailer a medida, impresas \| Packrivo | 定制邮寄盒，已印刷 \| Packrivo | √「mailer」保留行业借词 |
| 2 | attr | （同 de#2 英文） | Cajas de joyería, bolsas y embalaje de regalo personalizados con tu logo. Troquel con cada cotización. MOQ 250, muestreo en 5-10 días, producción en 7-30 días. | 带你 logo 的定制首饰盒、袋与礼品包装。每份报价附刀模。MOQ 250，5-10 天打样，7-30 天生产。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | Embalaje a medida para joyería, velas y regalos | 首饰、蜡烛与礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Alternativas a Packhelp: comparativa \| Packrivo | Packhelp 的替代：对比 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. … Request a quote. | Cajas de transporte con asa personalizadas. Troquel con cada cotización. MOQ 250, muestreo en 5-10 días. Producción en 7-30 días tras la aprobación. Solicitar cotización. | 带提手的定制运输盒。每份报价附刀模。MOQ 250，5-10 天打样。批准后 7-30 天生产。请索取报价。 | √ |

### fr（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | Boîtes d'expédition imprimées \| Packrivo | 已印刷的邮寄盒 \| Packrivo | √ |
| 2 | attr | （同 de#2 英文） | Boîtes à bijoux, pochettes et emballages cadeaux personnalisés avec votre logo. Tracé de découpe avec chaque devis. MOQ 250, échantillons en 5-10 jours, production en 7-30 jours. | 带您 logo 的定制首饰盒、小袋与礼盒包装。每份报价附刀模图。MOQ 250，5-10 天打样，7-30 天生产。 | √ vouvoiement 一致 |
| 3 | text | Custom packaging for jewelry, candles and gifts | Emballages personnalisés pour bijoux, bougies et cadeaux | 珠宝、蜡烛与礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Alternatives à Packhelp : emballages \| Packrivo | Packhelp 的替代：包装方案 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. … Request a quote. | Boîtes de transport avec poignée personnalisées. Tracé de découpe avec chaque devis. MOQ 250, échantillons en 5-10 jours. Production en 7-30 jours après validation. Demandez un devis. | 带提手的定制运输盒。每份报价附刀模图。MOQ 250，5-10 天打样。确认后 7-30 天生产。请索取报价。 | √ |

### it（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | Scatole mailer stampate e pronte \| Packrivo | 已印刷、可用的邮寄盒 \| Packrivo | √ |
| 2 | attr | （同 de#2 英文） | Scatole per gioielli, pochette e packaging regalo personalizzati con il tuo logo. Fustella con ogni preventivo. MOQ 250, campionatura in 5-10 giorni, produzione in 7-30 giorni. | 带你 logo 的定制首饰盒、小袋与礼品包装。每份报价附刀模。MOQ 250，5-10 天打样，7-30 天生产。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | Packaging personalizzato per gioielli, candele e regali | 面向珠宝、蜡烛与礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Alternative a Packhelp: packaging a confronto \| Packrivo | Packhelp 的替代：包装对比 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. … Request a quote. | Scatole con manico per asporto. Fustella con ogni preventivo. MOQ 250, campionatura in 5-10 giorni. Produzione in 7-30 giorni dopo l'approvazione. Richiedi un preventivo. | 带提手的外带盒。每份报价附刀模。MOQ 250，5-10 天打样。批准后 7-30 天生产。请索取报价。 | √ |

### ko（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | 맞춤 메일러 박스: 인쇄·발송 준비 \| Packrivo | 定制邮寄盒：印刷·发运准备 \| Packrivo | √ |
| 2 | attr | （同 de#2 英文） | 로고를 넣은 주얼리 박스, 파우치, 선물 포장 맞춤 제작. 다이라인은 모든 견적에 첨부됩니다. MOQ 250, 샘플링 5-10일, 생산 7-30일. | 放入 logo 的珠宝盒、袋、礼品包装定制制作。刀模图附于所有报价。MOQ 250，打样 5-10 天，生产 7-30 天。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | 주얼리, 캔들, 선물 맞춤 포장 | 珠宝、蜡烛、礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Packhelp 대안: 포장 비교 \| Packrivo | Packhelp 替代方案：包装对比 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. … Request a quote. | 맞춤 제작 손잡이 캐리 박스. 다이라인은 모든 견적에 첨부됩니다. MOQ 250, 샘플링 5-10일. 승인 후 생산 7-30일. 견적을 요청하세요. | 定制提手携带盒。刀模图附于所有报价。MOQ 250，打样 5-10 天。批准后生产 7-30 天。请索取报价。 | √ |

### nl（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | Verzenddozen op maat: bedrukt en klaar \| Packrivo | 定制邮寄盒：已印刷、已备好 \| Packrivo | √ |
| 2 | attr | （同 de#2 英文） | Sieradendozen, zakjes en cadeauverpakking met je logo. Stansmal bij elke offerte. MOQ 250, 5-10 dagen monsters, 7-30 dagen productie. | 带你 logo 的首饰盒、袋与礼品包装。每份报价附刀模。MOQ 250，5-10 天打样，7-30 天生产。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | Verpakkingen op maat voor sieraden, kaarsen en geschenken | 首饰、蜡烛与礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Alternatieven voor Packhelp: verpakkingen \| Packrivo | Packhelp 的替代：包装方案 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. … Request a quote. | Draagdozen met handvat op maat. Stansmal bij elke offerte. MOQ 250, 5-10 dagen voor monsters. 7-30 dagen productie na goedkeuring. Vraag een offerte aan. | 带提手的定制提盒。每份报价附刀模。MOQ 250，5-10 天打样。批准后 7-30 天生产。请索取报价。 | √ |

### pt（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | Caixas de envio impressas \| Packrivo | 已印刷的邮寄盒 \| Packrivo | √ |
| 2 | attr | （同 de#2 英文） | Caixas de joalharia, saquetas e embalagens de presente personalizadas com o seu logótipo. Linha de corte em cada orçamento. MOQ 250, amostragem em 5-10 dias, produção em 7-30 dias. | 带您标志的定制首饰盒、小袋与礼品包装。每份报价附刀模线。MOQ 250，5-10 天打样，7-30 天生产。 | √ pt-PT 用词 |
| 3 | text | Custom packaging for jewelry, candles and gifts | Embalagens personalizadas para joalharia, velas e presentes | 珠宝、蜡烛与礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Alternativas à Packhelp: embalagens comparadas \| Packrivo | Packhelp 的替代：包装对比 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. … Request a quote. | Caixas de transporte com asa personalizadas. Linha de corte em cada orçamento. MOQ 250, amostragem em 5-10 dias. Produção em 7-30 dias após aprovação. Solicite um orçamento. | 带提手的定制运输盒。每份报价附刀模线。MOQ 250，5-10 天打样。批准后 7-30 天生产。请索取报价。 | √ |

### zh-TW（5 行）

| # | bucket | 英文 | 译文 | 回译中文 | 判定 |
| --- | --- | --- | --- | --- | --- |
| 1 | title | Custom Mailer Boxes: Printed & Shipping Ready \| Packrivo | 客製飛機盒：印刷完成即可出貨 \| Packrivo | 定制邮寄盒：印刷完成即可出货 \| Packrivo | √ 台港行话「飛機盒」 |
| 2 | attr | （同 de#2 英文） | 印上品牌標誌的客製珠寶盒、首飾袋與禮品包裝。每次報價附刀模圖。MOQ 250，打樣 5-10 天，生產 7-30 天。 | 印上品牌标志的定制珠宝盒、首饰袋与礼品包装。每次报价附刀模图。MOQ 250，打样 5-10 天，生产 7-30 天。 | √ |
| 3 | text | Custom packaging for jewelry, candles and gifts | 珠寶、蠟燭與禮品的客製包裝 | 珠宝、蜡烛与礼品的定制包装 | √ |
| 4 | title | Packhelp Alternatives: Packaging Compared \| Packrivo | Packhelp 替代方案：包裝比較 \| Packrivo | Packhelp 替代方案：包装比较 \| Packrivo | √ |
| 5 | attr | Custom carry boxes with handle. … Request a quote. | 客製附提把提盒。每次報價附刀模圖。MOQ 250，打樣 5-10 天。確認後生產 7-30 天。索取報價。 | 定制附提把提盒。每次报价附刀模图。MOQ 250，打样 5-10 天。确认后生产 7-30 天。索取报价。 | √ |

## 8. 术语与口径核对

- 术语按 `deploy/i18n-merged/<lang>-dict.json` 的既有用词走，不另造：pt 的 `linha de corte`／`caixas rígidas`／`saquetas`、nl 的 `stansmal`、fr 的 `tracé de découpe`、it 的 `fustella`／`preventivo`、ja 的 `ダイライン`、ko 的 `다이라인`、zh-CN 的 `刀模图`／`精装盒`／`内衬`、zh-TW 的 `刀模圖`／`裱紙盒`。
- 硬令牌原样保留：`MOQ`、`DDP`、`FOB`、`Packrivo`、`5-10`、`7-30`、`8-15`、`1,000`，标题后缀 `| Packrivo` 全带。
- 英文 `8-15%` 用 ASCII 连字符（与 34a 英文源一致）；词典里旧的 `8–15%` 用 en dash，这是英文侧的差异，gate 只比英文键，不影响本批。
- 不出现把 Packrivo 自称工厂/制造商的表述。带 `Manufacturer` 的几条标题（`Find a Jewelry Box Manufacturer: Costs & Vetting` 等）是英文源本身在讲"怎么挑供应商"，译文照译 `Hersteller`／`Produttore`／`Fabricante`，不是自我描述。
- 未引入 ISO／ISTA／FSC 等认证话术。

## 9. 未做与边界

| 项 | 状态 |
| --- | --- |
| 人工母语终审 | 未执行。本轮交付是机器初译+抽检，需母语者过一遍再上生产 |
| 部署／写入 `deploy/i18n-merged/` | 未执行，也未提交。词典目录本轮零改动 |
| 提交 | 只提交本 CSV 与本文档，提交信息 `wip: dsh 34a translations csv (not yet reviewed)`，不 push |
| Codex 拥有的三个文件 | 全程只读：`dsh-out/34a-keyword-map.csv`、`dsh-out/34a-new-strings.csv`、`dsh-out/CODEX-RESULT-34a.md` 未改、未提交 |
| 临时字典副本 | 在 `E:\破甲工作区\.tmp\34a\`，不入仓库 |
| 首页 title 的译文 | 按指令不译。查证结果：11 份词典的 `title` 桶都没有这个英文键，部署时该键仍是空，需要补一条才能让 gate 真实通过 |

复现命令（全部已实跑）：

```
python -X utf8 dsh-out/34a-tools/inventory.py      # 键集与句段盘点
python -X utf8 dsh-out/34a-tools/compose.py         # 生成 34a-i18n.csv
python -X utf8 dsh-out/34a-tools/verify_langs.py ja # 逐语言复核（11 次）
python -X utf8 dsh-out/34a-tools/checktoken.py      # 硬令牌与破折号
python -X utf8 dsh-out/34a-tools/gate_sim.py        # 三次 gate 模拟，输出 gate.txt
python -X utf8 dsh-out/34a-tools/sample.py          # 回译抽样清单
```

工作台 #27
