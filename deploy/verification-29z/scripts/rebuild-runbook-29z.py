# -*- coding: utf-8 -*-
"""按负责人 29z 复核意见重建 RUNBOOK：
以 c62bc88 的版本为底稿，只做四件事：
  1) 两处 zip 哈希/字节数 49ddbe6f/796,514 -> 7d792ad9/791,053
  2) 正文插入 6.5 字体路径补丁（29w），带 sha256 与回滚
  3) 末尾追加 附录 D · 29z
  4) 自检：行数/代码块行数只增不减、无 U+FFFD、5.2 的 theme install 独占一行
写入一律 UTF-8 无 BOM、newline=''（换行原样保留）。
"""
import io
import os
import re
import subprocess
import sys

REPO = r"E:\破甲工作区\packrivo-backup"
TARGET = os.path.join(REPO, "deploy", "RUNBOOK-launch.md")
BASE_REV = "c62bc88"

OLD_HASH = "49ddbe6f1848419d2add0d801a0bf41817dbbab891b4747bcd629fdfc3a0599e"
NEW_HASH = "3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6"


def base_text():
    return subprocess.run(
        ["git", "-C", REPO, "show", BASE_REV + ":deploy/RUNBOOK-launch.md"],
        capture_output=True, check=True,
    ).stdout.decode("utf-8")


FONT_SECTION = """## 6.5 · 字体路径补丁（29w，机器：**生产 SSH**）

29w 做了这个补丁但当时没写进本手册，29z §3c 才发现漏项。补丁把 Inter woff2 的 URL 从
`get_stylesheet_directory_uri()` 换成 `get_template_directory_uri()`：字体文件在**父主题**
`packrivo-static-bridge-<ver>/packrivo-static/assets/packrivo-inter-latin-v3.woff2`（34,952 B），
子主题里没有这份文件，所以用 stylesheet 目录会 404。

**先看现状再决定打不打**（29z 实测生产返回 200，走的是子主题路径 + 缓存；补丁是把这条链钉死）：

```bash
CURL="curl -sS --noproxy '*' --resolve www.packrivo.com:443:127.0.0.1"   # 直连源站，不读 Cloudflare 缓存
$CURL https://www.packrivo.com/ | grep -o 'https://[^"]*packrivo-inter-latin-v3\\.woff2' | head -2
$CURL -o /dev/null -w 'code=%{{http_code}} type=%{{content_type}} size=%{{size_download}}\\n' \\
  https://www.packrivo.com/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2
```

**打补丁**（幂等：第二次 dry-run 会报 already applied）：

```bash
sha256sum "$PKG/packrivo-wave2-font-path.patch"   # 必须 022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d
docker cp "$PKG/packrivo-wave2-font-path.patch" php:/tmp/font-path.patch
docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w"
docker exec php sh -c "cd $WPC/wp-content/mu-plugins && patch -p1 --dry-run < /tmp/font-path.patch"
docker exec php sh -c "cd $WPC/wp-content/mu-plugins && patch -p1 < /tmp/font-path.patch"
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # 打过以后 73d43ba0262a9d74249b19a46380bc5c338f94bd9e0776baa46eddf074c9f259
```

**判据**：`grep -n 'PACKRIVO_W2_FONT_URL' packrivo-wave2.php` 里那行变成 `get_template_directory_uri()`；
首页 HTML 里的字体 URL 指向 `/wp-content/themes/packrivo-static-bridge-<ver>/packrivo-static/assets/...woff2`；
`$CURL -I` 该 URL 返回 **200** 且 `content-type: font/woff2`、`content-length: 34952`。

**回滚**：`docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w $WPC/wp-content/mu-plugins/packrivo-wave2.php"`，
再 `$WPCLI cache flush`。

"""

APPENDIX_D = """
---

## 附录 D · 29z 上线顺序（撤 1688 进货价 + 4.0.4 包 + 字体补丁），2026-10-30

> 演练环境用**生产现状库**（`29z-prod.sql`：208 个 catalog 页、209 条正文含 `1688`、209 条含 `¥`）按这个顺序
> 从头跑到尾，跑完按 D.6 回滚（判据见 D.6 第 4 步），再回到 D.2 跑第二次，三遍都通过。
> **本附录就是执行顺序表：从上往下敲，不要跳步，不要并行。**
>
> 记号：`【本机】` 在你电脑上（仓库根目录）；`【宿主】` 生产宿主机 SSH 里的 shell；`【容器】` 已经进了
> `docker exec php`（不用再套一层）。`⛳` 是判据，不通过就停，不要往下走。
>
> **一条硬规矩：宿主机上每一次 HTTP 访问都必须直连源站（`$CURL`），不许直接 `curl https://www.packrivo.com`。**
> 那个域名挂在 Cloudflare 后面，直接请求会读到缓存里的旧页面：装完 4.0.4 看不见 `Example quote`、
> 回滚比对首页哈希也对不上，人会卡在半路误判。改前、改后、回滚三处用的必须是同一种直连方式。

### D.0 变量、直连探测、预检

【宿主】1) 一次性把变量钉好，**把 `$BACKUP` 记下来**（回滚只认它）：

```bash
PKG=/root/29z-pkg
PKGC=/pkg
BACKUP=/home/web/backups/29z-$(date -u +%Y%m%d-%H%M%S)

SITE=https://www.packrivo.com
CURL="curl -sS -k --noproxy '*' --resolve www.packrivo.com:443:127.0.0.1"   # -k：源站是 CloudFlare Origin CA 证书，本机不认
ORIGIN_IP=$(docker exec php sh -c "ip route | awk '/default/ {{print $3}}'")   # php 容器里的宿主网关 IP，容器直连源站用
CURLX="--resolve www.packrivo.com:443:$ORIGIN_IP -k"   # 给容器里的 29z-criteria.sh（它解析不到 nginx 这个名字）
echo "ORIGIN_IP=$ORIGIN_IP"

echo "$BACKUP" | tee /root/29z-backup-path.txt
```

【宿主】2) 建目录（宿主机 `$PKG`、`$BACKUP`，容器内 `$PKGC`）。**D.1.1 的 `scp` 依赖这里先做完**：

```bash
mkdir -p "$PKG" "$BACKUP/29z"
docker exec php mkdir -p "$PKGC"
```

【宿主】3) 后面反复用的两行：

```bash
WPC=/var/www/html/www.packrivo.com/wordpress
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
```

【宿主】4) **直连源站探测**（不通就停，别急着装包）：

```bash
echo '--- 1) 响应头里有没有 Cloudflare 标记 ---'
$CURL -I "$SITE/" | tee /tmp/29z-probe-head.txt
if grep -qi 'cf-ray\|cf-cache-status' /tmp/29z-probe-head.txt; then echo 'FAIL 还在走 Cloudflare'; else echo 'OK 无 cf-ray / cf-cache-status'; fi

echo '--- 2) 连取两次首页，哈希必须相同 ---'
$CURL "$SITE/" -o /tmp/29z-probe1.html
$CURL "$SITE/" -o /tmp/29z-probe2.html
sha256sum /tmp/29z-probe1.html /tmp/29z-probe2.html
grep -c '<html' /tmp/29z-probe1.html
```

⛳ 三条同时成立才继续：响应头**没有** `cf-ray` / `cf-cache-status`；两个哈希**相同**；`<html` 计数 ≥ 1。

不通过就停，对号入座（四种，都实测过）：

| 现象 | 原因 | 处置 |
|---|---|---|
| 头里有 `cf-ray` / `cf-cache-status`，或直接 `503 Service Unavailable` + `Proxy-Connection: close` | 本机（Windows / macOS）的**系统代理**被 curl 拿走了，`--resolve` 发挥不了作用；实测 Windows 上 `ProxyEnable=1 / ProxyServer=127.0.0.1:10808` 就会这样 | `CURL` 默认已带 `--noproxy '*'`；若仍不行再 `env \| grep -i proxy` 看环境变量 |
| TLS 报 `unable to get local issuer certificate`（curl 60） | 源站挂的是 **CloudFlare Origin CA** 证书（issuer 写着 CloudFlare Origin SSL Certificate Authority），本机和容器的 CA 库都没有它的根 | `CURL` 默认已带 `-k`（只跳过证书校验，仍直连 127.0.0.1） |
| 两次哈希不同 | 页面里有随机串（nonce / 时间戳 / AB 变体） | `diff /tmp/29z-probe1.html /tmp/29z-probe2.html` 定位；若随机串在 `<script>` 的 `nonce=` 里，比对前先 `sed -E 's/nonce="[^"]*"//g'`；仍不同就改用 `/products/` 当基准页，并在 D.6 第 4 步同步改 |
| `Connection refused` | 源站 443 没开 | `CURL="curl -sS --resolve www.packrivo.com:80:127.0.0.1"`、`SITE=http://www.packrivo.com`；若 nginx 又 301 到 https，说明只能走 443，回头查 nginx 监听 |

演练环境等价写法（把端口指到演练 nginx，写法保持一致）：

```bash
SITE=http://www.packrivo.com:8091
CURL="curl -sS --noproxy '*' --resolve www.packrivo.com:8091:127.0.0.1"
```

【宿主】5) 只读预检：

```bash
$WPCLI theme list --fields=name,status,version | grep packrivo
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_type='page' AND post_status='publish' AND post_name LIKE 'catalog-%'"
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%1688%'"
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')"
```

⛳ 期望 `packrivo-home-v4 active 4.0.1`、catalog `208`、含 `1688` 的 publish `209`、含 `¥` 的 publish `209`。
不是这三个数就**别往下走**——生产已经变过，先回 §0 重新取基线。

### D.1 锁定物与上传（先核对哈希，再上传）

**D.1.0 锁定物**（改一个字就重跑本节，别手工改表）：

| 物 | 路径 | 字节 | sha256 |
|---|---|---|---|
| 子主题包 4.0.4 | `deploy/packrivo-home-v4.zip` | {ZIP_SIZE} | `{ZIP}` |
| 去价脚本 | `deploy/patches-29z/strip-1688.php` | {PHP_SIZE} | `{PHP_HASH}` |
| 去价包装器 | `deploy/patches-29z/strip-1688.sh` | {SH_SIZE} | `{SH_HASH}` |
| 九条判据脚本 | `deploy/verification-29z/scripts/29z-criteria.sh` | {CRIT_SIZE} | `{CRIT_HASH}` |
| 字体路径补丁 | `deploy/patches-29w/packrivo-wave2-font-path.patch` | 756 | `022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d` |
| 字体补丁 PHP 兜底（容器没 `patch` 时用） | `deploy/patches-29w/apply-font-patch.php` | {PF_SIZE} | `{PF_HASH}` |

包内 38 文件 + 5 目录 = 43 条目；`style.css` 与 `functions.php` 都是 `4.0.4`；
包内 `assets/img/hero.webp` = `6a55801599da73d2…`（1200×1200，33,758 B）、`hero-480.webp` = `266032c7bdfc0f51…`（480×480，8,866 B）。
`front-page.php` 里的首页说明卡片文案是 `Example quote · Rigid magnetic box · 9.5×9.5×4 cm`（价格仍由报价引擎算，没动数字）。

【本机】D.1.1 上传这 **6** 个文件（表里就这 6 个，少一个都别装）。
路径写死 `netcup:/root/29z-pkg/`：`$PKG` 是**宿主机**上的变量，本机 shell 里没有，
写成 `netcup:$PKG/` 会把文件丢到服务器根目录。所以 **D.0 第 2 步的 `mkdir -p "$PKG"` 必须先做完**。

```bash
scp deploy/packrivo-home-v4.zip                       netcup:/root/29z-pkg/
scp deploy/patches-29z/strip-1688.php                 netcup:/root/29z-pkg/
scp deploy/patches-29z/strip-1688.sh                  netcup:/root/29z-pkg/
scp deploy/patches-29w/packrivo-wave2-font-path.patch netcup:/root/29z-pkg/
scp deploy/patches-29w/apply-font-patch.php           netcup:/root/29z-pkg/
scp deploy/verification-29z/scripts/29z-criteria.sh   netcup:/root/29z-pkg/
```

【宿主】D.1.2 核对上传物：

```bash
cd "$PKG" && sha256sum packrivo-home-v4.zip strip-1688.php strip-1688.sh packrivo-wave2-font-path.patch apply-font-patch.php 29z-criteria.sh
```

⛳ 与 D.1.0 表逐个相同；`packrivo-home-v4.zip` 必须是 {ZIP_SIZE} 字节。

### D.1b 前态快照（判据 4 与回滚判据都要它）

【宿主】1) 判据脚本进容器 + 取前态（首页走 `$CURL` 直连）：

```bash
docker cp "$PKG/29z-criteria.sh" php:/tmp/29z-criteria.sh
docker exec php sh -c 'mkdir -p /tmp/29z-snap/shots'
docker exec -e SITE="$SITE" -e CURLX="$CURLX" php sh /tmp/29z-criteria.sh before
$CURL "$SITE/" -o "$BACKUP/29z/home-before.html"
sha256sum "$BACKUP/29z/home-before.html" | tee "$BACKUP/29z/home-before.sha256"
```

⛳ `home-before.sha256` 有值；容器里出现 `/tmp/29z-snap/`（pages-before / meta-before / index-before / products-before），且 `products-before.html` **不是 0 字节**、pages/meta/index 三个快照也不是空的——空就说明容器没真拿到页面，后面的 4 / 8 / 9 会假 PASS。生产上实测踩过这个坑：php 容器里 `nginx` 解析不到（`http://nginx/` 报 curl(6)）。
刚才 D.0 第 4 步探测过「两次哈希相同」，所以这个值以后可以直接拿来比。

【本机】2) `/products/` 的「改前」截图（**必须在装包之前拍**，本机直接打生产域名，**不要 `docker cp` 进容器**）：

```bash
node deploy/verification-29z/scripts/29z-products-shots.mjs deploy/verification-29z/evidence/products-shots before https://www.packrivo.com
```

⛳ 两张图（1440 / 375）都 `status 200`、`cards` 都是 24、`overflow` false；`shots-before.json` 落盘。
演练环境：把 base 换成演练源站 `http://www.packrivo.com:8091`，并加 `SHOTS_MAP=www.packrivo.com`（Chrome 按域名钉 127.0.0.1、关掉系统代理），写法与生产等价。
截图不放进容器——本机的图 `docker cp` 只会进**演练容器**，生产容器里根本没有，判据也不该依赖它。

### D.1c 备份（三样，全落宿主机 `$BACKUP`，回滚只从这里取）

【宿主】1) 生产现役子主题目录（4.0.1）——**回滚的关键，别用 `theme activate` 父主题**：

```bash
docker cp -a php:"$WPC/wp-content/themes/packrivo-home-v4" "$BACKUP/themes-packrivo-home-v4-4.0.1"
find "$BACKUP/themes-packrivo-home-v4-4.0.1" -type f | wc -l | tee "$BACKUP/theme-files.count"
( cd "$BACKUP/themes-packrivo-home-v4-4.0.1" && find . -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum ) | tee "$BACKUP/theme-dir.sha256"
grep '^Version:' "$BACKUP/themes-packrivo-home-v4-4.0.1/style.css" | tee "$BACKUP/theme-version.txt"
```

⛳ 文件数 > 0；`theme-version.txt` = `Version: 4.0.1`；`theme-dir.sha256` 记下来（回滚时逐字比对）。
目录哈希算的是**相对路径**（`cd` 进去再 `find .`），回滚后换目录名也能比。

【宿主】2) mu-plugin 原文件（字体补丁之前的样子）：

```bash
docker cp php:"$WPC/wp-content/mu-plugins/packrivo-wave2.php" "$BACKUP/29z/packrivo-wave2.php.orig"
sha256sum "$BACKUP/29z/packrivo-wave2.php.orig" | tee "$BACKUP/29z/mu-orig.sha256"
docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w"
```

⛳ 宿主机这份应是 `5ab5be3f85f46ce5416fdb4cafc98f54de86a1fc4ece041c56c2de40173a1d8c`（12390 B）。
容器里那份 `.orig-29w` 同内容，是 §6.5 回滚用手的那份。

【宿主】3) 三张表（**不放容器 `/tmp`**，拷到宿主机并记字节数与 sha256）：

```bash
$WPCLI db export /tmp/29z-pre.sql --tables=wp_posts,wp_postmeta,wp_yoast_indexable
docker cp php:/tmp/29z-pre.sql "$BACKUP/29z/29z-pre.sql"
wc -c < "$BACKUP/29z/29z-pre.sql" | tee "$BACKUP/29z/29z-pre.bytes"
sha256sum "$BACKUP/29z/29z-pre.sql" | tee "$BACKUP/29z/29z-pre.sha256"
docker exec php rm -f /tmp/29z-pre.sql
```

⛳ 字节数 > 0，`.bytes` 与 `.sha256` 都在 `$BACKUP/29z/` 里。回滚导的是宿主机这份，容器里那份已删。

### D.2 装 4.0.4

【宿主】D.2.1 装包：

```bash
docker cp "$PKG/packrivo-home-v4.zip" php:"$PKGC/packrivo-home-v4.zip"
docker exec php sha256sum "$PKGC/packrivo-home-v4.zip"     # ⛳ 必须 {ZIP}
$WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
$WPCLI theme activate packrivo-home-v4
$WPCLI theme list --fields=name,status,version | grep packrivo     # ⛳ active 4.0.4
docker exec php sh -c "grep -c 'Example quote · Rigid magnetic box' $WPC/wp-content/themes/packrivo-home-v4/front-page.php"   # ⛳ 1
```

`$PKGC` 是**容器里的包目录**（`/pkg`，D.0 第 2 步已 `mkdir`），装完的代码在 `$WPC/wp-content/themes/packrivo-home-v4`。
两者不是同一个目录，别混。

⛳ 首页卡片文案生效（**走 `$CURL` 直连，不能读缓存**）：

```bash
$CURL "$SITE/" | grep -o 'Example quote · Rigid magnetic box · 9.5×9.5×4 cm' | head -1
```

【宿主】D.2.2 字体补丁（同 §6.5，装完包紧接着打）：

```bash
docker cp "$PKG/packrivo-wave2-font-path.patch" php:/tmp/29w.patch
docker exec php sh -c "cd $WPC && patch -p1 --dry-run < /tmp/29w.patch"     # ⛳ 无 FAILED、无 .rej
docker exec php sh -c "cd $WPC && patch -p1 < /tmp/29w.patch"
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # ⛳ 73d43ba0…
$WPCLI cache flush
$CURL "$SITE/" | grep -o 'https://[^"]*packrivo-inter-latin-v3.woff2' | head -1
```

**容器里没有 `patch` 的话**（本机演练镜像就是，会报 `patch: not found`），用 PHP 兜底，同样对齐 §6.5 的哈希锁：

```bash
docker cp "$PKG/apply-font-patch.php" php:/tmp/apply-font-patch.php
docker exec php php /tmp/apply-font-patch.php          # 幂等：第二次打印 already applied
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # ⛳ 73d43ba0…
```

⛳ 字体 URL 指向 `/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2`，
直连该 URL 返回 `200` / `font/woff2` / `34952` 字节：

```bash
$CURL -o /dev/null -w 'code=%{{http_code}} type=%{{content_type}} size=%{{size_download}}\n' \
  "$SITE/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2"
```

### D.3 去价：干跑、真跑

【宿主】D.3.1 干跑（不写库）：

```bash
docker cp "$PKG/strip-1688.php" php:/tmp/strip-1688.php
docker exec -e STRIP1688_DRY=1 php sh -c "php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root eval-file /tmp/strip-1688.php"
```

⛳ `changes` = 210、`leftover_pages` = 0、`metadesc_unique` = 208、`metadesc_maxlen` ≤ 155。

【宿主】D.3.2 真跑 + 取审计：

```bash
docker exec -e STRIP1688_DRY=0 -e STRIP1688_AUDIT=/tmp/audit-29z.json php sh -c "php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root eval-file /tmp/strip-1688.php"
docker cp php:/tmp/audit-29z.json "$BACKUP/29z/audit-29z.json"
```

⛳ `changes` = 210；**紧接着再跑一次必须 `changes` = 0**（幂等）；审计里 34 句被删的原文人工过一遍。

`wp eval-file` 会把 `--dry-run` 当参数错误拦下，所以开关只有环境变量 `STRIP1688_DRY`，
或者用包装器 `bash deploy/patches-29z/strip-1688.sh --dry-run`。

它做什么：规格表 `Lowest listed tier / Historical 1688 reference` 整行删、换成 `Get a price →`；
hub 对照表里**任何**带价格的 `<td>` 换同一个链接（生产表头写 `Confirm stock minimum`，按表头匹配不到，靠这一层兜）；
`What this tells you` 的 `<h2>` + 那段价格话整块删；正文 `<p>/<li>` 里价格句逐句删（写进审计）；
208 页 meta 换成「产品名 — 结构/材质. Custom logo printing, MOQ 500 per design. Samples available.」≤155 字符、两两不同；
`/products/` 正文 158 个 `<p>Listed at ¥…</p>` 删掉；博客里 `detail.1688.com` 的 `<a>` 去链接留文字。

### D.4 重建 indexable + 清缓存

【宿主】

```bash
$WPCLI db query "UPDATE wp_yoast_indexable i JOIN wp_postmeta m ON m.post_id=i.object_id AND m.meta_key='_yoast_wpseo_metadesc' SET i.description=m.meta_value, i.open_graph_description=m.meta_value"
$WPCLI cache flush
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_yoast_indexable i JOIN wp_postmeta m ON m.post_id=i.object_id AND m.meta_key='_yoast_wpseo_metadesc' WHERE i.description<>m.meta_value"
```

⛳ 最后一条必须是 `0`。D.3 的脚本其实已经写过这三列，这一步是兜底（Yoast 自己刷新过 indexable、
或者中间手工改过 meta 时用）。

`wp db query` 的 SQL 是**位置参数**，不要加 `-e`（`wp` 会把 `-e` 之后当成多余参数报
`Too many positional arguments`）。

### D.5 容器内复测（compare-noshots，不含截图）

【宿主】

```bash
docker cp "$PKG/29z-criteria.sh" php:/tmp/29z-criteria.sh
docker exec -e SITE="$SITE" -e CURLX="$CURLX" php sh /tmp/29z-criteria.sh compare-noshots
```

⛳ `PASS=23 FAIL=0 SKIP=1`。`SKIP` 那条固定是 **4f 截图**——截图在本机拍、在本机判，容器里没有也不该有，
所以这一条在这里**既不算 PASS 也不算 FAIL**（旧版把「容器里没图」记成 FAIL，会把人卡住）。

判据 4 的口径是「卡片数量/名称/链接逐一相同 + 渲染里 `¥`、`\u00a5`、`1688` 计数为 0」，
不是「渲染字节完全相同」——生产模板把正文塞进页面 `<script>` 的 JSON，删价格必然改字节。

### D.5.5 【停】清 Cloudflare 缓存 → 本机判 4f → 线上复测

走到这里**停下来**，把 `$BACKUP` 路径和 D.0～D.5 每条 ⛳ 的输出发给负责人。清缓存由负责人执行，不在本流程里：

```bash
python E:\破甲工作区\i18n\cf_purge.py          # 负责人平时用这份（10-25 版）；--verify 只验证不清
```

清完缓存后【本机】按顺序做三件事：

```bash
# 1) 改后截图（缓存已清，拍到的是新页面）
node deploy/verification-29z/scripts/29z-products-shots.mjs deploy/verification-29z/evidence/products-shots after https://www.packrivo.com
#    （演练：SHOTS_MAP=www.packrivo.com + base 换成 http://www.packrivo.com:8091）

# 2) 判据 4f（四张图齐 + 卡片数与改前一致 + 无横向溢出）
node deploy/verification-29z/scripts/29z-shots-check.mjs deploy/verification-29z/evidence/products-shots

# 3) 全站复测（只读，不写库）
node deploy/verification-29z/scripts/29z-crawl-prod.mjs deploy/verification-29z/evidence/crawl-29z-after-cf.json
node deploy/verification-29z/scripts/29z-old-url-sources.mjs deploy/verification-29z/evidence/old-url-sources-after-cf.json
```

⛳ 三条都过：`29z-shots-check.mjs` 打印 `PASS 4f`（4/4 图、卡片 24 = 24、无溢出、改后渲染 `¥` 0 处）；
60 页里除 11 条多语种博客 404 外没有非 200；`/rigid-gift-boxes/ /resources/ /packaging-examples/ /pricing/ /sustainability/`
引用 0 条；首页字体 200；抽 5 个 catalog 页渲染里 `¥` 0 处。
`4f` 加上容器里那 23 条，合计 **24 PASS / 0 FAIL / 0 SKIP**。

### D.6 回滚（真出事才走这一段）

前提：`packrivo-home-v4` **保持激活**。**不要** `theme activate packrivo-static-bridge-1.2.38`——那会切回父主题，
整站变回上线前的样子（上一版 D.6 就是这里写错）。

【宿主】1) 主题目录退回 4.0.1（覆盖回去，slug 不变，激活状态不变）：

```bash
docker exec php sh -c "rm -rf $WPC/wp-content/themes/packrivo-home-v4"
docker cp -a "$BACKUP/themes-packrivo-home-v4-4.0.1" php:"$WPC/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --fields=name,status,version | grep packrivo        # ⛳ active 4.0.1
docker cp -a php:"$WPC/wp-content/themes/packrivo-home-v4" "$BACKUP/rollback-check"
( cd "$BACKUP/rollback-check" && find . -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum )
```

⛳ 目录哈希与 `theme-dir.sha256` 逐字相同，`theme-version.txt` 是 4.0.1。

【宿主】2) 字体补丁回滚（把 §6.5 那份 `.orig-29w` 换回去）：

```bash
docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w $WPC/wp-content/mu-plugins/packrivo-wave2.php"
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"    # ⛳ 5ab5be3f…
```

⛳ 与 `$BACKUP/29z/mu-orig.sha256` 相同（`5ab5be3f85f46ce5416fdb4cafc98f54de86a1fc4ece041c56c2de40173a1d8c`）。

【宿主】3) 数据库退回（导宿主机那份三表备份）：

```bash
docker cp "$BACKUP/29z/29z-pre.sql" php:/tmp/29z-pre.sql
$WPCLI db import /tmp/29z-pre.sql
$WPCLI cache flush
docker exec php rm -f /tmp/29z-pre.sql
```

【宿主】4) 回滚判据（四条全中才算回干净，**首页仍然用 `$CURL` 直连**，和 D.1b 同一种方式）：

```bash
$WPCLI theme list --fields=name,status,version | grep packrivo                                     # ⛳ 4.0.1 active
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')"   # ⛳ 209
$CURL "$SITE/" -o /tmp/home-after-rollback.html
sha256sum /tmp/home-after-rollback.html; cat "$BACKUP/29z/home-before.sha256"                      # ⛳ 两个哈希相同
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"                          # ⛳ 5ab5be3f…
```

⛳ 四条：`4.0.1 active` / `¥` = `209` / 首页 HTML 哈希等于 `home-before.sha256` / mu 文件哈希等于原文件。

回滚后若要重来，从 D.2 继续（`$BACKUP` 里那三份备份不要删）。
"""


def code_blocks(text):
    """返回每个 ``` 代码块的行数列表"""
    blocks, cur, inside = [], 0, False
    for line in text.split("\n"):
        if line.startswith("```"):
            if inside:
                blocks.append(cur)
                cur, inside = 0, False
            else:
                inside = True
            continue
        if inside:
            cur += 1
    return blocks


def main():
    base = base_text()
    out = base

    # 1) 两处哈希/大小
    n1 = out.count(OLD_HASH)
    out = out.replace(OLD_HASH, NEW_HASH)
    n2 = out.count("796,514")
    out = out.replace("796,514", "791,169")
    n3 = out.count("49DDBE6F")
    out = out.replace("49DDBE6F", "3279DC61")

    # 2) 字体补丁一节，插在 §7 之前
    anchor = "## 7. 清缓存"
    if anchor not in out:
        sys.exit("找不到 §7 锚点")
    out = out.replace(anchor, FONT_SECTION + anchor, 1)

    # 3) 附录 D
    def stat_item(p):
        data = io.open(os.path.join(REPO, p), "rb").read()
        import hashlib
        return len(data), hashlib.sha256(data).hexdigest()

    zip_size, _ = stat_item(os.path.join("deploy", "packrivo-home-v4.zip"))
    php_size, php_hash = stat_item(os.path.join("deploy", "patches-29z", "strip-1688.php"))
    sh_size, sh_hash = stat_item(os.path.join("deploy", "patches-29z", "strip-1688.sh"))
    pf_size, pf_hash = stat_item(os.path.join("deploy", "patches-29w", "apply-font-patch.php"))
    crit_size, crit_hash = stat_item(os.path.join("deploy", "verification-29z", "scripts", "29z-criteria.sh"))
    out = out.rstrip("\n") + "\n" + APPENDIX_D.format(
        ZIP=NEW_HASH, ZIP_SIZE=zip_size, PHP_SIZE=php_size, PHP_HASH=php_hash,
        SH_SIZE=sh_size, SH_HASH=sh_hash, CRIT_SIZE=crit_size, CRIT_HASH=crit_hash,
        PF_SIZE=pf_size, PF_HASH=pf_hash,
    )
    if not out.endswith("\n"):
        out += "\n"

    # 4) 自检
    problems = []
    if "\ufffd" in out:
        problems.append("输出里有 U+FFFD")
    base_lines = base.count("\n")
    new_lines = out.count("\n")
    if new_lines < base_lines:
        problems.append("行数减少 %d < %d" % (new_lines, base_lines))
    bb, nb = code_blocks(base), code_blocks(out)
    if len(nb) < len(bb):
        problems.append("代码块数量减少 %d < %d" % (len(nb), len(bb)))
    # 原有每个代码块的行数不能变少：按子序列匹配（新增的块插在中间，不能按位置硬比）
    j = 0
    for i, want in enumerate(bb):
        while j < len(nb) and nb[j] != want:
            j += 1
        if j >= len(nb):
            problems.append("底稿第 %d 个代码块（%d 行）在新文件里找不到" % (i + 1, want))
            break
        j += 1
    lines = out.split("\n")
    inst = [i for i, l in enumerate(lines) if "theme install" in l]
    for i in inst:
        if not lines[i].strip().startswith("$WPCLI theme install"):
            problems.append("第 %d 行 theme install 不在行首：%r" % (i + 1, lines[i][:80]))

    io.open(TARGET, "w", encoding="utf-8", newline="").write(out)

    print("底稿 c62bc88: %d 行" % base_lines)
    print("新文件      : %d 行" % new_lines)
    print("替换：hash %d 处 / 796,514 %d 处 / 49DDBE6F %d 处" % (n1, n2, n3))
    print("代码块：底稿 %d 个，新 %d 个；底稿行数序列前 8 个 = %s" % (len(bb), len(nb), bb[:8]))
    print("theme install 行：")
    for i in inst:
        print("  第 %d 行: %s" % (i + 1, lines[i]))
    print("自检问题:", problems if problems else "无")
    if problems:
        sys.exit(1)


if __name__ == "__main__":
    main()
