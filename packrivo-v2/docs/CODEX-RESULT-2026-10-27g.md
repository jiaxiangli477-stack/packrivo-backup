# Codex 结果 · 2026-10-27g

## 结论

已完成 130 页本地导入、134 页字段比对、11 语种增量及完整字典合并、15 个多语言 HTTP 样本和两张阿语截图、上线/回滚命令准备。没有改写 dsh 文案，没有部署或改动 www.packrivo.com；本轮没有 SSH/SCP 操作。

**内容字段验收通过，布局/标记仍有既有问题，不能称为全绿上线验收：** 133 页有两个相同的 head title 标签；5 个阿语样本均因既有隐藏 skip-link 产生横向溢出。两项均已写入上线包阻断清单。本轮按指定范围未修改主题代码。

## 0. 前置检查：通过

已重新读取 `E:\破甲工作区\packrivo-v2\agent.md` 与 27g 任务单。Python 不在 PATH，使用现有 E 盘环境的解释器执行同一脚本：

```powershell
& 'E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe' -I 'E:\破甲工作区\packrivo-backup\scripts\dsh-validate.py'
```

原样输出：

```text
pages done: 130/130   still to do: 0   ui strings: 60
RESULT PASS
```

README 检查输出：

```text
README correction section: PASS
Correction section references DSH-FIX: True
Correction item 8 present: True
```

所以允许继续第 1 节。已审输入文件保持原样；五个批次的哈希与导入前记录一致。

## 1. 本地导入：完成

脚本：`E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\import.php`。默认范围 pending130；expected-site 参数必须与实际 home URL 完全匹配。先导出原值到 `deploy/rollback-27g.json`，再在事务中应用。仅修改标题、H1 内部文字、相应 SEO/meta 字段及产品 meta 的名称/简介，正文的其他字节保持不变，slug/status/parent 保持不变。

运行目录 `E:\破甲工作区\packrivo-live`，完整命令：

```powershell
$env:PATH='D:\Docker\App\resources\bin;'+$env:PATH
docker compose -f docker-compose.yml -f 'E:\破甲工作区\.tmp\packrivo-27c-compose.yml' run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup:/package' cli wp eval-file /package/scripts/product-buyers-27g/import.php apply /package http://localhost:8081
```

首次业务输出（不包含 Docker 临时容器创建进度行）：

```text
input_pages=130 preflight=PASS
backup=created rows=130
apply_updated=130 already_current=0 URL_and_body_check=PASS
```

相同命令重跑：

```text
input_pages=130 preflight=PASS
backup=reused rows=130
apply_updated=0 already_current=130 URL_and_body_check=PASS
```

将上述命令的 `apply` 替换为 `verify`：

```text
input_pages=130 preflight=PASS
backup=reused rows=130
verified_pages=134 sourcing_titles=0 duplicate_titles=0 mismatches=0
URL_and_non_H1_body_unchanged=130/130
```

将 `apply` 替换为 `rollback-check`（只检查，没有执行回滚）：

```text
input_pages=130 preflight=PASS
backup=reused rows=130
rollback_eligible=130 already_restored=0 writes=0
backup_unchanged: True
```

备份包含本地 130 页的原始 title/content/meta 与输入哈希；SHA-256：

```text
4F2E80D31FD07D67187132D4210EB30B6DE6510F3778E4CF6887FBB1F6DD8123
```

前四页沿用 27e 已审清单，其余 130 页逐项与 dsh 比对，合计 134。没有把前四页冒称为 dsh 的 130 页。

## 2. 字典：完成

基线是 27e 只读取得的生产文件：`E:\破甲工作区\.tmp\packrivo-27e-dictionaries`。本轮没有重新连接生产。文件哈希见 `deploy/i18n-baseline-27g.json`。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\merge-i18n.mjs --root E:\破甲工作区\packrivo-backup --base E:\破甲工作区\.tmp\packrivo-27e-dictionaries
```

- 11 个增量文件在 `i18n-delta\`，包含原四页 + dsh 130 页 + 60 条界面词。
- 完整合并文件在 `deploy\i18n-merged\`。
- 完整 title 放 title，H1 放 block/text，description 放 block/attr；alt 放 attr，Search 同时覆盖 placeholder。具体 60 条 UI 分区在 `scripts\product-buyers-27g\ui-partitions.json`。
- 同一英文键在生产任一分区已有不同译文时，保留所有生产已有分区值；不会通过新增分区绕过该规则。冲突文件保留生产值、已审提案和处理结果。

原样输出：

```text
language delta_bucket_keys added_bucket_keys added_english_keys conflicts
ar 796 791 457 4
de 796 787 457 2
es 796 787 457 3
fr 796 787 457 1
it 796 787 457 1
ja 796 791 457 0
ko 796 791 457 1
nl 796 787 457 1
pt 796 787 457 2
zh-CN 796 787 457 3
zh-TW 796 791 457 1
production existing bucket values preserved; reviewed source conflicts=0
```


| 语种 | 新增分区键 | 新增英文键 | 冲突英文键 |
| --- | ---: | ---: | ---: |
| ar | 791 | 457 | 4 |
| de | 787 | 457 | 2 |
| es | 787 | 457 | 3 |
| fr | 787 | 457 | 1 |
| it | 787 | 457 | 1 |
| ja | 791 | 457 | 0 |
| ko | 791 | 457 | 1 |
| nl | 787 | 457 | 1 |
| pt | 787 | 457 | 2 |
| zh-CN | 787 | 457 | 3 |
| zh-TW | 791 | 457 | 1 |

统计定义：added_bucket_keys 是新增「分区+英文键」数；added_english_keys 是此前生产所有分区都没有的不同英文键；conflicts 是不同英文键数量。每种语言 457 个新增英文键，合计 19 个冲突；不是 19 页文案冲突。具体键为 Jewelry boxes、Gift boxes、Mailer boxes、Samples、Read the guide → 的部分语种译法，均保留生产。

完整性检查命令：

```powershell
node E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\verify-merge.mjs E:\破甲工作区\.tmp\packrivo-27e-dictionaries
```

输出：

```text
{"approvedBatchFilesUnchanged":5,"productDeltaSourceChecks":7370,"uiPartitionChecks":1386,"productionBucketValuesPreserved":71593,"conflictingEnglishKeys":19,"languages":11}
PASS: reviewed proposals complete; production values retained; placeholders unchanged
```

重复生成 11 个增量、11 个完整字典、冲突和统计文件的结果：

```text
Dictionary regeneration files=24 unchanged=true
```

## 3. 本地验证：内容通过；标记与 RTL 布局发现问题

### 134 个英文页面

```powershell
node E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\verify-english-http.mjs
```

原样输出：

```text
English HTTP pages=134 matches=134 mismatches=0 sourcing_titles=0 duplicate_titles=0
Existing markup finding: pages_with_multiple_head_title_tags=133
```

所有页面 HTTP 200，浏览器主标题、H1、description 与已审清单逐条一致。`duplicate_titles=0` 指不同页面的主标题不重复。**另有 133 页各自输出两个相同的 `<title>` 标签**，不能把前一项通过当成 HTML 标记完全正确；该问题在 `english-134.json` 的 titleTags 数组中完整记录。

最初严格按每页只能有一个 title 标签检查时，133 页因此报错；原始结果保存在 `english-134-initial-strict.json`。没有删除该发现，只将跨页面唯一性与同页标签数分开统计。父主题 `packrivo_output_page_metadata` 的输出与既有子主题 renderer 补偿逻辑重叠，属于当前主题代码问题，本轮未修改主题。

### 5 页 × de/ja/ar

```powershell
$env:TEMP='E:\破甲工作区\.tmp'
$env:TMP='E:\破甲工作区\.tmp'
node E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\verify-http.cjs
```

原样输出如下。title 是浏览器主标题，titleTags 列出真实 head 标签；阿语里第二个未翻译的英文 title 也如实保留在证据中。

```text
{"slug":"product-pillow-jewelry-box","lang":"de","status":200,"title":"Kissenschachteln für Schmuck | Packrivo","titleTags":["Kissenschachteln für Schmuck | Packrivo","Custom Pillow Jewelry Boxes | Packrivo"],"h1":["Kissenschachteln für Schmuck"],"description":["Kissenschachteln aus Karton für Armbänder, Ohrringe und kleinen Schmuck, mit Ihrem Logo, ab 500 Stück. Preis anfragen."],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-pillow-jewelry-box","lang":"ja","status":200,"title":"ピローボックス ジュエリー用 | Packrivo","titleTags":["ピローボックス ジュエリー用 | Packrivo","Custom Pillow Jewelry Boxes | Packrivo"],"h1":["ピローボックス ジュエリー用"],"description":["ブレスレットやイヤリングなど小物ジュエリー向けの枕型折りたたみ紙箱。ロゴ印刷は500個から。見積もりを依頼。"],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-pillow-jewelry-box","lang":"ar","status":200,"title":"علب وسادة للمجوهرات | Packrivo","titleTags":["علب وسادة للمجوهرات | Packrivo","Custom Pillow Jewelry Boxes | Packrivo"],"h1":["علب وسادة للمجوهرات"],"description":["علب وسادة كرتونية للأساور والأقراط والمجوهرات الصغيرة، مع طباعة الشعار وبحد أدنى 500 قطعة. اطلب عرض سعر."],"direction":"rtl","htmlDir":"rtl","overflow":true,"match":true}
{"slug":"product-double-wall-tray-lid","lang":"de","status":200,"title":"Wellpappe-Schalen mit Deckel | Packrivo","titleTags":["Wellpappe-Schalen mit Deckel | Packrivo","Custom Corrugated Tray Boxes with Lid | Packrivo"],"h1":["Wellpappe-Schalen mit Deckel"],"description":["Wellpappe-Schalen mit passgenauem Deckel, mit Ihrem Logo, ab 500 Stück. Preis anfragen."],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-double-wall-tray-lid","lang":"ja","status":200,"title":"段ボール トレー箱 蓋付き | Packrivo","titleTags":["段ボール トレー箱 蓋付き | Packrivo","Custom Corrugated Tray Boxes with Lid | Packrivo"],"h1":["段ボール トレー箱 蓋付き"],"description":["蓋がぴったり合う段ボールのトレー箱。ロゴ印刷は500個から。見積もりを依頼。"],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-double-wall-tray-lid","lang":"ar","status":200,"title":"صواني مموجة بغطاء | Packrivo","titleTags":["صواني مموجة بغطاء | Packrivo","Custom Corrugated Tray Boxes with Lid | Packrivo"],"h1":["صواني مموجة بغطاء"],"description":["صواني مموجة بغطاء محكم، مع طباعة الشعار وبحد أدنى 500 قطعة. اطلب عرض سعر."],"direction":"rtl","htmlDir":"rtl","overflow":true,"match":true}
{"slug":"product-hinged-nonmagnetic-rigid-box","lang":"de","status":200,"title":"Hartschachteln mit Klappdeckel | Packrivo","titleTags":["Hartschachteln mit Klappdeckel | Packrivo","Custom Hinged Rigid Gift Boxes | Packrivo"],"h1":["Hartschachteln mit Klappdeckel"],"description":["Hartschachteln mit Klappdeckel ohne Magnete, mit Ihrem Logo, ab 500 Stück. Preis anfragen."],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-hinged-nonmagnetic-rigid-box","lang":"ja","status":200,"title":"オリジナル 開き蓋 貼り箱 マグネットなし | Packrivo","titleTags":["オリジナル 開き蓋 貼り箱 マグネットなし | Packrivo","Custom Hinged Rigid Gift Boxes | Packrivo"],"h1":["オリジナル 開き蓋 貼り箱 マグネットなし"],"description":["マグネットを使わない開き蓋の貼り箱。ロゴ印刷は500個から。見積もりを依頼。"],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-hinged-nonmagnetic-rigid-box","lang":"ar","status":200,"title":"علب صلبة بغطاء مفصلي | Packrivo","titleTags":["علب صلبة بغطاء مفصلي | Packrivo","Custom Hinged Rigid Gift Boxes | Packrivo"],"h1":["علب صلبة بغطاء مفصلي"],"description":["علب صلبة بغطاء مفصلي بدون مغناطيس، مع طباعة الشعار وبحد أدنى 500 قطعة. اطلب عرض سعر."],"direction":"rtl","htmlDir":"rtl","overflow":true,"match":true}
{"slug":"molded-pulp-insert","lang":"de","status":200,"title":"Faserguss-Einlagen | Packrivo","titleTags":["Faserguss-Einlagen | Packrivo","Custom Molded Pulp Packaging Inserts | Packrivo"],"h1":["Faserguss-Einlagen"],"description":["Faserguss-Einlagen, die Produkte festhalten, auf Ihr Produkt geformt, ab 500 Stück. Preis anfragen."],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"molded-pulp-insert","lang":"ja","status":200,"title":"オリジナル パルプモールド 中仕切り | Packrivo","titleTags":["オリジナル パルプモールド 中仕切り | Packrivo","Custom Molded Pulp Packaging Inserts | Packrivo"],"h1":["オリジナル パルプモールド 中仕切り"],"description":["商品をしっかり固定するパルプモールドの中仕切り。形状は商品に合わせて成型し、500個から。見積もりを依頼。"],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"molded-pulp-insert","lang":"ar","status":200,"title":"حشوات لب مقولب للتغليف | Packrivo","titleTags":["حشوات لب مقولب للتغليف | Packrivo","Custom Molded Pulp Packaging Inserts | Packrivo"],"h1":["حشوات لب مقولب للتغليف"],"description":["حشوات لب مقولب تثبت المنتج في مكانه، تُصنع على مقاس قطعتك وبحد أدنى 500 قطعة. اطلب عرض سعر."],"direction":"rtl","htmlDir":"rtl","overflow":true,"match":true}
{"slug":"product-hinged-thermoformed-clamshell","lang":"de","status":200,"title":"Klappverpackungen (Clamshell) bedruckt | Packrivo","titleTags":["Klappverpackungen (Clamshell) bedruckt | Packrivo","Custom Printed Clamshell Packaging | Packrivo"],"h1":["Klappverpackungen (Clamshell) bedruckt"],"description":["Klappverpackungen (Clamshell), die das Produkt zeigen, mit Ihrem Logo, ab 500 Stück. Preis anfragen."],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-hinged-thermoformed-clamshell","lang":"ja","status":200,"title":"オリジナル クラムシェル 印刷パッケージ | Packrivo","titleTags":["オリジナル クラムシェル 印刷パッケージ | Packrivo","Custom Printed Clamshell Packaging | Packrivo"],"h1":["オリジナル クラムシェル 印刷パッケージ"],"description":["中身が見える開閉式のクラムシェルパック。ロゴ印刷は500個から。見積もりを依頼。"],"direction":"ltr","htmlDir":"","overflow":false,"match":true}
{"slug":"product-hinged-thermoformed-clamshell","lang":"ar","status":200,"title":"عبوات مفصلية (Clamshell) | Packrivo","titleTags":["عبوات مفصلية (Clamshell) | Packrivo","Custom Printed Clamshell Packaging | Packrivo"],"h1":["عبوات مفصلية (Clamshell)"],"description":["عبوات بلاستيكية مفصلية من نوع Clamshell تُظهر المنتج، مع طباعة الشعار وبحد أدنى 500 قطعة. اطلب عرض سعر."],"direction":"rtl","htmlDir":"rtl","overflow":true,"match":true}
HTTP checks=15 field_mismatches=0 RTL_direction_failures=0 horizontal_overflow_pages=5
```

15 个字段样本全部匹配。阿语五页 html dir 与 computed direction 都为 rtl，但脚本对既有横向溢出返回退出码 1：不是「全部验证通过」。定位结果：

```text
width: 375
scroll: 10374
offscreen element: A.pk-skip, x=-9999
```

主题 `.pk-skip { left:-9999px }` 在 RTL 下扩大文档宽度。两张截图为真实未修改页面的可见右侧 375×900 区域：

- `deploy/verification-27g/product-pillow-jewelry-box-ar-375.png`
- `deploy/verification-27g/product-double-wall-tray-lid-ar-375.png`

Chromium 默认 viewport 截图会在该 RTL 大画布上截到空白区域，因此脚本从完整画布提取右侧可见视口。方法和源画布宽度记录在 http-results.json；未注入 CSS、未改 DOM、未隐藏缺陷。截图显示 RTL 内容，但原有本地媒体未完整同步，不能作为图片完整性验收。

## 4. 上线包：命令准备完成，未执行

`deploy/README.md` 已补上字典合并/安装/回滚，内容 plan/apply/verify/rollback-check/rollback 的具体命令。包括：

- 以部署时最新实际生产备份为合并基线；先审阅冲突，再替换字典。
- 站点地址和输入哈希保护；生产使用独立的新 rollback-27g.json，禁止复用 localhost 备份。
- 本地实际执行 pending130；生产包可选 all134 将前四页与 130 页合在同一备份及事务中。本轮只在本地运行了 all134 的 plan，输出 `input_pages=134 preflight=PASS`、`writes=0`，未应用 all134。
- 上线前仍需处理既有报价重定向、父主题目录名、重复 head title、RTL skip-link，以及首页 5/8 卡片问题。这些不是本轮已部署的修复。

本轮不执行 README 中的生产命令和回滚命令。

## 5. 文件及提交范围

- `dsh-out/`：已审输入，原样纳入版本管理。
- `scripts/product-buyers-27g/`：幂等导入、回滚检查、字典合并及验证脚本。
- `i18n-delta/`：11 语种完整增量。
- `deploy/i18n-merged/`：11 份生产优先合并字典；冲突、统计、基线哈希和本地回滚原值随包保存。
- `deploy/verification-27g/`：134 页比对、15 个语言样本与两张阿语截图。
- 本结果文件同步到备份仓库的 `packrivo-v2/docs/`。提交和推送回执在末尾记录。

## 独立代码审查

提交前进行只读审查，未发现可证实的 Critical / Important 问题。审查范围包括导入范围、URL/正文保护、备份复用、回滚漂移保护、all134 参数与上线命令，以及字典生产优先策略。独立重算确认 71,593 个生产分区键值保留、19 项冲突一致、原四页 220 个增量桶键未丢失。审查者未调用数据库或网站；PHP 实际执行与 HTTP 验证是本报告前述总控实测结果。
