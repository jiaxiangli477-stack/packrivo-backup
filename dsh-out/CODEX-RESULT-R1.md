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
