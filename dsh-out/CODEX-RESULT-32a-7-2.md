# 32a-7-2 合并与缺译来源核对

## 合并

用户确认接受 32a-7 已记录的未通过项后，先提交本地合并。提交前确认 HEAD、MERGE_HEAD 和未解决冲突数均符合要求；35a 未跟踪文件未暂存。第一次合并提交的原始输出：

```text
ae29b1cadde6ff320dcdf54bb44b193cbca1f46f
50e000f9d4c0d2d28134c5a364391d69ffb69bd4 9ec011ab5d41c41c8942ba7417196b7767cc9316
merge: 32a quote calibration and MOQ 250 into main
```

为了同时满足“将本报告和 CSV 提交”和最终 `git log -1` 仍为双父合并提交，完成清单后将两份交付物追加到该本地合并提交；最终提交哈希以最终 `git log -1` 为准，两个父提交和说明不变。未 push、未部署。

## 分析方法和边界

- 输入为 `E:/破甲工作区/32a7-main-rehearsal/scan-all.jsonl` 中 170 个未通过页。获取这些语言页及去重后的 58 个英文对应页，共 228 个，均 HTTP 200；没有请求生产站。
- 在独立实例中用原 29y 扫描器的临时追踪副本重扫 170 页，只增加连续段输出；HTTP、main、残留词数和连续段数与原记录的差异为 0。1,480 个连续段均找到页面文本节点；其中 128 个连续段在同一页面有多个可能位置，清单保留可证实的候选来源，不能据此断言单一位置是唯一触发点。
- 同时检查扫描计数中的孤立英文词。将观察到的节点与英文页面、11 份词典现有值、公开文章/页面字段和本地模板代码交叉核对；数据库来源进行 187 次字段核对，代码来源进行 63 次行号核对（其中 14 次为动态构造人工核对），均无校验错误。
- `出现页面数` 是 170 个语言页中观察到该文本节点、属性或对应现有译值的去重页数，不是出现次数；示例最多列三个路径。19 行为 31b 要求一并列出、但在这 170 页没有观察到的项。
- 94 行含已有译文、借词或词法误报等待核对的记录，已在“来源”列加分析备注；它们不能全部当作缺键。四处图片 alt 属于 31b 要求的补充项，原连续段扫描器本身不扫描 alt。
- 开始取页面时尚未启用数据库只读，WordPress 可能自动写入派生缓存；没有本轮取页前的库快照，因此**不能证明那段时间数据库绝对未变**。本轮未直接执行 UPDATE/INSERT/DELETE；随后已对独立 `packrivo-main-db` 启用运行时 `read_only=1, super_read_only=1`，之后的复扫和数据库查询受只读保护。未操作生产或 32a/33a/33b 容器。

## 清单及验证原始输出

`dsh-out/32a8-missing-strings.csv` 为 UTF-8 BOM CSV，六列依次为英文原文、类型、桶或字段、来源、出现页面数、示例页面。正文中 `post_title` 按允许的 `post_content` 类型列出并在字段列明确标记；博客标题和摘要单列 `blog`，不计入需要翻译的正文。动态键和仅英文路由也在来源备注中说明。

```text
CSV_ROWS 216
TYPE_COUNTS dict=47 postmeta=30 post_content=115 option=0 blog=24
BLOG_FIELDS post_title=12 post_excerpt=12
UNIQUE_ENGLISH 216
ZERO_PAGE_ROWS 19
EXISTING_TRANSLATION_REVIEW_ROWS 94
CSV_DB_SOURCE_CHECKS 187 ISSUES 0
CSV_CODE_SOURCE_CHECKS 63 DYNAMIC_REVIEW 14 ISSUES 0
ARTIFACT_CSV_IMPORT_ROWS 216 HEADER_OK=true
CSV_SHA256 971cbd20981bec7a8b66f97649095c13c1dc51932eb6dbb5ba69c27f9c67566d
TRACE_ROWS 170 METRIC_DIFFS 0 LONG_RUNS 1480
TRACE_RUN_MATCH 1480 MISS 0 AMBIGUOUS 128
```

其中 `option=0` 代表本次确认的节点没有归因到选项字段，不证明网站不存在其他可能相关选项。完整的扫描与 HTML 追踪证据保留在本机 `E:/破甲工作区/32a7-main-rehearsal/`，未纳入 Git。

## 31b 逐条核对

```text
01 count=1 type=dict pages=0 key=More in this category
02 count=1 type=dict pages=0 key=Related reading
03 count=1 type=dict pages=0 key={Topic} | Packrivo
04 count=1 type=post_content pages=0 key=Drawer Box With Ribbon Pull
05 count=1 type=post_content pages=0 key=Corrugated Wine Bottle Shipper
06 count=1 type=post_content pages=0 key=Subscription Mailer Box With Insert
07 count=1 type=post_content pages=0 key=Eco Kraft Cosmetic Box for Small Batches
08 count=1 type=post_content pages=0 key=Magnetic Book-Style Presentation Box
09 count=1 type=post_content pages=0 key=Custom Kraft Soap Box With Window
10 count=1 type=post_content pages=0 key=Jewelry Pouch and Box Set
11 count=1 type=post_content pages=0 key=Kraft Stand-Up Pouch With Window
12 count=1 type=post_content pages=0 key=Jewelry Boxes, Pouches and Bags — Reference Catalogue
13 count=1 type=post_content pages=0 key=Pink Jewelry Storage Box
14 count=1 type=post_content pages=0 key=Jewelry Packaging Box — Ref. 996892
15 count=1 type=post_content pages=0 key=Velvet-Lined Boxes — Reference Catalogue
16 count=1 type=post_content pages=0 key=Velvet-Lined Box — Velvet, Faux Fur
17 count=1 type=dict pages=0 key=For a quote, email us or contact us on WhatsApp.
18 count=1 type=dict pages=0 key=illustrative walkthroughs
19 count=1 type=dict pages=0 key=English
20 count=1 type=dict pages=11 key=Supplier sample room with shelves of rigid gift boxes and jewelry boxes
21 count=1 type=dict pages=11 key=Supplier sample room, Dongguan. Structures are chosen from real samples before artwork starts.
22 count=1 type=dict pages=11 key=Operator feeding a printed proof sheet into a wide-format digital proofing printer
23 count=1 type=dict pages=11 key=Digital proof printing at a supplier in Dongguan.
24 count=1 type=dict pages=11 key=Multi-colour sheet-fed offset printing press in a packaging factory
25 count=1 type=dict pages=11 key=Sheet-fed offset press used for production runs.
26 count=1 type=dict pages=11 key=Finished jewelry boxes packed in export cartons before shipping
27 count=1 type=dict pages=11 key=Finished boxes packed into cartons, Shantou.
28 count=1 type=dict pages=11 key=Supplier photos, used with permission.
31B_COVERAGE 28/28
```

前 19 项在本次 170 个失败页中未观察到，仍按要求列入；包括仅英文路由、仅英文博客、语言选择器、`noscript` 和动态专题标题。`{Topic} | Packrivo` 是说明性模板，不是已证实的单一字典查找键，清单已标明。

## 补译后能否预计 29y 全过

**不能预计全过。** 本清单是可追溯的翻译及人工复核输入，不是通过承诺：

1. 博客标题与摘要按要求排除翻译，但拉丁语种扫描仍可能把首页或博客列表中的英文标题和摘要计入；扫描器会把相邻 HTML 节点合并，例如法语首页的英文博客标题与已翻译的 `Structures et ajustement` 被连成英文连续段。
2. 有既存译文保留 `MOQ: 250 pieces per product/design` 等英文，或包含 `Pinch Bottom Bags`、`self opening square`、`Packaging Corporation of America` 等专名和解释词；这些不是简单增加缺失字典键就能确定修复，需逐项判断译文、专名处理与既有规则。
3. 德语等页面存在 `die person die` 等本族词触发连续段的例子；这是扫描口径或翻译表述问题，补译无关新键无法保证消除。
4. 部分键由实时文章数、产品数或报价拼接。文章数相关调用还会尝试 `{N}` 归一后备键；补固定数字的键并不能保证未来数量变化仍命中。仅英文路由和动态专题模板也不能通过补一条字典键自动扩展为多语言页面。
5. 本轮只导出并标注候选，未进行补译后的全站复扫；128 个多位置段也需要人工复核，不能承诺一次补译后全部达标。原 `/packaging/` 404 是另一个独立问题。
