# 上线手册 · Packrivo 一次性上线（RUNBOOK�?
> **上线以本文件为准。`deploy/README.md` 里按轮次堆的 27g�?8q 记录只作历史参考，命令和顺序以这里为唯一口径�?*
> 本文件不含任何密码、密钥或 token：wp-cli 走服务器上已有的 SSH/容器配置，Cloudflare 清缓存走本机 `E:\破甲工作区\i18n\.cf.env`（只引用路径，不抄值）�?>
> **28q 改版要点**（相�?28o）：生产站跑�?Docker 里，本手册所有命令都标了「宿主机 / 容器内」；`$BACKUP` �?1 步定一次、写�?`CURRENT_LAUNCH`；备份路径与回滚路径一一对应；第 4�? 步按**首次安装**写（生产上子主题与报价插件都还不存在）；数据库备份落�?`$BACKUP/db.sql`�?>
> **首装已于 2026-10-29 完成。本手册是「首装手册」，不是升级手册�?*
> 那次上线的生�?ID、备份目录与逐步实测：`deploy/LAUNCH-LOG-2026-10-28.md` §37–�?0、`dsh-out/DSH-RESULT-2026-10-28-prod.md`。要点：`CURRENT_LAUNCH = packrivo-launch-20261029-0335`，`/get-a-quote/` �?ID = **3383**（本手册 4.5 建�?.8 删），两�?mu 补丁打后哈希 `887787ab…` / `f7d95f35…`�?> **以后改主题或插件不要重跑本手�?*：第 0 步「子主题/报价插件未安装」两条判据在已上线的生产上必然不成立，第 4/5 步的 `--force` 覆盖安装�?4.5 建页也不该再走一遍。升级另写升级手册（只覆盖装�?+ 打补�?+ 复测）�?
- 目标站点：`https://www.packrivo.com`�?*WordPress 7.1.4** + 父主�?`packrivo-static-bridge-1.2.38` + 子主�?`packrivo-home-v4` + 报价插件 `packrivo-quote` + mu-plugins `packrivo-i18n.php`、`packrivo-site-remediation-20261022.php`�?- 上线前生产现状（2026-10-28 实测）：活跃主题是父主题本身�?*子主题与报价插件都未安装**，`/get-a-quote/` �?mu 里那条老别�?301 �?`/inquiry/`
- 本轮上线内容�?34 个产品页新标�?描述�?7g）�? 个主页面�?7i）�?1 语种字典与路径表�?7g/27i/28k/28m/28n/28o）、报价插件与子主题新包、两�?mu 补丁
- 预计总耗时�?*1.5�? 小时**（含复测），建议�?*访问量低**的时段做（北京时�?02:00�?5:00 或周末）
- 每一步都能单独回滚；�?9 步是整体逆序回滚
- 任一步失败就停，按该步「回滚」恢复后再继续；不要跳过判据硬往前做

---

## 0 · 先读这里：宿主机 �?容器

生产站不在宿主机上直接跑，而是 **Docker**：`nginx`（`nginx:alpine`�? `php`（`kjlion/php:fpm-alpine`，PHP 8.3.31�? `mysql` + `redis`。宿主机�?*没有** `php`�?*没有** `wp`。所以：

| 东西 | 宿主机（SSH 进来的那�?bash�?| 容器 `php` �?|
|---|---|---|
| WordPress �?| `/home/web/html/www.packrivo.com/wordpress`（`$WP`�?| `/var/www/html/www.packrivo.com/wordpress`（`$WPC`�?|
| i18n 字典 | `$WP/wp-content/uploads/packrivo-i18n`（`$I18N`�?| `$WPC/wp-content/uploads/packrivo-i18n` |
| 上传的包 | `/home/web/packrivo-packages`（`$PKG`�?| `/pkg`（`$PKGC`，由 `docker cp` 灌进去） |
| 备份 | `/home/web/backups/<LAUNCH>`（`$BACKUP`�?| **不可�?*（需要时�?`docker cp` 进去/出来�?|

三条硬规则：

1. **`wp` 只能在容器里�?*，且容器 `disable_functions` �?`proc_open`，必须用 `-d disable_functions=` 覆盖，否则任何要起子进程的命令（`db export`、`plugin install`…）都会 `Cannot do 'Process::run'`�?2. **`php` 也只在容器里**（`php -l`、`merge-i18n.php`）。宿主机上的 `php` 一律写�?`docker exec php php …`�?3. **文件路径要看是谁在读�?*：宿主机侧的 `cp/ls/sha256sum/curl` �?`$WP`、`$PKG`、`$BACKUP`；容器侧�?`wp` 参数�?`$WPC`、`$PKGC`。`wp` **写出来的文件**（db 导出、rollback JSON）落在容器里，必�?`docker cp` 出来存进 `$BACKUP`�?
### 变量（每开一�?SSH 会话都先执行这一块）

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
PKGC=/pkg
SITE=https://www.packrivo.com
SITEMAP=/sitemap_index.xml    # Yoast 站点地图；若站点只用 WP 核心地图就改�?/wp-sitemap.xml
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
# $BACKUP 不在这里生成：第 1 步定一次，之后永远从文件读
```

### 0.1 上线前检查（只读�?
> **生产数据库是 MySQL，不�?MariaDB�?* 2026-10-29 那次实测：`mysql` 容器�?**MySQL Community Server 26.7.0**，账号认证插件是 **`caching_sha2_password`**；`php` 容器里的客户端是 `mariadb-client/mariadb-dump 11.4.13`，它的认证插件目�?`/usr/lib/mariadb/plugin/` 初始为空。这两件事各会产生一次报错，**导出探针要按下面的两层顺序处�?*，两层都过完探针才可能有字节�?> 演练环境�?29v 起也�?MySQL 官方镜像（`mysql:8.4`，本机实�?8.4.13�? `caching_sha2_password` + 自签证书 TLS，就是为了在本地先把这两层跑出来。版本号与生产不同（26.7.0 vs 8.4.13），但两种报错与改法一致�?

```bash
# 容器与版�?docker ps --format '{{.Names}}\t{{.Image}}' | grep -E '^(php|mysql|nginx|redis)\b'
$WPCLI core version                         # 期望 7.1.4（生产开着 WP 小版本自动更新，见下方说明）
docker exec php php -v | head -1            # 期望 PHP 8.3.x
$WPCLI option get siteurl                   # 期望 https://www.packrivo.com
$WPCLI option get template                  # 期望 packrivo-static-bridge-1.2.38
$WPCLI option get stylesheet                # 期望 与上面相同（子主题未装）

# 首次安装的现状必须确认（这两�?greps 都要「没输出」）
$WPCLI theme list  --format=csv | grep '^packrivo-home-v4,' || echo "子主题未安装 �?首次安装"
$WPCLI plugin list --format=csv | grep '^packrivo-quote,'  || echo "报价插件未安�?�?首次安装"
$WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active

# 容器里要�?mysqldump，否�?wp db export 会失败（容器重建后会丢，所以每次上线都查一遍）
docker exec php sh -c 'command -v mysqldump || apk add --no-cache mariadb-client'
docker exec php mysqldump --version

# 数据库导出探针（第一次跑几乎一定失败，这是预期，见下面「两层补救」）�?$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes
docker exec php sh -c 'wc -c /tmp/probe.sql'
docker exec php rm -f /tmp/probe.sql

# 包目�?mkdir -p "$PKG"
```

**通过判据**：`docker ps` 里的 php/mysql/nginx 都在；`core version` = `7.1.4`；PHP = `8.3.x`；`template` = `packrivo-static-bridge-1.2.38`；子主题与报价插件都「未安装」；`mysqldump --version` 有输出；**导出探针三条判据�?PASS**（见下）�?若子主题或报价插�?*已经存在**，说明不是首次安装，**停下**，本手册�?4/5 步与 9 步不适用�?
**导出探针的三条判据（改成三条，全 PASS 才算过）**�?
| # | 判据 | 怎么�?|
|---|---|---|
| 0k-1 | 导出字节�?**> 0** | `docker exec php sh -c 'wc -c < /tmp/probe.sql'` |
| 0k-2 | 导出里的 `CREATE TABLE` 条数 **= 库里 BASE TABLE 的条�?* | 导出：`docker exec php grep -c 'CREATE TABLE' /tmp/probe.sql`；库：`$WPCLI db query "SHOW FULL TABLES" \| grep -c 'BASE TABLE'`（或 `$WPCLI db tables --all-tables-with-prefix \| wc -l`�?|
| 0k-3 | 文件末尾�?`-- Dump completed` | `docker exec php tail -2 /tmp/probe.sql` |

> **0k-2 的写法是 2026-10-29 当场改正的，不要再退回去�?* 当时先用 `wp db query "SELECT COUNT(*) �?information_schema �? --skip-column-names` 取库里的表数�?*这条返回�?*（`wp db query` 对这个调用不吐值），拿空串去比就误判成 FAIL；同�?`SHOW TABLES | wc -l` 会多算一行表头（40 张表�?41）。正确做法：`SHOW FULL TABLES` 只数 `BASE TABLE` 行（去掉表头那一行），或直接�?`wp db tables --all-tables-with-prefix | wc -l`；两边必须一致才算对，口径不一致时先统一口径，不要直接把两个数字放一起比�?
> **生产开着 WordPress 小版本自动更新，执行当天先核版本�?* 2026-10-28 那次上线就是在窗口前�?WP 自动更新�?7.1.3 升到 7.1.4（增量包�?*全深度改�?48 �?core 文件**、`$wp_db_version` 保持 61833）。注�?48 是全深度口径；只 `find -maxdepth 1` �?WP 根目录会数成 8 个，别拿 8 当全量。如果执行当�?`core version` 又不是本节写的值，**�?0 步判 FAIL，不要临场改判据**：先�?28t 的做法在本地用官方包对齐同一版本、完整重跑一轮演练，改完本节期望值再上生产�?
> **导出探针每轮都要重跑，不能靠上一轮留下的包装�?* 包装装在容器 `/usr/local/bin` 里、认证插件装�?`/usr/lib/mariadb/plugin/` 里，容器一重建就都没了�?9v 起演练环境的 `reset.sh` 每次复位都会把这两样拆掉，保证第 0 步真的走一遍「报�?�?补救 �?重探」。探针仍然是 0 字节就当没通过，别往下做�?1 步�?
### 0.1 补救块（两层，按顺序做，不要跳）

**�?1 �?· TLS**：探针报下面两种 `TLS/SSL error` 之一时，做一次容器内的客户端包装再重探�?> - `TLS/SSL error: SSL is required, but the server does not support it` —�?`mysql` 侧没开 TLS、客户端�?mariadb 11.x 时的典型症状�?8q 演练环境就是这种）�?> - `TLS/SSL error: self-signed certificate in certificate chain` —�?`mysql` 侧开�?TLS 但证书是自签/链不全，mariadb 11.x 客户端拒绝�?*生产 2026-10-29 实测就是这句**（MySQL 26.7.0 + 自签证书 + `mariadb-dump 11.4.13`）�?
```bash
docker exec php sh -c 'cat > /usr/local/bin/mariadb-dump <<EOF
#!/bin/sh
exec /usr/bin/mariadb-dump "\$@" --skip-ssl
EOF
chmod +x /usr/local/bin/mariadb-dump'
docker exec php sh -c 'cat > /usr/local/bin/mariadb <<EOF
#!/bin/sh
exec /usr/bin/mariadb "\$@" --skip-ssl
EOF
chmod +x /usr/local/bin/mariadb'
docker exec php sh -c 'command -v mariadb-dump'
# 然后重跑上面的导出探�?```

要点：`--skip-ssl` 必须**追加在参数末�?*，因�?wp-cli 传的 `--no-defaults` 必须保持在第一位，放前面会被拒�?
**�?2 �?· caching_sha2**：装上包装后如果探针还是 0 字节，并且报下面这条，说明缺认证插件（不�?TLS 问题），补装 connector 再重探：

```
mariadb-dump: Got error: 1045: "Plugin caching_sha2_password could not be loaded:
Error loading shared library /usr/lib/mariadb/plugin/caching_sha2_password.so: No such file or directory"
```

```bash
docker exec php sh -c 'ls /usr/lib/mariadb/plugin | wc -l'      # 生产首装时是 0
docker exec php sh -c 'apk add --no-cache mariadb-connector-c'
docker exec php sh -c 'ls /usr/lib/mariadb/plugin'               # 期望 5�? �?.so，含 caching_sha2_password.so
# 然后重跑上面的导出探�?�?走下面三条判�?```

两层都过完，探针仍然 0 字节或报别的�?�?**停下报告**，不要往下做�?1 步。装了什么包、装了哪个版本、插件目录前后各有几个文件，都记进上线日志�?
### 0.2 从本机上传（**本机**执行，只在宿主机落地，不�?web 目录�?
| 文件（本机仓�?`E:\破甲工作区\packrivo-backup\` 下的路径�?| 文件�?| 大小 | SHA-256 |
|---|---|---|---|
| `deploy/packrivo-home-v4.zip` | 38 | 791,053 B | `7d792ad9f4e63025e2fce8a640856710ede5fe3b925e9bdaa372a6002bb945f7` |
| `deploy/packrivo-quote.zip` | 19（`demo/` 不进包） | 54,581 B | `096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93` |
| `deploy/27e-quote-redirect.patch` | �?| 647 B | 见第 6 �?|
| `deploy/patches-28m/packrivo-i18n-404-viewport.patch` | �?| 1018 B | 见第 6 �?|
| `deploy/i18n-merged/`�?1 �?`*-dict.json` + 11 �?`*-js.json` + `paths.json`�?| 23 | �?| 合并底稿 |
| `deploy/tools/merge-i18n.php` | 1 | 13,108 B | �?2 步合并工具；`4316c4c8cdc618cd228d6b54b1af3e462973263149a8127b1bbc01d39be454fa`�?8q 加了 0 字节文件容错�?|
| `dsh-out/batch-01.json` �?`batch-05.json`、`dsh-out/hubs-27i.json` | 6 | �?| �?3 步导入输�?|
| `scripts/product-buyers-27g/import.php` + `lib.php` | 2 | �?| �?3 �?|
| `scripts/product-buyers-27e/full-inventory.csv` + `products.json` + `third.json` + `fourth.json` | 4 | �?| �?3 �?`all134` �?|
| `scripts/hubs-27i/import.php` | 1 | �?| �?3 �?|

```powershell
# 本机：按 RUNBOOK 的目录结构传�?$PKG（不要落�?web 可访问目录）
$P='E:\破甲工作区\packrivo-backup'
scp -r "$P\deploy\packrivo-home-v4.zip" "$P\deploy\packrivo-quote.zip" "$P\deploy\27e-quote-redirect.patch" netcup:/home/web/packrivo-packages/
scp -r "$P\deploy\patches-28m" "$P\deploy\i18n-merged" "$P\deploy\tools" netcup:/home/web/packrivo-packages/
# 6 �?JSON 必须落进 $PKG/dsh-out/（第 3 步的脚本�?<包根>/dsh-out/ 读，落包根会 Cannot read /pkg/dsh-out/batch-01.json�?scp -r "$P\dsh-out\batch-01.json" "$P\dsh-out\batch-02.json" "$P\dsh-out\batch-03.json" "$P\dsh-out\batch-04.json" "$P\dsh-out\batch-05.json" "$P\dsh-out\hubs-27i.json" netcup:/home/web/packrivo-packages/dsh-out/
scp -r "$P\scripts\product-buyers-27g" "$P\scripts\product-buyers-27e" "$P\scripts\hubs-27i" netcup:/home/web/packrivo-packages/scripts/
```

> **落点清单�?026-10-29 上线时踩过一次，照这个对�?*�?> - `$PKG/*.zip`、`$PKG/27e-quote-redirect.patch` —�?包根
> - `$PKG/patches-28m/`、`$PKG/i18n-merged/`、`$PKG/tools/`、`$PKG/scripts/` —�?包根下同名子目录
> - **`$PKG/dsh-out/`�? �?JSON�?* —�?必须�?`dsh-out/` 子目录里，脚本读的是 `/pkg/dsh-out/batch-01.json`；落到包根时�?3 �?plan 会直�?`Cannot read /pkg/dsh-out/batch-01.json`
> - `scp` 之前先在宿主�?`mkdir -p "$PKG/dsh-out"`（`scp` 不会替你建目录）

上传后在**宿主�?*核对�?
```bash
sha256sum "$PKG"/*.zip
ls "$PKG"/merged 2>/dev/null; ls "$PKG"/i18n-merged | wc -l      # 期望 23
```

---

## 1. 备份（机器：**生产 SSH（宿主机�?*�?
**预计 10 分钟。这一步不改任何站点文件�?*

```bash
# 1.1 定下本次�?LAUNCH 名，写进 CURRENT_LAUNCH —�?之后所有会话都从这个文件读，不再用 $(date)
LAUNCH=packrivo-launch-$(date +%Y%m%d-%H%M)
mkdir -p /home/web/backups
echo "$LAUNCH" > /home/web/backups/CURRENT_LAUNCH
BACKUP=/home/web/backups/$LAUNCH
mkdir -p "$BACKUP"/{mu,i18n,db,rollback}

# 1.2 mu �?i18n：用 "/." 摊平，保�?$BACKUP/mu/<文件�? �?$BACKUP/i18n/<文件�?
cp -a "$WP/wp-content/mu-plugins/." "$BACKUP/mu/"
cp -a "$I18N/."                     "$BACKUP/i18n/"
sha256sum "$WP/wp-content/mu-plugins/packrivo-i18n.php" \
          "$WP/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" > "$BACKUP/before-mu.sha256"

# 1.3 数据库：wp 在容器里，导出文件先落容�?/tmp，再拉回宿主�?$WPCLI db export /tmp/packrivo-before-launch.sql --skip-plugins --skip-themes
docker cp php:/tmp/packrivo-before-launch.sql "$BACKUP/db.sql"
docker exec php rm -f /tmp/packrivo-before-launch.sql

# 1.4 现状快照（回滚时的对照基准）
$WPCLI plugin list --format=csv > "$BACKUP/before-plugin-list.csv"
$WPCLI theme  list --format=csv > "$BACKUP/before-theme-list.csv"
$WPCLI option get template   > "$BACKUP/before-template.txt"
$WPCLI option get stylesheet > "$BACKUP/before-stylesheet.txt"
$WPCLI post list --post_type=page --post_status=publish --fields=ID,post_name,post_title --format=csv > "$BACKUP/before-pages.csv"
curl -s "$SITE$SITEMAP" > "$BACKUP/before-sitemap.xml"
curl -s "$SITE/robots.txt"        > "$BACKUP/before-robots.txt"

# 1.5 首次安装的书面记录（�?9 步、以及事后审计都看这一份）
cat > "$BACKUP/INSTALL-STATE.txt" <<EOF
launch=$LAUNCH
time=$(date -u)
theme_before=$($WPCLI option get stylesheet)
parent_before=$($WPCLI option get template)
child_theme_present=$(test -d "$WP/wp-content/themes/packrivo-home-v4" && echo yes || echo no)
quote_plugin_present=$(test -d "$WP/wp-content/plugins/packrivo-quote" && echo yes || echo no)
EOF
cat "$BACKUP/INSTALL-STATE.txt"
```

**通过判据（逐条机检�?*

```bash
test -s /home/web/backups/CURRENT_LAUNCH && echo "CURRENT_LAUNCH ok"
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
test -f "$BACKUP/mu/packrivo-i18n.php"                       && echo "1a mu 文件到位"
test -f "$BACKUP/mu/packrivo-site-remediation-20261022.php"   && echo "1b mu 文件到位"
test -f "$BACKUP/i18n/de-dict.json"                           && echo "1c i18n 文件到位"
[ "$(ls "$BACKUP/i18n" | wc -l)" -ge 12 ]                     && echo "1d i18n 数量 �?2"
test -s "$BACKUP/db.sql"                                      && echo "1e db.sql 非空"
[ "$(grep -c 'CREATE TABLE' "$BACKUP/db.sql")" -gt 0 ]        && echo "1f db.sql �?CREATE TABLE"
[ "$(wc -l < "$BACKUP/before-mu.sha256")" -eq 2 ]             && echo "1g 两个 mu 哈希"
test -s "$BACKUP/before-sitemap.xml"                          && echo "1h sitemap 快照非空"
grep -q 'child_theme_present=no' "$BACKUP/INSTALL-STATE.txt"  && echo "1i 首装状态已记录"
```

全都打印出来才算过�?*备份只留在服务器这台机器上，不下载到本机�?*

**回滚**：本步不产生变更。上线确认成功后再删 `$BACKUP`（删之前先确认第 9 步不再需要）�?
---

## 2. 字典与路径表（机器：**生产 SSH**；底稿与脚本�?0.2 步已上传�?
**预计 20�?0 分钟（含人工看冲突清单）。这是整个上线里唯一不能自动判定的一步�?*

原则�?*以当天新读的生产字典为底**，把本轮�?134 产品页�? 个主页面�?0 条界面词�?8k �?207 条�?8m 的报价页�?8n �?`Instant quote` / `Sample kit` 一起合并；**同一键两边都有值时生产优先**，冲突清单必须人工看过再装�?
`merge-i18n.php` 要用 PHP 跑，�?PHP 只在容器�?�?先把包和当天字典推进容器，跑完再把结果拉回宿主机�?
```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 2.1 当天生产字典快照（落在宿主机，平铺）
cp -a "$I18N" "$BACKUP/i18n-today"
ls "$BACKUP/i18n-today" | head
grep -c '"' "$BACKUP/i18n-today/de-dict.json"        # 粗略确认不是空文�?
# 2.2 包与字典推进容器�?PKG 在宿主机，容器看不见�?docker exec php mkdir -p "$PKGC"
docker cp "$PKG/." php:"$PKGC/"
docker cp "$BACKUP/i18n-today" php:/bk-i18n-today      # 合并�?--prod 输入

# 2.3 合并（生产优先、冲突写 conflicts.json�?docker exec php mkdir -p /pkg/merged
docker exec php php "$PKGC/tools/merge-i18n.php" \
    --prod=/bk-i18n-today \
    --local="$PKGC/i18n-merged" \
    --out="$PKGC/merged" \
    --require=/get-a-quote/

# 2.4 结果拉回宿主机，人工看冲突清�?mkdir -p "$PKG/merged"
docker cp php:"$PKGC/merged/." "$PKG/merged/"
cat "$PKG/merged/conflicts.json"

# 2.5 校验（容器内跑，路径都在容器侧）。每条都打印 PASS:/FAIL:，任一�?FAIL �?本步 FAIL
docker exec php sh -c 'test -f /pkg/merged/paths.json && echo "PASS: paths.json 存在" || { echo "FAIL: paths.json 缺失（常见原因：当天 i18n 快照�?*-js.json 为空或坏 JSON，合并工具没产出 js 与路径表�?; exit 1; }'
docker exec php sh -c 'grep -q "\"/get-a-quote/\"" /pkg/merged/paths.json && echo "PASS: paths �?/get-a-quote/" || { echo "FAIL: paths.json �?/get-a-quote/"; exit 1; }'
docker exec php sh -c 'php -r "\$p=json_decode(file_get_contents(\"/pkg/merged/paths.json\"),true); exit((is_array(\$p)&&count(\$p)>=146)?0:1);" && echo "PASS: paths 条数 >= 146" || { echo "FAIL: paths 条数 < 146"; exit 1; }'
docker exec php sh -c '[ "$(ls /pkg/merged/*-dict.json 2>/dev/null | wc -l)" = "11" ] && echo "PASS: dict = 11" || { echo "FAIL: dict 文件�?!= 11"; exit 1; }'
docker exec php sh -c '[ "$(ls /pkg/merged/*-js.json   2>/dev/null | wc -l)" = "11" ] && echo "PASS: js = 11"   || { echo "FAIL: js 文件�?!= 11";   exit 1; }'
docker exec php sh -c 'for f in /pkg/merged/*.json; do php -r "\$d=json_decode(file_get_contents(\$argv[1]),true); exit((json_last_error()===JSON_ERROR_NONE)?0:1);" "$f" || echo "�?JSON: $f"; done' > /tmp/badjson.txt
if [ -s /tmp/badjson.txt ]; then echo "FAIL: 有坏 JSON"; cat /tmp/badjson.txt; else echo "PASS: 无坏 JSON"; fi
rm -f /tmp/badjson.txt

# 2.6 装上去（宿主机侧，只覆盖同名文件；conflicts.json 不要拷进 i18n�?cp "$PKG/merged"/*-dict.json "$I18N/"
cp "$PKG/merged"/*-js.json   "$I18N/"
cp "$PKG/merged/paths.json"  "$I18N/"
ls -l "$I18N" | head
```

**通过判据**

- `*-dict.json` = **11**、`*-js.json` = **11**�?- 每个 JSON 都能 `json_decode`（判据看 `json_last_error()`，不看返回值真假——`conflicts.json` �?`[]` 也合法，空数组在 PHP 里是 falsy，用 `or exit(1)` 会把它误判成�?JSON），没有「坏 JSON」行�?- `paths.json` �?`"/get-a-quote/"`，条目数 �?146�?- **2.5 的每一条都要打印出 `PASS:`**；任一条打 `FAIL:` �?**本步 FAIL**：按下面「回滚」把字典恢复回去�?*停止**，修好合并输入后重跑�?2 步。不得把 exit=0 的静默失败当通过�?026-10-28 演练实测：`paths.json` 缺失时整步仍 exit=0）；
- `conflicts.json` 已人工逐条看过：凡是被生产值覆盖的键，都是我们**这次不打算改**的；
- 装完�?`/de/` 仍正常：`curl -s "$SITE/de/" | grep -c 'lang="de"'` �?1�?
**回滚**（宿主机侧，按文件逐个覆盖回去，绝不整目录塞进去）�?
```bash
cp -a "$BACKUP/i18n/." "$I18N/"
```

---

## 3. 内容�?34 个产品页 + 8 个主页面（机器：**生产 SSH**�?
**预计 15�?5 分钟�?34 页批量导入）�?*

导入脚本�?`wp eval-file` 形式，命令形态固定为 `<plan|apply|verify|rollback-check|rollback> <package-root> <expected-site> <rollback-json> [all134]`；`package-root` �?`rollback-json` 都是**容器内路�?*（`$PKGC`、`$PKGC/rollback/`），因为解释执行这个脚本的是容器里的 PHP�?
- `plan` = dry-run，只读不写（27g 打印 `input_pages=134 preflight=PASS writes=0`�?7i 打印 `hub_pages=8`）；
- `apply` = 首次在生产上**独占创建** rollback 文件（写入失败就不动数据库），再开数据库事务写入；站点地址、输入哈希、正文漂移任一不符都会中止�?- 27g 生产必须�?`all134` 范围�?30 新页 + 27e �?4 页，同一备份同一事务）�?
```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
docker exec php mkdir -p "$PKGC/rollback"          # 2.2 已把包灌进容�?
# 3.0 应用前守卫：/pkg/rollback 里若已有 JSON，它�?site 必须等于本次�?$SITE�?# 残留他站/上一轮的 JSON 会让 apply �?site/input 不符拒写，所以先拦一次，别等 apply 报错�?for f in $(docker exec php sh -c 'ls /pkg/rollback/*.json 2>/dev/null'); do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一�? \
    || { echo "FAIL: $f 非本轮产物（site �?$SITE 不一致），先清空 /pkg/rollback 再重跑第 3 �?; exit 1; }
done

# 3.1 dry-run
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"

# 3.2 真跑，跑完立刻把 rollback 文件拉回宿主机存�?$BACKUP
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
docker cp php:"$PKGC/rollback/." "$BACKUP/rollback/"
ls -l "$BACKUP/rollback/"
```

**通过判据**

- `plan` 输出 `input_pages=134 preflight=PASS writes=0` �?`hub_pages=8 preflight=PASS`�?- `apply` �?*总数**对得上：`apply_updated + already_current = 134`�?7g）、`= 8`�?7i）�?*不要要求 `apply_updated` 单独等于 134**——重复执行时 `already_current` 会吃掉其中一部分�?8q 实测就是 130 + 4�? + 1）；
- 演练环境另加一条：`apply_updated > 0`。等�?0 说明演练库的内容没有被复位，导入从未真实写入�?*这一轮演练作�?*�?- `apply` 输出 `URL_and_body_check=PASS`�?7g）；27i 输出 `H1_body_URL_check=PASS` �?`H1_body_URL_unchanged=8/8`�?- 出现 `Backup input/site mismatch` / `Original backup site/input mismatch` / `Expected-site guard failed` �?**本步 FAIL**：先�?`/pkg/rollback` 有没有他站残留（3.0 守卫本该拦住），按下面「回滚」处理并**停止**�?- 27g `verify` 输出 `URL_and_non_H1_body_unchanged=134/134`�?7i `verify` 输出 `metadata_mismatches=0`，并且有 `H1_body_URL_unchanged=8/8` �?`inquiry_description_preserved=yes`（两个脚本的 verify 打的字段不一样，�?27i 的字段去�?27g 会一�?FAIL）；
- **判据要在对应命令的输出里查（2026-10-29 当场改过�?*：`H1_body_URL_unchanged=8/8` �?**27i �?`verify`** 打的，去 `apply` 的日志里 grep 会查不到 �?误判 FAIL。查之前先确认这行属�?`plan` / `apply` / `verify` 哪一个，再落到对应的输出文件上；查错了只改查法，不要重跑已经跑对的步骤；
- `$BACKUP/rollback/` 里两�?JSON 都在�?7g �?1.2 MB，含全部原值）�?  ```bash
  test -s "$BACKUP/rollback/rollback-27g.json" && echo "27g rollback ok"
  test -s "$BACKUP/rollback/rollback-27i.json" && echo "27i rollback ok"
  ```
- �?3 页核对新标题（每�?`grep -c '<title>'` = 1）：
  ```bash
  curl -s "$SITE/product-drawer-jewelry-box/" | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/products/"                   | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/jewelry-boxes/"              | grep -o '<title>[^<]*</title>'
  ```

**回滚**（先把宿主机那份 rollback 文件推回容器，再 rollback-check �?rollback）：

```bash
docker cp "$BACKUP/rollback/rollback-27i.json" php:"$PKGC/rollback/"
docker cp "$BACKUP/rollback/rollback-27g.json" php:"$PKGC/rollback/"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
```

---

## 4. 报价插件�?*首次安装**，机器：**生产 SSH**�?
**预计 5 分钟�?* 生产上现在没�?`packrivo-quote`，所以这是新装，不是覆盖�?
```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
docker cp "$PKG/packrivo-quote.zip" php:"$PKGC/packrivo-quote.zip"
docker exec php sha256sum "$PKGC/packrivo-quote.zip"      # 必须 096d0c8e�?
$WPCLI plugin install "$PKGC/packrivo-quote.zip" --force
$WPCLI plugin activate packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote     # 状态必须是 active
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"         # 200
```

**通过判据**

- `plugin list` �?`packrivo-quote` �?`active`�?- 站点 200�?- 此时 `/get-a-quote/` 仍会�?mu 里那条�?301 顶到 `/inquiry/`（第 6 步才修）�?*这是预期的空档期**，不算失败；
- 价格只有�?6 步打完补丁后才能验（8.2）�?
**回滚**（两件事分开写，不用 `&&` 串：删掉了就回不来）�?
```bash
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除（回到首装前状态）"
```

---

## 4.5 建报价页 `/get-a-quote/`�?*首次安装**，机器：**生产 SSH**�?
**预计 2 分钟。放在第 4 步装完插件之后、第 6 步打补丁之前�?*

为什么必须有这一步：报价插件 `packrivo-quote` 只提供短代码 `[packrivo_quote]`�?*它自己不会建页面**（插件里只有 `register_activation_hook` �?`flush_rewrite_rules()`，没�?`add_rewrite_rule`、没�?`template_include`）。生产上线前没有这个页面，是 mu 里那条�?301 �?`/get-a-quote/` 顶到 `/inquiry/` 遮住了；�?6 �?27e 删掉 301 之后就会露出 404�?026-10-29 就是这样 FAIL 的）。演练库以前自带这个页面（ID 3220），所以演练一直没暴露—�?9v �?`reset.sh` 会把它删掉�?
**4.5.1 守卫：任何状态都不能已有 slug �?`get-a-quote` 的页�?*

```bash
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 直接查库（含 draft / trash / revision，全 post_type�?$WPCLI db query "SELECT ID,post_type,post_status,post_name FROM wp_posts WHERE post_name='get-a-quote'"
# 计数（注意：wp db query 不要�?--skip-column-names，它对这个调用返回空；取输出里最后一个纯数字行）
$WPCLI db query "SELECT COUNT(*) AS c FROM wp_posts WHERE post_name='get-a-quote'"
# 再走一�?wp 接口，把草稿和回收站都显式列�?$WPCLI post list --post_type=any --post_status=any,draft,pending,private,future,trash \
                 --name=get-a-quote --fields=ID,post_type,post_name,post_status --format=csv
```

两条都要�?**0 �?/ 0 �?* 才继续�?*已经有就停下报告，不要覆盖、不要改 slug 硬建�?*

**4.5.2 建页**

```bash
$WPCLI post create --post_type=page --post_title='Get a Quote' --post_name=get-a-quote \
       --post_content='[packrivo_quote]' --post_status=publish --comment_status=closed --porcelain
# 返回的数字就是页�?ID（生�?2026-10-29 �?3383�?echo "get_a_quote_page_id=<ID> created=$(date -u +%FT%TZ)" >> "$BACKUP/INSTALL-STATE.txt"
tail -2 "$BACKUP/INSTALL-STATE.txt"
```

**通过判据**

| # | 判据 | 怎么�?|
|---|---|---|
| 4.5a | `post_name` **正好**�?`get-a-quote`（不�?`get-a-quote-2`�?| `$WPCLI post get <ID> --field=post_name` |
| 4.5b | `post_content` **正好**�?`[packrivo_quote]` | `$WPCLI post get <ID> --field=post_content` |
| 4.5c | `post_status` = `publish` | `$WPCLI post get <ID> --field=post_status` |
| 4.5d | ID 已记�?`$BACKUP/INSTALL-STATE.txt` | `grep get_a_quote_page_id "$BACKUP/INSTALL-STATE.txt"` |

此时 `/get-a-quote/` 仍然 301 �?`/inquiry/`�?7e 还没打）�?*这是预期空档�?*，不算失败；�?6 步打完补丁它才会 200 并渲染出报价组件�?
**回滚**

```bash
$WPCLI post delete <ID> --force          # ID �?INSTALL-STATE.txt �?$WPCLI post list --post_type=page --name=get-a-quote --post_status=any,draft,trash --format=count   # 期望 0
```

---

## 5. 子主题（**首次安装**，机器：**生产 SSH**�?
**预计 5 分钟�?*

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 5.1 先确认父主题没被换掉
$WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active     # 必须 1.2.38

# 5.2 �?+ 启用
docker cp "$PKG/packrivo-home-v4.zip" php:"$PKGC/packrivo-home-v4.zip"
docker exec php sha256sum "$PKGC/packrivo-home-v4.zip"    # 必须 7D792AD9�?$WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
$WPCLI theme activate packrivo-home-v4

# 5.3 核对
$WPCLI option get stylesheet      # 期望 packrivo-home-v4
$WPCLI option get template        # 期望 packrivo-static-bridge-1.2.38
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"      # 200
```

**通过判据**

- 父主题仍�?`packrivo-static-bridge-1.2.38`（若变了**停下**，先确认父子兼容）；
- `option get stylesheet` = `packrivo-home-v4`，`option get template` = 父主题目录名�?- 首页 200�?
**回滚**（先把主题切回父主题，再删目录；两条分开写）�?
```bash
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除（回到首装前状态）"
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"
```

---

## 4.6 指定页面模板 `_wp_page_template`�?*首次安装**，机器：**生产 SSH**�?
**预计 1 分钟。编号沿用任务单�?4.6，但机器上的顺序是「第 5 步装完子主题之后、第 6 步打补丁之前」——模板文�?`template-products-v4.php` 是随子主题包一起装进来的，�?5 步之前跑 4.6.1 的守卫必然失败（文件不存在），这是预期�?*

为什么必须有这一步：子主题给 `/products/` 准备�?`template-products-v4.php`，但**页面本身要有一�?`_wp_page_template` meta 才会用它**。没有这�?meta，WordPress �?`page.php` 默认模板，把页面正文里那�?legacy HTML 原样吐出来——筛选控件挤成一串文字、内容贴左边缘、产品图撑满整宽�?026-10-29 上线�?`/products/` 就是这样错乱的，`2026-10-29w` 的生产热修复补的就是这一处（页面 8，原�?*不存�?*）。这类「本地库里有、RUNBOOK 没写、演练库又是从本地复制的」遗漏，�?4.5 的报价页是同一类�?
> 生产 2026-10-29 实测：`post meta get 8 _wp_page_template` �?空、`rc=1`（根本没有这�?meta）；页面 meta 一�?10 条，`_packrivo_native_page` / `_packrivo_canonical` / `_yoast_wpseo_focuskw` / `_pkpw_legacy_body` 都在，唯独没有模板。全�?*只有这一�?*需要指定模板（`post list --meta_key=_wp_page_template --meta_compare='!=' --meta_value=default` 命中为空）�?
**4.6.1 守卫：页�?8 存在、模板文件存�?*

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

$WPCLI post get 8 --fields=ID,post_type,post_name,post_status --format=csv
#   期望 post_type=page、post_name=products、post_status=publish；对不上就停下报告，不要改别的页�?ls -l "$WP/wp-content/themes/packrivo-home-v4/template-products-v4.php"
#   模板�?*子主�?*目录里（父主题没�?template-*.php，这是正常的）；文件不在就停下（�?5 步装的主题包不对�?```

**4.6.2 先把原值记�?`INSTALL-STATE.txt`（回滚只认这里）**

```bash
V=$($WPCLI post meta get 8 _wp_page_template 2>/dev/null); rc=$?
[ -z "$V" ] && V=EMPTY
echo "page8_template_before=$V (rc=$rc) at=$(date -u +%FT%TZ)" >> "$BACKUP/INSTALL-STATE.txt"
grep page8_template_before "$BACKUP/INSTALL-STATE.txt"
```

`rc=1` + �?= **本来没有这条 meta**�?026-10-29 生产就是这样）；此时回滚�?`post meta delete`，不是「改回某个值」�?
**4.6.3 执行**

```bash
$WPCLI post meta update 8 _wp_page_template template-products-v4.php
$WPCLI post meta get 8 _wp_page_template            # 期望回显 template-products-v4.php
```

**通过判据（全 PASS 才算过；HTTP 一律带 `?cb=$(date +%s)` 破缓存）**

| # | 判据 | 怎么�?|
|---|---|---|
| 4.6a | meta 值正�?`template-products-v4.php` | `$WPCLI post meta get 8 _wp_page_template` |
| 4.6b | `/products/` �?`<body class>` �?`page-template-template-products-v4` | `curl -s "$SITE/products/?cb=$(date +%s)" \| grep -o '<body[^>]*>'` |
| 4.6c | 页面出现 `Every structure we can source` | `curl -s "$SITE/products/?cb=$(date +%s)" \| grep -c 'Every structure we can source'`（期�?1�?|
| 4.6d | `/de/products/`、`/ar/products/` 也是这个模板（文案随语种翻译，别拿英文句子判�?| 同上，只�?`page-template-template-products-v4` |
| 4.6e | 375 / 768 / 1024 三个宽度**无横向溢�?* | 真实浏览器：`document.documentElement.scrollWidth === window.innerWidth`；没有浏览器就停下报告，别用肉眼代替 |
| 4.6f | `/`、`/blog/` 仍然 200；`/get-a-quote/` **此刻仍是 301**�?7e 补丁在第 6 步才打，这是预期空档，第 6 步之后才该变 200�?| `curl -s -o /dev/null -w '%{http_code}\n' "$SITE/blog/?cb=$(date +%s)"` |

4.6e 的现成跑法（本机 `playwright-core` + 本机 Chrome，`2026-10-29w` 用它给生产做过三宽度检查）：三个宽度各开一�?`/products/?cb=<ts>`，`evaluate` �?`document.documentElement.scrollWidth` �?`window.innerWidth`，两者相等且没有越界元素才算过�?
**回滚**

```bash
V=$(sed -n 's/^page8_template_before=\(.*\) (rc=.*/\1/p' "$BACKUP/INSTALL-STATE.txt" | tail -1)
if [ "$V" = "EMPTY" ] || [ -z "$V" ]; then
  $WPCLI post meta delete 8 _wp_page_template        # 原值本来不存在
else
  $WPCLI post meta update 8 _wp_page_template "$V"
fi
$WPCLI post meta list 8 --keys=_wp_page_template --format=csv   # EMPTY 时只剩表�?curl -s "$SITE/products/?cb=$(date +%s)" | grep -o 'page-template-[a-z0-9-]*' | head -1   # 回到 page-template-default
```

---

## 6. mu 补丁（机器：**生产 SSH**�?
**预计 10 分钟。两个补丁落在不同文件上，各自独立回滚。顺序固定：�?`27e-quote-redirect.patch`，再 `packrivo-i18n-404-viewport.patch`�?*

`git apply` 在宿主机跑（宿主机有 git），`php -l` 在容器里跑�?
```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 6.1 27e：把 get-a-quote 从旧别名列表里删掉（只删这一项）
cd "$WP/wp-content/mu-plugins"
sha256sum packrivo-site-remediation-20261022.php
#   生产基线: ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d
#   与基线不一致就�?diff 生产文件与本机基线，确认改动范围再决定，不要盲目打补�?git apply -p1 --check "$PKG/27e-quote-redirect.patch" && echo CHECK-OK
git apply -p1         "$PKG/27e-quote-redirect.patch"
docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php"

# 6.2 404 viewport：给「本页尚未翻译」的 404 �?<meta name="viewport">
cd "$WP"                      # 这个补丁的路径前缀�?wp-content/mu-plugins/，要�?WP 根执�?sha256sum wp-content/mu-plugins/packrivo-i18n.php
#   生产 2026-10-29 实测：打�?a2a7bdf3…，打后 f7d95f35…（只截了前 8 位，全值见 28u �?compare-cols 日志�?#   这个文件**没有**�?27e 那样的固定基线：演练库里的副本是 3bc325503b16…（28m 之前的版本）�?#   与生产那份不是同一版，但同一份补丁两边都能干净打上。所以判据是 git apply --check 通过�?#   不是"哈希等于某个�?；打完只要哈希变了就算落盘�?git apply -p1 --check "$PKG/patches-28m/packrivo-i18n-404-viewport.patch" && echo CHECK-OK
git apply -p1         "$PKG/patches-28m/packrivo-i18n-404-viewport.patch"
docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-i18n.php"
```

**通过判据**

| # | 判据 | 怎么�?|
|---|---|---|
| 6a | 6.1 打补�?*之前**�?`sha256sum` = 基线 `ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d` | 打之前先 `sha256sum`，与基线不一致就�?diff 再决定，不要盲目�?|
| 6b | 6.1 打补�?*之后**�?`sha256sum` �?基线（说明补丁真的落盘了�?| 打完�?`sha256sum` 一�?|
| 6c | 两个 `git apply --check` �?`CHECK-OK`，两�?`php -l` �?`No syntax errors detected` | 见上面命令输出；`--check` 失败**不要强打** |
| 6d | `/get-a-quote/` = **200**（不再是 301�?| `curl -s -o /dev/null -w '%{http_code}' "$SITE/get-a-quote/?cb=$(date +%s)"` |
| 6e | `/de/get-a-quote/` = **200** | 同上，换�?`/de/get-a-quote/` |
| 6f | 报价页里有报价组件（短代码真的渲染出来了�?| `curl -s "$SITE/get-a-quote/?cb=$(date +%s)" \| grep -c 'pkq-root'` �?期望 �?1；`/de/get-a-quote/` 同样一�?|
| 6g | 语种 404 �?viewport | `curl -s "$SITE/ar/2026/10/26/jewelry-pouch-box-customer-handoff/?cb=$(date +%s)" \| grep -c 'name="viewport"'` �?期望 1 |

> **6b 的写法是 2026-10-29 当场改的**：那一轮拿「打补丁**�?*」抓的哈希变量去比「是否已变」，当然相等 �?误判 FAIL。要判「补丁落盘」就必须**打完再取一次哈�?*�?>
> **6d/6e 不过先查 4.5**：这两条 200 的前提是 `/get-a-quote/` **页面存在**�?.5 建的）�?026-10-29 首装就是在这一�?FAIL——补丁打对了，但生产从来没有这个页面�?7e �?301 删掉后直�?404。判 200 之前先确�?`$WPCLI post list --post_type=page --name=get-a-quote --format=count` = 1�?>
> **URL 判据一律带 `?cb=` 破缓�?*：Cloudflare 会缓�?301/404，拿�?URL 打两次会读到上一次的缓存结果�?8u �?6g 时就被缓存的 404 页骗�?0）�?
**回滚**（两个独立，读的是第 1 步摊平后�?`$BACKUP/mu/<文件�?`）：

```bash
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"   # 回滚 27e
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"   # 回滚 404 补丁
sha256sum -c "$BACKUP/before-mu.sha256"
```

---

## 7. 清缓存（机器�?*本机**（Cloudflare�? **生产 SSH**（transient））

**预计 5 分钟�?*

> **Cloudflare 这一段：负责人亲自执行�?* 凭据只存�?`E:\破甲工作区\i18n\.cf.env`，本手册只引用路径、不写任何值；执行时不要把文件内容 `cat`/`echo` 出来，也不要�?token 贴进聊天、工单或日志�?
```bash
# 本机（负责人亲自执行）：Cloudflare 全站 purge + 12 路径 × 11 语种点名�?+ 自动校验
python E:\破甲工作区\i18n\cf_purge.py

# 生产 SSH（宿主机）：清报价插件的 12h pricing transient
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"     # 有对象缓存插件时生效，没有就如实记录
```

**通过判据**

- `cf_purge.py` 结尾输出 `仍过�? �?—�?全部同步`�?- `curl -sI "$SITE/de/" | head -5` 返回 200�?- 页面语言标记正确（`<html lang="de">`）�?
**回滚**：缓存清了不需要回滚�?
---

## 8. 上线后复测清单（机器�?*本机浏览�?* + **生产 SSH** 抽查�?
**预计 20�?0 分钟。逐条照做，任何一条不过就按对应步回滚，不要「先上着」�?*

| # | 检查项 | 怎么�?| 通过判据 |
|---|---|---|---|
| 8.1 | title / H1 / 描述（首页、`/products/`�? 个产品页 × en / ar / de�?| 浏览器打开 9 �?URL，看标签页标题、页�?H1、右键查�?`<meta name="description">` | 三种语种都是新的、各自语言的标题；H1 与标题不冲突；描述非�?|
| 8.2 | 报价价格 | 浏览器打开 `/get-a-quote/?style=rigid&qty=500` �?`/ar/get-a-quote/` | 单价 **$2.83**、合�?**$1,417.43**（mailer $2.48/$1,239.04、sticker $0.96/$479.63、shipping $2.31/$1,156.69 同样逐格一致） |
| 8.3 | 询盘�?| `/inquiry/` �?`/inquiry/?product=product-drawer-jewelry-box` | 字段齐全（姓名、邮箱、数量、产品）；带 `?product=` 时产品已预选；**只读不提�?* |
| 8.4 | 横向溢出 | 375 / 768 / 1024 三个宽度各看首页、`/products/`�? 个产品页、`/get-a-quote/`、`/ar/` | 都没有横向滚动条（`document.documentElement.scrollWidth` = 视口宽）�?8p �?768/1024 也在范围�?|
| 8.5 | 每页 1 �?title | `curl -s <URL> \| grep -c '<title>'`，抽 5 �?URL | 每个都是 **1** |
| 8.6 | sitemap / robots 没变 | `curl -s "$SITE$SITEMAP"`、`robots.txt`，与�?1 步的 `before-*.xml/txt` diff | sitemap 仍有语种子图；`robots.txt` 与上线前逐字一�?|
| 8.7 | 语种报价按钮 | en / ar / de / ja 首页�?1 个产品页，看页头与页脚的报价按钮 | 文字是各自语言（`عرض سعر فوري` / `Sofortangebot` / `すぐに見積もり`），链接都指�?`/{lang}/get-a-quote/` |

固定命令部分�?
```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 8.5 每页 1 �?title（抽 5 �?URL�?for u in "$SITE/" "$SITE/products/" "$SITE/de/" "$SITE/ar/" "$SITE/product-drawer-jewelry-box/"; do
  echo -n "$u  title�?"; curl -s "$u" | grep -c '<title>'
done

# 8.6 sitemap / robots 与第 1 步快照对�?diff "$BACKUP/before-sitemap.xml" <(curl -s "$SITE$SITEMAP") && echo "sitemap 未变"
diff "$BACKUP/before-robots.txt"  <(curl -s "$SITE/robots.txt")       && echo "robots 未变"
```

**回滚**：本步是纯检查；哪条不过就回滚对应步—�?.1/8.5/8.6 �?�?3�? 步；8.2/8.7 �?�?4�? 步；8.3 �?�?5 步；8.4 �?�?5�? 步�?
---

## 9. 整体回滚（逆序，机器：**生产 SSH**�?
**预计 15 分钟。开局先取回本�?LAUNCH，不要重新生成�?*

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
PKGC=/pkg
SITE=https://www.packrivo.com
SITEMAP=/sitemap_index.xml    # Yoast 站点地图；若站点只用 WP 核心地图就改�?/wp-sitemap.xml
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)     # �?固定来源，绝不用 $(date) 重算
BACKUP=/home/web/backups/$LAUNCH
echo "回滚目标: $BACKUP"; test -f "$BACKUP/db.sql" && echo "备份在位"
```

```bash
# 9.8 报价页：�?INSTALL-STATE 里记�?ID 删掉（顺序在�?9.6 之前�?#     这一页是 4.5 建的，回滚到首装前状态就必须删掉，否则站点留着一个只在本次上线存在的页面
GQ_ID=$(sed -n 's/^get_a_quote_page_id=\([0-9][0-9]*\).*/\1/p' "$BACKUP/INSTALL-STATE.txt")
echo "报价�?ID = ${GQ_ID:-（没找到，先�?$BACKUP/INSTALL-STATE.txt）}"
test -n "${GQ_ID:-}" && $WPCLI post delete "$GQ_ID" --force
$WPCLI post list --post_type=page --name=get-a-quote --post_status=any,draft,trash --format=count   # 期望 0

# 9.7.5 页面模板�?.6 设的 meta）：�?INSTALL-STATE 里记的原值恢复；原值是 EMPTY 就删�?#    顺序：在 9.8 之后�?.6 之前——删模板要早于回滚子主题，否则中间那段页面还是新模板套旧主题
TB=$(sed -n 's/^page8_template_before=\(.*\) (rc=.*/\1/p' "$BACKUP/INSTALL-STATE.txt" | tail -1)
echo "页面 8 模板原�?= ${TB:-（没找到，先�?$BACKUP/INSTALL-STATE.txt）}"
if [ "${TB:-}" = "EMPTY" ] || [ -z "${TB:-}" ]; then
  $WPCLI post meta delete 8 _wp_page_template 2>/dev/null || echo "本来就没有这�?meta"
else
  $WPCLI post meta update 8 _wp_page_template "$TB"
fi
$WPCLI post meta list 8 --keys=_wp_page_template --format=csv    # EMPTY 时只剩表�?
# 9.7 缓存：无需回滚（重新生成即可）；想立即回旧内容就先清一�?#     python E:\破甲工作区\i18n\cf_purge.py      �?本机，负责人亲自
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"

# 9.6 两个 mu 补丁（读 $BACKUP/mu/<文件�?，摊平后的路径）
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"

# 9.5 子主题：先切回父主题，再删（两条分开，不�?&&�?$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除"

# 9.4 报价插件：先停用，再�?$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移�?

# 9.3 内容�?34 产品�?+ 8 主页面，�?rollback-check �?rollback�?docker cp "$BACKUP/rollback/rollback-27i.json" php:"$PKGC/rollback/"
docker cp "$BACKUP/rollback/rollback-27g.json" php:"$PKGC/rollback/"

# 9.3 回滚前守卫：推回去的两个 JSON 必须都是本轮�?3 步生成的（site 与本轮一致）�?# 否则 rollback 会以 site/input 不符拒写，而那时已经删了主题和插件，损失更大�?for f in /pkg/rollback/rollback-27g.json /pkg/rollback/rollback-27i.json; do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一�? \
    || { echo "FAIL: $f site �?$SITE 不一�?; exit 1; }
done
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134

# 9.2 字典与路径表（按文件覆盖回去�?cp -a "$BACKUP/i18n/." "$I18N/"
```

最后再跑一�?8.1 / 8.2 / 8.6，确认回到上线前状态�?
**判据**：`sha256sum -c "$BACKUP/before-mu.sha256"` �?OK；`option get stylesheet` 回到 `packrivo-static-bridge-1.2.38`；`plugin list | grep packrivo-quote` 无输出；27g/27i `rollback-check` 输出 `rollback_eligible` �?rollback �?`verify` �?`metadata_mismatches=0`�?.1/8.2/8.6 与上线前一致�?
**最后手�?*（分步回滚都不灵时）：把数据库整个换成第 1 步导出的 `db.sql`�?
> **这一招只回滚数据库�?* 上线后新增的询盘、表单提交、评论、订单会一起消失—�?8r 实测：导入前新建的草�?`28r-canary`，导入后查不到。用之前先确认这段时间没有新数据，或者先把新数据单独导出�?> **它不回滚文件�?* 子主题目录、插件目录、mu 文件、i18n 字典仍是「上线后」的版本，所以最后手段必须和 9.6 / 9.5 / 9.4 / 9.2 一起做，顺序见 L3�?
**前置检查（必做�?*：`wp db import` �?`wp db export` 一样走 `mariadb` 客户端、同样带 `--no-defaults`�?*没有�?0.1 步那个客户端包装就会失败**�?8r 实测：拆掉包装时 `db import` 退出码 1（导入没发生——表数仍�?40、`option get siteurl` 仍读得到，数据库没被导坏）；装上包装后同一�?`db.sql` 导入成功。所以先重跑 0.1 的导出探针确认包装在位：

```bash
$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes
docker exec php sh -c 'wc -c /tmp/probe.sql'      # 必须 > 0；若�?0 或报 TLS/SSL error，按 0.1 的补救块把包装装上再重探
docker exec php rm -f /tmp/probe.sql
```

```bash
LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)
BACKUP=/home/web/backups/$LAUNCH

# L1 整库导入（容器内临时文件，用完就删）
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
$WPCLI db import /tmp/db.sql
docker exec php rm -f /tmp/db.sql

# L2 导入后清 transient 与缓�?$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"

# L3 文件部分�?.6 �?9.5 �?9.4 �?9.2，顺序不能反，删和恢复不要串成一�?cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
cp -a "$BACKUP/i18n/." "$I18N/"
```

**判据（逐条打勾，不�?exit 码）**�?
| # | 判据 | 怎么�?|
|---|---|---|
| L-1 | 表数 = `db.sql` �?`CREATE TABLE` 的条数（演练�?40�?| `grep -c 'CREATE TABLE' "$BACKUP/db.sql"` 与库�?`information_schema.tables` 的计数相�?|
| L-2 | 上线后新增的数据确实没了 | 导入前建的草稿按 slug 查：`$WPCLI post list --name=<slug> --post_status=any --format=count` �?`0` |
| L-3 | `stylesheet` �?`template` 都是 `packrivo-static-bridge-1.2.38` | `$WPCLI option get stylesheet` / `template` |
| L-4 | `packrivo-quote` 不再**激�?* | `$WPCLI plugin list --status=active --format=csv \| grep '^packrivo-quote,'` 无输出�?*只看 active**：导入后插件文件还在，`plugin list` 里当然还看得到它，要�?L3 �?9.4 删目录才彻底消失 |
| L-5 | 页面标题回到上线�?| �?`$BACKUP/before-pages.csv`（或演练基线快照的「页面标题」一节）逐行一�?|
| L-6 | 首页 / `/products/` / `/de/` 200，`/get-a-quote/` 301 | `curl -s -o /dev/null -w '%{http_code}'`�?*301 必须�?L3 �?9.6 之后�?*�?7e 补丁还在�?`/get-a-quote/` �?200（渲染报价页），这不是故障是顺序 |

28r 本地实测（`deploy/verification-28r/lastresort.log`，复核在 `acceptance-recheck.log`）：L-1 / L-2 / L-3 / L-5 一�?PASS�?*L-4、L-6 首次执行时打�?FAIL**，原因就是上表这两条注记——L-4 当时写成了「在不在列表里」，L-6 �?9.6 之前判。按上表改写后复核：L-4 PASS、L-6 PASS�?01 �?`/inquiry/`）。原始一�?PASS=16 FAIL=2（`EXIT=1`），复核 PASS=11 FAIL=0（`EXIT=0`），L3 做完后整站快�?sha256 �?28q 基线 `4972cd81…` **逐字节一�?*�?
---

## 附录 A · 已知问题（不在本次上线范围）

> **28p 已修**：页头在 768 px �?1024 px 的横向溢出。做法是把汉堡断点统一到实测得到的 W=1294�?2 语种桌面页头右侧�?16 px 余量放得下的最大宽度）�?024 及以下全语种进汉堡；768 那一档的溢出其实在页脚底部（`.footer-bottom` �?768�?40 之间不换行）。验证见 `deploy/verification-28p/`�?
| 问题 | 实测 | 影响 | 何时处理 |
|---|---|---|---|
| `/ar/<文章路径>/` 返回 404 | 页面 `noindex,follow` 并链到英文版 | 阿语站内文章入口只能跳英�?| 等文章翻译排�?|
| `/contact/` 301 �?`/inquiry/` | 生产与本地一�?| 不构成重复标题页 | 若要做独立联系页再说 |
| 语种页副链接�?1440�?780 px 间按语种隐藏 | `.pk-actions .pk-text-link` 的按语种媒体查询 | 副链接在这些宽度不显示（不影响报价按钮） | 已按实测调过阈值，属预期行�?|

## 附录 B · 时间与顺序速查

| �?| 内容 | 机器 | 预计 | 是否可跳�?|
|---|---|---|---|---|
| 0 | 环境检�?+ 上传�?| 生产 SSH + 本机 | 10 min | 不可 |
| 1 | 备份（含 db.sql�?| 生产 SSH（宿主机�?| 10 min | 不可 |
| 2 | 字典 + 路径�?| 生产 SSH（容器里跑合并） | 20�?0 min | 不可（本轮主体） |
| 3 | 134 产品�?+ 8 主页�?| 生产 SSH（容器里 eval-file�?| 15�?5 min | 不可（SEO 主体�?|
| 4 | 报价插件（首装） | 生产 SSH | 5 min | 可（若报价插件不变） |
| 4.5 | 建报价页 `/get-a-quote/`（首装必做） | 生产 SSH | 2 min | 不可（插件只给短代码，不会自己建页） |
| 5 | 子主题（首装�?| 生产 SSH | 5 min | 可（若只改内容） |
| 4.6 | 指定页面模板 `_wp_page_template`（`/products/`，首装必做） | 生产 SSH | 1 min | 不可（没有这�?meta 页面就走默认模板，正�?legacy HTML 会排版错乱） |
| 6 | 两个 mu 补丁 | 生产 SSH | 10 min | 27e 不可省（否则报价按钮全跳询盘�?|
| 7 | 清缓�?| 本机 + 生产 SSH（Cloudflare 由负责人亲自执行�?| 5 min | 不可 |
| 8 | 复测 | 本机浏览�?+ 生产 SSH | 20�?0 min | 不可 |
| 9 | 整体回滚�?*�?9.8 删报价页 �?9.7.5 还原页面模板**，再 9.7 �?9.2�?| 生产 SSH（清缓存在本机） | 15 min | 只在出问题时执行 |

## 附录 C · 本地演练�?8q 正反�?+ 28r 最后手段，均已跑通）

本手册在 2026-10-28 于本地按「生产结构」完整演练过：WP **7.1.4**�?8t 起；7.1.3 时代跑过 28q/28r/28s 三轮�? PHP **8.3.36**、`php` / `mysql` / `nginx` 三个容器、宿主机目录 `/home/web/...`、子主题与报价插�?*未安�?*的首装前状态，内容也摆回上线前�?7g/27i 的旧标题、旧正文）�?
**判据以行为准，不�?exit 码�?* 28q 当时记的�? 个步骤块全部 exit=0」现在已经不算证据：`rehearsal.sh` 每个 step 结尾都是 `set +x`，脚本恒返回 0�?8r 起改成——每一步的判据打印 `PASS:` / `FAIL:`，`step_end` 统计本步 `PASS=n FAIL=m`，有 FAIL �?`exit 1`（`bash rehearsal.sh selftest` 可自检这套机制）�?
| 轮次 | 覆盖步骤 | 结果（按判据行） |
|---|---|---|
| 正向�?8q�?| �?0�? �?| 当时只记�?exit 码（已作废，见上）；可复算的证据是正向快�?`4247693f…` 与基�?`4972cd81…` 不同（第 3 步确实改了内容） |
| 回滚�?8q�?| �?9 �?| 站点快照与基�?*逐字节一�?*（`4972cd81…`）；注意这一�?*没有导入数据�?*，整库回滚见 28r |
| 再正向（28q�?| �?1�? �?| 站点快照与第一次正�?*逐字节一�?*（`4247693f…`），说明每步可重复执�?|
| **最后手段（28r�?* | �?9 步末尾的整库导入 + 9.6/9.5/9.4/9.2 文件部分 | 原始一�?`PASS=16 FAIL=2 EXIT=1`（L-4/L-6 判据写法与顺序都错了，见该节注记），按改写的判据复核 `PASS=11 FAIL=0 EXIT=0`；整站快照回到基�?`4972cd81…`（逐字节一致） |
| **并入三个守卫后重跑（28s�?* | �?0�? 步正�?+ �?9 步回�?| �?`DSH-RESULT-2026-10-28s.md`：每�?`PASS=n FAIL=0` �?`EXIT=0`；复位快�?`4972cd81…`、正向快�?`4247693f…`、回滚后回到 `4972cd81…`；反向自检确认 3.0 守卫拦得住他站残�?JSON |
| **本地对齐 WP 7.1.4 后重跑（28t�?* | �?0�? 步正�?+ �?9 步回�?+ 3.0 反向自检 | �?`DSH-RESULT-2026-10-28t.md`：core 升级�?7.1.4（官方包 `a147146c…`）后每步 `PASS=n FAIL=0` �?`EXIT=0`；复位快照作为本轮新基线，回滚后回到该基线；正向快照�?28s 正向 `4247693f…` �?diff 只来自版本号，页面标题没有变化。RUNBOOK 提交号见结果文档（下一次生产授权锁定它�?|
| **演练环境�?MySQL 后完整重跑（29v�?* | 正向 0�?（含 4.5 建页�? 最后手段（整库导入�? 第二轮正�?+ �?9 步回滚（�?9.8 删页�? 3.0 反向自检 | �?`DSH-RESULT-2026-10-29v.md` �?`deploy/verification-29v/`：`mysql` �?**MySQL 8.4.13**（`caching_sha2_password` + 自签 TLS）、`php` 客户端钉 **mariadb 11.4.13**，第 0 步如实复现「`2026 TLS/SSL error` �?装包�?�?`1045 caching_sha2` �?�?connector �?三条判据 PASS」；两轮正向�?`73 PASS / 0 FAIL`、`step9` `PASS=9 FAIL=0`、最后手�?`20 PASS / 0 FAIL`，全�?`EXIT=0`。新基线 `8971f447…`（取�?MariaDB 时代�?`4972cd81…`），正向 `eef3fd37…` �?28s �?`4247693f…` **只差一�?*（报价页 ID 3220 �?5444）；最后手�?/ �?9 �?/ 反向自检三条路径之后快照都回到基�?`8971f447…`（逐字节一致） |

### 附录 C-1 · 2026-10-29 生产实跑（首装，已上线）

第一次真上生产（`4544e0d`，Claude 线上只读复核通过）。逐字节证据：`deploy/LAUNCH-LOG-2026-10-28.md` §37–�?0、`dsh-out/DSH-RESULT-2026-10-28-prod.md` §18–�?1�?
**那一轮两次停下等临时批准（现在都已写进正文，下次不用再批�?*�?
| # | 现象 | 原因 | 现在的正文位�?|
|---|---|---|---|
| 偏离 1 | 0.1 导出探针先报 `TLS/SSL error: self-signed certificate in certificate chain`�? 字节）；打上 `--skip-ssl` 包装后改�?`1045 �?caching_sha2_password.so: No such file or directory`（仍 0 字节�?| 生产 `mysql` �?**MySQL 26.7.0**、认�?`caching_sha2_password`；php 容器�?`mariadb-dump 11.4.13`，`/usr/lib/mariadb/plugin/` 初始 0 个文件。当场批�?`apk add --no-cache mariadb-connector-c`（装�?3.3.10-r0，插件目�?0 �?5 个文件），探针随即导�?32,516,267 B | **0.1「补救块（两层）�?* |
| 偏离 2 | �?6 步补丁打对了，`/get-a-quote/` 却是 **404**（en/de/ar �?404，HEAD �?404�?| 生产**从来没有** `/get-a-quote/` 页面：插件只给短代码，旧 301 一直遮着；演练库自带这个页面（ID 3220）所以从没暴露。当场批�?`post create` 建页，生�?ID **3383** | **4.5 建报价页**�?*�?6 步判�?6d/6e 注记**�?*9.8 删报价页** |

**那轮当场改正的三处判据写�?*（都已进正文）：0k-2 �?`SHOW FULL TABLES` �?`BASE TABLE` 并去掉表头（`SELECT COUNT(*) �?--skip-column-names` 返回空、`SHOW TABLES | wc -l` 多算一行表头）�?g �?`H1_body_URL_unchanged=8/8` �?**verify** 输出里，不在 apply�?b 要拿**打补丁之�?*的哈希去比「已变」�?
**实测数字（下次可对照�?*：`CURRENT_LAUNCH=packrivo-launch-20261029-0335`；`db.sql` 32,516,235 B / `sha256 1733240d�?ab6`；`CREATE TABLE` 40 = `SHOW FULL TABLES` �?BASE TABLE 40�?7g `apply_updated=134 already_current=0`�?7i `7+1=8`；两�?mu 打后 `887787ab…` / `f7d95f35…`；core 全深度改�?**48** 个文件�?
28q 补跑过第 0.1 步新加的**数据库导出探�?*：拆掉客户端包装时探针报 `TLS/SSL error: SSL is required`、导�?0 字节；按本手册打包装后导�?36,381,501 B�?0 张表�?
28r �?*最后手�?*跑了两遍（这就是它必须演练的理由）：
- **包装不在�?* `db import` 退出码 1，导入没有发生（表数仍是 40、`option get siteurl` 仍读得到，数据库没被导坏）；
- **包装在时**同一�?`db.sql` 导入成功�?0 张表，导入前建的草稿 `28r-canary` 消失（证明最后手段会丢掉上线后新增数据），页面标题一节与基线 726 行逐行一致；
- 文件部分�?9.6 �?9.5 �?9.4 �?9.2 做完后，整站快照 sha256 �?28q 基线 `4972cd81…` **逐字节一�?*�?
结构化摘要：`deploy/verification-28q/rehearsal-verify.json`；演练脚本（复位 / 快照 / 逐步执行）：`deploy/verification-28q/scripts/`。最后手段的完整日志、三份快照与 diff：`deploy/verification-28r/`�?
**上线后补记（2026-10-29 当天与次日）**�?9 号上线当场的两处偏离是「connector-c 插件缺失」和「`/get-a-quote/` 页面不存在」；次日又发�?*第三处同类遗�?*——页�?8 `/products/` 没有 `_wp_page_template`，用的是默认模板，正文里�?legacy HTML 被原样吐出（筛选控件挤成文字、图片撑满宽）。三处的成因完全一样：**本地库里有的东西，RUNBOOK 没写，演练库又从本地复制**。现在的对应正文�?**0.1 补救块（两层�?*�?*4.5 建报价页**�?*4.6 指定页面模板**，回滚分别是 **9.8** �?**9.7.5**；生产那两次执行原文�?`deploy/LAUNCH-LOG-2026-10-28.md` §39 �?§41�?

---

## 附录 D · 29z（撤 1688 进货价 + 4.0.3 包 + 死链/字体），2026-10-29

> 本节内容全部在**演练环境**验证过；生产那一步（第 0 步导库）需要业主放行后另跑。
> 判定口径：每条判据打印 PASS/FAIL，不以退出码当证据。

### D.1 锁定物（sha256，改一个字就要重跑本节）

| 物 | 路径 | 字节 | sha256 |
|---|---|---|---|
| 子主题包 4.0.3 | `deploy/packrivo-home-v4.zip` | 791053 | `7d792ad9f4e63025e2fce8a640856710ede5fe3b925e9bdaa372a6002bb945f7` |
| 去价脚本 | `deploy/patches-29z/strip-1688.php` | 22621 | `bb96e87306948dd74039e35d125e637ad7e6d6beb14cf87ee80fd96212a83308` |
| 去价包装器 | `deploy/patches-29z/strip-1688.sh` | 1730 | `0deea313611ff71a760baae5646bcd0252f1cbad5bdb85d0ab3e2953e112ee1e` |
| 九条判据脚本 | `deploy/verification-29z/scripts/29z-criteria.sh` | 7374 | `367644719940c0f5e97b37f9a6ae7620ca33e723c47950d1903a19ab05727762` |

包内 38 个文件 + 5 个目录 = 43 条目；`style.css` 的 `Version:` 与 `functions.php` 的 `PKV4_VERSION` 都是 `4.0.3`。
包里 `assets/img/hero.webp` = `6a55801599da73d2…`（1200×1200，33758 B），`hero-480.webp` = `266032c7bdfc0f51…`（480×480，8866 B）。

### D.2 装包（机器：本机 → 容器）

```bash
docker cp deploy/packrivo-home-v4.zip php:/pkg/packrivo-home-v4.zip
docker exec php sh -c 'sha256sum /pkg/packrivo-home-v4.zip'      # 必须是 7d792ad9…
docker exec php php -d disable_functions= /usr/local/bin/wp --path="$WP_PATH" --allow-root \
  theme install /pkg/packrivo-home-v4.zip --force --activate
docker exec php php -d disable_functions= /usr/local/bin/wp --path="$WP_PATH" --allow-root \
  theme list --fields=name,status,version | grep packrivo             # 期望：packrivo-home-v4 active 4.0.3
```

> 演练机进 php 容器时 `themes_api()` 会连 wordpress.org 报 “unexpected error”，那是容器没外网，
> 只要最后一行出现 `Success: Switched to 'Packrivo Home v4 (child)' theme.` 就是装上了。

### D.3 去价（机器：生产 SSH）

```bash
# D.3.0 先备份三张表（不是整库，回滚快）
$WPCLI db export /tmp/29z-pre.sql \
  --tables=wp_posts,wp_postmeta,wp_yoast_indexable
docker exec php sh -c 'wc -c /tmp/29z-pre.sql'         # 必须 > 0

# D.3.1 干跑（不写库），把输出整段留档
docker cp deploy/patches-29z/strip-1688.php php:/tmp/strip-1688.php
docker exec -e STRIP1688_DRY=1 php sh -c \
  "php -d disable_functions= /usr/local/bin/wp --path=$WP_PATH --allow-root eval-file /tmp/strip-1688.php"

# D.3.2 真跑
docker exec -e STRIP1688_DRY=0 -e STRIP1688_AUDIT=/tmp/audit-29z.json php sh -c \
  "php -d disable_functions= /usr/local/bin/wp --path=$WP_PATH --allow-root eval-file /tmp/strip-1688.php"
docker cp php:/tmp/audit-29z.json ./audit-29z.json     # 被删掉的句子全在里面，人工过一遍

# D.3.3 再跑一次，必须打印「改动合计 0」（幂等）
```

**它做什么**（按 post_content 的真实标记处理）：
1. 规格表里 `<th>Lowest listed tier / Historical 1688 reference</th>` 那一行整行删掉，原位换成 `<th scope="row">Price</th><td><a class="text-link" href="/get-a-quote/">Get a price →</a></td>`；
2. 分类 hub 页对照表里表头 `Lowest tier` 改名 `Price`，该列价格单元格换成同一个报价链接；
3. `What this tells you` 那个 `<h2>` 连同后面讲价格的那一段删掉（整段都是价格话时整段走，删了什么写进审计清单）；
4. 正文 `<p>/<li>` 里带 `¥ / yuan / RMB / CNY / ex-works / US$0.0 / 1688` 的**句子**逐句删（带内联标签的段落不动，留给人工）；
5. 208 个 catalog 页的 `_yoast_wpseo_metadesc` 与 `_yoast_wpseo_opengraph-description` 重写为「产品名 — 结构/材质. Custom logo printing, MOQ 500 per design. Samples available.」≤155 字符、两两不同，同时改 `wp_yoast_indexable` 的 `description` / `open_graph_description`；
6. `/products/` 正文里 `<p>Listed at ¥…</p>` 删掉；博客里 `detail.1688.com` 的 `<a>` 去掉链接，文字留 `a supplier size sheet (kept on file)`。

> **`wp eval-file` 会把 `--dry-run` 当成参数错误拦下来**，所以干跑开关走环境变量 `STRIP1688_DRY=1`，
> 或者用包装器 `bash deploy/patches-29z/strip-1688.sh --dry-run`。

### D.4 复测（九条判据，脚本自动打印 PASS/FAIL）

```bash
docker cp deploy/verification-29z/scripts/29z-criteria.sh php:/tmp/29z-criteria.sh
# 打补丁之前先取前态（必须在未打补丁的库上跑一次）
docker exec php sh /tmp/29z-criteria.sh before
# …… D.3 打完补丁之后
docker exec php sh /tmp/29z-criteria.sh compare
```

实测（演练库，seed 数据）**17 PASS / 1 FAIL**，唯一 FAIL 是「`/products/` 渲染哈希逐字节一致」：
`template-products-v4` 会把正文塞进页面里那段 `<script>` JSON，所以删掉 158 个价格 `<p>` 之后
渲染必然变（534303 → 522438 字节）。这条与「正文里 `¥` 必须归零」互斥，二者只能取一——
现在取的是归零（判据 8 两个 PASS）。

### D.5 死链与字体（4.0.3 里已改）

| 原来 | 现在 | 校验 |
|---|---|---|
| `/resources/`（导航 Resources、面板 Guides） | `/blog/` | 200 |
| `Packaging examples` 那个 `<li>` | 整条删除 | 全站 0 次引用 |
| `Price index` → `/pricing/` | `/get-a-quote/`，文案 `Price calculator` | 301→`/inquiry/` 200 |
| `Rigid gift boxes`（产品分组） | 整条删除 | 0 次引用 |
| 首页 `Materials and sustainability →` `/sustainability/` | `/customization/` | 200 |
| Inter woff2 路径 | 走 29w 补丁 `get_template_directory_uri()` | 200 `font/woff2` 34952 B |

`/resources/ /packaging-examples/ /pricing/ /rigid-gift-boxes/ /sustainability/` 五个都是 404，
所以删/换之后必须复测它们是 0 次引用。

### D.6 回滚

```bash
$WPCLI db import /tmp/29z-pre.sql        # 只回滚这三张表
$WPCLI cache flush
docker exec php php -d disable_functions= /usr/local/bin/wp --path="$WP_PATH" --allow-root \
  theme activate packrivo-static-bridge-1.2.38
```
判据：`SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%¥%'` 回到 209，
`/products/` 渲染哈希回到 `e2dc30ec…`（seed 库的值；生产回滚后要重新记哈希）。
