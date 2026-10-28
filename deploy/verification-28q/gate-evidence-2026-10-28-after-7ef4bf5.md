# 门禁独立证据摘录 · 7ef4bf5 之后（tag9 整轮终态 + tag10 重启起点，2026-10-28 12:23:53–12:52 本机只读采集）

> 采集者：门禁核查链（只读）。原日志位于主机 bind 挂载 `E:\破甲工作区\.tmp\28q\rehearsal\home\web\rehearsal-28s`（tag9 轮日志已于 12:51:17 被 tag10 容器启动命令「先强制递归删除该目录再重建」清空，本摘录为唯一留存）。未执行任何脚本、未启动/停止/接入容器、未修改他人文件、未读取任何凭据。

## 1. runner 生命周期（tag9 完整跑完、未被杀；随后 tag10 重启）

```
docker inspect r28s-runner:  StartedAt=2026-10-28T19:23:53Z (本地 12:23:53)  image=r28q-host:9
12:48:54 (本地) runner Exited；rehearsal.log 尾部 "################ 28s 结束 2026-10-28T19:48:54Z ################" + "EXIT=0"
12:50:54 .tmp/28q/rehearsal.sh 修改（修复 3.0/9.3 守卫 php -r 转义；442→452 行）
12:51:16 (本地) 新 runner 启动：image=r28q-host:10；12:51:17 tag9 的 rehearsal-28s 被清空重建（reset.log 37B、rehearsal.log 102B）
```

## 2. tag9 step-summary.txt 原文（整轮逐步判据；唯一留存）

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
step9 :: 本步判据：PASS=0 FAIL=2 :: EXIT=1
```

## 3. step9 回滚失败实况（9.3 守卫 php -r 转义缺陷拦截了本轮合法回滚）

step9.log 尾部原文：

```
############### STEP 9.3 回滚前守卫 ###############
FAIL: 9.3 /pkg/rollback/rollback-27g.json site=
Parse error: syntax error, unexpected token "\", expecting end of file in Command line code on line 1 与本轮 http://nginx 不一致
FAIL: 9.3 /pkg/rollback/rollback-27i.json site=
Parse error: syntax error, unexpected token "\", expecting end of file in Command line code on line 1 与本轮 http://nginx 不一致
9.3 守卫拦截，rollback 不执行
本步判据：PASS=0 FAIL=2
```

根因：12:23:47 版 rehearsal.sh 两处守卫用 `php -r '\$j=json_decode(file_get_contents(\$argv[1]),true); echo \$j["site"] ?? "";'`（单引号内字面反斜杠传入 PHP → parse error → $S 恒空 → 判 FAIL）。9.3 守卫拦截后：27g/27i 的 import.php rollback、i18n 目录回拷（`cp -a "$BACKUP/i18n/."`）与 9a–9f 判据**全部未执行**。12:50:54 已修复为 `php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);'`（3.0 与 9.3 同改）。

## 4. 快照链实况

```
PASS: 复位快照 = 基线          （s-reset.txt 845 行 sha256=4972cd81…=基线）
PASS: 正向快照 = 4247693f…     （s-forward.txt）
FAIL: 回滚后快照 != 基线（实际 0f5ab240b676aa671f1035c441cb53412e7fb76c5eaccbfc1fedbdf51dc2813c）
```

s-reset.txt vs s-after-rollback.txt 差异（各 845 行、内容不同）：23 个 i18n 文件（11 dict+11 js+paths.json）哈希全部不同；多个产品页标题行不同（如 `14,product-pillow-jewelry-box,"Custom Pillow Jewelry Boxes"` 等）→ DB 内容层与 i18n 均未回到基线，回滚不完整。

## 5. 正向关键判据原文（唯一留存）

reset.log：`restore_slug=130 missing=0`、`restore_slug=8 missing=0`、`get-a-quote 301（期望 301）首页 200（期望 200）`（旧 SIGKILL 残留闭环持续成立）。

step0.log（0f 探针第三次真实走完补救路径）：
```
mariadb-dump: Got error: 2026: "TLS/SSL error: SSL is required, but the server does not support it" when trying to connect
探针字节数 = 0 → 装 /usr/local/bin/mariadb-dump、mariadb --skip-ssl 包装 → 重探
PASS: 0f 装包装后导出探针 > 0 字节（37403413）
```

step3.log（3a 修复后与真实输出一致，PASS=11 FAIL=0）：
```
27g apply: apply_updated=130 already_current=4 URL_and_body_check=PASS
27g verify: URL_and_non_H1_body_unchanged=134/134
27i apply: apply_updated=7 already_current=1 H1_body_URL_check=PASS
27i verify: verified_hubs=8 metadata_mismatches=0 H1_body_URL_unchanged=8/8
PASS: 3a 27g verify URL_and_non_H1_body_unchanged=134/134
PASS: 3·27g 总数 = 134（130+4=134）；PASS: 3·27i 总数 = 8（7+1=8）
PASS: 3·27g apply_updated > 0（演练要求真实写入）；PASS: 3·27i apply_updated > 0（演练要求真实写入）
本步判据：PASS=11 FAIL=0
```

step2.log：2a–2h 逐条 `PASS:`（2.5 逐条机制第三次实况生效）；zip 哈希不变（5974ae0c… / 096d0c8e…，与 28s 任务单一致）。

## 6. negtest 与驱动结构

- .tmp/28s/negtest.sh（3.0 反向自检脚本）存在，但 host run28s.sh 驱动未引用 negtest；tag9 轮 rehearsal-28s 内无任何 negtest 产物 → **28s 要求的反向自检未执行**。
- 驱动缺陷佐证：runner 命令以 `echo EXIT=$? >> rehearsal.log` 收尾，run28s.sh 不聚合各步退出码 → step9 EXIT=1 时整轮仍显示 `EXIT=0`（再次印证「退出码 0 不能作为整轮干净的证据」）。

## 7. 增量与授权核查（7ef4bf5 之后，12:52 采样）

- `git ls-remote origin main` = 7ef4bf5 = HEAD（零新提交）；无新任务单答复。
- `dsh-out/DSH-RESULT-2026-10-28s.md` 不存在；`deploy/verification-28s/` 不存在。
- 工作树仅 ` M deploy/RUNBOOK-launch.md`（并行链在途，12:50:59 再改，零触碰）；仓库无未跟踪文件。
- Claude 对 28s 最终修正结果的明确复验：不存在。负责人新的生产上线授权：不存在（28s 任务单仍为「本轮不碰生产」口径）。
