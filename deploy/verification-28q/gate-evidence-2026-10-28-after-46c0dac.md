# 28s 增量门禁核查证据摘录 · after 46c0dac（2026-10-28 13:52 本机只读）

- 零新提交：HEAD == origin/main == 46c0dac358614846134aef443996924f9c4b82f5（46c0dac 提交 2026-10-28 13:47:47 -0700）；git fetch 后 rev-list --left-right --count HEAD...origin/main = 0  0；git status 干净、无未跟踪文件。
- 无 28s 新任务单答复：dsh-in/ 无新文件（最新 quote-strings-28m.json 03:12:22）。
- DSH-RESULT-2026-10-28s.md 仍 13:25:39 版（14087 B），hash-object = 7e92a2c534a06add877842f0c25a557985d8e321；L5「本轮不碰生产：无 SSH、无生产写入…」、L95「Cloudflare 项在本地跳过」、L161「生产未动」。
- docker ps -a（只读）：r28s-runner(r28q-host:10) Exited (0)，创建 12:51:16；r28s-neg(r28q-host:11) Exited (0)，创建 13:22:14；无新容器、无运行中 28s 容器。
- step-summary.txt：step0–9 PASS=6/10/8/11/3/4/5/1/9/8 :: FAIL=0 :: EXIT=0（合计 65 PASS / 0 FAIL）。
- rehearsal.log：`28s 开始 2026-10-28T19:51:17Z` → `28s 结束 2026-10-28T20:19:10Z`，末行 `EXIT=0`。
- 四快照 Get-FileHash（本机复核）：
  - s-reset.txt / s-after-rollback.txt / s-after-negtest.txt = 4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB（= 基线 4972cd81…）
  - s-forward.txt = 4247693FA3A31F35477DCB059AD79E4AE5C13DE861F94DFCC3848FE57F81B392
- guard-selftest.log：合法 JSON `PASS: …site 与本轮一致`×2；他站 JSON `FAIL: …非本轮产物`；空目录场景计数 0。
- negtest.log：`negtest step3 EXIT=1`；`FAIL: 3.0 …rollback-bogus.json 非本轮产物（site=http://wrong-site.invalid，期望 http://nginx）`；`3.0 守卫拦截，apply 不执行`；apply 计数 0；反向自检后快照 sha256=4972cd81…=基线；`PASS: 反向自检没有改动站点（仍 = 基线）`；`EXIT=0`。
- 全工作区 13:47:31 之后零新文件（仅上一节落盘的本日志本体）。
- Claude 对 28s 最终修正结果的明确复验：无。负责人新生产上线授权：无。
- 门禁结论：生产第 1 步重跑条件仍不满足，生产第 1 步维持 FAIL，第 2–8 步与 SSH 生产继续禁止，继续停止等待。
