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
curl -s https://www.packrivo.com/ | grep -o 'https://[^"]*packrivo-inter-latin-v3\\.woff2' | head -2
curl -s -o /dev/null -w 'code=%{http_code} type=%{content_type} size=%{size_download}\\n' \\
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
`curl -sI` 该 URL 返回 **200** 且 `content-type: font/woff2`、`content-length: 34952`。

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

### D.0 变量与预检

【宿主】1) 定路径，**把 `$BACKUP` 记下来**（回滚只认它）：

```bash
PKG=/root/29z-pkg
BACKUP=/home/web/backups/29z-$(date -u +%Y%m%d-%H%M%S)
mkdir -p "$PKG" "$BACKUP/29z"
echo "$BACKUP" | tee /root/29z-backup-path.txt
```

【宿主】2) 后面反复用的两行：

```bash
WPC=/var/www/html/www.packrivo.com/wordpress
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
```

【宿主】3) 只读预检：

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

【本机】D.1.1 上传这 **6** 个文件（表里就这 6 个，少一个都别装）：

```bash
scp deploy/packrivo-home-v4.zip                       netcup:$PKG/
scp deploy/patches-29z/strip-1688.php                 netcup:$PKG/
scp deploy/patches-29z/strip-1688.sh                  netcup:$PKG/
scp deploy/patches-29w/packrivo-wave2-font-path.patch netcup:$PKG/
scp deploy/patches-29w/apply-font-patch.php           netcup:$PKG/
scp deploy/verification-29z/scripts/29z-criteria.sh   netcup:$PKG/
```

【宿主】D.1.2 核对上传物：

```bash
cd "$PKG" && sha256sum packrivo-home-v4.zip strip-1688.php strip-1688.sh packrivo-wave2-font-path.patch apply-font-patch.php 29z-criteria.sh
```

⛳ 与 D.1.0 表逐个相同；`packrivo-home-v4.zip` 必须是 {ZIP_SIZE} 字节。

### D.1b 前态快照（判据 4 与回滚判据都要它）

【宿主】1) 判据脚本进容器 + 取前态：

```bash
docker cp "$PKG/29z-criteria.sh" php:/tmp/29z-criteria.sh
docker exec php sh /tmp/29z-criteria.sh before
mkdir -p /tmp/29z-snap/shots
curl -sS https://www.packrivo.com/ -o "$BACKUP/29z/home-before.html"
sha256sum "$BACKUP/29z/home-before.html" | tee "$BACKUP/29z/home-before.sha256"
```

⛳ `home-before.sha256` 有值；容器里出现 `/tmp/29z-snap/`（pages-before / meta-before / index-before / products-before）。

【本机】2) `/products/` 的「改前」截图（**必须在装包之前拍**）：

```bash
node deploy/verification-29z/scripts/29z-products-shots.mjs deploy/verification-29z/evidence/products-shots before
docker cp deploy/verification-29z/evidence/products-shots/before-1440.png php:/tmp/29z-snap/shots/before-1440.png
docker cp deploy/verification-29z/evidence/products-shots/before-375.png  php:/tmp/29z-snap/shots/before-375.png
```

⛳ 两张图 `cards` 都是 24、`overflow` false。

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
docker exec php sh -c "grep -c 'Example quote · Rigid magnetic box' $PKGC/packrivo-home-v4/front-page.php"   # ⛳ 1
```

`$PKGC` 与 §5.2 同一个：容器内 `wp-content/themes` 目录，**zip 要先拷到那个目录里**（安装命令只认这个路径）。

⛳ 首页卡片文案生效：

```bash
curl -sS https://www.packrivo.com/ | grep -o 'Example quote · Rigid magnetic box · 9.5×9.5×4 cm' | head -1
```

【宿主】D.2.2 字体补丁（同 §6.5，装完包紧接着打）：

```bash
docker cp "$PKG/packrivo-wave2-font-path.patch" php:/tmp/29w.patch
docker exec php sh -c "cd $WPC && patch -p1 --dry-run < /tmp/29w.patch"     # ⛳ 无 FAILED、无 .rej
docker exec php sh -c "cd $WPC && patch -p1 < /tmp/29w.patch"
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # ⛳ 73d43ba0…
$WPCLI cache flush
curl -sS https://www.packrivo.com/ | grep -o 'https://[^"]*packrivo-inter-latin-v3.woff2' | head -1
```

**容器里没有 `patch` 的话**（本机演练镜像就是，会报 `patch: not found`），用 PHP 兜底，同样对齐 §6.5 的哈希锁：

```bash
docker cp "$PKG/apply-font-patch.php" php:/tmp/apply-font-patch.php
docker exec php php /tmp/apply-font-patch.php          # 幂等：第二次打印 already applied
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # ⛳ 73d43ba0…
```

⛳ 字体 URL 指向 `/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2`，
对该 URL 请求 `200` / `font/woff2` / `34952` 字节。

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

### D.5 复测（compare）

【宿主】

```bash
docker cp "$PKG/29z-criteria.sh" php:/tmp/29z-criteria.sh
docker exec php sh /tmp/29z-criteria.sh compare
```

⛳ `PASS=24 FAIL=0`。判据 4 要四张截图（1440/375 各一对改前改后），改后的两张由【本机】拍：

```bash
node deploy/verification-29z/scripts/29z-products-shots.mjs deploy/verification-29z/evidence/products-shots after
docker cp deploy/verification-29z/evidence/products-shots/after-1440.png  php:/tmp/29z-snap/shots/after-1440.png
docker cp deploy/verification-29z/evidence/products-shots/after-375.png   php:/tmp/29z-snap/shots/after-375.png
```

（改前那两张在 D.1b 第 2 步已经拍好、也拷进去了。）

判据 4 的口径是「卡片数量/名称/链接逐一相同 + 渲染里 `¥`、`\u00a5`、`1688` 计数为 0 + 四张截图」，
不是「渲染字节完全相同」——生产模板把正文塞进页面 `<script>` 的 JSON，删价格必然改字节。

### D.5.5 【停】清 Cloudflare 缓存，再做线上复测

走到这里**停下来**，把 `$BACKUP` 路径和上面每条 ⛳ 的输出发给负责人。清缓存由负责人执行（不在本流程里，
脚本在 `E:\破甲工作区\packrivo-handoff\scripts\cf_purge.py`，凭据读 `E:\破甲工作区\i18n\.cf.env`）：

```bash
python cf_purge.py            # 负责人跑：清全站；--verify 只验证不清
```

清完缓存后【本机】线上复测（只读，不写库）：

```bash
node deploy/verification-29z/scripts/29z-crawl-prod.mjs deploy/verification-29z/evidence/crawl-29z-after-cf.json
node deploy/verification-29z/scripts/29z-old-url-sources.mjs deploy/verification-29z/evidence/old-url-sources-after-cf.json
```

⛳ 60 页里除 11 条多语种博客 404 外没有非 200；`/rigid-gift-boxes/ /resources/ /packaging-examples/ /pricing/ /sustainability/`
引用 0 条；首页字体 200；抽 5 个 catalog 页渲染里 `¥` 0 处。

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

【宿主】4) 回滚判据（四条全中才算回干净）：

```bash
$WPCLI theme list --fields=name,status,version | grep packrivo                                     # ⛳ 4.0.1 active
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')"   # ⛳ 209
curl -sS https://www.packrivo.com/ -o /tmp/home-after-rollback.html
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
