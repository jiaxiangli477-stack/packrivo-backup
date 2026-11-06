# 36a · 首饰类目落地页英文文案 · 自检报告

- 工作区：`E:\破甲工作区\packrivo-backup`，分支 `main`，HEAD `e546ff3`
- 本轮改动：仅新增 `dsh-out/36a-landing/`，未动 `dsh-out/35a-*`、`_bak-35a-*`、`deploy/`、`packrivo-wp/` 及任何既有文件（见第 8 节 `git status`）
- 交付：4 个页面（第 5 页按去重判断放弃，理由见第 7 节）+ 1 份页面清单 CSV + 本报告

## 1. 磁盘交付物

| 文件 | 字节 |
|---|---|
| `dsh-out/36a-landing/custom-ring-boxes.md` | 8670 |
| `dsh-out/36a-landing/custom-earring-boxes.md` | 7705 |
| `dsh-out/36a-landing/custom-necklace-boxes.md` | 7599 |
| `dsh-out/36a-landing/jewelry-gift-boxes.md` | 7984 |
| `dsh-out/36a-landing/36a-pages.csv` | 3063 |
| `dsh-out/36a-landing/36a-report.md` | 本文件 |

`36a-pages.csv` 表头（逐字符核对）：

```
url,title,meta,h1,primary_kw,primary_vol,primary_kd,secondary_kws,internal_links
```

## 2. 字符数自检（从 CSV 单元格重新数，不采信生成时的打印值）

| url | title 字符 | 限 60 | meta 字符 | 限 120–155 | 首段词数 | 限 40–60 |
|---|---|---|---|---|---|---|
| `/custom-ring-boxes/` | 59 | OK | 147 | OK | 53 | OK |
| `/custom-earring-boxes/` | 54 | OK | 148 | OK | 57 | OK |
| `/custom-necklace-boxes/` | 54 | OK | 145 | OK | 52 | OK |
| `/jewelry-gift-boxes/` | 56 | OK | 153 | OK | 50 | OK |

首轮生成时三处超限，已修正后复测：

- 项链 title 63 → 54（原 `Custom Necklace Boxes | Pendant & Chain Presentation | Packrivo` 63 字符，改为 `Custom Necklace Boxes | Anti-Tangle Inserts | Packrivo`）
- 项链 meta 161 → 145、耳饰 meta 158 → 148、礼盒 meta 160 → 153（删去 `long lid-and-base`、`for brands buying in bulk`→`for bulk buyers`、`MOQ 250 per style`→`MOQ 250/style`）

## 3. 主词去重检查

判定口径：主词大小写不敏感、按 `34a-keyword-map.csv` 的「主关键词」列全表比对，共 141 行。

```
=== 4. primary-keyword dedupe
  /custom-ring-boxes/        custom ring box            no map collision
  /custom-earring-boxes/     earring jewelry box        no map collision
  /custom-necklace-boxes/    custom necklace boxes      no map collision
  /jewelry-gift-boxes/       jewelry gift box           no map collision
  duplicates among the 4 new pages: none
```

距离最近的三条既有主词（不构成重复，但边界值得记录）：

| 既有页 | 既有主词（搜索量/KD） | 与本轮页面的关系 |
|---|---|---|
| `/` 首页 | `custom packaging`（6600 / –） | 上位词，本轮四页做的都是它的长尾细分 |
| `/jewelry-boxes/` | `custom jewelry boxes`（1600 / 14） | 类目总页；`jewelry gift box`、`custom necklace boxes` 是收窄到使用场景/部位的独立 SERP |
| `/textile-jewelry-pouch/` | `custom jewelry pouches`（110 / –） | 布袋类，与盒类不重叠 |

「每个主词只能出现在一个页面上做主词」另有一处需说明：`ring box`（12100 / 39）搜索量远高于 `custom ring box`（590 / 10），但它无 `custom` 修饰、意图偏信息型，作为 H1 主词会与 `/custom-ring-boxes/` 的打样/起订量落地页意图错配，故本页主词仍取 `custom ring box`，`ring box` 只作为正文自然出现词，不列入次关键词表避免与自身主词抢。

## 4. 关键词数据逐条核对（对 `34a-keyword-universe.csv` 原值）

校验方式：脚本按行回查 universe，比 `搜索量` 与 `KD` 两列字符串是否**完全相等**，不做四舍五入、不补全。

```
=== 3. vol/KD verbatim vs 34a-keyword-universe.csv
  primary /custom-ring-boxes/        custom ring box       vol=590    kd=10  MATCH
    + velvet ring box               vol=2400   kd=11
    + ring velvet box               vol=1900   kd=13
    + wedding ring box              vol=2900   kd=21
    + personalized ring box         vol=720    kd=21
    + custom engagement ring box    vol=480    kd=5
    + custom proposal ring box      vol=170    kd=4
  primary /custom-earring-boxes/     earring jewelry box   vol=1600   kd=38  MATCH
    - custom earring boxes / earring boxes wholesale / bulk earring boxes
      earring packaging / earring packaging cards / earring box size      无量（universe 搜索量为空）
  primary /custom-necklace-boxes/    custom necklace boxes vol=70     kd=12  MATCH
    + necklace presentation gift box vol=90    kd=20
    - necklace box wholesale / custom necklace packaging / velvet necklace box wholesale
      necklace box with logo / necklace box size                          无量（universe 搜索量为空）
  primary /jewelry-gift-boxes/       jewelry gift box      vol=1900   kd=26  MATCH
    + jewelry packaging box         vol=210    kd=18
    + jewel box packaging           vol=140    kd=23
    + fine jewelry packaging        vol=70     kd=25
    + ring jewelry gift box         vol=50     kd=31
    + jewelry packaging supplies    vol=260    kd=24
    + luxury magnetic gift boxes    vol=40     kd=0
```

四条主词的 搜索量/KD 与 universe 全等（590/10、1600/38、70/12、1900/26）。

两处必须写清的处置：

1. **耳饰页 6 条次词全部无量。** `earring jewelry box` 是 universe 里唯一带搜索量的耳饰盒行（1600 / KD 38，也正是本页主词），其余耳饰长尾（`custom earring boxes`、`bulk earring boxes`、`earring box size` 等）搜索量字段为空。按「不改不编」要求，CSV 与页面头块一律记 `无量 / -`，不填估算值。
2. **`jewelry gift boxes`（复数）不在 universe。** 任务书把 `jewelry gift boxes` 列为礼盒页候选次词，但全表 6318 行无此行，首轮按 `无量` 写入后被校验器判为 `NOT IN UNIVERSE`，已替换为实数行 `jewelry packaging supplies`（260 / KD 24），替换后复测通过。这是本轮唯一一次关键词表条目变更，理由是原始条目无数据来源可引。

另注：`ring velvet box`（1900 / 13）与 `velvet ring box`（2400 / 11）是同一词的词序倒装，两者都有独立搜索量，故都保留在次关键词表中；若复核要求去重，建议删 `ring velvet box`，保留搜索量更高的前者。

## 5. 红线 grep（大小写不敏感，判据 `factory|manufactur|FSC|ISO|ISTA|MOQ 500`）

```
=== 5. red-line grep (case-insensitive) over deliverables
  36a-pages.csv                0 hits
  custom-earring-boxes.md      0 hits
  custom-necklace-boxes.md     0 hits
  custom-ring-boxes.md         0 hits
  jewelry-gift-boxes.md        0 hits
  TOTAL red-line hits: 0
```

首轮不是 0：耳饰页出现 `ista` 一处，定位到正文 `Earrings need the pair held apart at a fixed distance` 中的 `distance` 被 `ISTA` 子串命中。已改写为 `at a fixed spacing`，复测归零。**这不是误报可以忽略**——若复核用的是同一个 grep，`distance`/`assistant`/`consistency` 一类词都会命中，文案侧保持零命中比事后解释更稳。

同源提示一：`comparison` 含子串 `iso`，本轮文案刻意回避该词，比较表标题用 `at a glance`。

同源提示二：本报告自身故意写出了 `factory` / `manufactur` / `FSC` / `ISO` / `ISTA` 这几个模式名，所以对整个目录跑同一条 grep 时命中会落在 `36a-report.md` 上。四页正文与 CSV 的命中数是 0，复核时把 `36a-report.md` 排除或单独看即可：

```powershell
Select-String -Path 'E:\破甲工作区\packrivo-backup\dsh-out\36a-landing\*.md','E:\破甲工作区\packrivo-backup\dsh-out\36a-landing\*.csv' -Pattern 'factory|manufactur|FSC|ISO|ISTA|MOQ 500' -CaseSensitive:$false | Where-Object { $_.Filename -ne '36a-report.md' }
```

## 6. 允许事实 / 禁用事实核查

已写入且仅写入下列允许事实：

| 事实 | 出现在 |
|---|---|
| 印刷盒 MOQ 250 件/款 | 四页 MOQ 段 + FAQ |
| 无印现货 100 件起 | 四页 MOQ 段 + FAQ |
| 100–249 件人工报价 | 四页 MOQ 段 + FAQ |
| 打样 5–10 天 | 四页打样段 |
| 生产 7–30 天 | 四页报价/交期段 |
| DDP 或 FOB | 四页交期段 |
| 采购协调费 8–15%（最低 $150） | 四页报价段 |
| 每次报价附刀模 | 四页打样/报价段 |

禁用项核查输出：

```
=== 8. forbidden-claim screen (prices / dimensions / client counts)
  custom-ring-boxes        $digits=['$1']  dimensions=none
  custom-earring-boxes     $digits=['$1']  dimensions=none
  custom-necklace-boxes    $digits=['$1']  dimensions=none
  jewelry-gift-boxes       $digits=['$1']  dimensions=none
```

- `$1` 是允许事实「最低 $150」的匹配，非自造价格；四页均无单价、无运费报价。
- `dimensions=none`：比较表只写定性比较，无编造尺寸。项链页首轮出现 `1mm cable chain`、`over about 20cm` 两处数字，已改为 `a fine cable chain`、`long formats`，复测归零。
- 无客户数、无出货量、无案例、无评价、无客户名。
- 自称口径：全文用 `sourcing team` / `supplier network` / `we place the order into a vetted supplier network`，无厂/制造商自称；`factory`、`manufactur*` grep 均 0 命中。

## 7. 页面 5 `/custom-jewelry-packaging/`：不写

判定：**放弃该页**。依据是 `/` 与 `/jewelry-boxes/` 已经把 `jewelry packaging` 这条词吃下，另开一页属自相残杀。

`34a-keyword-map.csv` 逐行证据：

```
--- /
   主词: custom packaging | 6600 / Semrush KMT(seed=custom-jewelry-boxes-volume)+...
   次词: custom packaging boxes with logo; rigid gift boxes; mailer boxes wholesale;
         jewelry packaging; candle packaging; cosmetic packaging
   现H1: Custom packaging for jewelry, candles & gifts.
        新H1: Custom packaging for jewelry, candles and gifts
--- /jewelry-boxes/
   主词: custom jewelry boxes | 1600 / Semrush KMT(seed=custom-jewelry-boxes-volume)
   次词: magnetic jewelry box; drawer jewelry box; jewelry box with insert;
         rigid jewelry box; jewelry packaging set; packaging for jewelry
has /custom-jewelry-packaging/ in map: False
```

推理链：

1. `jewelry packaging`（1000 / 12）在 map 里**没有任何页面以它为主词**，所以硬串规则不冲突，冲突在意图层。
2. 首页 `/` 的次关键词已含 `jewelry packaging` 本身，H1 是 `Custom packaging for jewelry, candles and gifts`——首页就是这个词的既有承载页。
3. `/jewelry-boxes/` 主词 `custom jewelry boxes` 1600 / 14，次词含 `jewelry packaging set`、`packaging for jewelry`，与本页要写的 `jewelry packaging` 落在同一个 SERP 意图（首饰包装供应）。
4. 新页 `/custom-jewelry-packaging/` 作为第三张页面进入同一条词，会同时稀释首页与该类目页的内链权重，且三者 H1/首段必然高度重叠，属于典型的 keyword cannibalization。
5. 该 URL 目前在 map 的 141 行里**不存在**，即它是新建 URL；在已有两页覆盖的情况下新增第三页，改版成本换不到增量流量。

若复核结论是要做这一页，前置条件是先定它和 `/jewelry-boxes/` 的分工边界（例如 `/jewelry-boxes/` 收敛为盒型类目、`/custom-jewelry-packaging/` 收敛为整套包装方案 + 采购流程），并把首页次词里的 `jewelry packaging` 摘掉。在此之前不写。

## 8. 页面结构自检（脚本从 md 回读，非生成时自报）

每页：6 个 H2（4 个内容节 + 比较表 + FAQ）、比较表 5 行数据、FAQ 6 问答、内链见下表。

| 页面 | H2 | FAQ 对数 | 比较表数据行 | 内链数 |
|---|---|---|---|---|
| ring | 6 | 6 | 5 | 9 |
| earring | 6 | 6 | 5 | 7 |
| necklace | 6 | 6 | 5 | 9 |
| gift | 6 | 6 | 5 | 10 |

内链全集（脚本从正文 `](...)` 回读，且逐条比对 `34a-keyword-map.csv` 的 URL 列，**全部命中 map、无死链**）：

```
custom-ring-boxes.md     /customization/ /get-a-quote/ /guide-jewelry-insert-fit/ /packaging-cost/
                         /packaging-moq/ /product-lid-and-base-jewelry-box/ /product-magnetic-jewelry-box/
                         /product-pillow-jewelry-box/ /samples/
custom-earring-boxes.md  /customization/ /get-a-quote/ /guide-jewelry-insert-fit/ /packaging-cost/
                         /product-jewelry-box-with-insert/ /product-magnetic-jewelry-box/ /samples/
custom-necklace-boxes.md /customization/ /get-a-quote/ /guide-jewelry-insert-fit/ /jewelry-boxes/
                         /packaging-cost/ /packaging-moq/ /product-jewelry-box-with-insert/ /samples/
                         /textile-jewelry-pouch/
jewelry-gift-boxes.md    /bags-pouches/ /get-a-quote/ /guide-jewelry-insert-fit/ /packaging-cost/
                         /packaging-samples/ /printed-tissue-paper/ /product-drawer-jewelry-box/
                         /product-magnetic-jewelry-box/ /product-pillow-jewelry-box/ /samples/
```

四页均满足「≥2 个产品/指南页 + `/get-a-quote/` + `/samples/`」。首轮耳饰、项链、礼盒三页的 `/samples/` 只写在头部链接清单里、正文没有锚文本，脚本按正文回读判为缺失，已补正文链接后复测通过（同轮给耳饰页补了 `/product-magnetic-jewelry-box/` 正文链接，使其产品页链接从 1 条升到 2 条）。

FAQ 来源：`34a-questions.csv` 中有搜索量的行（`what is a dieline` 210/10、`what are pr boxes` 170/12、`what is foil stamping` 140/14 等）优先取用并改写为场景问句，无量行（`types of ring boxes`、`ring box with logo`、`insert for ring box`、`bulk earring boxes`、`earring packaging cards`、`earring box size`、`necklace box size`、`why are jewelry boxes so expensive` 等）直接取用。FAQ 答案词数 17–53 词，均为完整答复，无 stub。

## 9. 未执行 / 缺口

- **未写页面 5**，理由见第 7 节，等复核口径。
- **未校验线上现状**：本轮只对 `34a-*` CSV 与本机文件作业，未抓取 `www.packrivo.com` 现有页正文比对，因此「现有页是否已覆盖某条次词」只有 CSV 层面证据。需要更硬的一层就补：`curl -s https://www.packrivo.com/jewelry-boxes/ | grep -o 'jewelry packaging'`。
- **未做 SERP 实时复核**：搜索量/KD 全部来自 `34a-keyword-universe.csv`，未回源 Semrush/Ahrefs 重查。
- **未做拼写与语法外审**：文案经作者自查，未跑 LanguageTool 一类工具。
- **未 push**，按要求停在 `main` 本地提交等复核。

## 10. 复现命令

```
# 生成（4 页 + CSV，含内置校验闸门）
C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe -X utf8 E:\破甲工作区\.tmp\36a\build.py

# 独立复核（回读磁盘、重算字符数、逐条对 universe、红线 grep、结构计数）
C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe -X utf8 E:\破甲工作区\.tmp\36a\verify.py
# 完整输出落在 E:\破甲工作区\.tmp\36a\verify-out.txt
```

生成脚本内置闸门在任一条件不满足时把 `VALIDATION` 打成 `FAIL` 并列出原因（title/meta/首段词数越界、H1 缺主词、红线命中、产品链接不足、缺 `/get-a-quote/` 或 `/samples/`、H2 少于 4、表格缺失），当前状态：`VALIDATION: PASS`。
