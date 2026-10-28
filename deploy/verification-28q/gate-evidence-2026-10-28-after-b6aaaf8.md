# 28s 增量门禁证据摘录（b6aaaf8 之后，2026-10-28 14:11–14:13 本机只读核查）
> 本文件为只读核查的独立证据摘录，与 LAUNCH-LOG-2026-10-28.md §34 对应。
> 全程未执行任何脚本、未启动/停止/接入容器、未修改他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。

## 1. 增量范围（b6aaaf8 之后）
- **零新提交**：
  - `git rev-parse HEAD` = `b6aaaf88a08b5f16b1b3f8bde4188d544392a253`
  - `git fetch origin` 后 `git rev-parse origin/main` = `b6aaaf88a08b5f16b1b3f8bde4188d544392a253`
  - `git rev-list --left-right --count HEAD...origin/main` = `0  0`
  - `git status`：On branch main / Your branch is up to date with 'origin/main'. / nothing to commit, working tree clean；`git stash list` 为空；`git branch -a` 仅 main；`git reflog` 无 reset/rebase 痕迹。
- dsh-in/ 无新文件：quote-strings-28m.json (03:12:22) / untranslated-27j.json (00:33:54)，与 §33 相同 → 无 28s 新任务单答复。
- dsh-out/DSH-RESULT-2026-10-28s.md：mtime 13:25:39、14087 B；`git hash-object` = `7e92a2c534a06add877842f0c25a557985d8e321`（工作树 == HEAD blob == fact #717 的 7e92a2c5，内容未变）——仍自述「本轮不碰生产」，无任何 Claude 复验文本、无新生产授权。
- deploy/verification-28s/ 全部产物 mtime ≤ 13:26:09（guard-selftest.log 为最新），无新产物；.tmp/28s 目录不存在（原始日志目录已清理，仓库内 deploy/verification-28s/ 证据链为留存副本）。
- 全仓库 14:09:48（b6aaaf8 提交时刻）之后零新文件写入（深度扫描零命中，含隐藏目录）。

## 2. tag10 整轮全 PASS 证据复检（14:11–14:13）
- `docker ps -a`：仍仅 `r28s-runner(r28q-host:10) Exited (0)`（12:51:16 创建，约 52 分钟前退出）与 `r28s-neg(r28q-host:11) Exited (0)`（13:22:14 创建，约 47 分钟前退出），无新 runner/negtest 容器、无运行中 28s 容器；php/r28q-host 等基础容器 Up 5 hours，状态与 §33 一致。
- rehearsal.log：`################ 28s 结束 2026-10-28T20:19:10Z ################` / 末行 `EXIT=0`；无清档迹象。
- step-summary.txt：step0–9 = `PASS=6/10/8/11/3/4/5/1/9/8 :: FAIL=0 :: EXIT=0`（合计 65 PASS / 0 FAIL）。
- Get-FileHash（本机复检）：
  - s-reset.txt = `4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB`
  - s-after-rollback.txt = 同上（= 基线 4972cd81…）
  - s-after-negtest.txt = 同上（= 基线 4972cd81…）
  - s-forward.txt = `4247693FA3A31F35477DCB059AD79E4AE5C13DE861F94DFCC3848FE57F81B392`（= 正向快照 4247693f…）
- guard-selftest.log：[A] 合法 JSON `PASS: …site 与本轮一致` ×2；[B] 他站 JSON `PASS` ×2 + `FAIL: …非本轮产物`（bogus）；[C] 空目录场景计数 0。
- negtest-step3.log：3.0 守卫真实拦截 bogus JSON（site=http://wrong-site.invalid，期望 http://nginx），`3.0 守卫拦截，apply 不执行`、`本步判据：PASS=2 FAIL=1`。
- negtest.log：`反向自检后快照 sha256 = 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb`、`PASS: 反向自检没有改动站点（仍 = 基线）`、`EXIT=0`。
- rollback-vs-negtest.diff = 0 B。
- **结论：fact #717 的「tag10 整轮全 PASS」证据链在本节核查时点（14:11–14:13）仍成立，未被任何重启、失败、清档或新提交推翻。**

## 3. 两项外部前置
- Claude 对 28s 最终修正结果的明确复验：**不存在**（零新提交/答复/文件；全仓库无任何 Claude 复验文本）。
- 负责人新的生产上线授权：**不存在**（无任何新授权文件/提交/答复；DSH-RESULT-28s 仍为工作台 #56 自述交付、自述「本轮不碰生产」；覆盖第 1–8 步与 Cloudflare 清缓存复测的新授权无从核对）。

## 4. 门禁结论
- 生产第 1 步重跑条件仍不满足（两项外部前置仍缺）→ 第 1 步维持 FAIL、第 2–8 步与 SSH 生产继续禁止、继续停止等待；仅当两项均获明确证据时才可确认进入生产第 1 步，等待下一意图执行。
