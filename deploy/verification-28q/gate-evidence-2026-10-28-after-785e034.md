# 门禁证据摘录 · 785e034 之后的 28s 增量核查（intent 694 落盘，对应 LAUNCH-LOG §23）

来源：全部为主机侧只读观察（git / 文件读取 / docker ps）。tag8 轮条目因日志已被 tag9 重启清空，取自 fact #693（intent 692 观察时留证）；tag9 轮条目为 intent 694 直接观察。

## 1. 增量范围（2026-10-28 ~12:39 本地时，本意图开始与结束各核一次）
- `git rev-parse HEAD` == `git rev-parse origin/main` == `785e034aaf1f8d1be8f255a298d2c9725642bdb6`（零新提交）
- `Test-Path dsh-out\DSH-RESULT-2026-10-28s.md` = False；`Test-Path deploy\verification-28s` = False
- `git status --porcelain`：仅 ` M deploy/RUNBOOK-launch.md`（并行链在途，零触碰）；`git ls-files --others` = 0

## 2. tag8 轮 step3 FAIL 实况（fact #693 留证；原日志已被 tag9 重启清空，仅此留证）
- step-summary.txt：`step3 :: 本步判据：PASS=10 FAIL=1 :: EXIT=1`
- FAIL 行：`FAIL: 3a 27g verify（URL_and_non_H1_body_unchanged=134/134）`——27g verify 实际输出末行为 URL_and_non_H1_body_unchanged=134/134（正向结果），脚本按 `*metadata_mismatches=0*` 模式匹配不命中致 FAIL
- 同轮其余 10 条 PASS：27g 130+4=134、27i 7+1=8、两 apply_updated>0、URL_and_body_check=PASS、H1_body_URL_check=PASS、3c/3d rollback 依据非空、3e 产品页 200

## 3. tag9 轮（12:23:53 第三次启动）观察时点实况（intent 694 直接观察，~12:39 采样）
- `docker ps -a`：`r28s-runner|r28q-host:9|Up 14 minutes|2026-10-28 12:23:53 -0700 PDT`
- s-reset.txt（69,238B）Get-FileHash SHA256 = `4972CD8195D7B40A10DAC4140273BA7FAEF4E152B2B886489BFCC624F2773DEB`（=基线）
- step-summary.txt（242B，12:37:41）：
  - `step0 :: 本步判据：PASS=6 FAIL=0 :: EXIT=0`
  - `step1 :: 本步判据：PASS=10 FAIL=0 :: EXIT=0`
  - `step2 :: 本步判据：PASS=8 FAIL=0 :: EXIT=0`
  - `step3 :: 本步判据：PASS=11 FAIL=0 :: EXIT=0`
  - `step4 :: 本步判据：PASS=3 FAIL=0 :: EXIT=0`
- step3.log 关键行：`PASS: 3b 27i verify metadata_mismatches=0`、`PASS: 3·27g 总数 = 134（130+4=134）`、`PASS: 3·27i 总数 = 8（7+1=8）`、`PASS: 3·27g apply_updated > 0（演练要求真实写入）`、`PASS: 3·27i apply_updated > 0（演练要求真实写入）`、`PASS: 3c 27g rollback 依据非空`、`PASS: 3d 27i rollback 依据非空`、`PASS: 3e 产品页 200`、`本步判据：PASS=11 FAIL=0`
- step5.log 存在（3,430B，12:38:53），step-summary 尚未写入 step5 → step5 进行中
- 无 s-forward、无第 9 步回滚、无 negtest 产物、无 DSH-RESULT-28s、无 verification-28s → 整轮远未完成，不构成整轮 PASS
