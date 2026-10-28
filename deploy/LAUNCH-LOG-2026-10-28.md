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