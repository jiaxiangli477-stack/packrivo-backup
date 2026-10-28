# 28q 第四遍演练 · 独立只读证据摘录（意图 676 核实，原样记录，未修改任何他人文件）

来源目录（全部只读）：`E:\破甲工作区\.tmp\28q\`、`E:\破甲工作区\.tmp\28q\rehearsal\home\web\rehearsal-28q\`、`E:\破甲工作区\packrivo-backup\deploy\verification-28q\`
观察时间（本地 PDT/UTC-7）：2026-10-28 10:41–10:58

## 1) runner 容器与结束事实
- `docker inspect r28q-runner`：`running→exited exit=0`；started 2026-10-28T17:23:42.31Z；finished 2026-10-28T17:54:55.689Z。
- `docker logs r28q-runner` 尾段：
```
[17:38:02] 4) 整体回滚 9
   rollback 行数 93
snapshot: /home/web/rehearsal-28q/s-after-rollback.txt
行数: 845
整份 sha256: 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
[17:43:21] 5) 再正向 1-8
   forward2 行数 411
snapshot: /home/web/rehearsal-28q/s-forward2.txt
行数: 847
整份 sha256: 4247693fa3a31f35477dcb059ad79e4ae5c13de861f94dfcc3848fe57f81b392
[17:54:55] 6) 对比
=== 回滚后 vs 基线（应无差异）===
IDENTICAL ✓
=== 第二次正向 vs 第一次正向（应无差异）===
IDENTICAL ✓
[17:54:55] DONE
```

## 2) reset 判据（reset.log 10:26:41）
```
== 6) 内容回到上线前（按 slug 写回依据里的旧值）==
restore_slug=130 missing=0 无正文依据=0
restore_slug=8 missing=0 无正文依据=8
== 7) 状态确认 ==
packrivo-static-bridge-1.2.38
子主题未安装 ✓
报价插件未安装 ✓
Custom Clear Display Box Sourcing
get-a-quote 301（期望 301）
首页 200（期望 200）
```

## 3) forward1（forward1.log 10:36:36）关键判据行
```
================= step3 开始 17:31:41 =================
input_pages=134 preflight=PASS
hub_pages=8 preflight=PASS
apply_updated=130 already_current=4 URL_and_body_check=PASS
apply_updated=7 already_current=1 H1_body_URL_check=PASS
================= step3 结束 exit=0 =================
（step0/1/2/4/5/6/7/8 均 "结束 exit=0"；step1 "STEP 1 通过判据"、step2 "STEP 2 通过判据"、step6 "STEP 6 通过判据"；
 step8：sitemap 未变、robots 未变、get-a-quote/?style=rigid&qty=500 含 pkq-root、inquiry 200）
```

## 4) 回滚（rollback.log 10:41:27）关键判据行
```
+ sha256sum -c /home/web/backups/packrivo-launch-20261028-1729/before-mu.sha256
... packrivo-i18n.php: OK
... packrivo-site-remediation-20261022.php: OK
Success: Switched to 'Packrivo Static Bridge' theme.
子主题已移除
Plugin 'packrivo-quote' deactivated. / Success: Deleted 1 of 1 plugins.
报价插件已移除
hub_pages=8 preflight=PASS  backup=reused rows=8  rollback_updated=7 already_current=1 H1_body_URL_check=PASS
input_pages=134 preflight=PASS  backup=reused rows=134  rollback_updated=130 already_current=4 URL_and_body_check=PASS
================= step9 结束 exit=0 =================
```

## 5) forward2（forward2.log 10:53:02）step3 真实写入复现
```
================= step3 开始 17:46:16 =================
input_pages=134 preflight=PASS
hub_pages=8 preflight=PASS
apply_updated=130 already_current=4 URL_and_body_check=PASS
apply_updated=7 already_current=1 H1_body_URL_check=PASS
================= step3 结束 exit=0 =================
（step1/2/4/5/6/7/8 均 exit=0）
```

## 6) 0.1 探针补跑（probe.log 10:56:50，演练结束后由并行链补跑）
```
== 0.1b 裸探针（预期：TLS/SSL error，导出 0 字节）==
mariadb-dump: Got error: 2026: "TLS/SSL error: SSL is required, but the server does not support it" when trying to connect
-rw-r--r-- 1 root root 0 Oct 28 17:56 /tmp/probe.sql
== 0.1c/0.1d 按 RUNBOOK 打包装（--skip-ssl 追加在参数末尾）后重探 ==
Success: Exported to '/tmp/probe.sql'.
-rw-r--r-- 1 root root 36381501 Oct 28 17:56 /tmp/probe.sql
40
```

## 7) 五件套 / 补丁 / 脚本现状（截至 10:58 本地）
- `deploy/verification-28q/rehearsal.log`：存在（10:57:00，69090 B，未跟踪）。
- `deploy/verification-28q/rehearsal-verify.json`：存在（10:57:00，6646 B，未跟踪；generatedAt 17:57:00.019Z；无整轮/三轮 PASS 结论字段）。
- 三轮整轮 PASS 结论：不存在。Claude 核验产物：不存在（全盘搜索）。新生产授权：不存在（`git ls-remote origin main` == 00709261143e1e1b9d680235b8a613798eb9d904；DSH-TASK-2026-10-28q.md mtime 8:48:45 未变；无 DSH-RESULT-2026-10-28q.md）。
- supplement2（本链拟制补丁）：仍未落地/未批准合并；工作树 RUNBOOK 在途改动不含 2.5 硬 FAIL / 3.0 守卫 / 附录 D。
- rehearsal.sh：mtime 9:29:37 未变（12502 B），仍无 2.5 硬 FAIL / 3.0 守卫 / step0 探针。

## 8) 边界自证
全程只读（Get-ChildItem/Get-Content/Select-String/git status/log/ls-remote、docker ps/logs/inspect）；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未 SSH 生产、未读任何密码/密钥/token。
