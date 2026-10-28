# 28q 三轮演练本地环境清单（首装态准备证据）

- 生成：2026-10-28（intent 656，演练环境准备，未执行三轮演练）
- 基线：packrivo-backup @ **6f4aac1**（`git log -1 --stat` 确认；工作树无跟踪文件修改，仅 verification-28q/ 未跟踪产物）
- 演练目标栈：`E:\破甲工作区\.tmp\28q\rehearsal`（docker 栈 r28q-net），站点入口 http://localhost:8091（容器内 host 名 `nginx`）
- 约束：全程仅本地；未使用 SSH；未触碰生产（www.packrivo.com）。

## 1. 容器清单（`docker ps` 实录，见 docker-ps.txt）

| 容器 | 镜像 | 角色（RUNBOOK §0 对照） | 端口 | 关键挂载 |
|---|---|---|---|---|
| php | r28q-php:8.3 | 生产 php 容器同构（WP 7.1.3 / **PHP 8.3.36** / wp-cli） | 9000/tcp（栈内） | `..\rehearsal\home\web\html:/var/www/html` |
| nginx | nginx:alpine | 生产 nginx 同构 | **0.0.0.0:8091→80** | html(ro) + `nginx\default.conf`(ro) |
| mysql | mariadb:10.11 | 生产 mysql 同构（db 容器） | 3306/tcp（栈内） | html（历史遗留挂载，保留原样） |
| redis | redis:alpine | 生产 redis 同构（**本次补齐**） | 6379/tcp（栈内） | — |
| r28q-host | docker:27-cli | **模拟生产 SSH 宿主机**（宿主侧无 php/wp，docker-in-docker） | — | `..\rehearsal\home\web:/home/web` + docker.sock |

网络：r28q-net（成员 php/nginx/mysql/redis/r28q-host，见 r28q-net-members.txt）。编排文件：`docker-compose.rehearsal-28q.yml`（与现运行容器逐项一致，源文件在 rehearsal\docker-compose.yml）。

## 2. RUNBOOK §0 宿主/容器路径对照（已逐项验证）

| 用途 | 宿主机（r28q-host 内，= 生产 SSH 宿主机） | 容器 php 内 |
|---|---|---|
| WordPress 根 | `/home/web/html/www.packrivo.com/wordpress` ($WP) ✓ | `/var/www/html/www.packrivo.com/wordpress` ($WPC) ✓ |
| i18n 字典 | `$WP/wp-content/uploads/packrivo-i18n` ✓（23 个文件，符合 §0.2 期望 23） | 同路径 ✓ |
| 上传的包 | `/home/web/packrivo-packages` ($PKG) ✓（27e-quote-redirect.patch / dsh-out / i18n-merged / 两个 zip / patches-28m / scripts / tools 均已在位） | `/pkg`（演练时 docker cp 灌入） |
| 备份 | `/home/web/backups` ($BACKUP) ✓ 已建、**可写**（写入测试通过）；CURRENT_LAUNCH 机制可用（尚无文件，由 RUNBOOK 第 1 步生成） | 不可见（按需 docker cp） ✓ |

另：`/home/web/pristine-mu/`（两个补丁前 mu 文件）与 `/home/web/seed-rollback/` 已在位，供第 6 步补丁演练与回滚播种使用。

## 3. 0.1 上线前检查结果（实录见 check-0.1-output.txt）

| 检查项 | 期望 | 实测 | 结论 |
|---|---|---|---|
| php/mysql/nginx/redis 容器 | 均在 | 均在 | PASS |
| wp core version | 7.1.3 | **7.1.3** | PASS |
| PHP | 8.3.x | **8.3.36** | PASS |
| siteurl | 生产为 https://www.packrivo.com | **http://nginx** | 本地适配（见 §5-1） |
| template | packrivo-static-bridge-1.2.38 | 同左 | PASS |
| stylesheet | 与 template 相同（子主题未装） | 同左 | PASS |
| 子主题 packrivo-home-v4 | grep 无输出（未安装） | 无输出 → 首次安装 | PASS |
| 报价插件 packrivo-quote | grep 无输出（未安装） | 无输出 → 首次安装 | PASS |
| 活跃主题 | packrivo-static-bridge（父主题，可回切） | packrivo-static-bridge-1.2.38,active | PASS |
| mysqldump | 有输出 | mysqldump 11.8.9-MariaDB | PASS |
| 宿主无 php/wp | 无 | NO-PHP-ON-HOST / NO-WP-ON-HOST | PASS |

## 4. INSTALL-STATE（RUNBOOK 1.5）判定依据

```
theme_before=packrivo-static-bridge-1.2.38
parent_before=packrivo-static-bridge-1.2.38
child_theme_present=no
quote_plugin_present=no
```
（active_plugins 中亦无 packrivo-quote；wp theme list / wp plugin list 实录见 wp-theme-list.csv / wp-plugin-list.csv）

## 5. 与 RUNBOOK 前提的差异/缺口（如实记录）

1. **siteurl 本地适配**：RUNBOOK §0.1 期望 `https://www.packrivo.com`，本地演练栈为 `http://nginx`（rehearsal-env-init.sh 已把 home/siteurl 置为 http://nginx，容器间用主机名访问）。演练判据应以 http://nginx（外部 http://localhost:8091）为准，不影响升级/回滚语义。
2. **packrivo-static-bridge 目录为空壳**：themes/ 下存在无内容的 packrivo-static-bridge 目录（wp theme list 不显示它）；功能性父主题为 packrivo-static-bridge-1.2.38（现 active，RUNBOOK 第 9 步回滚激活目标正是它）。可回切 ✓，空目录仅为历史残留，不影响演练。
3. **wp.org 连接告警**：php 容器访问 wordpress.org 失败，`wp theme list / plugin list` 每次输出 wp_update_themes/plugins Warning（不影响命令结果与 grep 判据，见 check-0.1-output.txt）。建议演练判据解析器忽略该 Warning 行（或演练脚本加 `2>/dev/null` 时注意该 Warning 走 stderr 已被吞掉——若走 stdout 判据脚本需容错）。
4. **mysql 容器 MARIADB_DATABASE=www_packrivo_com，而 WP 实际用库 wp**：wp 库由 rehearsal/seed.sql 灌入（现 wp 与 www_packrivo_com 两库并存）。若用 docker-compose.rehearsal-28q.yml 重建栈，需重灌 seed.sql（rehearsal/seed.clean.sql 由 clean-seed.sh 生成）。
5. **redis 为本次补齐**：§0 生产栈含 redis，本地此前缺，已以 redis:alpine 加入 r28q-net（WP 无 object-cache drop-in，redis 仅栈完整性，不影响 WP 运行）。

## 6. 首装态结论

演练目标（php 容器站点）已处于 RUNBOOK 0.1/1.5 定义的首装态：子主题 packrivo-home-v4 与插件 packrivo-quote 均未安装（wp 列表与目录双重确认），父主题 packrivo-static-bridge-1.2.38 active 可回切。HTTP 侧签名一致：`GET /get-a-quote/` → 301 到 /inquiry/（mu 老别名，首装前特征）、首页 200。
