# 上线手册 · Packrivo 一次性上线（RUNBOOK）

> **上线以本文件为准。`deploy/README.md` 里按轮次堆的 27g…28o 记录只作历史参考，命令和顺序以这里为唯一口径。**
> 本文件不含任何密码、密钥或 token：wp-cli 走服务器上已有的 SSH/wp-cli 配置，Cloudflare 清缓存走本机 `E:\破甲工作区\i18n\.cf.env`（只引用路径，不抄值）。

- 目标站点：`https://www.packrivo.com`（WordPress 6.7（本地镜像 6.7.2）+ 父主题 `packrivo-static-bridge-1.2.38` + 子主题 `packrivo-home-v4` + 报价插件 `packrivo-quote` + mu-plugins `packrivo-i18n.php`、`packrivo-site-remediation-20261022.php`）
- 本轮上线内容：134 个产品页新标题/描述（27g）、8 个主页面（27i）、11 语种字典与路径表（27g/27i/28k/28m/28n/28o）、报价插件与子主题新包、两个 mu 补丁
- 预计总耗时：**1.5–2 小时**（含复测），建议在**访问量低**的时段做（北京时间 02:00–05:00 或周末）
- 每一步都能单独回滚；第 9 步是整体逆序回滚
- 任一步失败就停，按该步「回滚」恢复后再继续；不要跳过判据硬往前做

## 变量（每开一个 SSH 会话先执行一次）

```bash
WP=/home/web/html/www.packrivo.com/wordpress          # WordPress 根目录
BACKUP=/home/web/backups/packrivo-launch-$(date +%Y%m%d-%H%M)   # 本次备份目录
WPCLI="wp --path=$WP"                                 # 以 root 执行时加 --allow-root
PKG=/home/web/packrivo-packages                       # 上传包的位置（第 2–6 步用）
I18N=$WP/wp-content/uploads/packrivo-i18n             # 字典与 paths.json 所在目录
SITE=https://www.packrivo.com                          # 导入脚本的站点守卫用
```

上线前从**本机**用 scp 把下面这些放到 `$PKG`（**不要**落地到 web 可访问目录）：

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

上传后在服务器上核对：`sha256sum $PKG/*.zip`，与上表逐字一致才继续。

---

## 1. 备份（机器：**生产 SSH**，本机不上场）

**预计 10 分钟。这一步不改任何文件。**

```bash
mkdir -p "$BACKUP"/{theme,plugin,mu,db,i18n,rollback}
cp -a "$WP/wp-content/themes/packrivo-home-v4"              "$BACKUP/theme/"
cp -a "$WP/wp-content/themes/packrivo-static-bridge-1.2.38" "$BACKUP/theme/"
cp -a "$WP/wp-content/plugins/packrivo-quote"               "$BACKUP/plugin/"
cp -a "$WP/wp-content/mu-plugins"                           "$BACKUP/mu/"
cp -a "$I18N"                                               "$BACKUP/i18n/"
$WPCLI db export "$BACKUP/db/packrivo-before-launch.sql" --skip-plugins --skip-themes
$WPCLI plugin list  --format=csv > "$BACKUP/before-plugin-list.csv"
$WPCLI theme list   --format=csv > "$BACKUP/before-theme-list.csv"
$WPCLI option get siteurl  > "$BACKUP/before-siteurl.txt"
sha256sum "$WP/wp-content/mu-plugins/packrivo-i18n.php" \
          "$WP/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" > "$BACKUP/before-mu.sha256"
curl -s https://www.packrivo.com/sitemap_index.xml > "$BACKUP/before-sitemap.xml"
curl -s https://www.packrivo.com/robots.txt        > "$BACKUP/before-robots.txt"
ls "$BACKUP/db/packrivo-before-launch.sql"
```

**通过判据**

- `$BACKUP/theme/` 下有两个主题目录，`$BACKUP/plugin/` 下有 `packrivo-quote`，`$BACKUP/mu/` 里有全部 mu 文件；
- 数据库导出的 `.sql` 大小 > 0 且 `grep -c "CREATE TABLE" "$BACKUP/db/packrivo-before-launch.sql"` > 0；
- `$BACKUP/before-mu.sha256` 两个哈希已记下（第 6 步比对、回滚校验用）；
- **数据库导出只留在服务器这台机器上，不下载到本机、不传到别处。**

**回滚**：本步不产生变更；上线确认成功后再删 `$BACKUP`。

---

## 2. 字典与路径表（机器：**生产 SSH**；底稿与脚本从本机 scp 上传）

**预计 20–30 分钟（含人工看冲突清单）。这是整个上线里唯一不能自动判定的一步。**

原则：**以当天新读的生产字典为底**，把本轮的 134 产品页、8 个主页面、60 条界面词、28k 的 207 条、28m 的报价页、28n 的 `Instant quote` / `Sample kit` 一起合并；**同一键两边都有值时生产优先**，冲突清单必须人工看过再装。

```bash
# 2.1 先取当天生产字典（保存到备份目录，不动线上文件）
cp -a "$I18N" "$BACKUP/i18n-today"
ls "$BACKUP/i18n-today" | head
grep -c '"' "$BACKUP/i18n-today/de-dict.json"     # 粗略确认不是空文件

# 2.2 合并（deploy/tools/merge-i18n.php：11 份 dict + 11 份 js + paths.json 一起合并，
#     生产优先、冲突写入 conflicts.json）
mkdir -p "$PKG/merged"
php "$PKG/merge-i18n.php" \
    --prod="$BACKUP/i18n-today" \
    --local="$PKG/i18n-merged" \
    --out="$PKG/merged" \
    --require=/get-a-quote/

# 2.3 看冲突清单再决定（冲突键的生产值已被采用；凡是要改的键，改完重跑 2.2，直到清单只剩可接受的）
cat "$PKG/merged/conflicts.json"

# 2.4 校验：11 语种都在、JSON 合法、paths 里必须有 /get-a-quote/
ls "$PKG/merged"/*-dict.json | wc -l          # 期望 11
ls "$PKG/merged"/*-js.json   | wc -l          # 期望 11
for f in "$PKG"/merged/*.json; do php -r 'json_decode(file_get_contents($argv[1]),true) ?: exit(1);' "$f" || echo "坏 JSON: $f"; done
grep -q '"/get-a-quote/"' "$PKG/merged/paths.json" && echo "paths ok"
php -r '$p=json_decode(file_get_contents($argv[1]),true); exit((is_array($p)&&count($p)>=146)?0:1);' "$PKG/merged/paths.json" && echo "paths count ok"

# 2.5 装上去（第 1 步已整目录备份；这里只覆盖 i18n 目录里的同名文件，不要把 conflicts.json 拷进去）
cp "$PKG/merged"/*-dict.json "$I18N/"
cp "$PKG/merged"/*-js.json   "$I18N/"
cp "$PKG/merged/paths.json"  "$I18N/"
ls -l "$I18N" | head          # 确认目标文件存在、web 用户可读
```

**通过判据**

- `ls "$PKG/merged"/*-dict.json | wc -l` = **11**、`*-js.json` = **11**；
- 每个 JSON 都能 `json_decode` 通过，没有报错行；
- `paths.json` 里包含 `"/get-a-quote/"`，条目数 ≥ 146（本机底稿是 146 条，生产只多不少）；
- `conflicts.json` 已人工逐条看过：凡是被生产值覆盖的键，都是我们**这次不打算改**的；
- 装完后 `/de/` 首页仍能正常渲染：`curl -s https://www.packrivo.com/de/ | grep -c 'lang="de"'` ≥ 1。

**回滚**：`cp -a "$BACKUP/i18n"/* "$I18N"/`（用第 1 步整目录备份覆盖回去）。字典是纯文件，回滚立即生效。

---

## 3. 内容：134 个产品页 + 8 个主页面（机器：**生产 SSH**）

**预计 15–25 分钟（134 页批量导入）。**

导入脚本是 `wp eval-file` 形式，命令形态固定为 `<plan|apply|verify|rollback-check|rollback> <package-root> <expected-site> <rollback-json> [all134]`：
- `plan` = dry-run，只读不写（27g 打印 `input_pages=134 preflight=PASS writes=0`，27i 打印 `hub_pages=8`）；
- `apply` = 首次在生产上**独占创建** rollback 文件（写入失败就不动数据库），再开数据库事务写入；站点地址、输入哈希、正文漂移任一不符都会中止；
- 27g 生产必须用 `all134` 范围（130 新页 + 27e 的 4 页，同一备份同一事务）。

```bash
# 3.1 dry-run（都先跑一遍）
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" plan "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"          plan "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"

# 3.2 真跑（脚本自己在生产上写 rollback 文件）
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" apply  "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" verify "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"          apply  "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"          verify "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
```

**通过判据**

- `plan` 输出 `input_pages=134 preflight=PASS writes=0` 和 `hub_pages=8 preflight=PASS`（数量不符就停下）；
- `apply` 输出 `apply_updated=134` / `apply_updated=8`（重复执行时 `already_current` 计入总数）；`$BACKUP/rollback/` 里出现本次生成的 rollback JSON（27g 约 1.2 MB，含全部原值）；
- `verify` 输出 `metadata_mismatches=0`（27i 还要求 `H1_body_URL_unchanged=8/8 inquiry_description_preserved=yes`）；
- 抽 3 页核对新标题：
  ```bash
  curl -s https://www.packrivo.com/product-drawer-jewelry-box/ | grep -o '<title>[^<]*</title>'
  curl -s https://www.packrivo.com/products/ | grep -o '<title>[^<]*</title>'
  curl -s https://www.packrivo.com/jewelry-boxes/ | grep -o '<title>[^<]*</title>'
  ```
  都是新的、含关键词的标题，且每页 `grep -c '<title>'` = **1**。

**回滚**（先 `rollback-check` 核对可回滚性，再由操作者决定执行；只恢复本次备份的标题/描述/meta，不覆盖其他页面）：

```bash
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"          rollback-check "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"          rollback       "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback-check "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback       "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
```

仓库里的 `deploy/rollback-27g.json` / `deploy/rollback-27i.json` 来自 **localhost:8081**，只能作二次核对，**不能**用于回滚生产。

---

## 4. 报价插件（机器：**生产 SSH**）

**预计 5 分钟。**

```bash
sha256sum "$PKG/packrivo-quote.zip"      # 必须等于 096d0c8e…
$WPCLI plugin install "$PKG/packrivo-quote.zip" --force
$WPCLI plugin list --format=csv | grep packrivo-quote    # 状态必须是 active
```

**通过判据**

- `wp plugin list` 里 `packrivo-quote` 状态是 `active`；
- 站点仍正常：`curl -s -o /dev/null -w '%{http_code}\n' https://www.packrivo.com/` = 200；
- **价格功能在配置器路由恢复后（第 6 步打完补丁）才能在生产验证**——现在 `/get-a-quote/` 仍被旧重定向 301 到 `/inquiry/`（见第 6 步空档期说明）；完整价格判据见第 8 步 8.2；
- `--force` 会**先删旧目录再解包**，所以第 1 步的 `$BACKUP/plugin/` 不能省；若这一步报错，站点会瞬间失去报价插件，立刻按回滚列恢复。

**回滚**：`rm -r "$WP/wp-content/plugins/packrivo-quote" && cp -a "$BACKUP/plugin/packrivo-quote" "$WP/wp-content/plugins/" && $WPCLI plugin activate packrivo-quote`。

---

## 5. 子主题（机器：**生产 SSH**）

**预计 5 分钟。**

```bash
# 5.1 先核对父主题没被换掉（目录名必须是 packrivo-static-bridge-1.2.38）
$WPCLI theme list --format=csv | grep packrivo-static-bridge
sha256sum "$PKG/packrivo-home-v4.zip"                            # 必须等于 5974ae0c…

# 5.2 安装
$WPCLI theme install "$PKG/packrivo-home-v4.zip" --force
$WPCLI theme list --format=csv | grep packrivo-home-v4           # 状态 active
```

**通过判据**

- 父主题目录仍是 `packrivo-static-bridge-1.2.38`（不是新版本、不是别的名字）；若父主题变了，**停下**，先确认父子兼容再继续；
- `packrivo-home-v4` 是 `active`；
- 首页 200 且首屏有标题、主按钮、产品图：`curl -s -o /dev/null -w '%{http_code}\n' https://www.packrivo.com/` = 200。

**回滚**：`rm -r "$WP/wp-content/themes/packrivo-home-v4" && cp -a "$BACKUP/theme/packrivo-home-v4" "$WP/wp-content/themes/" && $WPCLI theme activate packrivo-home-v4`。

---

## 6. mu 补丁（机器：**生产 SSH**）

**预计 10 分钟。两个补丁落在不同文件上，各自独立回滚。**

**顺序固定：先 `27e-quote-redirect.patch`，再 `packrivo-i18n-404-viewport.patch`。**

> **空档期说明（必须知道）**：第 4–6 步之间，`/get-a-quote/` 这条老重定向**仍然**是 301 到 `/inquiry/`（27e 还没打）。所以这段时间里新装的报价按钮会落到询盘页——**不会 404**，只是暂时看不到配置器。打完 27e 立刻恢复。这也是为什么 27e 要排在 404 补丁前面：先让 `/get-a-quote/` 可用，再谈语种 404 的兜底。

```bash
cd "$WP/wp-content/mu-plugins"

# 6.1 27e：把 get-a-quote 从旧别名列表里删掉（只删这一项，其余别名保留）
sha256sum packrivo-site-remediation-20261022.php
#   生产基线（27e 轮只读下载该文件记录的 SHA-256）: ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d
#   与上面不一致就先 diff 生产文件与基线，确认改动范围再决定，不要盲目打补丁
git apply -p1 --check "$PKG/27e-quote-redirect.patch" && echo CHECK-OK
git apply -p1         "$PKG/27e-quote-redirect.patch"
php -l packrivo-site-remediation-20261022.php

# 6.2 404 viewport：给「本页尚未翻译」的 404 补 <meta name="viewport"> 与最小样式
cd "$WP"           # 注意：这个补丁的路径前缀是 wp-content/mu-plugins/，要在 WP 根执行
sha256sum wp-content/mu-plugins/packrivo-i18n.php
#   该补丁基于本地 packrivo-i18n.php 的 404 块生成，生产无基线哈希记录；以 git apply --check 为准
git apply -p1 --check "$PKG/patches-28m/packrivo-i18n-404-viewport.patch" && echo CHECK-OK
git apply -p1         "$PKG/patches-28m/packrivo-i18n-404-viewport.patch"
php -l wp-content/mu-plugins/packrivo-i18n.php
```

**通过判据**

- 6.1 的 `sha256sum` 与生产基线 `ac0a2753…` 一致（生产未漂移时）；两个补丁的 `git apply --check` 都返回 `CHECK-OK`（返回失败**不要强打**，先 `diff -u` 生产文件与本机基线，重新出补丁）；
- 两个 `php -l` 都 `No syntax errors detected`；
- `/get-a-quote/` 不再 301：
  ```bash
  curl -sI https://www.packrivo.com/get-a-quote/ | head -3     # 期望 200，不是 301
  curl -sI https://www.packrivo.com/de/get-a-quote/ | head -3  # 期望 200
  ```
- 语种 404 有 viewport：
  ```bash
  curl -s https://www.packrivo.com/ar/2026/10/26/jewelry-pouch-box-customer-handoff/ | grep -c 'name="viewport"'   # 期望 1
  ```

**回滚**（两个独立）：

```bash
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"   # 回滚 27e
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"   # 回滚 404 补丁
sha256sum -c "$BACKUP/before-mu.sha256"   # 第 1 步记过哈希，回滚后核对两个文件回到原状
```

---

## 7. 清缓存（机器：**本机**（Cloudflare）+ **生产 SSH**（transient））

**预计 5 分钟。**

> **Cloudflare 这一段：负责人亲自执行。**
> 凭据只存在 `E:\破甲工作区\i18n\.cf.env` 这一个文件里，本手册只引用它的路径、不写任何值。
> 执行时不要把文件内容 `cat`/`echo` 出来，也不要把命令输出里的 token 贴进聊天、工单或日志；脚本只会打印 purge 结果和校验结论。

```bash
# 本机（负责人亲自执行）：Cloudflare 全站 purge + 12 路径 × 11 语种点名清 + 自动校验
# 凭据由脚本自己从 E:\破甲工作区\i18n\.cf.env 读取，这里不出现任何 key / token / 邮箱
python E:\破甲工作区\i18n\cf_purge.py

# 生产 SSH：清掉报价插件的 12h pricing transient（pricing.json 变更后必须清）
$WPCLI transient delete --all
$WPCLI cache flush 2>/dev/null || echo "no object cache plugin"   # 有对象缓存插件时生效，没有就如实记录
```

**通过判据**

- `cf_purge.py` 结尾输出 `仍过期: 无 —— 全部同步`；
- `curl -sI https://www.packrivo.com/de/ | head -5` 返回 200，`cf-cache-status` 不再是旧缓存；
- 页面里的语言标记正确（`<html lang="de">`）。

**回滚**：缓存清了不需要回滚（重新生成即可）。

---

## 8. 上线后复测清单（机器：**本机浏览器** + **生产 SSH** 抽查）

**预计 20–30 分钟。逐条照做，任何一条不过就按对应步回滚，不要"先上着"。**

| # | 检查项 | 怎么做 | 通过判据 |
|---|---|---|---|
| 8.1 | title / H1 / 描述（首页、`/products/`、1 个产品页 × en / ar / de） | 浏览器打开 9 个 URL，看标签页标题、页面 H1、右键查看 `<meta name="description">` | 三种语种都是新的、各自语言的标题；H1 与标题不冲突；描述非空 |
| 8.2 | 报价价格 | 浏览器打开 `/get-a-quote/?style=rigid&qty=500` 和 `/ar/get-a-quote/` | 单价 **$2.83**、合计 **$1,417.43**（与 28m/28n 逐格一致的数值） |
| 8.3 | 询盘页 | `/inquiry/` 和 `/inquiry/?product=product-drawer-jewelry-box` | 表单字段齐全（姓名、邮箱、数量、产品）；带 `?product=` 时产品已预选；**只读不提交**（28l 起约定：复测不提交任何询盘） |
| 8.4 | 375 px 横向溢出 | 浏览器 375 px 宽，看首页 / `/products/` / 1 个产品页 / `/get-a-quote/` / `/ar/` | 都没有横向滚动条（`document.documentElement.scrollWidth` = 375） |
| 8.5 | 每页 1 个 title | `curl -s <URL> | grep -c '<title>'`，抽 5 个 URL | 每个都是 **1** |
| 8.6 | sitemap / robots 没变 | `curl -s https://www.packrivo.com/sitemap_index.xml`、`robots.txt`，与第 1 步的 `$BACKUP/before-sitemap.xml` / `before-robots.txt` diff | sitemap 条目里仍有 `de-sitemap.xml` 等语种子图；`robots.txt` 与上线前逐字一致 |
| 8.7 | 语种报价按钮 | en / ar / de / ja 首页与 1 个产品页，看页头与页脚的报价按钮 | 文字是各自语言（`عرض سعر فوري` / `Sofortangebot` / `すぐに見積もり`），链接都指向 `/{lang}/get-a-quote/` |

> 8.4 只覆盖 375 px（本轮已修）。**已知问题**里列了 768 / 1024 的页头横向溢出，不在本轮范围。

部分检查的固定命令（其余走浏览器逐条照做）：

```bash
# 8.5 每页 1 个 title（抽 5 个 URL）
for u in https://www.packrivo.com/ https://www.packrivo.com/products/ \
         https://www.packrivo.com/de/ https://www.packrivo.com/ar/ \
         https://www.packrivo.com/product-drawer-jewelry-box/; do
  echo -n "$u  title数="; curl -s "$u" | grep -c '<title>'
done
# 8.6 sitemap / robots 与第 1 步快照对比
diff "$BACKUP/before-sitemap.xml" <(curl -s https://www.packrivo.com/sitemap_index.xml) && echo "sitemap 未变"
diff "$BACKUP/before-robots.txt"  <(curl -s https://www.packrivo.com/robots.txt)       && echo "robots 未变"
```

**回滚**：本步是纯检查；哪条不过就回滚对应步——8.1/8.5/8.6 → 第 3、2 步；8.2/8.7 → 第 4、6 步；8.3 → 第 5 步；8.4 → 第 5、6 步。

---

## 9. 整体回滚（逆序，机器：**生产 SSH**）

**预计 15 分钟。每步都能单独执行，不依赖后面的步骤。**

```bash
# 9.7 缓存：无需回滚（重新生成）；若想立即回到旧内容，先清一次缓存让旧文件重新被拉到边缘：
python E:\破甲工作区\i18n\cf_purge.py    # 本机执行

# 9.6 先回滚两个 mu 补丁（顺序无所谓，各自独立）
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"

# 9.5 子主题
rm -r "$WP/wp-content/themes/packrivo-home-v4"
cp -a "$BACKUP/theme/packrivo-home-v4" "$WP/wp-content/themes/"
$WPCLI theme activate packrivo-home-v4

# 9.4 报价插件
rm -r "$WP/wp-content/plugins/packrivo-quote"
cp -a "$BACKUP/plugin/packrivo-quote" "$WP/wp-content/plugins/"
$WPCLI plugin activate packrivo-quote

# 9.3 内容（134 产品页 + 8 主页面，先 rollback-check 再 rollback）
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"          rollback-check "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
$WPCLI eval-file "$PKG/scripts/hubs-27i/import.php"          rollback       "$PKG" "$SITE" "$BACKUP/rollback/rollback-27i.json"
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback-check "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback       "$PKG" "$SITE" "$BACKUP/rollback/rollback-27g.json" all134

# 9.2 字典与路径表
cp -a "$BACKUP/i18n"/* "$I18N"/
```

最后再跑一遍第 8 步的 8.1 / 8.2 / 8.6 三条，确认回到上线前状态。

**判据**：两个 mu 文件 `sha256sum -c "$BACKUP/before-mu.sha256"` 全部 OK；子主题与插件在 `wp theme/plugin list` 里回到 `active`；27g/27i `rollback-check` 输出 `rollback_eligible` 且 rollback 后 `verify` 的 `metadata_mismatches=0`；8.1/8.2/8.6 复测与上线前一致。

**最后手段**（上面的分步回滚都不灵时）：`$WPCLI db import "$BACKUP/db/packrivo-before-launch.sql"` 回到上线前的数据库，再按 9.4–9.6 把文件也放回去。数据库回滚会丢掉上线后新增的询盘/订单，用之前先确认这段时间没有新数据。

---

## 附录 A · 已知问题（不在本次上线范围）

> **28p 已修**：页头在 768 px 和 1024 px 的横向溢出。做法是把汉堡断点统一到实测得到的 W=1294（12 语种桌面页头右侧留 16 px 余量放得下的最大宽度），1024 及以下全语种进汉堡；768 那一档的溢出其实在页脚底部（`.footer-bottom` 在 768–940 之间不换行，`.footer-bottom` 现在允许换行）。验证见 `deploy/verification-28p/`（960 组扫描 0 溢出 + 16 张截图）。

| 问题 | 实测 | 影响 | 何时处理 |
|---|---|---|---|
| `/ar/<文章路径>/` 返回 404 | 页面 `noindex,follow` 并链到英文版 | 阿语站内文章入口只能跳英文 | 等文章翻译排期 |
| `/contact/` 301 到 `/inquiry/` | 生产与本地一致 | 不构成重复标题页 | 若要做独立联系页再说 |
| 语种页 `Sample kit` 链接在 1351–1780 px 间按语种隐藏 | `.pk-actions .pk-text-link` 的按语种媒体查询 | 副链接在这些宽度不显示（不影响报价按钮） | 已按实测调过阈值，属预期行为 |

## 附录 B · 时间与顺序速查

| 步 | 内容 | 机器 | 预计 | 是否可跳过 |
|---|---|---|---|---|
| 1 | 备份 | 生产 SSH | 10 min | 不可 |
| 2 | 字典 + 路径表 | 生产 SSH（底稿自本机） | 20–30 min | 不可（本轮主体） |
| 3 | 134 产品页 + 8 主页面 | 生产 SSH | 15–25 min | 不可（SEO 主体） |
| 4 | 报价插件 | 生产 SSH | 5 min | 可（若报价插件不变） |
| 5 | 子主题 | 生产 SSH | 5 min | 可（若只改内容） |
| 6 | 两个 mu 补丁 | 生产 SSH | 10 min | 27e 不可省（否则报价按钮全跳询盘） |
| 7 | 清缓存 | 本机 + 生产 SSH（Cloudflare 那一步：负责人亲自执行） | 5 min | 不可 |
| 8 | 复测 | 本机浏览器 + 生产 SSH | 20–30 min | 不可 |
| 9 | 整体回滚 | 生产 SSH（清缓存在本机） | 15 min | 只在出问题时执行 |
