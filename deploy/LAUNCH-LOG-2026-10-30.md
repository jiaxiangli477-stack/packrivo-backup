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
## 7. 复核打回后的修正（`6466878` 之后，仍未上生产）

复核抓到 D.0 那行 `ORIGIN_IP=$(docker exec php sh -c "ip route | awk '/default/ {print $3}'")`：
双引号里的 `$3` 被**宿主机 shell** 展开成空，awk 打出整行；生产 php 镜像也不保证有 `ip` 命令。成立，已改：

1. 换成 `ORIGIN_IP=$(docker inspect php -f '{{range .NetworkSettings.Networks}}{{.Gateway}}{{end}}')`；
2. 紧接着加 ⛳ 两条（IPv4 正则判定 + 容器经 `$CURLX` 打源站必须 200），任一 FAIL 就停；
3. 演练改成 `SITE=https://www.packrivo.com`：web 容器只发布 443 + 自签证书，80 一律 301，
   php 容器删掉 hosts 里的域名，`ORIGIN_IP` 由上面那行算出来（`172.19.0.1`），不再手写 IP、不用 hosts、不用 8091；
4. 演练里又抓到一个生产同款陷阱：`CURL="curl … --noproxy '*' …"` 的引号在变量里是**字面量**，
   curl 收到 `'*'` 不匹配域名 → 代理生效 → 走 Cloudflare。改 `--noproxy=*`，失败表加一行。

生产同构下三轮全绿（日志 `deploy/verification-29z/evidence/rehearsal-29z-sequence-v5.log`，530 行）：
D.0 ⛳ 两条 PASS、探测直连 nginx 200 且无 CF、前态 308,696 B、正向 23/0/1 + 4f、回滚哈希/计数/字体四条全对、
再正向与首轮逐项相同。**生产仍是 4.0.1 + ¥209 + mu `5ab5be3f`，本次只改仓库。**

---

# 附：第三次授权（`09e272e`）—— 本次实际执行记录

## A0. 授权与锁定核对（开工前）

```text
授权           = 负责人 2026-10-30 10:33 消息（3774617 / 6466878 / 7db9557 全部作废）
HEAD           = 09e272e08151a6f56f6ea25bb52a1a91be3d7624                 ✅
HEAD:deploy/RUNBOOK-launch.md = b3edcd71ee33fcbf982dd2a4aefd3026022e4930 ✅
磁盘 hash-object = b3edcd71ee33fcbf982dd2a4aefd3026022e4930                ✅
29z-criteria.sh = 13079 B / 1f6dd732275191c58b780af068220a40e2d85cb6226bbe90b78d9322a0dbe546 ✅
工作区           = 干净
开工（UTC）      = 2026-10-30 10:33:24（消息后 0 分钟，在 3 小时窗内）
生产 curl        = 8.14.1 (x86_64-pc-linux-gnu) libcurl/8.14.1 OpenSSL/3.5.7
$BACKUP          = /home/web/backups/29z-20261030-103332
```

## A1. D.0（全部 PASS，零 FAIL）

**D.0-1 变量 + ORIGIN_IP ⛳ 两条**

```text
ORIGIN_IP=172.18.0.1
PASS ORIGIN_IP 是单个 IPv4
PASS 容器经 CURLX 打源站 = 200
```

**D.0-2 / D.0-3**

```text
host mkdir OK / container mkdir OK
drwxr-xr-x /home/web/backups/29z-20261030-103332（+ 29z/ 子目录）
drwxr-xr-x /root/29z-pkg
drwxr-xr-x /pkg（已存在）
WPCLI core version = 7.1.4
packrivo-home-v4	active	4.0.1
```

**D.0-4 直连源站探测（11 条全 PASS）**

```text
HTTP/2 200
server: nginx
PASS head 的 stderr 为空
PASS 头文件首行是 HTTP 200
PASS 头文件里 server: nginx
PASS 无 cf-ray / cf-cache-status
0fd4f570f5c471719d06bf781a996f1328729dce2dd9c4c85a8330fea50c5b54  probe1.html
0fd4f570f5c471719d06bf781a996f1328729dce2dd9c4c85a8330fea50c5b54  probe2.html
1                                                                   （<html 命中）
PASS probe1 有内容 110923B
PASS probe1 是本次抓的（mtime 1793356461 >= 1793356461）
PASS probe1 的 stderr 为空
PASS probe2 有内容 110923B
PASS probe2 是本次抓的（mtime 1793356461 >= 1793356461）
PASS probe2 的 stderr 为空
<title>Custom Jewelry Boxes &amp; Packaging with Logo | Packrivo</title>
```

**D.0-5 只读预检 ⛳**

```text
packrivo-home-v4	active	4.0.1
catalog 页数 = 208
含 1688 的 publish 行 = 209
含 ¥ 的 publish 行 = 209
```

三条与预期一致（208 / 209 / 209）。

## A2. D.1 上传与核对（6 个文件，逐个相同）

```text
3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6  packrivo-home-v4.zip
3c1be67b5c5a8298e317fb13ae1d910c64343370ebb440b0fb42d5fcb6f4f3fd  strip-1688.php
0deea313611ff71a760baae5646bcd0252f1cbad5bdb85d0ab3e2953e112ee1e  strip-1688.sh
022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d  packrivo-wave2-font-path.patch
64c8768e5ba3e00281fda4d5937f8c57a03cf5871818a84c2363095b0dda269b  apply-font-patch.php
1f6dd732275191c58b780af068220a40e2d85cb6226bbe90b78d9322a0dbe546  29z-criteria.sh
791169 packrivo-home-v4.zip
条目数 = 6
```

## A3. D.1b 前态快照 —— B1～B7 全 PASS

```text
容器内脚本 sha256 = 1f6dd732… / 13079 B
快照 before: pages 733 / meta 804 / index 774 / cards 24 / products 313656B / curl_stderr 0B
PASS  B1 pages 快照行数（≥ 400，实际 733）
PASS  B2 meta 快照行数（≥ 200，实际 804）
PASS  B3 index 快照行数（≥ 200，实际 774）
PASS  B4 products-before.html 字节数（≥ 100000，实际 313656）
PASS  B5 /products/ 卡片数（期望 24，实际 24）
PASS  B6 products-before 的 curl stderr 字节数（期望 0，实际 0）
PASS  B7 catalog 页 ID 条数（期望 208，实际 208）
PASS=7  FAIL=0    退出码 0
home-before.html = 0fd4f570…（110923 B），与 D.0-4 两次探测哈希一致
```

改前截图（打生产域名，1440 / 375）：`status=200 cards=24 overflow=false yen_in_dom=0`，图 434099 B / 131926 B。

## A4. D.1c 备份（回滚只认这三样）

```text
主题目录 4.0.1：38 文件
  目录哈希 = 85d2320276b21a5b53d4f32eeb9323ec915bc02d289cf48e912ec6001c7a76cc
  Version: 4.0.1
mu 原文件：5ab5be3f85f46ce5416fdb4cafc98f54de86a1fc4ece041c56c2de40173a1d8c（12390 B）
  容器内 .orig-29w 同哈希
三表导出：29393833 B / c2d9e18cba4d44eecc51f6bf9220867437c2116e2ac67e8e3e74e42c84164e57
  容器 /tmp/29z-pre.sql 已删
```

**一处与演练不同（如实记录，不影响判据）**：生产 4.0.1 主题目录哈希 `85d23202…` ≠ 演练 `c02a9324…`（文件数都是 38）。
演练目录来自 `git archive 7fe56ad`，生产目录是线上实际文件，两者内容本来就有差异；回滚判据比的是**本次备份的哈希**，不是演练值。

## A5. D.2 装 4.0.4 + 字体补丁

```text
/pkg/packrivo-home-v4.zip sha256 = 3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6
Theme updated successfully. / Installed 1 of 1 themes.
packrivo-home-v4	active	4.0.4
front-page.php 里 'Example quote · Rigid magnetic box' 计数 = 1
首页直连命中：Example quote · Rigid magnetic box · 9.5×9.5×4 cm
容器无 patch（sh: patch: not found）→ 走 apply-font-patch.php：applied
mu sha256 = 73d43ba0262a9d74249b19a46380bc5c338f94bd9e0776baa46eddf074c9f259（与 §6.5 锁定值一致）
cache flush OK
字体 URL = https://www.packrivo.com/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2
直连字体：code=200 type=font/woff2 size=34952
```

## A6. D.3 去价（干跑 / 真跑 / 幂等）

```text
dry-run : {"changes":210,"leftover_pages":0,"metadesc_unique":208,"metadesc_maxlen":153,...}
apply   : {"changes":210,...，"indexable_rows":208,"products_p_removed":158,"blog_links_unwrapped":1}
再跑一次 : {"changes":0,...}   ← 幂等成立
审计     : 10451 B / 1d76f2e652c96cfca2c893b88b12e1c91f9c268eb8087b06dd3502739a04ffac
```

## A7. D.4 / D.5

```text
D.4 indexable 重建：Success. Rows affected: 543；cache flush OK
D.4 ⛳ 不一致条数 = 0
三条 200 抽查：/ → 200，/products/ → 200，/get-a-quote/ → 200
```

**D.5 compare-noshots**

```text
快照 after: pages 733 / meta 1012 / index 774 / cards 24 / products 312127B / curl_stderr 0B
0a 0b 0c 0d 0e 0f  PASS（六条输入非空）
1a 1b 1c 2a 2b 2c 2d 2e 2f 3a 3b  PASS
4a 4b 4c 4d 4e  PASS   /  4f SKIP（截图本机判）
5a=210 5b=0 6a=0 6b=208 7=0 7b=208  PASS
8 五页：ring-box 200/77819/¥0、wooden 200/76445/¥0、velvet 200/89428/¥0、paper 200/84602/¥0、gift 200/80806/¥0
8 ¥0、8b 5/5  PASS
9a=200 9b=46 处  PASS
汇总：PASS=32  FAIL=0  SKIP=1
```

## A8. 停在 D.5.5（待负责人清 Cloudflare 缓存）

D.0 → D.5 全部 ⛳ 通过，零 FAIL。生产当前 = 4.0.4 + 字体补丁已打 + 正文归零。
清缓存由负责人执行；之后再做改后截图 → 判据 4f → 全站复测，然后把本节补完。
## A9. D.5.5 后半（负责人已清 Cloudflare 缓存）

清缓存后公网口径复查（`curl -I`，三条都 `cf-cache-status: MISS`）：

```text
https://www.packrivo.com/            200  Server: cloudflare | cf-cache-status: MISS
https://www.packrivo.com/products/   200  Server: cloudflare | cf-cache-status: MISS
https://www.packrivo.com/get-a-quote/ 200 Server: cloudflare | cf-cache-status: MISS
首页公网直取：'Example quote · Rigid magnetic box' 1 处 / ¥ 0 处 / 字体 v3 URL 2 处
```

**① 改后截图**

```text
after-1440: status=200 cards=24 overflow=false ¥=0 434099 B
after-375 : status=200 cards=24 overflow=false ¥=0 131926 B
shots-after.json console_errors = 0
```

**② 判据 4f**

```text
before-1440: status=200 cards=24 overflow=false ¥=0 434099 B
before-375 : status=200 cards=24 overflow=false ¥=0 131926 B
after-1440 : status=200 cards=24 overflow=false ¥=0 434099 B
after-375  : status=200 cards=24 overflow=false ¥=0 131926 B
PASS  4f 改前改后 x 1440/375 四张截图（4/4 齐，卡片 24 = 24，无溢出，改后 ¥ 0）
```

改前改后两张 1440 图 sha256 相同（`71d40763fac33290…`），375 也相同（`7f47536f2e53dc2e…`），mtime 分别是 03:36:23 与 03:44:47
（不是没重拍，是**渲染出来的像素本来就一样**）：本次去掉的价格在正文里，而 `/products/` 由 `template-products-v4` 渲染、
正文不上屏，所以页面视觉不变，变的是 HTML 体积（313656 → 312127 B）。

**③ 全站复测（60 页）**

```text
pages=60  pages_non200=11  links_unique=1064  links_non200=1
非 200 页面 = 11 条，全部是同一篇博客的 11 个语种版本：
  /de/ /fr/ /es/ /it/ /nl/ /pt/ /ar/ /ja/ /ko/ /zh-CN/ /zh-TW/ + 2026/09/21/jewelry-box-insert-fit-measurements/  均 404（改前就是这样）
唯一非 200 链接 = /xmlrpc.php?rsd → 403（WP 头部 EditURI，站点自己禁的 XML-RPC，与本轮无关）
字体 = 200 / font/woff2 / …/packrivo-static-bridge-1.2.38/…/packrivo-inter-latin-v3.woff2
旧 URL 引用：/rigid-gift-boxes/ /resources/ /packaging-examples/ /pricing/ /sustainability/ → summary {} （0 条）
```

**④ 公网口径抽查**

```text
/products/          200  312368 B  ¥=0 \u00a5=0 1688=0  Get a price 46 处
catalog-ring-box    200  78060 B   ¥=0 \u00a5=0 1688=0
catalog-wooden-box  200  76686 B   ¥=0 \u00a5=0 1688=0
catalog-velvet-box  200  89669 B   ¥=0 \u00a5=0 1688=0
catalog-paper-box   200  84843 B   ¥=0 \u00a5=0 1688=0
catalog-gift-box    200  81047 B   ¥=0 \u00a5=0 1688=0
meta 抽查 /catalog-velvet-box/：len=104
  Velvet Jewelry Boxes — Reference Catalogue. Custom logo printing, MOQ 500 per design. Samples available.
```

**执行结束。** 生产 = `packrivo-home-v4 4.0.4` + 字体补丁 + 正文/meta 归零，缓存由负责人清，本流程全程未清缓存、未碰密码与 `.cf.env`。

产物：
- `deliver/…` 见 `$BACKUP = /home/web/backups/29z-20261030-103332`（主题 4.0.1 目录、mu 原文件、三表 sql、审计 json、home-before）
- 仓库：`deploy/verification-29z/evidence/{products-shots/, crawl-29z-after-cf-prod.json, old-url-sources-prod.json}`

一处手册与实现的偏差（不改手册，先记录）：`29z-crawl-prod.mjs` / `29z-old-url-sources.mjs` 的参数是 **out-dir**，
按手册写成 `xxx.json` 会建出一个同名目录；本次已把 crawl 结果规整为 `crawl-29z-after-cf-prod.json`。