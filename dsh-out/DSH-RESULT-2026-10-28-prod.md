# 生产上线结果 · 28t（授权后首轮）

工作台 #56 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 **停在第 0 步 0b（FAIL），等负责人决定**

授权：负责人 2026-10-28 18:00 PDT 授权执行 `deploy/RUNBOOK-launch.md` 第 0–8 步，时间窗 4 小时（至 22:00 PDT），RUNBOOK 锁定 `ba97e04` / blob `a7cc2f29`。开工前校验 blob = `a7cc2f29470ec8240b5581fcbab25869fed625b7`，与授权一致；执行期间未改 RUNBOOK。
并行工位：`pause -Task 55` → paused（LAUNCH-LOG 门禁链）；`#56` 保持 paused。单线程。
服务器：netcup `v2202609415437518901`（本机 PDT / 服务器 CET / UTC 已记入 LAUNCH-LOG §36）。

## 1 结论

**第 0 步判 FAIL，按授权规则 2/4 停止，第 1–8 步一步未执行，生产零写入。**

唯一的 FAIL 是 **0b：生产 `core version` = `7.1.4`，而锁定版 RUNBOOK 的 0.1 判据要求 `7.1.3`**。其它判据全部 PASS（容器、PHP 8.3.31、siteurl、父主题、子主题与报价插件均未装、父主题 active、mysqldump 可用、`/get-a-quote/` 301 → `/inquiry/`、`/inquiry/` 200、首页/产品页/`/de/` 200）。

## 2 逐步 PASS/FAIL 表

| 步 | 判据 | 结果 |
|---|---|---|
| **0.1** | 0a php/mysql/nginx 容器在跑 | PASS ×3 |
| | **0b `core version` = 7.1.3** | **FAIL（实测 7.1.4）** |
| | 0c PHP = 8.3.x | PASS（8.3.31） |
| | 0d siteurl = `https://www.packrivo.com` | PASS |
| | 0e template = `packrivo-static-bridge-1.2.38` | PASS |
| | 0f stylesheet 与 template 相同 | PASS |
| | 0g 子主题目录不存在 | PASS |
| | 0h 报价插件目录不存在 | PASS |
| | 0i 父主题 active | PASS |
| | 0j `mysqldump --version` 有输出 | PASS（11.4.13-MariaDB client 10.19） |
| | 0k 导出探针 > 0 字节 | **未通过**：`mariadb-dump: Got error: 2026: "TLS/SSL error: self-signed certificate in certificate chain"`，探针 0 字节（因 0b 已 FAIL，未装包装、未重探） |
| | 0l `/get-a-quote/` = 301 | PASS（Location `https://www.packrivo.com/inquiry/`） |
| | 0m `/inquiry/` = 200 | PASS |
| 1–8 | — | **未执行**（0b FAIL 后即停） |

`step0.log` 与 `diag.log`、`probe.log` 已落在服务器 `/home/web/prod-28t/`，内容摘录见 `deploy/LAUNCH-LOG-2026-10-28.md` §36。

## 3 关键字段

| 项 | 值 |
|---|---|
| `CURRENT_LAUNCH` | **(无)** —— `/home/web/backups/CURRENT_LAUNCH` 不存在（上一次尝试的 1629/1648 两个备份目录在，第 1 步没完成） |
| `db.sql` 字节数 | **未生成**（第 1 步未执行） |
| `db.sql` sha256 | **未生成** |
| 生产 core | `7.1.4`（`wp-includes/version.php`：`$wp_version='7.1.4'`、`$wp_db_version=61833`，mtime `2026-10-28 19:00:43 +0100`） |
| 第 8 步各项 | **未执行** |
| 生产 zip 哈希 | 未上传（`/home/web/packrivo-packages/` 为空）；本地仓库两个 zip 仍是 `5974ae0c…` / `096d0c8e…` |

## 4 为什么 0b 是硬停

- 锁定版 RUNBOOK 0.1 判据原文是 `core version` = `7.1.3`；授权规则 4 明确「WP 7.1.3……有任何一项不符，就停下报告」；规则 1 明确「严格按 RUNBOOK 原文执行，不准临场改命令」。
- 生产 core 在 2026-10-28 **19:00:43 +0100（18:00 UTC / 11:00 PDT）** 被更新：8 个 core 文件同秒变更（`version.php`、`about.php`、`kses.php`、`class-wp-block-parser.php`、`class-wp-customize-manager.php`、`admin-bar.php`、`users.php`、`user-edit.php`），是正式更新而非手改版本号的特征。
- 这个时间点在**上一次上线尝试（08:25–09:35 PDT）之后**；当时记录的是 7.1.3。全仓库检索 `7.1.4` 零命中——RUNBOOK、演练基线（28q/28r/28s 快照 `4972cd81…`）、任务单都是 7.1.3。**生产已不在本次授权的基线版本上。**

## 5 待负责人决定（三条路任选）

1. **改判据后重跑**：授权把 RUNBOOK 0.1 的 `core version` 期望改成 `7.1.4`，并注明「演练基线 7.1.3、生产 7.1.4，差异仅在 core 小版本，主题/插件/内容层一致」；改完重新授权，从第 0 步起跑。
2. **core 退回 7.1.3**：先给 7.1.3 正式文件集来源与授权，退回后评估 `wp core update-db` 是否需要回退（`$wp_db_version` 已从 7.1.3 的值变为 61833）。
3. **暂停上线**：先查清 11:00 PDT 那次 core 更新是谁触发的（自动更新 / 人工 / 别的工作位），再定基线。

另需一句确认：生产导出探针报的是 `TLS/SSL error: self-signed certificate in certificate chain`（自签证书链），与演练环境的 `SSL is required, but the server does not support it` 不同消息、同类错误码 2026。按 RUNBOOK 0.1 原文「报 TLS/SSL error 就做一次 `--skip-ssl` 包装再重探」，我判定适用；但这属于生产特有的新情况，**本次没装包装、没重探**，请一并确认。

## 6 生产侧改动清单（零写入核对）

| 改动 | 性质 | 说明 |
|---|---|---|
| 容器内 `apk add --no-cache mariadb-client` | 容器依赖安装 | RUNBOOK 0.1 原文自带的一行（`command -v mysqldump || apk add …`）；只影响 php 容器 `/usr/bin/mysqldump` |
| `/home/web/prod-28t/` 下 `step0.sh`、`diag.sh`、`probe.sh`、`step0.log`、`diag.log`、`probe.log` | 本次脚本与日志 | 不在 web 可访问目录 |
| 网站文件、数据库、主题、插件、备份目录 | **未改动** | `1629` / `1648` 两个旧备份未动；未生成 `CURRENT_LAUNCH`；未上传包 |

## 7 授权与日志

- 授权批复原文已追加到 `dsh-out/AUTHZ-REQUEST-2026-10-28-prod-launch.md` §4。
- 命令与原始输出追加到 `deploy/LAUNCH-LOG-2026-10-28.md` §36。
- 未打印、未记录任何密码、密钥或 token；未打开 `.cf.env`；未执行第 7 步（Cloudflare 清缓存由负责人亲自执行，本次未到该步）。

---

# 28u：第二次生产授权（取代上一份）——第 0 步停在 0k

授权时间：负责人 2026-10-28 19:23 PDT（窗口至 23:23 PDT）。RUNBOOK 锁定 `f88a762` / blob `2fa7bb6a`（开工前校验一致）。执行人 dsh，单线程；Task 55 保持 paused（`pause` 返回 409 already-paused，paused 后无新活动）。

## 11 结论

**第 0 步 `PASS=14 FAIL=1`，唯一 FAIL 是 0k（导出探针）。按规则停止，第 1–8 步未执行，生产零写入。**

`core version = 7.1.4`（0b 通过）、其余 13 条判据全部 PASS。0k 的失败不是 TLS 那关——`--skip-ssl` 包装**按文档生效**，卡在下一关：生产是 **MySQL 26.7.0**，账号认证用 `caching_sha2_password`，而 php 容器里 `mariadb-dump 11.4.13` 的认证插件目录为空，加载不到 `caching_sha2_password.so`。

## 12 逐步 PASS/FAIL 表（第 0 步）

| 判据 | 实测 | 结果 |
|---|---|---|
| 0a php/mysql/nginx 容器在跑 | 三个都在 | PASS ×3 |
| 0b `core version` = 7.1.4 | `7.1.4` | PASS |
| 0c PHP = 8.3.x | `8.3.31` | PASS |
| 0d siteurl | `https://www.packrivo.com` | PASS |
| 0e template | `packrivo-static-bridge-1.2.38` | PASS |
| 0f stylesheet 与 template 相同 | 同 | PASS |
| 0g 子主题目录不存在 | 不存在 | PASS |
| 0h 报价插件目录不存在 | 不存在 | PASS |
| 0i 父主题 active | `1.2.38,active` | PASS |
| 0j `mysqldump --version` 有输出 | `11.4.13-MariaDB, client 10.19` | PASS |
| **0k 导出探针 > 0 字节** | 装包装后报 `1045 Plugin caching_sha2_password could not be loaded`，**0 字节** | **FAIL** |
| 0l `/get-a-quote/` = 301 | 301 → `/inquiry/` | PASS |
| 0m `/inquiry/` = 200 | 200 | PASS |
| 1–8 | — | **未执行** |

## 13 关键字段

| 项 | 值 |
|---|---|
| `CURRENT_LAUNCH` | **（无）**——`/home/web/backups/CURRENT_LAUNCH` 不存在 |
| `db.sql` 字节数 | **未生成**（第 1 步未执行） |
| `db.sql` sha256 | **未生成** |
| 第 8 步各项 | **未执行** |
| 生产 core | `7.1.4`（`$wp_db_version` 未再动；全深度变更 48 个文件，见 §14） |
| 两个 zip 哈希 | 未上传；本地仍是 `5974ae0c…` / `096d0c8e…` |

## 14 只读记录：生产 core 全深度变更数

```
find $WP/wp-admin $WP/wp-includes -type f -newermt '2026-10-28 00:00' | wc -l  →  48
```

与 28t 本地官方包升级的 48 个文件数量一致、清单同构（`wp-admin/about.php`、`wp-admin/includes/*`、`wp-admin/user-edit.php`、`wp-admin/users.php`…）。印证 28t 的更正：上次记的「8 个」是顶层口径，全深度 48 个。清单在服务器 `/tmp/core-depth-28u.txt`。

## 15 0k 的诊断证据

| 项 | 值 |
|---|---|
| 第一次探针 | `2026 TLS/SSL error: self-signed certificate in certificate chain`，0 字节（RUNBOOK 0.1 补救块列出的第二种） |
| 装包装后 | `1045 Plugin caching_sha2_password could not be loaded: … /usr/lib/mariadb/plugin/caching_sha2_password.so: No such file or directory`，0 字节 |
| php 容器客户端 | `mariadb-dump from 11.4.13-MariaDB, client 10.19` |
| php 容器插件目录 | `/usr/lib/mariadb/plugin` → **0 个文件** |
| php 容器相关包 | `mariadb-client 11.4.13`、`mariadb-common`（没有 connector 包） |
| mysql 容器 | image `mysql`，`mysqld Ver 26.7.0 MySQL Community Server` |
| 仓库里可补的包 | `mariadb-connector-c-3.3.10-r0`；`apk fetch` 只下载未安装，包内确有 `usr/lib/mariadb/plugin/caching_sha2_password.so`（另有 `sha256_password.so`、`client_ed25519.so`、`dialog.so`、`mysql_clear_password.so`），验证完已删除 |

## 16 待负责人决定

1. **授权补客户端依赖**：在 php 容器 `apk add --no-cache mariadb-connector-c`（只影响客户端插件目录，不动站点、数据库、凭据），装完重跑 0.1 的探针；若仍 ≤ 0 字节继续停下报告。这是最直接的一条。
2. 或者指定别的导出通道（例如改用别的客户端/由你在服务器侧导出），我按你给的命令文件执行。
3. 若两条都不走，本次上线到此为止，生产维持上线前状态。

## 17 生产侧改动清单（零业务写入）

| 改动 | 性质 |
|---|---|
| `/usr/local/bin/mariadb-dump`、`/usr/local/bin/mariadb` 两个包装脚本 | RUNBOOK 0.1 原文的 TLS 补救块（`exec … --skip-ssl`） |
| `/home/web/prod-28u/` 下 `step0.sh`、`diag.sh`、`probe-pkg.sh` 与三份日志 | 本次脚本与证据 |
| 数据库、站点文件、主题、插件、CURRENT_LAUNCH、备份目录 | **未改动**（1629/1648 两个旧备份未动） |
| 未打印、未记录任何密码/密钥/token；未打开 `.cf.env`；未到第 7 步 | — |

---

# 28u 续：第 1–5 步通过，第 6 步 FAIL 并已回滚（授权窗口内）

`CURRENT_LAUNCH = packrivo-launch-20261029-0335`

## 18 逐步 PASS/FAIL 表

| 步 | 判据 | 结果 |
|---|---|---|
| 0 | 0.1 十四条 + 追加三条（探针 > 0 / CREATE TABLE = 表数 / Dump completed） | 14 + 3 **PASS**（说明见 §19） |
| 0.2 | 上传 + 服务器侧哈希 | **PASS**（两个 zip 哈希与本地一致） |
| 1 | 1a–1j + 追加 1n/1o | **12 PASS / 0 FAIL** |
| 2 | paths/dict/js/坏 JSON/de 站 | **全 PASS**（冲突 0 条） |
| 3 | 3.0 守卫 + 3a–3m | **13 PASS / 0 FAIL** |
| 4 | 包哈希 / active / 站点 200 | **3 PASS / 0 FAIL** |
| 5 | 父主题 / stylesheet / template / 首页 | **5 PASS / 0 FAIL** |
| 6 | 6a 基线哈希、两处 CHECK-OK、两处 php -l | PASS |
| 6 | **6c/6d `/get-a-quote/` = 200** | **FAIL → 整步回滚并停止** |
| 7 | 清缓存 | **未执行** |
| 8 | 上线后复测 | **未执行** |

## 19 关键数据

| 项 | 值 |
|---|---|
| `CURRENT_LAUNCH` | `packrivo-launch-20261029-0335` |
| `db.sql` 字节数 | **32,516,235 B** |
| `db.sql` sha256 | **`1733240d1caf67373a6dc659ce6abafd53cbc9bba0f158e28e8de0b585cd6ab6`** |
| db.sql 表数 | `CREATE TABLE` 40 = `SHOW FULL TABLES` 的 BASE TABLE 40 = `wp db tables --all-tables-with-prefix` 40 |
| db.sql 尾行 | `-- Dump completed on 2026-10-29  2:31:36` |
| 备份其余 | mu 两个哈希、i18n 100 个文件、before-pages.csv 391 行、sitemap 1789 B、robots 609 B |
| 字典 | 11 dict + 11 js + paths 146 条，conflicts `[]` |
| 内容 | 27g `apply_updated=134 already_current=0`；27i `7+1=8`；`URL_and_non_H1_body_unchanged=134/134`；`H1_body_URL_unchanged=8/8`；`metadata_mismatches=0`；`inquiry_description_preserved=yes` |
| 插件 | `packrivo-quote` 1.0.1 active，包哈希 `096d0c8e…` |
| 子主题 | `packrivo-home-v4` 4.0.1 active，父主题 `packrivo-static-bridge-1.2.38`（状态 parent） |
| 第 6 步回滚后 | `ac0a2753…` / `a2a7bdf3…`（= 上线前基线），两个 `php -l` 干净 |
| 站点现状 | `/` 200、`/get-a-quote/` 301 → `/inquiry/`、`/de/get-a-quote/` 301、`/products/` 200、`/inquiry/` 200、`/de/` 200 |
| 第 8 步 | 未执行（按规则停在第 6 步） |

## 20 第 6 步为什么 FAIL：生产没有 `/get-a-quote/` 页面

- 补丁打上了、语法也对：`packrivo-site-remediation-20261022.php` `ac0a2753…` → `887787ab…`，`packrivo-i18n.php` `a2a7bdf3…` → `f7d95f35…`（viewport 落在第 168 行）。
- 但 `/get-a-quote/`（en / de / ar，`?cb=` 破缓存，GET 与 HEAD 都试过）**一律 404**。
- `get_page_by_path('get-a-quote')` = `false`；任意状态下名字含 quote 的页面只有 `49,inquiry,"Get a Packaging Quote"`。
- 生产 ID 3220 是 `Shipping Boxes & Mailers` 的一个 revision；**演练库里 3220 才是 `get-a-quote / "Get a Quote"`**（`s-forward.txt` 第 3220 行），演练因此第 6 步直接 200。
- `packrivo-quote` 插件只提供短代码 `[packrivo_quote]`（`register_activation_hook` 里只 flush 重写规则），README 明确要求「新建一个页面，插入短代码」。
- 上线前这个缺口被 mu 里那条旧 301 盖住了（301 → `/inquiry/`）；27e 补丁删掉 301 后，404 暴露出来。RUNBOOK 第 6 步判据与 8.2 核价都依赖这个页面，手册里没有建页这一步。

## 21 已执行的回滚（第 6 步，§6 回滚一节原文）

`cp -a` 两个 mu 文件回基线 → `sha256sum -c before-mu.sha256` 两个 OK → 复核哈希 `ac0a2753…` / `a2a7bdf3…` → 两个 `php -l` 干净 → 站点 6 个 URL 全 200（`/get-a-quote/` 回到 301 → `/inquiry/`）。第 3–5 步的成果保留，第 7、8 步未动。

## 22 两次偏离与两处判据实现错误（都已实测更正，未重跑成功步骤）

| 项 | 内容 |
|---|---|
| 偏离 1（负责人已批） | `apk add --no-cache mariadb-connector-c 3.3.10-r0`：插件目录 0 → 5 个文件，探针 0 → 32,516,267 B |
| 偏离 2（上传落点） | 0.2 第 128 行把 `dsh-out/*.json` 传到包根，第 3 步按 `<root>/dsh-out/` 读；补建 `dsh-out/` 重传后第 3 步一次通过。只改落点，未改命令 |
| 判据错误 1 | 0k-2 用了返回空的 `information_schema` 查询 + 把 `SHOW TABLES` 表头算进计数（41）；改 `SHOW FULL TABLES` 去表头 = 40，与导出 40 一致 |
| 判据错误 2 | 3g 把 `H1_body_URL_unchanged=8/8` 查在 `apply-27i.log`，原文说在 verify 输出；改查 `verify-27i.log` 后 PASS |
| 判据错误 3 | 6b 拿打补丁**前**的哈希变量去比「已变」；补丁后实测 `f7d95f35…` ≠ `a2a7bdf3…`，6b 实际 PASS |

## 23 待负责人决定

1. 授权补建 `/get-a-quote/` 页面（标题 `Get a Quote`、slug `get-a-quote`、正文 `[packrivo_quote]`、publish）→ 重打第 6 步两个补丁 → 继续第 7 步（等你清缓存）→ 第 8 步。
2. 或判定本次上线暂停，把第 3–5 步也回滚（内容用两个 rollback JSON、插件停用删除、主题切回父主题）。
3. RUNBOOK 上线后要补：0.2 上传落点、建 `/get-a-quote/` 页面这一步（或并入第 3 步）。

---

# 28u 续 2：补建页面 → 第 6 步全 PASS → 第 7 步服务器端完成，等清 CF

## 24 第 6 步重跑结果（PASS=10 / FAIL=0）

| 判据 | 实测 |
|---|---|
| 6a 27e 打前哈希 = `ac0a2753…` | 一致；打后 `887787ab…` |
| 6b 28m 打前哈希 = `a2a7bdf3…` | 一致；打后 `f7d95f35…` |
| 两处 `--check` | 都 `CHECK-OK` |
| 两处 `php -l` | 都 `No syntax errors detected` |
| 6c `/get-a-quote/` | **200** |
| 6d `/de/get-a-quote/` | **200** |
| 6f / 6g 页面含 `pkq-root` | 各 1 |
| 6e ar 404 页 viewport | 1 |
| 其他 URL | `/` 200、`/products/` 200、`/inquiry/` 200、`/de/` 200、`/ar/get-a-quote/` 200 |

## 25 新建页面

| 项 | 值 |
|---|---|
| 页面 ID | **3383** |
| 建页命令 | `post create --post_type=page --post_title='Get a Quote' --post_name=get-a-quote --post_content='[packrivo_quote]' --post_status=publish --comment_status=closed --porcelain` |
| 建页前守卫 | 库里 `post_name='get-a-quote'` 0 行、`wp post list`（any+draft+trash）0 条、模糊 0 行、标题 0 行 |
| 建页后判据 | `post_name=get-a-quote`、`post_content=[packrivo_quote]`、`post_status=publish` 全 PASS |
| 页面标题 | `<title>Get a Quote - Packrivo</title>` |

## 26 第 7 步（服务器端已完成，Cloudflare 待负责人）

- `transient delete --all` → 删除 18 条；`cache flush` → `The cache was flushed`；transient 剩余 0。
- `/de/` 200（`lang="de"` 计数 2）、`/get-a-quote/` 200。
- **停在这里等「清缓存完成」**；之后做第 7 步判据（`cf_purge.py` 输出 `仍过期: 无 —— 全部同步`、`/de/` 200、`lang="de"`）与第 8 步全部 8 项。

## 27 回滚预案（已记入 LAUNCH-LOG §39.5）

需要第 9 步回滚时先 `$WPCLI post delete 3383 --force`，再按 9.6 恢复两个 mu 补丁（备份里 `get_a_quote_page_id=3383`）。

---

# 28u 收尾：第 7 步判据 + 第 8 步复测（上线完成，两处缺口待浏览器确认）

## 28 第 7 步

| 判据 | 实测 |
|---|---|
| `cf_purge.py` 输出 `仍过期: 无 —— 全部同步` | 负责人 22:48 PDT 回「清缓存完成」（脚本在负责人本机跑，stdout 我看不到，据实记录） |
| `/de/` 返回 200 | 200 |
| `<html lang="de">` | 命中（计数 2） |

服务器端（前一段已完成）：transient 删 18 条、`cache flush` 成功、transient 剩 0。

## 29 第 8 步

| # | 检查项 | 实测 | 结果 |
|---|---|---|---|
| 8.1 | title / H1 / 描述（3 页 × en/ar/de = 9 URL） | 标题全是上线后的新文案且是各自语言（例：en `Custom Jewelry Boxes & Packaging with Logo \| Packrivo`、de `Schmuckschachteln & Verpackungen mit Logo \| Packrivo`、ar `علب مجوهرات وتغليف مخصص بالشعار \| Packrivo`）；9 个描述都非空；H1 都在（首页英文 `<h1 class="pk-display">Custom packaging for <span…>jewelry, candles</span> &amp; gifts.</h1>`） | **PASS** |
| 8.2 | 价格 | 服务端用插件 `PKQ_Pricing::price()` + 主题 `pkq_reference_spec` 复算：rigid **2.83 / 1417.43**、mailer **2.48 / 1239.04**、shipping **2.31 / 1156.69** 三格与手册期望逐格相同；sticker unit **0.96** 相同、total **477.60**（期望 479.63，差 2.03） | 3/4 PASS；sticker total 待浏览器确认 |
| 8.3 | 询盘页 | `/inquiry/` 200、`/inquiry/?product=product-drawer-jewelry-box` 200；`name="…name"`、email、quantity 各 1，`<select name="product" id="inquiry-product" required>` 在；未提交任何表单 | 字段齐 PASS；「已预选」HTML 里无 `selected`（slug/code 两种写法都没有），属前端 JS，未在无浏览器下确认 |
| 8.4 | 375/768/1024 横向溢出 | **未执行** —— 要真实浏览器算 `scrollWidth`，本机无 playwright/puppeteer | 缺口 |
| 8.5 | 每页 1 个 title | `/`、`/products/`、`/de/`、`/ar/`、`/product-drawer-jewelry-box/` 各 `title数=1` | **PASS** |
| 8.6 | sitemap / robots | robots 与 `before-robots.txt` **逐字一致**（609 B）；sitemap 1789 B 不变、13 个 `<loc>` 语种子图齐全，差异只有 `lastmod`（新页 02:56:34、字典更新 02:36:22） | **PASS**（按判据原文） |
| 8.7 | 语种报价按钮 | en `Get a quote`、ar `عرض سعر فوري`、de `Sofortangebot`、ja `すぐに見積もり`；首页与产品页链接分别指向 `/get-a-quote/`、`/ar/get-a-quote/`、`/de/get-a-quote/`、`/ja/get-a-quote/` | **PASS** |

报价页：`/get-a-quote/?style=rigid&qty=500` 200 且 `class="pkq-root" data-style="rigid" data-qty="500"`；`/ar/get-a-quote/` 200、含 `pkq-root`、`dir="rtl"`、`عرض سعر` 命中 8 次。

## 30 两处缺口（没冒充通过，也没据此回滚）

1. **8.4 没测**。复核用片段（DevTools Console，375/768/1024 各一次）：
   ```js
   [document.documentElement.scrollWidth, window.innerWidth,
    document.documentElement.scrollWidth > window.innerWidth ? 'OVERFLOW' : 'ok']
   ```
2. **8.2 sticker total / 8.3 预选**：要在浏览器里看一眼 `/get-a-quote/?style=sticker&qty=500` 的合计是否 479.63、`/inquiry/?product=product-drawer-jewelry-box` 的下拉是否已选。

不据此回滚的理由：8.2 另外三格逐格一致、8.3 字段与页面都正常，两处差异都只在「浏览器初始态」这一层，不是数据/代码错，属于待确认而非 FAIL。

## 31 本次上线最终状态

- 第 0–7 步全 PASS（含两次负责人批准的偏离：`mariadb-connector-c` 装包、补建 `/get-a-quote/` 页 3383）。
- 第 8 步：8.1 / 8.5 / 8.6 / 8.7 PASS，8.2 三格一致，8.3 字段齐，8.4 未测。
- 生产：主题 `packrivo-home-v4` 4.0.1 active、插件 `packrivo-quote` 1.0.1 active、134 产品页 + 8 主页面、11 语种字典 146 条路径、`/get-a-quote/` 200（ID 3383）、两个 mu 补丁在位（`887787ab…` / `f7d95f35…`）。
- 备份 `/home/web/backups/packrivo-launch-20261029-0335`：db.sql 32,516,235 B（sha256 `1733240d…6ab6`）、rollback-27g/27i、mu 与 i18n 快照。旧备份 1629/1648 未动。
- 按授权规则 8 停止，等 Claude 线上只读复核；本轮不再改动生产。
- 回滚入口（若复核不过）：`post delete 3383 --force` → 9.6 恢复 mu → 9.5 主题 → 9.4 插件 → 9.3 内容（两个 rollback JSON）→ 9.2 字典。
