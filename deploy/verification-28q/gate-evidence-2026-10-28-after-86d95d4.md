# 28s 增量门禁证据摘录（86d95d4 之后，2026-10-28 14:01–14:04 本机只读核查）
> 本文件为只读核查的独立证据摘录，与 LAUNCH-LOG-2026-10-28.md §31 对应。
> 全程未执行任何脚本、未启动/停止/接入容器、未修改他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。

## 1. 增量范围（86d95d4 之后）
- **零新提交**：
  - `git rev-parse HEAD` = `86d95d4d34a15904f4702fde5f7e83a0c72117d3`
  - `git fetch origin` 后 `git rev-parse origin/main` = `86d95d4d34a15904f4702fde5f7e83a0c72117d3`
  - `git rev-list --left-right --count HEAD...origin/main` = `0  0`
  - `git status`：On branch main / Your branch is up to date with 'origin/main' / nothing to commit, working tree clean（含无未跟踪文件）
- **无 28s 新任务单答复**：dsh-in/ 仍 quote-strings-28m.json（03:12:22）、untranslated-27j.json（00:33:54），无新文件；dsh-in 最近提交仍 102348d @03:28。
- **dsh-out/DSH-RESULT-2026-10-28s.md 未变**：mtime 13:25:39、14087 B、185 行；`git hash-object` = `7e92a2c534a06add877842f0c25a557985d8e321`（与 fact #711 的 7e92a2c5 一致）；关键表述仍在：
  - L5：「本轮不碰生产：无 SSH、无生产写入、未读未打印任何密码/密钥/token，`.cf.env` 未打开。」
  - L95：「| step7 | **PASS=1 FAIL=0** | 0 | `/de/` 200（Cloudflare 项在本地跳过，见既有说明） |」
  - L161：「- 生产未动。」
  - 无 Claude 复验文本、无新生产授权文本。
- deploy/verification-28s/ 全部产物 mtime ≤ 13:26:09（guard-selftest.log 最新 13:26:09），无新产物；.tmp/28s/art/rehearsal.log 与 deploy/verification-28s/rehearsal.log sha256 一致（64212C86C190E1F58E5E54B27F0D918D9C7686866764B23B7E8752621EAF35B5）。
- 全仓库 mtime > 13:58:40 的文件 0 个；.tmp/28s 全树 mtime > 13:40 的文件 0 个。

## 2. tag10 整轮全 PASS 证据未被推翻（本机独立复检 14:01–14:04）
- **无新一轮运行、无重启**：`docker ps -a` 关键条目：
  - `r28s-runner  r28q-host:10  Exited (0) 41 minutes ago`（12:51:16 创建，≈13:19 结束）
  - `r28s-neg     r28q-host:11  Exited (0) 36 minutes ago`（13:22:14 创建，≈13:24 结束）
  - 无新 runner/negtest 容器、无运行中 28s 容器；其余容器状态与 §30 一致。
- rehearsal.log 末行：`28s 结束 2026-10-28T20:19:10Z`、`EXIT=0`，无清档迹象。
- step-summary.txt：step0–9 逐行 `PASS=6/10/8/11/3/4/5/1/9/8 :: FAIL=0 :: EXIT=0`（合计 65 PASS / 0 FAIL）。
- **四快照本机 Get-FileHash 复检**：
  - s-reset.txt / s-after-rollback.txt / s-after-negtest.txt sha256 = `4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB`（= 基线 4972cd81…）
  - s-forward.txt sha256 = `4247693FA3A31F35477DCB059AD79E4AE5C13DE861F94DFCC3848FE57F81B392`（= 正向快照 4247693f…）
- guard-selftest.log：合法 JSON `PASS: …site 与本轮一致` ×2、他站 JSON `FAIL: …非本轮产物`、空目录场景计数 0。
- negtest.log / negtest-step3.log：3.0 守卫真实拦截 bogus JSON（site=http://wrong-site.invalid，期望 http://nginx）；`negtest step3 EXIT=1`、「3.0 守卫拦截，apply 不执行」、apply 计数 0、反向自检后快照 sha256=`4972cd81…`=基线、`PASS: 反向自检没有改动站点（仍 = 基线）`、`EXIT=0`。
- forward-vs-rollback.diff 434 行、reset-vs-forward.diff 434 行、rollback-vs-negtest.diff 0 B。

## 3. 两项外部前置核查
- **Claude 对 28s 最终修正结果的明确复验：仍不存在**。零新提交/零新答复/零新文件；`git grep 复验 HEAD` 仅命中本链既往日志与 gate-evidence 摘录，无任何 Claude 复验文本。
- **负责人新的生产上线授权：仍不存在**。全仓库检索「批准上线/上线授权/进入生产」仅命中本链既往日志与 packrivo-v2/docs/MIGRATION.md 历史文档；DSH-RESULT-28s 仍为工作台 #56 自述交付且自述「本轮不碰生产」。授权是否覆盖本次 RUNBOOK 第 1–8 步及 Cloudflare 清缓存复测：无授权对象，无从核对，判定不满足。

## 4. 门禁结论
**⇒ 两项外部前置（Claude 复验、负责人新生产授权）仍缺：生产第 1 步重跑条件不满足，生产第 1 步维持 FAIL；第 2–8 步与 SSH 生产继续禁止；本链继续停止等待，绝不执行第 2–8 步。**
（tag10 整轮全 PASS 与 DSH-RESULT-28s 交付继续成立，但门禁三前置中两项外部前置仍缺，任一缺即不满足；仅当两项均获明确证据时才可确认进入生产第 1 步，等待下一意图执行。）
