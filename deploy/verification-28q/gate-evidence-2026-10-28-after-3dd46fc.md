# 门禁证据摘录 · 2026-10-28 · 3dd46fc 之后（意图 682 独立只读核查）

来源：本机只读命令输出摘录。未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未读任何凭据。
（本文件与 LAUNCH-LOG §18 同轮提交，为 §18 结论的独立留证。）

## A. 仓库增量状态（3dd46fc 之后）

```
git rev-parse HEAD                       → 3dd46fca18ad84b1050190ac0e4e7931a5e4bd32
git fetch origin 后 rev-list --count main...origin/main → 0  0（零新提交，origin/main==HEAD）
git status --porcelain -uall             → M deploy/RUNBOOK-launch.md
                                            M dsh-out/DSH-RESULT-2026-10-28q.md
                                            （仅这 2 处；无任何未跟踪文件）
git diff --stat                          → deploy/RUNBOOK-launch.md | 67 +++（+58/−11 双 hunk）
                                            dsh-out/DSH-RESULT-2026-10-28q.md | 2 +-（§6 一行）
deploy/verification-28r/                 → 不存在（28r §5 交付物缺位）
dsh-out/DSH-RESULT-2026-10-28r.md        → 不存在
packrivo-v2/docs/（仓库内外两处）最新      → DSH-TASK-2026-10-28r.md（11:09/11:10，bd17806）；无 28r 答复、无新任务单
```

## B. 在途 RUNBOOK 声称（工作树，未提交；RUNBOOK mtime 11:23，早于演练终态 11:24–11:26）

```
L600: 28r 本地实测：L-1…L-6 全 PASS；L3 做完后整站快照 sha256 与 28q 基线 `4972cd81…` **逐字节一致**。证据：`deploy/verification-28r/lastresort.log`。
L640: | **最后手段（28r）** | 第 9 步末尾的整库导入 + 9.6/9.5/9.4/9.2 文件部分 | L-1…L-6 全 PASS；整站快照回到基线 `4972cd81…`（逐字节一致） |
L633: 28r 起改成——每一步的判据打印 `PASS:` / `FAIL:`，`step_end` 统计本步 `PASS=n FAIL=m`，有 FAIL 就 `exit 1`（`bash rehearsal.sh selftest` 可自检这套机制）。
L637–639: 正向（28q）/回滚（28q）/再正向（28q）…「各步判据行全 PASS」
```

## C. 真实演练日志（.tmp\28q\rehearsal\home\web\rehearsal-28r\lastresort.log，136 行终态，只读摘录）

```
PASS: 起点快照 = 4247693fa3a31f35477dcb059ad79e4ae5c13de861f94dfcc3848fe57f81b392
db import（无包装）退出码 = 1        （Error: ERROR 2026 (HY000): TLS/SSL error）
表数（导入失败后）= 40               （http://nginx ← siteurl 仍可读）
PASS: 无包装时 db import 失败（rc=1）—— 证明导入依赖包装
探针导出字节数 = 35265982
PASS: 导出探针 > 0 字节（35265982）
db import（有包装）退出码 = 0
PASS: 有包装时 db import 成功（rc=0）
canary 剩余条数 = 0（期望 0）
PASS: canary 已被整库导入覆盖掉（证明会丢上线后新数据）
FAIL: packrivo-quote 仍在插件列表
FAIL: /get-a-quote/ 200
PASS: 9.6 mu 恢复且 before-mu.sha256 全 OK
Deleted 'packrivo-quote' plugin. / Success: Deleted 1 of 1 plugins.
最终整份 sha256 = 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
PASS: 最终快照 = 基线 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
############ 汇总：PASS=16 FAIL=2 ############
CURRENT_LAUNCH=packrivo-launch-20261028-1743
EXIT=1
```

同目录产物：s-start.txt(847 行 4247693f…) / s-after-import.txt(847 行 a84ef535…) / s-final.txt(845 行 4972cd81…) / titles-after-import.txt==titles-baseline.txt（fc: no differences）/ start-vs-after-import.diff / after-import-vs-final.diff（final-vs-baseline.diff 因相等未生成）。

## D. 28q 判据行证据（deploy/verification-28q/rehearsal.log 入库版 e68b87a）

```
Select-String "PASS:|FAIL:|判据" → 仅 7 行命中，全部为「### STEP n 通过判据 ###」标题，无逐条 PASS/FAIL 判据行
Select-String "exit=0"          → 18 行命中（Claude 已否定的恒 exit=0 证据体系）
```

## E. rehearsal.sh 守卫现状（Claude 必改 2）

```
deploy/verification-28q/scripts/rehearsal.sh：266 行、mtime 09:29:37（cad8c62 入库版，未再改动）
  无 set -e；无 step_end/selftest/ckp/ckf；唯一 exit=L268 用法错误 exit 2；无 skip-ssl/probe
.tmp\28q\rehearsal.sh（仓库外，11:18，18,254 B，patch-rehearsal.mjs 产物）：
  L26 ckp / L27 ckf / L28 ck / L29 step_end / L41 selftest / L47 if step_end —— 机制只在仓库外副本
```

## F. supplement2（2.5/3.0/9.3/附录 D）落地核查（工作树 RUNBOOK）

```
grep 附录 D / 3.0 预 apply 守卫 / 9.3 预回滚 site 守卫 → 零命中
（仅附录 A/B/C；L231「# 2.5 校验」、L536「# 9.3 内容」为 28q 正文小标题，非 supplement2 硬判守卫）
RUNBOOK-launch.28q-supplement2.patch 在位（本链拟制、自注「待负责人批准」），未应用
```

## G. 容器（只读 docker ps，未启动/停止/接入任何容器）

```
r28r-runner Up 10 minutes（28r 演练 runner，已结束脚本、EXIT=1）
r28q-host / php / nginx / mysql / redis Up 2 hours
r28q-runner Exited (0) 31 minutes ago
packrivo-live-wp-1 Up 2 hours / packrivo-live-db-1 Up 2 hours (healthy)
```
