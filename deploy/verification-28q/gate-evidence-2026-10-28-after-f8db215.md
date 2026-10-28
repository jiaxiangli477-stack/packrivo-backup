# 门禁证据摘录（f8db215 之后 · 28s tag10 整轮终态核查 · 2026-10-28 本机只读）

> 本文件为 i702 门禁核查的独立只读证据摘录。原始日志位于 `E:\破甲工作区\.tmp\28q\rehearsal\home\web\rehearsal-28s\`
> 与 `E:\破甲工作区\.tmp\28s\`；并行链已在仓库放入 `deploy/verification-28s/`（未跟踪、未提交）。
> 未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。

## 1 · 增量范围（f8db215 之后）

- 核查采样期（13:20–13:24）为零新提交、verification-28s 未跟踪；**落盘前（13:25–13:26）并行链提交两笔**：
  `ba97e04`（RUNBOOK 三守卫并入 + verification-28s 全套 26 证据文件 + dsh-out/DSH-RESULT-2026-10-28s.md 185 行；32 文件 +6657/−20）、
  `954793e`（guard-selftest.log 转 UTF-8）。两笔内容与本文件第 2–7 节独立只读复核一致。
- 无 Claude 对 28s 最终轮的复验、无新的生产上线授权（DSH-RESULT-28s 为工作台 #56 自述交付，自述「本轮不碰生产」）。

## 2 · tag10 整轮：未重启/未截断/未清档，端到端完整结束

- driver 日志（rehearsal.log 725 行 44152B）：
  `################ 28s 开始 2026-10-28T19:51:17Z ################` … `################ 28s 结束 2026-10-28T20:19:10Z ################`，最后一行 `EXIT=0`。
  19:51:17Z = 12:51:17 本机，与 12:51:16 tag10 启动一致 → 同一整轮。
- 单一连续时间线：reset.log 12:54:53 → step0 12:58:23 → step1 13:00:49 → step2 13:01:15 → step3 13:03:46 →
  step4 13:05:09 → step5 13:07:25 → step6 13:07:50 → step7 13:08:58 → step8 13:10:35 → s-forward 13:12:29 →
  step9.log 13:17:19 → step-summary.txt 13:17:20 → s-after-rollback 13:19:10 → rehearsal.log 13:19:10。
- `docker ps -a`：`r28s-runner | r28q-host:10 | Exited (0)`、`r28s-neg | r28q-host:11 | Exited (0)`，无运行中的 r28s 容器。

## 3 · 正向 step0–8（同一整轮，未被后续失败推翻）

step-summary.txt 原文：

```
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
```

- step3.log 关键行：`27g verify: URL_and_non_H1_body_unchanged=134/134`、`PASS: 3a 27g verify URL_and_non_H1_body_unchanged=134/134`、
  `27g: apply_updated=130 already_current=4 合计=134`、`27i: apply_updated=7 already_current=1 合计=8`、
  `PASS: 3·27g 总数 = 134（130+4=134）`、`PASS: 3·27i 总数 = 8（7+1=8）`、两项 `apply_updated > 0` PASS。
- step0.log 关键行：`mariadb-dump: Got error: 2026: "TLS/SSL error: SSL is required..."` → 补救块装包装 →
  `装包装后探针字节数 = 39397375`、`PASS: 0f 装包装后导出探针 > 0 字节（39397375）`（第 4 次实跑）。
- step2.log：2a–2h 逐条全 PASS（paths.json 存在 / 含 /get-a-quote/ / >=146 / dict=11 / js=11 / 无坏 JSON / 冲突 0 / de 站 lang=de）。

## 4 · 第 9 步回滚（修复后 9.3 守卫真实解析，PASS=8 FAIL=0）

step9.log 关键行（原文）：

```
PASS: 9.3 /pkg/rollback/rollback-27g.json site 与本轮一致
PASS: 9.3 /pkg/rollback/rollback-27i.json site 与本轮一致
...
hub_pages=8 preflight=PASS
backup=reused rows=8
rollback_updated=7 already_current=1 H1_body_URL_check=PASS
...
input_pages=134 preflight=PASS
backup=reused rows=134
rollback_updated=130 already_current=4 URL_and_body_check=PASS
...
PASS: 9a before-mu.sha256 全 OK
PASS: 9b stylesheet 回到父主题
PASS: 9c 子主题目录已删
PASS: 9d 报价插件已移除
PASS: 9e 首页 200
PASS: 9f /get-a-quote/ 回到 301
本步判据：PASS=8 FAIL=0
```

- 9.3 守卫无 PHP parse error、无“site 恒读空”——修复生效，读取到真实证据。
- 27g/27i import rollback 与 i18n 回拷（`cp -a …/i18n/. …/wp-content/uploads/packrivo-i18n/`）实际执行。

## 5 · 快照（本机 Get-FileHash 独立复核）

```
s-reset.txt         => 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
s-forward.txt       => 4247693fa3a31f35477dcb059ad79e4ae5c13de861f94dfcc3848fe57f81b392
s-after-rollback.txt=> 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
s-after-negtest.txt => 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
```

- 回滚后快照逐字节 = 基线 4972cd81（快照 845 行，覆盖 mu sha256 / 23 个 i18n 字典 / 主题 / 插件 / 723 行页面标题 / 关键选项）→ 产品标题及 23 个 i18n 文件全部复位。
- `rollback-vs-negtest.diff` 行数 = 0（negtest 未改动站点）。

## 6 · negtest 3.0 反向自检（真实执行，按预期硬 FAIL）

negtest.log 关键行（原文）：

```
################ 28s 反向自检 2026-10-28T20:22:14Z ################
negtest step3 EXIT=1
PASS: 3.0 /pkg/rollback/rollback-27g.json site 与本轮一致
PASS: 3.0 /pkg/rollback/rollback-27i.json site 与本轮一致
FAIL: 3.0 /pkg/rollback/rollback-bogus.json 非本轮产物（site=http://wrong-site.invalid，期望 http://nginx），先清空 /pkg/rollback
3.0 守卫拦截，apply 不执行
本步判据：PASS=2 FAIL=1
--- apply 是否执行过（应无输出，期望计数 0）---
0
PASS: 3.0 守卫拦住他站 JSON，整步非零退出，apply 未执行
...
反向自检后快照 sha256 = 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
PASS: 反向自检没有改动站点（仍 = 基线）
################ 反向自检结束 ################
EXIT=0
```

- negtest 非“未调用/缺失结果/恒 EXIT=0”：step3 EXIT=1、FAIL 行真实、apply 计数 0。
- 注意：negtest 由并行链以独立容器 r28s-neg（镜像 r28q-host:11）在 driver（r28s-runner，镜像 r28q-host:10）结束后 3 分钟调用；host run28s.sh 驱动脚本本身（11:46:47）仍未引用 negtest.sh——“纳入同一 driver”在脚本层面不成立，但同一整轮、同一产物目录（rehearsal-28s）、同一备份（packrivo-launch-20261028-1958）内已实质执行并留证。

## 7 · 守卫静态验证与修复脚本核对

guard-selftest.log：合法 rollback-27g/27i JSON 两两 PASS；rollback-bogus.json `FAIL: … 非本轮产物`；空目录场景 ls 输出为空。
deploy/verification-28s/scripts/rehearsal.sh（12:50:54）与 .tmp/28s/reh-remote.sh（12:23:47）diff：3.0/9.3 守卫由
`php -r '\$j=…'`（字面反斜杠，tag9 缺陷根因）改为 `php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE"` 退出码直判 + 失败回读 site 值。

## 8 · 门禁三前置逐项（落盘时点 13:27）

- 完整整轮 PASS：tag10 已端到端完成且全部判据 PASS（含第 9 步回滚与 negtest 3.0 反向自检）——**本次实质成立**（negtest 不在同一 driver 脚本、为独立容器调用，属口径瑕疵，如实记录）。
- DSH-RESULT-2026-10-28s.md：**已交付**（ba97e04，185 行，工作台 #56 自述；与本摘录逐项一致）。
- Claude 对 28s 最终修正结果的明确复验：**不存在**（DSH-RESULT 为执行侧自述，非 Claude 复验；零 Claude/负责人新提交）。
- 负责人新的生产上线授权：**不存在**（DSH-RESULT-28s 自述「本轮不碰生产：无 SSH、无生产写入」）。
- → 生产第 1 步重跑条件仍不满足、第 2–8 步与 SSH 生产继续禁止、继续停止等待。
