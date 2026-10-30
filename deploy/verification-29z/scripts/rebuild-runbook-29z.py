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
NEW_HASH = "7d792ad9f4e63025e2fce8a640856710ede5fe3b925e9bdaa372a6002bb945f7"


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
sha256sum "$PKG/deploy/patches-29w/packrivo-wave2-font-path.patch"   # 必须 022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d
docker cp "$PKG/deploy/patches-29w/packrivo-wave2-font-path.patch" php:/tmp/font-path.patch
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

## 附录 D · 29z（撤 1688 进货价 + 4.0.3 包 + 死链/字体），2026-10-30

> 全部在演练环境用**生产现状库**（`29z-prod.sql`）跑过：208 个 catalog 页、209 条正文含 `1688` 的 publish 行、
> 209 条含 `¥`。判据逐条打印 PASS/FAIL，不拿退出码当证据。

### D.1 锁定物（sha256，改一个字就重跑本节）

| 物 | 路径 | 字节 | sha256 |
|---|---|---|---|
| 子主题包 4.0.3 | `deploy/packrivo-home-v4.zip` | 791053 | `{ZIP}` |
| 去价脚本 | `deploy/patches-29z/strip-1688.php` | {PHP_SIZE} | `{PHP_HASH}` |
| 去价包装器 | `deploy/patches-29z/strip-1688.sh` | {SH_SIZE} | `{SH_HASH}` |
| 九条判据脚本 | `deploy/verification-29z/scripts/29z-criteria.sh` | {CRIT_SIZE} | `{CRIT_HASH}` |
| 字体路径补丁 | `deploy/patches-29w/packrivo-wave2-font-path.patch` | 756 | `022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d` |

包内 38 文件 + 5 目录 = 43 条目；`style.css` 与 `functions.php` 都是 `4.0.3`；
包内 `assets/img/hero.webp` = `6a55801599da73d2…`（1200×1200，33,758 B）、`hero-480.webp` = `266032c7bdfc0f51…`（480×480，8,866 B）。

### D.2 装包

```bash
docker cp deploy/packrivo-home-v4.zip php:/pkg/packrivo-home-v4.zip
docker exec php sha256sum /pkg/packrivo-home-v4.zip                  # 必须 {ZIP}
$WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
$WPCLI theme activate packrivo-home-v4
$WPCLI theme list --fields=name,status,version | grep packrivo        # packrivo-home-v4 active 4.0.3
```

### D.3 去价（生产）

```bash
# D.3.0 只备份三张表，回滚快
$WPCLI db export /tmp/29z-pre.sql --tables=wp_posts,wp_postmeta,wp_yoast_indexable
docker exec php sh -c 'wc -c /tmp/29z-pre.sql'            # 必须 > 0

# D.3.1 干跑（不写库）
docker cp deploy/patches-29z/strip-1688.php php:/tmp/strip-1688.php
docker exec -e STRIP1688_DRY=1 php sh -c \\
  "php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root eval-file /tmp/strip-1688.php"

# D.3.2 真跑
docker exec -e STRIP1688_DRY=0 -e STRIP1688_AUDIT=/tmp/audit-29z.json php sh -c \\
  "php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root eval-file /tmp/strip-1688.php"
docker cp php:/tmp/audit-29z.json ./audit-29z.json        # 被删掉的句子都在里面，人工过一遍
```

`wp eval-file` 会把 `--dry-run` 当参数错误拦下，所以开关是环境变量 `STRIP1688_DRY=1`，
或用包装器 `bash deploy/patches-29z/strip-1688.sh --dry-run`。

它做什么：规格表里 `Lowest listed tier / Historical 1688 reference` 整行删并换成 `Get a price →`；
hub 对照表里任何带价格的 `<td>` 换成同一个链接（生产表头写的是 `Confirm stock minimum`，按表头匹配不到，靠这一层兜）；
`What this tells you` 的 `<h2>` + 那段价格话整块删；正文 `<p>/<li>` 里的价格句逐句删（进审计清单）；
208 页 meta 重写成「产品名 — 结构/材质. Custom logo printing, MOQ 500 per design. Samples available.」≤155 字符、两两不同；
`/products/` 正文 158 个 `<p>Listed at ¥…</p>` 删掉；博客里 `detail.1688.com` 的 `<a>` 去链接留文字。

### D.4 meta description 的真实来源（§0.3 结论）

生产实测：`<meta name="description">` 与 `<meta property="og:description">` 都等于该页
`wp_postmeta._yoast_wpseo_metadesc` 的值（逐字一致），由 **wordpress-seo（Yoast）** 渲染。
子主题 `packrivo-home-v4/functions.php:90` 有一个 `wpseo_metadesc` 过滤器，但只对带
`_packrivo_description` 的对象生效——catalog 页这个 meta 一条都没有，所以过滤器不参与；
同文件 `:103` 那段自己打 `<meta name="description">` 的代码被 `defined('WPSEO_VERSION')` 挡住，不执行。
`wp_yoast_indexable.description` 与 postmeta 不同步（是旧值），所以改完必须重建 indexable。

```bash
$WPCLI yoast index --reindex        # 没有这个子命令就用 D.5 的兜底：脚本直接写 indexable 行
$WPCLI cache flush
```

### D.5 复测

```bash
docker cp deploy/verification-29z/scripts/29z-criteria.sh php:/tmp/29z-criteria.sh
docker exec php sh /tmp/29z-criteria.sh before      # 打补丁前取前态
docker exec php sh /tmp/29z-criteria.sh compare     # 打完之后逐条 PASS/FAIL
```

判据 4 的口径是「产品卡片数量/名称/链接逐一相同 + 页面里 `¥`、`\\u00a5`、`1688` 计数为 0 + 1440/375 各一对改前改后截图」，
不再是「渲染字节完全相同」——生产模板会把正文塞进页面 `<script>` 的 JSON 里，删价格必然改字节。

### D.6 回滚

```bash
$WPCLI db import /tmp/29z-pre.sql
$WPCLI cache flush
$WPCLI theme activate packrivo-static-bridge-1.2.38
```
判据：`SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')`
回到 209；`/products/` 里 `\\u00a5` 计数回到改前值。
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
    out = out.replace("796,514", "791,053")
    n3 = out.count("49DDBE6F")
    out = out.replace("49DDBE6F", "7D792AD9")

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

    php_size, php_hash = stat_item(os.path.join("deploy", "patches-29z", "strip-1688.php"))
    sh_size, sh_hash = stat_item(os.path.join("deploy", "patches-29z", "strip-1688.sh"))
    crit_size, crit_hash = stat_item(os.path.join("deploy", "verification-29z", "scripts", "29z-criteria.sh"))
    out = out.rstrip("\n") + "\n" + APPENDIX_D.format(
        ZIP=NEW_HASH, PHP_SIZE=php_size, PHP_HASH=php_hash,
        SH_SIZE=sh_size, SH_HASH=sh_hash, CRIT_SIZE=crit_size, CRIT_HASH=crit_hash,
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
