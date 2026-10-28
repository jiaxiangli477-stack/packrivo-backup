# CODEX-RESULT-2026-10-28k

执行日期：2026-10-28（America/Los_Angeles）。起点：备份仓库 `main` / `3f530a9`。

已将 207 条、11 语种已审译文合并进本地增量和完整字典，原译文文件未改。6 个与 27i 基线不同的键按任务要求保留基线值并列出；没有部署，未修改 www.packrivo.com。生产只做了第 3 节允许的只读 HTTP 路由核对。

## 0. 输入与基线

- 已审文件：`E:\破甲工作区\packrivo-backup\dsh-out\untranslated-27j.json`，207 个原文键、每键 11 种语言；SHA-256 `edcadc0b117fa3a564c18c25c5b52b96d1917cf72e9777a6f80cd831d2792b3f`，合并前后相同。
- 分区：`dsh-in/untranslated-27j.json` 的 kind。文本同时进入 block 和 text，沿用 27g；attr 进入 attr。若 27j 节点证据显示同一 attr 键也作正文，则同时写文字分区。
- 完整字典、增量及 runtime 的本地合并前快照在 `E:\破甲工作区\.tmp\packrivo-28k\baseline`；来源是 27i 之后的仓库完整包，并非本轮实时下载的生产字典。SHA 清单位于 `deploy/i18n-baseline-28k.json`。将来上线前必须重新读取当时的生产字典作基线。

## 1. 合并和冲突

按各语种写入 `i18n-delta/<lang>-dict.json`、`deploy/i18n-merged/<lang>-dict.json` 和运行时 JS 字典。原有数据按分区保留；新请求与已存在的任意分区值不同时，完整字典保留基线值，新译文仍留在审定输入中，冲突清单列出全部原值和请求值。增量已有内容也不覆盖。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\merge-i18n.mjs --root E:\破甲工作区\packrivo-backup --base E:\破甲工作区\.tmp\packrivo-28k\baseline\i18n-merged --js-base E:\破甲工作区\.tmp\packrivo-28k\baseline\i18n-merged --delta-base E:\破甲工作区\.tmp\packrivo-28k\baseline\i18n-delta
```

原样输出：

```text
language added_english added_bucket production_conflicts prior_delta_or_runtime_conflicts runtime_exact runtime_patterns
ar 181 369 0 0 6 96
de 181 363 0 0 6 96
es 181 365 0 0 6 96
fr 181 365 0 0 6 96
it 181 364 1 0 6 96
ja 181 369 1 0 6 96
ko 181 369 0 0 6 96
nl 181 364 0 0 6 96
pt 181 365 0 0 6 96
zh-CN 181 363 2 0 6 96
zh-TW 181 369 2 0 6 96
PASS: reviewed source unchanged; existing production and prior delta values retained; October approved sample matched by runtime date pattern
```

冲突为 6 条，详细旧值和已审值在 `deploy/i18n-conflicts-28k.json`：

| 语种 | 英文键 | 处理 |
| --- | --- | --- |
| it | Home | 保留 27i 基线值 |
| ja | Bulk Shipping Boxes with Logo | 保留 27i 基线值 |
| zh-CN | Bulk Shipping Boxes with Logo | 保留 27i 基线值 |
| zh-CN | Custom Double-Wall Tray and Lid Sets | 保留 27i 基线值 |
| zh-TW | Bulk Shipping Boxes with Logo | 保留 27i 基线值 |
| zh-TW | Custom Double-Wall Tray and Lid Sets | 保留 27i 基线值 |

保留的中文 `Bulk Shipping Boxes with Logo` 旧值与英文含义存在差异；此处按明确的生产优先规则没有覆盖，后续如需修正应由站长另行决定。各语种完整机器统计见 `deploy/i18n-statistics-28k.json`。

### 文章日期和动态菜单

现有 MU 只接受 `[正则, 固定替换串]` 的 runtime patterns，不能在**一个**固定替换串里按匹配结果把四种分类和十二个月翻成不同词。因此不改插件：按四个已出现分类、12 个英文月份，以及有/无 `N min` 两种形式生成每语种 96 条受限规则；分类及日期布局沿用已审样本，月份名称取 Node Intl，本轮已审文本逐条仍按原文保留。新分类不在覆盖范围内。原 runtime patterns/exact 均保留。

浏览器审查还发现 `nav.js` 会给已翻译的 aria-label 拼入英文 Open/Close；针对三组按钮生成 6 个 runtime exact 映射，值复用已审或基线有效译文，遵循脚本原有的前缀剥离规则。没有修改主题或插件。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-merge.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-pattern-runtime.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-nav-runtime.mjs
```

原样输出：

```text
{"languages":11,"approvedEnglishKeys":207,"approvedSHA256":"edcadc0b117fa3a564c18c25c5b52b96d1917cf72e9777a6f80cd831d2792b3f","existingBucketValuesPreserved":80537,"syntheticDateCases":1056,"conflictCount":6,"pass":true}
PASS: approved input unchanged; all baseline values and old runtime rules retained; each requested kind resolved; dynamic month/day/year patterns tested
ar 96 PASS
de 96 PASS
es 96 PASS
fr 96 PASS
it 96 PASS
ja 96 PASS
ko 96 PASS
nl 96 PASS
pt 96 PASS
zh-CN 96 PASS
zh-TW 96 PASS
PASS runtime_injected_patterns=1056
ar 3 menus open/close PASS
de 3 menus open/close PASS
es 3 menus open/close PASS
fr 3 menus open/close PASS
it 3 menus open/close PASS
ja 3 menus open/close PASS
ko 3 menus open/close PASS
nl 3 menus open/close PASS
pt 3 menus open/close PASS
zh-CN 3 menus open/close PASS
zh-TW 3 menus open/close PASS
PASS 33 menus / 66 actual click transitions
```

## 2. 浏览器改前 → 改后

浏览器只访问 localhost:8081，等待 DOMContentLoaded 和前端 runtime；按 1280px 默认状态逐个统计实际可见英文**文字节点**，相同文字出现在多个节点分别计数。排除 Packrivo、纯价格/单位、DDP/FOB/MOQ 等通用缩写、文章标题/正文，以及与已审译文相同的当地同形词（例如法语 `Services`、`Journal`）。对照集为 27j 实际浏览器采集到的各页面英文节点；它不是对任意未采集英文的自动语言识别。完整每节点记录与执行日志在 `verification-28k/node-counts.json`、`node-counts-output.txt`。

前值由本地绑定文件暂切到 27i 基线取得，后值由 28k 合并文件取得；脚本的 finally 会恢复，并对 22 个文件逐一 SHA-256 校验。无生产写入。文章的 11 个正式语言 URL 均为 404：统计用本地 `/{lang}/?p=2937` 查询相同文章，验证实际 post ID；这不代表正式语言路径已经修复。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-node-counts.mjs
```

原样输出的最终表和结尾：

```text
page,ar,de,es,fr,it,ja,ko,nl,pt,zh-CN,zh-TW
"/",61→0,61→0,60→0,57→0,61→0,61→0,61→0,61→0,60→0,61→0,61→0
"/products/",10→0,10→0,10→0,8→0,10→0,10→0,10→0,10→0,10→0,10→0,10→0
"/jewelry-boxes/",65→0,65→0,65→0,65→0,66→1,65→0,65→0,65→0,65→0,65→0,65→0
"/blog/",24→0,24→0,24→0,23→0,24→0,24→0,24→0,24→0,24→0,24→0,24→0
"/2026/10/26/jewelry-pouch-box-customer-handoff/",6→0,6→0,6→0,5→0,7→1,6→0,6→0,6→0,6→0,6→0,6→0
"/product-pillow-jewelry-box/",10→0,10→0,10→0,10→0,11→1,10→0,10→0,10→0,10→0,10→0,10→0
PASS: 66 page/language node counts before and after; 22 mounted files restored and SHA-256 verified
```

66 组总计 **1,929 → 3 个节点**。三个剩余项均为意大利语 `Home`，分别出现在 `/jewelry-boxes/`、上述文章和 `/product-pillow-jewelry-box/` 的面包屑。已审译文为 `Pagina iniziale`，但 27i 基线已有 `Home`，按生产优先保留，所以如实列为未达 0 的例外，不把它改掉。另一次展开 FAQ/导航的去重词扫描原始数据见 `browser-output.txt`、`remaining-text.json`；其中包括 `Services` 等当地语言同形词，不与上表实际节点数混用。

375px 本地全页截图：

- [阿语首页](E:/破甲工作区/packrivo-backup/deploy/verification-28k/home-ar-375.png)
- [简体中文首页](E:/破甲工作区/packrivo-backup/deploy/verification-28k/home-zh-CN-375.png)

本地媒体快照仍有原有图片缺失，截图用于本轮文本/布局复核，不声称生产媒体完整性验收。价格、单位和通用缩写按任务排除，截图中仍可能看见英文单位。

## 3. 报价链接：记录，不修

```powershell
E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-routes.ps1
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\probe-quote.mjs
```

原样输出：

```text
local_ar_home_status=200
local_ar_quote_status=404
local_english_quote_href_count=13
local_paths_get_a_quote_present=False
production_ar_home_status=200
production_ar_quote_status=404
production_ar_inquiry_href_count=6
production_ar_quote_inquiry_buttons=6
{"status":200,"steps":5,"uniqueVisibleEnglishStrings":84,"submission":false}
```

本地阿语首页指向英文 `/get-a-quote/`，本地 `/ar/get-a-quote/` 为 404；生产只读检查阿语首页存在 `/ar/inquiry/` 链接，而 `/ar/get-a-quote/` 为 404。本轮不改路径、链接或生产。`deploy/README.md` 已写入上线前决策事项。

若建设报价多语言，至少需审阅 `wp-content/uploads/packrivo-i18n/paths.json` 路径白名单和现有 MU 路由/链接、`packrivo-quote.php` 前台 6 个 `i18n` 字段及 1 个 noscript 文案、`assets/pkq-quote.js` 动态选项/表单/状态、`includes/pricing.json` 和配置中的可见标签，以及 REST 提示。五步只读交互样本中有 **84 个不同的可见英文字符串**，含动态数量/价格，遍历了材料选项；成功、失败和未执行的服务端响应不在内，这不是全插件的穷举总数。未提交真实报价。

## 4. 范围保护与复核

```powershell
E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-protected.py
```

```text
parent_plugin_MU_files=107 changed=0
paths_unchanged=True approved_translation_sha256=edcadc0b117fa3a564c18c25c5b52b96d1917cf72e9777a6f80cd831d2792b3f
```

父主题、插件和 MU 的 107 个保护文件及本地路径表未变，已审译文 SHA 未变。此前 27j 的保护脚本包含“字典完全不变”的断言，本轮授权修改字典故不适用；已使用上述 28k 范围检查替代。

独立只读审查发现并复现了导航动态 aria-label 问题，修复了荷兰语保留 `Open ` 前缀的边界后，真实 11 语种 × 3 菜单开关均通过；未发现剩余 Critical / Important 问题。详情见 `verification-28k/independent-review.txt`。

## 提交与推送

主交付和推送回执在实际执行后补入；本段不代表已推送。
