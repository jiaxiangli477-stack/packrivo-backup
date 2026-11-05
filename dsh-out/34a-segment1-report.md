# 34a 第 1 段交付：Packrivo 英文关键词库（词库扩展 + 清洗 + 聚类）

日期：本轮；执行：deepseek-v4-pro（工作台 #9）｜监工/验收：kimi-k3.1-pro
范围：只交 CSV/MD，未写站、未动词典、未改线上任何文件。
版本：**v2（已按 Claude 复核意见修完 4 处，见 §10）**

## 0. 交付文件

| 文件 | 行数 | 说明 |
|---|---|---|
| `dsh-out/34a-keyword-universe.csv` | 6,101 | 主词库；列：keyword, 来源, 搜索量, 搜索量数据源, **意图, 意图来源**, 品类簇, 修饰词类型, 是否合规, 备注 |
| `dsh-out/34a-dropped.csv` | 5,669 | 全量丢弃审计；列：keyword, 搜索量, 来源, 丢弃原因 |
| `dsh-out/34a-questions.csv` | 303 | 疑问词子集（从主库按 `修饰词类型=question` 切出） |
| `dsh-out/34a-raw/suggest/` | 1,778 次请求 / 14,413 条原始建议 | 联想原始响应 + `index.jsonl` 台账 |
| `dsh-out/34a-raw/semrush-browser/` | 26 文件 | 只读，未改未删（`.tsv` 那份也一并解析了，见 §10.2） |

## 1. 数量与去重

- 原始记录（合并 KMT/Organic/GSC/Bing/Trends/联想后）**11,723** 行
- 清洗后保留 **6,101** 词（丢弃 5,669）
- 归一化去重（小写、单复数折叠、词序无关）后**唯一词 5,917**，折叠掉 184 组同义/变体
- 硬指标 ≥3,000：**达标，超出 2 倍**

## 2. 每源贡献（独立词数，一词多源重复计）

| 来源 | 词数 | 占比 |
|---|---|---|
| Google-suggest-us | 4,975 | 79.3% |
| Bing-suggest-us | 820 | 13.1% |
| semrush-kmt（精确词量） | 584 | 9.3% |
| Google-suggest-au | 229 | 3.7% |
| Google-suggest-ca | 228 | 3.6% |
| Google-suggest-gb | 228 | 3.6% |
| semrush-organic-competitor | 140 | 2.2% |
| gsc | 49 | 0.8% |
| semrush-topic | 43 | 0.7% |
| semrush-organic-self | 4 | 0.1% |
| bing | 2 | 0.0% |

多源交叉词 498 个。各源抓取行：kmt 760 / organic 216 / gsc 142 / bing 12 / suggest 14,413。

## 3. 搜索量覆盖（诚实口径）

- 有真实搜索量的词 **588 / 6,101 = 9.6%**；这 588 个有量词之和 **466,450/月**
  —— 该合计数被 broad-match 头部词拉高（`jewelry box 60,500`、`packaging 33,100`、`mailer box 5,400` 三个词占 21%），**合计数不做任何决策依据，只做覆盖度参考**。
- 无量的 5,513 个词**全部留空**，没有做任何估算赋值
- KD 有值 528 个、CPC 有值 584 个（Semrush 原样搬运，缺口不补）
- 联想接口本身**不返回搜索量**，因此大量新词天然无量——这是数据事实，不是遗漏。段 2 选词按「有量优先 + 无量词仅做长尾铺设」处理。
- 不变量自检（`final-audit.py`）：`有量但无数据源 = 0`、`有数据源但无量 = 0`、`残留 Topics 当量的行 = 0`。

## 4. 意图 / 修饰词分布

意图（C=商业调研，I=信息，T=交易）：C 4,810 / I 980 / T 206 / C/I 89 / I/T 15 / C/T 1
意图来源：`semrush` 613 / `inferred` 5,488

修饰词：head 2,736 / material 1,285 / wholesale-bulk 446 / custom 411 / price-cost 301 / question 272 / supplier-manufacturer 240 / template-guide 217 / with-logo 95 / size-dimension 79 / location 78 / finish-craft 69 / comparison 28 / moq 15

## 5. 聚类：37 簇（29 簇有真实量）

| # | 簇 | 词数 | 代表词 | 量 | 量来源 | 簇内有量词 |
|---|---|---|---|---|---|---|
| 1 | jewelry-box | 857 | jewelry box | 60,500 | Semrush KMT | 69/857 |
| 2 | packaging-supply-general | 469 | packaging | 33,100 | Semrush KMT | 101/469 |
| 3 | mailer-box | 286 | mailer box | 5,400 | Semrush KMT | 68/286 |
| 4 | paper-bag | 370 | custom paper bags | 1,900 | Semrush KMT | 48/370 |
| 5 | corrugated-carton | 209 | box corrugated boxes | 4,400 | Semrush KMT | 4/209 |
| 6 | tissue-paper | 239 | custom tissue paper | 2,900 | Semrush KMT | 28/239 |
| 7 | bag-generic | 58 | custom bags with logo | 2,900 | Semrush KMT | 5/58 |
| 8 | food-box | 15 | candy boxes | 2,400 | Semrush KMT | 5/15 |
| 9 | magnetic-box | 71 | magnetic closure gift boxes | 1,900 | Semrush KMT | 41/71 |
| 10 | gable-box | 9 | gable boxes | 1,900 | Semrush KMT | 1/9 |
| 11 | dieline-dimensions | 159 | dieline | 3,600 | Semrush KMT | 12/159 |
| 12 | rigid-box | 207 | rigid boxes | 1,600 | Semrush KMT | 28/207 |
| 13 | print-finish | 15 | foil stamping | 1,600 | Semrush KMT | 7/15 |
| 14 | paperboard-stock | 111 | custom cardboard boxes | 1,600 | Semrush KMT | 2/111 |
| 15 | candle-box | 291 | custom candle boxes | 1,000 | Semrush KMT | 33/291 |
| 16 | soap-box | 264 | custom soap boxes | 1,000 | Semrush KMT | 3/264 |
| 17 | folding-carton | 70 | folding cartons | 880 | Semrush KMT | 17/70 |
| 18 | pr-box | 129 | pr box | 880 | Semrush KMT | 12/129 |
| 19 | insert | 299 | packaging inserts | 720 | Semrush KMT | 14/299 |
| 20 | subscription-box | 251 | custom subscription boxes | 720 | Semrush KMT | 11/251 |
| 21 | cosmetic-box | 295 | custom cosmetic packaging | 590 | Semrush KMT | 26/295 |
| 22 | apparel-box | 69 | custom apparel boxes | 320 | Semrush KMT | 3/69 |
| 23 | gift-box | 378 | custom presentation boxes | 260 | Semrush KMT | 26/378 |
| 24 | display-box | 203 | display boxes for products | 170 | Semrush KMT | 2/203 |
| 25 | jewelry-pouch | 152 | suede pouch | 140 | Semrush KMT | 18/152 |
| 26 | label-sticker | 72 | labels for tapered containers | 110 | Semrush Organic(self) | 1/72 |
| 27 | paper-envelope | 39 | non machinable envelopes | 70 | Semrush Organic(self) | 1/39 |
| 28 | wine-bottle-shipper | 32 | magnetic wine box | 30 | Semrush KMT | 1/32 |
| 29 | tube-box | 10 | rigid tube box | 30 | Semrush KMT | 1/10 |
| 30 | kraft-box | 208 | kraft boxes wholesale | – | – | 0/208 |
| 31 | perfume-box | 151 | perfume packaging | – | – | 0/151 |
| 32 | stand-up-pouch | 78 | bags and pouches | – | – | 0/78 |
| 33 | poly-mailer | 19 | flap lock poly bags | – | – | 0/19 |
| 34 | belly-band | 7 | paper belly band custom | – | – | 0/7 |
| 35 | beverage-box | 5 | juice dieline | – | – | 0/5 |
| 36 | hexagon-box | 3 | hexagon dieline | – | – | 0/3 |
| 37 | unclustered | 1 | sos save our supplies | – | – | 0/1 |

**代表词选举规则**：先排除长尾（>4 词的词不能当页面主词），再比量来源权威度（KMT 精确词量 > Organic > Topics 条数 > 无量），再比量值，再比来源广度，最后比词长（短者胜）。证据：`report.py` 的 `vrank()` 与 `34a-keyword-universe.csv` 的「搜索量数据源」列。

## 6. 丢弃审计（top12）

无包装核心词（联想噪声）3,092 / a-z 联想残尾: me 334 / C 端消费意图 204 / 品牌缩写·垃圾尾词 75 / 品牌·导航词: amazon 59 / a-z 残尾: free 54 / **非目标市场地理词: india 50** / 联想串字母残片 41 / a-z 残尾: sale 41 / 导航·免费·下载: nearby 40 / DIY·手工 33 / a-z 残尾: code 32

丢弃口径（可复核）：
1. **包装核心词硬门**：必须命中包装名词（box/bag/pouch/label/insert/dieline…）、材料即产品（tissue paper/kraft paper/glassine/chipboard…）或技术词（FEFCO/ISTA/ECT/RSC/edge crush…）之一；`foil stamping` 这类纯工艺词只有带真实搜索量才收。
2. **C 端消费意图**：`subscription boxes for teens`、`veggie subscription boxes` 这类「买盒子里的东西」的词，在没有 custom/wholesale/supplier/packaging 等 B2B 信号时丢弃。
3. **a-z 联想残尾**：`jewelry box ycl`、`jewelry box xenos` 这类品牌缩写/无意义短尾丢弃；`logo`/`bulk`/`lid`/`tag`/`width` 等合法短尾在白名单内保留。
4. **品牌/导航**：amazon/etsy/michaels/hobby lobby/tiffany/walmart/temu/shein/kmart 等丢弃。
5. **竞品品牌词**：`noissue` / `pakfactory` / `packlane` / `packhelp` / `arka` / `packwire` / `boxup` / `packmojo` 等同行品牌词单独一类丢弃（原因写「竞品品牌词」），不与导航词混计。
6. **非目标市场地名**：mumbai/delhi/karachi/lahore/dhaka/dubai/durban/jakarta… 及 `in india`/`in pakistan` 丢弃；us/uk/ca/au 国家级保留。

## 7. 风险词（`是否合规 = N`，5 个，留给 Claude 裁）

`cigarette packaging boxes`、`pre roll packaging`、`custom cbd oil boxes`、`custom cbd subscription boxes`、`subscription vape box`
——保留在库内并标 N，段 2 不分配页面。要删就说一声，脚本一行就清掉。

## 8. 已知缺口（段 2 前需要知道的）

1. `jewelry-box` 857 词过宽，混了 C 端收纳盒、展示盒；段 2 只取其中 B2B 子集（custom/printed/wholesale/supplier 修饰）。
2. 8 个簇零真实量（kraft-box / perfume-box / stand-up-pouch / poly-mailer / belly-band / beverage-box / hexagon-box / unclustered）：词是有的，量没有，页面铺设时按无量长尾处理。
3. `kraft-box`、`hexagon-box`、`packaging-supply-general` 的部分词只剩「主题内含 N 个关键词」这条备注，**没有搜索量**——主题条数不是量，段 2 不许拿它当主词依据。
4. 联想词占 79% 且全无量——覆盖广度靠联想，决策权重靠 Semrush/GSC。

## 9. 复现命令

```powershell
$py='C:\Users\21996\.dsh\dsh-runtimes\dsh-primary-runtime\dependencies\python\python.exe'
$env:PYTHONIOENCODING='utf-8'
& $py 'E:\破甲工作区\.tmp\34a\gen-seeds.py'        # 种子 150 条
& $py 'E:\破甲工作区\.tmp\34a\run-suggest.py'      # 1,778 次联想，落 34a-raw/suggest/
& $py 'E:\破甲工作区\.tmp\34a\build-universe.py'   # 合并+清洗+聚类 → 3 个 CSV
& $py 'E:\破甲工作区\.tmp\34a\report.py'           # 验收数字
& $py 'E:\破甲工作区\.tmp\34a\final-audit.py'      # 完整性自检
```

## 10. Claude 复核 4 处整改（本轮已落地）

| # | 复核意见 | 改法 | 验证证据 |
|---|---|---|---|
| 1 | Semrush Topics 的数字是主题内关键词个数，不是搜索量 | Topics 分支改为 `add(name, 'semrush-topic', None, '', note='Semrush 主题内含 N 个关键词（主题条数，非搜索量）')`；凡由 Topics 带入的词一律无量 | `残留 Topics 当量的行 = 0`；`kraft paper boxes` 量空、备注写「主题内含 2.2K 个关键词」；`hexagon box packaging` 同 |
| 2 | `gable boxes` 真实搜索量 1900，来源 Semrush KMT | 根因不是数值写错，而是 `kmt-custom-jewelry-boxes-volume.tsv` **制表符分隔**，旧解析器只按 `|` 切 → 整份 101 行被静默跳过。改成 `\t`/`|` 双分隔 + 跳表头 | `gable boxes` 现为 `1900 / Semrush KMT(seed=custom-jewelry-boxes-volume)`；kmt 抓取行 660 → **760** |
| 3 | 新增「意图来源」列；`inferred` 行不许乱标 T | 新增列取 `semrush`（该行有 Semrush 原始意图）/`inferred`；`infer_intent` 的 T 收紧为只认 `buy/order/for sale/price(pricing)/cost/cheap/quote/budget/wholesale/bulk/supplier/vendor/manufacturer/how much/moq`，其余落到 C | 意图 T 由 3,779 降到 **206**；`意图来源: semrush 613 / inferred 5,488`；`mailer boxes` 之类纯品类名为 C |
| 4 | `noissue custom tissue paper`、`noissue dieline` 是竞品品牌词 | 新增 `COMPETITOR` 表（noissue/pakfactory/packlane/packhelp/arka/packwire/boxup/packmojo…），在 OFFTOPIC 之前判定，原因写「竞品品牌词」 | `noissue custom tissue paper` → 丢弃表，原因「竞品品牌词: noissue」 |

**连带修的两处数据质量问题（不在复核清单内，但会让段 2 选错主词，一并处理）**：
- 代表词选举加入「>4 词不做代表」前置条件 —— 否则 tsv 里的 `best packaging for homemade chocolate gifts`（3,600）会当 `food-box` 簇代表词，实际代表词现为 `candy boxes 2,400`。
- 新增 `GEO_NOISE`（pakistan/india/dhaka/karachi/dubai/jakarta… 全表见脚本）—— 否则 `kraft boxes pakistan` 会当 `kraft-box` 簇代表词，现为 `kraft boxes wholesale`。
