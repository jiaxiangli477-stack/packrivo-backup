# 独立只读证据摘录 · 28s 增量门禁核查（基线 7200cff / 日志 73bc3ba 之后）· 2026-10-28 本机 ~12:05

## A. 仓库状态（零新提交）
```
git fetch origin          # 无输出变更
git log --oneline origin/main -1
> 73bc3ba launch log: append section 20 incremental gate check after c168113 ...
git status -sb
> ## main...origin/main
>  M deploy/RUNBOOK-launch.md
git ls-files --others --exclude-standard | wc -l  → 0
```
无 dsh-out/DSH-RESULT-2026-10-28s.md、无 deploy/verification-28s/、docs 无 28s 之后的新任务单/答复。

## B. r28s-runner 实况（只读 docker ps / inspect）
```
docker ps -a | grep r28s → r28s-runner  Up (启动于 2026-10-28T18:56:32.75Z = 本机 11:56:32)
docker inspect r28s-runner
  img=r28q-host:7  (镜像 Created 2026-10-28T18:56:31.96Z = 本机 11:56:31，晚于 .tmp/28q/rehearsal.sh 最后编辑 11:56:24)
  cmd=[sh -c 清空并重建 /home/web/rehearsal-28s 后 bash /opt/x28r/run28s.sh > /home/web/rehearsal-28s/rehearsal.log 2>&1; echo EXIT=True >> 同日志]
  Mounts=[E:\破甲工作区\.tmp\28q\rehearsal\home\web → /home/web (RW), docker.sock]
```

## C. 本轮原始日志（主机侧只读，E:\破甲工作区\.tmp\28q\rehearsal\home\web\rehearsal-28s）
- reset.log（12:00:09 完成）：site→http://nginx；restore_slug=130 missing=0、restore_slug=8 missing=0；packrivo-static-bridge-1.2.38。
- 拆包装：grep -c mariadb → 0「包装已拆除」。
- s-reset.txt：845 行，sha256 = 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb → **PASS: 复位快照 = 基线**。
- 正向 step0 12:02 开始，step0.log（采样时 1,832 B）：php/mysql/nginx/redis 在位、core 7.1.3、PHP 8.3.36、siteurl http://nginx、子主题/报价插件「未安装」；0f 探针段随后执行（采样未到）。
- step-summary.txt 0 字节；s-forward / step9 回滚 / 反向自检产物均未出现 → 整轮进行中、未完成。

## D. 在途改写静态核对（只读）
- RUNBOOK（工作树 M，+27/−7）：2.5 逐条 PASS:/FAIL:、3.0 守卫×1、9.3 守卫×1、第 3 步总数 134/8+apply_updated>0、附录 C 28s 行；附录 D grep 零命中；apply_updated=134 写死零命中。
- .tmp/28q/rehearsal.sh（432 行，11:56:24）：ckp/ckf/ck+step_end(PASS=n FAIL=m→exit1)+selftest；step0 0f 探针（失败→装 --skip-ssl 包装→重探）；2a–2h；3.0 GUARD30；step3 总数=134/=8、>0、URL/H1=PASS；9.3 GUARD93。
- 仓库内 deploy/verification-28q/scripts/rehearsal.sh 无改动；两处在途改写均未提交。

## E. 插曲（不采信、不拼凑）
- 11:54:00–11:55:44 存在一次中断的半次 step1 备份目录 packrivo-launch-20261028-1854（INSTALL-STATE.txt 0 字节），随后 runner 清空目录干净重启。