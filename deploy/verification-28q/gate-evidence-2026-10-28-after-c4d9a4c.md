# 28s 增量门禁证据摘录（c4d9a4c 之后，2026-10-28 14:04–14:05 本机只读核查）
> 本文件为只读核查的独立证据摘录，与 LAUNCH-LOG-2026-10-28.md §32 对应。
> 全程未执行任何脚本、未启动/停止/接入容器、未修改他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。

## 1. 增量范围（c4d9a4c 之后）
- **零新提交**：
  - `git rev-parse HEAD` = `c4d9a4cec157eb5a4b54fce2c13aadaa87550372`
  - `git fetch origin` 后 `git rev-parse origin/main` = `c4d9a4cec157eb5a4b54fce2c13aadaa87550372`
  - `git rev-list --left-right --count HEAD...origin/main` = `0  0`
  - `git status`：On branch main / Your branch is up to date with 'origin/main'. / nothing to commit, working tree clean
- **无新任务单答复**：dsh-in/ 仅 quote-strings-28m.json（03:12:22）、untranslated-27j.json（00:33:54）。
- **DSH-RESULT-2026-10-28s.md 未变**：mtime 13:25:39、14087 B、`git hash-object` = `7e92a2c534a06add877842f0c25a557985d8e321`；关键字命中：
  - L5: 本轮不碰生产：无 SSH、无生产写入、未读未打印任何密码/密钥/token，`.cf.env` 未打开。
  - L161: 生产未动。
- deploy/verification-28s/ 产物 mtime 均 ≤ 13:26:09；deploy/verification-28q/ 目录 mtime 14:02:36 = §31 自身证据文件 gate-evidence-2026-10-28-after-86d95d4.md（已含于 c4d9a4c）。
- E:\破甲工作区\.tmp\28s 全树 13:26:00 之后零新文件。

## 2. tag10 证据链本机复检（14:04–14:05）
- 容器（只读枚举）：`docker ps -a --filter "name=r28s"` →
  - `r28s-neg|r28q-host:11|Exited (0) 41 minutes ago`
  - `r28s-runner|r28q-host:10|Exited (0) 46 minutes ago`
- rehearsal.log 末行：`28s 结束 2026-10-28T20:19:10Z` / `EXIT=0`。
- step-summary.txt：`step0..9 :: PASS=6/10/8/11/3/4/5/1/9/8 :: FAIL=0 :: EXIT=0`。
- 四快照 Get-FileHash（SHA256）：
  - s-reset.txt / s-after-rollback.txt / s-after-negtest.txt = `4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB`（= 基线）
  - s-forward.txt = `4247693FA3A31F35477DCB059AD79E4AE5C13DE861F94DFCC3848FE57F81B392`
  - rollback-vs-negtest.diff = 0 B；reset-vs-forward.diff = 31880 B。
- step3.log 关键行：`input_pages=134 preflight=PASS`、`apply_updated=130 already_current=4 URL_and_body_check=PASS`、`URL_and_non_H1_body_unchanged=134/134`。
- step9.log 末行：`本步判据：PASS=8 FAIL=0`（9a before-mu.sha256 OK / 9b stylesheet 回到父主题 / 9c 子主题目录已删 / 9d 报价插件已移除 / 9e 首页 200 / 9f /get-a-quote/ 回到 301）。
- guard-selftest.log：合法 JSON PASS×2、bogus JSON FAIL、空目录计数 0。
- negtest.log：bogus site=http://wrong-site.invalid 被 3.0 守卫拦截，`negtest step3 EXIT=1`、apply 计数 0、自检后快照 sha256=`4972cd8195…`=基线、EXIT=0。

## 3. 两项外部前置
- Claude 对 28s 最终修正结果的明确复验：**不存在**（零新提交/答复/文件，无任何 Claude 复验文本）。
- 负责人新生产上线授权（明确覆盖 RUNBOOK 第 1–8 步 + Cloudflare 清缓存复测）：**不存在**（DSH-RESULT-28s 仍为工作台 #56 自述「本轮不碰生产」）。
- 结论：生产第 1 步重跑条件仍不满足，第 1 步维持 FAIL，第 2–8 步与 SSH 生产继续禁止，本链继续停止等待。
