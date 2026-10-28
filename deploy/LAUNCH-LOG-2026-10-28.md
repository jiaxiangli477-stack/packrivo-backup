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
