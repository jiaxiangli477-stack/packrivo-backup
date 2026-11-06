# 34a-2 本地审查与验收记录

**验收已执行，整体未通过：11 语言翻译闸门失败；141 页渲染存在 5 个字段差异，整页标准词扫描 4 处。** 不部署、不合并、不 push。

## 分支与环境

- 原 `34a` 为 `615a399`，已 rebase 到 `e546ff3`；rebase 后父提交为 `e546ff3`。相对 main 没有词典变更。
- 原有未提交的 `dsh-out/CODEX-RESULT-34a.md` 已备份并恢复，SHA-256 为 `c643039347b5a570b3f7a11af472ddd2510f762d29458229b550c20f243644d4`；不纳入本任务提交。
- 独立 `packrivo-34a-*` 从 `packrivo-main-*` 数据卷只读复制后起库，没有操作 main/32a/33a/33b 容器。快照 40 张表检查 0 错误，448 标题确认包含 32a-6；快照 SQL SHA-256 为 `7b35245f49cc7899ea3d17fd8551de6ae2e6bbdcbd561878a1e91639ec68b3b2`。
- 复制 main 数据卷时其数据库仍在运行；40 张表检查和 448 抽查不等于事务一致性快照证明。上线前仍需用受控的最新备份重新彩排。
- 首次修改前的 34a 完整数据库备份 `E:/破甲工作区/34a2-rehearsal/pre-migration-full.sql` SHA-256 为 `4d21157ac7e383ce2b236219157084a24b252dba9a95128e9260e626a11c8dc0`。

## 审查及已实现更改

- 移除 indexable 的 open_graph / twitter 字段写入；版本 3 备份记录实际变化、meta 是否存在、indexable NULL 和记录校验，回滚必须传入位置参数并在有并发变化时整体中止。
- MySQL 的 Yoast `updated_at` 有 `ON UPDATE CURRENT_TIMESTAMP`。首次演练发现该列自动变化；修订后对该列自赋值以保持原值；[MySQL 官方手册](https://dev.mysql.com/doc/refman/26.7/en/timestamp-initialization.html)说明可显式赋当前值阻止自动更新，**修订版在本实例的回滚和范围校验中已验证**。
- WP-CLI `eval-file` 会拦截未知长选项，因此新增 `34a-cli.php` 包装命令，实际支持 `--force-url=/collapsible-magnetic-gift-box/`；其他路径报错，只有指定 URL 的 title 可强制。
- hub 标题缺失或为空时主题虽可回退 Yoast，迁移仍按冲突停止，避免无授权创建投影；本次独立基线中的 7 个目标 hub 均有非空 title。
- 独立代码审查后补充：排名门同时核对实际生效的 hub title 并在事务锁内复核；独立规划陈旧 indexable；备份增加记录数与 SHA-256 完整性校验；实际迁移计划绑定同一份翻译清单及过闸前后核验的 SHA-256；RUNBOOK 还逐种核对过闸前后及实例使用的 11 份词典哈希。上述补强已在 34a 实例完成主流程验证，另在本机隔离副本完成负向测试。
- `/get-a-quote/` 的三处主题字符串按词表更新并以 `source=theme` 追加清单。首页、`/products/`、`/samples/` 的 H1 保留。目标 title/meta 中 MOQ 500 行数为 0。
- 更新 RUNBOOK：34a 写入前先过翻译闸门，必须使用明确备份位置参数回滚；`/samples/` 父主题 H1 留待后续。

## 先前版本演练（不是最终版本通过证据）

```text
map_rows=141 resolved=141 missing=0
gate_rank8=pass (8/8 strip_eq)
NEGATIVE_RANK_EXIT 1: GATE_FAIL /labels/; nothing changed
NEGATIVE_FORCE_EXIT 1: Only /collapsible-magnetic-gift-box/ may be forced
NEGATIVE_ROLLBACK_NO_PATH_EXIT 1: Explicit backup path required
NEGATIVE_TESTS_DB_FIELDS_UNCHANGED True
changed=549 meta=216 h1=128 indexable=205
second changed=0
rollback changed=549 meta=216 h1=128 indexable=205
ROLLBACK_CHECK / EXACT=false（其他 indexable 字段哈希改变，原因是 updated_at 自动更新）
```

因此先前版本的回滚不算通过。之后从完整备份恢复独立 34a 库，Docker 恢复后重测，141 页基线与写前快照完全一致，再执行下列最终序列。

## 34a 容器最终迁移和回滚原始输出

第一次修订版演练的全库检查发现 WordPress 启动时刷新了两组主题缓存及超时，共 4 条 option 记录；已在独立 34a 库恢复原值及 ID，并在 CLI 包装器针对性屏蔽该缓存过期写入。`THEME_TIMEOUT_OPTION_GUARD PASS`，再次演练前后 37 张非目标表校验和均一致。

```text
=== DRY_RUN exit=0 ===
moq_500_normalized=0
manifest_coverage=pass rows=350 sha256=a8facea7260e91dc18aac50df387819486a712346d59b020f11275cd923e10d1
map=/tmp/34a2/map.csv
map_rows=141 resolved=141 missing=0
gate_rank8=pass (8/8 strip_eq)
  gate /labels/ strip_eq
  gate /foldable-gift-boxes/ strip_eq
  gate /jewelry-boxes/ strip_eq
  gate /inserts/ strip_eq
  gate /products/ strip_eq
  gate /services/ strip_eq
  gate /guide-jewelry-insert-fit/ strip_eq
  gate /product-magnetic-jewelry-box/ strip_eq
title: update=53 drift_update=12 skip=75 conflict=0 empty_new=0
meta:  update=140 skip=0
hub:   title update=4 drift_update=0 skip=3 conflict=0 | desc update=7 skip=0
h1:    update=127 drift_update=1 skip=12 conflict=0 no_content_h1=0
changed_pages=140 changed_fields=549 primary=344 indexable=205
DRIFT /mailer-boxes/ | h1 | db=Custom Mailer Boxes: Printed, MOQ 250 | csv_old=Custom Mailer Boxes: Printed, MOQ 500
DRIFT /candle-gift-set-box/ | title | db=Candle Gift Set Box: Insert + Sleeve (MOQ 250) | Packrivo | csv_old=Candle Gift Set Box: Insert + Sleeve (MOQ 500) | Packrivo
DRIFT /collapsible-magnetic-gift-box/ | title_forced | db=Collapsible Magnetic Gift Box (MOQ 250) | Packrivo | csv_old=Custom Collapsible Magnetic Gift Boxes | Packrivo
DRIFT /corrugated-wine-shipper/ | title | db=Corrugated Wine Bottle Shipper (MOQ 250) | Packrivo | csv_old=Corrugated Wine Bottle Shipper (MOQ 500) | Packrivo
DRIFT /drawer-box-with-ribbon/ | title | db=Drawer Box With Ribbon Pull (MOQ 250) | Packrivo | csv_old=Drawer Box With Ribbon Pull (MOQ 500) | Packrivo
DRIFT /eco-kraft-cosmetic-box/ | title | db=Eco Kraft Cosmetic Box for Small Batches (MOQ 250) | Packrivo | csv_old=Eco Kraft Cosmetic Box for Small Batches (MOQ 500) | Packrivo
DRIFT /jewelry-pouch-and-box-set/ | title | db=Jewelry Pouch and Box Set (MOQ 250) | Packrivo | csv_old=Jewelry Pouch and Box Set (MOQ 500) | Packrivo
DRIFT /kraft-stand-up-pouch/ | title | db=Kraft Stand-Up Pouch With Window (MOQ 250) | Packrivo | csv_old=Kraft Stand-Up Pouch With Window (MOQ 500) | Packrivo
DRIFT /magnetic-book-style-box/ | title | db=Magnetic Book-Style Presentation Box (MOQ 250) | Packrivo | csv_old=Magnetic Book-Style Presentation Box (MOQ 500) | Packrivo
DRIFT /paper-insert-card/ | title | db=Printed Insert Cards and Thank-You Cards (MOQ 250) | Packrivo | csv_old=Printed Insert Cards and Thank-You Cards (MOQ 500) | Packrivo
DRIFT /printed-tissue-paper/ | title | db=Custom Printed Tissue Paper (MOQ 250) | Packrivo | csv_old=Custom Printed Tissue Paper (MOQ 500) | Packrivo
DRIFT /soap-box-with-window/ | title | db=Custom Kraft Soap Box with Window (MOQ 250) | Packrivo | csv_old=Custom Kraft Soap Box with Window (MOQ 500) | Packrivo
DRIFT /subscription-mailer-with-insert/ | title | db=Subscription Mailer Box With Insert (MOQ 250) | Packrivo | csv_old=Subscription Mailer Box With Insert (MOQ 500) | Packrivo
DRIFT /packaging-moq/ | title | db=Custom Packaging MOQ: 250 Pieces Explained | Packrivo | csv_old=Custom Packaging MOQ: 500 Pieces Explained | Packrivo
SKIP / | h1 | retained by explicit review decision
SKIP /products/ | h1 | retained by explicit review decision
SKIP /get-a-quote/ | title/meta/h1 | theme-owned; no database fields changed
SKIP /samples/ | h1 | retained by explicit review decision
new_strings_new=0 new_strings_total=347
force_url=/collapsible-magnetic-gift-box/
dry_run=1 (no database change, no backup file)
Success: 34a dry-run finished.

=== APPLY_1 exit=0 ===
moq_500_normalized=0
manifest_coverage=pass rows=350 sha256=a8facea7260e91dc18aac50df387819486a712346d59b020f11275cd923e10d1
map=/tmp/34a2/map.csv
map_rows=141 resolved=141 missing=0
gate_rank8=pass (8/8 strip_eq)
  gate /labels/ strip_eq
  gate /foldable-gift-boxes/ strip_eq
  gate /jewelry-boxes/ strip_eq
  gate /inserts/ strip_eq
  gate /products/ strip_eq
  gate /services/ strip_eq
  gate /guide-jewelry-insert-fit/ strip_eq
  gate /product-magnetic-jewelry-box/ strip_eq
title: update=53 drift_update=12 skip=75 conflict=0 empty_new=0
meta:  update=140 skip=0
hub:   title update=4 drift_update=0 skip=3 conflict=0 | desc update=7 skip=0
h1:    update=127 drift_update=1 skip=12 conflict=0 no_content_h1=0
changed_pages=140 changed_fields=549 primary=344 indexable=205
DRIFT /mailer-boxes/ | h1 | db=Custom Mailer Boxes: Printed, MOQ 250 | csv_old=Custom Mailer Boxes: Printed, MOQ 500
DRIFT /candle-gift-set-box/ | title | db=Candle Gift Set Box: Insert + Sleeve (MOQ 250) | Packrivo | csv_old=Candle Gift Set Box: Insert + Sleeve (MOQ 500) | Packrivo
DRIFT /collapsible-magnetic-gift-box/ | title_forced | db=Collapsible Magnetic Gift Box (MOQ 250) | Packrivo | csv_old=Custom Collapsible Magnetic Gift Boxes | Packrivo
DRIFT /corrugated-wine-shipper/ | title | db=Corrugated Wine Bottle Shipper (MOQ 250) | Packrivo | csv_old=Corrugated Wine Bottle Shipper (MOQ 500) | Packrivo
DRIFT /drawer-box-with-ribbon/ | title | db=Drawer Box With Ribbon Pull (MOQ 250) | Packrivo | csv_old=Drawer Box With Ribbon Pull (MOQ 500) | Packrivo
DRIFT /eco-kraft-cosmetic-box/ | title | db=Eco Kraft Cosmetic Box for Small Batches (MOQ 250) | Packrivo | csv_old=Eco Kraft Cosmetic Box for Small Batches (MOQ 500) | Packrivo
DRIFT /jewelry-pouch-and-box-set/ | title | db=Jewelry Pouch and Box Set (MOQ 250) | Packrivo | csv_old=Jewelry Pouch and Box Set (MOQ 500) | Packrivo
DRIFT /kraft-stand-up-pouch/ | title | db=Kraft Stand-Up Pouch With Window (MOQ 250) | Packrivo | csv_old=Kraft Stand-Up Pouch With Window (MOQ 500) | Packrivo
DRIFT /magnetic-book-style-box/ | title | db=Magnetic Book-Style Presentation Box (MOQ 250) | Packrivo | csv_old=Magnetic Book-Style Presentation Box (MOQ 500) | Packrivo
DRIFT /paper-insert-card/ | title | db=Printed Insert Cards and Thank-You Cards (MOQ 250) | Packrivo | csv_old=Printed Insert Cards and Thank-You Cards (MOQ 500) | Packrivo
DRIFT /printed-tissue-paper/ | title | db=Custom Printed Tissue Paper (MOQ 250) | Packrivo | csv_old=Custom Printed Tissue Paper (MOQ 500) | Packrivo
DRIFT /soap-box-with-window/ | title | db=Custom Kraft Soap Box with Window (MOQ 250) | Packrivo | csv_old=Custom Kraft Soap Box with Window (MOQ 500) | Packrivo
DRIFT /subscription-mailer-with-insert/ | title | db=Subscription Mailer Box With Insert (MOQ 250) | Packrivo | csv_old=Subscription Mailer Box With Insert (MOQ 500) | Packrivo
DRIFT /packaging-moq/ | title | db=Custom Packaging MOQ: 250 Pieces Explained | Packrivo | csv_old=Custom Packaging MOQ: 500 Pieces Explained | Packrivo
SKIP / | h1 | retained by explicit review decision
SKIP /products/ | h1 | retained by explicit review decision
SKIP /get-a-quote/ | title/meta/h1 | theme-owned; no database fields changed
SKIP /samples/ | h1 | retained by explicit review decision
new_strings_new=0 new_strings_total=347
force_url=/collapsible-magnetic-gift-box/
backup=/tmp/34a2/backup/34a-before-20261106-044653-ac2cf42ec4.csv rows=549 sha256=5847a5099ad4b2d572783132ebe16219594138cd6ca5a3b9e5fd92994e3f3888
changed=549 meta=216 h1=128 indexable=205
after_check title_bad=0 meta_bad=0 h1_bad=0 hub_bad=0 indexable_bad=0
new_strings=/tmp/34a2/34a-new-strings.csv new=0 total=347
Success: 34a applied: pages=140 fields=549

=== COPY_BACKUP exit=0 ===


=== APPLY_2 exit=0 ===
moq_500_normalized=0
manifest_coverage=pass rows=350 sha256=a8facea7260e91dc18aac50df387819486a712346d59b020f11275cd923e10d1
map=/tmp/34a2/map.csv
map_rows=141 resolved=141 missing=0
gate_rank8=pass (8/8 strip_eq)
  gate /labels/ strip_eq
  gate /foldable-gift-boxes/ strip_eq
  gate /jewelry-boxes/ strip_eq
  gate /inserts/ strip_eq
  gate /products/ strip_eq
  gate /services/ strip_eq
  gate /guide-jewelry-insert-fit/ strip_eq
  gate /product-magnetic-jewelry-box/ strip_eq
title: update=0 drift_update=0 skip=140 conflict=0 empty_new=0
meta:  update=0 skip=140
hub:   title update=0 drift_update=0 skip=7 conflict=0 | desc update=0 skip=7
h1:    update=0 drift_update=0 skip=140 conflict=0 no_content_h1=0
changed_pages=0 changed_fields=0 primary=0 indexable=0
SKIP / | h1 | retained by explicit review decision
SKIP /products/ | h1 | retained by explicit review decision
SKIP /get-a-quote/ | title/meta/h1 | theme-owned; no database fields changed
SKIP /samples/ | h1 | retained by explicit review decision
new_strings_new=0 new_strings_total=347
force_url=/collapsible-magnetic-gift-box/
changed=0
changed_pages=0 — nothing to write (already applied).
Success: 34a finished: idempotent no-op.

=== ROLLBACK exit=0 ===
rollback_file=/tmp/34a2/backup/34a-before-20261106-044653-ac2cf42ec4.csv records=549 pending=549 unchanged=0
changed=549 meta=216 h1=128 indexable=205
Success: 34a rollback finished.

=== PROBE_AFTER_ROLLBACK exit=0 ===
probe_pages=141 sha256=6c056a5b03ac4daf15a521e2c0f933efefeb443648fac19e9c5e06b28eaa92eb

=== COPY_ROLLBACK_PROBE exit=0 ===


ROLLBACK_CHECK / EXACT=true
ROLLBACK_CHECK /labels/ EXACT=true
ROLLBACK_CHECK /jewelry-boxes/ EXACT=true
ROLLBACK_CHECK /product-magnetic-jewelry-box/ EXACT=true
ROLLBACK_CHECK /collapsible-magnetic-gift-box/ EXACT=true
ROLLBACK_ALL_PAGES 141 MISMATCHES 0
=== APPLY_AFTER_ROLLBACK exit=0 ===
moq_500_normalized=0
manifest_coverage=pass rows=350 sha256=a8facea7260e91dc18aac50df387819486a712346d59b020f11275cd923e10d1
map=/tmp/34a2/map.csv
map_rows=141 resolved=141 missing=0
gate_rank8=pass (8/8 strip_eq)
  gate /labels/ strip_eq
  gate /foldable-gift-boxes/ strip_eq
  gate /jewelry-boxes/ strip_eq
  gate /inserts/ strip_eq
  gate /products/ strip_eq
  gate /services/ strip_eq
  gate /guide-jewelry-insert-fit/ strip_eq
  gate /product-magnetic-jewelry-box/ strip_eq
title: update=53 drift_update=12 skip=75 conflict=0 empty_new=0
meta:  update=140 skip=0
hub:   title update=4 drift_update=0 skip=3 conflict=0 | desc update=7 skip=0
h1:    update=127 drift_update=1 skip=12 conflict=0 no_content_h1=0
changed_pages=140 changed_fields=549 primary=344 indexable=205
DRIFT /mailer-boxes/ | h1 | db=Custom Mailer Boxes: Printed, MOQ 250 | csv_old=Custom Mailer Boxes: Printed, MOQ 500
DRIFT /candle-gift-set-box/ | title | db=Candle Gift Set Box: Insert + Sleeve (MOQ 250) | Packrivo | csv_old=Candle Gift Set Box: Insert + Sleeve (MOQ 500) | Packrivo
DRIFT /collapsible-magnetic-gift-box/ | title_forced | db=Collapsible Magnetic Gift Box (MOQ 250) | Packrivo | csv_old=Custom Collapsible Magnetic Gift Boxes | Packrivo
DRIFT /corrugated-wine-shipper/ | title | db=Corrugated Wine Bottle Shipper (MOQ 250) | Packrivo | csv_old=Corrugated Wine Bottle Shipper (MOQ 500) | Packrivo
DRIFT /drawer-box-with-ribbon/ | title | db=Drawer Box With Ribbon Pull (MOQ 250) | Packrivo | csv_old=Drawer Box With Ribbon Pull (MOQ 500) | Packrivo
DRIFT /eco-kraft-cosmetic-box/ | title | db=Eco Kraft Cosmetic Box for Small Batches (MOQ 250) | Packrivo | csv_old=Eco Kraft Cosmetic Box for Small Batches (MOQ 500) | Packrivo
DRIFT /jewelry-pouch-and-box-set/ | title | db=Jewelry Pouch and Box Set (MOQ 250) | Packrivo | csv_old=Jewelry Pouch and Box Set (MOQ 500) | Packrivo
DRIFT /kraft-stand-up-pouch/ | title | db=Kraft Stand-Up Pouch With Window (MOQ 250) | Packrivo | csv_old=Kraft Stand-Up Pouch With Window (MOQ 500) | Packrivo
DRIFT /magnetic-book-style-box/ | title | db=Magnetic Book-Style Presentation Box (MOQ 250) | Packrivo | csv_old=Magnetic Book-Style Presentation Box (MOQ 500) | Packrivo
DRIFT /paper-insert-card/ | title | db=Printed Insert Cards and Thank-You Cards (MOQ 250) | Packrivo | csv_old=Printed Insert Cards and Thank-You Cards (MOQ 500) | Packrivo
DRIFT /printed-tissue-paper/ | title | db=Custom Printed Tissue Paper (MOQ 250) | Packrivo | csv_old=Custom Printed Tissue Paper (MOQ 500) | Packrivo
DRIFT /soap-box-with-window/ | title | db=Custom Kraft Soap Box with Window (MOQ 250) | Packrivo | csv_old=Custom Kraft Soap Box with Window (MOQ 500) | Packrivo
DRIFT /subscription-mailer-with-insert/ | title | db=Subscription Mailer Box With Insert (MOQ 250) | Packrivo | csv_old=Subscription Mailer Box With Insert (MOQ 500) | Packrivo
DRIFT /packaging-moq/ | title | db=Custom Packaging MOQ: 250 Pieces Explained | Packrivo | csv_old=Custom Packaging MOQ: 500 Pieces Explained | Packrivo
SKIP / | h1 | retained by explicit review decision
SKIP /products/ | h1 | retained by explicit review decision
SKIP /get-a-quote/ | title/meta/h1 | theme-owned; no database fields changed
SKIP /samples/ | h1 | retained by explicit review decision
new_strings_new=0 new_strings_total=347
force_url=/collapsible-magnetic-gift-box/
backup=/tmp/34a2/backup/34a-before-20261106-044706-0ecb7c0c89.csv rows=549 sha256=4f9bee8100a9545bdf2969d74a2d960571bf86e79d3073a94308bdf547ce3426
changed=549 meta=216 h1=128 indexable=205
after_check title_bad=0 meta_bad=0 h1_bad=0 hub_bad=0 indexable_bad=0
new_strings=/tmp/34a2/34a-new-strings.csv new=0 total=347
Success: 34a applied: pages=140 fields=549

=== COPY_FINAL_BACKUP exit=0 ===


=== PROBE_AFTER_APPLY exit=0 ===
probe_pages=141 sha256=5d2bb17f8faeb0d795ab9c43488a537d75eee86e288185bae023a8d2dec73b1a

=== COPY_APPLY_PROBE exit=0 ===


SCOPE_CHECK_PAGES 141 UNEXPECTED_FIELD_CHANGES 0
TABLE_SCOPE total=40 protected=37 unexpected_changed=0
```

容器曾在抓取期间失联；恢复后的最终包装器复测和真实容器渲染结果见下一节。

## 本机补充演练（不替代 34a 容器验收）

Docker 不可用期间，在本机临时独立 WordPress 7.1.4 / MySQL 26.7 中导入上述 `pre-migration-full.sql`。本机副本与原 34a 基线的 141 页正文、四类 SEO meta、indexable title/description、其他 post/meta 字段哈希均一致；indexable 其他列的基线哈希有差异，原因尚未核实，因此不把本机结果视为用户要求的容器结果。仅监听本机，演练后已关闭两个本机端口。

```text
FILE native-v3-apply1.log
manifest_coverage=pass rows=350 sha256=a8facea7260e91dc18aac50df387819486a712346d59b020f11275cd923e10d1
gate_rank8=pass (8/8 strip_eq)
backup=D:\Codex\34a2-native-test\backups-v3/34a-before-20261106-040730-5950e9b5a7.csv rows=549 sha256=9542d5eef6738175bc18b6d271b9661be614f72b40432be1f2a08b4ee75c5112
changed=549 meta=216 h1=128 indexable=205
after_check title_bad=0 meta_bad=0 h1_bad=0 hub_bad=0 indexable_bad=0
Success: 34a applied: pages=140 fields=549
FILE native-v3-apply2.log
manifest_coverage=pass rows=350 sha256=a8facea7260e91dc18aac50df387819486a712346d59b020f11275cd923e10d1
gate_rank8=pass (8/8 strip_eq)
changed=0
Success: 34a finished: idempotent no-op.
FILE native-v3-rollback.log
rollback_file=D:\Codex\34a2-native-test\backups-v3/34a-before-20261106-040730-5950e9b5a7.csv records=549 pending=549 unchanged=0
changed=549 meta=216 h1=128 indexable=205
Success: 34a rollback finished.
FILE native-v3-apply3.log
manifest_coverage=pass rows=350 sha256=a8facea7260e91dc18aac50df387819486a712346d59b020f11275cd923e10d1
gate_rank8=pass (8/8 strip_eq)
backup=D:\Codex\34a2-native-test\backups-v3/34a-before-20261106-040743-f567905ab3.csv rows=549 sha256=7dec70f26463e7fc5286675684e0bae8a97da52015370d58303ec178f51912b9
changed=549 meta=216 h1=128 indexable=205
after_check title_bad=0 meta_bad=0 h1_bad=0 hub_bad=0 indexable_bad=0
Success: 34a applied: pages=140 fields=549
NATIVE_ROLLBACK_CHECK / EXACT True
NATIVE_ROLLBACK_CHECK /labels/ EXACT True
NATIVE_ROLLBACK_CHECK /jewelry-boxes/ EXACT True
NATIVE_ROLLBACK_CHECK /product-magnetic-jewelry-box/ EXACT True
NATIVE_ROLLBACK_CHECK /collapsible-magnetic-gift-box/ EXACT True
NATIVE_ROLLBACK_ALL 141 MISMATCHES 0
NATIVE_SCOPE_CHECK pages=141 unexpected=0
```

进一步使用同一 SQL 基线在第二个本机隔离副本中验证新增保护（真实 WordPress/MySQL，仍不是 34a 容器）：

```text
NATIVE_RANK_HUB_NEGATIVE PASS
NATIVE_MANIFEST_HASH_NEGATIVE PASS
NATIVE_MANIFEST_COVERAGE_NEGATIVE PASS
NATIVE_NEGATIVE_TESTS_FIELDS_UNCHANGED PASS
NATIVE_INDEX_ONLY_REPAIR changed=1 PASS
NATIVE_INDEX_ONLY_SECOND changed=0 PASS
NATIVE_TRUNCATED_BACKUP_NEGATIVE PASS
NATIVE_ROLLBACK_CHECK pages=141 mismatches=0
NATIVE_SCOPE_CHECK pages=141 unexpected=0
```

其中分别人为制造 hub 标题漂移、清单哈希不符、缺少计划键、indexable 单列陈旧和完整行截断，确认中止或仅修复目标列；测试后回滚及其他字段核对为 0 差异。

最终缓存保护版另在本机独立副本重跑：`NATIVE_OPTION_GUARD existing_timeout=PASS stale_data=PASS missing_data=PASS expired_read=PASS`；549 → 0 → 回滚 549 → 再次 549，141 页非目标字段 `unexpected=0`，整库 40 张表中除 3 张目标表以外的 37 张表 `unexpected_changed=0`。最终包装器已在 34a 容器中重跑，结果见下一节；本机缺失与过期缓存夹具仍为补充证据。

又在本机隔离库中人为制造一组主题缓存缺失、另一组缓存版本过期，在这些负向夹具下完整重跑，保护范围仍通过：

```text
NATIVE_PATTERN_FIXTURES missing=1 stale_version=1
NATIVE_ROLLBACK_CHECK pages=141 mismatches=0
NATIVE_SCOPE_CHECK pages=141 unexpected=0
NATIVE_TABLE_SCOPE total=40 protected=37 unexpected_changed=0
```

本机抓取的 141 页、423 字段比对如下（仅补充证据）：

```text
pages=141 checked_fields=423 mismatch=5 redirects=1 non_200=0
MISMATCH {"path": "/", "field": "h1", "actual": "Custom packaging for jewelry, candles & gifts.", "expected": "Custom packaging for jewelry, candles and gifts", "reason": "首页 H1 按决定保留 & 和句号"}
MISMATCH {"path": "/collapsible-magnetic-gift-box/", "field": "title", "actual": "Custom Collapsible Magnetic Gift Boxes | Packrivo", "expected": "Collapsible Magnetic Gift Boxes | Packrivo", "reason": "原 URL 重定向，结果来自目标页"}
MISMATCH {"path": "/collapsible-magnetic-gift-box/", "field": "meta", "actual": "Custom collapsible magnetic gift boxes. Dieline with every quote. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Request a quote.", "expected": "Custom collapsible magnetic gift box. MOQ 250, 5-10 day sampling. 7-30 day production after approval. Recycled-content stock on request. Request a quote.", "reason": "原 URL 重定向，结果来自目标页"}
MISMATCH {"path": "/collapsible-magnetic-gift-box/", "field": "h1", "actual": "Collapsible Magnetic Gift Boxes, dieline first", "expected": "Collapsible Magnetic Gift Box", "reason": "原 URL 重定向，结果来自目标页"}
MISMATCH {"path": "/samples/", "field": "h1", "actual": "Packaging samples", "expected": "Custom Packaging Samples", "reason": "父主题 H1 不在仓库，本轮保留"}
FIELD_REDLINE {"moq_500": 0, "standards": 0, "factory_any": 1, "factory_self_claim": 0}
VISIBLE_PAGE_REDLINE {"moq_500": 0, "standards": 4, "factory_any": 29, "factory_self_claim": 0}
```

严格按整页可见内容查 ISO/ISTA/FSC 为 **4 处，未达零命中**：`/corrugated-wine-shipper/` 与 `/guide-mailer-rfq-checklist/` 提及 ISTA 作为测试讨论；`/eco-kraft-cosmetic-box/` 与 `/printed-tissue-paper/` 提及 FSC 作为需确认事项。按 title/meta/H1 字段查上述红线均为 0；字段中出现的一次 factory 是 `/about/` 的 “not a factory”，不是自称。全文 `factory_any=29` 的上下文已人工复核，属于否定自称、独立供应商或工序讨论，未发现自称工厂。依范围不改这些原正文。

## 翻译闸门（现状：失败，待 dsh）

```text
csv_rows=350 unique_keys=339
ar missing=338
de missing=338
es missing=338
fr missing=338
it missing=338
ja missing=338
ko missing=338
nl missing=338
pt missing=338
zh-CN missing=338
zh-TW missing=338
GATE FAIL missing_entries=3718 errors=0
```

按实际用途检查 title、attr 或 text/block 桶；未修改词典。报价页三条新英文均已追加。

## 离线检查

```text
PHP syntax: migration, rollback, backup library, plan library, CLI wrapper, quote page, probe, render-fetch: 8 PASS
NATIVE_MYSQL_26.7_SYNTHETIC AFTER_WRITE title=new updated_at=2001-02-03 04:05:06
NATIVE_MYSQL_26.7_SYNTHETIC AFTER_ROLLBACK title=old updated_at=2001-02-03 04:05:06
NATIVE_MYSQL_TIMESTAMP_TEST PASS synthetic_only
BACKUP_CSV_ROUNDTRIP rows=4 PASS
BACKUP_INTEGRITY_CASES=3 PASS
RANK_GATE_CASES=4 PASS
INDEXABLE_PLAN_CASES=3 PASS
PLAN_MANIFEST_CASES=6 PASS
34a2-i18n-gate-test: 5 tests OK
34a2-render-test: 6 tests OK
34a2-runbook-gate-test: 2 tests OK
MAP_TARGET_PREFLIGHT_ROWS 141 MOQ500=0 ISO_ISTA_FSC=0 FACTORY_SELF_CLAIM=0
```

原生 MySQL 使用空的合成测试库、仅监听本机并在测试后关闭；该结果只证明时间戳 SQL 行为，不代替 34a 实例的迁移与回滚。词表检查不是 141 页渲染验收。容器的首次 changed、二次 changed=0、回滚 5 页及全页核对、再次执行及 141 页渲染比对均已完成；未通过项见下。

## 恢复后最终 34a 容器验收（2026-11-05，美西）

重启仅限 `packrivo-34a-db` 和 `packrivo-34a-wp`；重启后只读标志为 0，迁移后抓取前设为 `read_only=1, super_read_only=1`。恢复时 141 页数据库快照 SHA-256 为 `5d2bb17f8faeb0d795ab9c43488a537d75eee86e288185bae023a8d2dec73b1a`，与先前重应用结果一致。最终 CLI 包装器已同步至容器；主题 quote 文件容器与工作区 SHA-256 均为 `b904bccba7e4d70cf125fb357da30b88428532bd167d5b51fae47d9b2dd52a39`。

- 容器重新演练：dry-run 141 页、排名门 8/8；首次 549、第二次 0、回滚 549、再应用 549。指定 5 页和全部 141 页回滚快照完全一致；非目标字段变化 0，37 张受保护表变化 0，抓取后表校验仍为 0。完整日志 `E:/破甲工作区/34a2-rehearsal/34a2-drill-resume.log`。
- 容器实际抓取 141 页，423 字段，HTTP 非 200 为 0，1 个重定向，5 个差异：首页 H1 按决定保留；`/samples/` 父主题 H1 保留；`/collapsible-magnetic-gift-box/` 301 目标页的 title、meta、H1 与原 URL 的词表各不一致。严格字段比对未通过。
- title/meta/H1 红线：MOQ 500、ISO/ISTA/FSC、自称工厂各 0；整页可见文本中 ISO/ISTA/FSC 共 4 处（两处 ISTA 测试讨论、两处 FSC 待确认事项），严格整页零命中未通过；MOQ 500 和自称工厂的自动规则均为 0。完整机器结果 `E:/破甲工作区/34a2-rehearsal/render-audit-resume.json`，审计程序退出码 1。
- 翻译闸门再次执行：11 种语言各缺 338 项，共 3718 项，退出码 1；未改动词典。必须补齐翻译并重新过闸，且解决或明确审议页面差异及整页标准词，才能考虑后续发布。
- 重新执行 Python 翻译、渲染、RUNBOOK 和契约测试分别为 5、6、2、2 项通过；先前离线和负向测试见上。

- 最终包装器在 34a 容器执行缓存保护事务测试：已有超时、过期数据、缺失数据及过期读取四项均 PASS；测试后 37 张受保护表仍无变化，数据库已恢复运行时只读。
