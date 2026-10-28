# 28s 增量门禁证据摘录（885e387 之后，2026-10-28 14:08–14:11 本机只读核查）
> 本文件为只读核查的独立证据摘录，与 LAUNCH-LOG-2026-10-28.md §33 对应。
> 全程未执行任何脚本、未启动/停止/接入容器、未修改他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。

## 1. 增量范围（885e387 之后）
- **零新提交**：
  - `git rev-parse HEAD` = `885e3878821e1e9b33d9ed83fd7c198f2ff4ae10`
  - `git fetch origin` 后 `git rev-parse origin/main` = `885e3878821e1e9b33d9ed83fd7c198f2ff4ae10`
  - `git rev-list --left-right --count HEAD...origin/main` = `0  0`
  - `git status`：On branch main / Your branch is up to date with 'origin/main'. / nothing to commit, working tree clean
- dsh-in/ 无新文件：quote-strings-28m.json (03:12:22) / untranslated-27j.json (00:33:54)
- dsh-out/DSH-RESULT-2026-10-28s.md：mtime 13:25:39、14087 B、144 行、`git hash-object` = `7e92a2c534a06add877842f0c25a557985d8e321`

## 2. tag10 整轮全 PASS 证据复检（14:08–14:11）
- `docker ps -a`：r28s-runner(r28q-host:10) Exited (0)（49 分钟前）；r28s-neg(r28q-host:11) Exited (0)（44 分钟前）；无新容器、无运行中 28s 容器
- rehearsal.log：`################ 28s 结束 2026-10-28T20:19:10Z ################` / 末行 `EXIT=0`
- step-summary.txt：step0–9 = `PASS=6/10/8/11/3/4/5/1/9/8 :: FAIL=0 :: EXIT=0`（合计 65 PASS / 0 FAIL）
- Get-FileHash（本机复检）：
  - s-reset.txt = `4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB`
  - s-after-rollback.txt = 同上（= 基线 4972cd81…）
  - s-after-negtest.txt = 同上（= 基线 4972cd81…）
  - s-forward.txt = `4247693FA3A31F35477DCB059AD79E4AE5C13DE861F94DFCC3848FE57F81B392`（= 正向快照 4247693f…）
- guard-selftest.log：PASS×2（rollback-27g.json / rollback-27i.json site 与本轮一致）、FAIL（rollback-bogus.json 非本轮产物，符合预期）、空目录场景计数 0
- negtest-step3.log：PASS×2（3.0 守卫对 rollback-27g/27i）、`FAIL: 3.0 /pkg/rollback/rollback-bogus.json 非本轮产物（site=http://wrong-site.invalid，期望 http://nginx）`、`3.0 守卫拦截，apply 不执行`、`本步判据：PASS=2 FAIL=1`
- negtest.log：反向自检后快照 sha256 = `4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb`（= 基线）、`PASS: 反向自检没有改动站点（仍 = 基线）`、`EXIT=0`
- rollback-vs-negtest.diff = 0 B；forward-vs-rollback.diff = 31889 B；reset-vs-forward.diff = 31880 B

## 3. 两项外部前置
- Claude 对 28s 最终修正结果的明确复验：**不存在**（零新提交/答复/文件；全仓库无任何 Claude 复验文本）
- 负责人新生产上线授权：**不存在**（无任何新授权文件/提交/答复；DSH-RESULT-28s 仍为工作台 #56 自述交付、自述「本轮不碰生产」；覆盖第 1–8 步与 Cloudflare 清缓存复测的新授权无从核对）

## 4. 门禁结论
- 生产第 1 步重跑条件仍不满足（两项外部前置仍缺）→ 第 1 步维持 FAIL、第 2–8 步与 SSH 生产继续禁止、继续停止等待；仅当两项均获明确证据时才可确认进入生产第 1 步，等待下一意图执行。

