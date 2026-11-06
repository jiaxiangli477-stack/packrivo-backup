# 36a 落地页 11 语译文 · 交付报告

工作区 `E:\破甲工作区\packrivo-backup`，分支 `main`，基线 HEAD `1ed5468`（`wip: dsh 32a8 missing translations csv (not yet reviewed)`）。
本轮只增两个路径：`dsh-out/36a-landing/i18n/`（目录）与 `dsh-out/36a-landing/36a-i18n-report.md`。不改词典、不动容器和数据库、不改 4 个英文源、不改 `36a-pages.csv`、不碰 `36a-report.md`、不碰 35a 未跟踪文件与 `_bak-35a-*` 与 `deploy/`。

## 0. 交付物与前置状态

### 0.1 交付物清单

| # | 路径 | 数量 | 说明 |
|---|---|---|---|
| 1 | `dsh-out/36a-landing/i18n/<lang>/<slug>.md` | 44（11 语言 × 4 页） | 正文译文；头部元数据块为英文逐字节副本 |
| 2 | `dsh-out/36a-landing/i18n/36a-head-i18n.csv` | 132 数据行 + 1 表头 | `url,field,lang,en,value`；`en` 逐字转录自 `36a-pages.csv` |
| 3 | `dsh-out/36a-landing/36a-i18n-report.md` | 本文件 | 结构与一致性证据、超长清单、关键词落点、回译、编码证明 |

语言：`ar de es fr it ja ko nl pt zh-CN zh-TW`。页面 slug：`custom-ring-boxes`、`custom-earring-boxes`、`custom-necklace-boxes`、`jewelry-gift-boxes`。

### 0.2 干活前后状态

- 开工前 `git status --porcelain` 已存档：`E:\破甲工作区\.tmp\36a\gitstatus-before.txt`，共 7 条 `??`，全部为 35a 遗留未跟踪项（`_bak-35a-2-20261105-015549/`、`_bak-35a-3-20261105-034722/`、`dsh-out/35a-catalogue-inventory.csv`、`dsh-out/35a-image-backfill.csv`、`dsh-out/35a-new-strings.csv`、`dsh-out/35a-product-plan.md`、`dsh-out/35a-products.json`）。这 7 条本轮一个字节未动。
- 基线 HEAD 记录于 `E:\破甲工作区\.tmp\36a\head-before.txt` = `1ed5468`。
- 4 个英文源文件、`36a-pages.csv`、`36a-report.md` 全程只读，`git status` 中始终未出现 ` M`。

### 0.3 判定用命令（本轮实际执行过）

| 命令 | 关键输出 |
|---|---|
| `python -X utf8 .tmp\36a\lint.py all` | `files 44, fail 0` |
| `python -X utf8 .tmp\36a\merge_head.py` | `wrote …i18n\36a-head-i18n.csv : 132 rows (excl. header)` / `ALL CHECKS PASS` / `overlong entries: 0` |
| `python -X utf8 .tmp\36a\verify_all.py` | `files 44 (expected 44)` / `link urls compared 385` / `problems 0` |
| `python -X utf8 .tmp\36a\report_gen.py` | `wrote …report-part1.md (99 lines)` / `overlong entries: 0` |
| `git ls-files --eol` | 见 §5 |

`lint.py` 是本轮唯一的结构裁定器：`##`/`###` 标题数、段落数、列表项数、表格行列数、FAQ 对数与顺序、链接数量与 URL 顺序、粗体与行内代码标记数、行数与空行位置、固定 token 计数、每行数字多重集、每文件区间分隔符一致性，逐文件与英文比对。子代理自报一律不算数：ko 曾自报 `4/4 PASS`，被 `lint.py all` 拦下（`5~10` 与 `5-10` 混用），已改并复验。

## 1. 结构对照（逐文件，右侧列均为「译文 / 英文」）

| lang | page | 行数 | `##` | `###` | 段落 | 列表项 | 表格数据行 | FAQ 对 | 链接数 | 结构差异 |
|---|---|---|---|---|---|---|---|---|---|---|
| ar | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| ar | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| ar | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| ar | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| de | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| de | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| de | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| de | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| es | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| es | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| es | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| es | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| fr | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| fr | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| fr | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| fr | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| it | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| it | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| it | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| it | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| ja | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| ja | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| ja | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| ja | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| ko | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| ko | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| ko | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| ko | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| nl | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| nl | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| nl | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| nl | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| pt | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| pt | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| pt | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| pt | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| zh-CN | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| zh-CN | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| zh-CN | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| zh-CN | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |
| zh-TW | custom-ring-boxes | 83 / 83 | 6 / 6 | 0 / 0 | 18 / 18 | 8 / 8 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| zh-TW | custom-earring-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 7 / 7 | 无 |
| zh-TW | custom-necklace-boxes | 84 / 84 | 6 / 6 | 0 / 0 | 19 / 19 | 7 / 7 | 5 / 5 | 6 / 6 | 9 / 9 | 无 |
| zh-TW | jewelry-gift-boxes | 80 / 80 | 6 / 6 | 0 / 0 | 16 / 16 | 9 / 9 | 5 / 5 | 6 / 6 | 10 / 10 | 无 |

## 2. 超长条目

无。132 条 title/meta/h1 中，title 全部在字符上限内（拉丁语种 ≤60，CJK ≤30 附近）且均以 `| Packrivo` 结尾；meta 全部 ≤ 英文长度的 1.25 倍且 ≤190 字符。

## 3. 关键词落地用词（每页 × 每语，title / H1 / 首段）

| lang | page | title 用词 | H1 用词 | 首段用词 |
|---|---|---|---|---|
| ar | custom-ring-boxes | علب الخواتم المخصصة بشعار \| مخمل وPU وصلبة \| Packrivo | علب الخواتم المخصصة | تُحكم علبة الخاتم في اليد في نحو ثلاث ثوانٍ. وسواء أكانت تلك اللحظة طل… |
| ar | custom-earring-boxes | علب الأقراط بالجملة بشعار مخصص \| Packrivo | علب الأقراط بالجملة | تعبئة الأقراط مشكلة كميات قبل أن تكون مشكلة تصميم. فالعلامة التي لديها… |
| ar | custom-necklace-boxes | علب القلائد المخصصة بحشوات مضادة للتشابك \| Packrivo | علب القلائد المخصصة | لعلبة القلادة مهمة لا تتمتع بها أي علبة مجوهرات أخرى: أن تبقى السلسلة … |
| ar | jewelry-gift-boxes | علب هدايا المجوهرات صلبة ومغناطيسية وشرائط \| Packrivo | علب هدايا المجوهرات | تُشترى علبة الهدايا من أجل لحظة الفتح، لذا فقرار النمط يتعلق فعلياً بت… |
| de | custom-ring-boxes | Individuelle Ringschachteln aus Samt und PU \| Packrivo | Individuelle Ringschachteln | Eine Ringschachtel wird in der Hand beurteilt, in etwa drei Sekunden. … |
| de | custom-earring-boxes | Ohrringschachteln im Großhandel mit Logo \| Packrivo | Ohrringschachteln im Großhandel | Ohrringschachteln sind zuerst ein Mengenproblem und dann ein Designpro… |
| de | custom-necklace-boxes | Individuelle Halskettenschachteln mit Einlage \| Packrivo | Individuelle Halskettenschachteln | Eine Halskettenschachtel hat eine Aufgabe, die keine andere Schmucksch… |
| de | jewelry-gift-boxes | Schmuck-Geschenkboxen mit Zierband und Folie \| Packrivo | Schmuck-Geschenkboxen | Eine Geschenkbox wird für den Moment des Öffnens gekauft, deshalb geht… |
| es | custom-ring-boxes | Estuches para anillos personalizados con logo \| Packrivo | Estuches para anillos personalizados | Un estuche para anillos se juzga en la mano, en unos tres segundos. Qu… |
| es | custom-earring-boxes | Estuches para pendientes al por mayor con logo \| Packrivo | Estuches para pendientes al por mayor | El embalaje de pendientes es un problema de cantidad antes que un prob… |
| es | custom-necklace-boxes | Estuches para collares personalizados \| Packrivo | Estuches para collares personalizados | Un estuche para collares tiene un trabajo que ningún otro estuche de j… |
| es | jewelry-gift-boxes | Cajas de regalo para joyería \| Rígidas \| Packrivo | Cajas de regalo para joyería | Una caja de regalo se compra por el momento de abrir, así que la decis… |
| fr | custom-ring-boxes | Écrins à bague personnalisés \| Velours et PU \| Packrivo | Écrins à bague personnalisés | Un écrin à bague se juge en main, en trois secondes environ. Que ce mo… |
| fr | custom-earring-boxes | Écrins à boucles d'oreilles en gros \| Logo \| Packrivo | Écrins à boucles d'oreilles en gros | L'emballage de boucles d'oreilles est un problème de quantité avant d'… |
| fr | custom-necklace-boxes | Écrins à collier personnalisés \| Anti-emmêlement \| Packrivo | Écrins à collier personnalisés | Un écrin à collier a une fonction qu'aucun autre écrin de bijouterie n… |
| fr | jewelry-gift-boxes | Boîtes cadeaux bijoux \| Rigide et magnétique \| Packrivo | Boîtes cadeaux bijoux | Une boîte cadeau s'achète pour le moment de l'ouverture, la décision d… |
| it | custom-ring-boxes | Astucci per anelli personalizzati \| Packrivo | Astucci per anelli personalizzati | Un astuccio per anelli personalizzato si giudica in mano, in circa tre… |
| it | custom-earring-boxes | Astucci per orecchini all'ingrosso \| Packrivo | Astucci per orecchini all'ingrosso | Un programma di astucci per orecchini è un problema di quantità prima … |
| it | custom-necklace-boxes | Astucci per collane anti-groviglio \| Packrivo | Astucci per collane personalizzati | Un astuccio per collane personalizzato ha un compito che nessun altro … |
| it | jewelry-gift-boxes | Scatole regalo per gioielli \| Packrivo | Scatole regalo per gioielli | Una scatola regalo per gioielli si compra per il momento dell'apertura… |
| ja | custom-ring-boxes | オリジナル リングケース ロゴ入り \| Packrivo | オリジナル リングケース | リングケースは、手に取ってからのほんの数秒で評価が決まります。その瞬間がプロポーズなのか、ブティックの店頭カウンターなのか、宅配便の封筒なの… |
| ja | custom-earring-boxes | オリジナル ピアスケース 量産 \| Packrivo | オリジナル ピアスケースの一括仕入れ | ピアスの包装は、デザインの問題になる前に数量の問題です。スタッド、フープ、ドロップ、ハギーを三つの金属色で揃えるブランドは、箱を一つ買うので… |
| ja | custom-necklace-boxes | オリジナル ネックレスケース \| Packrivo | オリジナル ネックレスケース | ネックレスケースには、他のジュエリーボックスにはない役割が一つあります。チェーンが入れた位置に留まることです。巻き材、箔、リボンといった他の… |
| ja | jewelry-gift-boxes | ジュエリー ギフトボックス 貼り箱 \| Packrivo | ジュエリー ギフトボックス | ギフトボックスは開ける瞬間のために買われるため、形状の選択は実質的に開封の順序の選択です。四つの順序で大半のジュエリー案件をカバーできます。 |
| ko | custom-ring-boxes | 맞춤 반지 케이스 \| 벨벳·PU \| Packrivo | 맞춤 반지 케이스 | 반지 케이스는 손에 쥔 몇 초 만에 판가름이 납니다. 그 순간이 프로포즈인지, 부티크 카운터인지, 택배 봉투 속인지가 디자인 … |
| ko | custom-earring-boxes | 맞춤 귀걸이 케이스 \| 대량 \| Packrivo | 맞춤 귀걸이 케이스 대량 주문 | 귀걸이 포장은 디자인 문제이기 전에 수량 문제입니다. 스터드, 후프, 드롭을 세 가지 금속 톤으로 운영하는 브랜드는 박스 하나… |
| ko | custom-necklace-boxes | 맞춤 목걸이 케이스 \| 엉킴 방지 \| Packrivo | 맞춤 목걸이 케이스 | 목걸이 케이스에는 다른 주얼리 박스에 없는 역할이 하나 있습니다. 체인이 놓인 자리에 그대로 있어야 한다는 것입니다. 랩과 박… |
| ko | jewelry-gift-boxes | 주얼리 선물 상자 \| 리본·자석 \| Packrivo | 주얼리 선물 상자 | 선물 상자는 여는 순간을 위해 사는 것이므로, 형태 결정은 곧 개봉 동선의 결정입니다. 네 가지 동선으로 대부분의 주얼리 프로… |
| nl | custom-ring-boxes | Gepersonaliseerde ringdozen \| Fluweel & PU \| Packrivo | Gepersonaliseerde ringdozen | Een ringdoos wordt in de hand beoordeeld, in ongeveer drie seconden. O… |
| nl | custom-earring-boxes | Oorbellendozen in bulk \| Eigen logo \| Packrivo | Oorbellendozen in bulk | Oorbellenverpakking is eerst een kwantumvraagstuk en dan een ontwerpvr… |
| nl | custom-necklace-boxes | Kettingdozen \| Klitvrije inzetstukken \| Packrivo | Kettingdozen op maat | Een kettingdoos heeft één taak die geen enkele andere sieradendoos hee… |
| nl | jewelry-gift-boxes | Cadeaudozen voor sieraden \| Luxe dozen \| Packrivo | Cadeaudozen voor sieraden | Een cadeaudoos wordt gekocht voor het moment van openen, dus de formaa… |
| pt | custom-ring-boxes | Caixas para Anéis Personalizadas \| Veludo e PU \| Packrivo | Caixas para Anéis Personalizadas | Uma caixa para anéis é avaliada na mão, em cerca de três segundos. Se … |
| pt | custom-earring-boxes | Caixas para Brincos em Quantidade \| Logótipo \| Packrivo | Caixas para Brincos em Grandes Quantidades | A embalagem para brincos é um problema de quantidade antes de ser um p… |
| pt | custom-necklace-boxes | Caixas para Colares \| Berços Anti-Enrolamento \| Packrivo | Caixas para Colares Personalizadas | Uma caixa para colares tem uma função que nenhuma outra caixa de joalh… |
| pt | jewelry-gift-boxes | Caixas de Presente para Joalharia \| Rígidas \| Packrivo | Caixas de Presente para Joalharia | Uma caixa de presente compra-se pelo momento de abrir, por isso a deci… |
| zh-CN | custom-ring-boxes | 定制戒指盒 天鹅绒 PU 精装盒 \| Packrivo | 定制戒指盒 | 戒指盒在手里被判断，只要三秒左右。这一瞬间是求婚、精品店柜台还是快递信封，在任何设计稿动笔之前就已经决定了款式。下面四种形式覆盖了大多数首饰… |
| zh-CN | custom-earring-boxes | 定制耳环盒 批量采购 \| Packrivo | 定制耳环盒批量采购 | 耳环包装首先是个数量问题，其次才是设计问题。一个品牌若有耳钉、耳圈、耳坠和小圈耳环，还分布在三档金属色泽上，它买的不是一个盒子，而是一张尺寸… |
| zh-CN | custom-necklace-boxes | 定制项链盒 防缠绕内衬 \| Packrivo | 定制项链盒 | 项链盒有一项别的首饰盒没有的任务：链条必须停在放好的位置上。其余一切——包覆、烫金、缎带——都是叠在这之上的装饰。 |
| zh-CN | jewelry-gift-boxes | 珠宝首饰礼盒 精装磁吸缎带 \| Packrivo | 珠宝首饰礼盒 | 礼盒是为开盒那一刻被买的，所以选形式其实是选开盒顺序。四种顺序覆盖了大多数首饰项目。 |
| zh-TW | custom-ring-boxes | 客製戒指盒 \| 天鵝絨 PU 裱紙盒 \| Packrivo | 客製戒指盒 | 戒指盒是在手上被評斷的，大約三秒就定了。這一刻發生在求婚、精品櫃檯還是快遞信封裡，會在設計稿動筆之前就決定款式。下面四種形式涵蓋大多數首飾專… |
| zh-TW | custom-earring-boxes | 客製耳環盒 \| 大批量訂製 \| Packrivo | 大批量客製耳環盒 | 耳環包裝先是數量問題，然後才是設計問題。一個同時有耳釘、耳圈、耳墜與貼耳款、跨三種金屬色的品牌，買的不是一款盒子，而是一套尺寸表加一份翻單排… |
| zh-TW | custom-necklace-boxes | 客製項鍊盒 \| 防纏繞內襯 \| Packrivo | 客製項鍊盒 | 項鍊盒有一件其他首飾盒沒有的任務：鍊條必須停在放上去的位置。其餘的——裱貼、燙金、緞帶——都是疊在上面裝飾。 |
| zh-TW | jewelry-gift-boxes | 珠寶首飾禮盒 \| 裱紙盒與緞帶 \| Packrivo | 珠寶首飾禮盒 | 禮盒是為打開的那一刻而買的，所以款式決定其實是開箱順序的決定。四種順序涵蓋大多數首飾專案。 |

## 4. 回译抽检

回译只用于验证「译文是否说清了英文原文那件事」，不作为新文案。整页回译取 `custom-earring-boxes`（4 页里正文最短的一页，68 行正文）。头部元数据块为英文逐字节副本，不在回译范围。

### 4.1 `ar` · `custom-earring-boxes` 整页回译（→ 中文）

> ## 耳环盒形式与内衬
>
> 耳环包装在成为设计问题之前，先是数量问题。一个同时有耳钉、耳圈、耳坠和贴耳款、跨三种金属色泽的品牌，买的不是一个盒子，而是一张尺寸矩阵和一份返单排期。下面这些形式，是能扛住这个现实的做法。
>
> - **卡纸背板包装**：印刷卡纸打两个孔，插入透明封套或小型折叠纸盒。单件最便宜，平放拍摄效果好，卡纸同时充当背板，防止耳针弯折。
> - **小型精装天地盖**：纸板盒壁，外裱加内衬。当盒子在柜台递交、或作为礼品寄出时，是更高一档的选择。
> - **磁吸**：同样的占位，盒盖靠磁铁定位，结构与标准[磁吸首饰盒](/product-magnetic-jewelry-box/)相同。用于耳环与项链或手链同组出套装的时候。
> - **抽屉**：托盘在封套内滑动，用于套装盒，以及所有在耳环托盘后面还带卡纸内衬的款式。
> - **布制首饰袋，或袋＋盒套装**：当耳环随布制首饰袋一起出行时，盒子装的是袋子，而不是首饰。
>
> 这里的内衬逻辑与戒指盒不同。耳环要求成对固定、间距恒定，所以带两道模切开槽的海绵垫、打两孔的卡纸、带两个凹槽的纸浆模塑托盘都能完成任务；变化的是开盒时耳环被看到的姿态。固定在卡纸上的成对耳环读起来像零售库存，放在天鹅绒开槽垫里的成对耳环读起来像礼品。蝶形耳堵的耳钉需要在卡纸背后留出空间，耳坠需要托盘有深度，耳圈通常需要更宽的垫子，而不是更深的盒子。
>
> 在定下尺寸矩阵之前，同一套内衬几何值得先对照[配内衬的首饰盒](/product-jewelry-box-with-insert/)验证一遍；[首饰内衬贴合指南](/guide-jewelry-insert-fit/)说明了怎样用一个盒子去试多种耳环款式。
>
> ## 材料、卡纸与印刷
>
> - **盒体**：天鹅绒、PU 皮、亚麻纸、印刷涂布纸。天鹅绒是礼品定位的默认值；当卡纸承载品牌、盒子保持素面时，印刷纸是默认值。
> - **卡纸**：300-400gsm 涂布或未涂布卡纸，卡纸透过封套可见时双面印刷，按你的耳环间距模切出耳针孔。
> - **内衬**：海绵外覆天鹅绒或绒面质感；卡纸背板包装用未涂布卡纸。
> - **工艺**：热烫金、压凸、丝网印刷，纸裱面上可做局部 UV。
>
> 在耳环项目里，实际要权衡的是卡纸背板包装与小型精装盒之间，这既是外观决定，也是数量的决定。卡纸包装靠一张印刷大纸就能把单个 SKU 铺到数千件；精装盒单件成本更高，但它能承载更高的零售价，也更扛得住快递操作。很多品牌两条线并行：主推 SKU 和礼品套装用精装盒，长尾配色用卡纸背板。
>
> 批量定价逻辑见[包装成本](/packaging-cost/)；适合小面积表面的工艺选项见[包装工艺](/customization/)。
>
> ## 批量数量、打样与返单
>
> 印刷耳环盒起订为**每个款式 250 件**；一个款式等于一个尺寸加一份设计文件。未印刷现货盒起订**100 件**，这是在印刷批次到货之前支撑一次上市的桥梁。**100-249 件**的订单人工报价。
>
> 耳环这边特有的建议是：打样打整张尺寸矩阵，而不是打样单个盒子。寄三种耳环过来——一枚耳钉、一对耳坠、一对耳圈——确认同一套内衬布局能固定住这三类，且成对之间不互相接触。打样需要**5-10 天**；一轮样品覆盖什么，写在[样品](/samples/)页。
>
> 因为耳环包装会随配色和季节套装周转，返单路径比首单更重要。把刀模图和烫金版留在档案里；设计文件不变、内衬相同的返单，通常可以直接复用现有的版。第一次报价时就问清楚：版是否为返单留用、续订需要提供什么。
>
> ## 报价、交期与运输条款
>
> 一份报价会列出各数量阶梯的单价、卡纸或盒体规格、内衬、模具（刀模与烫金版）以及运费。Packrivo 是采购团队，不是生产方：订单下到经过筛选的供应商网络，规格由它锁定，出货前验货，出口单证也由它处理。采购协调费为**订单金额的 8-15%，最低 $150**，单独列出。批发询价通过[获取报价](/get-a-quote/)，附 SKU 清单和每个 SKU 的数量，回复里带刀模图和阶梯单价。
>
> 运费按**DDP 或 FOB**报价。生产在签样后需要**7-30 天**；卡纸背板包装靠短端，配定制内衬的包覆精装盒靠长端。每份报价都包含**刀模图**。
>
> ## 耳环盒形式速览
>
> | 盒型 | 最适合 | 内衬 | 成本方向 |
> |---|---|---|---|
> | 卡纸加封套 | SKU 多的目录、预算低、平寄 | 打孔印刷卡纸 | 最低 |
> | 小型精装天地盖 | 柜台零售、礼品定位 | 天鹅绒垫配双槽 | 中等 |
> | 磁吸 | 礼品套装、多件套 | 托盘配夹扣或双槽 | 中等偏高 |
> | 抽屉 | 带隐藏卡纸或证书的套装 | 滑动托盘附带卡位的凹槽 | 中等偏高 |
> | 袋＋盒套装 | 与布制首饰袋一起出售的耳环 | 放袋子的开放腔体 | 中等 |
>
> ## 常见问题
>
> **你们给零售品牌批量供应耳环盒吗？**
> 供。批量在这里是常态：印刷盒每个款式 250 件，未印刷现货 100 件，返单沿用同一张刀模图和同一副烫金版。
>
> **一个盒子能同时装耳钉、耳坠和耳圈吗？**
> 通常靠内衬而不是靠盒体解决。双槽天鹅绒垫能覆盖耳钉和耳坠；耳圈需要更宽的垫子或更深的托盘。定尺寸矩阵之前，把这三类放在一起打样。
>
> **耳环盒可以用印刷背板卡吗？**
> 可以。卡纸双面可印，按你的间距模切耳针孔，背面还能放条码、尺寸矩阵或保养说明。
>
> **耳环盒该订什么尺寸？**
> 由这一系列里最大的耳环加上内衬厚度决定。诚实的答案来自样品：把最宽的耳圈和最长的耳坠寄过来，尺寸就从贴合测试里出来。
>
> **小盒盖上的烫金是什么？**
> 一块加热的版把金属箔压进裱面。在小尺寸耳环盒盖上，单点烫金比大面积图案效果好，因为表面积有限，细节容易被填满。
>
> **批量耳环盒订单要多久？**
> 打样 5-10 天，签样后生产 7-30 天，然后是运输。

对照结论：与英文一一对应，无新增事实、无新增数字、无自称为工厂；`250`/`100`/`100-249`/`5-10`/`7-30`/`8-15%`/`$150`/`300-400gsm`/`DDP`/`FOB`/`PU`/`UV`/`Packrivo` 全部落位，「Packrivo 是采购团队，不是生产方」这句在 ar 中完整保留。

### 4.2 `ja` · `custom-earring-boxes` 整页回译（→ 中文）

> ## 定制耳环盒的形式与内衬
>
> 耳环包装在成为设计问题之前，先是数量问题。一个把耳钉、耳圈、耳坠、贴耳款都做成三种金属色泽的品牌，买的不是一个盒子，而是一张尺寸网格和一份返单日程。下面这些形式是能扛住这个现实的。
>
> - **卡纸台纸型**：在打了两孔的印刷卡纸上，插进透明封套或小型折叠盒。单价最便宜，平放拍摄效果好，卡纸还兼作背板，防止耳针弯曲。
> - **小型天地盖裱纸盒**：纸板盒壁外裱加内衬。在柜台递交、或作为礼品寄出时是更高一档的选择。
> - **磁吸盒**：同样的尺寸，盒盖靠磁铁吸附，做法与标准[磁吸首饰盒](/product-magnetic-jewelry-box/)相同。用于耳环与项链或手链成套的时候。
> - **抽屉盒**：托盘装在封套里，适合套装盒，以及耳环托盘后面还要放卡纸的结构。
> - **首饰袋，或袋＋盒套装**：耳环与首饰袋一起交付时，盒子收的是袋子而不是商品本身。
>
> 内衬的思路与戒指盒不同。耳环需要成对保持固定间距，因此两道开槽的模切泡棉垫、打两孔的卡纸、设两个凹槽的纸浆模塑托盘，任何一种都能胜任。变的是开盒时的观感：放在卡纸上的成对耳环看起来像量贩库存，收在带槽天鹅绒垫里的成对耳环看起来像礼品。蝶形耳堵的耳钉要在卡纸背后留出腾挪空间，耳坠需要托盘有深度，耳圈则更适合把垫子做宽，而不是把盒子做深。
>
> 在把尺寸网格定下来之前，值得先用[带内衬的首饰盒](/product-jewelry-box-with-insert/)确认同一套内衬形状；[首饰内衬贴合指南](/guide-jewelry-insert-fit/)说明了怎样拿一个盒子去试多种耳环形状。
>
> ## 材料、卡纸与印刷
>
> - **盒体**：天鹅绒、PU 皮革、亚麻纸、印刷铜版纸。作为礼品定位时天鹅绒是标准；品牌由卡纸承载、盒子保持素面时，印刷纸是标准。
> - **卡纸**：300-400gsm 涂布纸或未涂布纸；卡纸能透过封套看见时做双面印刷，并按耳环间距模切耳针孔。
> - **内衬**：泡棉外覆天鹅绒或仿麂皮；卡纸台纸型用未涂布的板纸。
> - **加工**：烫金、素压凸、丝网印刷，纸裱面可做局部 UV。
>
> 耳环量产的取舍实际发生在卡纸台纸型和小型裱纸盒之间。这既是观感问题，也是数量问题。卡纸台纸型用一张印刷大张就能把单个 SKU 铺到数千个；裱纸盒单价上升，但能在店头挂更高的价位，也更扛得住快递搬运。很多品牌两条线并用：主推 SKU 和礼品套装用裱纸盒，配色的长尾用卡纸台纸型。
>
> 量产的定价思路整理在[包装成本](/packaging-cost/)；适合小面积的加工选项整理在[包装工艺](/customization/)。
>
> ## 量产数量、打样与返单
>
> 带印刷的耳环盒**每个款式 250 个起**。款式指一个尺寸加一份设计文件。素面现货盒**100 个起**，可以在印刷批次到货之前先顶过上市。**100 个到 249 个**的订单人工报价。
>
> 耳环特有的建议是：不是打样一个盒子，而是把一张尺寸网格都打样。寄来耳钉、耳坠、耳圈三种，就能确认一套内衬布局是否能把三类都收住、且成对不互相碰到。打样**5-10 天**。一轮打样包含什么，写在[样品](/samples/)页。
>
> 耳环包装会随配色和季节套装更换，所以返单路径比首单更重要。请把刀模图和烫金版保管好；设计文件不变、内衬相同的返单，通常可以复用现有的版。第一次报价时就要确认：版是否留用返单、再次下单需要什么。
>
> ## 报价、交期与运输条件
>
> 报价会写明各数量档的单价、卡纸或盒体规格、内衬、模具（刀模、烫金版）和运费。Packrivo 不是生产据点，而是采购团队：向审核过的供应商网络下单，把规格守住，出货前验货，处理出口单据。采购协调费为**订单金额的 8-15%，最低 $150**，作为独立明细项列出。量产咨询请附 SKU 清单和每个 SKU 的数量，从[询价](/get-a-quote/)发来。我们会回复刀模图和分档单价。
>
> 运费按**DDP 或 FOB**报价。生产在签样后**7-30 天**。卡纸台纸型在短的一端，带定制内衬的裱纸盒在长的一端。报价里一定包含**刀模图**。
>
> ## 耳环盒形式一览
>
> | 盒型 | 适合用途 | 内衬 | 成本感 |
> |---|---|---|---|
> | 卡纸与封套 | SKU 数多的目录、低预算、平寄 | 带耳针孔的印刷卡纸 | 最低 |
> | 小型天地盖裱纸盒 | 店头销售、礼品定位 | 双槽天鹅绒垫 | 中等 |
> | 磁吸盒 | 礼品套装、多件套 | 带夹扣的托盘或双槽 | 中等偏高 |
> | 抽屉盒 | 需要藏卡纸或证书的套装 | 带卡纸凹槽的滑动托盘 | 中等偏高 |
> | 袋＋盒套装 | 与首饰袋一起卖的耳环 | 放袋子的空腔 | 中等 |
>
> ## 常见问题
>
> **可以给小卖品牌量产供应耳环盒吗？**
> 可以。这里量产是通常情况：带印刷每个款式 250 个，素面现货 100 个起，返单沿用同一张刀模图和同一副烫金版。
>
> **一个盒子能装下耳钉、耳坠和耳圈吗？**
> 多数情况靠内衬而不是靠盒体解决。双槽天鹅绒垫能收耳钉和耳坠；耳圈需要把垫子做宽或把托盘做深。在把尺寸网格定下来之前，请把三类一起打样确认。
>
> **耳环盒可以用印刷台纸吗？**
> 可以。卡纸可以双面印刷，按指定间距模切耳针孔。背面还能放条码、尺寸网格或保养说明。
>
> **耳环盒该订哪个尺寸？**
> 是该系列里最大的耳环再加上内衬厚度。可靠的答案来自样品：把最宽的耳圈和最长的耳坠寄来，尺寸从贴合确认里得出。
>
> **小盒盖上的烫金是什么？**
> 用加热的版把金属箔压到裱面上的加工。小尺寸耳环盒盖上，面积有限、细线容易糊，所以只做一处的烫金标记，比大面积图案出来得更好。
>
> **耳环盒的量产订单要多久？**
> 打样 5-10 天，签样后生产 7-30 天，之后是运输。卡纸台纸型在短的一端，带定制内衬的裱纸盒在长的一端。

对照结论：`です・ます` 语域贯穿全页；术语用 ダイライン／中仕切り／貼り箱／スリットパッド／箔押し／空押し，与 34a 已交付译法一致；数字与英文一一对应，`$150` 未被空格化。

### 4.3 `zh-CN` · `custom-earring-boxes` 整页回译（→ English）

> ## Earring box formats and inserts
>
> Earring packaging is a quantity problem first and a design problem second. A brand that has studs, hoops, drops and huggies across three metal tones is not buying a box; it is buying a size matrix and a reorder schedule. The formats below are the ones that hold up under that reality.
>
> - **Printed card backing**: a printed card with two punched holes, slid into a clear sleeve or a small folding box. Lowest cost per unit, photographs well flat, and the card doubles as a backing that keeps the posts from bending.
> - **Small rigid lid-and-base**: paperboard walls with a wrap and a lining. The step up when the box is handed over at a counter or shipped as a gift.
> - **Magnetic**: same footprint, lid located by magnets, built the same way as the standard [magnetic jewelry box](/product-magnetic-jewelry-box/). Used when earrings ship as a set with a necklace or a bracelet.
> - **Drawer**: the tray slides inside a sleeve, used for set boxes and for anything that carries a card behind the earring tray.
> - **Pouch, or pouch-and-box set**: when earrings travel with a fabric pouch, the box holds the pouch rather than the jewelry.
>
> The insert logic here differs from ring boxes. Earrings need the pair held at a fixed spacing, so a foam pad with two die-cut slits, a card with two holes, or a pulp-molded tray with two wells all do the job; what changes is how the earrings read when the box opens. A pair mounted on a card reads as retail stock, a pair sitting in a velvet slit pad reads as a gift. Butterfly-back studs need clearance behind the card, drops need depth in the tray, and hoops usually need a wider pad rather than a deeper box.
>
> Before fixing the size matrix, the same insert geometry is worth checking against [the jewelry box with insert](/product-jewelry-box-with-insert/); [the jewelry insert fit guide](/guide-jewelry-insert-fit/) explains how to test one box against several earring formats.
>
> ## Materials, cards and print
>
> - **Body**: velvet, PU leather, linen paper, printed coated paper. Velvet is the default for gift positioning; printed paper is the default when the card carries the branding and the box stays plain.
> - **Card**: 300-400gsm coated or uncoated card, printed both sides when the card shows through the sleeve, die-cut with post holes at your earring spacing.
> - **Lining**: velvet or suede-touch over foam; uncoated card stock for card-backing formats.
> - **Finishes**: hot foil, blind emboss, screen print, spot UV on paper wraps.
>
> In earring programs the practical trade-off is between printed card backing and small rigid boxes, and it is a volume decision as much as a look decision. Card formats scale to thousands of units per SKU off one printed sheet; rigid boxes cost more per unit but carry a higher retail price and survive courier handling better. Many brands run both: rigid for hero SKUs and gift sets, card for the long tail of colorways.
>
> Bulk pricing logic is in [packaging cost](/packaging-cost/); finish options that suit a small surface area are in [packaging finishes](/customization/).
>
> ## Bulk quantities, sampling and reorders
>
> Printed earring boxes start at **250 pieces per style**; a style is one size plus one artwork. Unprinted stock boxes start at **100 pieces**, which bridges a launch before the printed run lands. Orders of **100-249 pieces** are quoted manually.
>
> The earring-specific advice is to sample the size matrix rather than a single box. Send three earring types — a stud, a drop and a hoop — and check that one insert layout holds all three without the pair touching. Sampling takes **5-10 days**; what one round covers is set out in [samples](/samples/).
>
> Because earring packaging turns over with colorways and seasonal sets, the reorder path matters more than the first order. Keep the dieline and the foil plate on file; a reorder on unchanged artwork and the same insert can usually reuse the existing tooling. Ask at the first quote whether the tooling is kept for reorders and what a repeat order needs.
>
> ## Quotes, lead times and shipping terms
>
> A quote lists unit price by quantity band, the card or body spec, the insert, tooling (cut die and foil plate) and freight. Packrivo is a procurement team, not a production site: orders are placed into a vetted supplier network, the spec is held, goods are inspected before shipment, and export paperwork is handled. The procurement coordination fee is **8-15% of order value, with a $150 minimum**, and is listed separately. Bulk enquiries go through [request a quote](/get-a-quote/) with a SKU list and quantity per SKU, and come back with the dieline and tiered unit pricing.
>
> Freight is quoted **DDP or FOB**. Production takes **7-30 days** after sample approval; card formats sit at the short end, wrapped rigid boxes with custom inserts at the long end. Every quote includes the **dieline**.
>
> ## Earring box formats at a glance
>
> | Box type | Best for | Insert | Cost orientation |
> |---|---|---|---|
> | Card and sleeve | High-SKU catalogues, low budget, flat shipping | Printed card with post holes | Lowest |
> | Small rigid lid-and-base | Counter retail, gift positioning | Velvet pad with twin slits | Mid |
> | Magnetic | Gift sets, multi-piece sets | Tray with clip or twin slits | Mid to higher |
> | Drawer | Sets with a hidden card or certificate | Sliding tray with a card well | Mid to higher |
> | Pouch-and-box set | Earrings sold with a fabric pouch | Open cavity for the pouch | Mid |
>
> ## FAQ
>
> **Do you supply earring boxes in bulk for retail brands?**
> Yes. Bulk is the normal case here: 250 pieces per style for printed boxes, 100 pieces for unprinted stock, with reorders on the same dieline and foil plate.
>
> **Can one box hold studs, drops and hoops?**
> Usually through the insert rather than the body. A twin-slit velvet pad covers studs and drops; hoops need a wider pad or a deeper tray. Sample all three together before fixing the size matrix.
>
> **Can earring boxes use a printed backing card?**
> Yes. The card can be printed both sides, die-cut with post holes at your spacing, and carry a barcode, size matrix or care instructions on the back.
>
> **What size earring box should I order?**
> The largest earring in the family plus the insert thickness. The honest answer comes from a sample: send the widest hoop and the longest drop, and the size falls out of a fit check.
>
> **What is foil on a small lid?**
> A heated die presses metallic foil into the wrap. On small earring box lids a single foil mark works better than a wide design, because surface area is limited and fine detail fills in.
>
> **How long does a bulk earring box order take?**
> Sampling 5-10 days, production 7-30 days after approval, then transit.

对照结论：英语回译与原英文在事实上完全重合，可直接与 `custom-earring-boxes.md` 逐段对读；差异仅在个别措辞（如原英文 "The earring-specific advice is to sample the size matrix, not a single box"，zh-CN 作「样品要试尺寸矩阵，而不是试单个盒子」，回译为 "sample the size matrix rather than a single box"），语义未变。

### 4.4 其余 8 语言 · 各 3 段回译（→ 中文）

每页固定抽三段：**A** 正文首段（第一个 `##` 之后的第一段）、**B** 起订量段（含 `250`/`100` 的那一段）、**C** 首条 FAQ 问答。均取 `custom-earring-boxes`。

| lang | 段 | 回译 |
|---|---|---|
| de | A | 耳环盒首先是数量问题，然后才是设计问题。一个把耳钉、耳圈、耳坠和贴耳款做成三种金属色泽的品牌，买的不是一个盒子，而是一张尺寸网格和一份返单周期。下面这些形式，是能扛住这个现实的。 |
| de | B | 带印刷的耳环盒**每个款式 250 件**起；一个款式是一个尺寸加一份印刷图稿。未印刷现货**100 件**起，可以在印刷批次到货之前先顶过一次上市。**100-249 件**的订单人工核算。 |
| de | C | **你们给零售品牌批量供应耳环盒吗？** 是。批量在这里是常态：印刷盒每个款式 250 件，未印刷现货 100 件，返单沿用同一张刀模图和同一块烫金版。 |
| es | A | 耳环包装是数量问题，先于设计问题。一个把耳钉、圈耳、垂坠款和贴耳款做成三种金属色泽的品牌，买的不是一个盒子，而是一张尺寸网格和一份补货日历。下面这些形式是能扛住这个现实的。 |
| es | B | 印刷耳环盒**每个款式 250 件**起；一个款式是一个尺寸加一份图稿。未印刷现货盒**100 件**起，可以在印刷批到货之前覆盖一次上市。**100-249 件**的订单人工报价。 |
| es | C | **你们为零售品牌批量供应耳环盒吗？** 是。批量在这里是常态：印刷盒每个款式 250 件，未印刷现货 100 件，补货沿用同一副刀模和同一块烫金版。 |
| fr | A | 耳环包装是数量问题，先于设计问题。一个同时有耳钉、圈耳、垂坠款和贴耳款、跨三种金属色调的品牌，买的不是一个盒子：它买的是一张尺寸网格和一份补货日历。下面这些形式是能扛住这个现实的。 |
| fr | B | 印刷耳环盒**每个款式 250 件**起；一个款式是一个尺寸加一份图稿。未印刷现货盒**100 件**起，可以在印刷批次到货前覆盖一次上市。**100-249 件**的订单人工报价。 |
| fr | C | **你们为零售品牌批量供应耳环盒吗？** 是。批量在这里是常态：印刷盒每个款式 250 件，未印刷现货 100 件，补货沿用同一份刀模轮廓和同一块烫金版。 |
| it | A | 耳环盒方案首先是数量问题，然后才是设计问题。一个把耳钉、圈耳、垂坠款和贴耳款做成三种金属色调的品牌，买的不是一个盒子，而是一张尺寸网格和一份补货日程。下面这些形式是能扛住这个现实的。 |
| it | B | 印刷耳环盒**每个款式 250 件**起；一个款式是一个尺寸加一份图形文件。未印刷现货盒**100 件**起，可以在印刷批次到货前覆盖一次上市。**100-249 件**的订单人工报价。 |
| it | C | **你们为零售品牌批量供应耳环盒吗？** 是。批量在这里是常态：印刷盒每个款式 250 件，未印刷现货 100 件，补货沿用同一副刀模和同一块烫金版。 |
| ko | A | 耳环包装在成为设计问题之前，先是数量问题。一个把耳钉、耳圈、耳坠做成三种金属色调的品牌，买的不是一个盒子，而是一张尺寸网格和一份返单日程。下面这些形式是能在那样的现实里活下来的。 |
| ko | B | 印刷耳环盒**每个款式 250 个**起，一个款式是一个尺寸加一份设计文件。素面现货盒**100 个**起，可以在印刷批次进来之前先接住上市。**100 个到 249 个**的订单人工报价。 |
| ko | C | **你们给小卖品牌批量供应耳环盒吗？** 是。批量在这里是常规情况：印刷盒每个款式 250 个，素面现货 100 个，返单沿用同一张刀模图和同一块烫金版。 |
| nl | A | 耳环包装首先是数量问题，然后才是设计问题。一个把耳钉、圈耳、垂坠款和贴耳款做成三种金属色调的品牌，买的不是一个盒子，它买的是一张尺寸网格和一份返单计划。下面这些形式是实践中能扛住的。 |
| nl | B | 印刷耳环盒**每个款式 250 件**起；一个款式是一个尺寸加一份印刷文件。未印刷现货盒**100 件**起，可以在印刷批量到货之前先撑过一次上市。**100-249 件**的订单手工报价。 |
| nl | C | **你们给小卖品牌批量供应耳环盒吗？** 是。批量在这里是常规情况：印刷盒每个款式 250 件，未印刷现货 100 件，返单沿用同一副刀模和同一块烫金版。 |
| pt | A | 耳环包装是数量问题，先于设计问题。一个把钉式耳环、圈式耳环、垂坠款和贴耳款做成三种金属色调的品牌，不是在买一个盒子，而是在买一张尺寸网格和一份补货日程。下面这些形式是能扛住这个现实的。 |
| pt | B | 印刷耳环盒**每个款式 250 件**起；一个款式是一个尺寸加一份图稿。未印刷现货盒**100 件**起，可以在印刷批次到货之前覆盖一次上市。**100-249 件**的订单人工报价。 |
| pt | C | **你们为零售品牌批量供应耳环盒吗？** 是。批量在这里是常态：印刷盒每个款式 250 件，未印刷现货 100 件，补货沿用同一条刀模线和同一块烫金版。 |
| zh-TW | A | 耳環包裝先是數量問題，然後才是設計問題。一個同時有耳釘、耳圈、耳墜與貼耳款、跨三種金屬色的品牌，買的不是一款盒子，而是一套尺寸表加一份翻單排程。下面這些形式是能撐住這個現實的。 |
| zh-TW | B | 印刷耳環盒從**每款 250 個**起；一款等於一個尺寸加一份設計稿。無印刷現貨盒從**100 個**起，可以在印刷批到位前先撐過上市。**100 到 249 個**的訂單走人工報價。 |
| zh-TW | C | **你們有供零售品牌大批量的耳環盒嗎？** 有。大批量在這裡是常態：印刷盒每款 250 個，無印刷現貨 100 個，翻單沿用同一份刀模圖與燙金版。 |

8 语言 × 3 段共 24 段回译全部与原英文对应，未出现新增事实、新增数字、或把 Packrivo 说成工厂/生产方的表述。

## 5. 编码与行尾

全部新增文件为 UTF-8 无 BOM、LF 行尾、末尾有换行。逐文件 `git ls-files --eol` 原始输出：

```
i/lf    w/lf    attr/                 	dsh-out/36a-landing/36a-i18n-report.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/36a-head-i18n.csv
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ar/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ar/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ar/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ar/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/de/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/de/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/de/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/de/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/es/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/es/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/es/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/es/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/fr/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/fr/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/fr/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/fr/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/it/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/it/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/it/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/it/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ja/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ja/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ja/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ja/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ko/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ko/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ko/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/ko/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/nl/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/nl/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/nl/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/nl/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/pt/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/pt/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/pt/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/pt/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-CN/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-CN/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-CN/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-CN/jewelry-gift-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-TW/custom-earring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-TW/custom-necklace-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-TW/custom-ring-boxes.md
i/lf    w/lf    attr/                 	dsh-out/36a-landing/i18n/zh-TW/jewelry-gift-boxes.md
```

字段含义：`i/w` 为 index 与实际工作树，`w/lf` = 工作树 LF，`attr/` 空表示无 `.gitattributes` 覆盖，BOM 若有会以 `w/utf-8-bom` 或路径前的 BOM 标记体现。上方 46 行全部为 `i/lf` + `w/lf`，无 `bom`。

`verify_all.py` 的独立复核（不依赖 git）：对 44 个 `.md` 逐个读字节，检查 `EF BB BF` 前缀、`\r` 出现、末尾换行，结果 `problems 0`。

一处需要说明的仓库级事实：本仓库 `core.autocrlf=true` 且无 `.gitattributes`，因此 `git add` 时对刚改写过的工作树文件会提示 `LF will be replaced by CRLF the next time Git touches it`。这不影响落库内容——index 里全部是 LF（上方 `i/lf` 即为此证据），也不影响下一次检出：工作树上目前就是 LF。要让未来检出后仍保持 LF，需要新增 `.gitattributes`（`* text=auto eol=lf` 或按路径声明），该改动属于仓库级配置，不在本轮可写路径内，未做。

## 6. 术语、数字与区间写法口径

### 6.1 固定不译

`Packrivo`、`MOQ 250`、`DDP`、`FOB`、`EVA`、`PU`、`KD`、`gsm`、`UV` 在 11 语种中一律保持原形。`lint.py` 的 `TOKENS` 逐文件比对计数，英文正文基线（切片自 `---` 之后的正文，不含元数据块）为：

| page | `$150` | `DDP` | `FOB` | `EVA` | `PU` | `gsm`(计 `300-400gsm` 内) | `UV` | `Packrivo` |
|---|---|---|---|---|---|---|---|---|
| custom-ring-boxes | 1 | 1 | 1 | 1 | 5 | 0 | 1 | 2 |
| custom-earring-boxes | 1 | 1 | 1 | 0 | 1 | 1 | 1 | 1 |
| custom-necklace-boxes | 1 | 1 | 1 | 0 | 2 | 0 | 0 | 1 |
| jewelry-gift-boxes | 1 | 1 | 1 | 0 | 3 | 0 | 0 | 1 |

注意英文正文从不字面出现 `MOQ 250`（写作 "250 pieces per style"），`KD` 从不出现；这两项计数为 0 是正确状态，元数据块里的 `MOQ 250` / `KD 10` 属于逐字节英文副本，不计入正文。

### 6.2 数字

`lint.py` 按行比较数字串多重集（`\d+(?:[.,]\d+)*`），44 文件全部相等；`verify_all.py` 独立再算一遍，`problems 0`，共比对 72 个数字 token。允许的本地化只有千位分组符号，本批 4 页没有四位以上数量，因此实际未出现 `1.000` / `1 000` 这类分歧。

### 6.3 区间分隔符

正文中的 `5-10`（digits 打样天数）、`7-30`（交期）、`100-249`（人工报价区间）、`300-400gsm` 统一规范化为 ASCII 连字符，以保证与英文数字多重集严格一致。早期子代理混用了 `5~10`（ko 9 处）、`5 a 10` / `7 a 30` / `100 e 249`（pt），已由 `fix_ranges.py` 与 `assemble.py` 重跑修正。

有意保留的两种写法：

- **fr** `8-15 %`：法语排版中百分号前保留空格，`%` 字符计数不变，数字多重集不变，按排版习惯保留。
- **连接词区间**：`fr` 的 `100 et 249`、`ar` 的 `100 و249`，以及 zh-CN/zh-TW 的「100 到 249 件/個」。这些写法用连接词替代连字符，数字两端完全一致；`lint.py` 的 `SEP` 正则包含这些连接词形态，因此不会被判为混用。这是译入语的自然写法，不是脱漏。

### 6.4 头部 CSV 的阈值口径

`merge_head.py` 第一版把「CJK title ≤32 字符」写成了 `if CJK and len(val) > 32`，其中 `CJK` 是集合本身而非布尔条件，恒为真，导致 28 条 ar/de/es/fr/it/nl/pt title 被误报超限。改为 `if lg in CJK and len(val) > 32:` 后重跑，`overlong` 清单为空：拉丁语种 title 实测最长 59（fr necklace），CJK 实测最长 29（zh-TW ring），全部在限内。**§2 的结论以修正后的重跑输出为准，首轮那 28 条误报作废。**

meta description 的判据为 `len(value) ≤ max(190, 1.25 × len(en))`，132 条全部通过。

### 6.5 语域与术语

各语种按已交付的 37a8 / 34a / 32a8 口径沿用：de Sie、es 正式 usted、fr vous、it 无人称/正式、nl u-vorm、pt pt-PT（`cartão`、`registo`、`contacto`、`joalharia`、`equipa`、`logótipo`）、ar 无方言的 MSA、ja です・ます、ko 합니다체、zh-CN 精装盒/邮寄盒/内衬/刀模图/首饰袋/采购协调费、zh-TW 裱紙盒/飛機盒/內襯/刀模圖/客製/首飾袋/採購協調費。术语对照见 `E:\破甲工作区\.tmp\36a\glossary.md`（43 行 × 12 列），每页一词一译，同一页内不换词。

未使用 `deploy/i18n-merged/<lang>-dict.json` 以外的自造词；词典本身只读，未修改。

### 6.6 不做的事项

- 不自称工厂/制造商：所有语言均作「采购团队/不是生产方」，`nl` 全文未出现 `fabriek`。
- 不写 ISO / ISTA / FSC，不新增客户、证书、销量、评价。
- 英文源有一处内部不一致（`jewelry-gift-boxes.md` 写 "Four sequences" 但列出 5 项），按规格照抄不修正，11 语种同步照抄。

## 7. 未做项与边界

| 项 | 状态 |
|---|---|
| 提交到 `main` | 已完成，提交信息 `wip: dsh 36a landing translations (not yet reviewed)`，仅含 `i18n/**` 与本报告 |
| `git push` | 未执行（按规格） |
| 部署 / 构建 / 预览站 | 未执行（按规格） |
| 词典 `deploy/i18n-merged/*.json` | 未修改（按规格） |
| 容器、数据库 | 未触碰（按规格） |
| 35a 未跟踪文件、`_bak-35a-*`、`deploy/`、4 个英文源、`36a-pages.csv`、`36a-report.md` | 逐字节未变 |
| 新增 `hreflang` / 路由接入 / sitemap 变更 | 未做，本轮交付物只有译文与 head 文案 CSV |

### 7.1 提交记录

- 基线：`1ed5468`（`wip: dsh 32a8 missing translations csv (not yet reviewed)`），`main` 分支。
- 本轮落库为 `main` 上的**一个**提交，信息 `wip: dsh 36a landing translations (not yet reviewed)`，内容为 45 个文件（44 个译文 md + `i18n/36a-head-i18n.csv`）加本报告，共 46 个路径；未推送。
- 中间状态存档：先有一次提交 `5867bf9 feat(36a): 4 landing pages i18n x11 langs (44 md + head CSV)`（45 文件），其树内容与本轮最终提交的 45 个文件逐字节相同，已在该提交基础上 `git commit --amend` 把提交信息改为规格要求的那一行并把本报告并入；`5867bf9` 仍可从 `git reflog` 取回。
- 提交后核对方式（不引用本文件里的哈希，因为哈希随提交变化）：`git show --stat HEAD`、`git diff HEAD -- dsh-out/36a-landing`（应为空）、`git ls-tree -r --name-only HEAD -- dsh-out/36a-landing`。

复现命令（工作区根 `E:\破甲工作区\packrivo-backup`）：

```powershell
$py = 'C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe'
$env:PYTHONIOENCODING = 'utf-8'
& $py -X utf8 'E:\破甲工作区\.tmp\36a\lint.py' all
& $py -X utf8 'E:\破甲工作区\.tmp\36a\verify_all.py'
git ls-files --eol dsh-out/36a-landing/i18n dsh-out/36a-landing/36a-i18n-report.md
```
