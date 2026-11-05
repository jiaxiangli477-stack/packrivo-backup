# 32a 译文返工报告

| 项 | 值 |
| --- | --- |
| 任务 | 32a-译文返工（只改列出条目，其余不动；只交文件） |
| 仓库 / 分支 | `E:\破甲工作区\packrivo-backup-32a` / `32a` |
| 基线 | `6c49b99` |
| 改动文件 | 11 个 `deploy/i18n-merged/<lang>-dict.json` |
| 改动值总数 | 76（9 语各 6 + zh-CN 11 + zh-TW 11） |
| 键增删 | 0（键集合与基线逐桶逐键一致） |
| 门禁 | `GATE PASS missing_entries=0 title_errors=0 errors=0` |
| 未触碰 | 11 个 `quote-i18n/<lang>.json`、`32a-new-strings.csv`、非目标键 |

改动面按语言逐项核对（与 `6c49b99` 的 git blob 逐键比对，脚本实算，非人工统计）：

| 语言 | 变更值数 | 键集合一致 | title | attr | block | text |
| --- | --- | --- | --- | --- | --- | --- |
| 阿拉伯语 `ar` | 6 | 是 | 295 | 879 | 7123 | 426 |
| 德语 `de` | 6 | 是 | 295 | 880 | 7170 | 477 |
| 西班牙语 `es` | 6 | 是 | 301 | 880 | 7121 | 469 |
| 法语 `fr` | 6 | 是 | 301 | 880 | 7118 | 476 |
| 意大利语 `it` | 6 | 是 | 301 | 880 | 7120 | 471 |
| 日语 `ja` | 6 | 是 | 295 | 879 | 7126 | 426 |
| 韩语 `ko` | 6 | 是 | 295 | 880 | 7128 | 424 |
| 荷兰语 `nl` | 6 | 是 | 301 | 880 | 7122 | 475 |
| 葡萄牙语 `pt` | 6 | 是 | 301 | 880 | 7129 | 475 |
| 简体中文 `zh-CN` | 11 | 是 | 295 | 881 | 7128 | 474 |
| 繁体中文 `zh-TW` | 11 | 是 | 295 | 879 | 7126 | 426 |

桶计数与基线完全一致（例：ar 295/879/7123/426，zh-CN 295/881/7128/474，zh-TW 295/879/7126/426），键数与桶位都未变，只换了值。

## 一、第一部分：6 个键按新英文重译（66 个值）

四个竞品行的英文除品牌词外完全相同，因此译文也只允许品牌词不同；下表每组给 Arka 行，另三行仅替换品牌词，脚本已逐字节校验（去掉品牌词后四行完全相同）。

### 阿拉伯语 `ar`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：الحد الأدنى للعلب المطبوعة 250 قطعة لكل تصميم. تُشحن العلب غير المطبوعة المتوفرة بدءًا من 100 قطعة. تُسعَّر الكميات من 100–249 عند الطلب.
- 回译：印刷盒每款设计起订量 250 件。未印刷现货从 100 件起发货。100–249 件的批量按需报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：احسب القطع المطلوبة لكل تصميم. إذا كانت الكمية أقل من 250، فأرسل الموجز وسنعدّ عرض السعر يدويًا؛ وعند 250 قطعة أو أكثر، قارن عروض أسعار المصانع.
- 回译：按设计清点所需件数。不足 250 件时，把需求简报发来，我们人工报价；250 件及以上，比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：يعتمد ذلك على الكمية ونوع الصندوق. تناسب Arka الطلبيات الصغيرة التي تُطلب عبر الإنترنت. وتبدأ Packrivo من 250 قطعة لكل تصميم. فعلى سبيل المثال، عند طلب 500 قطعة، يبدأ سعر صناديق المجوهرات المتوفرة بنمط الدرج من نحو US$0.10–0.33 للصندوق الواحد تسليمًا من المصنع، قبل طباعة الشعار والشحن والرسوم الجمركية، إضافةً إلى رسوم تنسيق بنسبة 8–15%، على أن يُتفق كتابيًا على أساس الحساب.
- 回译：这取决于数量和盒型。Arka 适合在网上下单的小批量。Packrivo 从每款设计 250 件起。例如订购 500 件时，现货抽屉式首饰盒每盒自工厂交货约 US$0.10–0.33 起，不含 Logo 印刷、运费和关税，另加 8–15% 的协调费，计算基准以书面约定。

### 德语 `de`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：Mindestmenge 250 Stück pro Design für bedruckte Schachteln. Unbedruckte Lagerware wird ab 100 Stück versandt. Auflagen von 100–249 werden auf Anfrage kalkuliert.
- 回译：印刷盒每款设计起订量 250 件。未印刷的现货从 100 件起发货。100–249 件的批量按需计价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：Zählen Sie die benötigten Teile je Design. Unter 250 Stück senden Sie das Briefing, wir kalkulieren manuell; ab 250 Stück sollten Sie Angebote von Fabriken vergleichen.
- 回译：请按设计清点所需件数。不足 250 件时，把简报发给我们，我们人工计价；250 件起应比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：Das hängt von Menge und Boxart ab. Arka eignet sich für kleine, online bestellte Auflagen. Packrivo arbeitet ab 250 Stück je Design. Bei 500 Stück beginnen vorrätige Schmuckboxen im Schubladenstil beispielsweise bei etwa US$0.10–0.33 je Box ab Werk, vor Logodruck, Fracht und Zöllen, zuzüglich einer Koordinationsgebühr von 8–15%, deren Berechnungsgrundlage schriftlich vereinbart wird.
- 回译：这取决于数量和盒型。Arka 适合网上订购的小批量。Packrivo 从每款设计 250 件起。例如 500 件时，现货抽屉式首饰盒每盒工厂交货约 US$0.10–0.33 起，不含 Logo 印刷、运费和关税，另加 8–15% 的协调费，其计算基准以书面约定。

### 西班牙语 `es`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：Pedido mínimo de 250 unidades por diseño para cajas impresas. El stock sin imprimir se envía desde 100 unidades. Las tiradas de 100 a 249 se cotizan bajo petición.
- 回译：印刷盒每款设计起订量 250 件。未印刷现货从 100 件起发货。100 到 249 件的批量按需报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：Cuente las piezas que necesita por diseño. Para menos de 250, envíe el briefing y le cotizamos a mano; con 250 o más, compare cotizaciones de fábrica.
- 回译：按设计清点所需件数。少于 250 件时，发送简报，我们人工报价；250 件及以上，比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：Depende de la cantidad y del tipo de caja. Arka es adecuada para tiradas pequeñas pedidas en línea. Packrivo trabaja a partir de 250 unidades por diseño. Por ejemplo, con 500 unidades, las cajas de joyería tipo cajón en stock cuestan desde unos US$0.10–0.33 por caja en fábrica, antes de la impresión del logotipo, el transporte y los aranceles, más una comisión de coordinación del 8–15%, con la base de cálculo acordada por escrito.
- 回译：取决于数量和盒型。Arka 适合线上订购的小批量。Packrivo 从每款设计 250 件起。例如 500 件时，现货抽屉式首饰盒每盒出厂价约 US$0.10–0.33 起，不含 Logo 印刷、运输和关税，另加 8–15% 的协调费，计算基准以书面约定。

### 法语 `fr`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：Minimum de 250 pièces par modèle pour les boîtes imprimées. Le stock non imprimé est expédié à partir de 100 pièces. Les séries de 100–249 sont chiffrées sur demande.
- 回译：印刷盒每款设计起订量 250 件。未印刷现货从 100 件起发货。100–249 件的批量按需报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：Comptez les pièces nécessaires par modèle. En dessous de 250, envoyez le brief et nous chiffrons à la main ; à partir de 250, comparez les devis d’usine.
- 回译：请按款式清点所需件数。低于 250 件时，把需求简报发给我们，我们人工报价；250 件起，比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：Cela dépend de la quantité et du type de boîte. Arka convient aux petites séries commandées en ligne. Packrivo travaille à partir de 250 pièces par modèle. Par exemple, pour 500 pièces, les boîtes à bijoux à tiroir en stock commencent autour de US$0.10–0.33 par boîte départ usine, avant l’impression du logo, le transport et les droits, auxquels s’ajoutent des frais de coordination de 8–15 %, selon une base de calcul convenue par écrit.
- 回译：这取决于数量和盒型。Arka 适合线上订购的小批量。Packrivo 从每款 250 件起做。例如 500 件时，现货抽屉式首饰盒每盒出厂价约 US$0.10–0.33 起，不含 Logo 印刷、运输和关税，另加 8–15% 的协调费，计算基准以书面约定。

### 意大利语 `it`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：MOQ 250 pezzi per design per le scatole stampate. I modelli disponibili non stampati vengono spediti a partire da 100 pezzi. Le serie da 100–249 pezzi sono quotate su richiesta.
- 回译：印刷盒每款设计起订量 250 件。未印刷现货从 100 件起发货。100–249 件的批量按要求报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：Calcola i pezzi necessari per ogni design. Sotto 250, inviate il brief e vi quotiamo manualmente; da 250 in su, confronta i preventivi dei produttori.
- 回译：按设计清点所需件数。低于 250 件，发送需求简报，我们人工报价；250 件及以上，比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：Dipende dalla quantità e dal tipo di scatola. Arka è adatta a piccoli quantitativi ordinati online. Packrivo opera da 250 pezzi per design. Ad esempio, per 500 pezzi, le scatole a cassetto disponibili per gioielli partono da circa US$0.10–0.33 per scatola franco fabbrica, prima di stampa del logo, trasporto e dazi, oltre a una commissione di coordinamento dell'8–15%, con base di calcolo concordata per iscritto.
- 回译：取决于数量和盒型。Arka 适合线上订购的小批量。Packrivo 每款设计从 250 件起。例如 500 件时，现货抽屉式首饰盒每盒工厂交货价约 US$0.10–0.33 起，不含 Logo 印刷、运输和关税，另加 8–15% 的协调费，计算基准以书面约定。

### 日语 `ja`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：印刷入りボックスはデザインごとに最小250個です。印刷なしの在庫品は100個から出荷します。100–249個のロットはご要望に応じてお見積もりします。
- 回译：印刷盒每款设计起订量 250 个。未印刷现货从 100 个起发货。100–249 个的批量按需另行报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：デザインごとに必要な個数を数えてください。250個未満なら要件をお送りいただければ担当者が個別にお見積もりし、250個以上なら工場の見積もりを比較します。
- 回译：请按设计清点所需数量。不足 250 个时把需求发来，由专人人工报价；250 个以上则比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：数量と箱の種類によります。Arkaはオンラインで注文する小ロットに適しています。Packrivoではデザインごとに250個から対応します。たとえば500個の場合、在庫品の引き出し式ジュエリーボックスは1箱あたりUS$0.10–0.33程度からで、ロゴ印刷、運賃、関税は別途です。さらに調整手数料8–15%が加わります。計算基準は書面で合意します。
- 回译：取决于数量和盒型。Arka 适合线上下单的小批量。Packrivo 每款设计从 250 个起做。例如 500 个时，现货抽屉式首饰盒每盒约 US$0.10–0.33 起，不含 Logo 印刷、运费和关税，另加 8–15% 的手续费，计算基准以书面约定。

### 韩语 `ko`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：인쇄 상자는 디자인당 최소 250개입니다. 인쇄되지 않은 재고는 100개부터 출고됩니다. 100–249개 수량은 요청 시 견적합니다.
- 回译：印刷盒每款设计起订量 250 个。未印刷现货从 100 个起发货。100–249 个的数量按需单独报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：디자인별 필요한 수량을 세어 보세요. 250개 미만이면 브리프를 보내주시면 직접 견적해 드리고, 250개 이상이면 공장 견적을 비교하세요.
- 回译：请按设计清点所需数量。不足 250 个时把需求发来，我们人工报价；250 个以上则比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：수량과 상자 유형에 따라 다릅니다. Arka는 온라인으로 주문하는 소량 생산에 적합합니다. Packrivo는 디자인당 250개부터 대응합니다. 예를 들어 500개 기준으로 재고형 서랍식 주얼리 상자는 EXW 기준 상자당 약 US$0.10–0.33부터이며 로고 인쇄, 운임, 관세는 별도입니다. 여기에 조정 수수료 8–15%가 추가되고 계산 기준은 서면 합의합니다.
- 回译：取决于数量和盒型。Arka 适合在线下单的小批量。Packrivo 每款设计从 250 个起。例如 500 个基准下，现货抽屉式珠宝盒每盒约 US$0.10–0.33 起（工厂交货），Logo 印刷、运费和关税另计，另加 8–15% 的协调费，计算基准以书面约定。

### 荷兰语 `nl`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：MOQ 250 stuks per ontwerp voor bedrukte dozen. Onbedrukte voorraad wordt vanaf 100 stuks verzonden. Oplagen van 100–249 worden op aanvraag geprijsd.
- 回译：印刷盒每款设计起订量 250 件。未印刷现货从 100 件起发货。100–249 件的批量按需计价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：Tel hoeveel stuks u per ontwerp nodig hebt. Bij minder dan 250 stuurt u de briefing en maken we handmatig een prijsopgave; vanaf 250 vergelijkt u fabrieksoffertes.
- 回译：请按设计清点所需件数。少于 250 件时把简报发来，我们手工出价；250 件起比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：Dat hangt af van de hoeveelheid en het doosmodel. Arka is geschikt voor kleine oplagen die online worden besteld. Packrivo werkt vanaf 250 stuks per ontwerp. Bij 500 stuks beginnen standaard ladedozen voor sieraden bijvoorbeeld rond US$0.10–0.33 per doos af fabriek, vóór logobedrukking, vracht en invoerrechten, plus 8–15% coördinatiekosten, met de berekeningsbasis schriftelijk afgesproken.
- 回译：取决于数量和盒型。Arka 适合网上订购的小批量。Packrivo 从每款设计 250 件起。例如 500 件时，现货抽屉式首饰盒每盒出厂价约 US$0.10–0.33 起，不含 Logo 印刷、运费和进口关税，另加 8–15% 的协调费，计算基准以书面约定。

### 葡萄牙语 `pt`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：Pedido mínimo de 250 peças por design para caixas impressas. Itens em estoque sem impressão são enviados a partir de 100 peças. Tiragens de 100–249 são orçamentadas a pedido.
- 回译：印刷盒每款设计起订量 250 件。未印刷现货从 100 件起发货。100–249 件的批量按需报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：Conte as peças necessárias por design. Para menos de 250, envie o briefing e orçamentamos manualmente; a partir de 250, compare cotações de fábricas.
- 回译：按设计清点所需件数。少于 250 件时发送简报，我们人工报价；250 件及以上，比较工厂报价。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：Depende da quantidade e do tipo de caixa. A Arka atende pequenas tiragens encomendadas online. A Packrivo trabalha a partir de 250 peças por design. Por exemplo, para 500 peças, caixas tipo gaveta para joias em estoque custam cerca de US$0.10–0.33 por caixa ex-works, antes da impressão do logotipo, frete e impostos de importação, além de uma taxa de coordenação de 8–15%, com a base de cálculo acordada por escrito.
- 回译：取决于数量和盒型。Arka 适合线上下单的小批量。Packrivo 从每款设计 250 件起做。例如 500 件时，现货抽屉式首饰盒每盒出厂价约 US$0.10–0.33 起，不含 Logo 印刷、运费和进口税，另加 8–15% 的协调费，计算基准以书面约定。

### 简体中文 `zh-CN`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：印刷盒每款设计最低起订量为 250 件。无印现货从 100 件起发货。100–249 件的小批量按需单独报价。
- 回译：（中文即原文）三段对应：起订量 250 件 / 无印现货 100 件起 / 100–249 件按需报价。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：按设计统计所需件数。不到 250 件时，把需求发给我们人工报价；达到 250 件或以上时，比较工厂报价。
- 回译：（中文即原文）「不足 250 件 → 发需求人工报价；250 件以上 → 比较工厂报价」与英文一致。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：这取决于数量和纸盒类型。Arka 适合在线下单的小批量订单。Packrivo 从每款设计 250 件起订。以 500 件为例，现货抽屉式珠宝盒约为每盒 US$0.10–0.33（工厂交货价），不含 Logo 印刷、运费和关税，另加 8–15% 协调费，计算基准以书面约定为准。
- 回译：（中文即原文）四行仅品牌词不同：Arka / Packhelp / Packlane / PakFactory。

### 繁体中文 `zh-TW`

**K1** `MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.`

- 新值：印刷盒每款設計最低起訂量為 250 件。無印現貨從 100 件起發貨。100–249 件的訂單另行報價。
- 回译：（中文即原文）三段对应：起訂量 250 件 / 無印現貨 100 件起 / 100–249 件另行報價。

**K2** `Count the pieces you need per design. Under 250, send the brief for a manual quote; at 250 or more, compare factory quotations.`

- 新值：計算每款所需件數。少於 250 件時，把需求寄來由我們人工報價；250 件以上，比較工廠報價。
- 回译：（中文即原文）「少於 250 件 → 寄需求人工報價；250 件以上 → 比較工廠報價」與英文一致。

**K3–K6** `It depends on quantity and box type. <brand> …`（Arka 行）

- 新值：這取決於數量與盒型。Arka 適合在線上下單的小批量訂單。Packrivo 每款從 250 件起。以 500 件為例，現貨抽屜式珠寶盒每盒約 US$0.10–0.33，為工廠交貨價，尚未包含商標印刷、運費與關稅，另加 8–15% 協調費，並以書面議定計算依據。
- 回译：（中文即原文）四行僅品牌詞不同：Arka / Packhelp / Packlane / PakFactory。

## 二、第二部分：中文术语统一（zh-CN / zh-TW）

### 简体中文 `zh-CN`

| # | 桶 | 键（截断） | 旧值 | 新值 |
| --- | --- | --- | --- | --- |
| 1 | `block` | `Sourcing fee…` | 代采服务费 | 采购协调费 |
| 2 | `block` | `We coordinate sampling, production, inspecti…` | 我们与经过审核的供应商协调打样、生产、验货和运输，并按订单金额收取 8–15% 的代采服务费。 | 我们与经过审核的供应商协调打样、生产、验货和运输，并按订单金额收取 8–15% 的采购协调费。 |
| 3 | `text` | `Sourcing fee…` | 代采服务费 | 采购协调费 |
| 4 | `text` | `We coordinate sampling, production, inspecti…` | 我们与经过审核的供应商协调打样、生产、验货和运输，并按订单金额收取 8–15% 的代采服务费。 | 我们与经过审核的供应商协调打样、生产、验货和运输，并按订单金额收取 8–15% 的采购协调费。 |
| 5 | `text` | `Instant quotes include the sourcing fee at {…` | 即时报价按 {percent}% 计入采购协调费；最终价格以书面报价为准。 | 即时报价按 {percent}% 计入采购协调费；最终费率以书面报价为准。 |

### 繁体中文 `zh-TW`

| # | 桶 | 键（截断） | 旧值 | 新值 |
| --- | --- | --- | --- | --- |
| 1 | `block` | `Sourcing fee…` | 代採服務費 | 採購協調費 |
| 2 | `block` | `We coordinate sampling, production, inspecti…` | 我們與經過審核的供應商協調打樣、生產、驗貨和運輸，並按訂單金額收取 8–15% 的代採服務費。 | 我們與經過審核的供應商協調打樣、生產、驗貨和運輸，並按訂單金額收取 8–15% 的採購協調費。 |
| 3 | `text` | `Sourcing fee…` | 代採服務費 | 採購協調費 |
| 4 | `text` | `We coordinate sampling, production, inspecti…` | 我們與經過審核的供應商協調打樣、生產、驗貨和運輸，並按訂單金額收取 8–15% 的代採服務費。 | 我們與經過審核的供應商協調打樣、生產、驗貨和運輸，並按訂單金額收取 8–15% 的採購協調費。 |
| 5 | `text` | `Instant quotes include the sourcing fee at {…` | 即時報價已按 {percent}% 計入採購協調費；最終單價以書面報價為準。 | 即時報價已按 {percent}% 計入採購協調費；最終費率以書面報價為準。 |

"Sourcing fee" 标签 4 处（`block` + `text` 各 1 处，`Sourcing fee` 与 `We coordinate sampling…` 两句）统一为采购协调费 / 採購協調費；`Sourcing fee: {fee}` 原本已正确，未动。"final rate" 是费率不是单价，改为最终费率 / 最終費率。键名一律未改。

## 三、验证证据

### 3.1 门禁（原样输出，`python deploy/32a-i18n-gate.py`，退出码 0）

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

### 3.2 改动面与内容自检（`verify.py`，逐键比对 6c49b99 的 git blob）

```
ar: 变更值 6 | 键集合一致 True | 桶 {'title': 295, 'attr': 879, 'block': 7123, 'text': 426}
de: 变更值 6 | 键集合一致 True | 桶 {'title': 295, 'attr': 880, 'block': 7170, 'text': 477}
es: 变更值 6 | 键集合一致 True | 桶 {'title': 301, 'attr': 880, 'block': 7121, 'text': 469}
fr: 变更值 6 | 键集合一致 True | 桶 {'title': 301, 'attr': 880, 'block': 7118, 'text': 476}
it: 变更值 6 | 键集合一致 True | 桶 {'title': 301, 'attr': 880, 'block': 7120, 'text': 471}
ja: 变更值 6 | 键集合一致 True | 桶 {'title': 295, 'attr': 879, 'block': 7126, 'text': 426}
ko: 变更值 6 | 键集合一致 True | 桶 {'title': 295, 'attr': 880, 'block': 7128, 'text': 424}
nl: 变更值 6 | 键集合一致 True | 桶 {'title': 301, 'attr': 880, 'block': 7122, 'text': 475}
pt: 变更值 6 | 键集合一致 True | 桶 {'title': 301, 'attr': 880, 'block': 7129, 'text': 475}
zh-CN: 变更值 11 | 键集合一致 True | 桶 {'title': 295, 'attr': 881, 'block': 7128, 'text': 474}
zh-TW: 变更值 11 | 键集合一致 True | 桶 {'title': 295, 'attr': 879, 'block': 7126, 'text': 426}
  ar 竞品行归一一致: True
  de 竞品行归一一致: True
  es 竞品行归一一致: True
  fr 竞品行归一一致: True
  it 竞品行归一一致: True
  ja 竞品行归一一致: True
  ko 竞品行归一一致: True
  nl 竞品行归一一致: True
  pt 竞品行归一一致: True
  zh-CN 竞品行归一一致: True
  zh-TW 竞品行归一一致: True
---
VERIFY PASS bad=0
```

### 3.3 与基线的 diff

`git diff --stat 6c49b99 -- deploy/i18n-merged`

```
 deploy/i18n-merged/ar-dict.json    | 12 ++++++------
 deploy/i18n-merged/de-dict.json    | 12 ++++++------
 deploy/i18n-merged/es-dict.json    | 12 ++++++------
 deploy/i18n-merged/fr-dict.json    | 12 ++++++------
 deploy/i18n-merged/it-dict.json    | 12 ++++++------
 deploy/i18n-merged/ja-dict.json    | 12 ++++++------
 deploy/i18n-merged/ko-dict.json    | 12 ++++++------
 deploy/i18n-merged/nl-dict.json    | 12 ++++++------
 deploy/i18n-merged/pt-dict.json    | 12 ++++++------
 deploy/i18n-merged/zh-CN-dict.json | 22 +++++++++++-----------
 deploy/i18n-merged/zh-TW-dict.json | 22 +++++++++++-----------
 11 files changed, 76 insertions(+), 76 deletions(-)
```

`git diff --numstat 6c49b99 -- deploy/i18n-merged`

```
6	6	deploy/i18n-merged/ar-dict.json
6	6	deploy/i18n-merged/de-dict.json
6	6	deploy/i18n-merged/es-dict.json
6	6	deploy/i18n-merged/fr-dict.json
6	6	deploy/i18n-merged/it-dict.json
6	6	deploy/i18n-merged/ja-dict.json
6	6	deploy/i18n-merged/ko-dict.json
6	6	deploy/i18n-merged/nl-dict.json
6	6	deploy/i18n-merged/pt-dict.json
11	11	deploy/i18n-merged/zh-CN-dict.json
11	11	deploy/i18n-merged/zh-TW-dict.json
```

全仓库改动文件清单（`git diff --name-only 6c49b99`）：

```
deploy/i18n-merged/ar-dict.json
deploy/i18n-merged/de-dict.json
deploy/i18n-merged/es-dict.json
deploy/i18n-merged/fr-dict.json
deploy/i18n-merged/it-dict.json
deploy/i18n-merged/ja-dict.json
deploy/i18n-merged/ko-dict.json
deploy/i18n-merged/nl-dict.json
deploy/i18n-merged/pt-dict.json
deploy/i18n-merged/zh-CN-dict.json
deploy/i18n-merged/zh-TW-dict.json
```

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
?? .rehearsal-32a/
?? dsh-out/32a-i18n-fix-report.md
```

每语言变更值与插入/删除行数完全对应：9 语 6/6，zh-CN 与 zh-TW 11/11；除 11 个词典外无其他已跟踪文件改动（quote-i18n 与 csv 均未动；本报告为本轮新增）。

### 3.4 关键点逐条对照

| 要求 | 落点 | 证据 |
| --- | --- | --- |
| K1 补回第三句「100–249 按需报价」 | 11 语 | 独立键 `Runs of 100–249 are quoted on request.` 的既有译法原样拼入，未另造词 |
| K2 删「先用在线平台」改「发需求人工报价」 | 11 语 | 复用各语 `Under 250? Send the brief…` 的既有术语（如 ja 要件をお送りいただければ、ko 브리프를 보내주시면、zh-CN 把需求发来） |
| K3–K6 删「这个数量工厂单价下降」 | 11 语 | 旧句片段在各语新值中检索为 0（de `Stückpreise ab Werk sinken`、ja `工場の単価が下がります`、zh-CN `工厂单价会降低` 等） |
| K3–K6 补「以 500 件为例」 | 11 语 | 复用各语 `At 500 pieces,` 既有写法（de `Bei 500 Stück`、ja `500個の場合`、ko `500개 기준`、zh `以 500 件为例`） |
| 500 不得变成 250 | 11 语 | 各语新值同时含 250（起订）与 500（举例），校验脚本逐值断言 |
| 品牌词原样保留 | 11 语 | ar 句中嵌品牌、pt 带冠词 `A Arka` 等旧形态全部保留，仅品牌标识替换 |
| es 区间写法 | es | 沿用本语种既有写法 `100 a 249`（与同语独立键一致），其余语种为 `100–249` |
| 价格与费率原值 | 11 语 | `US$0.10–0.33` 与 `8–15%` 逐值断言存在且未被改写 |

## 四、对上一份报告的更正

`dsh-out/32a-i18n-report.md` 第 21 行的说法有误：那里写 zh-CN 复用了既有的「采购协调费」，实际旧词典 `block` / `text` 桶的 `Sourcing fee` 标签是「代采服务费」，`We coordinate sampling…` 一句里也是「代采服务费」，共 4 处；只有 `Sourcing fee: {fee}` 那句原本就是「采购协调费」。zh-TW 同理，旧值是「代採服務費」。本轮已把这 4 处改成采购协调费 / 採購協調費，与报价插件一致。

## 五、未改、供评审知悉

1. 四行竞品译文里收的是「协调费 / 協調費」（zh-CN / zh-TW）与各语 coordination fee 写法，本应与新的「采购协调费」同族。该处不在本轮列出条目内，按「只改列出的条目」未动；若要统一，需新增一轮指令。
2. es 语把英文区间改写为 `100 a 249`，是该语种词典里 `Runs of 100–249 are quoted on request.` 等键的既有写法，未改为 `100–249`。
3. `zh-TW` 的 `We coordinate sampling…` 把 freight 译作「運費」，与 `freight` 在本词典其他键的译法不一致，属既有问题，未动。
4. `zh-TW` 的 `**Basis for every price above:**` 键仍在用「最終單價」，与本轮改过的 `Instant quotes…` 一句口径不同，未动。

## 六、复现

```
cd E:\破甲工作区\packrivo-backup-32a
python deploy/32a-i18n-gate.py                      # GATE PASS
python -u E:\破甲工作区\.tmp\32a-fix\verify.py       # VERIFY PASS bad=0
git diff --numstat 6c49b99 -- deploy/i18n-merged
```

补丁与校验脚本：`E:\破甲工作区\.tmp\32a-fix\patch.py`（写入器）、`verify.py`（校验）、`report.py`（本报告生成）、`manifest.json`（76 处新旧值对照）。

## 七、提交

分支 `32a`，基线 `6c49b99`，提交信息 `wip: dsh 32a translation fixes (not yet reviewed)`，只提交 11 个词典与本报告；`.rehearsal-32a/` 保持未跟踪。待评审。
