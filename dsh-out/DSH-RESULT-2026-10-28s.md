# 28s 结果：并入 2.5 / 3.0 / 9.3 三个守卫 + 用硬判据重跑一整轮「正向 + 回滚」

工作台 #56 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 已完成（本轮单线程，#56 保持 paused，未派工）

本轮不碰生产：无 SSH、无生产写入、未读未打印任何密码/密钥/token，`.cf.env` 未打开。

## 1 结论

1. **三个守卫已按内容手工移植进 RUNBOOK**（2.5 第 2 步合并后、3.0 第 3 步 apply 前、9.3 第 9 步 rollback-check 前），**附录 D 没有并入**；`rehearsal.sh` 与 RUNBOOK 逐条对应，每条判据走 `ck`，每步 `step_end || exit 1`。
2. **第 3 步判据改成按总数算**：`apply_updated + already_current = 134`（27g）与 `= 8`（27i），另加演练环境专用 `apply_updated > 0`、`URL_and_body_check=PASS`、`H1_body_URL_check=PASS`。RUNBOOK 正文里已经没有写死的 `apply_updated=134`。
3. **正向 0–8 每一步 `FAIL=0`、`EXIT=0`**（合计 57 条判据），**第 9 步 `PASS=8 FAIL=0 EXIT=0`**。
4. **快照链条对上**：复位 `4972cd81…` → 正向 `4247693f…` → 回滚 `4972cd81…` → 反向自检后仍 `4972cd81…`。
5. **反向自检成功**：往 `/pkg/rollback/` 放一个 `site=http://wrong-site.invalid` 的 JSON，3.0 守卫打 `FAIL`、整步 `EXIT=1`、`apply` 没执行；删掉后快照仍是基线。
6. 这一轮**前后重跑了 5 次**。前 4 次各停在一个判据上，原因全是判据/命令写错（不是环境问题），每次都按规矩**从复位重跑整轮**，没有「只补出错那一步再拼成全部 PASS」。逐次记录见 §3。

## 2 三个守卫怎么落地的

### 2.1 第 2 步：2.5 校验改成逐条 PASS/FAIL

```bash
# 2.5 校验（容器内跑，路径都在容器侧）。每条都打印 PASS:/FAIL:，任一条 FAIL → 本步 FAIL
docker exec php sh -c 'test -f /pkg/merged/paths.json && echo "PASS: paths.json 存在" || { echo "FAIL: paths.json 缺失（常见原因：当天 i18n 快照里 *-js.json 为空或坏 JSON，合并工具没产出 js 与路径表）"; exit 1; }'
docker exec php sh -c 'grep -q "\"/get-a-quote/\"" /pkg/merged/paths.json && echo "PASS: paths 含 /get-a-quote/" || { echo "FAIL: paths.json 缺 /get-a-quote/"; exit 1; }'
docker exec php sh -c 'php -r "$p=…count>=146…" && echo "PASS: paths 条数 >= 146" || { echo "FAIL: paths 条数 < 146"; exit 1; }'
docker exec php sh -c '[ "$(ls /pkg/merged/*-dict.json 2>/dev/null | wc -l)" = "11" ] && echo "PASS: dict = 11" || { echo "FAIL: dict 文件数 != 11"; exit 1; }'
docker exec php sh -c '[ "$(ls /pkg/merged/*-js.json   2>/dev/null | wc -l)" = "11" ] && echo "PASS: js = 11"   || { echo "FAIL: js 文件数 != 11";   exit 1; }'
docker exec php sh -c 'for f in /pkg/merged/*.json; do php -r "…json_last_error()…" "$f" || echo "坏 JSON: $f"; done' > /tmp/badjson.txt
if [ -s /tmp/badjson.txt ]; then echo "FAIL: 有坏 JSON"; cat /tmp/badjson.txt; else echo "PASS: 无坏 JSON"; fi
rm -f /tmp/badjson.txt
```

第 2 步判据里补了一条硬规则：**2.5 的每一条都要打印 `PASS:`，任一条 FAIL → 本步 FAIL，按回滚恢复字典并停止**，不得把 exit=0 的静默失败当通过。

### 2.2 第 3 步：3.0 应用前守卫 + 判据按总数

```bash
# 3.0 应用前守卫：/pkg/rollback 里若已有 JSON，它的 site 必须等于本次的 $SITE
for f in $(docker exec php sh -c 'ls /pkg/rollback/*.json 2>/dev/null'); do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一致" \
    || { echo "FAIL: $f 非本轮产物（site 与 $SITE 不一致），先清空 /pkg/rollback 再重跑第 3 步"; exit 1; }
done
```

判据：

- `apply_updated + already_current = 134`（27g）、`= 8`（27i）——**不要求 `apply_updated` 单独等于 134**（28q 实测就是 130+4、7+1）；
- 演练环境：`apply_updated > 0`，等于 0 说明演练库内容没复位、导入从未真实写入，该轮作废；
- 27g `URL_and_body_check=PASS`、27i `H1_body_URL_check=PASS`；
- 出现 `Backup input/site mismatch` / `Original backup site/input mismatch` / `Expected-site guard failed` → 本步 FAIL，按回滚处理并停止。

### 2.3 第 9 步：9.3 回滚前守卫（只加一次）

```bash
for f in /pkg/rollback/rollback-27g.json /pkg/rollback/rollback-27i.json; do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一致" \
    || { echo "FAIL: $f site 与 $SITE 不一致"; exit 1; }
done
```

### 2.4 D.2.1 的意思保留在 0.1 节

0.1 节加了一段：**导出探针每轮都要重跑**，包装装在容器 `/usr/local/bin`、容器重建就丢，不能依赖上一轮留下的包装；**判据只看补救后的结果**——包装本来不在时第一次探针失败是预期现象、不算本步 FAIL，装上包装重探到 `> 0` 才算通过，装了仍然 0 字节/报 TLS 错误才是真 FAIL。

### 2.5 附录 D 没有并入

`grep '附录 D|prep-restore\.php|apply_updated=134' deploy/RUNBOOK-launch.md` 无输出。附录 C 加了一行 28s 的结果。

## 3 跑这一轮之前改过哪些判据、为什么

规矩是「判据写错就改判据、从复位重跑整轮」。实际重跑 5 次，前 4 次都停住查因：

| 第几次 | 停在哪 | FAIL 原文 | 判定 | 改了什么 |
|---|---|---|---|---|
| 1 | step0 | `FAIL: 0f 导出探针失败/0 字节（0）→ 走 0.1 补救块装包装` | **判据写错**：本轮故意先拆包装，「包装不在 → 探针失败」是预期路径，不是失败 | 0f 改成「观察行」+ **单一判据**（补救后 > 0 字节）；RUNBOOK 0.1 同步写明「判据只看补救后的结果」 |
| 2 | step2 | `FAIL: 2f 无坏 JSON` / `坏 JSON: /pkg/merged/conflicts.json` | **判据写错**：`json_decode(...) or exit(1)` 把合法的空数组 `[]`（0 冲突时合并工具就写 `[]`）当成假，误判成坏 JSON | 改用 `json_last_error()===JSON_ERROR_NONE` 判断；RUNBOOK 2.5 同步改并在判据里注明 `[]` 合法 |
| 3 | step3 | `FAIL: 3a 27g verify（URL_and_non_H1_body_unchanged=134/134）` | **判据写错**：拿 27i 的 `metadata_mismatches=0` 去判 27g，而 27g 的 verify 根本不打这个字段，它打的是 `URL_and_non_H1_body_unchanged=134/134` | 3a 改判 `URL_and_non_H1_body_unchanged=134/134`；同时把 step8 两条 `sh -c "... <(...)"` 改成「先落临时文件再 `diff -q`」（`sh` 是 busybox ash，不支持进程替换）；RUNBOOK verify 判据同步改 |
| 4 | step9 | `FAIL: 9.3 …/rollback-27g.json site=` + `Parse error: syntax error, unexpected token "\"` | **命令写错**：守卫里 `php -r '\$j=…'` 在单引号内反斜杠不会被吃掉，PHP 收到的是 `\$j`，直接语法错 | rehearsal.sh 与 RUNBOOK **两处守卫都改成**「PHP 代码整段单引号、文件路径与 `$SITE` 用位置参数传」；改完先用「合法 JSON / 他站 JSON / 空目录」三种情况静态验证命令，再重跑整轮 |
| 5 | — | 无 | — | 本轮全 PASS |

第 4 次那轮**正向 0–8 已经全 PASS、正向快照已经是 `4247693f…`**，仍然整轮作废重跑——只补第 9 步会变成「拼出来的全部 PASS」，任务单明令不允许。

## 4 本轮（第 5 次）逐步结果

| 步 | 本步判据 | 退出码 | 内容要点 |
|---|---|---|---|
| step0 | **PASS=6 FAIL=0** | 0 | 探针先失败（`TLS/SSL error`、0 字节）→ 装包装 → 重探 `39,397,375` 字节 |
| step1 | **PASS=10 FAIL=0** | 0 | 备份：db.sql 非空 + 含 `CREATE TABLE`、mu 两文件、i18n、sitemap、INSTALL-STATE |
| step2 | **PASS=8 FAIL=0** | 0 | 合并：paths.json 存在/含 `/get-a-quote/`/≥146、dict=11、js=11、无坏 JSON、冲突 0、`/de/` 正常 |
| step3 | **PASS=11 FAIL=0** | 0 | 3.0 守卫（本轮 `/pkg/rollback` 为空，不迭代）→ plan → apply：27g `130+4=134`、27i `7+1=8`，两处 `apply_updated>0`、`URL_and_body_check=PASS`、`H1_body_URL_check=PASS` |
| step4 | **PASS=3 FAIL=0** | 0 | 报价插件已激活、目录存在、首页 200 |
| step5 | **PASS=4 FAIL=0** | 0 | 子主题 stylesheet/template、目录存在、首页 200 |
| step6 | **PASS=5 FAIL=0** | 0 | `/get-a-quote/` 200、`/de/get-a-quote/` 200、ar 文章 viewport、两个 mu `php -l` |
| step7 | **PASS=1 FAIL=0** | 0 | `/de/` 200（Cloudflare 项在本地跳过，见既有说明） |
| step8 | **PASS=9 FAIL=0** | 0 | 5 个 URL 200、sitemap 未变、robots 未变、报价页 `pkq-root`、`/inquiry/` 200 |
| step9 | **PASS=8 FAIL=0** | 0 | 9.3 守卫（两个 JSON site 一致）→ 清 transient 8 条 → mu 回滚（`sha256sum -c` 两项 OK）→ 主题/插件 → 内容 rollback-check/rollback |
| 合计 | **65 PASS / 0 FAIL** | 全 0 | |

## 5 快照

| 快照 | 大小 | 行数 | sha256 | 判 |
|---|---|---|---|---|
| `s-reset.txt`（复位、拆掉包装后） | 69,238 B | 845 | `4972cd81…` | **= 基线 PASS** |
| `s-forward.txt`（正向 0–8 之后） | 67,774 B | 847 | `4247693f…` | **= 28q 正向 PASS**（一行不差） |
| `s-after-rollback.txt`（第 9 步之后） | 69,238 B | 845 | `4972cd81…` | **= 基线 PASS** |
| `s-after-negtest.txt`（反向自检之后） | 69,238 B | 845 | `4972cd81…` | **= 基线 PASS** |

三份 diff：

- `reset-vs-forward.diff` 434 行 / 31,880 B：正向带来的差异（子主题激活、报价插件装、mu 换成 27e、字典换成合并版、800 多行标题变化）；
- `forward-vs-rollback.diff` 434 行 / 31,889 B：回滚把上面这些改回去；
- `rollback-vs-negtest.diff` **0 行**：反向自检没有改动站点。

正向与 28q 的正向快照 sha256 完全相同（`4247693f…`），说明新加的守卫和判据**没有改变任何站点状态**——只读检查，不写。

## 6 反向自检（3.0 守卫）

```
一开始 /pkg/rollback: rollback-27g.json、rollback-27i.json（本轮第 3 步生成）
放入 rollback-bogus.json：{"site":"http://wrong-site.invalid"}
单独跑第 3 步：
  PASS: 3.0 /pkg/rollback/rollback-27g.json site 与本轮一致
  PASS: 3.0 /pkg/rollback/rollback-27i.json site 与本轮一致
  FAIL: 3.0 /pkg/rollback/rollback-bogus.json 非本轮产物（site=http://wrong-site.invalid，期望 http://nginx），先清空 /pkg/rollback
  3.0 守卫拦截，apply 不执行
  本步判据：PASS=2 FAIL=1
negtest step3 EXIT=1
apply 是否执行过：apply_updated 出现 0 次
删掉 bogus → 快照仍 4972cd81…
```

另外单独验证过守卫命令本身的三种输入：合法 JSON → PASS；他站 JSON → FAIL；`/pkg/rollback` 为空 → 不迭代（`ls` 无输出）。这三条在 `guard-selftest.log` 里。

## 7 改动摘要

```
 deploy/RUNBOOK-launch.md                     |  46 ++++++++---
 deploy/verification-28q/scripts/rehearsal.sh | 115 ++++++++++++++++++++++++---
 2 files changed, 141 insertions(+), 20 deletions(-)
```

- **RUNBOOK 650 → 677 行**：0.1 加「每轮重跑探针 + 判据只看补救后」；2.5 逐条 PASS/FAIL + 判据补 FAIL 规则；3.0 守卫；第 3 步判据改总数；9.3 守卫；附录 C 加 28s 一行。**附录 D 未并入**，正文无 `apply_updated=134`。
- **rehearsal.sh 359 → 446 行**：step0 加导出探针 + 补救块；step2 的 2a–2h 全部换成 2.5 的六条 + 冲突 + `/de/`；step3 加 3.0 守卫、抓 apply 输出、判总数与 `apply_updated>0`；step8 进程替换改临时文件；step9 加 9.3 守卫。

## 8 验收对照（任务单 §3）

| 要求 | 结果 |
|---|---|
| 正向 0–8 每步 `FAIL=0`、`EXIT=0`，第 9 步同样 | **PASS**（表见 §4，合计 65/0） |
| 第 0 步日志能看到「没装包装 → 探针 0 字节/TLS 错误 → 装上后 > 0」 | **PASS**：`mariadb-dump: Got error: 2026: "TLS/SSL error…"` → `探针字节数 = 0` → 装包装 → `PASS: 0f 装包装后导出探针 > 0 字节（39397375）` |
| 快照：复位 `4972cd81…` → 正向 `4247693f…` → 回滚 `4972cd81…` | **PASS**（§5） |
| 反向自检：3.0 守卫拦截成功 | **PASS**（§6） |
| RUNBOOK 无写死 `apply_updated=134`、无附录 D、附录 C 有 28s 行 | **PASS** |
| 两个 zip 哈希不变 | **PASS**：`packrivo-home-v4.zip` = `5974ae0c…`、`packrivo-quote.zip` = `096d0c8e…` |

## 9 结束状态

- 演练环境停在**回滚之后**，也就是基线状态：`s-after-rollback.txt` = `s-after-negtest.txt` = `4972cd81…`（845 行）。
- 站点状态：父主题 `packrivo-static-bridge-1.2.38` active、子主题目录不存在、报价插件已删、`/get-a-quote/` 301 → `/inquiry/`、mu 两个文件回到上线前版本。
- 生产未动。

## 10 交付物

| 路径 | 内容 |
|---|---|
| `deploy/verification-28s/rehearsal.log` | 整轮全部命令与原始输出（44 KB） |
| `deploy/verification-28s/step0.log … step9.log` | 每步单独日志（含本步 `PASS:`/`FAIL:` 行与 `本步判据：PASS=n FAIL=m`） |
| `deploy/verification-28s/step-summary.txt` | 逐步 `PASS/FAIL/EXIT` 汇总 |
| `deploy/verification-28s/reset.log` | 复位过程 |
| `deploy/verification-28s/s-reset.txt` / `s-forward.txt` / `s-after-rollback.txt` / `s-after-negtest.txt` | 四份快照 |
| `deploy/verification-28s/reset-vs-forward.diff` / `forward-vs-rollback.diff` / `rollback-vs-negtest.diff` | 快照两两 diff |
| `deploy/verification-28s/negtest.log` / `negtest-step3.log` | 反向自检全流程 + 那次 step3 的原始输出 |
| `deploy/verification-28s/guard-selftest.log` | 守卫命令三种输入的静态验证 |
| `deploy/verification-28s/scripts/` | `run28s.sh`（驱动）、`negtest.sh`、`guard-selftest.sh`、`rehearsal.sh`（本轮版本）、`reset.sh` |

## 11 复现

```bash
# 整轮（复位 → 拆包装 → 快照 → 正向 0–8 → 快照 → 回滚 9 → 快照）
docker exec r28q-host bash /opt/x28r/run28s.sh

# 反向自检（放他站 JSON → 单独跑第 3 步 → 删掉 → 再取快照）
docker exec r28q-host bash /opt/x28r/negtest.sh
```
