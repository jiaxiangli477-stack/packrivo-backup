# DSH-RESULT · 2026-10-29v（照单执行 29v）

> 任务单：`packrivo-v2/docs/DSH-TASK-2026-10-29v.md`
> 本轮约束：不碰生产、不 SSH、不碰密码、不打开 `.cf.env`；单线程；#56 与 Task 55 保持 paused。全程只在本机演练栈上动手。
> 结论：**RUNBOOK 的两处临时偏离与三处判据修正已写进正文；演练环境换成 MySQL 8.4 后完整重跑一轮，所有判据 PASS，三条回滚路径都把站点带回新基线（逐字节一致）。**

新 RUNBOOK：**提交 `050a9a4`**（正文补丁在 `dea6898`），`deploy/RUNBOOK-launch.md` 的 **git blob = `288a5f75dec825d83084504496db71865543ed4c`**。
两个发布包哈希**未变**：`packrivo-home-v4.zip` `5974ae0c…34bf9`（790,164 B）、`packrivo-quote.zip` `096d0c8e…52d93`（54,581 B）。

---

## 1 · 任务单逐项对照

| # | 要求 | 落在哪 | 证据 |
|---|---|---|---|
| 1 | 头部注明「首装已完成，本手册是首装手册」 | RUNBOOK **L8–L10**（新 blockquote） | 写明 `CURRENT_LAUNCH=packrivo-launch-20261029-0335`、`/get-a-quote/` 页 ID 3383、两个 mu 打后哈希；并写明「以后换主题/插件不要重跑本手册」 |
| 2 | §0.1 注明生产库是 MySQL（实测版本）+ `caching_sha2_password` | **L54–L56** | 「MySQL Community Server **26.7.0** + `caching_sha2_password`；客户端 `mariadb 11.4.13`，插件目录初始为空」 |
| 3 | 补救分两层（先 `--skip-ssl` 包装解 TLS，再 `apk add --no-cache mariadb-connector-c` 解 1045） | **L102–L150**（`### 0.1 补救块（两层）`） | 第 1 层给了两种 TLS 报错原文 + 包装块；第 2 层给了 `1045 … caching_sha2_password.so` 原文 + `apk add` + `ls /usr/lib/mariadb/plugin` |
| 4 | 探针判据改三条（字节>0；`CREATE TABLE`=`SHOW FULL TABLES` 的 BASE TABLE 且去表头；末尾 `-- Dump completed`） | **L88–L100** | 三条判据写成表格（0k-1/0k-2/0k-3），并注明 `wp db query … --skip-column-names` 返回空、`SHOW TABLES \| wc -l` 多一行表头的坑 |
| 5 | 「8 个文件」改 48 | **L92** 附近（0.1 判据下的注） | 明确 48 是全深度口径，`-maxdepth 1` 会数成 8 |
| 6 | 新增 §4.5 建报价页（守卫 → `post create … --porcelain` → ID 写进 `INSTALL-STATE.txt` → 三条判据） | **L416–L500** | 守卫查库 + `post list --post_status=any,draft,…` 都要 0；建页命令与 4.5a–4.5d 四条判据；回滚 `post delete <ID> --force` |
| 7 | 第 9 步开头加删报价页（顺序在 9.6 之前） | **L643–L651**（`# 9.8 报价页：…`） | 从 `INSTALL-STATE.txt` 取 ID 后 `post delete --force`，再判「任何状态计数 0」 |
| 8 | 附录 B 时间表同步 | 附录 B | 在 4 与 5 之间插入「4.5 建报价页 2 min 不可省」；第 9 行改成「先 9.8 删报价页，再 9.7 → 9.2」 |
| 9 | §0.2 上传落点写清 | **L160–L168** | `scp` 目标改成 `netcup:/home/web/packrivo-packages/dsh-out/`，并加「落点清单」：6 个 JSON 不在包根，落错第 3 步会 `Cannot read /pkg/dsh-out/batch-01.json` |
| 10 | 正文写进 0k-2 / 3g / 6b 三处判据修正 | **L94–L96**（0k-2）、**L358**（3g）、**L550**（6b） | 0k-2：`SHOW FULL TABLES` 数 BASE TABLE 去掉表头；3g：`H1_body_URL_unchanged=8/8` 在 **verify** 输出里，不在 apply；6b：要拿**打补丁之后**的哈希去比「已变」 |
| 11 | 附录 C 加一行 10-29 生产实跑 + 两次偏离 | **L788 起 `### 附录 C-1`** | 两次偏离（connector-c / 建报价页 3383）逐条写现象→原因→正文位置；另附实测数字（`db.sql` 32,516,235 B、`CREATE TABLE` 40 = BASE TABLE 40、48 个 core 文件） |
| 12 | 演练环境：mysql 换 MySQL 官方镜像 + `caching_sha2_password` + 自签 TLS | `deploy/verification-29v/env/README-ENV.md`、`STEPS-29v.md` | 实测 `mysql:8.4` → **8.4.13**，`root@%` 插件 `caching_sha2_password`，自签 `CN=MySQL_Server_8.4.13_Auto_Generated_Server_Certificate` |
| 13 | reset.sh 删掉演练库 get-a-quote 页 + 拆掉 connector | `deploy/verification-29v/scripts/reset.sh` §5/§6 | 实测：`ID,post_status / 3220,publish → Success: Deleted post 3220`；`apk del mariadb-connector-c` → 插件目录 **0** 个文件、`/usr/local/bin` 里 0 个包装 |
| 14 | 完整重跑（复位 → 正向 0–8 含 4.5 → 回滚 9 含删页 → 3.0 反向自检） | `deploy/verification-29v/logs/` | 见下面 §2 |
| 15 | 第 0 步日志依次出现 TLS 报错 → 装包装 → 1045 报错 → 装 connector → 三条判据 PASS | `logs/fwd-step0.log`、`logs/fwd2-step0.log` | 两条报错原文与生产逐字节相同，见 §3 |
| 16 | 换 MySQL 后取复位快照作新基线；正向快照与 `4247693f…` 做 diff 并解释 | `snapshots/s-reset.txt`、`s-forward.txt`、`logs/forward-vs-28s.diff` | 新基线 `8971f447…`；diff 只有一行（报价页 ID 3220 → 5444），见 §4 |
| 17 | 在 MySQL 上补跑第 9 步「最后手段」整库导入（28r 方式：canary → 导入后消失 → 文件部分回滚 → 快照回基线） | `logs/lastresort.log` | 20 PASS / 0 FAIL，见 §5 |
| 18 | 产出放 `deploy/verification-29v/` | `deploy/verification-29v/` | `README.md`（结果+哈希）、`STEPS-29v.md`（切换与重跑口径）、`env/`、`scripts/`、`logs/`、`snapshots/` |
| 19 | 结果文档写明 RUNBOOK 提交号与 blob、两个 zip 哈希、贴 `git log -3 --stat` | 本文件 | 见文首与 §7 |

---

## 2 · 完整重跑（一轮跑完四段，`logs/run29v.log` 结尾 `EXIT=0`）

时间都是本机时间（UTC-7）。整轮 **07:03:33 → 08:07:35**。

| 阶段 | 内容 | 结果 |
|---|---|---|
| [1] 复位 | `reset.sh`：siteurl 回 `http://nginx`、mu/i18n 回 pristine、子主题与插件删除、**删 get-a-quote 页**、**拆 connector 与包装**、重灌 `/pkg`、134+8 篇内容按 slug 回旧值 | 退出码 0；`restore_slug=130/…` |
| [2] 基线快照 | `snapshot.sh` → `s-reset.txt`（844 行） | sha256 **`8971f447510523ed468eedfaa9ceec1ac1d4762465bfd0fb006ae58dad3dd2b4`** |
| [3] 正向 0–8（含 4.5） | 10 步 | **73 PASS / 0 FAIL**，每步 `EXIT=0` |
| [4] 最后手段（整库导入） | canary → 无包装导入失败 → 装包装 → 导入成功 → 文件部分 9.6/9.5/9.4/9.2 | **20 PASS / 0 FAIL**；最终快照 = 基线 |
| [5] 再复位 | `reset.sh` + 快照 `s-reset2.txt` | 与基线**逐字节一致** |
| [6] 正向 0–8（第二轮） | 10 步 | **73 PASS / 0 FAIL**（与第一轮逐项相同） |
| [7] 第 9 步回滚（含 9.8） | 9.8 删页 → 9.7 缓存 → 9.6 mu → 9.5 主题 → 9.4 插件 → 9.3 内容 → 9.2 字典 | **PASS=9 FAIL=0**；回滚后快照 = 基线 `8971f447…` |
| [8] 3.0 反向自检 | 放 `site=http://wrong-site.invalid` 的 JSON 单跑 step3 | step3 `EXIT=1`、打印 `FAIL: 3.0 …rollback-bogus.json 非本轮产物`、`apply_updated` 计数 **0**；删文件后快照仍是基线 |

逐步明细（两轮一致）：step0 `10/0`、step1 `10/0`、step2 `8/0`、step3 `11/0`、step4 `3/0`、**step45 `5/0`**、step5 `4/0`、step6 `12/0`、step7 `1/0`、step8 `9/0`。
（`deploy/verification-29v/step-summary-fwd.txt`、`step-summary-fwd2.txt`）

---

## 3 · 第 0 步：两层补救真的被走到了（这是本轮环境改造的目的）

`logs/fwd-step0.log`（第二轮 `fwd2-step0.log` 同样一遍）：

```
第 1 次裸探（预期 TLS/SSL error）
  Warning: Failed to get current character set of the posts table. Reason:
    ERROR 2026 (HY000): TLS/SSL error: self-signed certificate in certificate chain
  mariadb-dump: Got error: 2026: "TLS/SSL error: self-signed certificate in certificate chain" when trying to connect
  第 1 次探针字节数 = 0
  PASS: 0k-L1 裸探报 TLS/SSL error
装 --skip-ssl 包装 → 第 2 次
  mariadb-dump: Got error: 1045: "Plugin caching_sha2_password could not be loaded:
    Error loading shared library /usr/lib/mariadb/plugin/caching_sha2_password.so: No such file or directory" when trying to connect
  第 2 次探针字节数 = 0
  PASS: 0k-L2 装包装后报 1045 caching_sha2 插件缺失
apk add --no-cache mariadb-connector-c
  (1/1) Installing mariadb-connector-c (3.4.6-r0)  → 插件目录出现 caching_sha2_password.so
最终探针：字节=45379954  CREATE TABLE=40  BASE TABLE=40  DumpCompleted=1
PASS: 0k-1 / 0k-2（40 vs 40）/ 0k-3
```

两条报错与生产 2026-10-29 的原文**逐字节相同**（`LAUNCH-LOG-2026-10-28.md` §37 记录的就是这两条）。
之所以能复现，是因为 `reset.sh` 每轮都把 connector 与 `/usr/local/bin` 包装拆掉，且 php 容器的 `mariadb-client` 钉在 **11.4.13**（= 生产版本；Alpine 3.24 自带的 11.8.9 会先报 1045、复现不出 TLS 那一层——见 `env/README-ENV.md` §2.1）。

---

## 4 · 新基线 与 与旧正向快照的差异

| 快照 | sha256 | 行数 |
|---|---|---|
| 新基线 `snapshots/s-reset.txt`（MySQL） | `8971f447510523ed468eedfaa9ceec1ac1d4762465bfd0fb006ae58dad3dd2b4` | 844 |
| 正向 `snapshots/s-forward.txt` | `eef3fd37a8cbe2a4079c89fb6836d2518a06c6cf2ee4bd8d0f95ac75d729a33a` | 847 |
| 28s 时代基线 / 正向（MariaDB，仅对照） | `4972cd81…` / `4247693f…` | 844 / 847 |

`logs/forward-vs-28s.diff`（与 28s 正向 `4247693f…` 的全部差异）：

```
@@ -598,7 +598,6 @@
-3220,get-a-quote,"Get a Quote"
@@ -666,6 +665,7 @@
+5444,get-a-quote,"Get a Quote"
```

**解释**：只差报价页的 ID。28s 时代演练库自带这一页（ID 3220），本轮 `reset.sh` 按生产首装前状态把它删了，第 4.5 步用 `post create` 重建 → ID 5444。页面标题、插件清单、主题、`siteurl`/`home` 全部逐行一致，说明 MySQL 化的演练库与 MariaDB 时代产生的站点状态完全相同。
（`fwd2` 那轮 4.5 建出来的也是 5444：中间做过整库导入，`wp_posts` 的 AUTO_INCREMENT 跟着回到备份值，所以第二次插回同一个 ID。这不是判据问题，反而印证「整库导入会把自增也一起回退」。）

---

## 5 · 最后手段（整库导入）在 MySQL 上：20 PASS / 0 FAIL

`logs/lastresort.log`：

- 起点快照 = 本轮正向 `eef3fd37…`；
- `db.sql` **47,374,862 B**、`CREATE TABLE` 40 条；
- **包装不在时** `db import` `rc=1`，导入没有发生（表数仍 40、"库没被导坏"）；
- 装上包装后探针 **47,230,679 B**，`db import` `rc=0`；
- L-1 表数 40 = `CREATE TABLE` 40（只用 `table_type='BASE TABLE'` 计数）；
- L-2 canary（ID 5445）消失；**L-2b** 报价页 5444 也随导入消失（整库导入顺带把 4.5 的页删了）；
- L-3 `stylesheet`/`template` 都回父主题；L-4 `packrivo-quote` 未激活（只看 `--status=active`，文件此时还在）；
- L-5 页面标题一节 725 行与基线逐行一致；
- L-6 `/get-a-quote/` 301 —— **在 9.6（mu 恢复）之后判**，这是 28r 踩过的顺序坑；
- 文件部分 9.6 → 9.5 → 9.4 → 9.2 做完后，**最终快照 = 基线，逐字节一致**。

---

## 6 · 本轮发现并修掉的一处演练判据错误（记录一下，避免下次重犯）

第一轮 step6 出过一次 `PASS=11 FAIL=1`：失败项是我新加的 `6b2`，它拿生产的 `a2a7bdf3…` 去比演练库里的 `packrivo-i18n.php`，而演练库那份一直是 `3bc325503b16a5de8cea7600801512653d4ab94db94739ac773e3d4a2741bb5e`（28s 那轮就是这个值，不是本轮换 MySQL 引入的）。两份文件不是同一版本，但同一份补丁两边都能 `git apply --check` 通过。

处理：按任务单「任何 FAIL 从复位起重跑整轮」，整轮作废重跑；判据改成环境无关的两条 ——「打前哈希 = 本步 `$BACKUP/before-mu.sha256` 里记的值」「打后 ≠ 打前」。同时在 RUNBOOK 6.2 补了注记（**L529–L532**）：这个文件没有固定基线，判据是 `--check` 通过；生产 10-29 实测是打前 `a2a7bdf3…`、打后 `f7d95f35…`。

第二轮起 step6 稳定 `12 PASS / 0 FAIL`。

---

## 7 · 提交与文件清单

```
工作台 #57 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 已完成
```

```
$ git log -3 --stat
commit 050a9a4b5cffde51a79f84ac41760205143011e2
Author: jiaxiangli477-stack <jiaxiangli477@gmail.com>
Date:   Thu Oct 29 01:11:10 2026 -0700

    29v: 演练环境换 MySQL 后完整重跑（两轮正向 73/0、最后手段 20/0、回滚 9/0、反向自检拦截）+ RUNBOOK 附录 C 记 29v 与 6.2 i18n 基线注记

 deploy/RUNBOOK-launch.md                           |   5 +
 deploy/verification-29v/README.md                  |  89 +++
 deploy/verification-29v/STEPS-29v.md               | 164 ++++
 deploy/verification-29v/env/Dockerfile.php         |  11 +
 deploy/verification-29v/env/README-ENV.md          |  92 +++
 deploy/verification-29v/logs/forward-vs-28s.diff   |  18 +
 .../verification-29v/logs/lsr-after-vs-final.diff  |  96 +++
 .../logs/lsr-start-vs-after-import.diff            | 365 +++++++++
 deploy/verification-29v/scripts/guard-selftest.sh  |  29 +
 deploy/verification-29v/scripts/lastresort.sh      | 222 ++++++
 deploy/verification-29v/scripts/lastresort29v.sh   | 188 +++++
 deploy/verification-29v/scripts/negtest.sh         |  41 +
 deploy/verification-29v/scripts/negtest29v.sh      |  43 ++
 .../verification-29v/scripts/ref/s-forward-28s.txt | 847 +++++++++++++++++++++
 deploy/verification-29v/scripts/rehearsal.sh       | 547 +++++++++++++
 deploy/verification-29v/scripts/reset.sh           |  66 ++
 deploy/verification-29v/scripts/run29v.sh          |  80 ++
 deploy/verification-29v/scripts/runner-entry.sh    |   6 +
 deploy/verification-29v/scripts/snapshot.sh        |  41 +
 deploy/verification-29v/snapshots/baseline.sha256  |   1 +
 .../snapshots/lsr-s-after-import.txt               | 846 ++++++++++++++++++++
 deploy/verification-29v/snapshots/lsr-s-final.txt  | 844 ++++++++++++++++++++
 deploy/verification-29v/snapshots/lsr-s-start.txt  | 847 +++++++++++++++++++++
 .../snapshots/lsr-titles-after.txt                 | 725 ++++++++++++++++++
 .../verification-29v/snapshots/lsr-titles-base.txt | 725 ++++++++++++++++++
 .../snapshots/s-after-negtest.txt                  | 844 ++++++++++++++++++++
 .../snapshots/s-after-rollback.txt                 | 844 ++++++++++++++++++++
 deploy/verification-29v/snapshots/s-forward.txt    | 847 +++++++++++++++++++++
 deploy/verification-29v/snapshots/s-reset.txt      | 844 ++++++++++++++++++++
 deploy/verification-29v/snapshots/s-reset2.txt     | 844 ++++++++++++++++++++
 .../snapshots/step-summary-fwd.txt                 |  10 +
 .../snapshots/step-summary-fwd2.txt                |  10 +
 deploy/verification-29v/step-summary-fwd.txt       |  10 +
 deploy/verification-29v/step-summary-fwd2.txt      |  10 +
 34 files changed, 11201 insertions(+)

commit dea6898530858f9d8f9eb3328af460c3d442ca6a
Author: jiaxiangli477-stack <jiaxiangli477@gmail.com>
Date:   Wed Oct 28 23:49:00 2026 -0700

    29v: RUNBOOK 补丁——0.1 两层补救（TLS+connector-c）、4.5 建报价页、9.8 删页、附录 C-1、判据修正 0k-2/3g/6b 与 0.2 上传落点

 deploy/RUNBOOK-launch.md | 173 ++++++++++++++++++++++++++++++++++++++++-------
 1 file changed, 147 insertions(+), 26 deletions(-)

commit cbcb3f7dc980a39407045c76a210919f03f27956
Author: jiaxiangli477-stack <jiaxiangli477@gmail.com>
Date:   Wed Oct 28 23:13:43 2026 -0700

    29v 任务单：上线实战经验补回 RUNBOOK（MySQL/connector-c、建报价页、判据修正），演练环境换 MySQL 并重跑一轮

    Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>

 packrivo-v2/docs/DSH-TASK-2026-10-29v.md | 57 ++++++++++++++++++++++++++++++++
 1 file changed, 57 insertions(+)
```

> `050a9a4` 里还带了两份**过时副本** `scripts/lastresort.sh`、`scripts/negtest.sh`（从 28r/28s 直接拷来、路径写死 `/opt/x28*`）。它们已被 `lastresort29v.sh` / `negtest29v.sh` 取代，本结果文档的提交里删掉了，避免下次照着错的路径跑。

本轮新建文件全部落在 `E:\`（仓库内 `deploy/verification-29v/`，演练日志在 `E:\破甲工作区\.tmp\28q\rehearsal\home\web\rehearsal-29v`），没有向 `C:` 写任何东西（中途一次变量名撞上只读 `$HOME` 误挂了 `C:\Users\21996` 一瞬，已当场停容器并删掉误写的 `rehearsal-29v` 目录，复核 `C:` 上没有残留）。

---

## 8 · 仍然存在的缺口（不影响本轮结论，下次别当已知）

1. **8.4 的 375/768/1024 三档横向溢出**仍需真浏览器（28u 就列为缺口），演练环境无法机检。
2. **生产 MySQL 是 26.7.0，演练是 8.4.13**；connector 生产 `3.3.10-r0`、演练 `3.4.6-r0`。报错原文与判据一致，版本差异已写进 `env/README-ENV.md`。
3. 演练库的 `packrivo-i18n.php` 与生产那份不是同一版本（`3bc32550…` vs `a2a7bdf3…`）。这属于演练基线的历史差异，第 6 步判据已改成 `--check` + 「打后 ≠ 打前」，不再依赖固定哈希。
4. 生产侧的 `INSTALL-STATE.txt` 是 28u 当场新建的（里面有 `get_a_quote_page_id=3383`）。**9.8 依赖这个文件**，如果以后有人把它删了，回滚就取不到 ID——RUNBOOK 9.8 里留了「没找到就先查文件」的提示，但文件本身要当资产保管。
