# 门禁核查证据摘录 · cad8c62 之后的增量（2026-10-28，本链独立只读）

来源命令（全部只读）：
`git log --oneline -5` / `git show --stat e68b87a` / `git show b155b40` /
`git diff --name-status cad8c62 HEAD` / `git show HEAD:deploy/verification-28q/rehearsal.log` /
`git show HEAD:deploy/verification-28q/rehearsal-verify.json` /
`git show HEAD:deploy/RUNBOOK-launch.md` / `git show HEAD:deploy/verification-28q/scripts/rehearsal.sh`

## 1. cad8c62 之后的新提交（cad8c62..HEAD，仅 3 个文件）

```
e68b87a 2026-10-28 10:59:49 28q: 把演练日志纳入版本库（.gitignore 的 *.log 规则加一条白名单）
b155b40 2026-10-28 11:00:11 28q: 结果文档补一条本地状态说明（27e 补丁打在本地 mu 副本，生产未动）

$ git diff --name-status cad8c62 HEAD
M	.gitignore
A	deploy/verification-28q/rehearsal.log
M	dsh-out/DSH-RESULT-2026-10-28q.md
```

- `.gitignore` 新增一行：`!deploy/verification-28q/rehearsal.log`
- `deploy/verification-28q/rehearsal.log`：69,090 B / 1063 行入库
- `dsh-out/DSH-RESULT-2026-10-28q.md` 追加 2 行：27e 补丁只打在本地 mu 副本（原文件留档 `E:\破甲工作区\.tmp\28q\backup\packrivo-live\mu-plugins\`，sha256 `abe6f134…`），生产那份没有动。

任务单新答复：无。`packrivo-v2/docs/` 在 cad8c62..HEAD 零变更，无新任务单（无 28r）。

## 2. rehearsal.log（新入库，与 §15 已核验的第四遍一致）

阶段结构（六阶段，仍是 **2 次正向 + 1 次回滚**）：

```
阶段 0 · 演练环境复位          restore_slug=130 missing=0 无正文依据=0
                              restore_slug=8 missing=0 无正文依据=8
阶段 1 · 正向执行 RUNBOOK 第 0–8 步    step0 17:28:17 … step8 结束 exit=0
阶段 2 · 整体回滚 RUNBOOK 第 9 步      exit=0；s-after-rollback == s-baseline IDENTICAL ✓
阶段 3 · 再次正向执行 RUNBOOK 第 1–8 步（验证可重复）  8 块全 exit=0；s-forward2 == s-forward1 IDENTICAL ✓
阶段 4 · 补跑：第 0.1 步数据库导出探针（轮次之后，17:56 单独执行）
```

关键行（rehearsal.log 原文）：

```
apply_updated=130 already_current=4 URL_and_body_check=PASS
apply_updated=7 already_current=1 H1_body_URL_check=PASS
rollback_updated=130 already_current=4 URL_and_body_check=PASS
rollback_updated=7 already_current=1 H1_body_URL_check=PASS
```

rehearsal-verify.json（generatedAt 2026-10-28T17:57:00.019Z）：
- 阶段1 9 个步骤块、阶段2 step9、阶段3 8 个步骤块全部 exit=0；
- snapshots：s-after-rollback 与 s-baseline 同为 `4972cd81…3deb`；s-forward2 与 s-forward1 同为 `4247693f…b392`；
- diffs：`回滚后 vs 基线 IDENTICAL ✓`、`第二次正向 vs 第一次正向 IDENTICAL ✓`；
- 该 json **无整轮/三轮 PASS 结论字段**。

## 3. 轮内 step0 未跑 §0.1 导出探针（新发现）

rehearsal.log 阶段 1 的 step0 输出止于「STEP 0.2 上传清单核对」，随后即 `step0 结束 exit=0`，**无探针命令**；探针仅出现在阶段 4 补跑（probe.log）。而轮内 step1 的 db export 直接 `Success: Exported to '/tmp/packrivo-before-launch.sql'.`（17:30）。阶段 4 补跑先拆掉包装，裸探针复现：

```
mariadb-dump: Got error: 2026: "TLS/SSL error: SSL is required, but the server does not support it" when trying to connect
-rw-r--r--    1 root     root             0 Oct 28 17:56 /tmp/probe.sql
```

再按 RUNBOOK 打 `--skip-ssl` 包装（追加在参数末尾）后：

```
Success: Exported to '/tmp/probe.sql'.
-rw-r--r--    1 root     root      36381501 Oct 28 17:56 /tmp/probe.sql
40
```

即：轮内 step1 的成功依赖容器上一轮遗留的 `--skip-ssl` 包装（容器态遗留），并非轮内 step0 探针路径；与 supplement2 D.2.1「每轮先跑 0.1 探针、不得依赖上次遗留包装」不符。

## 4. supplement2 / rehearsal.sh 守卫：未落地

`git show HEAD:deploy/RUNBOOK-launch.md | grep -E "守卫|FAIL|3\.0|附录 D"` → **零命中**。
HEAD RUNBOOK 只有并行链 cad8c62 自写的近似内容：§0.1 导出探针+`--skip-ssl` 包装指引、2.5 校验行（无硬 FAIL 语义）、9.3 rollback-check（无 site 守卫）。supplement2 的「2.5 硬 FAIL / 3.0 应用前守卫 / 9.3 回滚前守卫 / 附录 D」四项均不在。

`git show HEAD:deploy/verification-28q/scripts/rehearsal.sh`（自 cad8c62 后未变，256 行）：
- 首行 `set -u`，无 `set -e`；无 `exit 1`/`FAIL` 硬判；step0 无探针命令；
- 仅 step9 含两条 `rollback-check`（原 RUNBOOK 27g/27i 命令，非 supplement2 site 守卫）；
- 探针独立在 `scripts/step0-probe.sh`，本轮作为「阶段 4 补跑」在轮次之外执行。

## 5. Claude 核验 / 新生产授权（bd17806 之前：均不存在；bd17806 起：Claude 核验已出现，见 §6）

- bd17806 之前全仓库检索：无任何 28q 演练的 Claude 核验结论（DSH-RESULT-2026-10-28q.md 无核验节）。
- 任务单 28q 答复 3 仍为唯一口径：「先改 RUNBOOK，再在本地把整份 RUNBOOK 完整演练一遍，包括回滚。演练通过、Claude 核验之后，才会再授权上生产。」
- cad8c62..HEAD（截至 b155b40）仅 3 文件变更，无任何新生产授权记录。

## 6. bd17806（11:10:02）新任务单 28r：Claude 核验结论出现、生产授权仍无

`git show bd17806` 仅新增 `packrivo-v2/docs/DSH-TASK-2026-10-28r.md`（70 行）。要点：

- Claude 对 28q 核验结论（28r「背景」节，Co-Authored-By: Claude Opus 5.5）：「**28q 基本通过**。哈希、版本、四份快照都独立核对过；在容器里重跑快照，结果 `4247693f…` 与正向一致。但有三处要处理」：
  1. 最后手段没有演练过（`docker cp db.sql → db import` 一次没跑；28q 结果文档 §6 写的「db import 成功」不属实）；
  2. `exit=0` 没有意义（rehearsal.sh 每步末条 `set +x`，exit 恒 0，「18 块全 exit=0」证明不了任何事）；
  3. 包装可能不在（容器重建即丢，最后手段时包装在否未验证）。
- 28r 任务单规则：「**本轮不碰生产**：不 SSH，不碰密码」→ 无新生产授权。
- 28r §4.4：rehearsal.sh「每个 step 结束时统计本步 FAIL 的数量，有 FAIL 就 exit 1；或者至少不要再用 exit 码当证据（二选一）」；§4.1：RUNBOOK 最后手段加「先重跑 0.1 导出探针确认包装在位」前置。→ 与 supplement2 的 2.5 硬 FAIL / D.2.1 同向，已获任务单背书，但仓库尚未落地。
- 28r §3：验收「每条判据都打印 PASS/FAIL，不能只靠 exit 码」→ 印证「三轮整轮 PASS」按严格口径仍不成立。
- 结论：生产第 1 步重跑条件仍不满足（严格口径三轮整轮 PASS 无 / Claude 核验为「基本通过+三缺陷」有条件结论、未到另行授权 / 新生产授权无 / supplement2 与 rehearsal.sh 硬判未落地）→ 第 2–8 步与 SSH 生产继续禁止，继续停止等待。
