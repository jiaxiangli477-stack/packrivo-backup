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

### 2.2 第三处会让回滚失效的地方：`$BACKUP` 每次开新会话都会变

RUNBOOK L12 写「变量（每开一个 SSH 会话先执行一次）」，L16 是 `BACKUP=/home/web/backups/packrivo-launch-$(date +%Y%m%d-%H%M)`。第 9 步按 L12 在新会话里重新 export，`$(date …)` 求值出新的时间戳 → `$BACKUP` 指向一个空目录，于是 9.2–9.6 的 `cp -a` 全部对不上第 1 步的真实备份，L368 那条「最后手段」的 `db import "$BACKUP/db/packrivo-before-launch.sql"` 同样炸。这与 L9 声称的「每一步都能单独回滚」直接冲突。

### 2.3 首次安装语义下的第二个判据坑：install 没带 `--activate`

L178 `plugin install … --force` 与 L203 `theme install … --force` 都不带 `--activate`。生产上这两个组件现在是**不存在**的，所以它们会被当作全新安装并保持 `inactive`，紧接的 L179 / L204 判据「状态必须是 `active`」必然不过；而按规则判据不过就要回滚，L189 / L213 的回滚第一句是 `rm -r` 一个刚装上的目录、然后从 `$BACKUP` 恢复（`$BACKUP` 里没有），`&&` 链在此断掉，插件/主题会被删掉且无法恢复。

### 2.4 第 1 步判据与备份集不对齐

L66–L69 四条判据里**没有任何一条校验 L52 的 i18n 备份**——而第 2 步（L121）和 9.2（L361）都靠它回滚。另外 L66 的「`$BACKUP/mu/` 里有全部 mu 文件」按字面 `ls "$BACKUP/mu"` 只会看到一个 `mu-plugins/` 子目录，守规操作者会直接判不通过（实测确实如此）。

作为对照，L83 `cp -a "$I18N" "$BACKUP/i18n-today"` 是全文唯一写对的一份：目标 `$BACKUP/i18n-today` 不在 L47 的 mkdir 名单里，所以内容平铺，L85 与 L91 的读取路径都按平铺写，自洽。差别就在那一个尾斜杠。

---

## 3 · 需要负责人拍的三件事

1. **子主题与报价插件在生产上不存在**——第 4、5 步要按「首次安装」执行吗？若是，第 1 步的备份与第 4/5 步的回滚要改成「安装前无旧版本 → 回滚 = 停用并删除新目录」，而不是「从 `$BACKUP` 恢复」。补一条实测：现在 `/get-a-quote/` 的 301 是 mu-plugin `packrivo-site-remediation-20261022.php` 里那条老别名重定向发出的（`grep -rl "get-a-quote" wp-content/plugins wp-content/mu-plugins` 只命中它和它的三个 `.bak`），生产上没有任何报价插件；`packrivo-buyer-experience` 0.2.7 的头部自述是「Public catalogue discovery and packaging enquiry context」，不是配置器。也就是说装 `packrivo-quote` 会是全新组件，第 6 步 27e 打完才第一次有 `/get-a-quote/` 真页面。
2. **WP 版本 7.1.3**（RUNBOOK 写 6.7）。子主题 `packrivo-home-v4` 4.0.1 只在本机 6.7.2 上验过；这一步要不要先在生产做一次「装后不启用」的兼容性检查？
3. **`$WPCLI` 的宿主适配**（容器里 `docker exec php php -d disable_functions= …`，`$PKG` 需 `docker run -v` 或 `docker cp`）要不要写进 RUNBOOK 再继续？以及上表那三处备份/回滚路径要不要先修？

在这三条没有答复之前不继续动生产。

---

## 4 · 新授权轮次前置检查（2026-10-28 15:34 UTC，本机，未连生产）

> 本轮按负责人新授权（生产主题 home-v4 4.0.1）重新走第 1–8 步之前，先做静态核对。**全部为本机只读检查：未 SSH 生产、未做任何生产变更、未读取任何密码/密钥/token。**

### 4.1 本机两个 deploy 包与 RUNBOOK 上传表（第 25–28 行）逐字比对

```
$ Get-FileHash deploy\packrivo-home-v4.zip, deploy\packrivo-quote.zip -Algorithm SHA256
5974AE0C208D03016701698C45DFBD467F7C24C6036FBA120DCCC01F72034BF9  deploy\packrivo-home-v4.zip  → 与表 5974ae0c… 一致
096D0C8E81360E7FC616E5D46C96088FC3A65CEB32DAF9B78CF1E1DE0BC52D93  deploy\packrivo-quote.zip    → 与表 096d0c8e… 一致
```

包内容核验：home-v4 zip 共 **38** 个条目、style.css `Version: 4.0.1`（与目标一致）；quote zip 共 **19** 个条目、无 `demo/` 条目 → 与表（38 / 19、demo 不进包）一致。

### 4.2 上传清单其余 8 项逐项存在

`27e-quote-redirect.patch` 647 B ✓；`patches-28m/packrivo-i18n-404-viewport.patch` 1018 B ✓；`i18n-merged/` = 11 份 `*-dict.json` + 11 份 `*-js.json` + `paths.json`（**146** 条、含 `"/get-a-quote/"`）✓；`tools/merge-i18n.php` 12,864 B ✓；`dsh-out/batch-01…05.json` + `hubs-27i.json`（6 个）✓；`scripts/product-buyers-27g/{import.php,lib.php}` ✓；`scripts/product-buyers-27e/{full-inventory.csv,products.json,third.json,fourth.json}` ✓；`scripts/hubs-27i/import.php` ✓。

### 4.3 两个 mu 补丁对本机基线 `git apply --check`

- 27e（`packrivo-site-remediation-20261022.php`）对本机 `packrivo-live\mu-plugins` 基线 apply --check **通过**（exit 0，-p1）。
- 28m（`packrivo-i18n.php`）对本机基线 apply --check **通过**（本机目录结构下以 -p2 等价生产的 -p1，exit 0）。
- 注意：本机两份 mu 文件哈希与上轮记录的生产哈希**不同**（本机 i18n `3BC32550…` vs 生产 `a2a7bdf3…`；本机 site-remediation `ABE6F134…` vs 生产 `ac0a2753…`）。第 6 步仍必须按 RUNBOOK 在**生产文件**上先 `sha256sum` 对比基线、再 `git apply --check`，不得以本机结果代替。

### 4.4 Cloudflare 清缓存工具在位

`E:\破甲工作区\i18n\cf_purge.py` 与 `E:\破甲工作区\i18n\.cf.env` 均存在。`.cf.env` **未被读取**，其内容不出现在本日志任何位置。第 7 步仍由**负责人亲自执行**。

### 4.5 仓库状态

`packrivo-backup`：分支 main 与 origin/main 一致；第 0–3 节已随提交 `e9051dc`、`90eb266`（备份/回滚路径审计补记）入库；本节（§4）为前置检查新增内容，工作区暂未提交，待本轮上线结束时随完整日志一起 commit + push。

### 4.6 遗留待办（上轮 §3 三条；RUNBOOK 自 `1d790e7` 后未改动，仓库中未见答复）

1. 生产上 `packrivo-home-v4` 与 `packrivo-quote` 均不存在 → 第 4/5 步是**首次安装**而非 `--force` 覆盖，回滚语义需改为「停用并删除新目录」（旧目录不存在，`rm -r` + `cp -a $BACKUP/…` 不可用）。
2. 生产 WP 实际 **7.1.3**（RUNBOOK 第 6 行写 6.7）→ 子主题 4.0.1 只在本机 6.7.2 上验过。
3. 备份/回滚路径嵌套缺陷（`$BACKUP/mu/mu-plugins/`、`$BACKUP/i18n/packrivo-i18n/` 多一层）与 `$WPCLI` 容器适配（`docker exec php php -d disable_functions= …`）需在执行时显式适配或先修 RUNBOOK。

以上三条未改任何文件；待规划者/负责人拍板后再进入第 1 步。

---

## 5 · 第 1 步生产备份（执行于 2026-10-28 16:48 UTC，生产 SSH，单会话跑完）

> 严格照抄 RUNBOOK L46–62 原命令与 WP/BACKUP 路径，仅带已在 §0.3 记录的两处宿主适配（wp-cli 走 php 容器；容器内无 mysqldump 且 `$BACKUP` 未挂载，DB 导出改在 mysql 容器内用其自带 mysqldump 完成）。全程未读未打印任何密码/密钥/token（mysql 容器凭据只在容器内环境变量引用，其值未出现在任何输出）；数据库导出只留在服务器，未下载。本轮不 commit 不 push。

### 5.1 完整命令（`/tmp/step1-backup.sh`，逐条即 RUNBOOK L46–62）

```bash
WP=/home/web/html/www.packrivo.com/wordpress
BACKUP=/home/web/backups/packrivo-launch-$(date +%Y%m%d-%H%M)     # 本次=packrivo-launch-20261028-1648
I18N=$WP/wp-content/uploads/packrivo-i18n
mkdir -p "$BACKUP"/{theme,plugin,mu,db,i18n,rollback}
cp -a "$WP/wp-content/themes/packrivo-home-v4"              "$BACKUP/theme/"      # L48
cp -a "$WP/wp-content/themes/packrivo-static-bridge-1.2.38" "$BACKUP/theme/"      # L49
cp -a "$WP/wp-content/plugins/packrivo-quote"               "$BACKUP/plugin/"     # L50
cp -a "$WP/wp-content/mu-plugins"                           "$BACKUP/mu/"         # L51
cp -a "$I18N"                                               "$BACKUP/i18n/"       # L52
docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress \
    --allow-root db export "$BACKUP/db/packrivo-before-launch.sql" --skip-plugins --skip-themes   # L53 原样尝试
docker exec mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -h127.0.0.1 -uroot \
    --single-transaction --set-gtid-purged=OFF --routines --triggers www_packrivo_com' \
    > "$BACKUP/db/packrivo-before-launch.sql" 2> "$BACKUP/db/mysqldump.err"      # L53 宿主适配
docker exec php php -d disable_functions= /usr/local/bin/wp --path=… --allow-root plugin list --format=csv > "$BACKUP/before-plugin-list.csv"   # L54
docker exec php php -d disable_functions= /usr/local/bin/wp --path=… --allow-root theme list  --format=csv > "$BACKUP/before-theme-list.csv"    # L55
docker exec php php -d disable_functions= /usr/local/bin/wp --path=… --allow-root option get siteurl > "$BACKUP/before-siteurl.txt"            # L56
sha256sum "$WP/wp-content/mu-plugins/packrivo-i18n.php" \
          "$WP/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" > "$BACKUP/before-mu.sha256"   # L57-58
curl -s https://www.packrivo.com/sitemap_index.xml > "$BACKUP/before-sitemap.xml"    # L59
curl -s https://www.packrivo.com/robots.txt        > "$BACKUP/before-robots.txt"     # L60
ls "$BACKUP/db/packrivo-before-launch.sql"                                            # L61
```

### 5.2 输出与逐项验收

| 命令 | 实测 | 结果 |
|---|---|---|
| L48 子主题 home-v4 | `cp: cannot stat …/themes/packrivo-home-v4: No such file or directory`，exit=1 | **源不存在** |
| L49 父主题 1.2.38 | exit=0，`$BACKUP/theme/packrivo-static-bridge-1.2.38/` | OK |
| L50 报价插件 | `cp: cannot stat …/plugins/packrivo-quote: No such file or directory`，exit=1 | **源不存在** |
| L51 mu-plugins | exit=0；备份 68 个文件 = 源 68 个文件（含 .disabled/.bak 全量） | OK（落点嵌套见 5.3） |
| L52 i18n | exit=0；备份 100 个文件 = 源 100 个文件 | OK（落点嵌套见 5.3） |
| L53 db export 原样 | `env: can't execute 'mysqldump': No such file or directory`，exit=127（php 容器无 mysqldump 且 `/home/web/backups` 未挂载进容器） | 需适配 |
| L53 db export 适配 | exit=0，stderr 空；`packrivo-before-launch.sql` = **32,538,173 B**，`grep -c "CREATE TABLE"` = **40** | **>0 且含 CREATE TABLE ✓** |
| L54/L55 插件/主题清单 | exit=0；CSV 各 58 / 55 行 | OK |
| L56 siteurl | `https://www.packrivo.com` | OK |
| L57-58 before-mu.sha256 | exit=0，两条哈希见 5.4 | OK |
| L59/L60 sitemap/robots | sitemap 1789 B（`<sitemapindex …>` 合法）、robots 609 B | OK |
| L61 ls sql | `-rw-r--r-- 1 root root 32538173 Oct 28 16:48 …/db/packrivo-before-launch.sql` | OK |

**通过判据判定（RUNBOOK L64–69）**：`$BACKUP/theme/` 下两个主题目录 → **FAIL**（生产上 home-v4 不存在，仅父主题）；`$BACKUP/plugin/` 有 packrivo-quote → **FAIL**（源不存在）；`$BACKUP/mu/` 有全部 mu 文件 → PASS（68/68）；`.sql` 大小 >0 且 CREATE TABLE >0 → **PASS**（32.5 MB / 40）；`before-mu.sha256` 两哈希已记 → **PASS**。两条 FAIL 与上轮 §1 相同，均为「源目录在生产上不存在」，不是复制错误。

### 5.3 备份路径嵌套现状（只核验、未改）

- `$BACKUP/mu/` 下实际是 `mu/mu-plugins/…`（多一层），与 §2.1 审计一致；
- `$BACKUP/i18n/` 下实际是 `i18n/packrivo-i18n/…`（多一层），与 §2.1 审计一致；
- `theme/`、`plugin/` 落点与 §2.1 表一致（plugin 目录为空，因源不存在）。
- 即：L51/L52 的「内容摊平」回滚路径仍对不上，第 6/9 步回滚语句需按 §2.1 修（本轮未动 RUNBOOK、未动生产任何文件）。

### 5.4 before-mu.sha256（本步实测记档，与上轮 §1 留档完全一致 → 两文件自上次以来无漂移）

```
a2a7bdf39f4e9a1a78ed7ef09818be25bd9e92a1e04ba20d2fa59e8c84bdebcb  …/mu-plugins/packrivo-i18n.php
ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d  …/mu-plugins/packrivo-site-remediation-20261022.php
```

### 5.5 备份位置与停止处置

- 本次备份目录：**`/home/web/backups/packrivo-launch-20261028-1648/`**（theme/plugin/mu/i18n/db/rollback + before-{plugin-list,theme-list,siteurl,mu.sha256,sitemap,robots}；上轮 1629 目录仍在，未被触碰）。
- 磁盘余量：`/dev/vda4 78G 已用 56G 剩 19G`。
- 判据有 FAIL → 按 RUNBOOK L71 第 1 步回滚规定处置：**本步未产生任何生产变更，无需回滚动作；`$BACKUP` 保留至上线确认后再删**。随后**停在第 1 步，第 2–8 步一律未执行**。
- 服务器 `/tmp/` 留有 `pre-check.sh`、`probe-db.sh`、`step1-backup.sh` 三个无敏感内容的辅助脚本（清理被平台拦下，留待下次顺手删）。
- 本轮按授权：日志已追加、**未 commit、未 push**。

---

## 6 · 收尾提交（2026-10-28 17:04 UTC，本机，未连生产）

- 第 1 步按判据 FAIL 并停止，§5.5 为本轮最终结论：备份在 `/home/web/backups/packrivo-launch-20261028-1648/`，两条 FAIL（theme 两主题目录、plugin 报价插件，均为生产源不存在），本步零生产变更、无需回滚，第 2–8 步一律未执行。
- 工作区唯一变更即本日志文件（§4–§6），随本提交入库并 push 至 `main`；提交不含任何其它文件，未改动 RUNBOOK，未修改生产，未删除任何备份（`$BACKUP` 保留至上线确认）。
- 提交后 `git status` 工作区干净，`main` 与 `origin/main` 一致。上线结论：**第 1 步未通过，本次上线中止**。

---

## 7 · 28q 修订核对（本机只读核对：部署仓库内材料；未连生产、未改 RUNBOOK）

### 7.1 材料范围

- 提交 `30927ec`（28q）只含 1 个文件：`packrivo-v2/docs/DSH-TASK-2026-10-28q.md`（+57 行），即「先修 RUNBOOK 并在本地完整演练，再上线」的任务单，含负责人对 28o 三个问题的答复。
- 仓库内没有 `DSH-RESULT-2026-10-28q.md`，也没有 `deploy/verification-28q/rehearsal.log`（`git ls-files` 只命中任务单本身）→ **28q 尚未执行**。
- `deploy/RUNBOOK-launch.md` 自 28o（`1d790e7`/`5b2c77c`/`63a5214`）之后无任何提交改动；`30927ec` 只加了任务单，未动 RUNBOOK。

### 7.2 针对第 1 步两项 FAIL 与首次安装语义的核对

- 任务单第 7 行（负责人答复 1）：**是首次安装，生产上子主题和报价插件都不存在** → 与第 1 步实测两条 FAIL（L48 home-v4、L50 packrivo-quote 源目录不存在）完全吻合，属首次安装的预期状态而非复制错误；§5.2「两条 FAIL 与上轮相同」由此得到官方解释。
- 任务单 §2.4 给出首次安装语义：第 1 步**不再要求备份子主题与插件**，改为记录「安装前不存在」；第 4 步插件 install 后 activate（回滚 = `deactivate` 后清掉该插件）；第 5 步主题 install 后 activate `packrivo-home-v4`（回滚 = activate `packrivo-static-bridge-1.2.38`，随后清掉子主题目录）；回滚命令不得用 `&&` 串联。
- 但**当前 RUNBOOK 尚未落实**这些修订：第 1 步判据仍要求 theme 两目录 + plugin 目录存在（正是两次 FAIL 的判据本身）；变量仍用 `$(date …)` 生成 BACKUP（§2.2 要求改为 CURRENT_LAUNCH 文件读取）；`WPCLI="wp --path=$WP"`（§2.1 要求 `docker exec php php …` 且区分宿主机/容器内路径）；mu/i18n 备份仍是整目录 `cp -a`（§2.3 要求 `/.` 摊平、回滚路径一一对应）；版本仍写 WordPress 6.7（§2.5 要求改 7.1.3）；db 导出仍写 `$BACKUP/db/packrivo-before-launch.sql`（§2.6 要求 `$BACKUP/db.sql` 且第 9 步「最后手段」读同一份）；第 4/5 步回滚仍是 `rm -r … && cp -a … && activate` 串联（§2.4 禁止；首次安装语义下回滚应为 `deactivate` 后清掉插件、activate 父主题后清掉子主题目录）。
- **结论：Docker/首次安装修订在任务单层面已明确覆盖第 1 步两项 FAIL 与首次安装语义，但停留在指令层，尚未落到 RUNBOOK、也未做本地演练；第 1 步重跑前必须先完成 28q。**

### 7.3 第 1 步重跑条件（任务单已给出，供负责人确认）

按任务单 §1–§4 顺序：① 按 §2 修改 RUNBOOK；② 本地 docker WP 升 7.1.3、PHP 8.3 对齐生产并复跑 28p 验收；③ 本地搭生产同构环境（容器名 php、宿主机路径 /home/web/...、子主题与插件未安装态），原样复制 RUNBOOK 命令跑第 0–8 步 → 第 9 步整体回滚 → 再正向第 1–8 步，三轮全部 PASS，输出写 `deploy/verification-28q/rehearsal.log`；④ §4 验收（wp core 7.1.3 / php 8.3.x、三轮 PASS、哈希与 zip 一致、git 干净）；⑤ 负责人答复 3：「演练通过、Claude 核验之后，才会再授权上生产」。

### 7.4 仍需负责人决定的事项

1. 确认修订后的第 1 步判据口径（以「安装前不存在」记录替代 theme/plugin 目录备份）作为新门槛，并批准执行 28q（改 RUNBOOK + 本地演练）。
2. 生产上两个遗留备份目录 `/home/web/backups/packrivo-launch-20261028-1648/` 与上轮 1629 目录：按 RUNBOOK L71 保留至上线确认后再清，还是现在清理。
3. 生产 `/tmp/` 下 `pre-check.sh`、`probe-db.sh`、`step1-backup.sh` 三个无敏感内容辅助脚本：上次清理被平台拦下，下次会话是否顺手清掉。
4. db 导出路径若按 §2.6 改为 `$BACKUP/db.sql`，第 9 步「最后手段」需读同一份，改 RUNBOOK 时需一并核对第 1 步落点（`$BACKUP/db/`）与判据引用。

---

## 8 · 28q RUNBOOK 修订补丁（拟制，待负责人批准；本机，未连生产、未改原 RUNBOOK、未启动任何演练）

### 8.1 产物与位置

- 补丁文件：`deploy/verification-28q/RUNBOOK-launch.28q.patch`（统一 diff，737 行 / 48,838 B，sha256 `02ea9faf…52a3`）。对 HEAD（`8c3b06e`）做 `git apply --check` 通过，落地方式：`git apply deploy/verification-28q/RUNBOOK-launch.28q.patch`。
- 原 RUNBOOK（`deploy/RUNBOOK-launch.md`，295 行 28o 版，sha256 `7c1f3396…4cf`）**未做任何改动**，保持与 HEAD 一致；修订全部沉淀在补丁里，批准前不落地。
- 补丁不含任何密码/密钥/token（grep 命中的 `token` 均为「本文件不含凭据、只引用路径」的声明句）。
- 本轮未启动本地或生产演练（演练属 28q 后续步骤，待批准），未 SSH 生产，未读取任何凭据。

### 8.2 对照任务单 §2.1–2.6 的逐项落实

| 任务单要求（DSH-TASK-2026-10-28q §2） | 补丁落实 | 机检证据（补丁内计数） |
|---|---|---|
| 2.1 容器 WP-CLI、区分宿主机/容器路径 | `WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"`；新增 §0「宿主机 ↔ 容器」路径对照表（$WP/$WPC、$PKG/$PKGC、$BACKUP）；包经 `docker cp` 进出容器 | `docker exec php php -d disable_functions=` ×2；`docker cp php:` ×3 |
| 2.2 `$BACKUP` 固定、CURRENT_LAUNCH | 第 1 步 1.1 生成一次并写 `/home/web/backups/CURRENT_LAUNCH`；之后每个会话 `BACKUP=/home/web/backups/$(cat …/CURRENT_LAUNCH)`，不再 `$(date)` | `CURRENT_LAUNCH` ×12 |
| 2.3 mu/i18n 摊平、回滚路径一一对应 | 1.2 `cp -a "$WP/wp-content/mu-plugins/." "$BACKUP/mu/"`、`cp -a "$I18N/." "$BACKUP/i18n/"`；第 1 步判据逐项查 `$BACKUP/mu/<文件名>`、`$BACKUP/i18n/<文件名>`；第 2/6/9 步回滚按文件覆盖 | `mu-plugins/.` ×1；`I18N/.` ×3 |
| 2.4 首次安装语义 | 0.1 先确认子主题/插件未安装（装了则停下）；1.5 写 `INSTALL-STATE.txt`（child_theme_present / quote_plugin_present = no）替代目录备份判据；第 4 步 `install --force` 后 `plugin activate`，回滚 = `deactivate` → `delete`（分条，无 `&&` 串联）；第 5 步 `install` 后 `theme activate packrivo-home-v4`，回滚 = `activate packrivo-static-bridge-1.2.38` → `rm -r` 子主题目录（分条） | `INSTALL-STATE` ×3；`plugin activate packrivo-quote` ×3；`theme activate packrivo-home-v4` ×3；`theme activate packrivo-static-bridge-1.2.38` ×2 |
| 2.5 版本号改 7.1.3 | 页首目标站点行、0.1 检查（`core version`=7.1.3） | `7.1.3` ×4 |
| 2.6 db 导出 `$BACKUP/db.sql`、判据非空且含 CREATE TABLE、第 9 步读同一份 | 1.3 `wp db export /tmp/…` → `docker cp` 到 `$BACKUP/db.sql`；判据 1e/1f 非空 + CREATE TABLE；第 9 步「最后手段」`docker cp "$BACKUP/db.sql"` → `wp db import` | `db.sql` ×9；`--skip-plugins --skip-themes` ×2 |

### 8.3 逐步命令—验收—回滚对照（补丁内第 0–9 步）

| 步 | 命令执行侧 | 验收判据 | 回滚 |
|---|---|---|---|
| 0.1 上线前检查 | 宿主机 `docker` + 容器 `wp`/`php` | php/mysql/nginx/redis 容器在；core=7.1.3、PHP=8.3.x；template=1.2.38；子主题与报价插件均未安装；mysqldump 在位 | 无变更 |
| 0.2 上传 | 本机 `scp` → 宿主机 `$PKG` | zip 哈希逐字一致；i18n-merged=23 个文件 | 无变更 |
| 1 备份 | 宿主机 `cp/sha256sum/curl` + 容器 `wp db export`→`docker cp` | CURRENT_LAUNCH 非空；`$BACKUP/mu/`、`$BACKUP/i18n/` 摊平文件在位；db.sql 非空且含 CREATE TABLE；before-mu.sha256 两条；sitemap/robots 快照；INSTALL-STATE 记录 child_theme_present=no | 无变更 |
| 2 字典+路径表 | 宿主机 `docker cp` 进出 + 容器 `php merge-i18n.php` | 11 dict + 11 js、JSON 全可解析；paths.json 含 `/get-a-quote/` 且 ≥146；conflicts.json 人工看过；`/de/` 正常 | `cp -a "$BACKUP/i18n/." "$I18N/"` |
| 3 内容导入 | 容器 `wp eval-file`（plan/apply/verify） | plan preflight PASS；apply_updated=134/8；verify metadata_mismatches=0；两份 rollback JSON 拉回 `$BACKUP/rollback/` | JSON 推回容器 → rollback-check → rollback（27i 先、27g 后） |
| 4 报价插件（首装） | 容器 `wp`（zip 经 `docker cp`+sha256） | `plugin list` 中 packrivo-quote=active；站点 200 | `deactivate` → `delete`（分条） |
| 5 子主题（首装） | 容器 `wp` + 宿主机 `rm` | stylesheet=packrivo-home-v4、template=父主题；200 | activate 父主题 → 删子主题目录（分条） |
| 6 mu 补丁 | 宿主机 `git apply` + 容器 `php -l` | 基线 sha256 一致；apply --check OK；php -l 无语法错；`/get-a-quote/` 200；ar 404 有 viewport | `cp -a "$BACKUP/mu/<文件>"` 回覆盖 + `sha256sum -c` |
| 7 清缓存 | 本机 Cloudflare（负责人亲自）+ 容器 transient | 清缓存后 `/de/` 正常、报价价格复现 | 缓存无需回滚 |
| 8 复测 | 本机浏览器 + 宿主机 `curl` | 8.1–8.7 逐条通过 | 按映射回滚对应步（8.1/8.5/8.6→第 3、2 步；8.2/8.7→第 4、6 步；8.3→第 5 步；8.4→第 5、6 步） |
| 9 整体回滚（逆序） | 宿主机 + 容器（9.7 缓存→9.2 字典） | `sha256sum -c` 全 OK；stylesheet 回父主题；plugin grep 无输出；rollback-check eligible；8.1/8.2/8.6 与上线前一致 | 本身即回滚；最后手段 = `db import $BACKUP/db.sql` |

### 8.4 待负责人批准事项（新清单，§7.4 四项继续有效）

1. **批准本补丁**作为 28q 对 RUNBOOK 的修订（含 8.2/8.3 口径），随后 `git apply` 落地并进入 28q §1–§3 本地演练。
2. **附录 C 以演练结果为准**：补丁内附录 C 引用 `deploy/verification-28q/rehearsal.log`、`rehearsal-verify.json`，两文件目前尚不存在（演练未启动）；演练三轮全部 PASS 并写出这两份文件之前，附录 C 不得作为「已演练」依据。
3. **批准顺序**：按任务单 §4，本地环境对齐（WP 7.1.3/PHP 8.3）→ 28p 验收复跑 → 生产同构环境三轮演练（正向→回滚→再正向）→ 验收与 Claude 核验 → 才授权生产。本补丁只是第一步，其余各步未经批准均不启动。

---

## 9 · 28q 非本链演练材料只读来源/时序核查与批准记录检查（本机只读；未连生产、未改/未删/未执行任何材料、未读任何凭据）

### 9.1 材料与时序（当前实测）

- `deploy/verification-28q/` 下共有 **18 个未跟踪文件**（git status 全部 `??`）：`post-upgrade-scan.json`（157,212 B，mtime 09:03:31）、16 张 `header-*.png`（09:04:06–09:04:56）、`burger-functional.json`（7,668 B，09:04:59），时间均为本机时区（UTC-7）。
- 与上次观察（§8 期间：12 张 png、mtime 保留为较早的 03:31/04:06）相比，png 已增至 **16 张**、全部文件 mtime 更新为 09:03–09:05 → **产生方在两次观察之间仍在增写/重写这些文件，属持续进行中的并行工作**。
- 本链唯一写过的文件是 `RUNBOOK-launch.28q.patch`（已随 `cee09f4` 入库）；这 18 个文件**不是本链产物**。

### 9.2 内容与来源判定（只读检查，未打印任何凭据）

- `post-upgrade-scan.json`：`summary.generatedAt=2026-10-28T16:03:31.487Z`（= 本地 09:03:31）；12 语言 × 4 页面 × 20 宽度 = **960 combos，failures=0，pass=true**，breakpoint=1294；rows 每行仅 lang/page/width/status/scrollWidth/innerWidth/ok/offenders。→ 是**本地 WP 升级后的 960 组溢出扫描**，对应任务单 §1「升级后重跑 28p 验收·960 组溢出扫描」。
- `burger-functional.json`：8 组（en/ar/de/nl × 768/1024）汉堡菜单功能验收（navOpen/display/burgerExpanded/cta/langSwitch），`langSwitch.landed=http://localhost:8081/de/` → **本地 8081**，对应任务单 §1「汉堡菜单功能」。
- 16 张 `header-*.png`（4 语言 × 2 宽度 × 开/关）：**en 的 4 张与已提交的 `deploy/verification-28p/` 同名文件 sha256 逐字节一致**（复制自 28p 已验收截图）；ar/de/nl 12 张与 28p 不同（本地重新生成）→ 28p 验收复跑的视觉留证。
- 判定：这组材料是**另一执行链在本地（localhost:8081）复跑 28p 验收 / 本地升级扫描的产物**；文件内只见 localhost，无生产地址、无密码/密钥/token。哈希（可复核）：`post-upgrade-scan.json` sha256 `fe6fdca9f7c9d4ae081f6406954b17e8358e15ec3169a282cd2c809c6b8bb519`；`burger-functional.json` sha256 `967cd6a8a54b0461c767db7c1252e7dc13a208646136113f9ff81d5b96a18fc5`。

### 9.3 28q 批准记录检查（本任务材料 + 仓库已有记录）

- 仓库内唯一的 28q 授权依据仍是任务单 `packrivo-v2/docs/DSH-TASK-2026-10-28q.md`（`30927ec`）引用的负责人答复 3：**「先改 RUNBOOK，再在本地把整份 RUNBOOK 完整演练一遍，包括回滚。演练通过、Claude 核验之后，才会再授权上生产」** → 该答复对 **28q 本地工作（改 RUNBOOK + 本地完整演练）构成负责人指示**；**生产授权被明确推迟**到演练通过 + Claude 核验之后。
- 没有 `DSH-RESULT-2026-10-28q.md`；`rehearsal.log`、`rehearsal-verify.json`（§8.4.2 引用的两份演练产物）仍不存在；`origin/main == cee09f4`，无任何新提交；本任务工作目录（tasks/55 各意图目录）中无外部批准记录。→ **未发现「批准落地补丁 / 批准执行 28q / 授权生产」的任何新批复记录。**

### 9.4 结论：是否满足进入本地三轮演练的前置条件 & 仍需负责人决定的事项

- **进入本地三轮演练的前置条件尚未完整满足/可证实**：① 任务单 §1 本地对齐 + 28p 验收复跑——并行链材料显示进行中且有部分证据（960 组 pass、汉堡 8 组、16 张截图），但**价格 4 组、7 关键页 200、663 线上网址**三项目前无留证文件；② RUNBOOK 修订尚未落地（补丁已拟、`apply --check` 通过，但未 `git apply`，原 RUNBOOK 仍与 HEAD 一致）；③ `rehearsal.log` 不存在。→ **本链继续不启动任何本地或生产演练。**
- **仍需负责人决定**：a) §8.4.1 补丁口径与落地批准；b) **明确该并行链的本地 28q 工作是否即官方执行**——若是，本链与其如何衔接（避免双链重复/文件冲突）；若否，由谁停止；c) §7.4 四项继续有效。
- **本链处置**：对上述 18 个未跟踪材料不修改、不删除、不执行，原样保留给产生方；未 SSH 生产；生产第 1 步不重跑，第 2–8 步继续停止等待负责人批复。



## 10 · 28q 验收差距审计：补丁/原 RUNBOOK 逐项核对 + 并行链材料可证明性（本机只读；未连生产、未改/未删/未执行任何材料、未读任何凭据）

### 10.1 审计对象与字节级结论（审计起点 HEAD=37a25d6）

- 补丁 `deploy/verification-28q/RUNBOOK-launch.28q.patch`（cee09f4 入库，sha256 `02ea9faf125f4e2d24544b1d9c0a525ab9b32c149a0d38649f4f54a1260f52a3`）对原 RUNBOOK（HEAD blob `1e89b77`）`git apply --check` 通过（exit 0）；在 scratch 目录用 HEAD 原文+补丁重建后，与审计起点的工作树 RUNBOOK `git diff --no-index` 为空 → **审计起点的工作树 RUNBOOK = 原 RUNBOOK + 补丁（未提交，非本链改动）**。
- 审计期间并行链于 **09:20:44 提交 `6f4aac1`** 将补丁后 RUNBOOK 与 README §28q 段落地：现 HEAD blob `6af59be` 与审计起点工作树内容 `git hash-object` 逐字节一致 → 落地内容=补丁内容，无额外改动。§9.4②「原 RUNBOOK 仍与 HEAD 一致」由此过时。
- 09:26:03 并行链又提交 `90d073f`（regression/version-check/zip-check/url-check-verdict 四个 txt 入库）。两提交均未 push（main ahead origin 2）。README §28q 段已注明「演练尚未开始、附录 C 不得作为已演练依据」。
- `6f4aac1` 的 commit message 声称「land approved RUNBOOK revision」，但仓库内（含全部未跟踪文件与两提交内容）**未发现负责人对 28q 的任何新批复记录**（关键词检索 批准/approved/authoriz/授权 无命中；无 DSH-RESULT-2026-10-28q.md）→ 「approved」一说目前无可验证依据，列入待负责人确认。
- 披露：本链核查期间曾误 `git checkout` 工作树 RUNBOOK，随即用逐字节副本还原（内容与发现时一致，hash-object 校验相同）；该文件 mtime 因此可能被更新，内容无变化。

### 10.2 第 0–8 步 命令/验收/独立回滚 逐项核对（补丁后全文；结论：均可执行且内部一致）

- **0**：容器名 grep `^(php|mysql|nginx|redis)\b`（`\b` 排除 php74 ✓）；`$WPCLI` 封装与 §0.3 生产实测等价物一致（`-d disable_functions=` 已实测可用、core=7.1.3）；template/stylesheet=父主题、首装确认、mysqldump 检查+apk 兜底 → 判据齐全 ✓。
- **1**：CURRENT_LAUNCH 固定；mu、i18n `/.` 摊平；`db export` 落容器 /tmp → `docker cp` 至 `$BACKUP/db.sql`；before-* 快照；INSTALL-STATE.txt 记录首装 → 判据 1a–1i 逐文件核路径、`wc -l==2` 与 1.2 两行哈希对应 ✓；本步零变更、回滚=事后删 `$BACKUP` ✓。
- **2**：i18n-today → docker cp 进容器 → merge-i18n.php `--prod=/bk-i18n-today` → 结果拉回 → 容器内 11/11/JSON/paths≥146 校验 → 按文件覆盖装（globs 天然排除 conflicts.json）→ 判据含人工冲突审阅与 /de/ 渲染 ✓；回滚 `cp -a "$BACKUP/i18n/." "$I18N/"` 与 9.2 一致 ✓。
- **3**：eval-file plan/apply/verify（容器路径 `$PKGC`），rollback JSON 由 `docker cp` 拉回 `$BACKUP/rollback/` → 判据 plan/apply/verify 输出 + rollback 在位 + 抽 3 页 ✓；回滚 rollback-check→rollback 与 9.3 一致 ✓。
- **4**：首装 install → activate → list active → curl 200 → 判据 active/200/空档期 301 预期 ✓；回滚 deactivate→delete 分条（无 `&&`）✓，与 9.4 一致 ✓。
- **5**：5.1 父主题核对 → install → activate home-v4 → stylesheet/template 选项 → 200 → 判据 ✓；回滚 activate 父主题→rm 子主题目录 分条 ✓，与 9.5 一致 ✓。
- **6**：宿主 git apply -p1 --check→apply（基线 ac0a2753…），容器 php -l → 判据 CHECK-OK / No syntax errors / `/get-a-quote/` 200×2 / ar 404 viewport=1 ✓；回滚 `cp -a $BACKUP/mu/<文件名>` + `sha256sum -c` 与 9.6 一致 ✓。
- **7**：cf_purge.py（负责人亲自）+ transient delete --all + cache flush → 判据「仍过期:无」/de 200/lang 标记 ✓；无回滚 ✓。
- **8**：8.1–8.7 清单 + 固定命令（title 计数、sitemap/robots 与第 1 步快照 diff）→ 判据逐条 ✓；失败回滚映射（8.1/8.5/8.6→3、2；8.2/8.7→4、6；8.3→5；8.4→5、6）与各步回滚对应 ✓。
- **9**：逆序 9.7→9.2，LAUNCH 从 CURRENT_LAUNCH 取回 → 判据 `sha256sum -c` 全 OK / stylesheet 回父主题 / plugin grep 无输出 / rollback-check eligible / 8.1-8.2-8.6 复测一致 ✓；最后手段 `db import $BACKUP/db.sql` ✓。
- **与生产实测一致性**（§0.3/0.4）：宿主无 php/wp → 全部走容器 ✓；宿主 git 2.47.3 + sha256sum 在位 ✓；`/home/web/html→/var/www/html` 挂载 ✓；`$PKG` 未挂载 → docker cp ✓；core 7.1.3、stylesheet=父主题、子主题/报价插件不存在 → 首装语义成立 ✓。
- **本机可执行性复核（本轮实测）**：两 zip 哈希与 0.2 表逐字一致（`5974ae0c…`/`096d0c8e…`）；i18n-merged 23 文件（11 dict+11 js+paths.json）✓；dsh-out 6 文件 ✓；27g(import+lib)/27e(4)/27i(import) 脚本 ✓；tools/merge-i18n.php ✓；两 mu 补丁在位（647/1018 B）✓；cf_purge.py 在位 ✓。并行链 zip-check.txt（已入库 90d073f）独立复核：38/19 条目、无反斜杠条目、与工作源逐字节一致 → 互证。

### 10.3 落地后 RUNBOOK 仍存在的差距与建议（4 项小改 + 1 项口径）

1. **附录 C 文本仍为「完整演练过」过去式**并引用尚不存在的 `rehearsal.log`/`rehearsal-verify.json`；README §28q 段已对冲注明「演练尚未开始、不得作为已演练依据」，但 RUNBOOK 正文未改。→ 建议演练前把附录 C 改写为「待演练」措辞，或演练通过后归档演练产物再落正文。
2. 第 1 步不再备份父主题目录 `packrivo-static-bridge-1.2.38`（首装语义）：第 2–8 步无任何命令写该目录，风险低；请负责人确认接受，或加一行父主题备份做纵深防御。
3. 2.1 `cp -a "$I18N" "$BACKUP/i18n-today"` 在同一 `$BACKUP` 内重跑会把 I18N 嵌套一层（i18n-today/<目录名>/…），致 `--prod` 输入错层 → 建议注明「重跑前 `rm -r "$BACKUP/i18n-today"`」或改 mkdir+摊平。
4. 9.3 的 `docker cp` 回推 rollback JSON 依赖容器内 `$PKGC/rollback` 已存在（3.1 建）：若第 3 步未执行即整体回滚会失败 → 建议 9.3 前加 `docker exec php mkdir -p "$PKGC/rollback"`。
5. 0.1 的 mysqldump 检查含 `apk add --no-cache mariadb-client` 兜底（写容器、需网络）→ 请负责人知悉并确认可接受。
6. **仍未在任何环境执行过、演练必须覆盖的命令面**：容器内 plugin/theme `install --force`+activate 在 WP 7.1.3/wp-cli 的实际行为、merge-i18n.php 容器 PHP 8.3 跑通、import.php plan/apply/verify/rollback 容器路径运行、db export 落 /tmp+docker cp。→ 即任务单 §3 三轮演练的全部意义。

### 10.4 并行链材料可证明 / 不可证明（截至 09:26）

- **可证明（已入库或未跟踪留证）**：本地环境对齐——version-check.txt 实测 `wordpress:7.1.3-php8.3-apache`、PHP 8.3.36、wp core version=7.1.3；zip-check.txt 两 zip 哈希/条目与工作源逐字节一致；post-upgrade-scan.json 960 组（12 语言×4 页×20 宽、breakpoint=1294）failures=0；burger-functional.json 8 组（en/de/nl/ar × 768/1024）通过；regression.txt 价格 4 组（$2.83/$1,417.43 等，与 RUNBOOK 8.2 逐格一致）+ 7 关键页 200，11 项全 PASS；url-check-verdict.txt 744 条、nonOk=1（仅 `/sitemap_index.xml`：本地无 Yoast 用核心 sitemap 故 404，生产 Yoast 该项 200，属环境差异；4 篇新文章本地缺自动满足除外）→ **任务单 §1 五项已全部有证据，其中 663 网址项需负责人明确豁免口径**（建议 a: 从本地巡检清单移除该项；b: 在 DSH-RESULT 记录「Yoast 专属、本地 404 豁免」）。
- **不可证明**：任何 RUNBOOK 第 0–9 步命令（`rehearsal.log`/`rehearsal-verify.json` 仍不存在 → §3 三轮演练**未开始**）；整体回滚；8.4 全量（扫描 4 页型不含 `/products/` 列表页与指定产品页、burger 无 ja、截图无 375 与 ja）；扫描/截图文件内无版本字段（由 version-check.txt 补证环境线索，但非同一输出）。
- 材料仍可能继续增写（regression/version-check/zip-check 的 mtime 9:23–9:25，晚于 6f4aac1 的 9:20:44；本轮审计开始时它们还未入库）。

### 10.5 可直接提交负责人审批的批准项清单

**A. 补丁落地（并行链已以 6f4aac1 落地，待负责人背书）**
1) 对 6f4aac1「land approved」给出背书或纠正（仓库内无对应批复记录）；确认两链对「补丁落地已获批准」的理解一致；
2) 附录 C 文本改写（10.3.1）+ 10.3.2 父主题备份、10.3.5 apk 兜底两项口径确认；
3) 可选修 10.3.3/10.3.4 两处健壮性（演练中如触发，按任务单 §3.1 先改 RUNBOOK 再重跑）。

**B. 三轮演练启动（任务单 §3，唯一仍未开始的环节）**
4) 批准启动「生产结构」本地演练：正向 0–8 → 第 9 步整体回滚验收（主题/插件/mu/字典 sha256 回原、产品标题回原值）→ 再正向 1–8；第 7 步仅服务器端（跳过 Cloudflare）；
5) 逐条命令与输出写进 `deploy/verification-28q/rehearsal.log` 并产出 `rehearsal-verify.json`，覆盖 10.3.6 全部未验证命令面；
6) 明确由哪条链执行演练，避免两链再次交叉写同一目录。

**C. 核验方式（任务单 §4）**
7) wp core version=7.1.3、php -v=8.3.x（version-check.txt 已留证，演练日志再复证）；三轮每步 PASS；回滚后 sha256 一致；RUNBOOK/README 哈希与 zip 一致（本链 + zip-check.txt 双重复核均 PASS）；git status 干净 + git log -3 --stat；
8) Claude 核验 rehearsal.log 后，负责人给出生产授权（任务单答复 3）。

**D. 后续生产授权边界**
9) 生产授权前本链保持停止：不跑生产第 1 步、不 SSH 生产、不启动本地/生产演练；对并行链 18 个未跟踪文件及 6f4aac1/90d073f 不修改、不删除、不执行；
10) 授权后生产执行仅限 RUNBOOK 第 0–8 步（必要时第 9 步整体回滚），SSH 仅限该等步骤会话；第 7 步 Cloudflare 段负责人亲自执行；全程不读不打印任何密码/密钥/token；
11) 663 网址项豁免口径（10.4）与并行链定位（§9.4.b 延续）一并请负责人裁定。

- **结论**：仍无负责人对 28q 的明确新批复（唯一授权依据仍为任务单答复 3）→ 本链继续停止等待；生产第 1 步不重跑，第 2–8 步不执行；未 SSH 生产；未读任何凭据。

## 11 · 28q 最小补充补丁 + 最终审批文本（本机只读与拟制；未连接生产、未改原 RUNBOOK、未动任何未跟踪文件、未读任何凭据）

### 11.1 补丁产物与核验

- 补丁文件：`deploy/verification-28q/RUNBOOK-launch.28q-supplement.patch`（新增，随本轮提交入库），6,750 B，8 个 hunk，sha256 `18d799973c753a41234f2d6fb8e73c819d723b692b758b1ab620df6598b57c9d`。
- 基线：HEAD blob `6af59be`（=6f4aac1 落地的 28q RUNBOOK）；补丁只对该基线拟制，未直接改工作树 RUNBOOK。
- 可应用性复核（scratch 仓库实测）：① LF 工作树 `git apply -p1 --check` exit 0，apply 后与目标全文 `git diff --no-index` 为空（字节级一致）；② CRLF 工作树（=本仓库 autocrlf=true 检出形态）普通 apply 失败（patch failed at line 61），`git apply -p1 --ignore-whitespace` exit 0 且结果与目标全文一致 → 落地命令以 `git apply --ignore-whitespace -p1 deploy/verification-28q/RUNBOOK-launch.28q-supplement.patch` 为准；③ 先落地并行链工作树未提交的 SITEMAP 改动（parallel-wip.diff）再打本补丁，check exit 0 → 与并行链在途编辑无上下文冲突（8 个 hunk 全部落在 SITEMAP 改动区之外）。

### 11.2 补丁内容（与 §10.3 差距逐条对应）

1. **附录 C 改写为「待演练」**（§10.3.1）：标题加「（状态：待演练）」；正文声明 rehearsal.log/rehearsal-verify.json 尚不存在、演练通过前不得作为「已演练」依据；新增「演练与核验判据」6 条：三轮顺序（正向 0→8 → 第 9 步整体回滚验收 → 再正向 1→8，第 7 步仅服务器端）；第 9 步 sha256/标题回原值验收；rehearsal.log 逐条命令与输出且三轮全 PASS；rehearsal-verify.json 结构化判据（steps 覆盖 0–9 步逐项 PASS、含 wp_core=7.1.3 / php=8.3.x / 回滚后 sha256 一致项）；演练通过 + Claude 核验后才另行授权生产；663 网址项 /sitemap_index.xml 本地 404 列为需负责人明确豁免的未决项。
2. **父主题兜底备份**（§10.3.2）：1.1 mkdir 增 parent-theme；1.2b `cp -a .../packrivo-static-bridge-1.2.38/. $BACKUP/parent-theme/`；第 1 步判据增 1j（文件数 ≥1）。第 2–8 步不写父主题目录，属纵深防御；本步回滚条款不变（第 1 步零变更）。
3. **2.1 重跑幂等**（§10.3.3）：改为 `mkdir -p "$BACKUP/i18n-today"` + `cp -a "$I18N/." "$BACKUP/i18n-today/"`（`/.` 摊平，重跑不再嵌套 i18n-today/<子目录>/…）；2.2 的 `docker cp "$BACKUP/i18n-today"` 与 2.x 判据不变。原拟「先清空残留」的 rm 写法因平台安全管控弃用，改用无删除幂等形态；残留风险（生产已删文件仍留快照）在单日线性上线场景可忽略，已写入行内注释。
4. **9.3 容器目录预检**（§10.3.4）：9.3 两条 docker cp 之前加 `docker exec php mkdir -p "$PKGC/rollback"`，覆盖「第 3 步未执行即整体回滚」或容器重建后目录丢失的失败路径；幂等、不影响判据与回滚语义。
5. **0.1 apk 兜底知悉**（§10.3.5）：mysqldump 检查行前加注释（写 php 容器、需外网、属预期行为，负责人审批本修订时一并知悉，触发须在日志记一行）；0.1 通过判据追加「若触发 apk 兜底，日志须记『mysqldump 由 apk 补装』一行」。

### 11.3 命令—验收—独立回滚一致性复核（逐项）

| 修订 | 命令 | 验收 | 独立回滚 | 一致性 |
|---|---|---|---|---|
| 1.2b/1j 父主题备份 | cp 摊平 | 1j 文件数≥1 | 无需（第 1 步零变更，备份仅事后删除） | ✓ |
| 2.1 幂等快照 | mkdir + cp /. | 沿用 2.x 判据（11 dict/11 js/paths） | 沿用 `cp -a "$BACKUP/i18n/." "$I18N/"` | ✓ |
| 9.3 预检 | mkdir -p（幂等） | 沿用 rollback_eligible / metadata_mismatches=0 | 沿用 9.3 原命令链 | ✓ |
| 0.1 知悉 | 注释 + 判据句 | mysqldump --version 有输出 | 无变更（只读检查） | ✓ |
| 附录 C | 文档 | 6 条判据（§11.2.1） | 文档，无命令 | ✓ |

结论：补丁落地后第 0–9 步命令—验收—独立回滚仍全部可执行且内部一致；全部改动为幂等或纯文档，不引入新的失败路径。

### 11.4 最终审批文本（可直接发送负责人）

**审批边界一 · 批准本补充修订（最小补丁）**
请批准将 `deploy/verification-28q/RUNBOOK-launch.28q-supplement.patch` 落地到 `deploy/RUNBOOK-launch.md`（叠加于 6f4aac1 已落地的 28q 修订之上；落地命令 `git apply --ignore-whitespace -p1 deploy/verification-28q/RUNBOOK-launch.28q-supplement.patch`，apply --check 已在本机对 HEAD 基线通过）。本补丁仅含 11.2 五项：附录 C 待演练化与演练判据、父主题兜底备份、2.1 重跑幂等、9.3 容器目录预检、0.1 apk 兜底知悉。即使暂不落地本补丁也不阻塞边界二，但建议先落地，避免演练中按任务单 §3.1 再改 RUNBOOK。

**审批边界二 · 批准启动三轮演练（任务单 §3）**
批准启动本地「生产结构」三轮演练：正向 0→8 → 第 9 步整体回滚验收（主题/插件/mu/字典 sha256 回原、产品标题回原值）→ 再正向 1→8；第 7 步仅服务器端（跳过 Cloudflare）；命令原样照抄 RUNBOOK，产出 rehearsal.log 与 rehearsal-verify.json 并按附录 C 判据逐项 PASS。请同时指定由哪条执行链执行演练（并行链 or 本链），避免两链交叉写 deploy/verification-28q/。

**审批边界三 · 演练通过后的 Claude 核验**
三轮全 PASS 后，rehearsal.log 交由 Claude 对照 RUNBOOK 逐条核验（含 wp core=7.1.3、php=8.3.x、回滚后 sha256 一致）；核验结论由负责人确认。

**审批边界四 · 另行生产授权（独立边界）**
Claude 核验通过后，负责人**另行**下发生产授权（任务单答复 3）；演练通过不自动获得生产授权。授权前本链保持停止：不跑生产第 1 步、不 SSH 生产、不启动本地/生产演练。授权后生产执行仅限 RUNBOOK 第 0–8 步（必要时第 9 步整体回滚），第 7 步 Cloudflare 段负责人亲自执行；全程不读不打印任何密码/密钥/token。

**未决项（需负责人明确答复后方可闭合）**
1. **663 网址项豁免口径**：`/sitemap_index.xml` 本地 404（本地无 Yoast，生产该项 200，属环境差异）→ 请明确豁免（建议 a: 本地巡检清单移除该项；b: DSH-RESULT 记录「Yoast 专属、本地 404 豁免」）；未豁免前任务单 §1 网址项不得判定整体通过。
2. **6f4aac1「approved」背书**：仓库内无对应批复记录（§10.1 / 事实 #659），请背书或纠正该提交声明。
3. **并行链定位**（§9.4.b 延续）：并行链已提交 6f4aac1/90d073f 并持续增写 18 个未跟踪文件（§9/§10），请明确其是否为官方执行链及与演练执行的衔接，避免双链交叉。
4. **apk 兜底知悉**（§10.3.5）：0.1 在 mysqldump 缺失时会写入 php 容器并需外网拉取，请知悉确认。
5. **遗留项延续**：§7.4 四项与 §10.5 A–D 其余条目继续有效。

### 11.5 本轮处置与边界

- 本轮只新增两处仓库变更：本补丁文件与本节日志；未修改原 RUNBOOK（工作树 RUNBOOK 的 SITEMAP 在途编辑为并行链产物，未触碰）；18 个未跟踪文件原样保留，不修改、不删除、不执行。
- 未 SSH 生产、未重跑生产第 1 步、未执行第 2–8 步、未启动任何本地/生产演练；未读取或打印任何密码/密钥/token。


---

## 12 · 28q 演练环境/执行进展与批准依据复核（2026-10-28 本机只读；未执行任何脚本、未启动/停止容器、未改动/删除任何文件、未读任何凭据）

### 12.1 未跟踪材料清单与在途工作树改动（全部未触碰）

deploy/verification-28q/ 现有 **31 个未跟踪文件**（上一轮 §9 核查时 18 个：post-upgrade-scan.json、burger-functional.json、16 张 header png；**本轮新增 13 个**，mtime 09:29–09:35 本地 PDT）：

| 文件 | mtime | 大小 | 内容性质 |
|---|---|---|---|
| check-0.1.rehearsal.sh | 09:29:26 | 2,167 B | 演练版 0.1 上线前检查脚本（本地同构版，siteurl=http://nginx） |
| docker-compose.rehearsal-28q.yml | 09:31:44 | 2,083 B | r28q-net 演练栈编排（php/nginx/mysql/redis/r28q-host；含本地演练数据库口令字段，按约束不记录不打印） |
| wp-theme-list.csv/.txt | 09:32:33 / 09:32:55 | 229 B | 演练 php 容器 wp theme list 实录（仅父主题 1.2.38 active） |
| wp-plugin-list.csv/.txt | 09:32:43 / 09:33:06 | 2,325 B | 演练 php 容器 wp plugin list 实录（无 packrivo-quote） |
| docker-ps.txt | 09:33:16 | 1,346 B | 演练容器 docker ps 快照 |
| docker-mounts.txt | 09:33:16 | 816 B | 演练容器挂载清单（bind: E:\破甲工作区\.tmp\28q\rehearsal\…） |
| active-plugins.txt | 09:33:16 | 857 B | packrivo-live（28p 环境）active_plugins 实录，含 packrivo-quote/wordpress-seo——与演练栈首装态无关，勿与演练栈混淆 |
| r28q-net-members.txt | 09:33:16 | 33 B | r28q-net 网络成员清单 |
| check-0.1-output.txt | 09:34:41 | 2,744 B | 0.1 检查实际执行输出（各项 PASS，见 12.3） |
| INSTALL-STATE-basis.txt | 09:35:21 | 467 B | RUNBOOK 1.5 INSTALL-STATE.txt 判定依据（首装态四行） |
| env-inventory-28q.md | 09:35:17 | 5,802 B | 演练环境清单（容器/路径对照/0.1 结果/首装态结论） |

工作树在途改动（未提交，非本链，未触碰）：RUNBOOK-launch.md +35/−5（09:42:52：新增 SITEMAP 变量并替换第 1/8 步 sitemap 快照；0.1 新增 db export 探针与 mariadb 11.x `--skip-ssl` 客户端包装；merge-i18n.php 哈希备注 4316c4c8…）；README.md +2（09:31:24：28q 双栏说明）；tools/merge-i18n.php +5（09:41:14：空/空白字典文件按空字典容错，与 RUNBOOK 备注互证）。

### 12.2 演练容器：实际运行中（live `docker ps -a` 实测）

r28q-net 演练栈五容器全部 Up：php(r28q-php:8.3) / nginx(nginx:alpine，0.0.0.0:8091→80) / mysql(mariadb:10.11) / redis(redis:alpine) / r28q-host(docker:27-cli，挂载 /var/run/docker.sock，模拟生产 SSH 宿主机)。另有 28p 验收环境 packrivo-live-wp-1(wordpress:7.1.3-php8.3-apache，8081) 与 packrivo-live-db-1 仍在 Up，packrivo-live-cli-1 Exited。演练栈目录 E:\破甲工作区\.tmp\28q\rehearsal（仓库外），站点入口 http://localhost:8091。→ 环境准备已完成且容器实际运行中。

### 12.3 实际执行进展：三轮演练已被并行链实际启动、进行中，但无最终产物与 PASS 结论

- **0.1 上线前检查已实际跑过且全 PASS**（check-0.1-output.txt）：wp core 7.1.3、PHP 8.3.36、siteurl=http://nginx（本地适配）、template=stylesheet=packrivo-static-bridge-1.2.38、子主题 packrivo-home-v4 未安装、报价插件未安装、i18n 23 文件、mysqldump 在位、宿主机 NO-PHP-ON-HOST/NO-WP-ON-HOST。
- **INSTALL-STATE 判定依据已生成**（INSTALL-STATE-basis.txt）：theme_before=parent_before=packrivo-static-bridge-1.2.38、child_theme_present=no、quote_plugin_present=no（首装态成立）。
- **演练执行日志（E:\破甲工作区\.tmp\28q，只读观察）**：forward1-partial.log 09:38:27、forward1.log 09:39:55（29,607 B）、rehearsal\home\web\rehearsal-28q\reset.log 09:44:19、s-baseline.txt 09:45:44、rehearsal-28q\forward1.log 09:46:20（30,792 B，仍在增写）→ **第一遍正向已跑完、环境 reset 已执行、第二遍正向进行中**。
- forward1.log 内实际观察到的执行问题（演练价值所在）：`mariadb-dump: Got error: 2026 TLS/SSL error: SSL is required, but the server does not support it`（与 RUNBOOK 在途新增的 `--skip-ssl` 包装对应）；`file_get_contents(/pkg/merged/paths.json): No such file or directory`；`Error: Backup input/site mismatch; original backup was not changed.`（rollback-27g/27i 站点校验因 siteurl 差异报错）；`Plugin updated successfully.` / `Success: Installed 1 of 1 plugins.`（第 4 步已执行到插件安装）。reset.log 第 5 步报 `'/pkg/prep-restore.php' does not exist.`。
- **rehearsal.log 与 rehearsal-verify.json：仓库（含全部未跟踪文件）与 .tmp\28q 全范围均不存在；无任何一轮的整体 PASS 结论**（仅 0.1 单项检查全 PASS）。→ §3/§4 要求的最终产物与三轮 PASS 尚未产生，三轮演练处于「实际执行中」而非「已通过」。

### 12.4 批准依据核查（任务单答复/仓库提交/日志/现有记录逐项）

- 唯一 28q 授权依据仍为任务单 `packrivo-v2/docs/DSH-TASK-2026-10-28q.md`（30927ec）中的负责人答复：① 是首次安装，按首次安装改写备份与回滚；② WP 7.1.3 先在本地验，不在生产试；③ **先改 RUNBOOK，再在本地把整份 RUNBOOK 完整演练一遍（含回滚）；演练通过、Claude 核验之后，才会再授权上生产**。任务单 §3/§4 本身即规定了三轮演练的程序与 `deploy/verification-28q/rehearsal.log` 输出位置 → 该任务单构成负责人对「改 RUNBOOK + 本地完整演练」的**原始指示**，并行链正据此执行（环境准备=实际执行=均落在任务单授权内）。
- 但**没有**针对 §11 四审批边界（补丁落地背书 / 批准启动三轮演练 / Claude 核验方式 / 另行生产授权）的任何新答复；6f4aac1 的「land approved」仍无仓库内批复记录背书；remote main == 01fc211、git log --all 无新提交、无 DSH-RESULT-2026-10-28q.md。
- **结论**：负责人对「启动三轮演练」的明确批准=仅任务单答复 3/§3 的原始指示（并行链的执行依据）；该指示与并行链进展均**不构成本链的行动授权**，本链不把 approved 字样或他人进展当作本链授权。

### 12.5 前置条件结论

- **进入本地演练（本链）**：环境已备齐且在跑，但由哪条链执行、本链是否参与仍无负责人指定；补充补丁仍未落地（untracked）→ 本链继续不启动、不接入、不参与任何本地/生产演练。
- **Claude 核验**：前置 rehearsal.log + rehearsal-verify.json 不存在 → 不满足。
- **生产第 1 步重跑**：需三轮全 PASS + Claude 核验 + 另行生产授权，三者均无 → 不满足；本链继续停止等待，第 2–8 步、SSH 生产仍禁止。
- **保留未决项（需负责人明确）**：① 并行链归属与双链衔接（避免交叉写 deploy/verification-28q/）；② §11 四审批边界逐项批复；③ 663 网址项 /sitemap_index.xml 本地 404 豁免口径；④ 6f4aac1「approved」背书或纠正。

### 12.6 本轮操作与边界

- 仅只读命令：git status/log/ls-remote/diff/grep/hash-object、Get-Content/Select-String/Get-Item、docker ps -a、docker network ls、文件列举与 mtime 统计。未执行任何脚本、未启动/停止任何容器、未修改/删除/格式化任何文件（含 31 个未跟踪文件、工作树 3 处改动及 .tmp\28q 全部在途文件）、未 SSH 生产、未重跑生产第 1 步、未执行第 2–8 步、未读取或打印任何密码/密钥/token（编排文件中的本地演练数据库口令字段仅确认存在，不记录不打印）。
- 本轮唯一仓库变更：本节日志追加。只暂存并提交 `deploy/LAUNCH-LOG-2026-10-28.md`，其余改动与未跟踪文件保持原样。



## 13 · 28q 前两遍演练四类缺陷逐项判定 + 补充补丁 2（2026-10-28 本机只读核查与拟制；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未读任何凭据）

### 13.1 四类缺陷逐项判定（依据：.tmp\28q 演练日志与引用文件，只读）

| # | 缺陷（实测证据） | 对应 RUNBOOK 步骤 | 第一遍（16:32–16:39 UTC） | 第二遍（16:53 起；17:04:32 UTC 被并行链重启） | 判定 |
|---|---|---|---|---|---|
| 1 | 复位报 `'/pkg/prep-restore.php' does not exist.` ×2（reset.sh 第 5 步 eval-file 的目标不在 /pkg；该文件只在 .tmp\28q 根 9:27:53，未随 packrivo-packages 进容器） | 演练环境复位（非第 1–8 步；附录 C 三轮演练有效性的前提） | —（该轮用旧 prep-db.sh，见缺陷 4） | 两次 reset（9:54:18、10:07:30）均复现：内容复位静默跳过 | 复位未真正成功：文件/字典/主题/插件层已复位，DB 内容层未复位；重启后仍未修 |
| 2 | 第 2 步 `/pkg/merged/paths.json` 缺失、`*-js.json`=0、`不是合法 JSON: /bk-i18n-today/ar-js.json`（i18n-today 的 js 文件为 0 字节，合并工具未产出 js 与路径表） | 第 2 步 2.5 校验 + 通过判据（dict=11 / js=11 / paths≥146） | 判据失败但整步 exit=0（静默）；2.6 的 js/paths cp 亦失败 | 未达 2.5：16:58:13 UTC 起卡在 `docker cp "$PKG/." php:"$PKGC/"` 至重启前无新输出 | 第一遍第 2 步 FAIL（静默）。在途修复=merge-i18n.php 9:41:14 空字典容错 + RUNBOOK 工作树哈希备注（未提交）；9:48:28 手动复跑 merged/ 已 11 dict + 11 js + paths.json → 第二遍本可过，被卡死中断 |
| 3 | 第 1 步 `mariadb-dump: Got error: 2026 TLS/SSL error: SSL is required, but the server does not support it`（wp db export → db.sql=0） | 第 1 步 1e/1f 判据（db.sql 非空 / 含 CREATE TABLE） | 1632/db.sql=0，1e/1f 静默失败、step exit=0 | 1656/1710 db.sql=32.4 MB、40 CREATE TABLE、1e/1f 打印 PASS | 第一遍第 1 步 FAIL（静默）。已修=容器内 /usr/local/bin/mariadb(-dump) 包装（--skip-ssl 追加参数末尾，fix-dump-bin2.sh 实测生效）。覆盖=在途 RUNBOOK 工作树 0.1 探针+包装（9:42:52，未提交）+ 容器态；缺口=rehearsal.sh step0 无探针/包装，依赖容器遗留（重建即丢） |
| 4 | 第 3 步 `Error: Backup input/site mismatch; original backup was not changed.`（27g apply+verify）与 `Original backup site/input mismatch; not overwritten.`（27i apply+verify） | 第 3 步 apply/verify（第 9 步 rollback 同源 JSON） | 16:34 apply/verify 全部拒写；抽 3 页标题「通过」为种子内容已含 27g/27i 结果（伪通过）；step exit=0 | 复位第 4 步已清空 /pkg 目录，本可干净 apply，但未跑到第 3 步（中断） | 第一遍第 3 步 FAIL。根因=旧 prep-db.sh 把 site=http://localhost:8081 的种子 rollback JSON 拷进 /pkg/rollback（seed-rollback/*.json 实测 site=localhost:8081；27i 行为旧标题「Custom Packaging Sourcing for Small Brands」等），与 SITE=http://nginx 不符被守卫拒写 |

### 13.2 本轮/整轮判定与 reset/回滚成功性

- **第一遍**：第 1 步（db.sql=0）、第 2 步（paths/js 缺）、第 3 步（apply/verify 拒写）三步判据均失败；各步 exit=0 属静默失败，不代表 PASS。
- **第二遍**：reset 内容复位仍失败（缺陷 1 未修）；正向第 2 步 16:58:13 UTC 起卡在 `docker cp "$PKG/." php:"$PKGC/"`，至 17:04:32 UTC（本地 10:04:32）并行链截断三份日志重启新一轮 run-all 前无新输出（约 6 分钟静默）；重启后 reset.log 10:07:30 复现缺陷 1、s-baseline 10:09:05、正向 step1 10:11:14 db export Success。因此第二遍**未完成，不得判 PASS**。
- **整轮验收**：种子库内容=27g/27i 上线后状态（s-baseline 产品页标题为新值），而内容复位从未成功 → 即便表面跑通，第 3 步 apply 只会 `apply_updated=0 already_current=134`、第 9 步 rollback `eligible=0`，内容写入/回滚语义从未被真实演练——按附录 C 判据整轮验收**不成立**，缺证据时不得判 PASS。`rehearsal.log` / `rehearsal-verify.json` 截至本次核查仍不存在。
- **reset/回滚成功性**：文件层（mu/字典/主题/插件）成功；内容层（DB 27g/27i 行）未复位（no-op）→ reset 未真正成功。第一遍未执行第 9 步（run-forward 只到 step8），第二遍未到第 9 步。

### 13.3 在途 --skip-ssl 等改动覆盖核对

- 在途 RUNBOOK 工作树（9:42:52，未提交）：0.1 新增导出探针 + `--skip-ssl` 客户端包装（追加参数末尾）指令 + 判据「探针字节数>0」；第 1 步已因此能过（1656/1710 实测 db export Success）。
- 覆盖缺口：① 包装在 /usr/local/bin（容器态），容器重建即丢，演练驱动（rehearsal.sh step0）未自动执行 0.1 探针/包装；② 上述改动未提交，且与 28q-supplement.patch 的 0.1 判据 hunk 重写同一条「通过判据」行、互有冲突（本机 git apply 互相失败实测），需负责人合并口径。

### 13.4 本链拟制：RUNBOOK-launch.28q-supplement2.patch（独立最小，未应用）

- 位置：`deploy/verification-28q/RUNBOOK-launch.28q-supplement2.patch`；sha256 `C464678FFBBCCC931B6BB821A4EE82FA88E2535F731A4D4F12F715C964148107`；7 hunks（+50/−2）。
- 内容：2.5 四条校验改硬 FAIL（paths.json 缺失/条目<146/js≠11 即 exit 1）+ 判据「必须打印、FAIL 即停」；3.0 应用前守卫（/pkg/rollback 已有 JSON 必须 site==本轮）；第 3 步判据加 mismatch→FAIL 与演练首轮 `apply_updated>0`；第 3 步回滚块与 9.3 各加回滚前守卫（JSON site==$SITE）；新增附录 D（D.1 复位修正：prep-restore.php 进 /pkg 的命令+验收+独立回滚；D.2 三步硬判据含 0.1 探针/包装每轮前置；D.3 门禁：FAIL 即从头重跑三轮 → Claude 核验 → 另行生产授权）。
- 应用顺序与独立性：先 `RUNBOOK-launch.28q-supplement.patch` 再本补丁；`git apply --check` 对「HEAD 6af59be + supplement」实测通过；不覆盖 supplement 任何 hunk、不覆盖在途工作树改动（SITEMAP 变量 / 0.1 探针与包装 / merge-i18n 哈希备注）。

### 13.5 未决审批项（在 §11/§12 之上追加）

- ⑤ supplement 的 0.1 判据 hunk 与在途 0.1 探针/包装改动的冲突合并口径（谁先落地、以哪个为准）；
- ⑥ 演练内容复位（附录 D.1）由演练负责人操作，本链不代执行；复位未修复前，任何一轮的第 3/9 步不得判 PASS；
- ⑦ 第二遍中断（16:58 UTC 卡死）与 17:04:32 UTC 重启由并行链执行，其归属与重启后的轮次计数（是否仍算「第二遍」）需负责人确认；
- ⑧ 本补丁落地与三轮重跑 / Claude 核验 / 另行生产授权四边界仍待负责人逐项批复（§11 审批文本继续有效）。

### 13.6 本轮操作与边界

- 仅只读命令：Get-Content/Select-String/Get-ChildItem/Get-Item/Get-FileHash、git show/diff/log/status、git apply --check（在 i667 工作目录 scratch 副本上验证补丁，未触碰仓库工作树）、docker ps -a、docker logs（只读）。未执行任何脚本、未启动/停止/接入任何容器、未修改/删除任何他人文件（含 .tmp\28q 全部在途文件、全部未跟踪文件与工作树 3 处改动）、未 SSH 生产、未重跑生产第 1 步、未执行第 2–8 步、未读取或打印任何密码/密钥/token（编排文件中的本地演练口令字段仅确认存在，不记录不打印）。
- 本轮仓库变更：新增 `deploy/verification-28q/RUNBOOK-launch.28q-supplement2.patch` + 本节日志追加；只暂存并提交这两个文件并 push，其余改动与未跟踪文件保持原样。

## 14 · 28q 第三/四遍演练逐项核查结论落盘（fact #673；2026-10-28 本机只读核查；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未读任何凭据）

### 14.1 第三遍（17:04:32 UTC 重启后，本地 10:07:30 起）逐项判定

- 缺陷 1（`/pkg/prep-restore.php` 缺失 → 内容未复位）复现：reset.log 第 5 步仍两行 `Error: '/pkg/prep-restore.php' does not exist.`，DB 内容层（27g/27i 行）仍未复位，与第一/二遍一致；该文件始终未进 /pkg。
- step3 首轮 `apply_updated=0` 复现（17:12:46 起，exit=0）：`apply_updated=0 already_current=134 URL_and_body_check=PASS`（27g）、`apply_updated=0 already_current=8`（27i）——种子库仍是上线后值，按 supplement2 D.2.3「首轮 apply_updated>0」判据本轮无效；rehearsal.sh 无 3.0 守卫，step3 仍 exit=0 静默。
- 旧缺陷三项未复现：TLS 2026——step1 `wp db export` Success、backup/db.sql=32384985 B（--skip-ssl 包装仍属容器态遗留，rehearsal.sh step0 无探针/包装）；paths.json——step2 合并 146（生产 144+本地 146→146）、`*-dict.json`=11、`*-js.json`=11、paths ok（conflicts.json=[] 触发校验循环假阳性，非硬失败）；docker cp 卡死——step2 正常推进未卡。
- 后置快照卡死：s-forward1.txt.raw 17:18:44 起卡在「== 插件 ==」后的 `wp plugin list`（≥4.5 分钟无新输出，仅 75 行 vs 基线 845 行），rollback.log 保持 0 字节 → run-all 未到第 9 步；并行链于 17:23:42 UTC 截断三份日志重启（第四遍）。

### 14.2 第四遍（本地 10:23:42 = 17:23:42 UTC 重启，进行中）

- 内容复位修复（并行链自有方案，非 supplement2 附录 D.1）：并行链自写 `prep-restore-slug.php`（按 slug 写回依据里的旧值）并重写 reset.sh（10:23:40）；reset.log 第 6 步实测 `restore_slug=130 missing=0 无正文依据=0`（27g）、`restore_slug=8 missing=0 无正文依据=8`（27i）→ 内容复位真实成功。
- step3 首轮真实写入成立（17:31:41 起，exit=0）：`apply_updated=130 already_current=4 URL_and_body_check=PASS`（27g）、`apply_updated=7 already_current=1 H1_body_URL_check=PASS`（27i）——首轮 apply 真实改写 130/7 行，满足「首轮 apply_updated>0」语义（该语义出自拟制 supplement2 附录 D，尚未经负责人批准落地）。
- 状态：第四遍仍在进行（forward1.log 10:36:36 仍在增长；forward2.log / rollback.log 0 字节）→ 未完成，不得判 PASS。

### 14.3 补丁/脚本/五件套现状（截至本地 10:37:23 UTC-07）

- supplement2 仍未落地：RUNBOOK 工作树 diff（30+/5−）为并行链在途改动，不含 2.5 硬 FAIL / 3.0 守卫 / prep-restore / 附录 D 字样；`RUNBOOK-launch.28q-supplement2.patch` 仍为未跟踪拟制件。
- rehearsal.sh（9:29:37 起未变，12502 B）仍无 2.5 硬 FAIL、3.0 应用前守卫、step0 探针（--skip-ssl 包装/导出探针仍为容器态遗留）。
- 五件套仍全部缺失：`rehearsal.log`、`rehearsal-verify.json`、三轮整轮 PASS、Claude 核验、新生产授权 → 生产第 1 步仍 FAIL，第 2–8 步与 SSH 生产继续禁止。

### 14.4 未决审批项⑤–⑧最新状态（在 §13.5 之上更新）

- ⑤ 0.1 判据 hunk 与在途 0.1 探针/包装改动的冲突合并口径：仍未决；RUNBOOK 工作树在途改动未提交、supplement2 未落地。
- ⑥ 演练内容复位：并行链已用自有 slug 方案在第四遍实测修复（restore_slug=130/8 missing=0），但 supplement2 附录 D.1（prep-restore.php 进 /pkg）未落地；第 3/9 步判 PASS 须在负责人批准的落地版本上重新验证。
- ⑦ 重启归属与轮次计数：并行链于 17:23:42 UTC 再次截断重启（现第四遍），其归属与轮次计数口径仍待负责人确认。
- ⑧ 四边界批复（补丁落地/三轮重跑/Claude 核验/另行生产授权）：仍无新批复；§11 审批文本继续有效。

### 14.5 本轮操作与边界

- 仅只读命令（Get-Content/Select-String/Get-Item/Get-ChildItem/git status/log/diff/ls-files）+ 本节日志追加 + 复制本链证据摘录文件入库；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件（工作树 3 处改动与 31 个未跟踪文件保持原样）、未 SSH 生产、未重跑生产第 1 步、未执行第 2–8 步、未读取或打印任何密码/密钥/token。
- 本轮仓库变更：`deploy/LAUNCH-LOG-2026-10-28.md` 追加本节 + 新增 `deploy/verification-28q/round3-evidence-summary.md`（意图 672 所写独立只读证据摘录，原样入库未改动）；只暂存并提交这两个文件并 push。

## 15 · 28q 第四遍演练完整结束核查与五件套最新状态（2026-10-28 本机只读核查；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未读任何凭据）

### 15.1 第四遍是否真正结束
- 第四遍由并行链以新 runner 容器 `r28q-runner`（镜像 r28q-host:3，Cmd=`/opt/x28q/run-all.sh`，挂载 .tmp\28q\rehearsal\home\web → /home/web）于本地 10:23:42（17:23:42 UTC）启动，17:54:55 UTC `DONE`，容器 `exited exit=0`。**未被截断、真正结束**（前三次分别被并行链 17:04:32 UTC 重启、step2 docker cp 卡死、s-forward 快照卡死截断）。
- 六阶段序列（docker logs r28q-runner + rehearsal-28q/ 日志目录实读，全部只读）：
  1) 复位到首装前（reset.log 10:26:41）：siteurl=http://nginx；i18n 23 文件；`子主题未安装 ✓`；`报价插件未安装 ✓`；`get-a-quote 301（期望 301）`；`首页 200（期望 200）`。
  2) 基线快照 s-baseline.txt：845 行，sha256 `4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb`。
  3) 正向 0-8（forward1.log 10:36:36，471 行）：step0–step8 全部 `exit=0`；step3 `apply_updated=130 already_current=4 URL_and_body_check=PASS`、`apply_updated=7 already_current=1 H1_body_URL_check=PASS`；step4/5 安装+激活成功；step8 `sitemap 未变`、`robots 未变`、inquiry 200；快照 s-forward1.txt：847 行，sha256 `4247693fa3a31f35477dcb059ad79e4ae5c13de861f94dfcc3848fe57f81b392`。
  4) 整体回滚 9（rollback.log 10:41:27，93 行）：`exit=0`；mu `sha256sum -c` 两项 OK、主题回切 static-bridge、home-v4 移除、quote 插件 deactivate+delete；27g `rollback_updated=130 already_current=4 URL_and_body_check=PASS`、27i `rollback_updated=7 already_current=1 H1_body_URL_check=PASS`；s-after-rollback.txt 845 行，sha256 `4972cd81…3deb` **与基线 IDENTICAL ✓**。
  5) 再正向 1-8（forward2.log 10:53:02，411 行）：step1–step8 全部 `exit=0`；**step3 再次 `apply_updated=130/4`、`apply_updated=7/1` 真实写入复现** → 第四遍内 reset/回滚的内容层复位持续成立、可重复；s-forward2.txt 847 行，sha256 `4247693f…b392` **与 s-forward1 IDENTICAL ✓**。
  6) 对比（diff.txt 10:54:55）：`回滚后 vs 基线 IDENTICAL ✓`、`第二次正向 vs 第一次正向 IDENTICAL ✓`。
- 结论：第四遍自身全部判据（reset/forward/rollback/快照一致性）逐项 exit=0 且快照一致；`prep-restore-slug.php` 的 `restore_slug=130/8 missing=0` 与 step3/step9 的 apply/rollback_updated=130/7 真实写入在整遍内持续成立。**但第四遍完成 ≠ 三轮整轮 PASS**（见 15.4）。

### 15.2 第四遍结构注意点（口径项，需负责人确认）
- 第四遍 = 复位 + 正向0-8 + 回滚9 + 再次正向1-8（**2 次正向 + 1 次回滚**）；未见第 3 轮完整循环。任务单 §3“三轮演练”口径是否由该结构满足，仍需负责人确认（原 §13.5⑦ 延续）。
- reset 第 6 步 27i 输出 `restore_slug=8 missing=0 无正文依据=8`：27i 回滚证据只存正文哈希、不存正文，8 个主页面正文未按 slug 写回（27g 130 页已真实写回）；该缺口由第 3/9 步 import.php 的 `H1_body_URL_check=PASS` 间接覆盖，reset 层语义建议列入负责人确认项。
- 0.1 导出探针为演练结束后的**补跑**（probe.log 10:56:50，非 forward1 step0 内）：裸探针复现 `TLS/SSL error 2026` → /tmp/probe.sql=0 字节；按 RUNBOOK 包装 `--skip-ssl`（追加参数末尾）后导出 `Success` 36,381,501 B / 40 CREATE TABLE → 探针正反路径实测成立；但 `--skip-ssl` 包装仍为容器态遗留，rehearsal.sh 自身仍无 step0 探针/包装步骤。

### 15.3 supplement2 / rehearsal.sh 现状（截至本地 10:57）
- supplement2（`deploy/verification-28q/RUNBOOK-launch.28q-supplement2.patch`，本链拟制）仍为未跟踪拟制文件：**未按原样、也未经负责人批准合并**。工作树 RUNBOOK 在途改动（并行链、未提交，+40/−5 量级）仍只含 SITEMAP 变量 / 0.1 导出探针+mariadb `--skip-ssl` 包装指引 / merge-i18n 哈希备注，**不含 2.5 硬 FAIL、3.0 应用前守卫、附录 D 复位判据**。
- rehearsal.sh（9:29:37 起未变，12502 B）：仍无 2.5 硬 FAIL、无 3.0 守卫、无 step0 探针；第四遍实际由容器内 `/opt/x28q/run-all.sh` 驱动（rehearsal.sh 未参与本轮）。

### 15.4 五件套最新状态（发生变化的项）
- **rehearsal.log：10:57:00 起存在**（`deploy/verification-28q/`，69090 B，并行链生成，未跟踪未提交；内容为第四遍各阶段日志汇总）。
- **rehearsal-verify.json：10:57:00 起存在**（6646 B，未跟踪未提交；generatedAt 2026-10-28T17:57:00.019Z；含 5 阶段 step 级 exit=0、判据行、4 快照哈希与两项 IDENTICAL；**无“整轮/三轮 PASS”结论字段**）。
- 三轮整轮 PASS：**仍不存在**（第四遍完成 ≠ 三轮完成；verify.json 无三轮判定；轮次口径待负责人确认）。
- Claude 核验：**仍不存在**（全盘搜索无任何核验产物/记录）。
- 新生产授权：**仍不存在**（remote main == 0070926 无新提交；DSH-TASK-2026-10-28q.md mtime 8:48:45 未变；无 DSH-RESULT-2026-10-28q.md）。
- 门禁结论：进入本地演练（本链）/ Claude 核验 / 生产第 1 步重跑的前置仍不满足 → 生产第 1 步仍 FAIL；第 2–8 步与 SSH 生产继续禁止；本链继续停止等待。

### 15.5 未决审批项⑤–⑧最新状态（在 §14.4 之上更新）
- ⑤ 0.1 判据 hunk 与在途 0.1 探针/包装改动的冲突合并口径：仍未决；在途改动未提交、supplement2 未落地、supplement 的 0.1 判据 hunk 冲突未合并（§13.3）。
- ⑥ 演练内容复位：第四遍用并行链自有 slug 方案实测成立（restore_slug=130/8 missing=0，且第二正向 apply_updated 复现真实写入）；supplement2 附录 D.1（prep-restore.php 进 /pkg）未落地；27i“无正文依据=8”口径仍待负责人；第 3/9 步判 PASS 须在负责人批准的落地版本上重新验证。
- ⑦ 重启归属与轮次计数：第四遍（17:23:42–17:54:55 UTC）未再截断、完整结束；但“三轮”计数口径（第四遍内 2 正向 + 1 回滚 vs 三轮完整循环）仍待负责人确认。
- ⑧ 四边界批复（补丁落地 / 三轮重跑 / Claude 核验 / 另行生产授权）：仍无新批复；§11 审批文本继续有效。

### 15.6 本轮操作与边界
- 只读命令：Get-ChildItem / Get-Content / Select-String / Get-Item / git status / git log / git ls-remote、docker ps / docker logs / docker inspect（全部只读）。未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未 SSH 生产、未重跑生产第 1 步、未执行第 2–8 步、未读取或打印任何密码/密钥/token。
- 本轮仓库变更：`deploy/LAUNCH-LOG-2026-10-28.md` 追加本节 + 新增 `deploy/verification-28q/round4-evidence-summary.md`（本链独立只读证据摘录）；只暂存并提交这两个文件并 push。并行链工作树 3 处修改（README.md / RUNBOOK-launch.md / tools/merge-i18n.php）与 33 个未跟踪文件（含并行链新生成的 rehearsal.log、rehearsal-verify.json 及本链拟制补丁等）保持零触碰。

## 16 · 28q 增量门禁核查：cad8c62 之后的新提交 / 任务单 / 产物与四前置判定（2026-10-28 本机只读核查；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未读任何凭据）

### 16.1 增量范围（cad8c62..HEAD，仅 3 文件）
- `e68b87a`（10:59:49）：`.gitignore` 加白名单 `!deploy/verification-28q/rehearsal.log`，`deploy/verification-28q/rehearsal.log`（69,090 B / 1063 行）正式入库。
- `b155b40`（11:00:11）：`dsh-out/DSH-RESULT-2026-10-28q.md` 追加 2 行——27e 补丁只打在本地 mu 副本（原文件留档 `.tmp\28q\backup\packrivo-live\mu-plugins\`，sha256 `abe6f134…`），生产那份没有动。
- 任务单新答复：**无**。`packrivo-v2/docs/` 零变更，无新任务单（无 28r）。
- 新产物：仅 rehearsal.log 入库，内容与 §15 已核验的第四遍一致；工作区干净、无新增未跟踪文件。

### 16.2 三轮整轮 PASS 判定（严格口径）
- 新入库 log 的阶段结构不变：复位 → **正向 0-8** → **回滚 9** → **再正向 1-8** → 0.1 探针补跑，即 **2 次正向 + 1 次回滚**；无第 2 次整体回滚、无第 3 个完整循环。按「三轮整轮（每轮含回滚）」口径**仍不成立，不得以 2 次正向+1 次回滚替代**。
- 单遍判据属实（可复核）：18 个步骤块全部 exit=0；`apply_updated=130 already_current=4` + `apply_updated=7 already_current=1` 真实写入；回滚后快照==基线（`4972cd81…3deb`）IDENTICAL；再正向后==首正向（`4247693f…b392`）IDENTICAL；探针补跑：裸报 `TLS/SSL error 2026`→0 字节，`--skip-ssl` 包装后 36,381,501 B / 40 表。
- RUNBOOK 附录 C 的「三轮结果（正向/回滚/再正向）」为并行链 cad8c62 自改口径，且该次 RUNBOOK 重写未经 supplement2 或审批流程落地——「符合 RUNBOOK 口径的三轮整轮 PASS」仍缺；轮次计数口径仍待负责人确认（审批项⑦）。
- **新发现（轮内 step0 未跑探针）**：forward1 的 step0 止于「STEP 0.2 上传清单核对」即 `exit=0`，**未执行 §0.1 导出探针**；轮内 step1 db export 直接 Success，依赖容器上一轮遗留的 `--skip-ssl` 包装（阶段 4 补跑先拆包装、裸探针复现 TLS 2026→0 字节证明）。与 supplement2 D.2.1「每轮先跑 0.1 探针、不得依赖上次遗留包装」不符；探针仅轮后补跑。

### 16.3 Claude 核验 / 新生产授权
- Claude 核验：**仍不存在**。全仓库（含 150 行 DSH-RESULT-2026-10-28q.md）无任何 28q 演练核验结论；任务单 28q 答复 3 仍为唯一口径「演练通过、Claude 核验之后，才会再授权上生产」。
- 新生产授权：**仍不存在**。cad8c62..HEAD 仅 3 文件变更，无任何新批复/授权记录。

### 16.4 supplement2 / rehearsal.sh 守卫落地核查
- supplement2（本链拟制）**未落地、未获批准**：HEAD RUNBOOK 无「2.5 硬 FAIL」、无「3.0 应用前守卫」、无「9.3 回滚前 site 守卫」、无「附录 D」（`grep -E "守卫|FAIL|3\.0|附录 D"` 零命中）。现有 §0.1 探针、2.5 校验行、9.3 rollback-check 为并行链 cad8c62 自写近似，均无硬 FAIL 语义。
- `scripts/rehearsal.sh`（自 cad8c62 后未变，256 行）：首行 `set -u` 无 `set -e`；无 `exit 1`/`FAIL` 硬判；step0 无探针；step9 仅原 RUNBOOK 的两条 rollback-check；探针独立在 `scripts/step0-probe.sh`，本轮作为「阶段 4 补跑」在轮次之外执行。

### 16.5 门禁结论
- 四前置——①严格口径三轮整轮 PASS、②真实 Claude 核验结论、③负责人新的生产授权、④supplement2 与 rehearsal.sh 守卫按批准落地——**全部仍缺**。
- 因此：**生产第 1 步重跑条件不满足**，生产第 1 步维持 FAIL；**第 2–8 步与 SSH 生产继续禁止**；本链继续停止等待。
- 未决审批项⑤–⑧状态无变化；⑥新增口径点：轮内 step0 未跑 0.1 探针、step1 依赖容器遗留 `--skip-ssl` 包装，需并入口径确认。

### 16.6 本轮操作与边界
- 只读命令：`git log/show/diff/ls-files/grep/status` + 文件读取（含 rehearsal.log、rehearsal-verify.json、RUNBOOK、rehearsal.sh、DSH-RESULT/DSH-TASK）。未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未 SSH 生产、未重跑生产第 1 步、未执行第 2–8 步、未读取或打印任何密码/密钥/token。
- 本轮仓库变更：仅 `deploy/LAUNCH-LOG-2026-10-28.md` 追加本节 + 新增 `deploy/verification-28q/gate-evidence-2026-10-28-after-cad8c62.md`（本链独立只读证据摘录）；只暂存这两个文件、commit 并 push。在途改动与未跟踪文件零触碰（当前工作区本身干净）。

### 16.7 更正与补充：bd17806（11:10:02，28r 任务单）带来的新证据（16.1–16.5 为 bd17806 之前的结论，其中「Claude 核验仍不存在/无 28r」已被本节更正）
- 新提交 `bd17806`（2026-10-28 11:10:02，并行链）仅新增 `packrivo-v2/docs/DSH-TASK-2026-10-28r.md`（70 行，任务单 28r「补演练第 9 步最后手段（整库导入）」）。本链全程未执行其任何指令（见 16.6/16.7 边界）。
- **真实 Claude 核验结论已出现（更正 16.3）**：28r「背景」节引述 Claude 对 28q 的核验 = **「基本通过」**——「哈希、版本、四份快照都独立核对过；在容器里重跑快照，结果 `4247693f…` 与正向一致」，但列**三处必改**：
  1. 最后手段未演练：第 9 步末尾 `docker cp db.sql → db import` 一次都没跑过、`reset.sh` 也不导入数据库，而 28q 结果文档 §6 写的「db import 成功」**不属实**；
  2. `exit=0` 无判别力：`rehearsal.sh` 每步最后一条都是 `set +x`，exit 恒为 0，「18 块全 exit=0」证明不了任何事；
  3. `--skip-ssl` 包装可能不在位：容器重建即丢，到用最后手段时包装在不在没人验证过（与本链 16.2 新发现互为印证）。
  → Claude 核验为**有条件结论**：基本通过但三项缺陷待修，故负责人未授权生产，而是新开 28r 继续本地补演练/改文件。
- **新生产授权：仍不存在（维持 16.3 判定）**：28r 明确「本轮不碰生产：不 SSH，不碰密码」，只允许单线程在本地 r28q-*/php/mysql/nginx 容器与 .tmp/28q 上动手，交付 `DSH-RESULT-2026-10-28r.md` + `deploy/verification-28r/lastresort.log`。
- **三轮整轮 PASS：仍不成立（16.2 判定强化）**：Claude 核验反而坐实「exit=0 无判别力」与「db import 成功不属实」，即 28q 单遍演练的判据体系本身被否定；结构仍是 2 次正向+1 次回滚、无三轮完整循环；28r §3 要求验收「每条判据打印 PASS/FAIL，不能只靠 exit 码」重验。严格口径三轮整轮 PASS 依旧不存在。
- **supplement2 / rehearsal.sh 守卫：仍未落地（维持 16.4）**，但方向已获负责人背书：28r §4.4 要求 rehearsal.sh「每个 step 结束时统计本步 FAIL 的数量，有 FAIL 就 exit 1；或者至少不要再用 exit 码当证据（二选一）」——与 supplement2 的 2.5 硬 FAIL 同向；仓库 HEAD 尚未落地。28r §4.1 还要求 RUNBOOK 最后手段加「先重跑 0.1 探针确认包装在位」的前置（与 supplement2 D.2.1 同向）。
- **门禁结论（修正后）**：四前置仍全缺——①严格口径三轮整轮 PASS 不存在（且 28q 判据体系被 Claude 否定）；②Claude 核验为「基本通过+三项必改」的有条件结论、未到「通过后另行授权」；③新生产授权不存在（28r 仍禁生产）；④supplement2/rehearsal.sh 硬判守卫未落地（仅获任务单背书）。**生产第 1 步重跑条件不满足，生产第 1 步维持 FAIL，第 2–8 步与 SSH 生产继续禁止，本链继续停止等待。**
- 未决审批项更新：⑧ 四边界批复变为「28r 本地补演练/改文件获批、生产授权仍未批」；⑥/⑦ 口径点（轮内 step0 未跑探针、2 正向+1 回滚计数）在 28r 中仍未被负责人直接裁定，继续保留。
- 16.7 操作边界：只读 `git log/show/fetch/status` 与文件读取（DSH-TASK-2026-10-28r.md）；未执行 28r 任何指令、未启动/停止/接入容器、未修改/删除他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。本轮仓库变更仍仅 LAUNCH-LOG 本节追加 + 证据摘录文件补充 28r 段，其余零触碰。

## 17 · 28q/28r 增量门禁核查：cfd4bd7 之后的新提交 / 28r 执行进展 / Claude 三项必改与 supplement2 落地 / 审批项⑥⑦裁定（2026-10-28 本机只读核查；未执行任何脚本、未启动/停止/接入容器、未修改/删除他人文件、未读任何凭据）

### 17.1 增量范围与任务单答复（cfd4bd7..HEAD）
- `git fetch origin` 后 `origin/main == HEAD == cfd4bd7`：**零新提交**；`git status` 干净（porcelain=0）、无未跟踪文件；全仓 11:12:10 之后无任何文件变更。
- `packrivo-v2/docs/` 最新仍为 DSH-TASK-2026-10-28r.md（11:10:01，bd17806）：**无 28r 答复、无新任务单**。
- 28r 交付物均不存在：`dsh-out/DSH-RESULT-2026-10-28r.md` 无、`deploy/verification-28r/` 目录无（lastresort.log 无）→ 28r 尚未执行。

### 17.2 Claude 三项必改落地核查（全部：未落地）
1. **最后手段 db import 真实演练**：未执行（无 verification-28r 产物、无 DSH-RESULT-28r、无导入前/后/文件回滚三快照）。RUNBOOK L552–558 仍为裸 3 命令块，无 28r §4.1 要求的前置探针、文件部分顺序说明、transient/缓存清理与判据。
2. **rehearsal.sh 可判别 FAIL/exit 1 硬守卫**：未改。`deploy/verification-28q/scripts/rehearsal.sh`（271 行，mtime 09:29:37，cad8c62 入库版，HEAD 与工作树一致）每步块仍以 `set +x` 收尾（Claude 指出的 exit 恒 0 结构未变）；全文唯一 `exit` 是 L268 用法错误的 `exit 2`；无每步 FAIL 计数、无 FAIL→exit 1。全文无 'skip-ssl'、无 'probe'。
3. **每轮 §0.1 --skip-ssl 探针且不依赖容器遗留包装**：未落地。RUNBOOK-launch.md（mtime 10:57:16）无变更；附录 C（L587–597）仍「已跑通」+「全部 exit=0」口径（28r §4.2 要求改为「以判据行为准」未做）；rehearsal-verify.json 仍为「阶段 4 轮后补跑探针」结构（file: probe.log），`scripts/step0-probe.sh`（10:48:14）仍是容器内 /usr/local/bin 安装 --skip-ssl 包装的补跑模式，非每轮轮内自检自装。

### 17.3 supplement2（2.5/3.0/9.3/附录 D）批准与落地核查
- **落地：未落地**。RUNBOOK 无「附录 D」（仅附录 A/B/C）；2.5（L231–236）为合并校验打印（失败被 `|| echo` 吞掉、无步骤中止）；无 3.0 预 apply 回滚守卫、无 9.3 预回滚 site 守卫（L536 的 9.3 命中为 RUNBOOK 自身内容回滚节，非 supplement2 的 9.3 守卫）。
- **批准：补丁本身无批准记录**。28r 任务单以独立措辞背书同向要求（28r §4.4 rehearsal.sh FAIL 计数+exit 1 ↔ supplement2 2.5 硬 FAIL；28r §4.1 最后手段 0.1 前置探针 ↔ supplement2 D.2.1）→ 方向获负责人背书，补丁形式未获批。

### 17.4 三轮完整整轮 PASS（严格 RUNBOOK 口径）：不成立
- rehearsal-verify.json 阶段结构 = 复位 / 正向 0–8（9 块）/ 回滚 9 / 再正向 1–8（8 块）/ 轮后补探针 = **2 次正向+1 次回滚**，无三轮完整循环（不得以 2 正向+1 回滚替代、不得以恒 exit=0 作证据）。
- Claude 已否定「18 块全 exit=0」证据体系；28r §3 要求逐条判据打印 PASS/FAIL 的重验尚未发生。
- DSH-RESULT-2026-10-28q.md L10 仍写「三轮 18 个步骤块全部 exit=0」、L98 仍保留「`db import` 成功」（28r §4.3 要求删除改写，未做）。

### 17.5 Claude 修正后复验结论 / 新生产授权
- **修正后复验结论：不存在**（28r 尚未执行，无对修正结果的二次核验）。
- **新生产授权：不存在**。28r 明确「本轮不碰生产：不 SSH，不碰密码」；仓库无任何新授权记录。

### 17.6 审批项⑥⑦口径裁定
- ⑥（轮内 step0 未跑 0.1 探针、step1 db export 依赖容器遗留 --skip-ssl 包装、探针轮后补跑）：负责人仍未直接裁定；28r §3/§4.1 以「包装不在位没人验证过」同向背书，待 28r 结果落地后再议。
- ⑦（轮次计数口径：2 正向+1 回滚 vs 三轮完整循环）：负责人仍未直接裁定，继续保留未决。
- ⑧ 维持「28r 本地补演练/改文件获批、生产授权仍未批」。

### 17.7 门禁结论与操作边界
- 四前置仍全缺：①严格口径三轮整轮 PASS 不存在（且 28q 判据体系被 Claude 否定）；②Claude 修正后复验结论不存在；③新生产授权不存在（28r 仍禁生产）；④Claude 三项必改与 supplement2 守卫均未落地（仅获 28r 同向背书）。**生产第 1 步重跑条件不满足，生产第 1 步维持 FAIL，第 2–8 步与 SSH 生产继续禁止，本链继续停止等待，绝不执行第 2–8 步。**
- 操作边界：只读 `git fetch/log/show/status` 与文件读取（DSH-TASK-2026-10-28r.md、rehearsal.sh、step0-probe.sh、check-0.1.rehearsal.sh、RUNBOOK-launch.md、DSH-RESULT-2026-10-28q.md、rehearsal-verify.json）；未执行 28r 任何指令、未启动/停止/接入容器、未修改/删除他人文件、未 SSH 生产、未读取或打印任何密码/密钥/token。本轮仓库变更仅本日志 §17 + 证据摘录文件 `deploy/verification-28q/gate-evidence-2026-10-28-after-cfd4bd7.md` 两个文件，其余零触碰。