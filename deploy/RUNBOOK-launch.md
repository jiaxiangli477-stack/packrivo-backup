# 上线手册 · Packrivo 一次性上线（RUNBOOK）

> **上线以本文件为准。`deploy/README.md` 里按轮次堆的 27g…28q 记录只作历史参考，命令和顺序以这里为唯一口径。**
> 本文件不含任何密码、密钥或 token：wp-cli 走服务器上已有的 SSH/容器配置，Cloudflare 清缓存走本机 `E:\破甲工作区\i18n\.cf.env`（只引用路径，不抄值）。
>
> **28q 改版要点**（相对 28o）：生产站跑在 Docker 里，本手册所有命令都标了「宿主机 / 容器内」；`$BACKUP` 第 1 步定一次、写进 `CURRENT_LAUNCH`；备份路径与回滚路径一一对应；第 4、5 步按**首次安装**写（生产上子主题与报价插件都还不存在）；数据库备份落到 `$BACKUP/db.sql`。

- 目标站点：`https://www.packrivo.com`（**WordPress 7.1.3** + 父主题 `packrivo-static-bridge-1.2.38` + 子主题 `packrivo-home-v4` + 报价插件 `packrivo-quote` + mu-plugins `packrivo-i18n.php`、`packrivo-site-remediation-20261022.php`）
- 上线前生产现状（2026-10-28 实测）：活跃主题是父主题本身，**子主题与报价插件都未安装**，`/get-a-quote/` 是 mu 里那条老别名 301 到 `/inquiry/`
- 本轮上线内容：134 个产品页新标题/描述（27g）、8 个主页面（27i）、11 语种字典与路径表（27g/27i/28k/28m/28n/28o）、报价插件与子主题新包、两个 mu 补丁
- 预计总耗时：**1.5–2 小时**（含复测），建议在**访问量低**的时段做（北京时间 02:00–05:00 或周末）
- 每一步都能单独回滚；第 9 步是整体逆序回滚
- 任一步失败就停，按该步「回滚」恢复后再继续；不要跳过判据硬往前做

---

## 0 · 先读这里：宿主机 ↔ 容器

生产站不在宿主机上直接跑，而是 **Docker**：`nginx`（`nginx:alpine`）+ `php`（`kjlion/php:fpm-alpine`，PHP 8.3.31）+ `mysql` + `redis`。宿主机上**没有** `php`、**没有** `wp`。所以：

| 东西 | 宿主机（SSH 进来的那个 bash） | 容器 `php` 内 |
|---|---|---|
| WordPress 根 | `/home/web/html/www.packrivo.com/wordpress`（`$WP`） | `/var/www/html/www.packrivo.com/wordpress`（`$WPC`） |
| i18n 字典 | `$WP/wp-content/uploads/packrivo-i18n`（`$I18N`） | `$WPC/wp-content/uploads/packrivo-i18n` |
| 上传的包 | `/home/web/packrivo-packages`（`$PKG`） | `/pkg`（`$PKGC`，由 `docker cp` 灌进去） |
| 备份 | `/home/web/backups/<LAUNCH>`（`$BACKUP`） | **不可见**（需要时才 `docker cp` 进去/出来） |

三条硬规则：

1. **`wp` 只能在容器里跑**，且容器 `disable_functions` 含 `proc_open`，必须用 `-d disable_functions=` 覆盖，否则任何要起子进程的命令（`db export`、`plugin install`…）都会 `Cannot do 'Process::run'`。
2. **`php` 也只在容器里**（`php -l`、`merge-i18n.php`）。宿主机上的 `php` 一律写成 `docker exec php php …`。
3. **文件路径要看是谁在读写**：宿主机侧的 `cp/ls/sha256sum/curl` 用 `$WP`、`$PKG`、`$BACKUP`；容器侧的 `wp` 参数用 `$WPC`、`$PKGC`。`wp` **写出来的文件**（db 导出、rollback JSON）落在容器里，必须 `docker cp` 出来存进 `$BACKUP`。

### 变量（每开一个 SSH 会话都先执行这一块）

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
PKGC=/pkg
SITE=https://www.packrivo.com
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
# $BACKUP 不在这里生成：第 1 步定一次，之后永远从文件读
```

### 0.1 上线前检查（只读）

```bash
# 容器与版本
docker ps --format '{{.Names}}\t{{.Image}}' | grep -E '^(php|mysql|nginx|redis)\b'
$WPCLI core version                         # 期望 7.1.3
docker exec php php -v | head -1            # 期望 PHP 8.3.x
$WPCLI option get siteurl                   # 期望 https://www.packrivo.com
$WPCLI option get template                  # 期望 packrivo-static-bridge-1.2.38
$WPCLI option get stylesheet                # 期望 与上面相同（子主题未装）

# 首次安装的现状必须确认（这两条 greps 都要「没输出」）
$WPCLI theme list  --format=csv | grep '^packrivo-home-v4,' || echo "子主题未安装 → 首次安装"
$WPCLI plugin list --format=csv | grep '^packrivo-quote,'  || echo "报价插件未安装 → 首次安装"
$WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active

# 容器里要有 mysqldump，否则 wp db export 会失败（容器重建后会丢，所以每次上线都查一遍）
docker exec php sh -c 'command -v mysqldump || apk add --no-cache mariadb-client'
docker exec php mysqldump --version

# 包目录
mkdir -p "$PKG"
```

**通过判据**：`docker ps` 里的 php/mysql/nginx 都在；`core version` = `7.1.3`；PHP = `8.3.x`；`template` = `packrivo-static-bridge-1.2.38`；子主题与报价插件都「未安装」；`mysqldump --version` 有输出。
若子主题或报价插件**已经存在**，说明不是首次安装，**停下**，本手册的 4/5 步与 9 步不适用。

### 0.2 从本机上传（**本机**执行，只在宿主机落地，不进 web 目录）

| 文件（本机仓库 `E:\破甲工作区\packrivo-backup\` 下的路径） | 文件数 | 大小 | SHA-256 |
|---|---|---|---|
| `deploy/packrivo-home-v4.zip` | 38 | 790,164 B | `5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9` |
| `deploy/packrivo-quote.zip` | 19（`demo/` 不进包） | 54,581 B | `096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93` |
| `deploy/27e-quote-redirect.patch` | — | 647 B | 见第 6 步 |
| `deploy/patches-28m/packrivo-i18n-404-viewport.patch` | — | 1018 B | 见第 6 步 |
| `deploy/i18n-merged/`（11 份 `*-dict.json` + 11 份 `*-js.json` + `paths.json`） | 23 | — | 合并底稿 |
| `deploy/tools/merge-i18n.php` | 1 | — | 第 2 步合并工具 |
| `dsh-out/batch-01.json` … `batch-05.json`、`dsh-out/hubs-27i.json` | 6 | — | 第 3 步导入输入 |
| `scripts/product-buyers-27g/import.php` + `lib.php` | 2 | — | 第 3 步 |
| `scripts/product-buyers-27e/full-inventory.csv` + `products.json` + `third.json` + `fourth.json` | 4 | — | 第 3 步 `all134` 用 |
| `scripts/hubs-27i/import.php` | 1 | — | 第 3 步 |

```powershell
# 本机：按 RUNBOOK 的目录结构传到 $PKG（不要落在 web 可访问目录）
$P='E:\破甲工作区\packrivo-backup'
scp -r "$P\deploy\packrivo-home-v4.zip" "$P\deploy\packrivo-quote.zip" "$P\deploy\27e-quote-redirect.patch" netcup:/home/web/packrivo-packages/
scp -r "$P\deploy\patches-28m" "$P\deploy\i18n-merged" "$P\deploy\tools" netcup:/home/web/packrivo-packages/
scp -r "$P\dsh-out\batch-01.json" "$P\dsh-out\batch-02.json" "$P\dsh-out\batch-03.json" "$P\dsh-out\batch-04.json" "$P\dsh-out\batch-05.json" "$P\dsh-out\hubs-27i.json" netcup:/home/web/packrivo-packages/
scp -r "$P\scripts\product-buyers-27g" "$P\scripts\product-buyers-27e" "$P\scripts\hubs-27i" netcup:/home/web/packrivo-packages/scripts/
```

上传后在**宿主机**核对：

```bash
sha256sum "$PKG"/*.zip
ls "$PKG"/merged 2>/dev/null; ls "$PKG"/i18n-merged | wc -l      # 期望 23
```

---

## 1. 备份（机器：**生产 SSH（宿主机）**）

**预计 10 分钟。这一步不改任何站点文件。**

```bash
# 1.1 定下本次的 LAUNCH 名，写进 CURRENT_LAUNCH —— 之后所有会话都从这个文件读，不再用 $(date)
LAUNCH=packrivo-launch-$(date +%Y%m%d-%H%M)
mkdir -p /home/web/backups
echo "$LAUNCH" > /home/web/backups/CURRENT_LAUNCH
BACKUP=/home/web/backups/$LAUNCH
mkdir -p "$BACKUP"/{mu,i18n,db,rollback}

# 1.2 mu 与 i18n：用 "/." 摊平，保证 $BACKUP/mu/<文件名> 与 $BACKUP/i18n/<文件名>
cp -a "$WP/wp-content/mu-plugins/." "$BACKUP/mu/"
cp -a "$I18N/."                     "$BACKUP/i18n/"
sha256sum "$WP/wp-content/mu-plugins/packrivo-i18n.php" \
          "$WP/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" > "$BACKUP/before-mu.sha256"

# 1.3 数据库：wp 在容器里，导出文件先落容器 /tmp，再拉回宿主机
$WPCLI db export /tmp/packrivo-before-launch.sql --skip-plugins --skip-themes
docker cp php:/tmp/packrivo-before-launch.sql "$BACKUP/db.sql"
docker exec php rm -f /tmp/packrivo-before-launch.sql

# 1.4 现状快照（回滚时的对照基准）
$WPCLI plugin list --format=csv > "$BACKUP/before-plugin-list.csv"
$WPCLI theme  list --format=csv > "$BACKUP/before-theme-list.csv"
$WPCLI option get template   > "$BACKUP/before-template.txt"
$WPCLI option get stylesheet > "$BACKUP/before-stylesheet.txt"
$WPCLI post list --post_type=page --post_status=publish --fields=ID,post_name,post_title --format=csv > "$BACKUP/before-pages.csv"
curl -s "$SITE/sitemap_index.xml" > "$BACKUP/before-sitemap.xml"
curl -s "$SITE/robots.txt"        > "$BACKUP/before-robots.txt"

# 1.5 首次安装的书面记录（第 9 步、以及事后审计都看这一份）
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

**通过判据（逐条机检）**

```bash
test -s /home/web/backups/CURRENT_LAUNCH && echo "CURRENT_LAUNCH ok"
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
test -f "$BACKUP/mu/packrivo-i18n.php"                       && echo "1a mu 文件到位"
test -f "$BACKUP/mu/packrivo-site-remediation-20261022.php"   && echo "1b mu 文件到位"
test -f "$BACKUP/i18n/de-dict.json"                           && echo "1c i18n 文件到位"
[ "$(ls "$BACKUP/i18n" | wc -l)" -ge 12 ]                     && echo "1d i18n 数量 ≥12"
test -s "$BACKUP/db.sql"                                      && echo "1e db.sql 非空"
[ "$(grep -c 'CREATE TABLE' "$BACKUP/db.sql")" -gt 0 ]        && echo "1f db.sql 含 CREATE TABLE"
[ "$(wc -l < "$BACKUP/before-mu.sha256")" -eq 2 ]             && echo "1g 两个 mu 哈希"
test -s "$BACKUP/before-sitemap.xml"                          && echo "1h sitemap 快照非空"
grep -q 'child_theme_present=no' "$BACKUP/INSTALL-STATE.txt"  && echo "1i 首装状态已记录"
```

全都打印出来才算过。**备份只留在服务器这台机器上，不下载到本机。**

**回滚**：本步不产生变更。上线确认成功后再删 `$BACKUP`（删之前先确认第 9 步不再需要）。

---

## 2. 字典与路径表（机器：**生产 SSH**；底稿与脚本第 0.2 步已上传）

**预计 20–30 分钟（含人工看冲突清单）。这是整个上线里唯一不能自动判定的一步。**

原则：**以当天新读的生产字典为底**，把本轮的 134 产品页、8 个主页面、60 条界面词、28k 的 207 条、28m 的报价页、28n 的 `Instant quote` / `Sample kit` 一起合并；**同一键两边都有值时生产优先**，冲突清单必须人工看过再装。

`merge-i18n.php` 要用 PHP 跑，而 PHP 只在容器里 → 先把包和当天字典推进容器，跑完再把结果拉回宿主机。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 2.1 当天生产字典快照（落在宿主机，平铺）
cp -a "$I18N" "$BACKUP/i18n-today"
ls "$BACKUP/i18n-today" | head
grep -c '"' "$BACKUP/i18n-today/de-dict.json"        # 粗略确认不是空文件

# 2.2 包与字典推进容器（$PKG 在宿主机，容器看不见）
docker exec php mkdir -p "$PKGC"
docker cp "$PKG/." php:"$PKGC/"
docker cp "$BACKUP/i18n-today" php:/bk-i18n-today      # 合并的 --prod 输入

# 2.3 合并（生产优先、冲突写 conflicts.json）
docker exec php mkdir -p /pkg/merged
docker exec php php "$PKGC/tools/merge-i18n.php" \
    --prod=/bk-i18n-today \
    --local="$PKGC/i18n-merged" \
    --out="$PKGC/merged" \
    --require=/get-a-quote/

# 2.4 结果拉回宿主机，人工看冲突清单
mkdir -p "$PKG/merged"
docker cp php:"$PKGC/merged/." "$PKG/merged/"
cat "$PKG/merged/conflicts.json"

# 2.5 校验（容器内跑，路径都在容器侧）
docker exec php sh -c 'ls /pkg/merged/*-dict.json | wc -l'          # 期望 11
docker exec php sh -c 'ls /pkg/merged/*-js.json   | wc -l'          # 期望 11
docker exec php sh -c 'for f in /pkg/merged/*.json; do php -r "json_decode(file_get_contents(\$argv[1]),true) or exit(1);" "$f" || echo "坏 JSON: $f"; done'
docker exec php sh -c 'grep -q "\"/get-a-quote/\"" /pkg/merged/paths.json && echo "paths ok"'
docker exec php sh -c 'php -r "\$p=json_decode(file_get_contents(\"/pkg/merged/paths.json\"),true); exit((is_array(\$p)&&count(\$p)>=146)?0:1);" && echo "paths count ok"'

# 2.6 装上去（宿主机侧，只覆盖同名文件；conflicts.json 不要拷进 i18n）
cp "$PKG/merged"/*-dict.json "$I18N/"
cp "$PKG/merged"/*-js.json   "$I18N/"
cp "$PKG/merged/paths.json"  "$I18N/"
ls -l "$I18N" | head
```

**通过判据**

- `*-dict.json` = **11**、`*-js.json` = **11**；
- 每个 JSON 都能 `json_decode`，没有「坏 JSON」行；
- `paths.json` 含 `"/get-a-quote/"`，条目数 ≥ 146；
- `conflicts.json` 已人工逐条看过：凡是被生产值覆盖的键，都是我们**这次不打算改**的；
- 装完后 `/de/` 仍正常：`curl -s "$SITE/de/" | grep -c 'lang="de"'` ≥ 1。

**回滚**（宿主机侧，按文件逐个覆盖回去，绝不整目录塞进去）：

```bash
cp -a "$BACKUP/i18n/." "$I18N/"
```

---

## 3. 内容：134 个产品页 + 8 个主页面（机器：**生产 SSH**）

**预计 15–25 分钟（134 页批量导入）。**

导入脚本是 `wp eval-file` 形式，命令形态固定为 `<plan|apply|verify|rollback-check|rollback> <package-root> <expected-site> <rollback-json> [all134]`；`package-root` 与 `rollback-json` 都是**容器内路径**（`$PKGC`、`$PKGC/rollback/`），因为解释执行这个脚本的是容器里的 PHP。

- `plan` = dry-run，只读不写（27g 打印 `input_pages=134 preflight=PASS writes=0`，27i 打印 `hub_pages=8`）；
- `apply` = 首次在生产上**独占创建** rollback 文件（写入失败就不动数据库），再开数据库事务写入；站点地址、输入哈希、正文漂移任一不符都会中止；
- 27g 生产必须用 `all134` 范围（130 新页 + 27e 的 4 页，同一备份同一事务）。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
docker exec php mkdir -p "$PKGC/rollback"          # 2.2 已把包灌进容器

# 3.1 dry-run
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"

# 3.2 真跑，跑完立刻把 rollback 文件拉回宿主机存进 $BACKUP
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
docker cp php:"$PKGC/rollback/." "$BACKUP/rollback/"
ls -l "$BACKUP/rollback/"
```

**通过判据**

- `plan` 输出 `input_pages=134 preflight=PASS writes=0` 与 `hub_pages=8 preflight=PASS`；
- `apply` 输出 `apply_updated=134` / `apply_updated=8`（重复执行时 `already_current` 计入总数）；
- `verify` 输出 `metadata_mismatches=0`（27i 还要求 `H1_body_URL_unchanged=8/8 inquiry_description_preserved=yes`）；
- `$BACKUP/rollback/` 里两个 JSON 都在（27g 约 1.2 MB，含全部原值）：
  ```bash
  test -s "$BACKUP/rollback/rollback-27g.json" && echo "27g rollback ok"
  test -s "$BACKUP/rollback/rollback-27i.json" && echo "27i rollback ok"
  ```
- 抽 3 页核对新标题（每页 `grep -c '<title>'` = 1）：
  ```bash
  curl -s "$SITE/product-drawer-jewelry-box/" | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/products/"                   | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/jewelry-boxes/"              | grep -o '<title>[^<]*</title>'
  ```

**回滚**（先把宿主机那份 rollback 文件推回容器，再 rollback-check → rollback）：

```bash
docker cp "$BACKUP/rollback/rollback-27i.json" php:"$PKGC/rollback/"
docker cp "$BACKUP/rollback/rollback-27g.json" php:"$PKGC/rollback/"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
```

---

## 4. 报价插件（**首次安装**，机器：**生产 SSH**）

**预计 5 分钟。** 生产上现在没有 `packrivo-quote`，所以这是新装，不是覆盖。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
docker cp "$PKG/packrivo-quote.zip" php:"$PKGC/packrivo-quote.zip"
docker exec php sha256sum "$PKGC/packrivo-quote.zip"      # 必须 096d0c8e…

$WPCLI plugin install "$PKGC/packrivo-quote.zip" --force
$WPCLI plugin activate packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote     # 状态必须是 active
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"         # 200
```

**通过判据**

- `plugin list` 里 `packrivo-quote` 是 `active`；
- 站点 200；
- 此时 `/get-a-quote/` 仍会被 mu 里那条老 301 顶到 `/inquiry/`（第 6 步才修），**这是预期的空档期**，不算失败；
- 价格只有第 6 步打完补丁后才能验（8.2）。

**回滚**（两件事分开写，不用 `&&` 串：删掉了就回不来）：

```bash
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除（回到首装前状态）"
```

---

## 5. 子主题（**首次安装**，机器：**生产 SSH**）

**预计 5 分钟。**

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 5.1 先确认父主题没被换掉
$WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active     # 必须 1.2.38

# 5.2 装 + 启用
docker cp "$PKG/packrivo-home-v4.zip" php:"$PKGC/packrivo-home-v4.zip"
docker exec php sha256sum "$PKGC/packrivo-home-v4.zip"    # 必须 5974ae0c…
$WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
$WPCLI theme activate packrivo-home-v4

# 5.3 核对
$WPCLI option get stylesheet      # 期望 packrivo-home-v4
$WPCLI option get template        # 期望 packrivo-static-bridge-1.2.38
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"      # 200
```

**通过判据**

- 父主题仍是 `packrivo-static-bridge-1.2.38`（若变了**停下**，先确认父子兼容）；
- `option get stylesheet` = `packrivo-home-v4`，`option get template` = 父主题目录名；
- 首页 200。

**回滚**（先把主题切回父主题，再删目录；两条分开写）：

```bash
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除（回到首装前状态）"
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"
```

---

## 6. mu 补丁（机器：**生产 SSH**）

**预计 10 分钟。两个补丁落在不同文件上，各自独立回滚。顺序固定：先 `27e-quote-redirect.patch`，再 `packrivo-i18n-404-viewport.patch`。**

`git apply` 在宿主机跑（宿主机有 git），`php -l` 在容器里跑。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 6.1 27e：把 get-a-quote 从旧别名列表里删掉（只删这一项）
cd "$WP/wp-content/mu-plugins"
sha256sum packrivo-site-remediation-20261022.php
#   生产基线: ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d
#   与基线不一致就先 diff 生产文件与本机基线，确认改动范围再决定，不要盲目打补丁
git apply -p1 --check "$PKG/27e-quote-redirect.patch" && echo CHECK-OK
git apply -p1         "$PKG/27e-quote-redirect.patch"
docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php"

# 6.2 404 viewport：给「本页尚未翻译」的 404 补 <meta name="viewport">
cd "$WP"                      # 这个补丁的路径前缀是 wp-content/mu-plugins/，要在 WP 根执行
sha256sum wp-content/mu-plugins/packrivo-i18n.php
git apply -p1 --check "$PKG/patches-28m/packrivo-i18n-404-viewport.patch" && echo CHECK-OK
git apply -p1         "$PKG/patches-28m/packrivo-i18n-404-viewport.patch"
docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-i18n.php"
```

**通过判据**

- 6.1 的 `sha256sum` 与 `ac0a2753…` 一致；两个 `git apply --check` 都 `CHECK-OK`（失败**不要强打**）；
- 两个 `php -l` 都 `No syntax errors detected`；
- `/get-a-quote/` 不再 301：
  ```bash
  curl -sI "$SITE/get-a-quote/"    | head -3      # 期望 200
  curl -sI "$SITE/de/get-a-quote/" | head -3      # 期望 200
  ```
- 语种 404 有 viewport：
  ```bash
  curl -s "$SITE/ar/2026/10/26/jewelry-pouch-box-customer-handoff/" | grep -c 'name="viewport"'   # 期望 1
  ```

**回滚**（两个独立，读的是第 1 步摊平后的 `$BACKUP/mu/<文件名>`）：

```bash
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"   # 回滚 27e
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"   # 回滚 404 补丁
sha256sum -c "$BACKUP/before-mu.sha256"
```

---

## 7. 清缓存（机器：**本机**（Cloudflare）+ **生产 SSH**（transient））

**预计 5 分钟。**

> **Cloudflare 这一段：负责人亲自执行。** 凭据只存在 `E:\破甲工作区\i18n\.cf.env`，本手册只引用路径、不写任何值；执行时不要把文件内容 `cat`/`echo` 出来，也不要把 token 贴进聊天、工单或日志。

```bash
# 本机（负责人亲自执行）：Cloudflare 全站 purge + 12 路径 × 11 语种点名清 + 自动校验
python E:\破甲工作区\i18n\cf_purge.py

# 生产 SSH（宿主机）：清报价插件的 12h pricing transient
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"     # 有对象缓存插件时生效，没有就如实记录
```

**通过判据**

- `cf_purge.py` 结尾输出 `仍过期: 无 —— 全部同步`；
- `curl -sI "$SITE/de/" | head -5` 返回 200；
- 页面语言标记正确（`<html lang="de">`）。

**回滚**：缓存清了不需要回滚。

---

## 8. 上线后复测清单（机器：**本机浏览器** + **生产 SSH** 抽查）

**预计 20–30 分钟。逐条照做，任何一条不过就按对应步回滚，不要「先上着」。**

| # | 检查项 | 怎么做 | 通过判据 |
|---|---|---|---|
| 8.1 | title / H1 / 描述（首页、`/products/`、1 个产品页 × en / ar / de） | 浏览器打开 9 个 URL，看标签页标题、页面 H1、右键查看 `<meta name="description">` | 三种语种都是新的、各自语言的标题；H1 与标题不冲突；描述非空 |
| 8.2 | 报价价格 | 浏览器打开 `/get-a-quote/?style=rigid&qty=500` 和 `/ar/get-a-quote/` | 单价 **$2.83**、合计 **$1,417.43**（mailer $2.48/$1,239.04、sticker $0.96/$479.63、shipping $2.31/$1,156.69 同样逐格一致） |
| 8.3 | 询盘页 | `/inquiry/` 和 `/inquiry/?product=product-drawer-jewelry-box` | 字段齐全（姓名、邮箱、数量、产品）；带 `?product=` 时产品已预选；**只读不提交** |
| 8.4 | 横向溢出 | 375 / 768 / 1024 三个宽度各看首页、`/products/`、1 个产品页、`/get-a-quote/`、`/ar/` | 都没有横向滚动条（`document.documentElement.scrollWidth` = 视口宽）；28p 起 768/1024 也在范围内 |
| 8.5 | 每页 1 个 title | `curl -s <URL> \| grep -c '<title>'`，抽 5 个 URL | 每个都是 **1** |
| 8.6 | sitemap / robots 没变 | `curl -s "$SITE/sitemap_index.xml"`、`robots.txt`，与第 1 步的 `before-*.xml/txt` diff | sitemap 仍有语种子图；`robots.txt` 与上线前逐字一致 |
| 8.7 | 语种报价按钮 | en / ar / de / ja 首页与 1 个产品页，看页头与页脚的报价按钮 | 文字是各自语言（`عرض سعر فوري` / `Sofortangebot` / `すぐに見積もり`），链接都指向 `/{lang}/get-a-quote/` |

固定命令部分：

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 8.5 每页 1 个 title（抽 5 个 URL）
for u in "$SITE/" "$SITE/products/" "$SITE/de/" "$SITE/ar/" "$SITE/product-drawer-jewelry-box/"; do
  echo -n "$u  title数="; curl -s "$u" | grep -c '<title>'
done

# 8.6 sitemap / robots 与第 1 步快照对比
diff "$BACKUP/before-sitemap.xml" <(curl -s "$SITE/sitemap_index.xml") && echo "sitemap 未变"
diff "$BACKUP/before-robots.txt"  <(curl -s "$SITE/robots.txt")       && echo "robots 未变"
```

**回滚**：本步是纯检查；哪条不过就回滚对应步——8.1/8.5/8.6 → 第 3、2 步；8.2/8.7 → 第 4、6 步；8.3 → 第 5 步；8.4 → 第 5、6 步。

---

## 9. 整体回滚（逆序，机器：**生产 SSH**）

**预计 15 分钟。开局先取回本次 LAUNCH，不要重新生成。**

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
PKGC=/pkg
SITE=https://www.packrivo.com
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)     # ← 固定来源，绝不用 $(date) 重算
BACKUP=/home/web/backups/$LAUNCH
echo "回滚目标: $BACKUP"; test -f "$BACKUP/db.sql" && echo "备份在位"
```

```bash
# 9.7 缓存：无需回滚（重新生成即可）；想立即回旧内容就先清一次
#     python E:\破甲工作区\i18n\cf_purge.py      ← 本机，负责人亲自
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"

# 9.6 两个 mu 补丁（读 $BACKUP/mu/<文件名>，摊平后的路径）
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"

# 9.5 子主题：先切回父主题，再删（两条分开，不用 &&）
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除"

# 9.4 报价插件：先停用，再删
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除"

# 9.3 内容（134 产品页 + 8 主页面，先 rollback-check 再 rollback）
docker cp "$BACKUP/rollback/rollback-27i.json" php:"$PKGC/rollback/"
docker cp "$BACKUP/rollback/rollback-27g.json" php:"$PKGC/rollback/"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134

# 9.2 字典与路径表（按文件覆盖回去）
cp -a "$BACKUP/i18n/." "$I18N/"
```

最后再跑一遍 8.1 / 8.2 / 8.6，确认回到上线前状态。

**判据**：`sha256sum -c "$BACKUP/before-mu.sha256"` 全 OK；`option get stylesheet` 回到 `packrivo-static-bridge-1.2.38`；`plugin list | grep packrivo-quote` 无输出；27g/27i `rollback-check` 输出 `rollback_eligible` 且 rollback 后 `verify` 的 `metadata_mismatches=0`；8.1/8.2/8.6 与上线前一致。

**最后手段**（分步回滚都不灵时；数据库回滚会丢掉上线后新增的询盘，用之前先确认这段时间没有新数据）：

```bash
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
$WPCLI db import /tmp/db.sql
docker exec php rm -f /tmp/db.sql
```

---

## 附录 A · 已知问题（不在本次上线范围）

> **28p 已修**：页头在 768 px 和 1024 px 的横向溢出。做法是把汉堡断点统一到实测得到的 W=1294（12 语种桌面页头右侧留 16 px 余量放得下的最大宽度），1024 及以下全语种进汉堡；768 那一档的溢出其实在页脚底部（`.footer-bottom` 在 768–940 之间不换行）。验证见 `deploy/verification-28p/`。

| 问题 | 实测 | 影响 | 何时处理 |
|---|---|---|---|
| `/ar/<文章路径>/` 返回 404 | 页面 `noindex,follow` 并链到英文版 | 阿语站内文章入口只能跳英文 | 等文章翻译排期 |
| `/contact/` 301 到 `/inquiry/` | 生产与本地一致 | 不构成重复标题页 | 若要做独立联系页再说 |
| 语种页副链接在 1440–1780 px 间按语种隐藏 | `.pk-actions .pk-text-link` 的按语种媒体查询 | 副链接在这些宽度不显示（不影响报价按钮） | 已按实测调过阈值，属预期行为 |

## 附录 B · 时间与顺序速查

| 步 | 内容 | 机器 | 预计 | 是否可跳过 |
|---|---|---|---|---|
| 0 | 环境检查 + 上传包 | 生产 SSH + 本机 | 10 min | 不可 |
| 1 | 备份（含 db.sql） | 生产 SSH（宿主机） | 10 min | 不可 |
| 2 | 字典 + 路径表 | 生产 SSH（容器里跑合并） | 20–30 min | 不可（本轮主体） |
| 3 | 134 产品页 + 8 主页面 | 生产 SSH（容器里 eval-file） | 15–25 min | 不可（SEO 主体） |
| 4 | 报价插件（首装） | 生产 SSH | 5 min | 可（若报价插件不变） |
| 5 | 子主题（首装） | 生产 SSH | 5 min | 可（若只改内容） |
| 6 | 两个 mu 补丁 | 生产 SSH | 10 min | 27e 不可省（否则报价按钮全跳询盘） |
| 7 | 清缓存 | 本机 + 生产 SSH（Cloudflare 由负责人亲自执行） | 5 min | 不可 |
| 8 | 复测 | 本机浏览器 + 生产 SSH | 20–30 min | 不可 |
| 9 | 整体回滚 | 生产 SSH（清缓存在本机） | 15 min | 只在出问题时执行 |

## 附录 C · 28q 本地演练

本手册在 2026-10-28 于本地按「生产结构」完整演练过：WP 7.1.3 + PHP 8.3、`php` 容器、`/home/web/...` 宿主机目录、子主题与报价插件**未安装**的初始状态。演练记录（正向 → 整体回滚 → 再正向，逐条命令与输出）见：

- `deploy/verification-28q/rehearsal.log`
- `deploy/verification-28q/rehearsal-verify.json`
