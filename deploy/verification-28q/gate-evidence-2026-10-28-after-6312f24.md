# 独立只读证据摘录 · 28s 增量门禁核查（基线日志提交 6312f24 之后）· 2026-10-28 本机 ~12:05–12:12

## A. 仓库状态（零新提交）
```
git fetch origin                          # 无输出变更
git log --oneline 6312f24..origin/main    # 空
git rev-parse HEAD origin/main            # 两者同为 6312f246c237dac511fc5c1005306d27d12378e6
git status --short                        # 仅: M deploy/RUNBOOK-launch.md
git ls-files --others --exclude-standard  # 空
```
无 dsh-out/DSH-RESULT-2026-10-28s.md；无 deploy/verification-28s/（Test-Path False）。

## B. r28s-runner 被杀 + 重建重启（只读 docker ps / inspect）
```
docker ps -a --format "{{.Names}} {{.Status}}"
# 首次采样: r28s-runner   Exited (137) 36 seconds ago     （旧实例 ~12:07:30 被 SIGKILL）
# 再次采样: r28s-runner   Up 12 seconds / Up 3 minutes    （新实例）
docker inspect r28s-runner --format 'RestartCount={{.RestartCount}} StartedAt={{.State.StartedAt}}'
# RestartCount=0 StartedAt=2026-10-28T19:07:34.12763711Z
docker images r28q-host --format "{{.Tag}} {{.CreatedAt}}"
# 8 2026-10-28 12:07:33   7 2026-10-28 11:56:31   6 11:46:52   5 11:27:43 ...
docker inspect r28s-runner --format 'Image=... Cmd=...'
# Image=sha256:9428053c960d...（tag 8）
# Cmd=[sh -c <先删除并重建 /home/web/rehearsal-28s 目录>; mkdir -p ...; bash /opt/x28r/run28s.sh > /home/web/rehearsal-28s/rehearsal.log 2>&1; echo EXIT=$? >> /home/web/rehearsal-28s/rehearsal.log]
# Mounts=E:\破甲工作区\.tmp\28q\rehearsal\home\web -> /home/web (RW) + docker.sock
```

## C. 旧实例被杀前残留（主机侧只读）
- backups/packrivo-launch-20261028-1903/：db.sql 35,409,451B（12:03:45）；INSTALL-STATE.txt 205B（12:05:54）：
```
launch=packrivo-launch-20261028-1903
time=Wed Oct 28 19:05:24 UTC 2026
theme_before=packrivo-static-bridge-1.2.38
parent_before=packrivo-static-bridge-1.2.38
child_theme_present=no
quote_plugin_present=no
```
before-* 9 项（12:03:44–12:05:24）；CURRENT_LAUNCH=packrivo-launch-20261028-1903。
- packrivo-packages/merged/（12:06:12）23 个 JSON；conflicts.json=[]（3B）；paths.json count=146（ConvertFrom-Json 后 $p.Count=146，含 "/get-a-quote/"）。
- 并行链诊断脚本（被杀前写入，未执行）：.tmp/28s/inspect-merged.sh 12:07:02、check-pkg.sh 12:07:20。

## D. 新轮运行中进度（截至 ~12:11:30）
- rehearsal-28s/rehearsal.log 102B（12:07:34）：
```
################ 28s 开始 2026-10-28T19:07:34Z ################
===== [1] 复位（reset.sh）=====
```
- rehearsal-28s/reset.log 1,421B（12:10:44，自 417B/920B 持续递增）：
```
== 1) 站点地址指回演练站点 ==
http://nginx
== 2) mu 回到打补丁前 ==
== 3) 字典回到演练前（pristine-i18n）==
i18n 文件数: 23
== 4) 主题与插件回到首装前 ==
== 5) 清掉上一轮遗留的 rollback 依据，重灌干净的包 ==
== 6) 内容回到上线前（按 slug 写回依据里的旧值）==
restore_slug=130 missing=0 无正文依据 0
restore_slug=8 missing=0 无正文依据 8
== 7) 状态确认 ==
packrivo-static-bridge-1.2.38
（一条 WordPress wp_update_themes 联网告警，演练容器无外网，与判据无关）
子主题未安装 ✓
```
- 目录内仅 rehearsal.log+reset.log 两文件：无 s-reset.txt / step-summary.txt / s-forward / step9 / negtest 产物。

## E. 在途改写只读核对
- deploy/RUNBOOK-launch.md：`git diff` +28/−8（mtime 12:07:32）。命中：§0.1 探针注记 2 段（每轮重跑 + 判据只看补救后结果）；2.5 六条 PASS:/FAIL:+exit 1 与 json_last_error 判据注（conflicts.json=[] falsy 坑）；3.0 应用前守卫；9.3 回滚前守卫；step3 总数 134/8 + apply_updated>0 + URL/H1=PASS；附录 C 28s 行。附录 D 零命中；写死 apply_updated=134 零命中。
- .tmp/28q/rehearsal.sh：432 行、22,862B、mtime 12:07:13（晚于 tag7 的 11:56:24 版；tag8 镜像 12:07:33 重建）。命中：ckp/ckf/ck+step_end+selftest；step0 0f 探针（失败→装 --skip-ssl 包装→重探→ckp/ckf）；2a–2h；3.0 GUARD30；step3 27g/27i 总数 =134/=8、apply_updated>0、URL/H1=PASS、3c/3d/3e；9.3 GUARD93。
- 仓库 deploy/verification-28q/scripts/rehearsal.sh 无改动、未提交。

## F. 门禁结论
整轮被重启一次（旧实例 step2 后被杀、新轮 12:07:34 从复位重跑，运行中未完成）；无 DSH-RESULT-28s / verification-28s/；Claude 对 28s 最终轮复验不存在；新生产授权不存在（28s 任务单「本轮不碰生产」）→ 生产第 1 步重跑条件仍不满足、第 2–8 步与 SSH 生产继续禁止、继续停止等待。
