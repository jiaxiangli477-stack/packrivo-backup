# -*- coding: utf-8 -*-
"""Fill lang/ja.json from the English skeleton, preserving keys byte-for-byte."""
import io, json, os, re, sys

BASE = r'E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-tools'
SKEL = r'E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-tools\lang\ja.json'

# ---------- SEG ----------
A_REQ = '見積もりをご依頼ください。'
A_MOQ = 'MOQ 250、サンプリング5〜10日。'
A_PROD = '承認後、生産7〜30日。'
A_DIEL = 'ダイラインは見積もりごとに添付します。'
A_DDP = 'DDPまたはFOB、両条件で見積もります。'
A_BOARD = '板紙、段ボール、貼り箱用素材に対応。'
A_REC = '再生原料の素材もご要望に応じて対応。'
A_DIELB = '生産前にダイラインを確認。'

SEG = {
 'Request a quote.': A_REQ,
 'MOQ 250, 5-10 day sampling.': A_MOQ,
 '7-30 day production after approval.': A_PROD,
 'Dieline with every quote.': A_DIEL,
 'DDP or FOB, quoted both ways.': A_DDP,
 'Board, corrugated or rigid stock.': A_BOARD,
 'Recycled-content stock on request.': A_REC,
 'Dieline before production.': A_DIELB,
 'Compare structures and materials.': '形状と材料を比較できます。',
 'MOQ and tooling compared.': 'MOQと型代を比較。',
 'No retainer before scope.': '範囲確定前の着手金は不要です。',
 '8-15% coordination fee on production only.': 'コーディネーション費8〜15%は生産分のみ。',
 'MOQ 250, 5-10 day sampling, 7-30 day production.': 'MOQ 250、サンプリング5〜10日、生産7〜30日。',
 'Sampling and production time.': 'サンプリングと生産の期間。',
 'What suppliers do with it.': 'サプライヤーがそれで何をするか。',
 'Common mistakes that add a week.': '納期を一週間延ばすよくあるミス。',
 'Open the sheet.': 'シートを開いてください。',
 'How quotes differ on landed cost.': '見積もりは着地コストでどう変わるか。',
 'What each one will not quote.': '各社が見積もらない条件。',
 'A blank worksheet you fill in.': '空欄を埋めるだけのワークシート。',
 'Custom jewelry boxes, pouches and gift packaging with your logo.': 'ロゴを入れたジュエリーボックス、ポーチ、ギフト包装のオーダーメイド。',
 'Custom packaging labels.': 'オリジナル包装ラベル。',
 'Custom gift boxes.': 'オリジナルギフトボックス。',
 'Custom jewelry boxes.': 'オリジナルジュエリーボックス。',
 'Custom packaging inserts.': 'オリジナル包装インサート。',
 'Custom mailer boxes.': 'オリジナルメーラーボックス。',
 'Custom jewelry pouches, gift bags and mailer bags with your logo.': 'ロゴを入れたジュエリーポーチ、ギフトバッグ、メーラーバッグのオーダーメイド。',
 'Packrivo is a custom packaging sourcing team, not a factory.': 'Packrivoはオリジナル包装の調達チームで、工場ではありません。',
 'Packaging for jewelry brands: rigid boxes, pouches, gift bags, mailers and inserts, printed with your logo.': 'ジュエリーブランド向け包装：貼り箱、ポーチ、ギフトバッグ、メーラー、インサートにロゴを印刷。',
 'MOQ 250 and a dieline with every quote.': 'MOQ 250、ダイラインは見積もりごとに添付。',
 'Custom molded pulp packaging.': 'オリジナルパルプモールド包装。',
 'Custom candle gift box.': 'オリジナルキャンドルギフトボックス。',
 'Custom clear display boxes.': 'オリジナルクリアディスプレイボックス。',
 'Custom collapsible magnetic gift box.': 'オリジナル折りたたみマグネット式ギフトボックス。',
 'Custom wine shipper box.': 'オリジナルワイン用発送箱。',
 'Custom drawer box with ribbon.': 'オリジナルリボン付きドロワーボックス。',
 'Custom kraft cosmetic boxes.': 'オリジナルクラフト化粧品ボックス。',
 'Recycled-content kraft stock on request.': '再生原料のクラフト素材もご要望に応じて対応。',
 'Custom gift bag and tissue paper.': 'オリジナルギフトバッグと薄葉紙。',
 'Custom jewelry pouch and box set.': 'オリジナルジュエリーポーチとボックスのセット。',
 'Custom kraft stand up pouch.': 'オリジナルクラフトスタンドパウチ。',
 'Custom magnetic closure gift boxes.': 'オリジナルマグネット式ギフトボックス。',
 'Custom printed insert cards.': 'オリジナル印刷インサートカード。',
 'Custom paper mailer bags.': 'オリジナル紙製宅配袋。',
 'Custom poly mailer bags.': 'オリジナルポリ宅配袋。',
 'Custom tissue paper.': 'オリジナル薄葉紙。',
 'Custom cardboard tube boxes.': 'オリジナル紙管ボックス。',
 'Custom packaging samples before you commit to a full production run.': '本生産の前にオリジナル包装のサンプルをご確認ください。',
 'Custom soap boxes.': 'オリジナル石けんボックス。',
 'Custom subscription boxes.': 'オリジナルサブスクリプションボックス。',
 'Custom jewelry pouches.': 'オリジナルジュエリーポーチ。',
 'Custom thermoformed insert trays.': 'オリジナル熱成形インサートトレー。',
 'Custom windowed folding cartons.': 'オリジナル窓付き折りたたみ化粧箱。',
 'Custom jewelry box manufacturers.': 'オリジナルジュエリーボックスの製造元。',
 'Sampling before you commit.': '発注前にサンプルで確認。',
 'Red flags in a spec sheet.': '仕様書の危険信号。',
 'Custom mailer box manufacturers.': 'オリジナルメーラーボックスの製造元。',
 'What to ask in writing before paying.': '支払い前に書面で確認すべきこと。',
 'Custom packaging cost.': 'オリジナル包装のコスト。',
 'What moves unit price most.': '単価を最も左右する要因。',
 'Tooling, sampling and freight split out.': '型代、サンプリング、運賃を分けて表示。',
 'How to hold a quote for 30 days.': '見積もりを30日間有効に保つ方法。',
 'Custom packaging moq.': 'オリジナル包装のMOQ。',
 'How to split a first order across SKUs.': '初回発注をSKUごとに分ける方法。',
 'Stock sizes that skip tooling.': '型代が不要な既存サイズ。',
 'What changes above 1,000 units.': '1,000個を超えると何が変わるか。',
 'Packaging samples: what a physical or digital proof costs and what it proves.': '包装サンプル：現物またはデジタルのプルーフの費用と、それが証明するもの。',
 'Packaging lead time: 5-10 days for samples, 7-30 days for production.': '包装のリードタイム：サンプル5〜10日、生産7〜30日。',
 '5-10 days for samples.': 'サンプルは5〜10日。',
 'Custom packaging suppliers.': 'オリジナル包装のサプライヤー。',
 'Side by side across five suppliers.': '五社を横並びで比較。',
 'No sales call needed.': '営業電話は不要です。',
 'Which one fits a 250-piece first run.': '250個の初回発注に合うのはどれか。',
 'Arka alternatives: order terms side by side.': 'Arkaの代替：発注条件を横並びで比較。',
 'Packhelp alternatives: order terms side by side.': 'Packhelpの代替：発注条件を横並びで比較。',
 'Packlane alternatives: order terms side by side.': 'Packlaneの代替：発注条件を横並びで比較。',
 'Pakfactory alternatives: order terms side by side.': 'Pakfactoryの代替：発注条件を横並びで比較。',
 'Custom packaging sourcing.': 'オリジナル包装の調達。',
 'Then a quote.': 'そのあとに見積もり。',
 'Custom packaging guide.': 'オリジナル包装ガイド。',
 'Custom packaging quote request.': 'オリジナル包装の見積もり依頼。',
 'Custom packaging finishes.': 'オリジナル包装の仕上げ。',
 'Custom jewelry insert fit.': 'ジュエリー用インサートの収まり。',
 'Send it with your spec.': '仕様と一緒にお送りください。',
 'Custom flat pack gift boxes.': 'オリジナル平折りギフトボックス。',
 'Custom mailer box rfq.': 'オリジナルメーラーボックスの見積依頼。',
 'Custom magnetic jewelry boxes.': 'オリジナルマグネット式ジュエリーボックス。',
 'Custom collapsible magnetic gift boxes.': 'オリジナル折りたたみマグネット式ギフトボックス。',
 'Custom five-panel corrugated folders.': 'オリジナル五面段ボールフォルダー。',
 'Custom chipboard dividers.': 'オリジナルチップボール中仕切り。',
 'Custom printed display boxes.': 'オリジナルディスプレイボックスの印刷。',
 'Custom shipping boxes.': 'オリジナル発送箱。',
 'Custom auto-lock bottom boxes.': 'オリジナル自動底組み化粧箱。',
 'Custom book-style rigid gift boxes.': 'オリジナルブック型貼り箱ギフトボックス。',
 'Custom bookend folding cartons.': 'オリジナルBookend型折りたたみ化粧箱。',
 'Custom fabric tote bags.': 'オリジナル布製トートバッグ。',
 'Custom coated labels.': 'オリジナルコーティングラベル。',
 'Custom corrugated book mailers.': 'オリジナル段ボールブックメーラー。',
 'Custom bottle gift boxes with handle.': 'オリジナル手提げ付きボトルギフトボックス。',
 'Custom corrugated dividers and inserts.': 'オリジナル段ボール中仕切りとインサート。',
 'Custom corrugated gusset folders.': 'オリジナル段ボールマチ付きフォルダー。',
 'Custom reverse tuck boxes.': 'オリジナル逆差し込み式化粧箱。',
 'Custom hinged lid boxes.': 'オリジナル開き蓋ボックス。',
 'Custom tuck top auto bottom boxes.': 'オリジナル差し込み蓋自動底ボックス。',
 'Custom snap bottom cartons.': 'オリジナルスナップ底化粧箱。',
 'Custom counter display boxes.': 'オリジナルカウンター陳列箱。',
 'Custom carry boxes with handle.': 'オリジナル手提げ付きキャリー箱。',
 'Custom die-cut handle paper bags.': 'オリジナル抜き型取っ手付き紙袋。',
 'Custom die-cut box inserts.': 'オリジナル型抜きインサート。',
 'Custom double-wall tray and lid sets.': 'オリジナル二重構造トレーと蓋のセット。',
 'Custom tray and sleeve boxes.': 'オリジナルトレー＆スリーブ化粧箱。',
 'Custom corrugated tray boxes with lid.': 'オリジナル蓋付き段ボールトレー箱。',
 'Custom drawer jewelry boxes.': 'オリジナルドロワー式ジュエリーボックス。',
 'Custom drawstring gift bags in bulk for jewelry and small gifts.': 'ジュエリーや小物向けのオリジナル巾着袋をまとめて。',
 'Custom paper gift bags.': 'オリジナル手提げ紙袋。',
 'Custom eva foam inserts.': 'オリジナルEVAフォームインサート。',
 'Custom dispenser boxes.': 'オリジナルディスペンサー箱。',
 'Custom petal top gift boxes.': 'オリジナル花びら蓋ギフトボックス。',
 'Custom foldable lid and base boxes.': 'オリジナル折りたたみ蓋付きベース箱。',
 'Custom folded box liners.': 'オリジナル折り加工の箱用ライナー。',
 'Custom folded tray and sleeve boxes.': 'オリジナル折りトレー＆スリーブ箱。',
 'Custom folding cartons wholesale.': 'オリジナル化粧箱の卸売。',
 'Custom display trays for retail.': '小売向けオリジナル陳列トレー。',
 'Custom pre-glued boxes bulk.': 'オリジナル糊付け済み化粧箱をまとめて。',
 'Custom full flap auto bottom boxes.': 'オリジナルフルフラップ自動底箱。',
 'Custom seal end cartons wholesale.': 'オリジナルシールエンド箱の卸売。',
 'Custom full overlap shipping boxes.': 'オリジナルフルオーバーラップ発送箱。',
 'Custom gable boxes with handle.': 'オリジナル手提げ付きギャブル箱。',
 'Custom gusset folder boxes.': 'オリジナルマチ付きフォルダー箱。',
 'Custom hanging retail boxes.': 'オリジナル吊り下げ店頭ボックス。',
 'Custom header card bag toppers.': 'オリジナルヘッダーカード袋用トッパー。',
 'Custom hexagonal gift boxes.': 'オリジナル六角形ギフトボックス。',
 'Custom hinged rigid gift boxes.': 'オリジナル開き蓋貼り箱ギフトボックス。',
 'Custom printed info cards.': 'オリジナル印刷インフォカード。',
 'Custom jewelry boxes with fitted inserts.': 'オリジナル専用インサート付きジュエリーボックス。',
 'Custom lid-and-base jewelry boxes.': 'オリジナル蓋と台のジュエリーボックス。',
 'Custom lock cap auto bottom boxes.': 'オリジナルロックキャップ自動底箱。',
 'Custom mailer boxes with inserts.': 'オリジナルインサート付きメーラーボックス。',
 'Custom pe foam packaging inserts.': 'オリジナルPEフォーム包装インサート。',
 'Custom corrugated end caps.': 'オリジナル段ボールエンドキャップ。',
 'Custom printed belly bands.': 'オリジナル印刷帯紙。',
 'Custom flat paper merchandise bags.': 'オリジナル平型紙製手提げ袋。',
 'Custom padded paper mailers.': 'オリジナル緩衝付き紙製メーラー。',
 'Custom grid dividers for boxes.': 'オリジナル格子中仕切り。',
 'Custom paperboard inserts.': 'オリジナル板紙インサート。',
 'Custom dust flap cartons.': 'オリジナルダストフラップ箱。',
 'Custom peg hook counter displays.': 'オリジナルペグフック式カウンター陳列。',
 'Custom pillow jewelry boxes.': 'オリジナル枕付きジュエリーボックス。',
 'Custom pinch lock trays.': 'オリジナルピンチロックトレー。',
 'Custom poly bubble mailers.': 'オリジナルポリバブルメーラー。',
 'Custom pu foam inserts.': 'オリジナルPUフォームインサート。',
 'Custom printed tissue sheets.': 'オリジナル印刷済み薄葉紙シート。',
 'Custom rectangle roll labels.': 'オリジナル長方形ロールラベル。',
 'Custom regular slotted shipping cartons.': 'オリジナル標準スロッテッド発送箱。',
 'Custom returnable parcel bags.': 'オリジナル返却可能な宅配袋。',
 'Custom returnable poly mailers.': 'オリジナル返却可能なポリメーラー。',
 'Custom reverse tuck retail boxes.': 'オリジナル逆差し込み式店頭ボックス。',
 'Custom rigid postal envelopes.': 'オリジナル貼り箱封筒。',
 'Custom self-locking mailers.': 'オリジナルセルフロックメーラー。',
 'Custom printed sheet labels.': 'オリジナル印刷シートラベル。',
 'Custom tiered counter displays.': 'オリジナル段付きカウンター陳列。',
 'Custom document window pouches.': 'オリジナル書類窓付きポーチ。',
 'Custom shoulder neck rigid boxes.': 'オリジナルショルダーネック貼り箱。',
 'Custom side lock display boxes.': 'オリジナルサイドロック陳列箱。',
 'Custom hinged gift trays.': 'オリジナル開き蓋ギフトトレー。',
 'Custom six-corner pre-glued boxes.': 'オリジナル六隅糊付け済み箱。',
 'Custom snap bottom shipping boxes.': 'オリジナルスナップ底発送箱。',
 'Custom snap lock bottom boxes.': 'オリジナルスナップロック底箱。',
 'Custom sos paper bags bulk.': 'オリジナルSOS紙袋をまとめて。',
 'Custom straight tuck boxes.': 'オリジナルストレート差し込み式箱。',
 'Custom tab lock corrugated packs.': 'オリジナルタブロック段ボールパック。',
 'Custom three flap lock boxes.': 'オリジナル三フラップロック箱。',
 'Custom tuck end sleeve boxes.': 'オリジナル差し込み端スリーブ箱。',
 'Custom two-door rigid boxes.': 'オリジナル観音開き貼り箱。',
 'Custom window lid rigid boxes.': 'オリジナル天面窓貼り箱。',
 'Custom zippered fabric pouches for jewelry and gifts.': 'ジュエリーやギフト向けのオリジナルファスナー付き布ポーチ。',
}

# ---------- H1 ----------
H1_FIXED = {
 'Custom packaging for jewelry, candles and gifts': 'ジュエリー、キャンドル、ギフト向け オリジナル包装',
 'Custom Mailer Boxes for small brands': '小規模ブランド向け オリジナル メーラーボックス',
 'Custom Bags with Logo for small brands': '小規模ブランド向け ロゴ入りバッグ',
 'Who you are actually working with': '実際に仕事をする相手',
 'Every structure we can source': '手配可能なすべての形状',
 'Molded Pulp Packaging': 'パルプモールド包装',
 'Candle Gift Box': 'キャンドル ギフトボックス',
 'Clear Display Boxes': 'クリア ディスプレイボックス',
 'Wine Shipper Box': 'ワイン用 発送箱',
 'Drawer Box With Ribbon': 'リボン付き ドロワーボックス',
 'Custom Kraft Cosmetic Boxes': 'オリジナル クラフト 化粧品ボックス',
 'Gift Bag and Tissue Paper': 'ギフトバッグと薄葉紙',
 'Kraft Stand Up Pouch': 'クラフト スタンドパウチ',
 'Magnetic Closure Gift Boxes': 'マグネット式 ギフトボックス',
 'Printed Insert Cards': '印刷済み インサートカード',
 'Paper Mailer Bags': '紙製 宅配袋',
 'Poly Mailer Bags': 'ポリ 宅配袋',
 'Custom Tissue Paper': 'オリジナル 薄葉紙',
 'Cardboard Tube Boxes': '紙管ボックス',
 'Custom Packaging Samples': 'オリジナル包装 サンプル',
 'Custom Soap Boxes': 'オリジナル 石けんボックス',
 'Custom Subscription Boxes': 'オリジナル サブスクリプションボックス',
 'Custom Jewelry Pouches': 'オリジナル ジュエリーポーチ',
 'Thermoformed Insert Trays': '熱成形 インサートトレー',
 'Windowed Folding Cartons': '窓付き 折りたたみ化粧箱',
 'How to choose a jewelry box manufacturer, and what to ask': 'ジュエリーボックス製造元の選び方と確認事項',
 'How to choose a mailer box manufacturer, and what to ask': 'メーラーボックス製造元の選び方と確認事項',
 'What packaging costs, and why': '包装コストの内訳と理由',
 'Packaging MOQ: how 250-piece runs work': '包装のMOQ：250個からの生産の考え方',
 'What a packaging sample costs and proves': '包装サンプルの費用と証明できること',
 'Packaging lead time, step by step': '包装のリードタイムを順を追って',
 'Custom packaging suppliers, compared': 'オリジナル包装サプライヤーの比較',
 'Arka alternatives, and Packrivo side by side': 'Arkaの代替とPackrivoを横並びで比較',
 'Packhelp alternatives, and Packrivo side by side': 'Packhelpの代替とPackrivoを横並びで比較',
 'Packlane alternatives, and Packrivo side by side': 'Packlaneの代替とPackrivoを横並びで比較',
 'PakFactory alternatives, and Packrivo side by side': 'PakFactoryの代替とPackrivoを横並びで比較',
 'Packaging guides and buyer worksheets': '包装ガイドと購買担当のワークシート',
 'Packaging Quote Request': '包装の見積もり依頼',
 'Custom Packaging Examples': 'オリジナル包装の事例',
 'Packaging Finishes': '包装の仕上げ',
 'Flat Pack Gift Boxes: a working sheet': '平折りギフトボックス：実務ワークシート',
 'Mailer Box RFQ: a working sheet': 'メーラーボックスの見積依頼：実務ワークシート',
}

# base product phrase (without the ", dieline first" suffix) -> Japanese
PN = {
 'Collapsible Magnetic Gift Boxes': '折りたたみ マグネット式ギフトボックス',
 'Five-panel Corrugated Folders': '五面 段ボールフォルダー',
 'Chipboard Dividers': 'チップボール 中仕切り',
 'Printed Display Boxes': 'ディスプレイボックスの印刷',
 'Custom Shipping Boxes': 'オリジナル 発送箱',
 'Auto-lock Bottom Boxes': '自動底組み 化粧箱',
 'Book-style Rigid Gift Boxes': 'ブック型 貼り箱',
 'Bookend Folding Cartons': 'Bookend型 折りたたみ化粧箱',
 'Fabric Tote Bags': '布製トートバッグ',
 'Coated Labels': 'コーティングラベル',
 'Corrugated Book Mailers': '段ボール ブックメーラー',
 'Bottle Gift Boxes with Handle': '手提げ付き ボトルギフトボックス',
 'Corrugated Dividers and Inserts': '段ボール 中仕切り インサート',
 'Corrugated Gusset Folders': '段ボール マチ付きフォルダー',
 'Reverse Tuck Boxes': '逆差し込み式 化粧箱',
 'Hinged Lid Boxes': '開き蓋 段ボール箱',
 'Tuck Top Auto Bottom Boxes': '差し込み蓋 自動底 化粧箱',
 'Snap Bottom Cartons': 'スナップ底 化粧箱',
 'Counter Display Boxes': 'カウンター陳列箱',
 'Carry Boxes with Handle': '手提げ付き キャリー箱',
 'Die-cut Handle Paper Bags': '抜き型取っ手 紙袋',
 'Die-cut Box Inserts': '型抜き インサート',
 'Double-wall Tray and Lid Sets': '二重構造 トレー 蓋セット',
 'Tray and Sleeve Boxes': 'トレー スリーブ 化粧箱',
 'Corrugated Tray Boxes with Lid': '蓋付き 段ボールトレー箱',
 'Drawer Jewelry Boxes': 'ドロワー式 ジュエリーボックス',
 'Drawstring Gift Bags Bulk': '巾着袋 ロット対応',
 'Custom Paper Gift Bags': 'オリジナル 手提げ紙袋',
 'Eva Foam Inserts': 'EVAフォーム インサート',
 'Dispenser Boxes': 'ディスペンサー箱',
 'Petal Top Gift Boxes': '花びら蓋 ギフトボックス',
 'Foldable Lid and Base Boxes': '折りたたみ 蓋付きベース箱',
 'Folded Box Liners': '折り加工 箱用ライナー',
 'Folded Tray and Sleeve Boxes': '折りトレー スリーブ箱',
 'Folding Cartons Wholesale': '化粧箱 卸売',
 'Display Trays for Retail': '陳列トレー',
 'Pre-glued Boxes Bulk': '糊付け済み 化粧箱 ロット対応',
 'Full Flap Auto Bottom Boxes': 'フルフラップ 自動底 箱',
 'Seal End Cartons Wholesale': 'シールエンド箱 卸売',
 'Full Overlap Shipping Boxes': 'フルオーバーラップ 発送箱',
 'Gable Boxes with Handle': '手提げ付き ギャブル箱',
 'Gusset Folder Boxes': 'マチ付き フォルダー箱',
 'Hanging Retail Boxes': '吊り下げ 店頭箱',
 'Header Card Bag Toppers': 'ヘッダーカード 袋用トッパー',
 'Hexagonal Gift Boxes': '六角形 ギフトボックス',
 'Hinged Rigid Gift Boxes': '開き蓋 貼り箱',
 'Printed Info Cards': '印刷 インフォカード',
 'Jewelry Boxes with Fitted Inserts': '専用インサート付き ジュエリーボックス',
 'Lid-and-base Jewelry Boxes': '蓋と台の ジュエリーボックス',
 'Lock Cap Auto Bottom Boxes': 'ロックキャップ 自動底 箱',
 'Mailer Boxes with Inserts': 'インサート付き メーラーボックス',
 'Pe Foam Packaging Inserts': 'PEフォーム 包装インサート',
 'Corrugated End Caps': '段ボール エンドキャップ',
 'Printed Belly Bands': '印刷 帯紙',
 'Flat Paper Merchandise Bags': '平型 紙製手提げ袋',
 'Padded Paper Mailers': '緩衝付き 紙製メーラー',
 'Grid Dividers for Boxes': '格子 中仕切り',
 'Paperboard Inserts': '板紙 インサート',
 'Dust Flap Cartons': 'ダストフラップ 箱',
 'Peg Hook Counter Displays': 'ペグフック式 カウンター陳列',
 'Pillow Jewelry Boxes': '枕付き ジュエリーボックス',
 'Pinch Lock Trays': 'ピンチロック トレー',
 'Poly Bubble Mailers': 'ポリ バブルメーラー',
 'Pu Foam Inserts': 'PUフォーム インサート',
 'Printed Tissue Sheets': '印刷済み 薄葉紙シート',
 'Rectangle Roll Labels': '長方形 ロールラベル',
 'Regular Slotted Shipping Cartons': '標準スロッテッド 発送箱',
 'Returnable Parcel Bags': '返却可能な 宅配袋',
 'Returnable Poly Mailers': '返却可能な ポリメーラー',
 'Reverse Tuck Retail Boxes': '逆差し込み式 店頭箱',
 'Rigid Postal Envelopes': '貼り箱 封筒',
 'Self-locking Mailers': 'セルフロック メーラー',
 'Printed Sheet Labels': '印刷 シートラベル',
 'Tiered Counter Displays': '段付き カウンター陳列',
 'Document Window Pouches': '書類窓付き ポーチ',
 'Shoulder Neck Rigid Boxes': 'ショルダーネック 貼り箱',
 'Side Lock Display Boxes': 'サイドロック 陳列箱',
 'Hinged Gift Trays': '開き蓋 ギフトトレー',
 'Six-corner Pre-glued Boxes': '六隅 糊付け済み箱',
 'Snap Bottom Shipping Boxes': 'スナップ底 発送箱',
 'Snap Lock Bottom Boxes': 'スナップロック底 箱',
 'Sos Paper Bags Bulk': 'SOS紙袋 ロット対応',
 'Straight Tuck Boxes': 'ストレート差し込み式 箱',
 'Tab Lock Corrugated Packs': 'タブロック 段ボールパック',
 'Three Flap Lock Boxes': '三フラップロック 箱',
 'Tuck End Sleeve Boxes': '差し込み端 スリーブ箱',
 'Two-door Rigid Boxes': '観音開き 貼り箱',
 'Window Lid Rigid Boxes': '天面窓 貼り箱',
 'Zippered Fabric Pouches': 'ファスナー付き 布ポーチ',
}

H1 = dict(H1_FIXED)
SUF = ', dieline first'
for base, ja in PN.items():
    H1[base + SUF] = ja + '、ダイライン先行'

# ---------- TITLE (body only; " | Packrivo" appended) ----------
TITLE = {
 'Custom Mailer Boxes: Printed & Shipping Ready': 'オリジナル メーラーボックス 印刷・発送対応',
 'Custom Bags with Logo: Paper, Cotton & Pouches': 'ロゴ入りバッグ：紙・コットン・ポーチ',
 'Custom Packaging Supplier: Who You Work With': 'オリジナル包装サプライヤー：取引相手',
 'Molded Pulp Packaging: Inserts and Trays': 'パルプモールド包装：インサートとトレー',
 'Candle Gift Box Sets With Inserts': 'インサート付き キャンドルギフトボックス',
 'Clear Display Boxes for Retail': '店頭向け クリアディスプレイボックス',
 'Collapsible Magnetic Gift Boxes': 'オリジナル 折りたたみ マグネット式ギフトボックス',
 'Wine Shipper Box, Corrugated (MOQ 250)': '段ボール製 ワイン発送箱（MOQ 250）',
 'Drawer Box With Ribbon Pull': 'リボン引き手付き ドロワーボックス',
 'Custom Kraft Cosmetic Boxes (MOQ 250)': 'オリジナル クラフト化粧品ボックス（MOQ 250）',
 'Gift Bag and Tissue Paper Sets': 'ギフトバッグと薄葉紙のセット',
 'Jewelry Pouch and Box Sets': 'ジュエリーポーチとボックスのセット',
 'Kraft Stand-Up Pouches with Window': '窓付き クラフトスタンドパウチ',
 'Magnetic Closure Gift Boxes, Book-Style': 'ブック型 マグネット式ギフトボックス',
 'Printed Insert Cards & Thank-You Cards': '印刷インサートカードとサンキューカード',
 'Printed Paper Mailer Bags': '印刷済み 紙製宅配袋',
 'Poly Mailer Bags, Printed to Order': 'ポリ宅配袋 受注印刷',
 'Custom Tissue Paper Printing (MOQ 250)': 'オリジナル薄葉紙の印刷（MOQ 250）',
 'Round Cardboard Tube Boxes': '丸型 紙管ボックス',
 'Custom Soap Boxes with Window (MOQ 250)': '窓付き オリジナル石けんボックス（MOQ 250）',
 'Custom Subscription Boxes & Mailers': 'オリジナル サブスクボックスとメーラー',
 'Custom Jewelry Pouches, Textile': 'オリジナル 布製ジュエリーポーチ',
 'Thermoformed Insert Trays': '熱成形 インサートトレー',
 'Windowed Folding Cartons': '窓付き 折りたたみ化粧箱',
 'Find a Jewelry Box Manufacturer: Costs & Vetting': 'ジュエリーボックス製造元：コストと選定',
 'Find a Mailer Box Manufacturer: Costs & Vetting': 'メーラーボックス製造元：コストと選定',
 'Packaging Cost: What Drives the Price': '包装コスト：価格を決める要因',
 'Packaging MOQ: How 250-Piece Runs Work': '包装のMOQ：250個ロットの進め方',
 'Packaging Samples: Cost and Timing': '包装サンプル：費用と期間',
 'Packaging Lead Time: 5-30 Days Explained': '包装リードタイム：5〜30日の内訳',
 'Custom Packaging Suppliers Compared': 'オリジナル包装サプライヤー比較',
 'Arka Alternatives: Packaging Compared': 'Arkaの代替：包装の比較',
 'Packhelp Alternatives: Packaging Compared': 'Packhelpの代替：包装の比較',
 'Packlane Alternatives: Packaging Compared': 'Packlaneの代替：包装の比較',
 'PakFactory Alternatives: Packaging Compared': 'PakFactoryの代替：包装の比較',
 'Packaging Quote Request: Custom Boxes': '包装の見積もり依頼：オリジナル化粧箱',
 'Custom Packaging Examples: Concepts': 'オリジナル包装の事例：コンセプト',
 'Packaging Finishes: Colors and Materials': '包装の仕上げ：色と素材',
 'Flat Pack Gift Boxes: Working Sheet': '平折りギフトボックス：実務シート',
 'Mailer Box RFQ: Working Sheet': 'メーラーボックス見積依頼：実務シート',
 'Custom Five-panel Corrugated Folders': 'オリジナル 五面 段ボールフォルダー',
 'Custom Shipping Boxes': 'オリジナル 発送箱',
 'Custom Auto-lock Bottom Boxes': 'オリジナル 自動底組み 化粧箱',
 'Custom Book-style Rigid Gift Boxes': 'オリジナル ブック型 貼り箱',
 'Custom Fabric Tote Bags': 'オリジナル 布製トートバッグ',
 'Custom Coated Labels': 'オリジナル コーティングラベル',
 'Custom Hinged Lid Boxes': 'オリジナル 開き蓋ボックス',
 'Custom Die-cut Handle Paper Bags': 'オリジナル 抜き型取っ手 紙袋',
 'Custom Die-cut Box Inserts': 'オリジナル 型抜きインサート',
 'Custom Double-wall Tray and Lid Sets': 'オリジナル 二重構造 トレー蓋セット',
 'Custom Drawer Jewelry Boxes': 'オリジナル ドロワー式ジュエリーボックス',
 'Custom Paper Gift Bags': 'オリジナル 手提げ紙袋',
 'Custom Eva Foam Inserts': 'オリジナル EVAフォーム インサート',
 'Custom Dispenser Boxes': 'オリジナル ディスペンサー箱',
 'Custom Pre-glued Boxes Bulk': 'オリジナル 糊付け済み化粧箱 ロット',
 'Custom Lid-and-base Jewelry Boxes': 'オリジナル 蓋と台のジュエリーボックス',
 'Custom Pe Foam Packaging Inserts': 'オリジナル PEフォーム包装インサート',
 'Custom Pu Foam Inserts': 'オリジナル PUフォーム インサート',
 'Custom Printed Tissue Sheets': 'オリジナル 印刷済み薄葉紙シート',
 'Custom Regular Slotted Shipping Cartons': 'オリジナル 標準スロッテッド発送箱',
 'Custom Self-locking Mailers': 'オリジナル セルフロックメーラー',
 'Custom Six-corner Pre-glued Boxes': 'オリジナル 六隅糊付け済み箱',
 'Custom Sos Paper Bags Bulk': 'オリジナル SOS紙袋 ロット対応',
 'Custom Two-door Rigid Boxes': 'オリジナル 観音開き 貼り箱',
 'Get a Custom Packaging Quote': 'オリジナル包装の見積もりを依頼',
}

# ---------- OTHER ----------
OTHER = {
 'Get a packaging quote': '包装の見積もりを依頼',
 'Custom packaging quote. DDP or FOB, quoted both ways. Dieline before production. MOQ 250, 5-10 day sampling. Request a quote.':
     'オリジナル包装の見積もり。' + A_DDP + A_DIELB + A_MOQ + A_REQ,
}

MAPS = {'SEG': SEG, 'H1': H1, 'TITLE': TITLE, 'OTHER': OTHER}

SUFFIX = ' | Packrivo'
skel = json.load(io.open(SKEL, encoding='utf-8'))
problems = []
for sec, mp in skel.items():
    have = MAPS[sec]
    if sec == 'TITLE':
        mp = {k[:-len(SUFFIX)]: v for k, v in mp.items()}
    missing = [k for k in mp if k not in have]
    extra = [k for k in have if k not in mp]
    if missing:
        problems.append('%s missing %d: %r' % (sec, len(missing), missing[:6]))
    if extra:
        problems.append('%s extra %d: %r' % (sec, len(extra), extra[:6]))
if problems:
    print('KEY PROBLEMS')
    for p in problems:
        print(' ', p)
    sys.exit(2)

NUM = re.compile(r'\d+(?:[.,]\d+)?')
for sec, mp in skel.items():
    have = MAPS[sec]
    for full in mp:
        k = full[:-len(SUFFIX)] if sec == 'TITLE' else full
        v = have[k]
        if sec == 'TITLE':
            v = v + SUFFIX
        if NUM.findall(k) != NUM.findall(v):
            print('NUM MISMATCH', sec, k, '->', v)
        if v.strip() != v:
            print('SPACE', sec, k)
        if v == k:
            print('UNTRANSLATED', sec, k)
        for tok in ('MOQ', 'DDP', 'FOB', 'Packrivo'):
            if tok in k and tok not in v:
                print('TOKEN', tok, sec, k, '->', v)

out = {}
for sec, mp in skel.items():
    out[sec] = {}
    for full in mp:
        k = full[:-len(SUFFIX)] if sec == 'TITLE' else full
        v = MAPS[sec][k]
        if sec == 'TITLE':
            v = v + SUFFIX
        out[sec][full] = v

with io.open(SKEL, 'w', encoding='utf-8', newline='\n') as f:
    json.dump(out, f, ensure_ascii=False, indent=2)
    f.write('\n')
print('written', SKEL, 'sections', {k: len(v) for k, v in out.items()})
