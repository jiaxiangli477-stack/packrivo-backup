# 28s 增量门禁核查独立证据摘录（1536f5d 之后，2026-10-28 13:44 本机只读核查）

本摘录为 LAUNCH-LOG-2026-10-28.md §28 的独立只读证据，全部来自 2026-10-28 13:43–13:44 本机实时复检。

## 1. 零新提交 / 工作区干净

```
> git rev-parse HEAD
1536f5d0ea67006426750f2de6654a6b5e060469
> git log -1 --format=%ci
2026-10-28 13:31:38 -0700
> git fetch --all   （无输出）
> git rev-list --left-right --count HEAD...origin/main
0       0
> git status --porcelain
（空）
> git ls-files --others
（空）
```

## 2. 无新任务单答复 / DSH-RESULT-28s 未变

dsh-in/ 无新文件：最新仍 `2026/10/28 3:12:22  quote-strings-28m.json`。

`dsh-out/DSH-RESULT-2026-10-28s.md`：`2026/10/28 13:25:39`，14087 B，内容未变（与 §27 核查一致）：
- L5：`本轮不碰生产：无 SSH、无生产写入、未读未打印任何密码/密钥/token，.cf.env 未打开。`
- L95：`| step7 | PASS=1 FAIL=0 | 0 | /de/ 200（Cloudflare 项在本地跳过，见既有说明） |`
- L161：`- 生产未动。`
- 全文无 Claude 复验文本、无新生产授权。

## 3. tag10 整轮全 PASS 证据未被动摇（本机复检）

```
> docker ps -a（节选）
r28s-neg    | r28q-host:11 | Exited (0) 20 minutes ago | 2026-10-28 13:22:14 -0700 PDT
r28s-runner | r28q-host:10 | Exited (0) 25 minutes ago | 2026-10-28 12:51:16 -0700 PDT
（无新 runner/negtest 容器、无运行中 r28s 容器）
```

```
> deploy/verification-28s/rehearsal.log 首/末
################ 28s 开始 2026-10-28T19:51:17Z ################
################ 28s 结束 2026-10-28T20:19:10Z ################
EXIT=0
```

```
> deploy/verification-28s/step-summary.txt（逐行）
step0 :: 本步判据：PASS=6 FAIL=0 :: EXIT=0
step1 :: 本步判据：PASS=10 FAIL=0 :: EXIT=0
step2 :: 本步判据：PASS=8 FAIL=0 :: EXIT=0
step3 :: 本步判据：PASS=11 FAIL=0 :: EXIT=0
step4 :: 本步判据：PASS=3 FAIL=0 :: EXIT=0
step5 :: 本步判据：PASS=4 FAIL=0 :: EXIT=0
step6 :: 本步判据：PASS=5 FAIL=0 :: EXIT=0
step7 :: 本步判据：PASS=1 FAIL=0 :: EXIT=0
step8 :: 本步判据：PASS=9 FAIL=0 :: EXIT=0
step9 :: 本步判据：PASS=8 FAIL=0 :: EXIT=0
（合计 65 PASS / 0 FAIL）
```

```
> Get-FileHash（SHA256，本机复核）
s-reset.txt           (845 行) 4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB
s-forward.txt         (847 行) 4247693FA3A31F35477DCB059AD79E4AE5C13DE861F94DFCC3848FE57F81B392
s-after-rollback.txt  (845 行) 4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB
s-after-negtest.txt   (845 行) 4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB
（reset / 回滚后 / 反向自检后三者 = 基线 4972CD81…；正向 = 4247693F…）
```

```
> deploy/verification-28s/guard-selftest.log（节选）
PASS: /pkg/rollback/rollback-27g.json site 与本轮一致
PASS: /pkg/rollback/rollback-27i.json site 与本轮一致
FAIL: /pkg/rollback/rollback-bogus.json 非本轮产物
（[C] 空目录场景计数 0）
```

```
> deploy/verification-28s/negtest.log（节选）
################ 28s 反向自检 2026-10-28T20:22:14Z ################
bogus site=http://wrong-site.invalid
negtest step3 EXIT=1
FAIL: 3.0 /pkg/rollback/rollback-bogus.json 非本轮产物（site=http://wrong-site.invalid，期望 http://nginx）…
3.0 守卫拦截，apply 不执行 本步判据：PASS=2 FAIL=1
0
PASS: 3.0 守卫拦住他站 JSON，整步非零退出，apply 未执行
反向自检后快照 sha256 = 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
PASS: 反向自检没有改动站点（仍 = 基线）
EXIT=0
```

## 4. 两项外部前置：均不存在

- **Claude 对 28s 最终修正结果的明确复验**：零新提交/零新答复/零新文件，不存在。
- **负责人新的生产上线授权**（须明确覆盖本次 RUNBOOK 第 1–8 步与 Cloudflare 清缓存复测）：无任何新授权文件/提交/答复，不存在（DSH-RESULT-28s 仍自述「本轮不碰生产」）。

## 5. 门禁结论

生产第 1 步重跑条件不满足，生产第 1 步维持 FAIL；第 2–8 步与 SSH 生产继续禁止；本链继续停止等待，绝不执行第 2–8 步。

## 6. 操作边界

全程只读（git fetch/log/rev-parse/status、文件读取、Get-FileHash、docker ps 只读枚举、目录列举）；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未 SSH 生产、未重跑生产第 1 步、未读取或打印任何密码/密钥/token；仅本摘录与 LAUNCH-LOG §28 提交。
