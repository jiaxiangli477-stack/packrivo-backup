# 门禁核查证据摘录 · cfd4bd7 之后的增量（2026-10-28，本链独立只读）

来源命令（全部只读）：
`git fetch origin` / `git log --oneline` / `git rev-parse HEAD` / `git status` / `git show --stat bd17806` /
文件只读（UTF-8）：`packrivo-v2/docs/DSH-TASK-2026-10-28r.md`、
`deploy/verification-28q/scripts/rehearsal.sh`、`deploy/verification-28q/scripts/step0-probe.sh`、
`deploy/verification-28q/check-0.1.rehearsal.sh`、`deploy/RUNBOOK-launch.md`、
`dsh-out/DSH-RESULT-2026-10-28q.md`、`deploy/verification-28q/rehearsal-verify.json`

## 1. cfd4bd7 之后的新提交与文件变更：零

```
$ git rev-parse HEAD
cfd4bd75c84c0dd548249ead3b05dc7bc74c60ff
$ git fetch origin && git log --oneline origin/main -4
cfd4bd7 launch log: append section 16.7 + evidence update - new task ticket DSH-TASK-2026-10-28r.md ...
e0f64b9 launch log: append section 16 incremental gate check after cad8c62 ...
bd17806 28r 任务单：补演练第 9 步最后手段（整库导入）
b155b40 28q: 结果文档补一条本地状态说明（27e 补丁打在本地 mu 副本，生产未动）
$ git status
On branch main
Your branch is up to date with 'origin/main'.
nothing to commit, working tree clean
```

- origin/main == HEAD == cfd4bd7，porcelain = 0，无未跟踪文件。
- 全仓 11:12:10 之后无任何文件变更（按 LastWriteTime 全量扫描，最新两个文件为
  LAUNCH-LOG-2026-10-28.md 11:12:01 与 gate-evidence-...-after-cad8c62.md 11:11:58，均为意图 678 产物）。
- 任务单新答复：无。packrivo-v2/docs/ 最新仍为 DSH-TASK-2026-10-28r.md（11:10:01，bd17806）。
- 28r 交付物均不存在：`dsh-out/DSH-RESULT-2026-10-28r.md`（无）、`deploy/verification-28r/`（无，含 lastresort.log 无）。

## 2. Claude 三项必改落地核查（全部未落地）

### 2.1 最后手段 db import 真实演练：未执行
- 无 `deploy/verification-28r/`、无 lastresort.log、无 DSH-RESULT-2026-10-28r.md → 28r §2/§5 未执行。
- RUNBOOK L552–558 仍为裸 3 命令块（docker cp db.sql → db import → 删临时文件，未加 28r §4.1
  要求的 0.1 前置探针、文件部分顺序说明、transient/缓存清理与判据）。

### 2.2 rehearsal.sh 可判别 FAIL / exit 1 硬守卫：未改
- `deploy/verification-28q/scripts/rehearsal.sh` 271 行（mtime 09:29:37，cad8c62 入库版；HEAD 与工作树一致）。
- 每步块仍以 `set +x` 收尾（Claude 指出的 exit 恒 0 结构未变）；全文唯一 `exit` 为 L268 用法错误的 `exit 2`；
  无每步 FAIL 计数、无 FAIL→exit 1：
```
8: set -u
27: set -x  40: set +x  42: set -x  47: set +x  ...（步骤块 set -x / set +x 包裹模式不变）
255: set +x   ← STEP 9 块收尾仍是 set +x
268: exit 2   ← 仅用于非法参数用法提示（case *) 分支）
```
- `rehearsal.sh` 全文无 'skip-ssl'、无 'probe'（0.1 导出探针未进轮内 step0）。

### 2.3 每轮 §0.1 --skip-ssl 探针且不依赖容器遗留包装：未落地
- RUNBOOK-launch.md（mtime 10:57:16）HEAD 无变更；附录 C 仍为「已跑通」+「全部 exit=0」口径（28r §4.2 未做）：
```
587: ## 附录 C · 28q 本地演练（已跑通）
595: | 正向 | 第 0–8 步 | 9 个步骤块全部 exit=0 |
596: | 回滚 | 第 9 步 | exit=0，站点快照与基线**逐字节一致**（sha256 `4972cd81…`） |
597: | 再正向 | 第 1–8 步 | 8 个步骤块全部 exit=0，...与第一次正向**逐字节一致**（sha256 `4247693f…`） |
```
- 探针现为轮后补跑：rehearsal-verify.json 阶段 4 =「补跑：第 0.1 步新加的数据库导出探针 + 必要时包装客户端」
  （file: probe.log），非每轮轮内探针。
- `scripts/step0-probe.sh`（mtime 10:48:14）为补跑脚本，0.1c 段在容器内 /usr/local/bin 安装
  mariadb-dump/mariadb 的 --skip-ssl 包装——即依赖容器遗留包装的模式（Claude 必改项 3 所述），
  尚未改为每轮自检自装。

## 3. supplement2（2.5/3.0/9.3/附录 D）批准与落地

- 落地：未落地。RUNBOOK 无「附录 D」（仅附录 A/B/C）；2.5（L231–236）为合并校验打印
  （失败被 `|| echo` 吞掉、无步骤中止）；无 3.0 预 apply 回滚守卫、无 9.3 预回滚 site 守卫
  （9.3 命中为 RUNBOOK 自身的 9.3 内容回滚节，非 supplement2 的 9.3 守卫）。
- 批准：仓库内无对 supplement2 补丁的批准记录；28r 任务单以独立措辞背书同向要求
  （28r §4.4 rehearsal.sh FAIL 计数+exit 1 ↔ supplement2 2.5 硬 FAIL；28r §4.1 最后手段 0.1 前置探针 ↔ supplement2 D.2.1）
  → 方向获负责人背书，补丁形式未获批。

## 4. 三轮完整整轮 PASS（严格 RUNBOOK 口径）：不成立

- rehearsal-verify.json phases：阶段0 复位 / 阶段1 正向 0–8（9 块）/ 阶段2 回滚 9 /
  阶段3 再正向 1–8（8 块）/ 阶段4 轮后补探针 = **2 次正向 + 1 次回滚**，非三轮完整循环。
- 28q 结果文档仍写「三轮 18 个步骤块全部 exit=0」（DSH-RESULT-2026-10-28q.md L10），
  而 Claude 已否定该 exit=0 证据体系；28r §3 要求逐条判据打印 PASS/FAIL 的重验尚未发生。
- DSH-RESULT-2026-10-28q.md L98 仍保留「`db import` 成功」字样（28r §4.3 要求删除改写，未做）。

## 5. Claude 修正后复验结论 / 新生产授权

- 修正后复验结论：不存在（28r 尚未执行，无对修正结果的二次核验）。
- 新生产授权：不存在。28r 明确「本轮不碰生产：不 SSH，不碰密码」；仓库无任何新授权记录；
  git log 全量中最新授权类文件仍为任务单/日志，无负责人批复提交。

## 6. 审批项⑥⑦口径裁定

- ⑥（轮内 step0 未跑 0.1 探针、step1 db export 依赖容器遗留 --skip-ssl 包装、探针轮后补跑）：
  无直接裁定；28r §3/§4.1 以「包装不在位没人验证过」同向背书，待 28r 结果落地后再议。
- ⑦（轮次计数口径：2 正向+1 回滚 vs 三轮完整循环）：无直接裁定，继续保留未决。

## 7. 操作边界

只读 `git fetch/log/show/status` 与文件读取；未执行 28r 任何指令、未启动/停止/接入容器、
未修改/删除他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。
