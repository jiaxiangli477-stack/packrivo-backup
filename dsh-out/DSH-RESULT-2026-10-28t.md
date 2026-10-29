# 28t 结果：本地对齐 WP 7.1.4 + 改 RUNBOOK 0.1 + 按 28s 方式完整重跑一轮

工作台 #56 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 已完成（单线程，#56 与 Task 55 保持 paused）

本轮不碰生产：无 SSH、无生产写入、未读未打印任何密码/密钥/token，`.cf.env` 未打开。

## 1 结论

1. **本地演练环境与 `packrivo-live` 都升到 WP 7.1.4**，用的是官方包 `wordpress-7.1.4.zip`（37,239,942 B，sha256 `a147146c159362778ecbbbfb07bb20ddf4e85b130f971c824e656a8f938c5f87`，MD5 与官方 `Content-MD5: 9761e62677474639e95b6168ed0a650f` 一致）。升级后 `wp_version=7.1.4`、`$wp_db_version=61833`（与 7.1.3 相同，没有 update-db 动作）。
2. **升级改动了 48 个 core 文件**（与生产那 8 个是子集关系，见 §4）。`kses.php` 换成 7.1.4 后**没有改变任何已存内容**：正向快照与 28s 的 `4247693f…` **逐字节相同**。
3. **RUNBOOK 已改并提交**：`f88a762`（完整 `f88a762771ca5869cb710f632b61d564c4283986`），**`deploy/RUNBOOK-launch.md` 的 git blob = `2fa7bb6a61afa912a7109fefd90ca284f2c0422c`**。下一次生产授权请锁定这个提交与 blob。
4. **完整重跑一轮全绿**：正向 0–8 每步 `FAIL=0 EXIT=0`（PASS = 6/10/8/11/3/4/5/1/9），第 9 步 `PASS=8 FAIL=0 EXIT=0`；复位快照 → 正向 → 回滚三份快照与 28s **逐字节相同**；3.0 反向自检拦得住。
5. **这一轮前后跑了 2 次**：第 1 次 `1h sitemap 快照非空` 与 `2h de 站 lang=de` FAIL，原因是我把 runner 接到默认 `bridge` 而不是 `r28q-net`，`http://nginx` 解析不了 → 改驱动命令后**从复位重跑整轮**（没有只补出错的两步）。
6. 两个包哈希不变：`packrivo-home-v4.zip` = `5974ae0c…`、`packrivo-quote.zip` = `096d0c8e…`。

## 2 逐步结果（第 2 次，也是最终这轮）

| 步 | 判据 | 退出码 |
|---|---|---|
| step0 | PASS=6 FAIL=0（0a `core version = 7.1.4`） | 0 |
| step1 | PASS=10 FAIL=0 | 0 |
| step2 | PASS=8 FAIL=0 | 0 |
| step3 | PASS=11 FAIL=0 | 0 |
| step4 | PASS=3 FAIL=0 | 0 |
| step5 | PASS=4 FAIL=0 | 0 |
| step6 | PASS=5 FAIL=0 | 0 |
| step7 | PASS=1 FAIL=0 | 0 |
| step8 | PASS=9 FAIL=0 | 0 |
| step9 | PASS=8 FAIL=0 | 0 |
| 合计 | **65 PASS / 0 FAIL** | 全 0 |

第 3 步的关键数：27g `130+4=134`、27i `7+1=8`，两处 `apply_updated>0`、`URL_and_body_check=PASS`、`H1_body_URL_check=PASS`、`URL_and_non_H1_body_unchanged=134/134`。

## 3 快照（与 28s 逐字节相同）

| 快照 | 大小/行数 | sha256 | 与 28s 比较 |
|---|---|---|---|
| `s-reset.txt` | 69,238 B / 845 | `4972cd81…` | **逐字节相同**（`diff -q` 无输出） |
| `s-forward.txt` | 67,774 B / 847 | `4247693f…` | **逐字节相同** |
| `s-after-rollback.txt` | 69,238 B / 845 | `4972cd81…` | **逐字节相同** |
| `s-after-negtest.txt` | 69,238 B / 845 | `4972cd81…` | — |

任务单预计「复位快照可能不再等于 `4972cd81…`」。实测**仍然完全相等**：快照清单记的是主题/插件 CSV、选项、mu 与 i18n 哈希、页面标题区等，**不含 core 版本号**，所以 core 从 7.1.3 换成 7.1.4 不改变清单。两份「与 28s 对齐」的 diff 文件是 0 行差异（见 `deploy/verification-28t/reset-28s-vs-28t.diff`、`forward-28s-vs-28t.diff`）。

本轮内部的 diff（`reset-vs-forward.diff` / `forward-vs-rollback.diff`，各 434 行）与 28s 的同名文件 **sha256 完全相同**（`974576C7C9F2…` / `C6D3A14E0E8E…`）。也就是说：

- **回滚后 = 本轮基线 = 28s 基线**；
- **正向快照与 28s 正向一模一样 → 页面标题一节零变化**，正文也零变化（3a 判据 `URL_and_non_H1_body_unchanged=134/134` 就是拿 134 条的 URL 与非 H1 正文跟原始备份逐条比）。`kses.php` 的改动没有影响任何写入结果。

## 4 core 升级记录

### 4.1 官方包

| 项 | 值 |
|---|---|
| 来源 | `https://downloads.wordpress.org/release/wordpress-7.1.4.zip` |
| 大小 | 37,239,942 B |
| sha256 | `a147146c159362778ecbbbfb07bb20ddf4e85b130f971c824e656a8f938c5f87` |
| MD5 | `9761e62677474639e95b6168ed0a650f`（= 官方响应头 `Content-MD5`，完整性对得上） |
| Last-Modified | `Wed, 28 Oct 2026 16:17:45 GMT`（与生产被自动更新的时间同一天） |

### 4.2 演练环境（`r28q-net`）

- 升级前：`wp_version=7.1.3`、`$wp_db_version=61833`；core 清单 1950 条（wp-admin + wp-includes 全量 php/js 与根 php）。
- 升级：`wp core update /tmp/wordpress-7.1.4.zip --force`（本地 zip、离线；`get_core_checksums()` 的联网告警是容器无外网导致的 cosmetic 警告）。
- 升级后：`wp_version=7.1.4`、`$wp_db_version=61833`；`wp core update-db` → `Success: WordPress database already at latest db version 61833.`
- 备份：升级前把 core 打包到本机 `E:\破甲工作区\.tmp\28t\backup\rehearsal-wordpress-core-7.1.3.tar.gz`（22,048,108 B，不进 git）。

### 4.3 变了哪些文件（48 个）与生产那 8 个的对照

变动 48 个、新增 0、删除 0（清单见 `core-changed-files-rehearsal.txt`）。生产 2026-10-28 记录的那 8 个：

| 生产记录的 8 个 | 是否在 48 个里 |
|---|---|
| `wp-admin/user-edit.php` | 在 |
| `wp-admin/users.php` | 在 |
| `wp-admin/about.php` | 在 |
| `wp-includes/version.php` | 在 |
| `wp-includes/class-wp-block-parser.php` | 在 |
| `wp-includes/kses.php` | 在 |
| `wp-includes/class-wp-customize-manager.php` | 在 |
| `wp-includes/admin-bar.php` | 在 |

**更正一处先前的观察口径**：28t 前一版记录里说「生产 8 个 core 文件同秒变更」，那个数字是 `find -maxdepth 1` 的**顶层**计数（只扫了 `wp-admin/` 与 `wp-includes/` 的两个顶层目录）。全量包里 7.1.3→7.1.4 实际改 48 个文件，生产的 8 个全部落在其中，是**子集**而非全集；子目录里的 `wp-admin/includes/*`、`wp-includes/blocks/*`、`wp-includes/js/dist/*` 等 40 个文件当时没被统计到。生产的全深度计数需要下次 SSH 授权时只读复核一次（本轮不碰生产）。

其余 40 个（按目录归类，完整清单见文件）：

- `wp-admin/includes/`：`class-wp-users-list-table.php`、`ms.php`、`update-core.php`、`class-wp-upgrader.php`、`class-wp-privacy-*-table.php`（5 个）、`ajax-actions.php`、`class-wp-ms-users-list-table.php`
- `wp-includes/blocks/`：`categories.php`、`comments-pagination-{next,previous}.php`、`image.php`、`playlist.php`、`post-navigation-link.php`
- `wp-includes/js/dist/`：`block-serialization-{default,spec}-parser{,.min}.js`（4）、`customize-{preview-widgets,selective-refresh}{,.min}.js`（4）、`script-modules/{block-library/image/view,interactivity,interactivity-router}.*`（9）
- `wp-includes/`：`assets/script-loader-packages.php`、`assets/script-modules-packages.php`、`interactivity-api/class-wp-interactivity-api.php`、`rest-api/endpoints/class-wp-rest-comments-controller.php`、`build/routes/connectors-home/*`（3）
- 根目录：`wp-signup.php`

`kses.php` 与 `version.php` 在 28t 环境里的 sha256 分别是 `3560ff58…`、`7163a7bf…`，与 `packrivo-live` 升级后的同名文件**完全一致**，说明两处装的是同一个官方包。

### 4.4 `packrivo-live` 本地站

- 备份（只在本机，不进 git）：`packrivo-live-core-7.1.3.tar.gz`（22,228,777 B，sha256 `3888c2cd4fca6f66a7914a0ab8d951c3b0836050c2809cec79fd45015a5fb750`）、`packrivo-live-db-7.1.3.sql`（32,392,977 B，sha256 `b9f0fb3d5d12144f9d3df5e18bbfbb95cb2e762b3f0ae1af617899ae92372057`）。
- 升级：`wp core update` 在该 compose 栈里连不上库（`wordpress:cli-php8.3` 一次性容器解析不到 `db`），改用等价做法——把官方包的 `wp-admin` / `wp-includes` / 根 `*.php` 整目录替换进 `wpdata` 卷、`chown 33:33`，`wp-content` 与 `wp-config.php` 未动。
- 结果：`$wp_version='7.1.4'`、`$wp_db_version=61833`、`kses.php` = `3560ff58…`，`wp-content/{themes,plugins,mu-plugins}` 目录时间戳仍是升级前。
- 说明（不做美化）：这次没有产出 live 站的逐文件 diff 清单，只做了版本行、两个核心文件哈希、目录时间戳与备份三件套的核对；逐文件对照在演练环境里已经做过（48 个），live 只用于本地开发，不参与上线判据。

## 5 RUNBOOK 改动（提交 `f88a762`，blob `2fa7bb6a…`）

| 位置 | 改动 |
|---|---|
| 头部目标站点行 | `WordPress 7.1.3` → `WordPress 7.1.4` |
| 0.1 `$WPCLI core version` 注释 | `# 期望 7.1.3` → `# 期望 7.1.4（生产开着 WP 小版本自动更新，见下方说明）` |
| 0.1 通过判据 | `core version` = `7.1.3` → `7.1.4` |
| 0.1 新增引用块 | 生产开着 WP 小版本自动更新；执行当天版本又变了就第 0 步 FAIL、不要临场改判据，先按 28t 在本地对齐并完整重跑一轮、改完期望值再上生产 |
| 0.1 TLS 补救块 | 标题改成「下列两种 `TLS/SSL error` 之一都做同一个 `--skip-ssl` 包装」：`SSL is required, but the server does not support it`（演练实测）+ `self-signed certificate in certificate chain`（生产 2026-10-28 实测，负责人已放行）；判据不变：装上后必须 > 0 字节，仍导不出就停下报告 |
| 附录 C | 加 28t 一行 |
| 附录 D | 仍未并入（`grep '附录 D|prep-restore.php|apply_updated=134'` 无输出） |

**除以上之外没有改任何命令**；`git diff` 只有这 6 处 + 一处演练环境版本行。

`rehearsal.sh` 同步：`0a core version = 7.1.3` → `= 7.1.4`（`deploy/verification-28q/scripts/rehearsal.sh` 与 `deploy/verification-28t/scripts/rehearsal.sh` 两份同内容，452 行）。

## 6 3.0 反向自检

```
PASS: 3.0 /pkg/rollback/rollback-27g.json site 与本轮一致
PASS: 3.0 /pkg/rollback/rollback-27i.json site 与本轮一致
FAIL: 3.0 /pkg/rollback/rollback-bogus.json 非本轮产物（site=http://wrong-site.invalid，期望 http://nginx），先清空 /pkg/rollback
3.0 守卫拦截，apply 不执行
本步判据：PASS=2 FAIL=1        negtest step3 EXIT=1        apply_updated 出现 0 次
删掉 bogus 后：反向自检后快照 sha256 = 4972cd81…  PASS
```

## 7 第一次跑失败的原因与处理（从复位重跑，不拼结果）

| 停在哪 | FAIL 原文 | 判定 | 处理 |
|---|---|---|---|
| step1 | `FAIL: 1h sitemap 快照非空` | **命令写错**：runner 容器建在默认 `bridge` 网络，`http://nginx` 解析不了 → `curl` 返回 000，快照文件为空 | 驱动命令加 `--network r28q-net`（28s 的 runner 就在这个网络上），从复位重跑整轮 |
| step2 | `FAIL: 2h de 站 lang=de` | 同上，`curl -s http://nginx/de/ | grep -c 'lang="de"'` = 0 | 同上 |

旁证：`reset.log` 里那条 `首页 000（期望 200）` 也是同一个原因；改完网络后同一条命令返回 `nginx=200`，第 2 次跑 step1 PASS=10（比第 1 次多出 `1h`）。

## 8 交付物

| 路径 | 内容 |
|---|---|
| `deploy/verification-28t/run.log` | 整轮驱动日志（含逐步 `PASS/FAIL/EXIT` 与三份快照哈希） |
| `deploy/verification-28t/step0.log … step9.log` | 每步单独日志 |
| `deploy/verification-28t/step-summary.txt` | 逐步 `PASS/FAIL/EXIT` 汇总 |
| `deploy/verification-28t/reset.log` | 复位过程 |
| `deploy/verification-28t/s-*.txt` | 四份快照 |
| `deploy/verification-28t/reset-28s-vs-28t.diff` / `forward-28s-vs-28t.diff` | 与 28s 基线对齐的 0 行差异说明 |
| `deploy/verification-28t/reset-vs-forward.diff` / `forward-vs-rollback.diff` | 本轮内部 diff（各 434 行，与 28s 同名文件 sha256 相同） |
| `deploy/verification-28t/negtest.log` / `negtest-step3.log` | 反向自检全流程与该次 step3 原始输出 |
| `deploy/verification-28t/core-upgrade-rehearsal.log` | 演练环境 core 升级全过程（含包 sha256、版本、清单） |
| `deploy/verification-28t/core-changed-files-rehearsal.txt` | 本次升级变动的 48 个 core 文件 |
| `deploy/verification-28t/core-upgrade-live.log` | `packrivo-live` 升级日志（含备份路径与失败原因） |
| `deploy/verification-28t/scripts/` | `run28t.sh`、`rehearsal.sh`（7.1.4 版）、`negtest.sh`、`upgrade-core.sh` |

## 9 复现

```powershell
# 1) 官方包（校验 MD5 应等于官方 Content-MD5）
curl.exe -sS -o E:\破甲工作区\.cache\wp\wordpress-7.1.4.zip https://downloads.wordpress.org/release/wordpress-7.1.4.zip
(Get-FileHash E:\破甲工作区\.cache\wp\wordpress-7.1.4.zip -Algorithm SHA256).Hash   # a147146c…5f87

# 2) 演练环境升级 + 完整重跑（r28q-host 容器内，必须 --network r28q-net）
docker exec r28q-host bash /home/web/upgrade-core.sh
docker run -d --name r28t-runner --network r28q-net `
  -v "E:\破甲工作区\.tmp\28q\rehearsal\home\web:/home/web" `
  -v /var/run/docker.sock:/var/run/docker.sock r28q-host:11 `
  sh -c 'bash /home/web/v28t/run28t.sh > /home/web/v28t/run.log 2>&1; echo EXIT=$? >> /home/web/v28t/run.log'

# 3) 3.0 反向自检
docker run -d --name r28t-neg --network r28q-net `
  -v "E:\破甲工作区\.tmp\28q\rehearsal\home\web:/home/web" `
  -v /var/run/docker.sock:/var/run/docker.sock r28q-host:11 `
  sh -c 'bash /home/web/v28t/negtest.sh > /home/web/v28t/negtest.log 2>&1; echo EXIT=$? >> /home/web/v28t/negtest.log'
```

## 10 下一次生产授权的锁定值

- RUNBOOK 提交：`f88a762771ca5869cb710f632b61d564c4283986`
- `deploy/RUNBOOK-launch.md` 的 git blob：`2fa7bb6a61afa912a7109fefd90ca284f2c0422c`
- 第 0 步期望：`core version = 7.1.4`、PHP = `8.3.x`、`template = packrivo-static-bridge-1.2.38`、子主题与报价插件均未安装、`/get-a-quote/` = 301 → `/inquiry/`
- 执行当天若 WP 又自动更新，按 RUNBOOK 0.1 新增的说明处理：第 0 步 FAIL → 本地对齐 → 重跑一轮 → 改期望值 → 再上生产
