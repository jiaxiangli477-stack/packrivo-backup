# 29v 演练环境（与生产的差异，及为什么这么配）

本轮把演练环境的数据库从 **MariaDB 10.11** 换成 **MySQL 官方镜像**，目的是让第 0 步那两层报错在本地**真实复现**，而不是靠人记着「生产会报这个」。10-29 那轮上生产的两次临时批准，就是因为演练环境从来没报过这两条错。

## 1. 栈的样子

| 容器 | 镜像 | 说明 |
|---|---|---|
| `php` | `r29v-php:8.3`（见 `env/Dockerfile.php`） | PHP 8.3 + wp-cli，`mariadb` / `mariadb-dump` 客户端 **11.4.13**（= 生产版本），认证插件目录初始为 **空** |
| `mysql` | `mysql:8.4`（实测 **8.4.13**） | MySQL Community Server，`root@%` 认证插件 **`caching_sha2_password`**，自签证书 TLS（`ssl_ca` / `ssl_cert` / `ssl_key` 自动生成） |
| `nginx` | `nginx:alpine` | 站点入口 `http://nginx` |
| `redis` | `redis:alpine` | 未被本轮使用 |
| `r28q-host` | 自建（docker CLI + bash + git + curl） | 模拟生产 SSH 宿主机，一次性跑手容器挂同一个 `docker.sock` |

站点目录仍是宿主机 bind mount：`E:\破甲工作区\.tmp\28q\rehearsal\home\web\html` → `/var/www/html`。
数据库数据在命名卷 `r29v-mysql-data`（旧 MariaDB 容器的匿名卷不动，留着可回退）。

## 2. 三处刻意的差异

### 2.1 客户端版本必须钉在 11.4.13

Alpine 3.24 自带的 `mariadb-client` 是 **11.8.9**，它**先检查认证插件、再谈 TLS**，所以第一条错报成 `1045 Plugin caching_sha2_password could not be loaded`——生产的顺序正好相反（先 `2026 TLS/SSL error`，装包装之后才 `1045`）。

演练要复现生产的顺序，客户端必须与生产同版本。Alpine 3.22 仓库里正好有 `mariadb-client=11.4.13-r0`：

```dockerfile
FROM r28q-php:8.3
ARG R=https://dl-cdn.alpinelinux.org/alpine/v3.22/main
RUN apk add --no-cache --repository "$R" \
      mariadb-client=11.4.13-r0 mariadb-common=11.4.13-r0
```

装完实测：`mariadb from 11.4.13-MariaDB`、`mariadb-dump from 11.4.13-MariaDB`、`ls /usr/lib/mariadb/plugin | wc -l` = **0**。
（11.8.9 用 `--ssl-verify-server-cert` / `--ssl-ca=…` / `/etc/my.cnf.d` 配置都试过，仍然先报 1045——是客户端行为顺序，不是配置问题，所以只能换版本。）

### 2.2 第二层补救装的包版本与生产不同

| | 生产（2026-10-29 实测） | 演练 |
|---|---|---|
| 包 | `mariadb-connector-c` | 同 |
| 版本 | `3.3.10-r0` | `3.4.6-r0` |
| 插件目录 | 0 → **5** 个文件 | 0 → 6 个文件（含 `caching_sha2_password.so`） |

版本不同不影响结论：两层补救的**顺序与判据**一致，`3.4.6-r0` 与 11.4.13 客户端配合正常，装上后 `mariadb-dump` 立刻能连。

### 2.3 MySQL 大版本不同

| | 生产 | 演练 |
|---|---|---|
| 版本 | MySQL Community Server **26.7.0** | **8.4.13** |
| 认证插件 | `caching_sha2_password` | `caching_sha2_password` |
| TLS | 开（自签，链不全） | 开（`CN=MySQL_Server_8.4.13_Auto_Generated_Server_Certificate`，自签） |
| 报错原文 | `ERROR 2026 (HY000): TLS/SSL error: self-signed certificate in certificate chain` | **逐字节相同** |

这一项差异不影响判据：三层判据（字节数、`CREATE TABLE` = `BASE TABLE`、末尾 `-- Dump completed`）都是导出文件自身的性质。

## 3. 每轮复位都会拆掉的东西（这是本轮的关键设计）

`reset.sh` 第 6 节每次都执行：

```bash
docker exec php sh -c 'apk del mariadb-connector-c'
docker exec php sh -c 'rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb'
```

不这么做，第二轮就会「包装和插件都在」，第 0 步直接一次通过——两层补救那条路再也不会被走到。29v 起每轮都必须看到：

1. `ERROR 2026 … TLS/SSL error: self-signed certificate in certificate chain`（0 字节）
2. 装上 `--skip-ssl` 包装后 → `ERROR 1045 … caching_sha2_password.so: No such file or directory`（0 字节）
3. `apk add --no-cache mariadb-connector-c` → 插件目录出现 `caching_sha2_password.so`
4. 三条探针判据 PASS

`reset.sh` 同时删掉演练库里的 `/get-a-quote/` 页（原 ID 3220）。生产首装前本来没有这一页；演练库自带它，导致 10-29 上生产时第 6 步 404 这个问题一直没在演练里暴露。

## 4. 重建这套栈的命令

```powershell
$dk='D:\Docker\App\resources\bin\docker.exe'
# 1) php 镜像（钉客户端版本）
& $dk build -t r29v-php:8.3 -f deploy\verification-29v\env\Dockerfile.php deploy\verification-29v\env
# 2) MySQL 容器
& $dk run -d --name mysql --network r28q-net --restart unless-stopped `
  -e MYSQL_ROOT_PASSWORD=r28qlocal -e 'MYSQL_ROOT_HOST=%' -e MYSQL_DATABASE=wp `
  -v r29v-mysql-data:/var/lib/mysql -v E:\破甲工作区\.tmp\28q\rehearsal\home\web\html:/var/www/html `
  mysql:8.4 --character-set-server=utf8mb4 --collation-server=utf8mb4_general_ci
# 3) php 容器（与生产同环境变量口径）
& $dk run -d --name php --network r28q-net --restart no `
  -e WORDPRESS_DB_HOST=mysql -e WORDPRESS_DB_NAME=wp -e WORDPRESS_DB_USER=root -e WORDPRESS_DB_PASSWORD=r28qlocal -e WORDPRESS_DEBUG=1 `
  -v E:\破甲工作区\.tmp\28q\rehearsal\home\web\html:/var/www/html -w /var/www/html --entrypoint php-fpm r29v-php:8.3
```

> `r28qlocal` 是本地演练口令，只存在于本机演练栈；生产凭据一个字都没进过这个环境。
