# R1 发版准备与演练记录

状态：**未完成 / NOT READY，禁止生产执行。** 未连接生产、未部署、未 push。用户要求完整从头演练、整体回滚、再执行和全部验收；这些项目本轮尚未完成，不得将下述局部检查视为通过。

## 基线与隔离

- 以 `main` `090478f74aeae3ca4dd08e9efbd0f88d63e75b87` 创建独立 `r1` worktree。main 的 35a 未跟踪文件不纳入本任务。
- 本机可核验的原始生产导出是 `E:/破甲工作区/packrivo-backup/deploy/verification-29y/export/29y-prod.sql`，导出时间 2026-10-30 17:45:26（America/Los_Angeles），SHA256 `3007fb4aef2d2af4a5251d1196494ea12e6d5a9cd493e13cb2cddcc4bafd2820`；11 月 5 日 34a 文件是本地演练数据库快照，不是当日生产导出。没有找到可证明为当天生产的 uploads 导出。
- 已创建 `packrivo-r1-db` / `packrivo-r1-wp`，内部网络、无对外端口。数据库导入上述 SQL；站点文件及 uploads 来自 34a 的本地演练卷只读复制，**不等于与 SQL 同时点的生产文件或 uploads**。这份站点文件的主题已经是 4.0.6，不能证明 4.0.4 起始状态；WordPress 7.1.3 与旧记录所述生产 7.1.4 也有差异。
- 因此从 4.0.4 开始的完整序列、完整回滚以及再次应用均未完成。旧 29y RUNBOOK 硬编码通用容器 `php`、旧本机路径和 `git checkout`，必须先适配及审查；不能以直接执行它的方式声称演练成功。历史首次安装回滚包含删除主题/插件，不适合直接作为本次增量升级回滚。

## 词典

308 行、11 语言、按指定桶检查，现有键冲突 0；每语言新增 28，原有键未覆盖。导入器对冲突先列出并在写入任何词典前停止；合成冲突与正常导入测试通过。两道闸门原始摘要：

```text
IMPORT PASS rows=308 added=308
32a: csv_rows=573 unique_keys=565 quote_keys=3
ar/de/es/fr/it/ja/ko/nl/pt/zh-CN/zh-TW: missing=0 quote_missing=0 quote_invalid=0 title_missing=0
GATE PASS missing_entries=0 title_errors=0 errors=0
31b: rows=37 languages=11
ar/de/es/fr/it/ja/ko/nl/pt/zh-CN/zh-TW: missing=0 import_mismatch=0
GATE PASS issues=0
```

## 本地实例初步检查（不是全流程验收）

```text
packrivo-r1-wp Up; packrivo-r1-db Up; ports {"80/tcp":null}
base tables=40
448 Custom Packaging MOQ: Plan 500 Pieces per Design
508 Multi SKU Jewelry Packaging: Build a Fit Matrix
1621 Retail Demonstrator Packaging: Count Material Use
theme=4.0.6; quote header=1.0.1; WordPress=7.1.3
32a6 dry-run: Error: Post 448 must match the complete old or transformed new content; nothing changed.
PREFLIGHT_EXIT=1
```

32a6 是在尚未运行前置 32a-5 时单独探测；结果仅说明该状态不能直接执行该步骤，不证明顺序执行后仍会失败。没有进行用户指定的完整验收、全量页面与 sitemap 扫描、回滚和二次应用。

## 候选包（尚未完成部署和全流程验收，不得生产安装）

```text
packrivo-home-v4-4.0.7.zip  sha256 da8479935ebc930bc16d0f4aa889ea1d55aa31720d4dd407ce1b24aa2d93a512
packrivo-quote-1.0.2.zip    sha256 2a40e11a0dc13e42011221257f4ac0ce0e6256bd993d8800b104c309d193794a
packrivo-language-selector-1.0.10.zip sha256 3094100ad8cccf291ca2de6cf4f8195141d06c509950d2d8458e5543b4d0fed4
```

包内版本已递增；zip 完整性检查通过。`deploy/RUNBOOK-launch.md` 附录 E 标为草案，列出了顺序与阻塞，尚非可执行定稿。站长导出命令草案见 `dsh-out/R1-上线操作单.md`；命令未连接生产执行。

后续需要获得可核对的站点文件基线和 uploads 导出，在新 R1 实例上先验证 29y、31a、31b、32a 的准确部署及回滚命令，随后按照全部验收项完成从头应用、整体恢复快照一致、再次应用，并贴出原始输出。完成前不得声称 R1 已通过。

附加核验：11 份词典中原有 **96,927** 个条目的键和值与起始 HEAD 一致；导入器再次运行 `added=0`。完整闸门和测试输出见 `dsh-out/R1-verification.log`。PHP 语法检查覆盖本轮递增版本的三个 PHP 文件，均通过。

## R1-2 重建与中止记录（2026-11-06，美西）

**整体未通过；32a-6 预检失败后已停止正向并恢复到本地 S0；未执行完整验收和再次正向。** 本轮没有连接生产、push 或部署。

- 分支起点 `6e5efe7`；仅重建 `packrivo-r1-*` 容器、卷和内部网络。旧 R1 卷移除前核对仅被两个 R1 容器引用。
- SQL 为 2026-10-30 17:45:26 美西生产导出，SHA256 `3007fb4aef2d2af4a5251d1196494ea12e6d5a9cd493e13cb2cddcc4bafd2820`。WordPress 镜像 `wordpress:7.1.4-php8.3-apache`、镜像 digest `sha256:6888208af0e3cb93af427c13a434273b07ba353ce9e2d5a29abe3031bb28ea3a`；`wp core version` 为 `7.1.4`。
- 子主题包 SHA256 `3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6`，`wp theme list` 显示 active `4.0.4`；报价插件包 SHA256 `096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93`，插件显示 active `1.0.1`。`packrivo-wave2.php` SHA256 `73d43ba0262a9d74249b19a46380bc5c338f94bd9e0776baa46eddf074c9f259`。该哈希只证明一个文件，其他 mu 文件来自本机 28q 演练副本，不能声称全目录生产一致；两个明确属于 29y 的临时/增量文件在 S0 前从 R1 移除。
- 生产 SQL 的 active_plugins 包含 language-selector；但 10-30 上线记录未证明实际版本。28r 本地验收记录及 28q 文件显示 `1.0.8`，不能单凭这两项证明 10-30 生产版本；按任务规则 S0 将插件目录视为缺失，保留 SQL 原始选项，必须站长指纹确认。
- uploads 来源：34a 本地演练卷的只读复制，非当天导出。正向曾覆盖其中的 11 个 `wp-content/uploads/packrivo-i18n/<lang>-dict.json`，已纳入 E1 并恢复；其余 uploads 未作为本轮目标写入。`31b/prepare-photos.py` 输出主题目录 `assets/img/31b-process/`，这次使用候选主题包而未运行该脚本。S0 的 `llms.txt` 和 `llms-full.txt` 均缺失，不能给缺失文件伪造哈希；回滚恢复缺失状态。
- 开始预备曾在专项备份前更换报价插件，发现后立即完整回滚，数据库、文件及 30 页面正文与当时 S0 匹配；随后补齐备份再重试。开始重试时发现复制的 34a 工作区插件/父主题已经是 32a 修改版，原值守卫失败。以 28q 历史副本重建这两个目录，32a 的六个旧值补丁预检与 PHP 语法检查通过；其中 renderer/admin/parent 的候选哈希与先前混入版本相同。重新生成最终 S0、E1。此本地恢复只证明演练基线，不证明这些文件当前生产指纹。

### E1 本地备份

完整 SQL 及每个存在文件的 tar 已记录字节数、SHA256，并将 tar 全部解压到隔离目录检查；缺失项目记录 absent。父主题和产品工作区插件也在备份中，因为 32a 会修改它们。完整本地数据未提交，位于 `E:/破甲工作区/r1-2-rehearsal/E1/`。

| 项目 | 字节 | SHA256 / 状态 |
| --- | ---: | --- |
| parent | 24340480 | 369e4a1c431d30424df61cacde7da15ca2d7cc85d5dd50cfd46e62da0cc7b514 |
| workspace | 153600 | 521372c411ae92ebd9fac7e3ebbf5940bde46197e1b1d8b6b8eddbf55684d63a |
| theme | 911360 | f2a014706dbf8f2ef8d30fcc3bb5761592fe2baaff83faee312587d2d4a28428 |
| quote | 163840 | fbf78dccdce9e440eac79f2cb8682f763ca1391272451deb2495474a7a88d630 |
| selector | — | absent |
| mu | 3072000 | 768745945c7f5d757dc8d1c5978f183203d9c07e308e9b5e5de9ca5bbdf14a94 |
| dictionaries | 19537920 | c9b96f7f830617128938b603e045268d9e06b5e528a779488ce69fdbb6dfd4e1 |
| llms | — | absent |
| llms-full | — | absent |
| db | 35153340 | 7b360455de332560180d062330bebb5c10af8c6aa289849800dbae0bb8215c4d |

32a 工作区专项备份：6 个数据行 + 表头，SHA256 `87904c173b79f9e38593448ac54bef16223dd53c9133bcc71c8ebf6bae991d15`，先于第二次候选插件替换。

### 正向原始输出（停止点）

```text
=== COPY 32a-update-options.php exit=0 ===

=== COPY 32a-moq-content.php exit=0 ===

=== COPY 32a-moq-plan.json exit=0 ===

=== COPY 32a5-blog-migrate.php exit=0 ===

=== COPY 32a5-blog-plan.json exit=0 ===

=== COPY 32a6-blog-rewrite.php exit=0 ===

=== COPY 32a6-blog-rollback.php exit=0 ===

=== COPY 32a-blog-rewrite.csv exit=0 ===

=== COPY default-category.php exit=0 ===

=== COPY 32a-workspace-backup-final.csv exit=0 ===

=== 31B_DEFAULT_CATEGORY exit=0 ===
Success: Default category renamed to Guides (ID 1).

=== INSTALL wave1 exit=0 ===

=== INSTALL remediation exit=0 ===

=== INSTALL workspace-renderer exit=0 ===

=== INSTALL workspace-admin exit=0 ===

=== INSTALL workspace-validation exit=0 ===

=== INSTALL parent-functions exit=0 ===

=== WORKSPACE_MIGRATE_1 exit=0 ===
before_500=6 before_250=0
after_500=0 after_250=6
replaced=6 mode=migrate snapshots=6

=== WORKSPACE_MIGRATE_2 exit=0 ===
before_500=0 before_250=6
after_500=0 after_250=6
replaced=0 mode=migrate snapshots=6

=== OPTIONS_1 exit=0 ===
pkq_settings: updated
title-404-wpseo: updated

=== OPTIONS_2 exit=0 ===
pkq_settings: unchanged
title-404-wpseo: unchanged

=== MOQ_1 exit=0 ===
before MOQ 500=452 | 500 pieces per=550
site_moq_before=missing(default 500)
after MOQ 500=0 | 500 pieces per=1
replaced=1666 | fields=1123 | pages=399
yoast_cache_replaced=925 | indexable_rows=363
site_moq_after=250 | site_moq_updated=1

=== MOQ_2 exit=0 ===
before MOQ 500=0 | 500 pieces per=1
site_moq_before=250
after MOQ 500=0 | 500 pieces per=1
replaced=0 | fields=0 | pages=0
yoast_cache_replaced=0 | indexable_rows=0
site_moq_after=250 | site_moq_updated=0

=== 32A5_DRY_RUN exit=0 ===
mode=dry-run plan=318 posts=276 state=before old=318 new=0

=== 32A5_BACKUP exit=0 ===
mode=backup plan=318 posts=276 state=before old=318 new=0
backup_rows=276 sha256=8cbbbdfd3c46c5a0aff3fc37eb23f97ec54eb4dd947bd5fa9f91e7387db1e646

=== 32A5_BACKUP_COPY exit=0 ===

=== 32A5_APPLY_1 exit=0 ===
mode=apply plan=318 posts=276 state=before old=318 new=0
changed=318 posts=276

=== 32A5_APPLY_2 exit=0 ===
mode=apply plan=318 posts=276 state=after old=0 new=318
changed=0 posts=0

=== 32A6_DRY_RUN exit=1 ===
Error: Post 448 must match the complete old or transformed new content; nothing changed.
```

**32a-6 按 32a-5 首次 318、二次 0 后执行，仍以 Post 448 完整正文不匹配失败；守卫未改动，后续不继续。** 本地原始完整日志也保存在 `dsh-out/R1-2-forward-data.log`。本次文件安装将候选主题中 29y/31a/31b/32a 的代码一并放入，并非已证明的逐组件生产顺序；因此即使本地早期步骤成功，也不能视为完整按附录 E 通过。

### 失败后的整体恢复与 S0 比对

维护隔离环境中从 E1 导回 SQL、清空受影响目标目录后还原 tar，恢复原本缺失项，并运行 `wp cache flush`；数据库置回 `super_read_only=1`。这是失败后的恢复测试，不是成功正向后的三连跑。

| 项目 | S0 SHA256 | 恢复后 SHA256 | 结果 |
| --- | --- | --- | --- |
| 全库 SQL（去除 dump 时间戳） | 7b360455de332560180d062330bebb5c10af8c6aa289849800dbae0bb8215c4d | 7b360455de332560180d062330bebb5c10af8c6aa289849800dbae0bb8215c4d | 一致 |
| theme 文件哈希清单（present） | f5a4f957475673f80a37118dddf0a3b747e764116071177a9239b9642f730ae0 | f5a4f957475673f80a37118dddf0a3b747e764116071177a9239b9642f730ae0 | 一致 |
| parent 文件哈希清单（present） | 7a66f6146e0b3b7a92a4352684baf0146cf4d96a3c7f782eae59de826191661e | 7a66f6146e0b3b7a92a4352684baf0146cf4d96a3c7f782eae59de826191661e | 一致 |
| workspace 文件哈希清单（present） | 3d265373acf32268b22f930fd650a0d342fd878a9d02e24ec60b1836b6c662ba | 3d265373acf32268b22f930fd650a0d342fd878a9d02e24ec60b1836b6c662ba | 一致 |
| quote 文件哈希清单（present） | bb9026b64dc4b49a1b13e5e3a53b5e3894a0ec1e9fc9ae4bce6d2faa81d9dfbe | bb9026b64dc4b49a1b13e5e3a53b5e3894a0ec1e9fc9ae4bce6d2faa81d9dfbe | 一致 |
| selector 文件哈希清单（missing） | 44a32d4506665562fdfed83f4deb46ba771d9432b1767f6b8d445f0451bdf42b | 44a32d4506665562fdfed83f4deb46ba771d9432b1767f6b8d445f0451bdf42b | 一致 |
| mu 文件哈希清单（present） | 7cef3a9ba9f4b72230c10de1bc4f61737454527c3a85a473b6d2d21087e05228 | 7cef3a9ba9f4b72230c10de1bc4f61737454527c3a85a473b6d2d21087e05228 | 一致 |
| dictionaries 文件哈希清单（present） | c8ea107ada9bbe7a9f21a5c028de6408d3a6167337ccca1fdf546dc2aaf3ec00 | c8ea107ada9bbe7a9f21a5c028de6408d3a6167337ccca1fdf546dc2aaf3ec00 | 一致 |
| llms 文件哈希清单（missing） | 44a32d4506665562fdfed83f4deb46ba771d9432b1767f6b8d445f0451bdf42b | 44a32d4506665562fdfed83f4deb46ba771d9432b1767f6b8d445f0451bdf42b | 一致 |
| llms_full 文件哈希清单（missing） | 44a32d4506665562fdfed83f4deb46ba771d9432b1767f6b8d445f0451bdf42b | 44a32d4506665562fdfed83f4deb46ba771d9432b1767f6b8d445f0451bdf42b | 一致 |

缺失文件的清单哈希是 `MISSING\n` 标记的哈希，不是实际文件哈希。下面的 30 页对比使用原始响应正文 SHA256 和状态码；HTTP Date 等响应头会变化，故不声称整个 HTTP 响应字节一致。

| 路径 | S0 状态 | S0 正文 SHA256 | 恢复后正文 SHA256 |
| --- | --- | --- | --- |
| / | HTTP/1.1 200 OK | ffc1309e66a2e99b6fee84108dea9b21ef3501c7572bf4a0d6f070ecd4337495 | ffc1309e66a2e99b6fee84108dea9b21ef3501c7572bf4a0d6f070ecd4337495 |
| /products/ | HTTP/1.1 200 OK | 5945a5bf58e19e8634e31a4d30827c39daff45d397d30c717e5d5163310bc63c | 5945a5bf58e19e8634e31a4d30827c39daff45d397d30c717e5d5163310bc63c |
| /jewelry-boxes/ | HTTP/1.1 200 OK | 1bbf674e83bf0259608228969856450cf7ba4b2d2fe6c1058634e3e6857fd7ac | 1bbf674e83bf0259608228969856450cf7ba4b2d2fe6c1058634e3e6857fd7ac |
| /get-a-quote/ | HTTP/1.1 200 OK | d23dfa0dfd8aedaa246f8964daa0941bee57948994758395e7375b8edb58eb51 | d23dfa0dfd8aedaa246f8964daa0941bee57948994758395e7375b8edb58eb51 |
| /2026/10/01/packaging-moq-design-planning/ | HTTP/1.1 200 OK | c371629f8e45fa40b5b678b1007a459857066536bb2082add01d79eceafea98b | c371629f8e45fa40b5b678b1007a459857066536bb2082add01d79eceafea98b |
| /2026/10/02/multi-sku-jewelry-packaging-fit-matrix/ | HTTP/1.1 200 OK | e8b6f86144db12f0358a83adc9210cee8184f5c860f746fe0f969b1430a81027 | e8b6f86144db12f0358a83adc9210cee8184f5c860f746fe0f969b1430a81027 |
| /2026/10/15/retail-demonstrator-material-plan/ | HTTP/1.1 200 OK | 96e8c601d07d359b5833ae38a120a41f9b0921293052d9f629374e3ffe8ee8ce | 96e8c601d07d359b5833ae38a120a41f9b0921293052d9f629374e3ffe8ee8ce |
| /product-magnetic-jewelry-box/ | HTTP/1.1 200 OK | a688e7aa39a57161a1b9d33b5c378b4647fd9e9e6a3e07a12deaeef6dd29d8e4 | a688e7aa39a57161a1b9d33b5c378b4647fd9e9e6a3e07a12deaeef6dd29d8e4 |
| /product-front-tuck-mailer/ | HTTP/1.1 200 OK | 7771fbcc4dea2cc5b7682546c31e34b70d400f4b4af9cc1733bc897b96700a9b | 7771fbcc4dea2cc5b7682546c31e34b70d400f4b4af9cc1733bc897b96700a9b |
| /product-collapsible-magnetic-gift-box/ | HTTP/1.1 200 OK | 97c67cbb3c3eb6d9d835982c5ee733532ff32a469790cdb6e1276713eaf2069c | 97c67cbb3c3eb6d9d835982c5ee733532ff32a469790cdb6e1276713eaf2069c |
| /product-double-wall-tray-lid/ | HTTP/1.1 200 OK | 407b9ad4dffb1e11e17d63e1458417977dfd41913dfaed3bd0cddba0f871f230 | 407b9ad4dffb1e11e17d63e1458417977dfd41913dfaed3bd0cddba0f871f230 |
| /product-lock-cap-auto-bottom-carton/ | HTTP/1.1 200 OK | a18292067dc3c175e3d70d6dba4001fefab8c25e45ed09bd5c89e030208a3020 | a18292067dc3c175e3d70d6dba4001fefab8c25e45ed09bd5c89e030208a3020 |
| /ar/ | HTTP/1.1 200 OK | b85a8c7623fec0f77b71b9c51d222e5bf4a286fffc4e6b966a2553e75b9cb5a0 | b85a8c7623fec0f77b71b9c51d222e5bf4a286fffc4e6b966a2553e75b9cb5a0 |
| /de/ | HTTP/1.1 200 OK | ccba9d524accc401f8f95151a4d1935b944fd096589bce8bdf6c4527d93bfc30 | ccba9d524accc401f8f95151a4d1935b944fd096589bce8bdf6c4527d93bfc30 |
| /es/ | HTTP/1.1 200 OK | f5ddf1f677713bd94fb429ec852060063cfd787f8a541545aed88083a209f2b6 | f5ddf1f677713bd94fb429ec852060063cfd787f8a541545aed88083a209f2b6 |
| /fr/ | HTTP/1.1 200 OK | 45d302673047bfb41de3165df8a8f4d2b26bf3213e31065bb5521d033eaad5a8 | 45d302673047bfb41de3165df8a8f4d2b26bf3213e31065bb5521d033eaad5a8 |
| /it/ | HTTP/1.1 200 OK | d2a335941b82758e7bc1f78450cede9ec9f7b1f36e034389586b41a48186e3d4 | d2a335941b82758e7bc1f78450cede9ec9f7b1f36e034389586b41a48186e3d4 |
| /ja/ | HTTP/1.1 200 OK | 64e9f252a4f2716ea96e0230fd2520072cf8a94d7cec04c9f1ea94bb4ee8b188 | 64e9f252a4f2716ea96e0230fd2520072cf8a94d7cec04c9f1ea94bb4ee8b188 |
| /ko/ | HTTP/1.1 200 OK | cf5733aef4cf9c6e5c89d890c41b6a4931480a2503b8670b0e2a79b25a1635e1 | cf5733aef4cf9c6e5c89d890c41b6a4931480a2503b8670b0e2a79b25a1635e1 |
| /nl/ | HTTP/1.1 200 OK | f010d3523c1f968b560bb4b3514e7282d8217c6fefbba21f421f36be05def006 | f010d3523c1f968b560bb4b3514e7282d8217c6fefbba21f421f36be05def006 |
| /pt/ | HTTP/1.1 200 OK | 9919893c8ad13ee7a3687e67708cc0d62b5fbb36aad673282f9e970121e9d5f1 | 9919893c8ad13ee7a3687e67708cc0d62b5fbb36aad673282f9e970121e9d5f1 |
| /zh-CN/ | HTTP/1.1 200 OK | 2fcde64d5db8d3cc5f46583830a5f3114ef3010ce105ff9bae6899fbfed1dace | 2fcde64d5db8d3cc5f46583830a5f3114ef3010ce105ff9bae6899fbfed1dace |
| /zh-TW/ | HTTP/1.1 200 OK | 80120472aebdc8b71e0a47c908b47c860221046d14c02cb966cfb9bff9d01c8b | 80120472aebdc8b71e0a47c908b47c860221046d14c02cb966cfb9bff9d01c8b |
| /PRODUCTS/ | HTTP/1.1 200 OK | e3c5d560ceaf6c6828547aea3974d457185d6fe7b7b89c8c39d04da847239192 | e3c5d560ceaf6c6828547aea3974d457185d6fe7b7b89c8c39d04da847239192 |
| /ABOUT/ | HTTP/1.1 200 OK | 1af68bf2a5d0218063862dd363aab1a93cb297e88efabb72f9fcd59dec556fba | 1af68bf2a5d0218063862dd363aab1a93cb297e88efabb72f9fcd59dec556fba |
| /DE/ABOUT/ | HTTP/1.1 404 Not Found | 62aa7ce106874fcecfba121c2e8d929975c1bef2659a049b7319d8bc97c55d57 | 62aa7ce106874fcecfba121c2e8d929975c1bef2659a049b7319d8bc97c55d57 |
| /PRIVACY/ | HTTP/1.1 200 OK | b8e84ef9e5c220980cc930e63f89b01e42a2e49ca0298fbe450e47ab5798ca75 | b8e84ef9e5c220980cc930e63f89b01e42a2e49ca0298fbe450e47ab5798ca75 |
| /DE/ | HTTP/1.1 404 Not Found | 62aa7ce106874fcecfba121c2e8d929975c1bef2659a049b7319d8bc97c55d57 | 62aa7ce106874fcecfba121c2e8d929975c1bef2659a049b7319d8bc97c55d57 |
| /SAMPLES/ | HTTP/1.1 200 OK | 4054825239b765431d3d27471c26d9233f428eee193af26018b56780ff1e2f64 | 4054825239b765431d3d27471c26d9233f428eee193af26018b56780ff1e2f64 |
| /GET-A-QUOTE/ | HTTP/1.1 200 OK | fc04e768e0a8caaf7b4b261d37f7443dac39d4006fb0f51c35be4bf6fcb46cb3 | fc04e768e0a8caaf7b4b261d37f7443dac39d4006fb0f51c35be4bf6fcb46cb3 |

### 操作单指纹的限制

在本地 `super_read_only` 下，普通 `wp theme list` 和 `wp plugin list` 尝试写入更新缓存；加入 `--skip-update-check` 后本地重测返回 0 且未见上述数据库写入错误，但本地不能证明生产 mu 插件无其他副作用。当前操作单命令仍须只读数据库身份与复核，禁止视为已经验证可在生产只读执行。

附录 E 仍为草案：缺完整正向及两轮验收、32a6 失败、生产只读指纹未获验证，不得去掉草案标识。
