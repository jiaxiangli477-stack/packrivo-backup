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
