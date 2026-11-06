# 32a-i18n MOQ 译文迁移 · 交付报告

- 工作目录：`E:\破甲工作区\packrivo-backup-32a`（分支 `32a`）
- 指令来源：`E:\Claude\packrivo-报价校准-2026-11-04\给dsh的指令-32a-MOQ译文.md`
- 输入：`dsh-out/32a-new-strings.csv`（573 行 / 565 个唯一英文键 / 552 行带旧英文 / 21 行无旧英文；source 分布 db 534、theme 32、llms 4、backend 3）
- 验收门：`deploy/32a-i18n-gate.py`，本轮最后一次运行 **GATE PASS**，退出码 0
- 改动范围：11 个站点词典 + 11 个 quote-i18n 词典 + 本报告；未提交，未动容器 / 数据库 / WP / 主题

## 1. 执行口径

**A 类（552 行，有旧英文）**：逐行逐语言在目标语言的 title / attr / block / text 四个桶里找旧英文键，取它的译文，把 `500` 改成 `250`，存到**新英文键**下、且与旧键**同桶**；旧键原样保留。

- 自动迁移（283 行）：旧键在、译文里 `500` 出现次数与旧英文一致、新旧英文除数字外逐字相同 —— 直接做数字替换。
- 人工迁移（269 行）：以上任一条件不成立 —— 旧键不存在、译文的 `500` 数与旧英文不符（如 `at 500 pieces` 这类定价示例句必须保留 500）、非 ASCII 数字、新旧英文除数字外还有别的差异（拆句 / 费用措辞变化 / `source=llms`）—— 整句重译。
- 定价示例句（`At 500 pieces, a 9.5×9.5×4 cm rigid jewelry box is about $3.26…`）按规则**保留 500**：它是价格示例的量级，不是 MOQ 基准。两处 `500` 的那一行两个 500 都是 MOQ 门槛，都改 250。

**B 类（21 行，无旧英文）**：首页新文案与 404 标题直接翻译。`<title>` → title 桶；按钮 / alt / placeholder → attr 桶；其余 → text 桶。`Page not found | Packrivo` 必须落在 `title` 桶。

**C 类（3 条 quote 组件键）**：`Less than 250?…`、`Goods {goods} · Sourcing fee {fee}…`、`Instant quotes include a {percent}% sourcing fee…` 写进 `packrivo-wp/packrivo-quote/includes/quote-i18n/<lang>.json`，同时写进站点词典（gate 校验两处）。占位符 `{goods} {fee} {percent} {minimum} {total}` 按名、按数原样保留 —— 11 语言 `quote_invalid=0`。

**术语与约束**：sourcing fee 一律沿用各语言既有词典措辞（de Beschaffungsgebühr、ja 調達手数料、it commissione di approvvigionamento、ko 소싱 수수료、ar رسوم التوريد、nl inkoopvergoeding、pt Taxa de fornecimento、zh-CN 采购协调费、fr frais de sourcing、es comisión de aprovisionamiento、zh-TW 採購協調費），全程未出现「佣金 / 中介费 / 仲介費」；未新增任何数字、认证、客户数；未出现「自有工厂 / 我们生产」这类自述。

## 2. 结果总表

| 语言 | A 自动 | A 人工 | B 直译 | C quote | 失败 | 写入新键 |
|---|---|---|---|---|---|---|
| ar | 283 | 269 | 21 | 3 | 0 | 564 |
| de | 283 | 269 | 21 | 3 | 0 | 564 |
| es | 283 | 269 | 21 | 3 | 0 | 564 |
| fr | 283 | 269 | 21 | 3 | 0 | 564 |
| it | 283 | 269 | 21 | 3 | 0 | 564 |
| ja | 283 | 269 | 21 | 3 | 0 | 564 |
| ko | 283 | 269 | 21 | 3 | 0 | 564 |
| nl | 283 | 269 | 21 | 3 | 0 | 564 |
| pt | 283 | 269 | 21 | 3 | 0 | 564 |
| zh-CN | 283 | 269 | 21 | 3 | 0 | 564 |
| zh-TW | 283 | 269 | 21 | 3 | 0 | 564 |

合计：11 语言 × 564 条新键 = 6204 条新增条目，失败 0。

`Sourcing fee` 这一行（2 词键）在 11 个语言的同桶里**本来就有同键**，按「旧键保留 + 同桶写入」的规则不重复写，故新增 564 而非 565 —— gate 的 `missing=0` 由既有键满足。

## 3. 验收证据（原始输出）

#### 1. 22 个 JSON 文件解析检查

| 文件 | 字节 | 键数 | json.loads |
|---|---|---|---|
| `deploy/i18n-merged/ar-dict.json` | 2027440 | 8723 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/ar.json` | 7100 | 102 | OK |
| `deploy/i18n-merged/de-dict.json` | 1797814 | 8822 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/de.json` | 6069 | 102 | OK |
| `deploy/i18n-merged/es-dict.json` | 1782966 | 8771 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/es.json` | 6111 | 102 | OK |
| `deploy/i18n-merged/fr-dict.json` | 1834939 | 8775 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/fr.json` | 6133 | 102 | OK |
| `deploy/i18n-merged/it-dict.json` | 1802114 | 8772 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/it.json` | 6128 | 102 | OK |
| `deploy/i18n-merged/ja-dict.json` | 1881004 | 8726 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/ja.json` | 6429 | 102 | OK |
| `deploy/i18n-merged/ko-dict.json` | 1760900 | 8727 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/ko.json` | 6159 | 102 | OK |
| `deploy/i18n-merged/nl-dict.json` | 1738556 | 8778 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/nl.json` | 5895 | 102 | OK |
| `deploy/i18n-merged/pt-dict.json` | 1787765 | 8785 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/pt.json` | 6113 | 102 | OK |
| `deploy/i18n-merged/zh-CN-dict.json` | 1519288 | 8778 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/zh-CN.json` | 5472 | 102 | OK |
| `deploy/i18n-merged/zh-TW-dict.json` | 1519633 | 8726 | OK |
| `packrivo-wp/packrivo-quote/includes/quote-i18n/zh-TW.json` | 5505 | 102 | OK |

解析成功 22/22。

#### 2. 旧键保留检查（旧键计数不得减少）

| 语言 | HEAD title/attr/block/text | 现 title/attr/block/text | 减少 |
|---|---|---|---|
| ar | 291/738/6748/382 | 295/879/7123/426 | 0 |
| de | 291/739/6795/433 | 295/880/7170/477 | 0 |
| es | 297/739/6746/425 | 301/880/7121/469 | 0 |
| fr | 297/739/6743/432 | 301/880/7118/476 | 0 |
| it | 297/739/6745/427 | 301/880/7120/471 | 0 |
| ja | 291/738/6751/382 | 295/879/7126/426 | 0 |
| ko | 291/739/6753/380 | 295/880/7128/424 | 0 |
| nl | 297/739/6747/431 | 301/880/7122/475 | 0 |
| pt | 297/739/6754/431 | 301/880/7129/475 | 0 |
| zh-CN | 291/740/6753/430 | 295/881/7128/474 | 0 |
| zh-TW | 291/738/6751/382 | 295/879/7126/426 | 0 |

减少文件数 0（0 = 旧键一个没少）。

#### 3. 新增条目分布

| 语言 | title | attr | block | text | 合计 |
|---|---|---|---|---|---|
| ar | 4 | 141 | 375 | 44 | 564 |
| de | 4 | 141 | 375 | 44 | 564 |
| es | 4 | 141 | 375 | 44 | 564 |
| fr | 4 | 141 | 375 | 44 | 564 |
| it | 4 | 141 | 375 | 44 | 564 |
| ja | 4 | 141 | 375 | 44 | 564 |
| ko | 4 | 141 | 375 | 44 | 564 |
| nl | 4 | 141 | 375 | 44 | 564 |
| pt | 4 | 141 | 375 | 44 | 564 |
| zh-CN | 4 | 141 | 375 | 44 | 564 |
| zh-TW | 4 | 141 | 375 | 44 | 564 |

#### 4. 抽查：英文原文 → 译文（新增条目里随机取）

##### ar（10 条，含额外 5 条）

- `[attr]` Tissue sheets printed with your logo to line a box, from 250 pieces. Get a price.
  → أوراق تغليف مطبوعة بالشعار لتبطين العلب، بحد أدنى 250 قطعة. اطلب عرض سعر.
- `[block]` . MOQ is 250 pieces per design; material, sample and commercial terms are confirmed against the project.
  → . الحد الأدنى للطلب MOQ هو 250 قطعة لكل تصميم؛ وتُؤكَّد المادة والعينة والشروط التجارية وفقًا للمشروع.
- `[attr]` Returnable paper parcel bags with a second seal, printed with your logo from 250 pieces. Get a price.
  → أكياس طرود ورقية بلاصق ثانٍ للإرجاع، مع طباعة الشعار وبحد أدنى 250 قطعة. اطلب عرض سعر.
- `[block]` Red Ring Box — Paper. Custom logo printing, MOQ 250 per design. Samples available.
  → علبة خاتم حمراء — ورقية. طباعة شعار مخصصة، الحد الأدنى للطلب 250 قطعة لكل تصميم. العينات متاحة.
- `[block]` . MOQ is 250 pieces per design; the part construction, grade, samples and commercial conditions are confirmed for the project.
  → . الحد الأدنى للطلب 250 قطعة لكل تصميم؛ تُؤكَّد بنية القطعة ودرجتها والعينات والشروط التجارية لكل مشروع.
- `[block]` Ring Box — Clear Window. Clear Window, Floating Tray. Custom logo printing, MOQ 250 per design. Samples available.
  → علبة خاتم — بنافذة شفافة. بنافذة شفافة، بصينية عائمة. طباعة شعار مخصصة، الحد الأدنى للطلب 250 قطعة لكل تصميم. العينات متاحة.
- `[block]` Jewelry Pouch — Travel. Custom logo printing, MOQ 250 per design. Samples available.
  → كيس مجوهرات — للسفر. طباعة شعار مخصصة، الحد الأدنى للطلب 250 قطعة لكل تصميم. العينات متاحة.
- `[block]` . Packrivo starts at 250 pieces per design. The cover construction, board, artwork, sample scope, price and timing are settled against the project bri…
  → . تبدأ Packrivo من 250 قطعة لكل تصميم. تُحدَّد بنية الغلاف والورق المقوى والتصميم الفني ونطاق العينات والسعر والتوقيت وفقًا لموجز المشروع.
- `[block]` Velvet Jewelry Box. Custom logo printing, MOQ 250 per design. Samples available.
  → علبة مجوهرات مخملية. طباعة شعار مخصصة، الحد الأدنى للطلب 250 قطعة لكل تصميم. العينات متاحة.
- `[block]` Jewelry Pouch. Custom logo printing, MOQ 250 per design. Samples available.
  → كيس مجوهرات. طباعة شعار مخصصة، الحد الأدنى للطلب 250 قطعة لكل تصميم. العينات متاحة.

##### de（5 条）

- `[block]` . Packrivo's minimum is 250 pieces per design. Materials, sample scope, price and timing are confirmed for the project after the brief and supplier qu…
  → . Packrivos Minimum sind 250 Stück pro Design. Materialien, Musterumfang, Preis und Zeitpunkt werden für das Projekt bestätigt, nachdem Briefing und L…
- `[block]` . Packrivo starts at 250 pieces per design. The cover construction, board, artwork, sample scope, price and timing are settled against the project bri…
  → . Packrivo beginnt bei 250 Stück pro Design. Deckelkonstruktion, Kartonage, Druckbild, Musterumfang, Preis und Zeitplan werden anhand des Projekt-Brie…
- `[attr]` Die-cut inserts that cradle each item in the box, made to your outline from 250 pieces. Get a price.
  → Gestanzte Einlagen, die jedes Teil in der Schachtel halten, nach Ihrer Kontur, ab 250 Stück. Preis anfragen.
- `[block]` Jewelry Pouch — Velvet. Velvet, Drawstring, Storage — Ref. Q5YF1Q. Custom logo printing, MOQ 250 per design. Samples available.
  → Schmuckbeutel — Samt. Samt, Kordelzug, Aufbewahrung — Ref. Q5YF1Q. Druck mit eigenem Logo, MOQ 250 pro Design. Muster verfügbar.
- `[block]` Gold Ring Box — Flocked. Custom logo printing, MOQ 250 per design. Samples available.
  → Goldene Ringbox — Beflockt. Druck mit eigenem Logo, MOQ 250 pro Design. Muster verfügbar.

##### es（5 条）

- `[block]` MOQ: 250 pieces per product / design.
  → MOQ: 250 piezas por producto / diseño.
- `[attr]` Full-flap cartons with an auto bottom for heavy loads, with your logo from 250 pieces. Get a price.
  → Cajas con solapa completa y fondo automático para cargas pesadas, con su logo desde 250 unidades. Solicite un precio.
- `[block]` White Ring Box — Drawer. Custom logo printing, MOQ 250 per design. Samples available.
  → Caja para anillos blanca — cajón. Impresión de logo personalizada, cantidad mínima de pedido de 250 por diseño. Muestras disponibles.
- `[attr]` Mailer box inlays that hold products flat in transit, cut to your layout from 250 pieces. Get a price.
  → Bandejas interiores que mantienen el producto plano en el transporte, cortadas a su medida desde 250 unidades. Solicite un precio.
- `[block]` What the 250-piece minimum order means
  → Qué significa el pedido mínimo de 250 piezas

##### fr（5 条）

- `[block]` . Packrivo's minimum is 250 pieces per design. The final structure, material, sample work and commercial terms need project confirmation.
  → . Le minimum de Packrivo est de 250 pièces par modèle. La structure finale, le matériau, les travaux d'échantillonnage et les conditions commerciales …
- `[block]` White Jewelry Gift Box. Custom logo printing, MOQ 250 per design. Samples available.
  → Boîte cadeau à bijoux blanche. Impression de logo personnalisée, quantité minimum de commande 250 pièces par design. Échantillons disponibles.
- `[block]` . MOQ is 250 pieces per design; offered materials, samples and commercial conditions are confirmed for the project.
  → . Le MOQ est de 250 pièces par modèle ; les matières, les échantillons et les conditions commerciales proposés sont confirmés pour le projet.
- `[block]` Packrivo's order planning policy is 250 pieces per product / design. Identify the wrap, sealing component, printed extras and sample work in the scope…
  → La politique de planification de commande de Packrivo est de 250 pièces par produit / design. Identifiez l'emballage enveloppant, le composant de scel…
- `[attr]` Screw-top metal tins that reseal after opening, printed with your logo from 250 pieces. Get a price.
  → Boîtes métal à couvercle vissé qui se referment après ouverture, avec votre logo, dès 250 pièces. Demandez un prix.

##### it（5 条）

- `[block]` You want factory quotations compared at 250+ pieces per design, where unit costs fall.
  → Vuoi confrontare preventivi di fabbriche da 250 o più pezzi per design, quando i costi unitari diminuiscono.
- `[block]` Gold Ring Box — Flocked. Custom logo printing, MOQ 250 per design. Samples available.
  → Scatola per anelli oro — Floccato. Stampa logo personalizzata, quantità minima d'ordine 250 per modello. Campioni disponibili.
- `[text]` per unit at 1,000 pieces, sourcing fee included
  → per unità a 1.000 pezzi, commissione di approvvigionamento inclusa
- `[block]` Ring Box — Leather. Custom logo printing, MOQ 250 per design. Samples available.
  → Scatola per anelli — Pelle. Stampa logo personalizzata, quantità minima d'ordine 250 per modello. Campioni disponibili.
- `[attr]` Folding gift boxes with a decorative finish, printed with your logo and available from 250 pieces. Get a price.
  → Scatole regalo pieghevoli con finitura decorativa, stampate con il tuo logo, da 250 pezzi. Richiedi un preventivo.

##### ja（10 条，含额外 5 条）

- `[text]` Custom Kraft Soap Box with Window (MOQ 250) | Packrivo
  → 窓付きクラフト紙製ソープボックス（最小ロット250）| Packrivo
- `[block]` Velvet Jewelry Box. Storage — Ref. 010JBB. Custom logo printing, MOQ 250 per design. Samples available.
  → ベルベット製ジュエリーボックス。収納 — Ref. 010JBB。ロゴのカスタム印刷に対応しており、最小ロットはデザインあたり250個です。サンプルをご用意しています。
- `[attr]` Hang tags for gifts and retail, printed with your logo on both sides from 250 pieces. Get a price.
  → ギフトや店頭に付ける下げ札。両面にロゴ印刷ができ、250枚から。見積もりを依頼。
- `[block]` Velvet Jewelry Box. Travel, Storage. Custom logo printing, MOQ 250 per design. Samples available.
  → ベルベット製ジュエリーボックス。トラベル、収納。ロゴのカスタム印刷に対応しており、最小ロットはデザインあたり250個です。サンプルをご用意しています。
- `[block]` Small jewelry, candle and gift brands ordering 250+ custom boxes per design
  → デザインごとに250+個の特注箱を注文する、小規模なジュエリー・キャンドル・ギフトブランド
- `[block]` Ring Box — Ref. 01GDGP. Custom logo printing, MOQ 250 per design. Samples available.
  → リングボックス — Ref. 01GDGP。ロゴのカスタム印刷に対応しており、最小ロットはデザインあたり250個です。サンプルをご用意しています。
- `[block]` Necklace Box — Leather. Custom logo printing, MOQ 250 per design. Samples available.
  → ネックレスボックス — レザー。ロゴのカスタム印刷に対応しており、最小ロットはデザインあたり250個です。サンプルをご用意しています。
- `[attr]` Tiered counter displays that show several lines, printed with your logo from 250 pieces. Get a price.
  → 複数のラインを見せる多段カウンター什器。ロゴ印刷は250個から。見積もりを依頼。
- `[block]` Black Ring Box — Drawer. Drawer, Flocked, Paper. Custom logo printing, MOQ 250 per design. Samples available.
  → ブラックのリングボックス — 引き出し式。引き出し式、フロッキー加工、紙製。ロゴのカスタム印刷に対応しており、最小ロットはデザインあたり250個です。サンプルをご用意しています。
- `[attr]` Round roll labels for jars, lids and seals, printed with your logo and available from 250 pieces. Get a price.
  → ビンやフタ、封かん用の丸型ロールラベル。ロゴ印刷に対応し、250枚から。見積もりを依頼。

##### ko（5 条）

- `[block]` Discuss artwork and decoration options with the supplier for the proposed fabric or finish. Custom printing starts at 250 pieces per design; confirm c…
  → 제안된 원단이나 마감에 대해 아트워크와 장식 옵션을 공급사와 상의하세요. 맞춤 인쇄는 디자인당 250개부터 시작하며, 현재 재고 조건을 확인해야 합니다.
- `[attr]` Document pouches with a clear window for parcels, printed with your logo from 250 pieces. Get a price.
  → 투명 창이 있는 소포용 서류 봉투입니다. 로고 인쇄 가능하며 250개부터. 견적 문의.
- `[text]` 250 sheets, usually aligned with the box run.
  → 250장이며, 보통 상자 생산 수량과 맞춰 진행합니다.
- `[attr]` Hanger cartons with a five-panel back for retail pegs, printed with your logo from 250 pieces. Get a price.
  → 페그 진열용 5면 걸이형 카톤입니다. 로고 인쇄 가능하며 250개부터 제작합니다. 견적 문의.
- `[block]` . The minimum is 250 pieces per design. Final materials, construction, sample scope and commercial terms are confirmed for the project.
  → . 최소 수량은 디자인당 250개입니다. 최종 재료, 구조, 샘플 범위 및 상업 조건은 프로젝트 기준으로 확정됩니다.

##### nl（5 条）

- `[block]` to send unit details, the panel sketch, dispensing states, refill plan, quantity and destination. The minimum is 250 pieces per design. Final material…
  → om unitgegevens, de paneelschets, doseerstanden, het navulplan, hoeveelheid en bestemming te sturen. Het minimum is 250 stuks per ontwerp. Definitieve…
- `[attr]` Mailer boxes with a fitted insert for safe transit, printed with your logo from 250 pieces. Get a price.
  → Verzenddozen met een passende inleg voor veilig transport, bedrukt met je logo vanaf 250 stuks. Vraag een prijs aan.
- `[block]` Paper Jewelry Gift Bag. Pouch. Custom logo printing, MOQ 250 per design. Samples available.
  → Papieren sieradencadeauzakje. Buidel. Bedrukking met eigen logo, minimale bestelhoeveelheid 250 per ontwerp. Monsters beschikbaar.
- `[block]` Jewelry Pouch — Portable. Custom logo printing, MOQ 250 per design. Samples available.
  → Draagbare sieradenbuidel. Bedrukking met eigen logo, minimale bestelhoeveelheid 250 per ontwerp. Monsters beschikbaar.
- `[block]` Paper Jewelry Box — Ref.. Ref. TKTQ1T. Custom logo printing, MOQ 250 per design. Samples available.
  → Papieren sieradendoos — Ref.. Ref. TKTQ1T. Bedrukking met eigen logo, minimale bestelhoeveelheid 250 per ontwerp. Monsters beschikbaar.

##### pt（5 条）

- `[attr]` Foldable lid-and-base boxes that store flat, printed with your logo and available from 250 pieces. Get a price.
  → Caixas com tampa e base que se guardam planas, impressas com o seu logótipo, a partir de 250 unidades. Peça um preço.
- `[block]` . MOQ is 250 pieces per design; materials, sample scope and commercial terms are confirmed for the project.
  → . O MOQ é de 250 peças por design; materiais, escopo de amostras e termos comerciais são confirmados para o projeto.
- `[block]` Shenzhen-based Packrivo helps you source packaging through a network of 50+ independent suppliers, from product specifications and quotation compariso…
  → A Packrivo, sediada em Shenzhen, ajuda-o a adquirir embalagens através de uma rede de mais de 50 fornecedores independentes, desde as especificações d…
- `[text]` Magnetic Book-Style Presentation Box (MOQ 250) | Packrivo
  → Caixa de apresentação magnética estilo livro (quantidade mínima de encomenda 250) | Packrivo
- `[block]` Envelope Jewelry Pouch. Custom logo printing, MOQ 250 per design. Samples available.
  → Bolsa de joias tipo envelope. Impressão de logótipo personalizada, quantidade mínima de encomenda de 250 por design. Amostras disponíveis.

##### zh-CN（5 条）

- `[text]` Custom Printed Tissue Paper (MOQ 250) | Packrivo
  → 定制印刷薄页纸（起订量 250）| Packrivo
- `[block]` Gold Velvet Jewelry Box. Paper, Storage. Custom logo printing, MOQ 250 per design. Samples available.
  → 金色天鹅绒首饰盒。纸质、收纳。支持定制 LOGO 印刷，每款 250 件起订。可提供样品。
- `[block]` Jewelry Storage Box. Ref. 01BWNN. Custom logo printing, MOQ 250 per design. Samples available.
  → 首饰收纳盒。编号 01BWNN。支持定制 LOGO 印刷，每款 250 件起订。可提供样品。
- `[block]` Folding Cartons — Reference Catalogue. Custom logo printing, MOQ 250 per design. Samples available.
  → 折叠纸盒 — 参考目录。支持定制 LOGO 印刷，每款 250 件起订。可提供样品。
- `[block]` Paper Jewelry Gift Bag. Pouch. Custom logo printing, MOQ 250 per design. Samples available.
  → 纸质首饰礼品袋。袋式。支持定制 LOGO 印刷，每款 250 件起订。可提供样品。

##### zh-TW（5 条）

- `[block]` Gold Ring Box — Flocked. Custom logo printing, MOQ 250 per design. Samples available.
  → 金色戒指盒 — 植絨。可客製 Logo 印刷，每款最低訂購量 250 件。可索取樣品。
- `[attr]` Paperboard inserts that hold products in place, folded to your shape from 250 pieces. Get a price.
  → 可固定商品的紙板內襯，依外型摺製，250 件起訂。取得報價。
- `[block]` . The minimum is 250 pieces per design. Final construction, samples, price and timing are confirmed for the project.
  → . 每款最低量為 250 件。最終結構、樣品、價格與時程已就本專案確認。
- `[attr]` Molded pulp corner cradles that cushion edges, shaped to your product from 250 pieces. Get a price.
  → 可緩衝邊角的紙漿成型護角，依商品形狀製作，250 件起訂。取得報價。
- `[attr]` Poly bubble envelopes for online orders, printed with your logo and available from 250 pieces. Get a price.
  → 網路訂單用的塑膠氣泡信封，可印 logo，250 件起訂。取得報價。

#### 5. quote 三键落位（quote-i18n 与站点词典）

**ar** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- أقل من 250؟ أرسل الموجز وسنعدّ عرض السعر يدويًا.
- البضائع {goods} · رسوم التوريد {fee} ({percent}%، بحد أدنى {minimum}) · الإجمالي {total}
- تشمل عروض السعر الفورية رسوم التوريد بنسبة {percent}%؛ ويُثبَّت سعرك النهائي في عرض السعر المكتوب.

**de** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- Unter 250? Senden Sie das Briefing, wir kalkulieren manuell.
- Ware {goods} · Beschaffungsgebühr {fee} ({percent} %, min. {minimum}) · Gesamt {total}
- Sofortangebote enthalten die Beschaffungsgebühr mit {percent} %; Ihr endgültiger Preis wird im schriftlichen Angebot bestätigt.

**es** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- ¿Menos de 250? Envíe el briefing y le cotizamos a mano.
- Mercancía {goods} · Comisión de aprovisionamiento {fee} ({percent} %, mín. {minimum}) · Total {total}
- Los presupuestos al instante incluyen la comisión de aprovisionamiento al {percent} %; su tarifa final se confirma en el presupuesto escrito.

**fr** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- Moins de 250 ? Envoyez le brief et nous chiffrons à la main.
- Marchandise {goods} · Frais de sourcing {fee} ({percent} %, min. {minimum}) · Total {total}
- Les devis immédiats intègrent les frais de sourcing à {percent} % ; votre tarif final est confirmé sur le devis écrit.

**it** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- Meno di 250 pezzi? Inviate il brief e vi quotiamo manualmente.
- Merci {goods} · Commissione di approvvigionamento {fee} ({percent}%, min. {minimum}) · Totale {total}
- I preventivi immediati includono la commissione di approvvigionamento al {percent}%; la tariffa finale è confermata sul preventivo scritto.

**ja** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- 250個未満ですか？要件をお送りいただければ、担当者が個別にお見積もりします。
- 商品 {goods} · 調達手数料 {fee}（{percent}%、最低 {minimum}）· 合計 {total}
- 即時見積もりには{percent}%の調達手数料が含まれます。最終的なレートは書面の見積書で確定します。

**ko** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- 250개 미만인가요? 브리프를 보내주시면 직접 견적해 드립니다.
- 상품 {goods} · 소싱 수수료 {fee} ({percent}%, 최저 {minimum}) · 합계 {total}
- 즉시 견적에는 {percent}% 소싱 수수료가 포함되며, 최종 단가는 서면 견적에서 확정됩니다.

**nl** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- Minder dan 250? Stuur de briefing en we maken handmatig een prijsopgave.
- Goederen {goods} · Inkoopvergoeding {fee} ({percent}%, min. {minimum}) · Totaal {total}
- Directe prijsopgaven bevatten de inkoopvergoeding van {percent}%; je definitieve tarief wordt bevestigd op de schriftelijke offerte.

**pt** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- Menos de 250? Envie o briefing e orçamentamos manualmente.
- Mercadorias {goods} · Taxa de fornecimento {fee} ({percent}%, mín. {minimum}) · Total {total}
- Os orçamentos imediatos incluem a taxa de fornecimento de {percent}%; a sua tarifa final é confirmada no orçamento escrito.

**zh-CN** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- 不到 250 件？把需求发来，我们人工报价。
- 货款 {goods} · 采购协调费 {fee}（{percent}%，最低 {minimum}）· 合计 {total}
- 即时报价按 {percent}% 计入采购协调费；最终价格以书面报价为准。

**zh-TW** 3/3 落位：3；站点词典 title 桶含 404 标题：是
- 少於 250 件？寄來需求簡報，我們人工報價。
- 貨品 {goods} · 採購協調費 {fee}（{percent}%，最低 {minimum}）· 總計 {total}
- 即時報價已按 {percent}% 計入採購協調費；最終單價以書面報價為準。

## 4. verifier 原始输出

命令：`cd E:\破甲工作区\.tmp\32a-i18n && python -u verify2.py *> verify3.log`（退出码 0）

```
lang   新增(title/attr/block/text)    旧键/旧值丢失 残留500                      quote/404/格式
.. ar   0.0s
ar     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'الصفحة غير موجودة | Packri' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. de   0.8s
de     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'Seite nicht gefunden | Pac' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. es   1.3s
es     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'Página no encontrada | Pac' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. fr   1.9s
fr     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'Page introuvable | Packriv' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. it   2.4s
it     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'Pagina non trovata | Packr' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. ja   3.1s
ja     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'ページが見つかりません | Packrivo' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. ko   3.8s
ko     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 '페이지를 찾을 수 없습니다 | Packrivo' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. nl   4.4s
nl     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'Pagina niet gevonden | Pac' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. pt   5.1s
pt     4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 'Página não encontrada | Pa' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. zh-CN   5.7s
zh-CN  4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 '页面不存在 | Packrivo' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
.. zh-TW   6.3s
zh-TW  4/141/375/44                 丢键0 丢值0 残留0                        quote缺0 丢0 占位0 | 404 '找不到頁面 | Packrivo' | 格式 dict{'bom': False, 'crlf': True, 'trail': True, 'indent': 2} quote{'bom': False, 'crlf': True, 'trail': True, 'indent': 1}
---
新增合计 {'title': 44, 'attr': 1551, 'block': 4125, 'text': 484} 总计 6204
VERIFY PASS bad=0 
```

## 5. gate 原始输出

命令：`cd E:\破甲工作区\packrivo-backup-32a && python -u deploy/32a-i18n-gate.py`（退出码 0）

```
csv_rows=573 unique_keys=565 quote_keys=3
ar missing=0 quote_missing=0 quote_invalid=0 title_missing=0
de missing=0 quote_missing=0 quote_invalid=0 title_missing=0
es missing=0 quote_missing=0 quote_invalid=0 title_missing=0
fr missing=0 quote_missing=0 quote_invalid=0 title_missing=0
it missing=0 quote_missing=0 quote_invalid=0 title_missing=0
ja missing=0 quote_missing=0 quote_invalid=0 title_missing=0
ko missing=0 quote_missing=0 quote_invalid=0 title_missing=0
nl missing=0 quote_missing=0 quote_invalid=0 title_missing=0
pt missing=0 quote_missing=0 quote_invalid=0 title_missing=0
zh-CN missing=0 quote_missing=0 quote_invalid=0 title_missing=0
zh-TW missing=0 quote_missing=0 quote_invalid=0 title_missing=0
GATE PASS missing_entries=0 title_errors=0 errors=0
```

最后一行 `GATE PASS missing_entries=0 title_errors=0 errors=0`。11 语言 `missing=0 quote_missing=0 quote_invalid=0 title_missing=0`。

## 6. 本轮对译文的修正

zh-TW 的 B/A 条目里有 130 条被译成了「…250 件起訂，**由我們安排出貨**。取得報價。」—— 「由我們安排出貨」原文里没有，属于凭空承诺。已从 130 条新键里删掉该分句（只动新增键的译文，键与桶未变，故 gate / 旧键计数均不受影响）。现在这些条目是「…250 件起訂。取得報價。」。

同名分句在 260 条**旧键**里仍存在（`Clear display boxes…available from 500 pieces` 一类的历史条目），那批旧键属于既有内容、不是本轮迁移范围，本轮只做保留不改写。

另做了两轮越界扫描确认没有别的凭空承诺：一是「译文出现第一人称 / 发货 / 认证 / 工厂 / 客户数，而英文原文没有」的全量比对，剩余命中全是假阳性（`Sekunden` 含 `kunden`、`المنحنى` 含 `نحن`、`ship flat` 等原文自带的运输词）；二是 zh-TW 删句后含「我們」的 6 条，对应英文原文本身就带 `We / Our / we'll`。

## 7. 已知弱项与说明

**与既有键冲突（保留既有值，新键照常写入目标桶）**：ko 1、zh-CN 3、zh-TW 16、es 1、fr 1；it / nl / pt / ar / de / ja 各 0。冲突只影响同一个英文键在两个桶里各有一份译文，gate 与旧键计数都不受影响。

**术语统一修正**：pt 的 sourcing fee 子代理先写成 `taxa de aprovisionamento`，已按词典统一为 `Taxa de fornecimento`（8 处）；es 的 dielines 写成 `Plantillas de troquel incluidas`，已按词典统一为 `Trazados de troquel incluidos`（1 处）。

**各语言自报最弱条目**（保留原文，便于后续人工润色，均不影响 gate）：

| 语言 | 弱项 |
|---|---|
| ar | 源串本身残缺的 `Ring Box — Drawer — Ref..`；`Percent` 偏长；`Forest Green` 直译 |
| ja | `Ref.。` 双句点；`Pouches` → ポーチ 略窄 |
| ko | `Percent` 取 `비율`（词库另有 `요율`）；`From · 250 pcs` → `250개부터` |
| de | #183 句式偏硬；#044 `Carry.` 未译；#027 产品名截断 |
| zh-CN | `X — Ref..` 变成悬空「编号。」；[019] 描述重复；`$%s` 语序别扭 |
| fr | `supplier..` 合并；`1 000` 需 U+00A0；`$3,26` 保留 `$` |
| zh-TW | `250 / design` → 「250 件 / 設計」；`Beige Jewelry Storage` 补了品类词 |
| it | `250 / design` → `250 unità / modello`；`Carry.` 未译；`Trasparente.` 丢了 window 含义 |
| es | `bolsas y bolsas de regalo` 撞词；`Caja para collares — transparente` 语序 |
| pt | #269 `A partir de 250 unidades` 丢掉 `·`；#274/#267 术语展开约 2.5× 偏长（徽标位）；#216 `o nosso` 与「a Packrivo」不一致 |
| nl | #275 `MOQ 250` → `Minimale bestelhoeveelheid 250` 偏长；#258 `inLanguage` 由 `en` 改 `nl`；#263 `plate and die setup` → `de opstart van plaat en stans` |

**`inLanguage`**：长 JSON-LD 键的译文里 `inLanguage` 各语言不一致（de/es/fr/it → 本语言，it 为 `it-IT`；nl → `nl`；ja/ko/zh-CN/zh-TW/ar → `en`）。按各翻译手的选择保留，gate 不校验该字段。

## 8. 文件格式与变更清单

22 个 JSON 全部：UTF-8 无 BOM；站点词典 `indent=2`、quote 词典 `indent=1`；行尾 CRLF；文件末尾有换行。`json.loads` 全部成功（见第 3 节第 1 表）。

`git status --porcelain`：

```
 M deploy/i18n-merged/ar-dict.json
 M deploy/i18n-merged/de-dict.json
 M deploy/i18n-merged/es-dict.json
 M deploy/i18n-merged/fr-dict.json
 M deploy/i18n-merged/it-dict.json
 M deploy/i18n-merged/ja-dict.json
 M deploy/i18n-merged/ko-dict.json
 M deploy/i18n-merged/nl-dict.json
 M deploy/i18n-merged/pt-dict.json
 M deploy/i18n-merged/zh-CN-dict.json
 M deploy/i18n-merged/zh-TW-dict.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/ar.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/de.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/es.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/fr.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/it.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/ja.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/ko.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/nl.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/pt.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/zh-CN.json
 M packrivo-wp/packrivo-quote/includes/quote-i18n/zh-TW.json
?? .rehearsal-32a/
?? dsh-out/32a-blog-rewrite.csv
?? dsh-out/32a-blog-rewrite.md
?? dsh-out/32a-i18n-report.md
```

`git diff HEAD --numstat`（22 个文件）：

```
568	4	deploy/i18n-merged/ar-dict.json
568	4	deploy/i18n-merged/de-dict.json
568	4	deploy/i18n-merged/es-dict.json
568	4	deploy/i18n-merged/fr-dict.json
568	4	deploy/i18n-merged/it-dict.json
568	4	deploy/i18n-merged/ja-dict.json
568	4	deploy/i18n-merged/ko-dict.json
568	4	deploy/i18n-merged/nl-dict.json
568	4	deploy/i18n-merged/pt-dict.json
568	4	deploy/i18n-merged/zh-CN-dict.json
568	4	deploy/i18n-merged/zh-TW-dict.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/ar.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/de.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/es.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/fr.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/it.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/ja.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/ko.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/nl.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/pt.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/zh-CN.json
4	1	packrivo-wp/packrivo-quote/includes/quote-i18n/zh-TW.json
```

每份站点词典新增 568 行、删除 4 行（4 行来自末尾结构行，不是删条目）；每份 quote 词典新增 4 行、删除 1 行。`.rehearsal-32a/`、`dsh-out/32a-blog-rewrite.*` 是本任务之前就存在的未跟踪文件，本轮未动。

## 9. 未执行 / 未改动

- 未提交（按要求做完停下汇报）。
- 未部署、未重建容器、未迁移数据库、未跑 WP 后台导入 —— 本轮只改词典文件。译文在真实站点上的渲染未验证（未执行）；下一步要验，命令是起 .rehearsal-32a 站点后逐个语言访问首页、产品页与报价组件。
- 未改动 `packrivo-i18n.php` 消费端；查表函数 `packrivo_i18n_lookup($s, array $kinds)` 的桶顺序与空白归一化行为决定了「键按 CSV 原文原样存」是安全的。
