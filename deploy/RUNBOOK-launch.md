# 上线手册 · Packrivo 一次性上线（RUNBOOK）

> **上线以本文件为准。`deploy/README.md` 里按轮次堆的 27g…28o 记录只作历史参考，命令和顺序以这里为唯一口径。**
> 本文件不含任何密码、密钥或 token；所有凭证走服务器上已有的 SSH 配置与 wp-cli。

- 目标站点：`https://www.packrivo.com`（WordPress 6.7.2 + 父主题 `packrivo-static-bridge-1.2.38` + 子主题 `packrivo-home-v4` + 报价插件 `packrivo-quote` + mu-plugins `packrivo-i18n.php`、`packrivo-site-remediation-20261022.php`）
- 本轮上线内容：134 个产品页的新标题/描述（27g）、8 个主页面（27i）、11 语种字典与路径表（27g/27i/28k/28m/28n/28o）、报价插件与子主题新包、两个 mu 补丁
- 预计总耗时：**1.5–2 小时**（含复测），建议在访问量低的时段做（北京时间 02:00–05:00 或周末）
- 每一步都能单独回滚；第 9 步是整体逆序回滚

## 变量（每开一个 SSH 会话先执行一次）

```bash
WP=/home/web/html/www.packrivo.com/wordpress                       # WordPress 根目录
BACKUP=/home/web/backups/packrivo-launch-$(date +%Y%m%d-%H%M)      # 本次备份目录
WPCLI="wp --path=$WP --allow-root"                                 # 服务器上已配置好的 wp-cli
PKG=/home/web/packrivo-packages                                    # 上传包解压后的根目录
I18N=$WP/wp-content/uploads/packrivo-i18n                          # 字典与 paths.json 所在目录
SITE=https://www.packrivo.com                                      # 导入脚本的站点守卫
```

## 上传内容与校验（第 1 步之前做）

从本机用 scp 上传到 `$PKG`，**保留目录结构**（导入脚本按相对路径读输入文件，压平就读不到）：

```text
$PKG/
  packrivo-home-v4.zip                 38 个文件 / 788,881 B / 05de19e1e5072e4bda8e6d843824d915725a57732b4da728036c94e88cf8c3a8
  packrivo-quote.zip                   19 个文件 / 54,512 B / 8e62865ea202d7711b4f113390be898a64024648f348151e27f1dace54c3c05c
  27e-quote-redirect.patch             647 B
  patches-28m/packrivo-i18n-404-viewport.patch   1,018 B
  tools/merge-i18n.php                 字典合并脚本（第 2 步用）
  i18n-merged/                         11 个 <lang>-dict.json + paths.json（第 2 步的本地底稿）
  dsh-out/batch-01.json … batch-05.json   27g 的 5 × 26 = 130 条输入
  dsh-out/hubs-27i.json                27i 的 8 个主页面输入
  scripts/product-buyers-27g/import.php, lib.php
  scripts/product-buyers-27e/full-inventory.csv, products.json, third.json, fourth.json   （all134 的另外 4 条）
  scripts/hubs-27i/import.php
```

上传后先核对（不一致就停下，别接着做）：

```bash
cd "$PKG" && sha256sum packrivo-home-v4.zip packrivo-quote.zip
#   期望 05de19e1… 与 8e62865e…
php -l tools/merge-i18n.php && php -l scripts/product-buyers-27g/import.php && php -l scripts/hubs-27i/import.php
ls i18n-merged/*-dict.json | wc -l        # 期望 11
ls dsh-out/batch-0*.json | wc -l          # 期望 5
```

---

## 1. 备份（**生产 SSH**）

**预计 10 分钟。这一步不改任何文件。**

```bash
mkdir -p "$BACKUP"/{theme,plugin,mu,db,i18n,rollback}
cp -a "$WP/wp-content/themes/packrivo-home-v4"              "$BACKUP/theme/"
cp -a "$WP/wp-content/themes/packrivo-static-bridge-1.2.38" "$BACKUP/theme/"
cp -a "$WP/wp-content/plugins/packrivo-quote"               "$BACKUP/plugin/"
cp -a "$WP/wp-content/mu-plugins"                           "$BACKUP/mu/"
cp -a "$I18N"                                               "$BACKUP/i18n/"
$WPCLI db export "$BACKUP/db/packrivo-before-launch.sql" --skip-plugins --skip-themes
$WPCLI plugin list --format=csv > "$BACKUP/before-plugin-list.csv"
$WPCLI theme list  --format=csv > "$BACKUP/before-theme-list.csv"
$WPCLI option get home > "$BACKUP/before-home.txt"
curl -s https://www.packrivo.com/robots.txt > "$BACKUP/before-robots.txt"
sha256sum "$WP/wp-content/mu-plugins/packrivo-i18n.php" \
          "$WP/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" > "$BACKUP/before-mu.sha256"
du -sh "$BACKUP"/*
```

**通过判据**

- `$BACKUP/theme/` 下两个主题目录都在，`$BACKUP/plugin/packrivo-quote` 在，`$BACKUP/mu/` 里有全部 mu 文件，`$BACKUP/i18n/` 里有 11 个字典 + `paths.json`；
- `grep -c "CREATE TABLE" "$BACKUP/db/packrivo-before-launch.sql"` > 0；
- `$BACKUP/before-mu.sha256` 里两个哈希已记下（第 6 步比对用）；
- **数据库导出只留在这台服务器上，不下载、不外传。**

**回滚**：本步不改文件；确认上线成功后再删 `$BACKUP`。

---

## 2. 字典与路径表（**生产 SSH**）

**预计 20–30 分钟（含人工看冲突清单）。这是整个上线里唯一不能自动判定的一步。**

原则：**以当天新读的生产字典为底**，把本轮的 134 产品页、8 个主页面、60 条界面词、28k 的 207 条、28m 的报价页、28n 的 `Instant quote` / `Sample kit` 一并合并；**同一键两边都有值时生产优先**，冲突清单必须人工过目再装。

```bash
# 2.1 取当天生产字典（只复制，不动线上文件）
cp -a "$I18N" "$BACKUP/i18n-today"
ls "$BACKUP/i18n-today" | head -3
php -r 'json_decode(file_get_contents($argv[1]),true)?:exit(1); echo "prod de ok\n";' "$BACKUP/i18n-today/de-dict.json"

# 2.2 合并：生产优先，冲突单独列出，paths 取并集并补必填
cd "$PKG" && php tools/merge-i18n.php \
    --prod="$BACKUP/i18n-today" \
    --local="$PKG/i18n-merged" \
    --out="$PKG/merged" \
    --require=/get-a-quote/
# 期望末尾两行形如：
#   paths.json：生产 N + 本地 146 → 合并 M（本轮补 X：…）
#   冲突清单：K 条 → /…/merged/conflicts.json

# 2.3 看冲突（有冲突就必须逐条看过）
cat "$PKG/merged/conflicts.json"

# 2.4 装之前再校验一遍
ls "$PKG/merged"/*-dict.json | wc -l        # 期望 11
for f in "$PKG"/merged/*.json; do php -r 'json_decode(file_get_contents($argv[1]),true)?:exit(1);' "$f" || echo "坏 JSON: $f"; done
grep -q '"/get-a-quote/"' "$PKG/merged/paths.json" && echo paths-ok

# 2.5 覆盖线上字典（原文件已备在第 1 步）
cp -a "$PKG/merged"/*.json "$I18N/"
```

**通过判据**

- 合并脚本输出的冲突条数已人工核对：凡是被生产值覆盖的键，都是这次**不打算改**的；如果有该改却被生产值盖住的键，改完本地值再跑一遍 2.2，直到冲突清单可接受；
- `ls "$PKG/merged"/*-dict.json | wc -l` = **11**；每个 JSON 都能 `json_decode`；
- `paths.json` 含 `"/get-a-quote/"`，条目数 ≥ 146；
- 装完后 `curl -s https://www.packrivo.com/de/ | grep -c 'lang="de"'` ≥ 1，页面正常。

**回滚**：`cp -a "$BACKUP/i18n"/* "$I18N"/`。

---

## 3. 内容：134 个产品页 + 8 个主页面（**生产 SSH**）

**预计 15–25 分钟。**

脚本是 `wp eval-file` 形式：`import.php <plan|apply|verify|rollback-check|rollback> <package-root> <expected-site> [backup] [pending130|all134]`。
`<package-root>` 就是 `$PKG`（脚本按 `dsh-out/…`、`scripts/…` 相对路径读输入），`<expected-site>` 必须与站点 `home` 完全一致，否则脚本自己会拒绝执行。

```bash
# 3.1 产品页：先 plan（只读，writes=0），确认输入 134 条
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" plan "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
#   期望输出：
#     input_pages=134 preflight=PASS
#     writes=0

# 3.2 产品页：真写（脚本自己在 $BACKUP/rollback 建独占 rollback 文件）
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" apply "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134

# 3.3 主页面：先 plan，确认 8 页
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php" plan "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
#   期望输出：
#     hub_pages=8 preflight=PASS
#     inquiry_existing_description=preserved writes=0

# 3.4 主页面：先备份再写（27i 有单独的 backup 模式）
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php" backup "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php" apply  "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"

# 3.5 两边都 verify（脚本自带的只读校验）
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" verify "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"           verify "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
```

**通过判据**

- 3.1 与 3.3 的输出与上面注释里的期望行一致（`input_pages=134`、`hub_pages=8`、`preflight=PASS`、`writes=0`）；数字不对就停下核对输入文件；
- `ls -l "$BACKUP/rollback/"` 里有两个 rollback JSON（27g 约 1.2 MB，27i 约 9 KB）；
- 3.5 两个 verify 都通过；
- 抽 3 页看标题（应含关键词，不是泛标题）：
  ```bash
  for u in /product-drawer-jewelry-box/ /products/ /jewelry-boxes/; do
    printf '%s ' "$u"; curl -s "https://www.packrivo.com$u" | grep -o '<title>[^<]*</title>' | head -1
  done
  ```
- 每页只有 1 个 title：`curl -s <URL> | grep -c '<title>'` = 1。

**回滚**：

```bash
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback-check "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback       "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"           rollback-check "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"           rollback       "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
```

本机仓库里还留了 27g / 27i 的原始快照 `deploy/rollback-27g.json`、`deploy/rollback-27i.json`，可用于二次核对（不必回滚线上）。

---

## 4. 报价插件（**生产 SSH**）

**预计 5 分钟。**

```bash
sha256sum "$PKG/packrivo-quote.zip"        # 必须等于 8e62865e…
$WPCLI plugin install "$PKG/packrivo-quote.zip" --force
$WPCLI plugin list --format=csv | grep packrivo-quote      # 状态必须是 active
```

**通过判据**

- `packrivo-quote` 状态 `active`；
- 浏览器打开 `/get-a-quote/?style=rigid&qty=500` 单价 **$2.83**、合计 **$1,417.43**；
- `--force` 会先删旧目录再解包，第 1 步的 `$BACKUP/plugin/` 不能省。

**回滚**：`rm -rf "$WP/wp-content/plugins/packrivo-quote" && cp -a "$BACKUP/plugin/packrivo-quote" "$WP/wp-content/plugins/" && $WPCLI plugin activate packrivo-quote`。

---

## 5. 子主题（**生产 SSH**）

**预计 5 分钟。**

```bash
# 5.1 父主题必须还是这一版，否则停下
$WPCLI theme list --format=csv | grep packrivo-static-bridge      # 必须是 1.2.38
sha256sum "$PKG/packrivo-home-v4.zip"                             # 必须等于 05de19e1…

# 5.2 安装
$WPCLI theme install "$PKG/packrivo-home-v4.zip" --force
$WPCLI theme list --format=csv | grep packrivo-home-v4            # 状态必须 active
```

**通过判据**

- 父主题仍是 `packrivo-static-bridge-1.2.38`；变了就停，先确认父子兼容；
- 子主题 `active`；
- `curl -s -o /dev/null -w '%{http_code}\n' https://www.packrivo.com/` = 200，首屏有标题、主按钮、产品图。

**回滚**：`rm -rf "$WP/wp-content/themes/packrivo-home-v4" && cp -a "$BACKUP/theme/packrivo-home-v4" "$WP/wp-content/themes/" && $WPCLI theme activate packrivo-home-v4`。

---

## 6. mu 补丁（**生产 SSH**）

**预计 10 分钟。两个补丁落在不同文件上，各自独立回滚。**

**顺序固定：先 `27e-quote-redirect.patch`，再 `patches-28m/packrivo-i18n-404-viewport.patch`。**

> **空档期说明（必须知道）**：第 4–6 步之间 `/get-a-quote/` 仍然 301 到 `/inquiry/`（27e 还没打）。这段时间新装的报价按钮会落到询盘页——**不会 404**，只是暂时看不到配置器；打完 27e 立刻恢复。这也是 27e 必须排在 404 补丁前面的原因。

```bash
# 6.1 27e：把 get-a-quote 从旧别名列表里删掉（只删这一项）
cd "$WP/wp-content/mu-plugins"
sha256sum packrivo-site-remediation-20261022.php
#   本机基线（与生产一致时）: abe6f1346276172a773039e397646cafdcfb9a40f9c76ff48bbefae1f22bad41
#   不一致就先 diff 生产文件与本机基线，重新出补丁，别硬打
git apply --check "$PKG/27e-quote-redirect.patch" && echo CHECK-OK
git apply         "$PKG/27e-quote-redirect.patch"
php -l packrivo-site-remediation-20261022.php

# 6.2 404 viewport：给「本页尚未翻译」的 404 补 viewport 与最小样式
cd "$WP"        # 这个补丁的路径前缀是 wp-content/mu-plugins/，必须在 WP 根执行
sha256sum wp-content/mu-plugins/packrivo-i18n.php
#   本机基线（与生产一致时）: 3bc325503b16a5de8cea7600801512653d4ab94db94739ac773e3d4a2741bb5e
git apply --check "$PKG/patches-28m/packrivo-i18n-404-viewport.patch" && echo CHECK-OK
git apply         "$PKG/patches-28m/packrivo-i18n-404-viewport.patch"
php -l wp-content/mu-plugins/packrivo-i18n.php
```

**通过判据**

- 两个 `git apply --check` 都打印 `CHECK-OK`（失败**不要强打**）；
- 两个 `php -l` 都 `No syntax errors detected`；
- `/get-a-quote/` 不再 301：
  ```bash
  curl -sI https://www.packrivo.com/get-a-quote/ | head -1      # 期望 HTTP/… 200
  curl -sI https://www.packrivo.com/de/get-a-quote/ | head -1   # 期望 200
  ```
- 语种 404 有 viewport：`curl -s https://www.packrivo.com/ar/2026/10/26/jewelry-pouch-box-customer-handoff/ | grep -c 'name="viewport"'` = 1。

**回滚**（两个独立，互不影响）：

```bash
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"   # 撤 27e
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"   # 撤 404 补丁
sha256sum -c "$BACKUP/before-mu.sha256"                                                   # 校验回到原状
```

---

## 7. 清缓存（**生产 SSH**）

**预计 5 分钟。**

```bash
$WPCLI cache flush 2>/dev/null || true
$WPCLI transient delete --all
# 站点若有页面缓存插件或 CDN，按站点现有方案清一次（本手册不绑定具体插件命令）
curl -sI https://www.packrivo.com/de/ | head -3
```

**通过判据**：`/de/`、`/ja/get-a-quote/`、`/products/` 都 200，页面 `<html lang="…">` 正确。

**回滚**：缓存不需要回滚（重新生成即可）。

---

## 8. 上线后复测清单（**本机浏览器 + 生产 SSH 抽查**）

**预计 20–30 分钟。任何一条不过就按对应步回滚，不要"先上着"。**

| # | 检查项 | 怎么做 | 通过判据 |
|---|---|---|---|
| 8.1 | title / H1 / 描述 | 浏览器打开首页、`/products/`、`/product-drawer-jewelry-box/` × en / ar / de 共 9 个 URL | 三种语种都是本轮的新标题（各自语言），H1 与标题一致，`<meta name="description">` 非空 |
| 8.2 | 报价价格 | `/get-a-quote/?style=rigid&qty=500`、`/ar/get-a-quote/` | 单价 **$2.83**、合计 **$1,417.43** |
| 8.3 | 询盘页 | `/inquiry/`、`/inquiry/?product=product-drawer-jewelry-box` | 字段齐全（姓名、邮箱、数量、产品）；带 `?product=` 时已预选；**只读不提交** |
| 8.4 | 375 px 横向溢出 | 浏览器 375 px 宽，看首页、`/products/`、1 个产品页、`/get-a-quote/`、`/ar/` | 都没有横向滚动条（`document.documentElement.scrollWidth` = 375） |
| 8.5 | 每页 1 个 title | `for u in / /products/ /jewelry-boxes/ /de/ /ja/get-a-quote/; do printf '%s ' $u; curl -s "https://www.packrivo.com$u" \| grep -c '<title>'; done` | 全部是 **1** |
| 8.6 | sitemap / robots 没变 | `curl -s https://www.packrivo.com/sitemap_index.xml \| head -20`；`diff <(curl -s https://www.packrivo.com/robots.txt) "$BACKUP/before-robots.txt"` | sitemap 里仍有 `de-sitemap.xml` 等语种子图；robots 与备份逐字相同 |
| 8.7 | 语种报价按钮 | en / ar / de / ja 首页 + 1 个产品页，看页头与页脚报价按钮 | 文字是各自语言（`عرض سعر فوري` / `Sofortangebot` / `すぐに見積もり`），href 都在 `/{lang}/get-a-quote/` |

> 8.4 只覆盖 375 px（本轮已修）。768 / 1024 的页头溢出见附录 A，不在本轮范围。

---

## 9. 整体回滚（逆序，**生产 SSH**）

**预计 15 分钟。每步都能单独执行。**

```bash
# 9.6 先撤两个 mu 补丁（各自独立）
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"

# 9.5 子主题
rm -rf "$WP/wp-content/themes/packrivo-home-v4"
cp -a  "$BACKUP/theme/packrivo-home-v4" "$WP/wp-content/themes/"
$WPCLI theme activate packrivo-home-v4

# 9.4 报价插件
rm -rf "$WP/wp-content/plugins/packrivo-quote"
cp -a  "$BACKUP/plugin/packrivo-quote" "$WP/wp-content/plugins/"
$WPCLI plugin activate packrivo-quote

# 9.3 内容
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"           rollback "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"

# 9.2 字典与路径表
cp -a "$BACKUP/i18n"/* "$I18N"/

# 9.7 清缓存
$WPCLI cache flush 2>/dev/null || true
$WPCLI transient delete --all
```

回滚完再跑 8.1 / 8.2 / 8.6 三条，确认回到上线前。

**最后手段**（分步回滚都不灵时）：`$WPCLI db import "$BACKUP/db/packrivo-before-launch.sql"` 回到上线前的数据库，再按 9.4–9.6 把文件放回。数据库回滚会丢掉上线后新增的询盘/订单，用之前先确认这段时间没有新数据。

---

## 附录 A · 已知问题（不在本次上线范围）

| 问题 | 实测（2026-10-28 本机） | 影响 | 何时处理 |
|---|---|---|---|
| 页头在 768 px 横向溢出（de 801、nl 896） | 溢出元素是配色切换按钮与一条导航链接 | iPad 竖屏下德/荷语首页出现横向滚动 | 下一轮（要动页头结构，需视觉确认） |
| 页头在 1024 px 横向溢出（7 个语种，en 1117 / nl 1221） | 溢出元素是 `.pk-actions` 那一簇（语言选择 + 阅读链接 + CTA） | 1024 宽平板/小笔记本出现横向滚动 | 下一轮 |
| `/ar/<文章路径>/` 返回 404 | 页面 `noindex,follow` 并链到英文版 | 阿语文章入口只能跳英文 | 等文章翻译排期 |
| `/contact/` 301 到 `/inquiry/` | 生产与本地一致 | 不构成重复标题页 | 若要做独立联系页再说 |
| 语种页 `Sample kit` 副链接在 1351–1780 px 按语种隐藏 | 子主题 `.pk-actions .pk-text-link` 的按语种媒体查询 | 副链接在部分宽度不显示（不影响报价按钮） | 已按实测调过阈值，属预期行为 |

## 附录 B · 时间与顺序速查

| 步 | 内容 | 预计 | 能否跳过 |
|---|---|---|---|
| 1 | 备份 | 10 min | 不能 |
| 2 | 字典 + 路径表 | 20–30 min | 不能（本轮主体） |
| 3 | 134 产品页 + 8 主页面 | 15–25 min | 不能（SEO 主体） |
| 4 | 报价插件 | 5 min | 可（插件内容不变时） |
| 5 | 子主题 | 5 min | 可（只改内容时） |
| 6 | 两个 mu 补丁 | 10 min | 27e 不能省（否则报价按钮全跳询盘） |
| 7 | 清缓存 | 5 min | 不能 |
| 8 | 复测 | 20–30 min | 不能 |
| 9 | 整体回滚 | 15 min | 只在出问题时执行 |
