# 29z 上生产执行记录（2026-10-30，中止）

## 0. 结论

**中止在 D.1b，D.2 未开始，生产站点零改动。** 中止原因是判据脚本在生产的真实拓扑下取不到页面、也算不出快照（两条硬故障，见 §3）。按授权口径「D.2 之前任何一条 ⛳ 不通过：立刻停下，什么都不改」，就此停手；负责人随后撤销本次授权，改为先把演练环境改成生产同构再走一遍。

## 1. 授权与锁定核对

| 项 | 值 | 结果 |
|---|---|---|
| 执行提交 | `3774617e1be2602b9c2bc8e57869740bb002e8c0` | ✅ |
| `git rev-parse HEAD:deploy/RUNBOOK-launch.md` | `34de4571e15d87344919b3c210e538deb8dfc27f` | ✅ 与锁定值一致 |
| 时间窗 | 负责人发出指令起 3 小时内开始 | ✅ 当日 07:38 UTC 开工 |

## 2. 逐条判据（原样）

### D.0 变量与目录

```text
PKG=/root/29z-pkg
PKGC=/pkg
/home/web/backups/29z-20261030-073819
host mkdir OK
container mkdir OK
drwxr-xr-x    9 root     root          4096 Oct 29 02:43 /pkg      ← 本来就存在，mkdir -p 无副作用
```

### D.0-4 直连源站探测

首跑命中手册失败表第 2 行：

```text
curl: (60) SSL certificate problem: unable to get local issuer certificate
```

源站证书是 CloudFlare Origin CA 签的：

```text
issuer=C=US, O=CloudFlare, Inc., OU=CloudFlare Origin SSL Certificate Authority, L=San Francisco, ST=California
subject=O=CloudFlare, Inc., OU=CloudFlare Origin CA, CN=CloudFlare Origin Certificate
notBefore=Sep 21 09:40:00 2026 GMT   notAfter=Sep 17 09:40:00 2041 GMT
```

按手册加 `-k` 重跑，三条全过：

```text
HTTP/2 200
server: nginx
content-type: text/html; charset=UTF-8
cache-control: public, max-age=60, s-maxage=600, stale-while-revalidate=86400

--- cf 标记 ---
OK 无 cf-ray / cf-cache-status

fetch1 http=200 size=110923
fetch2 http=200 size=110923
0fd4f570f5c471719d06bf781a996f1328729dce2dd9c4c85a8330fea50c5b54  /tmp/29z-probe1.html
0fd4f570f5c471719d06bf781a996f1328729dce2dd9c4c85a8330fea50c5b54  /tmp/29z-probe2.html
两次哈希相同 = True
html 命中 = 1
```

### D.0-5 只读预检

```text
packrivo-home-v4                active   4.0.1
packrivo-static-bridge-1.2.38   parent   1.2.38
catalog=208   publish_1688=209   publish_yen=209
home=200      products=200       get-a-quote=200
```

### D.1.1 / D.1.2 上传与核对

六个文件逐个与 D.1.0 表相同：

```text
3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6   791169  packrivo-home-v4.zip
3c1be67b5c5a8298e317fb13ae1d910c64343370ebb440b0fb42d5fcb6f4f3fd    23491  strip-1688.php
0deea313611ff71a760baae5646bcd0252f1cbad5bdb85d0ab3e2953e112ee1e     1730  strip-1688.sh
022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d      756  packrivo-wave2-font-path.patch
64c8768e5ba3e00281fda4d5937f8c57a03cf5871818a84c2363095b0dda269b     1840  apply-font-patch.php
1d3f39c24eaed0c8771619f30b9991d83b6c38600b63fc54dc6c6fcffe3cb8f1     9824  29z-criteria.sh
```

### D.1b 前态快照 —— ⛳ 不通过

```text
== 取前态 ==
Error: Query failed: … ERROR 1305 (42000) at line 1: FUNCTION www_packrivo_com.MD5 does not exist
Error: Query failed: … ERROR 1305 (42000) at line 1: FUNCTION www_packrivo_com.MD5 does not exist
Error: Query failed: … ERROR 1305 (42000) at line 1: FUNCTION www_packrivo_com.MD5 does not exist
  快照 before: pages 0 / meta 0 / index 0 / cards 0 / products 0B
--- 快照文件 ---
  0  cards-before.txt      0  index-before.txt   0  meta-before.txt
  0  pages-before.txt      0  products-before.html
```

宿主侧那一半是好的：

```text
$CURL "$SITE/" -o "$BACKUP/29z/home-before.html"  → http=200 size=110923
0fd4f570f5c471719d06bf781a996f1328729dce2dd9c4c85a8330fea50c5b54  …/29z/home-before.html
```

## 3. 两条故障（生产实测）

### 3.1 生产 MySQL 26.7.0 没有 `MD5()` / `SHA1()`

```text
wp db query "SELECT VERSION()"   → 26.7.0  MySQL Community Server - GPL
SELECT MD5('a')        → ERROR 1305 (42000): FUNCTION www_packrivo_com.MD5 does not exist
SELECT SHA1('a')       → ERROR 1305 (42000): FUNCTION www_packrivo_com.SHA1 does not exist
SELECT SHA2('a',256)   → ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb
SELECT CRC32('a')      → 3904355907
```

判据脚本第 32/34/36 行的三条快照查询全用 `MD5()`，于是 pages/meta/index 三个快照全空。
**演练用的是 MySQL 8.4，MD5 还在，所以三轮演练一直是绿的** —— 这是演练与生产的拓扑差。

### 3.2 php 容器够不到源站

```text
docker exec php getent hosts nginx              → NO_ENTRY
curl http://nginx/                              → curl: (6) Could not resolve host: nginx
curl -H 'Host: www.packrivo.com' http://172.18.0.1/     → 301  https://www.packrivo.com/
curl -k --resolve www.packrivo.com:443:172.18.0.1 https://www.packrivo.com/  → 200 size=110923
同一条不加 -k                                    → curl: (60) unable to get local issuer certificate
```

演练里我自己起了名叫 `nginx` 的容器，`http://nginx` 能通；生产 php 容器解析不到这个名字，`products-before.html` 恒为 0 字节 —— 后面 4c/4d/4e/8/9 会**假 PASS**。这正是不能继续的原因。

## 4. 生产清理

删掉的两处（负责人指定）：

```text
removed: /root/29z-pkg
removed: /home/web/backups/29z-20261030-073819
```

另有两个这次中止的残留一并清掉：`/home/web/backups/29z-20261030-075452`（worker 重跑 D.0 时建的）、`/root/29z-backup-path.txt`（D.0 第 1 步写的记录文件，指向已删目录）。清理后：

```text
ls: cannot access '/root/29z-pkg': No such file or directory
ls: cannot access '/home/web/backups/29z-*': No such file or directory
/root 下已无 29z*
```

站点状态（清理后复核，与开工前一致）：

```text
packrivo-home-v4   active   4.0.1
mu sha256 = 5ab5be3f85f46ce5416fdb4cafc98f54de86a1fc4ece041c56c2de40173a1d8c
publish_yen = 209
```

没有装包、没有改库、没有碰 mu、没有清缓存、没有动 `.cf.env`。

## 5. 过程异常（必须记录）

本次执行期间，工作台任务 #66 的执行工 `work#1` **自行**开始跑附录 D 的 D.0（第 1/2/4/5 步），在 07:54:52 UTC 建了 `$BACKUP=/home/web/backups/29z-20261030-075452`。发现后立即 `pause -Task 66`（日志：`运行被中断：用户暂停了任务；停在工具执行阶段，已运行 26 轮 / 5m6s`），核对其全部 Bash 调用：只有 `ssh … d0-step1/2/4/4b/5.sh`，均为建目录与只读探测，**没有 scp、没有 theme install、没有写库**，站点未受影响。它建出来的目录已按 §4 删除。

教训：任务描述里写"上生产"会让执行工把手册当成待办清单自己去跑。以后生产执行只在会话内人肉逐步下命令，不登记成会派 worker 的任务。

## 6. 后续

1. 本次授权作废，等负责人重新发授权；
2. 演练环境改成生产同构（MySQL 26.7.0、php 容器解析不到 `nginx`）并重跑三轮 —— 见 `DSH-RESULT-2026-10-29z.md` §14；
3. 判据脚本改为 `SHA2(…,256)` + `CURLX` 直连钩子，新哈希 `e3602e3d…`（10006 B）已写进 D.1.0 表。