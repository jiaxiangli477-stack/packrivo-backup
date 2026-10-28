# 上线执行日志 · 2026-10-28（生产）

**任务**：按 `deploy/RUNBOOK-launch.md`（提交 `c59404f`）执行第 1–8 步上线。
**授权**：负责人本轮授权 SSH 到生产，仅限本次上线任务。
**执行时间**：2026-10-28 15:25–16:35 UTC（本机 PDT 08:25–09:35）。
**结论**：**第 1 步未通过，按规则停在备份步，未进入第 2 步。** 站点文件、数据库、缓存全程未做任何变更，因此无需回滚。

> 本文件不含任何密码、密钥或 token；`E:\破甲工作区\i18n\.cf.env` 未被读取。所有输出原文照贴，凭据类字段一律未出现。

---

## 0 · 环境与前置核对（RUNBOOK 未写、但必须先确认）

### 0.1 本机两个包（第 25–28 行表）

```
$ Get-FileHash deploy\packrivo-home-v4.zip, deploy\packrivo-quote.zip -Algorithm SHA256
5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9  E:\破甲工作区\packrivo-backup\deploy\packrivo-home-v4.zip
096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93  E:\破甲工作区\packrivo-backup\deploy\packrivo-quote.zip
期望 home-v4 : 5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9   → 一致
期望 quote   : 096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93   → 一致
```

上传清单的其余 8 项（`27e-quote-redirect.patch` 647 B、`patches-28m/…404-viewport.patch` 1018 B、`i18n-merged/` 23 文件、`tools/merge-i18n.php`、`dsh-out/batch-01…05.json` + `hubs-27i.json`、`scripts/product-buyers-27g/{import,lib}.php`、`scripts/product-buyers-27e/*`、`scripts/hubs-27i/import.php`）逐项在本机仓库核对齐全，无缺件。

### 0.2 连生产

```
$ ssh -o BatchMode=yes netcup 'echo SSH-OK; hostname; date -u; whoami; uname -a'
SSH-OK
v2202609415437518901
Wed Oct 28 03:25:10 PM UTC 2026
root
Linux v2202609415437518901 7.2.6-x64v3-xanmod1 #0~20260914.g08752fb SMP PREEMPT_DYNAMIC Mon Sep 14 22:49:59 UTC 2026 x86_64 GNU/Linux
exit=0  用时 5.4 s
```

本机 `~/.ssh/config` 里只有这一个 Host（`netcup`，端口 25251，用户名 root，密钥登录，无口令提示）。**首次连接主机指纹未在 `known_hosts` 中**，本次以 `StrictHostKeyChecking=accept-new` 首次落盘。

### 0.3 生产工具链与 RUNBOOK 的假设不一致

```
$ ssh netcup 'bash /tmp/00-preflight.sh'
== 关键路径 ==
drwxr-xr-x 5 82 82 4096 Oct 27 13:58 .../wordpress
drwxr-xr-x 2 root root 4096 Oct 27 09:42 .../wp-content/mu-plugins
ls: cannot access '/home/web/packrivo-packages': No such file or directory    ← $PKG 不存在
== 工具 ==
wp: 缺失
php: command not found                                                        ← 主机上没有 php
git version 2.47.3
UnZip 6.00 of 20 April 2009
/usr/bin/sha256sum
```

站点不在主机上跑，而是 **Docker 容器**：

```
$ docker ps
php     kjlion/php:fpm-alpine      9000/tcp     ← 正在跑的 PHP-FPM（PHP 8.3.31 CLI）
php74   kjlion/php:7.4-fpm-alpine  9000/tcp
nginx   nginx:alpine                            ← fastcgi_pass unix:/run/php/php-fpm.sock
mysql   mysql (healthy)
redis   redis:alpine

$ docker inspect php --format '{{range .Mounts}}…{{end}}'
/home/web/html -> /var/www/html (rw)            ← $WP 在容器内是 /var/www/html/www.packrivo.com/wordpress
```

wp-cli 在容器里（`/usr/local/bin/wp`，7,142,777 B，与主机 `/root/claude-tools/wp-cli.phar` 同尺寸），但容器 `disable_functions=passthru,system,proc_open,popen,parse_ini_string,show_source`，直接跑 `wp` 会死在 `Process::run`：

```
$ docker exec php php /usr/local/bin/wp --info --allow-root
Error: Cannot do 'Process::run': The PHP functions `proc_open()` and/or `proc_close()` are disabled.
```

用 `-d disable_functions=` 覆盖后可用（实测 `proc_open` 存在、`wp core version` 正常返回）：

```
$ docker exec php php -d disable_functions= /usr/local/bin/wp --path=… --allow-root core version
7.1.3
```

**因此本轮的 `$WPCLI` 实际等价物是**：

```
docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root
```

（这是对 RUNBOOK 第 17 行 `WPCLI="wp --path=$WP"` 的宿主适配，不是 RUNBOOK 写的形式。`php merge-i18n.php`、`php -l` 同理都只能在容器里跑；`$PKG=/home/web/packrivo-packages` 未挂载进容器，第 2 步需要 `docker run -v` 或先 `docker cp`。）

### 0.4 生产现状与 RUNBOOK 第 6 行的描述不一致（决定性）

```
$ docker exec php php -d disable_functions= … core version
7.1.3                                        ← RUNBOOK 写「WordPress 6.7」

$ … option get template      → packrivo-static-bridge-1.2.38
$ … option get stylesheet    → packrivo-static-bridge-1.2.38   ← 当前活跃主题是「父主题本身」

$ curl -s https://www.packrivo.com/ | grep -o 'wp-content/themes/[a-zA-Z0-9._-]*' | sort -u
wp-content/themes/packrivo-static-bridge-1.2.38

$ ls .../wp-content/themes | grep -i home-v4
（无输出）
$ … theme list --format=csv | grep -c 'packrivo-home-v4'
0                                            ← 生产上没有子主题 packrivo-home-v4

$ ls .../wp-content/plugins | grep -i quote
（无输出）
$ … plugin list --format=csv | grep -c 'packrivo-quote'
0                                            ← 生产上没有报价插件 packrivo-quote

$ … option get siteurl
https://www.packrivo.com
$ … eval 'echo DB_NAME," @ ",DB_HOST;'
www_packrivo_com @ mysql
```

即：RUNBOOK 描述的现状是「父主题 + 子主题 + 报价插件」，**实际是「只有父主题，两个待上线组件都还不存在」**。第 4、5 步因此不是「`--force` 覆盖旧版本」，而是「首次安装」，而它们的回滚（`rm -r` 再 `cp -a` 从 `$BACKUP` 恢复）依赖旧目录存在——RUNBOOK 在这条路上不自洽。

---

## 1 · 备份（照抄 RUNBOOK 第 47–62 行）

```
$ mkdir -p "$BACKUP"/{theme,plugin,mu,db,i18n,rollback}
BACKUP=/home/web/backups/packrivo-launch-20261028-1629

$ cp -a "$WP/wp-content/themes/packrivo-home-v4"              "$BACKUP/theme/"
cp: cannot stat '.../wp-content/themes/packrivo-home-v4': No such file or directory
$ cp -a "$WP/wp-content/themes/packrivo-static-bridge-1.2.38" "$BACKUP/theme/"
（成功）
$ cp -a "$WP/wp-content/plugins/packrivo-quote"               "$BACKUP/plugin/"
cp: cannot stat '.../wp-content/plugins/packrivo-quote': No such file or directory
$ cp -a "$WP/wp-content/mu-plugins"                           "$BACKUP/mu/"
（成功，$BACKUP/mu/mu-plugins 下 68 个文件）
$ cp -a "$I18N"                                               "$BACKUP/i18n/"
（成功，$BACKUP/i18n/packrivo-i18n 下 100 个文件）

$ $WPCLI db export … --skip-plugins --skip-themes
（未执行——判据已在前面失败，按「停下来报告」的规则不做无谓写入）
```

### 通过判据逐条判定

| 判据（RUNBOOK 第 64–69 行） | 实测 | 结果 |
|---|---|---|
| `$BACKUP/theme/` 下有两个主题目录 | 只有 `packrivo-static-bridge-1.2.38`（1 个） | **FAIL** |
| `$BACKUP/plugin/` 下有 `packrivo-quote` | 目录为空 | **FAIL** |
| `$BACKUP/mu/` 里有全部 mu 文件 | `$BACKUP/mu/mu-plugins/` 下 68 个 | PASS（但见下方路径缺陷） |
| `$BACKUP/i18n/` 有字典 | `$BACKUP/i18n/packrivo-i18n/` 下 100 个 | PASS（同上） |
| `.sql` 大小 > 0 且 `CREATE TABLE` > 0 | 未导出（前两条已 FAIL） | 未判定 |
| `before-mu.sha256` 两个哈希已记下 | 未生成（同一块命令在 db export 之后） | 未判定 |

**两条独立的 FAIL 都是「源不存在」，不是复制出错。** 生产上的 mu 两个文件哈希（只读留档，供后续比对）：

```
ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d  packrivo-site-remediation-20261022.php
a2a7bdf39f4e9a1a78ed7ef09818be25bd9e92a1e04ba20d2fa59e8c84bdebcb  packrivo-i18n.php
```

其中 `packrivo-site-remediation-20261022.php` 与 RUNBOOK 第 230 行记录的生产基线 `ac0a2753…` **一致**，该文件没有漂移。

### 第 1 步没做变更，因此不需要回滚（RUNBOOK 第 71 行原文）

本步只创建了 `/home/web/backups/packrivo-launch-20261028-1629/{theme,plugin,mu,db,i18n,rollback}` 并复制了已存在的文件；站点文件、数据库、缓存、`$PKG`（未创建）均未改动。该目录保留，里面有父主题、mu-plugins、i18n 三份真实可用的副本和一份 `README-STEP1.txt` 说明。

---

## 2 · 停止位置

按负责人规则「任何一条不通过：立刻按该步的回滚恢复，然后停下来报告」：第 1 步无变更可回滚，**停在第 1 步之后，第 2–8 步一律未执行**。第 7 步的 Cloudflare 清缓存自然也还没轮到负责人。

### 2.1 附带发现：RUNBOOK 的备份路径与回滚路径对不上

`cp -a SRC "$BACKUP/x/"` 在 `$BACKUP/x/` 已存在时会把 `SRC` 连同目录名放进去，回滚语句却按「内容摊平」去读。逐条对账（实测备份目录已证实：`mu/mu-plugins/`、`i18n/packrivo-i18n/` 都是多一层的形态）：

| 备份（RUNBOOK 行） | 实际落点（实测） | 回滚读的路径（行） | 是否对得上 |
|---|---|---|---|
| L48 `cp -a …themes/packrivo-home-v4 "$BACKUP/theme/"` | `$BACKUP/theme/packrivo-home-v4` | L213 / L346 `$BACKUP/theme/packrivo-home-v4` | 对得上（但本生产无此源目录） |
| L49 父主题 | `$BACKUP/theme/packrivo-static-bridge-1.2.38` | 未单独回滚（父主题不动） | — |
| L50 `cp -a …plugins/packrivo-quote "$BACKUP/plugin/"` | `$BACKUP/plugin/packrivo-quote` | L189 / L351 `$BACKUP/plugin/packrivo-quote` | 对得上（但本生产无此源目录） |
| L51 `cp -a "$WP/wp-content/mu-plugins" "$BACKUP/mu/"` | **`$BACKUP/mu/mu-plugins/…`** | L262–L263、L340–L341 读 `$BACKUP/mu/packrivo-i18n.php` | **对不上，回滚会 cannot stat** |
| L52 `cp -a "$I18N" "$BACKUP/i18n/"` | **`$BACKUP/i18n/packrivo-i18n/…`** | L121、L361 `cp -a "$BACKUP/i18n"/* "$I18N"/` | **对不上，会把目录再套一层塞进 `$I18N`** |

这四处必须在上线前修掉，否则第 6 步和第 9 步的回滚本身不可用（`sha256sum -c "$BACKUP/before-mu.sha256"` 也就无从谈起）。

---

## 3 · 需要负责人拍的三件事

1. **子主题与报价插件在生产上不存在**——第 4、5 步要按「首次安装」执行吗？若是，第 1 步的备份与第 4/5 步的回滚要改成「安装前无旧版本 → 回滚 = 停用并删除新目录」，而不是「从 `$BACKUP` 恢复」。补一条实测：现在 `/get-a-quote/` 的 301 是 mu-plugin `packrivo-site-remediation-20261022.php` 里那条老别名重定向发出的（`grep -rl "get-a-quote" wp-content/plugins wp-content/mu-plugins` 只命中它和它的三个 `.bak`），生产上没有任何报价插件；`packrivo-buyer-experience` 0.2.7 的头部自述是「Public catalogue discovery and packaging enquiry context」，不是配置器。也就是说装 `packrivo-quote` 会是全新组件，第 6 步 27e 打完才第一次有 `/get-a-quote/` 真页面。
2. **WP 版本 7.1.3**（RUNBOOK 写 6.7）。子主题 `packrivo-home-v4` 4.0.1 只在本机 6.7.2 上验过；这一步要不要先在生产做一次「装后不启用」的兼容性检查？
3. **`$WPCLI` 的宿主适配**（容器里 `docker exec php php -d disable_functions= …`，`$PKG` 需 `docker run -v` 或 `docker cp`）要不要写进 RUNBOOK 再继续？以及上表那三处备份/回滚路径要不要先修？

在这三条没有答复之前不继续动生产。
