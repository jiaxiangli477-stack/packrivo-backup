# dsh-out 交付说明 · 2026-10-27f

对象：`full-inventory.csv` 里 `status = unchanged` 的 130 个产品页，改写英文 title / H1 / description，并补 11 语种译文；另附 27c 遗留的 60 条界面字符串译文。

只写了内容文件。未导入 WordPress、未连数据库、未提交 git、未碰服务器，也未修改任何已有文件。

## 一、产出清单

| 文件 | 条数 | 字节 | 内容 |
|---|---|---|---|
| `batch-01.json` | 26 | 96,091 | `clear-display-box` → `product-die-cut-handle-paper-bag` |
| `batch-02.json` | 26 | 93,403 | `product-die-cut-insert` → `product-full-overlap-seal-end-carton` |
| `batch-03.json` | 26 | 91,898 | `product-full-overlap-slotted-outer-carton` → `product-paper-carton-sealing-tape` |
| `batch-04.json` | 26 | 91,003 | `product-paper-merchandise-sleeve-bag` → `product-sheet-labels` |
| `batch-05.json` | 26 | 91,561 | `product-shelf-counter-display` → `windowed-folding-carton` |
| `ui-strings.json` | 60 × 11 语种 | 38,023 | 27c「字典与待补译清单」全部反引号字符串 |

合计 130 条，批次切分严格按 CSV 中 `unchanged` 行的原顺序（第 1–26 行 → batch-01，依此类推），顺序已逐条与 CSV 比对通过。

`i18n` 键名：`ar de es fr it ja ko nl pt zh-CN zh-TW`，每条 11 个语种齐全，每个语种都有 `title` / `h1` / `description`。

## 二、英文母本口径

- 长度：title 33–51 字符（上限 60），description 81–117 字符（上限 155）。
- 唯一性：130 条 title 互不重复，也不与已完成的 4 个首饰盒页面重复。
- 修饰词分布：`custom` 97、`with logo` 12、`printed` 12、`bulk` 6、`wholesale` 3。同类产品用不同修饰词或不同结构特征（`with Window`、`Kraft`、`Hinged`、`Auto Bottom`、`Die-Cut` 等）区分。
- 产品名改成买家的叫法，例如：
  - `Product Auto Bottom Display Carton` → `Custom Printed Display Boxes`
  - `Regular Slotted Shipping Carton` → `Custom Shipping Boxes with Logo`
  - `Custom Paper Gift Carrier` → `Custom Paper Gift Bags with Logo`
  - `Custom Through-Cut EVA Insert` → `Custom EVA Foam Inserts`
- 全部 description 以 `Get a price.` 收尾；不含价格数字、交期天数、认证、环保百分比、`our factory`、案例或评价。
- 禁用词扫描（`sourcing`、`supplier review`、`near me`、城市名、`1688`、`alibaba`、`diy`、`for women`、`for girls`、`E-flute`、`SBS`、`GSM`、`Delta E`、`ISTA`、`dieline`、`mm` 规格）在 130 条英文里 0 命中。

## 三、翻译口径

- 不是直译，用当地买家搜索词：德语 `Schmuckschachteln` / `Versandkartons mit Logo` / `ab 500 Stück` / `Preis anfragen`；法语 `boîtes à bijoux personnalisées` / `dès 500 pièces` / `Demandez un prix`；意语 `scatole per gioielli personalizzate` / `da 500 pezzi`；荷语 `sieradendoos op maat` / `vanaf 500 stuks`；西语 `cajas para joyería personalizadas` / `desde 500 unidades`；葡语 `caixas para joias personalizadas` / `a partir de 500 unidades`；日语 `オリジナル` / `名入れ` / `化粧箱` / `500個から`；韩语 `주얼리 박스 제작` / `로고 인쇄` / `500개부터`；阿语 `علب مجوهرات مخصصة` / `بحد أدنى 500 قطعة`；中文 `定制首饰盒` / `500 件起订` / `获取报价`。
- 每个语种 title 都以 ` | Packrivo` 结尾，品牌名不翻译；各语种 title 最长：de 60、pt 60、it 59、es 57、fr 53、nl 49、ar 48、ja 36、ko 31、zh-CN 27、zh-TW 27。
- 界面字符串的花括号变量（`{price}`、`{N}`、`{q}`、`{dimensions}`、`{freePrintSetupFrom}`）与 ` · `、`→`、`“ ”` 全部原样保留，已逐键比对；英文键本身含 `dieline` / `sourcing` 的 4 条（`Dielines included`、`…dieline review.`、`…sourcing custom packaging.`、`We confirm the dieline…`）按原文保留键，译文用各语种说法（刀模 / 抜き型データ / 칼선 / gabarit / stansmal / molde de corte 等）。
- 27e 的 4 处改动已按新写法翻译：`Ring & necklace boxes / Rigid, magnetic, drawer`、`Jewelry pouches / Velvet, cotton, suede`、`Gift boxes / Lid-and-base, window`、`Mailer boxes / Printed inside and out`、`From · 500 pcs`、`Live prices in the quote builder`。

## 四、拿不准的页面

1. **16 条内衬/隔板类英文描述没写 logo**：`product-corrugated-divider`、`product-corrugated-inlay`、`product-die-cut-insert`、`product-eva-foam-holder`、`product-flocked-foam-holder`、`product-folded-box-liner`、`product-molded-pulp-corner-cradle`、`product-molded-pulp-end-support-pair`、`product-multi-piece-pe-foam-support`、`product-nest-stack-thermoformed-tray`、`product-paperboard-grid-divider`、`product-paperboard-insert`、`product-polyurethane-foam-holder`、`product-rigid-chipboard-divider`、`product-thermoformed-multi-cavity-tray`、`thermoformed-insert-tray`。原因：这类是纯结构件，按「只写真实信息」不虚构印刷能力，改成 `made to your layout / shaped to your item` + `from 500 pieces`。若站长确认这类也能印 logo，加一句 `printed with your logo` 即可，各语种对应位置已留好。

2. **「深圳发货」与「禁用城市名」两条规则互相冲突**：任务单的 description 结构允许写「深圳发货」，禁用词一节又写「`near me`、城市名」。英文母本统一**不写城市**，只保留 MOQ 500 / 可印 logo / 可打样这类买家关心点；德语首版译文曾写成 `Versand aus Shenzhen`，已按禁用词规则清掉。若要走「深圳发货」这一版，改 `master-en.json` 后重跑一次生成脚本即可，11 语种会一起更新。

3. **`product-front-tuck-mailer` 与 `product-printed-mailer` 的主词边界**：前者写 `Front Tuck Mailer Boxes`，后者写 `Printed Mailer Boxes`，两者都落在 `mailer boxes` 词根上，靠结构特征区分。若站长希望这两页分别承接 `mailer boxes` 和 `printed mailer boxes` 两个不同意图，建议把 `product-front-tuck-mailer` 的主词改为 `front tuck boxes`，避免与 `/mailer-boxes/` 分类页抢词。

4. **`product-euro-tote-paper-carrier` 的买家叫法**：原名是 `Paper Gift Carrier`，成品名写成 `Custom Paper Gift Bags with Logo`。它和 `product-paper-merchandise-sleeve-bag`（`Custom Flat Paper Merchandise Bags`）、`product-sos-paper-gift-bag`（`Custom SOS Paper Bags Bulk`）同属纸袋，靠结构（折叠提手 / 平张 / 平底）区分，未使用城市或 `near me` 类词。

5. **`molded-pulp-insert` 的译文做过手工修正**：初版意/葡/日译文漏了 logo 提法、es/nl 也缺，已补齐并与英文一致；另 `it` / `pt` / `ja` 三条 title 曾漏 ` | Packrivo` 后缀，已补。

6. **`zh-TW` 的「工廠直接出貨」已改写**：Packrivo 是代采不是工厂，繁体版初稿写了「工廠直接出貨」，全部 130 条改为「由我們安排出貨」；`ui-strings.json` 里 `Supplier briefs…` 的繁中译文也从「工廠溝通重點」改为「供應商溝通重點」。

## 五、自查（实跑输出）

```
$ node validate-output.mjs
批次合计 130 条；ui-strings 60 条 × 11 语种
PASS：结构、长度、唯一性、禁用词、占位符全部通过

$ node audit-final.mjs
批次条数：26 / 26 / 26 / 26 / 26
ui-strings：60 条 × 11 语种
PASS：顺序、old_title、h1 一致性、译文非残留、无 BOM 全部通过
```

- 每批 26 条齐全；slug 顺序与 CSV `unchanged` 行逐条一致；`old_title` 与 CSV 原文一致。
- 每个语种 `title === h1 + " | Packrivo"`。
- 文件为 UTF-8 无 BOM，`JSON.parse` 通过，无注释、无尾逗号。
- 拉丁语系 6 个语种的 description 无一条与英文原文相同（即没有漏译）。

生成与校验脚本留在 `E:\破甲工作区\.tmp\packrivo-27f\`（`build-master.mjs` 出英文母本、`merge.mjs` 出成品、`validate-output.mjs` 与 `audit-final.mjs` 做校验），改词后重跑即可，不需要手改 JSON。

## 修正记录

### 2026-10-27 · DSH-FIX-2026-10-27f

本节记录本次修正后的现状，覆盖前文初次交付时已过时的口径、字节数和「没有修改已有文件」描述。本轮只修改本地生成源、校验脚本和交付文件；未导入、未运行数据库操作、未执行 Git 提交或推送、未访问网站或服务器。

开始核对时，第 1–7 条的大部分指定修正已存在于 `build-master.mjs`、`fix-27f.mjs`、语种源文件及成品中。本次保留并重新执行这些源修正，补上全量结构名自查发现的问题，再由 `merge.mjs` 统一重出成品，没有手改成品 JSON。

1. **Pillow box**：11 个语种均采用修正单指定的枕头形折叠纸盒名称；中文不再是「枕芯」，德语不再是 `mit Kissen`。英文用途为 `for bracelets, earrings and small jewelry`，不写手表。各语种描述也明确为枕头形纸盒。
2. **Double-wall tray/lid**：英文为 `Custom Corrugated Tray Boxes with Lid | Packrivo`；11 个语种采用瓦楞托盘盒含义，德语 `Wellpappe-Schalen mit Deckel`、日语 `段ボール トレー箱 蓋付き`、简中 `定制瓦楞托盘盒带盖`，不再把该项写成灰板精装盒。
3. **Hinged nonmagnetic rigid box**：使用 `Custom Hinged Rigid Gift Boxes | Packrivo` 及修正单给定英文描述。11 个语种保留翻盖、无磁吸含义，去掉丝带系扣。
4. **Hinged metal container**：英文和 11 语种描述均未保留 `with a lined interior` 或对应内衬说法。
5. **Molded pulp insert**：使用 `Custom Molded Pulp Packaging Inserts | Packrivo` 及给定英文描述；11 语种均去掉印刷、logo 承诺。**本条覆盖前文「四、拿不准的页面」第 5 点的旧说明**。会重新加回印刷说法的旧 `fix-pulp.mjs` 已停用，运行只报错提示正确链路，不再改文件。
6. **Clamshell / blister**：德语 clamshell 为 `Klappverpackungen (Clamshell) bedruckt`。其余 10 语种逐项与 slide-in blister 区分；法语和阿语进一步明确铰接/对折式 clamshell，插卡 blister 保持独立名称，不把两者合并。
7. **Supplier photo 标注**：de、es、ja、ko、nl 均与修正单指定译文逐字一致，包含「供应商」；原英文键保持不变。
8. **全量自查**：核对 130 条英文、各语种标题；重点结构项逐语种复核描述，配合全部 130 × 11 记录及 60 × 11 界面翻译的程序检查。以下是本次额外修正：

| slug / 结构 | 涉及语种 | 修正 |
| --- | --- | --- |
| `product-straight-tuck-retail-carton` | 11 语种 + 英文 | 删除「单端开启」；德语不再用「一个插舌」来区别直插/反插，使用直插结构名和零售用途 |
| `product-bookend-folding-carton` | 11 语种 + 英文 | 删除「书立、支撑商品站立」推断；保留原始 `Bookend` 结构标识，待刀模确认当地具体名称 |
| `product-corrugated-reverse-tuck-pack`、`product-reverse-tuck-retail-carton` | de、nl | 明确反向插舌，不只写「两端各一个插舌」或泛称插口盒 |
| `product-corrugated-gusset-folder`、`product-gusset-folder-carton` | es、nl、zh-CN、zh-TW（按条目） | gusset 使用侧褶/侧折含义，去掉「风琴折」推断；瓦楞项西语保留 ondulado |
| `product-header-card-bag-topper` | ar、de、fr、it、pt | 用袋口封卡/挂卡的包装含义，去掉书信「抬头卡」和把袋子「加冕」的直译 |
| `product-snap-lock-bottom-carton` | es | 从 `fondo autoblocante` 改为手工卡合底 `fondo de encaje manual`，与自动底区分 |
| `product-six-corner-pre-glued-carton` | 11 语种 | 指六处预粘角点，不是六边形或六个面；中文改「六点预粘/六點預糊」 |
| `product-five-panel-corrugated-folder` | de | 五个面板不是五个独立零件；去掉 `fünfteilige` 的零件数歧义 |
| `product-round-metal-gift-container` | 11 语种 + 英文 | 原始产品名只确认圆形金属容器；不把 slip-lid 直译成横向滑盖，回到 `Custom Round Metal Gift Tins`，不臆造盖型 |
| `product-shoulder-neck-rigid-box` | 11 语种 + 英文 | 删除「内颈抬起/弹起上盖」动作推断，只保留 shoulder-and-neck 结构 |
| `product-tuck-end-sleeve-carton` | 11 语种 + 英文 | 删除无证据的「套在托盘外抽拉」描述，保留原始结构名和零售用途 |
| `product-gable-top-carry-carton` | 11 语种 + 英文 | 保留屋型盒和提手；不声称折出、隐藏式或伸缩提手 |
| `product-folded-box-liner` | 11 语种 + 英文 | 原始名称是 folded liner，不追加 sleeve 产品或柔软功能；只写折叠内衬和按盒型制作 |
| `product-box-bottom-fabric-tote` | pt、zh-CN | 不把 fabric 限定成帆布，也不凭 box-bottom 声称硬质底板 |
| `textile-jewelry-pouch` | zh-CN、zh-TW | 不把未限定的 textile 硬写成绒布；改为布质首饰袋 |
| `product-coated-labels` | es、fr、it、ja、zh-CN、zh-TW | 只表达涂层，不把未确认的 coated 标签限定成铜版纸或上光工艺 |

Belly band（包装腰封）、gable（屋型盒）、auto bottom（自动底）、snap bottom（手工卡合底）、SOS（平/方底纸袋）、header card（袋头卡）均已作为包装结构复核，未把这些词按日常用品含义翻译。结构回归检查覆盖了本轮发现的错误；它不等同于母语买家搜索量或刀模适配验证。

### 尚需源图/刀模确认的术语

下列项目不靠猜测补细节；保留可确认的结构标识，并列明待确认点：

| slug | 语种 | 原因 / 本轮处理 |
| --- | --- | --- |
| `product-bookend-folding-carton` | ar、de、es、fr、it、ja、ko、nl、pt、zh-CN、zh-TW | 仅从 `bookend` 不能确认具体盖片结构及当地标准名称。本轮全部保留 `Bookend`，去掉书立、竖立陈列功能；取得刀模后再确定当地叫法 |
| `product-lock-cap-auto-bottom-carton` | de、fr、it、nl（其余语种也需与同一刀模核对） | lock-cap 的锁合部位尚无刀模可查，不能确定应叫插盖、卡扣盖或其他锁盖。不追加锁舌数、锁合位置等细节 |
| `product-pinch-lock-paperboard-tray` | ar、de、es、fr、it、nl、pt、zh-CN、zh-TW | `pinch-lock` 的实际折叠锁扣与当地商品名称未确认；不将「捏锁/夹扣」当作已由供应商证实的标准名，需用刀模确认 |
| `product-tuck-end-sleeve-carton` | 11 个语种 | tuck-end 与 sleeve 的组合结构及是否配托盘未确认；本轮已删去抽拉托盘断言，仍需结构图核对当地叫法 |
| `product-double-wall-frame-tray-lid`、`product-double-wall-frame-tray-sleeve` | 11 个语种 | `frame` 与 double-wall 的具体展开结构仍需图纸；不把第 2 条对单个 tray/lid 的瓦楞判断套用于这两项 |
| `product-full-flap-auto-bottom-carton` | de，及 11 语种描述的承重用途 | 具体 full-flap 形式及承重用途缺少结构/测试依据；保留待源图和规格核对，不新加承重数字 |

### 可重复生成链路

源目录：`E:\破甲工作区\.tmp\packrivo-27f`。修改的是生成脚本和语种修正规则，再由程序生成 `master-en.json`、语种源 JSON 和成品。**不要运行旧的 `fix-pulp.mjs`；不要只改成品 JSON。**

```powershell
node E:\破甲工作区\.tmp\packrivo-27f\build-master.mjs
node E:\破甲工作区\.tmp\packrivo-27f\fix-27f.mjs
node E:\破甲工作区\.tmp\packrivo-27f\fix-structure-audit.mjs
node E:\破甲工作区\.tmp\packrivo-27f\merge.mjs
node E:\破甲工作区\.tmp\packrivo-27f\validate-output.mjs
node E:\破甲工作区\.tmp\packrivo-27f\audit-final.mjs
node E:\破甲工作区\.tmp\packrivo-27f\audit-structure-fixes.mjs
```

`build-master` 本次输出：130/130，英文 title 最大 51 字符、description 最大 120 字符、title 唯一数 130。`fix-structure-audit` 写入 19 个 slug 的 117 个语种记录。再次运行完整生成链路后，以下六个成品的 SHA-256 均不变，确认重复生成结果稳定。

### 修正后校验原样输出

```text
$ node validate-output.mjs
批次合计 130 条；ui-strings 60 条 × 11 语种
PASS：结构、长度、唯一性、禁用词、占位符全部通过

$ node audit-final.mjs
批次条数：26 / 26 / 26 / 26 / 26
ui-strings：60 条 × 11 语种
PASS：顺序、old_title、h1 一致性、译文非残留、无 BOM 全部通过

$ node audit-structure-fixes.mjs
PASS: prescribed fixes and packaging-structure regressions

$ python -I E:\破甲工作区\packrivo-backup\scripts\dsh-validate.py E:\破甲工作区\packrivo-backup
pages done: 130/130   still to do: 0   ui strings: 60
RESULT PASS
```

修正后文件大小（替代上方首次交付表的旧字节数）：

| 文件 | 条数 | 字节 |
| --- | ---: | ---: |
| `batch-01.json` | 26 | 95,535 |
| `batch-02.json` | 26 | 92,886 |
| `batch-03.json` | 26 | 91,786 |
| `batch-04.json` | 26 | 91,003 |
| `batch-05.json` | 26 | 91,250 |
| `ui-strings.json` | 60 × 11 | 38,094 |

本轮开始时的本地源文件和成品快照：`D:\Codex\packrivo-dsh-20261027f\before-structure-fix`。修正后的批次顺序和 `old_title` 均与快照一致。

## 27i

已生成 `dsh-out/hubs-27i.json`：一个数组，共 **9 个对象**，包含 8 个主页面及 1 条按钮文字。8 页各有 ar、de、es、fr、it、ja、ko、nl、pt、zh-CN、zh-TW 共 11 个语种，每语种具备 title 和 description；按钮亦有 11 个译文。合计 88 个译文标题、88 个译文描述、11 个按钮译文。

英文 title / description 从 `DSH-TASK-2026-10-27i.md` 的定稿表提取，并以独立固定清单逐字校验，未改英文。翻译用词参照 SEO 方案第 1 节 E 及 27f 已用的同类包装词；pillow 指枕头形折叠纸盒，不是带枕垫的盒子。没有追加价格、交期、认证、工厂或原文以外城市。

格式口径及需注意之处：

- `/about/` 按任务单最后的特例处理：品牌保留在第一段，如 `Über Packrivo | …`，不在尾部再加一次 ` | Packrivo`；其余 7 页 × 11 语种都保留品牌后缀。
- `/inquiry/` 描述前的中文括号是任务说明，不属于英文正文。JSON 保留的英文为 `Request a packaging quotation with your product, dimensions, quantity and destination. MOQ 500 per design.`，未改写。
- 标题全部不超过 60 字符。译文 description 完整保留定稿含义，最长 189 字符；本任务没有规定 description 上限，没有为了套用旧批次的长度规则擅自删减内容。
- 没有新增需要业务确认的事实；此文件没有被导入，字典及页面均未修改。

各语种 title 最大长度：

| 语种 | 最大字符数 |
| --- | ---: |
| ar | 48 |
| de | 56 |
| es | 56 |
| fr | 58 |
| it | 60 |
| ja | 37 |
| ko | 37 |
| nl | 53 |
| pt | 52 |
| zh-CN | 29 |
| zh-TW | 30 |

本地生成与校验脚本留在 `E:\破甲工作区\.tmp\packrivo-27i\generate.mjs`、`validate.mjs`。实跑校验：

```text
$ node E:\破甲工作区\.tmp\packrivo-27i\validate.mjs
ar max_title_chars=48 max_description_chars=126
de max_title_chars=56 max_description_chars=166
es max_title_chars=56 max_description_chars=178
fr max_title_chars=58 max_description_chars=189
it max_title_chars=60 max_description_chars=183
ja max_title_chars=37 max_description_chars=84
ko max_title_chars=37 max_description_chars=82
nl max_title_chars=53 max_description_chars=155
pt max_title_chars=52 max_description_chars=187
zh-CN max_title_chars=29 max_description_chars=52
zh-TW max_title_chars=30 max_description_chars=53
PASS: pages=8 languages=11 title_checks=88 description_checks=88 ui_translations=11
PASS: approved English unchanged; brand format; <=60 titles; no sourcing terms; numbers preserved; UTF-8 without BOM
```

本轮只写本地文件；未导入 WordPress、未操作数据库、未执行 Git 命令、未访问网站或服务器。
