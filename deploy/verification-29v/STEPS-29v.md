# 29v 演练环境：切换 MySQL 与完整重跑步骤（STEPS-29v）

> 用途：把演练环境数据库从 **MariaDB 10.11** 换成 **MySQL 官方镜像**后的「切换步骤」+「完整重跑入口」+「deploy/verification-29v/ 日志留存清单」，供下次完整重跑照着做。
> 状态：切换已于 **2026-10-28 23:26–23:42（本地，= UTC 2026-10-29 06:26–06:42）执行完成**；完整重跑于 23:42 启动（r29v-runner），本文件是「下次怎么重跑」的口径。
> 生产对照与两处差异的详细说明：`env/README-ENV.md`。本文件不含生产凭据；`r28qlocal` 只存在于本机演练栈。

## 1. 切换 MySQL 的前提核对（先满足再动手）

| # | 前提 | 要求 | 本次实测（2026-10-28 23:5x） |
|---|---|---|---|
| 1 | Docker 引擎 | `D:\Docker\App\resources\bin\docker.exe` 可用 | Server **29.9.2** ✓ |
| 2 | 演练网络 | `r28q-net` 存在 | ✓（php/mysql/nginx/redis/runner 都在该网） |
| 3 | 宿主机站点目录 | `E:\破甲工作区\.tmp\28q\rehearsal\home\web`（容器侧 `/home/web`）：`html/`、`pristine-mu/`、`pristine-i18n/`、`seed-rollback/`、`packrivo-packages/`、`backups/CURRENT_LAUNCH` | ✓（CURRENT_LAUNCH = `packrivo-launch-20261029-0648`） |
| 4 | 基础镜像 | `r28q-php:8.3`（PHP 8.3 + wp-cli）、`r28q-host:11`（docker CLI 27.5.1）、`mysql:8.4` | ✓ |
| 5 | 灌库种子 | `E:\破甲工作区\.tmp\28q\wp-maria.sql`（MariaDB 时代 wp 库 dump，45,375,658 B，2026-10-28 23:26 重生成） | ✓ |
| 6 | 旧库可回退 | 旧 MariaDB 数据留在匿名卷（`48a2b3a2736a…`）不动 | ✓ |
| 7 | 演练口令 | `r28qlocal`（仅演练栈；与生产无任何关联） | ✓ |
| 8 | 客户端版本钉死 | php 容器 mariadb-client **11.4.13**（Alpine 3.22 仓库）；11.8.9 会先报 1045、复现不出 TLS 层 | ✓ `mariadb from 11.4.13-MariaDB` |
| 9 | MySQL 认证+TLS | `caching_sha2_password` + 自签证书 TLS（自动生成，链不全） | ✓ `/var/lib/mysql/*.pem` 8 个自签证书；root 用户 plugin=`caching_sha2_password` |
| 10 | 库已灌好 | `wp` 库 40 张 BASE TABLE、`siteurl=http://nginx`、4751 posts、**无 get-a-quote 页** | ✓ |

生产 vs 演练的三处已知差异（判据不受影响）：MySQL **26.7.0 vs 8.4.13**（TLS 报错原文逐字节相同）；mariadb-connector-c **3.3.10-r0 vs 3.4.6-r0**（插件目录 5 个 vs 6 个文件）；客户端同为 **11.4.13**。

## 2. 切换步骤（已执行，可复现）

```powershell
$dk='D:\Docker\App\resources\bin\docker.exe'
$home='E:\破甲工作区\.tmp\28q\rehearsal\home\web'

# 2.1 MySQL 容器（官方镜像；certs 初始化时自动生成）
& $dk run -d --name mysql --network r28q-net --restart unless-stopped `
  -e MYSQL_ROOT_PASSWORD=r28qlocal -e 'MYSQL_ROOT_HOST=%' -e MYSQL_DATABASE=wp `
  -v r29v-mysql-data:/var/lib/mysql -v "$home\html:/var/www/html" `
  mysql:8.4 --character-set-server=utf8mb4 --collation-server=utf8mb4_general_ci

# 2.2 灌库（等初始化完成后；原执行记录未留档，2026-10-29 补留见 §6）
& $dk exec -i mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" wp' < E:\破甲工作区\.tmp\28q\wp-maria.sql
# 校验：BASE TABLE=40、wp_options.siteurl、mysql.user 的 plugin 列

# 2.3 php 镜像（钉客户端 11.4.13）
& $dk build -t r29v-php:8.3 -f deploy\verification-29v\env\Dockerfile.php deploy\verification-29v\env

# 2.4 php 容器（与生产同环境变量口径）
& $dk run -d --name php --network r28q-net --restart no `
  -e WORDPRESS_DB_HOST=mysql -e WORDPRESS_DB_NAME=wp -e WORDPRESS_DB_USER=root -e WORDPRESS_DB_PASSWORD=r28qlocal -e WORDPRESS_DEBUG=1 `
  -v "$home\html:/var/www/html" -w /var/www/html --entrypoint php-fpm r29v-php:8.3

# 2.5 复核
& $dk exec php mariadb --version                        # 11.4.13-MariaDB
& $dk exec php sh -c 'ls /usr/lib/mariadb/plugin | wc -l'   # 期望 0（初始无插件）
```

**每轮复位自动拆掉的东西（reset.sh 第 5/6 节，本轮的关键设计）**：
1. 删演练库里的 `/get-a-quote/` 页（任何状态；演练库以前自带 ID 3220，掩盖了生产 404 问题）；
2. `apk del mariadb-connector-c`；
3. 拆 `/usr/local/bin` 里的 `mariadb-dump` / `mariadb`（`--skip-ssl` 包装）。

不拆这两样，第二轮第 0 步会「包装和插件都在」直接一次通过，两层补救再也走不到。

## 3. 完整重跑入口

### 3.1 一键驱动（推荐）

```powershell
& $dk run -d --name r29v-runner --network r28q-net --restart no `
  -v "$home:/home/web" `
  -v E:\破甲工作区\packrivo-backup\deploy\verification-29v\scripts:/opt/x29v:ro `
  -v //var/run/docker.sock:/var/run/docker.sock `
  r28q-host:11 bash /opt/x29v/runner-entry.sh
```

- 入口链：`runner-entry.sh`（**先清空上一轮 `rm -rf /home/web/rehearsal-29v`**，再 `run29v.sh > /home/web/rehearsal-29v/run29v.log 2>&1`，末尾写 `EXIT=$?`）。
- **注意**：每次启动 runner 都会清掉上一轮 `/home/web/rehearsal-29v/`（含 `baseline.sha256`）——重跑前先把上一轮日志搬进 `deploy/verification-29v/`。
- 跑完判读：`run29v.log` 末尾 `EXIT=0` 且各阶段无 FAIL。整轮序列（run29v.sh）：
  - **[1] 复位**（reset.sh）→ **[2] 复位后快照 = 本轮基线**（写 baseline.sha256；MySQL 时代新基线 `8971f447…`，MariaDB 时代的 `4972cd81…` 作废）
  - **[3] 正向 0–8（含 4.5 建页）** step0/1/2/3/4/45/5/6/7/8 → s-forward.txt + 与 28s 正向快照（`4247693f…`）的 diff（forward-vs-28s.diff，解释差异）
  - **[4] 最后手段：整库导入（MySQL）**（lastresort29v.sh，先建 canary → 无包装导入应失败且不导坏库 → 装包装 → 再导入成功 → canary 消失 → 文件回滚 → 快照回基线）
  - **[5] 二次复位**（快照必须 = 基线）→ **[6] 第二轮正向 0–8**（fwd2-*）
  - **[7] 第 9 步回滚（含 9.8 按 INSTALL-STATE 的 ID 删报价页）** → 回滚后快照必须 = 基线
  - **[8] 3.0 守卫反向自检**（negtest29v.sh：放 site 不一致 JSON → step3 必须被守卫拦截、EXIT≠0、apply 未执行 → 删文件 → 快照仍 = 基线）

### 3.2 分步 / 单步重跑（手动）

```powershell
# 通用跑法：另起一次性 runner，同样的三个挂载
& $dk run --rm --network r28q-net -v "$home:/home/web" `
  -v E:\破甲工作区\packrivo-backup\deploy\verification-29v\scripts:/opt/x29v:ro `
  -v //var/run/docker.sock:/var/run/docker.sock `
  r28q-host:11 bash /opt/x29v/rehearsal.sh step5        # 单步（step0..step9/step45）
& $dk run --rm ... r28q-host:11 bash /opt/x29v/reset.sh                                  # 只复位
& $dk run --rm ... r28q-host:11 bash /opt/x29v/snapshot.sh /home/web/rehearsal-29v/s-x.txt  # 只快照
& $dk run --rm ... r28q-host:11 bash /opt/x29v/lastresort29v.sh <H_FWD> <H_BASE>        # 只最后手段
& $dk run --rm ... r28q-host:11 bash /opt/x29v/negtest29v.sh                            # 只反向自检
& $dk run --rm ... r28q-host:11 bash /opt/x29v/rehearsal.sh selftest                    # 判据机制自检
```

### 3.3 FAIL 处理规则（任务单硬要求）

- 每步判据打印 `PASS:` / `FAIL:`，`step_end` 统计 `本步判据：PASS=n FAIL=m`，有 FAIL → 该步 `EXIT=1`；逐步汇总写 `step-summary-*.txt`。
- **任何 FAIL → 从复位开始重跑整轮**（不续跑、不跳过判据硬往前）。
- 第 0 步日志必须**依次**出现这四条，缺一条都算没复现：
  1. `TLS/SSL error: self-signed certificate in certificate chain`（0 字节）
  2. 装 `--skip-ssl` 包装后 → `1045 … Plugin caching_sha2_password could not be loaded`（0 字节）
  3. `apk add --no-cache mariadb-connector-c` → 插件目录出现 `caching_sha2_password.so`
  4. 三条探针判据全 PASS（本次实测：字节 45,379,954 > 0；CREATE TABLE=40 = BASE TABLE=40；末尾 `-- Dump completed` ✓）

## 4. deploy/verification-29v/ 应保存的文件清单

### A. 配置与脚本（已在 repo，随 git 提交）

- `env/Dockerfile.php`、`env/README-ENV.md`（环境差异说明）
- `scripts/reset.sh`、`rehearsal.sh`、`run29v.sh`、`runner-entry.sh`、`snapshot.sh`、`guard-selftest.sh`、`negtest29v.sh`、`lastresort29v.sh`、`ref/s-forward-28s.txt`
- `STEPS-29v.md`（本文件）

### B. 过程日志与结果（从宿主机 `E:\破甲工作区\.tmp\28q\rehearsal\home\web\rehearsal-29v\` 拷回）

| 文件 | 内容 | 来源 |
|---|---|---|
| `run29v.log` | 整轮驱动输出（末行 `EXIT=0`） | runner-entry |
| `reset.log` / `reset2.log` | 两次复位（拆 connector、删 get-a-quote 的输出） | run29v [1]/[5] |
| `baseline.sha256` | 本轮基线哈希（MySQL 新基线） | run29v [2] |
| `s-reset.txt` / `s-reset2.txt`（+ `.raw`） | 复位快照（=基线） | snapshot.sh |
| `step-summary-fwd.txt` / `step-summary-fwd2.txt` | 逐步 `PASS/FAIL/EXIT` 汇总 | run_steps |
| `fwd-step0.log … fwd-step8.log` + `fwd-step45.log`、`fwd2-*` | 逐步日志（step0 必须含两连错+三层补救+三条判据） | rehearsal.sh |
| `s-forward.txt`（+ `.raw`）、`forward-vs-28s.diff` | 正向快照；与 28s `4247693f…` 的 diff（解释差异） | run29v [3] |
| `lastresort.log` + `lsr-*` | 最后手段记录：`lsr-s-start.txt`、`lsr-db.sql.sha256`、`lsr-canary.id`、`lsr-s-after-import.txt`、`lsr-titles-base.txt`/`lsr-titles-after.txt`（+`lsr-titles.diff`）、`lsr-s-final.txt`、`lsr-*-vs-*.diff`；判据 L-1…L-6 | lastresort29v.sh |
| `step9.log` | 第 9 步回滚（含 9.8 删报价页 + 9a–9g） | run29v [7] |
| `s-after-rollback.txt`（+ `.raw`） | 回滚后快照（**必须 = 基线，逐字节一致**） | run29v [7] |
| `negtest.log` / `negtest-step3.log` / `s-after-negtest.txt`（+ `.raw`） | 3.0 反向自检记录（守卫 FAIL + EXIT≠0 + apply 未执行 + 快照仍=基线） | negtest29v.sh |

### C. 结果文档（交付物之一，另行写）

- `dsh-out/DSH-RESULT-2026-10-29v.md`：新 RUNBOOK 提交号与 git blob、两个 zip 哈希（不变）、`git log -3 --stat`。

## 5. 判据要点速查

- **0k-1/2/3**：字节>0 / `CREATE TABLE`=库 `BASE TABLE`（`SHOW FULL TABLES` 去表头，2026-10-29 当场改正的口径，不要退回 `SELECT COUNT(*)…--skip-column-names`）/ 末尾 `-- Dump completed`。
- **4.5**：守卫（任何状态 0 条 get-a-quote 才建）；4.5a slug 正好 `get-a-quote`；4.5b 正文正好 `[packrivo_quote]`；4.5c publish；4.5d ID 记进 `INSTALL-STATE.txt`。
- **6b**：拿**打补丁之后**的哈希比「已变」（打前=基线 `ac0a2753…`/`a2a7bdf3…`，打后≠基线）。
- **9.8**：按 `INSTALL-STATE.txt` 的 `get_a_quote_page_id` 删页，顺序在 9.6 之前；9g 任何状态计数 0；9f `/get-a-quote/` 回到 301。
- **快照三段**：复位=基线；回滚后=基线（逐字节）；正向≠基线且与 28s 的 diff 只应来自版本号等预期差异。
- **lastresort L-1…L-6**：表数=CREATE TABLE 数；canary 消失；stylesheet/template=父主题；插件未激活；页面标题一节与基线逐行一致；`/get-a-quote/` 301（在 9.6 mu 恢复后判）。
- **negtest**：3.0 守卫对 site 不一致 JSON 拦得住（FAIL + 整步非零 + `apply_updated` 计数 0），删文件后快照仍=基线。

## 6. 补留：灌库原命令记录（2026-10-29 补档，原执行记录未留档）

灌库（§2.2）的原始执行记录当时未留档，本节省按可核对证据链补留：

**重建后的原命令（与 §2.2 同一形式）**：

```powershell
& 'D:\Docker\App\resources\bin\docker.exe' exec -i mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" wp' < E:\破甲工作区\.tmp\28q\wp-maria.sql
```

**证据链（2026-10-29 逐项实测核对）**：

| 证据 | 实测值 |
|---|---|
| 种子文件 | `E:\破甲工作区\.tmp\28q\wp-maria.sql`，45,375,658 B，LastWriteTime 2026-10-28 **23:26:34**（恰在切换窗口 23:26–23:42 起点） |
| 灌后校验 SQL | `E:\破甲工作区\.tmp\28q\sql\q1.sql`（23:28:20，灌库后约 2 分钟）：`SELECT VERSION()`、`SHOW VARIABLES (have_ssl/require_secure_transport/ssl_ca/ssl_cert)`、`SELECT user,host,plugin FROM mysql.user` |
| 容器口径 | mysql 容器 Env：`MYSQL_ROOT_PASSWORD=r28qlocal`、`MYSQL_ROOT_HOST=%`、`MYSQL_DATABASE=wp`；Cmd：`--character-set-server=utf8mb4 --collation-server=utf8mb4_general_ci`；镜像 `mysql:8.4` |
| 库当前状态（重跑前实测） | `VERSION()`=**8.4.13**；root@% 与 root@localhost 的 plugin 均为 **caching_sha2_password**；`ssl_ca=ca.pem`、`ssl_cert=server-cert.pem`（自签 TLS）；wp 库 **BASE TABLE=40**；`wp_options.siteurl=http://nginx` |

该命令形式（stdin 重定向灌库）在 2026-10-29 重跑期间已用同形式实测可用（读侧验证），灌库本身不重复执行以免扰动进行中的重跑。
