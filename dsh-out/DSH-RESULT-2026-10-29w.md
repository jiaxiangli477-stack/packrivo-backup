# 29w-B 结果：RUNBOOK 4.6 / 9.7.5 + 本地整轮演练

任务：`DSH-TASK-2026-10-29w.md` B 部分（本地，不碰生产）。
本轮提交：`fa49b5b6f0e1428b02f1e4494c1b886ea73a4460`
RUNBOOK blob：`6a59f984b706aacef5b773f329a2842d287fe101`（`git rev-parse HEAD:deploy/RUNBOOK-launch.md`）

## 0 · 一句话

六项交付全部落地并跑过：RUNBOOK 多了 **4.6 指定页面模板** 与 **9.7.5 回滚**，演练复位会把页面 8 的模板 meta 删掉，字体 404 定位到 **mu 插件**（不是子主题）并修好，整轮演练 **基线仍是 `8971f447…`、回滚逐字节回到基线、三档无横向溢出**。

## 1 · RUNBOOK（`deploy/RUNBOOK-launch.md`，811 → 892 行）

| 位置 | 内容 |
|---|---|
| L508–L571 | **`## 4.6 指定页面模板 _wp_page_template`**：为什么（没有这条 meta 就走 `page.php`，正文 legacy HTML 原样吐出）、生产实测（`post meta get 8` → 空 / `rc=1` / meta 共 10 条、全站只有这一页需要）、4.6.1 守卫、4.6.2 记原值、4.6.3 执行、判据 4.6a–4.6f、回滚 |
| L717–L727 | **`# 9.7.5`**：在 9.8 之后、9.6 之前，按 `page8_template_before` 还原；`EMPTY` → `post meta delete` |
| L845 | 附录 B 新增 4.6 行（1 min，不可跳过）；第 9 行改成「先 9.8 删报价页 → 9.7.5 还原页面模板，再 9.7 → 9.2」 |
| L891 | 附录 C-1 补记：29 号上线后发现的**第三处同类遗漏**即本文这条，成因与 0.1、4.5 相同（本地库有、RUNBOOK 没写、演练库从本地复制） |

两处判断题记在这里，免得下次又踩：

- **4.6 的机器顺序在第 5 步之后**（编号仍是任务单要求的 4.6）。模板文件 `template-products-v4.php` 是随子主题装进来的，第 5 步之前跑 4.6.1 的守卫必然失败——RUNBOOK 正文第一段就写明了这一点。
- **4.6f 不能要求 `/get-a-quote/` 200**。27e 补丁在第 6 步才打，4.6 执行时它必然还是 301。第一版判据写成 200，演练当场 FAIL（4.6f3），已改成「此刻仍是 301，第 6 步之后才该变 200」。

## 2 · 本地 vs 生产逐项对照（有没有第三处遗漏）

本地数据取自演练库（= 本地库副本，`logs/compare-local-templates.log`）；生产数据取自 29w-A 的只读取证日志（本轮**不碰生产**）。

| 项 | 本地 | 生产（29w-A 取证时） | 结论 |
|---|---|---|---|
| 非 default 的 `_wp_page_template` | **1 条**：`8 / page / publish / products → template-products-v4.php` | **0 条**（`post list --meta_key=_wp_page_template --meta_compare='!=' --meta_value=default` 命中为空） | **第三处遗漏就是这条**，4.6 已补 |
| 页面 8 | `products` / `publish` / "Custom Packaging Products" | 同 | 一致 |
| 模板文件 | 子主题 `template-products-v4.php` | 同，7,768 B，`Oct 29 03:43` | 一致 |
| `show_on_front` | `page` | `page` | 一致 |
| `page_on_front` | `7`（`home`） | `7` | 一致 |
| `page_for_posts` | `53`（`blog`） | `53` | 一致 |

**没有第四处**：全库扫下来非 default 模板只有页面 8 一条；三个阅读设置逐项相同；父主题目录里没有 `template-*.php`（模板全在子主题，这点两边一致）。缺口：生产的页面 7 / 53 本身没在 B 轮取数（不碰生产），只能由三个 option 值（指向 7 / 53）间接确认它们在位。

## 3 · `reset.sh`：复位时删掉页面 8 的模板 meta

`deploy/verification-29w/scripts/reset.sh` 新增第 5.5 段：

```bash
P8T=$($WPCLI post meta get 8 _wp_page_template 2>/dev/null); echo "  复位前 = ${P8T:-（无）}"
$WPCLI post meta delete 8 _wp_page_template 2>/dev/null || echo "  本来就没有这条 meta"
$WPCLI post meta list 8 --keys=_wp_page_template --format=csv
$WPCLI post meta get 8 _wp_page_template >/dev/null 2>&1; echo "  复位后 post meta get rc=$?（期望 1）"
```

这就解释了 29v 为什么没暴露这个 bug：快照指纹（`snapshot.sh`）只记 mu/字典/主题/插件/页面标题/选项，**不含 post meta**，所以本地库带着的这条 meta 在整轮演练里既没进基线、也没被复位清掉，`/products/` 一直是新模板，看不出生产缺 meta。同一次改动还顺带把主题/插件删除改成「容器内 + 宿主机」两侧都删（Windows 绑定挂载上单侧删再重建，本轮实测出过一次目录消失，5c FAIL）。

为了不把基线改成别的数，`snapshot.sh` 保留了旧口径（844 行 / `8971f447…`），另出一份 `*.txt.ext` = 旧口径 + `page8_template=` 一行。正向 diff 用 `.ext` 看模板变化，基线比较仍用旧口径。

## 4 · 字体 404：出处不在子主题 zip 里

任务单假定要改子主题的字体路径并重打包。**实际不是**：

```text
$ grep -rn 'inter-latin\|woff2' <子主题解包目录>
page.php:19: $reference = get_template_directory() . '/packrivo-static/assets/inquiry-controls.html'   ← 只命中这行，且路径写法是对的
（其余 37 个条目零命中）
```

真正的出处是 **mu 插件** `wp-content/mu-plugins/packrivo-wave2.php` 第 35 行（生产、本机 `packrivo-live`、演练站三份文件 sha256 同为 `5ab5be3f…`）：

```php
// 改前：子主题目录里没有 packrivo-static/assets/，所以这条 100% 404
define('PACKRIVO_W2_FONT_URL', get_stylesheet_directory_uri() . '/packrivo-static/assets/packrivo-inter-latin-v3.woff2');
// 改后：字体在父主题里
define('PACKRIVO_W2_FONT_URL', get_template_directory_uri() . '/packrivo-static/assets/packrivo-inter-latin-v3.woff2');
```

它同时出现在 `<link rel="preload" as="font">`（`wp_head` priority 1）和 `@font-face` 里，所以之前是「首访走系统字体、命中缓存跳品牌字」。父主题 `remediation-ui.css:2` 那条 `url("./packrivo-inter-latin-v3.woff2")` 是相对路径、本来就对，不动。

- 补丁：`deploy/patches-29w/packrivo-wave2-font-path.patch`，sha256 `022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d`；`git apply -p1 --check` 通过。
- 落盘：`5ab5be3f…` → `73d43ba0…`（LF；Windows 上 `git apply` 会写成 CRLF，文本一致但哈希变 `a13fa03e…`，Linux 上按 LF 打即可）。
- 验证：页面上现在只剩父主题路径，`curl` 该路径 **200**；改前同一路径的子主题版本 **404**。
- **子主题 zip 没重新打包**，哈希仍是 `5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9`（790,164 B / 38 条目）。理由：里面根本没有这段代码，重打包只会产生一个字节不同、内容相同的包，还要连带改 RUNBOOK 里 5.2 的哈希判据——没有技术收益。

## 5 · 整轮重跑（复位 → 正向 0–8 → 回滚 9 → 反向自检）

时间线（2026-10-29 UTC）：

| 阶段 | 时间 | 说明 |
|---|---|---|
| 段 1 `13:11:35 → 13:43:49` | 复位 + 基线快照 + 正向 11 步 + 正向快照 | 全程单线程 |
| 4.6e 浏览器判定 `17:13:17` | 真实 Chrome 三档 | 段 1 结束、回滚开始**之前**的同一份正向态 |
| 段 2 `17:13:44 → 17:27:50` | 第 9 步回滚 + 快照 + 反向自检 | |

> 中间 13:43→17:13 的空档是环境事故，不是流程：Docker Desktop 在 13:45 左右自行退出（容器全停），17:12 重新拉起后先核对了站点仍是正向态（`post meta get 8 _wp_page_template` = `template-products-v4.php`、`option get stylesheet` = `packrivo-home-v4`、`/products/` body 带 `page-template-template-products-v4`），再跑 4.6e，读数与段 1 的正向快照一一对得上。MySQL 与站点数据完好，回滚照常跑完。

### 5.1 每一步判据

```text
step0  :: 本步判据：PASS=10 FAIL=0 :: EXIT=0
step1  :: 本步判据：PASS=10 FAIL=0 :: EXIT=0
step2  :: 本步判据：PASS=8  FAIL=0 :: EXIT=0
step3  :: 本步判据：PASS=11 FAIL=0 :: EXIT=0
step4  :: 本步判据：PASS=3  FAIL=0 :: EXIT=0
step45 :: 本步判据：PASS=5  FAIL=0 :: EXIT=0      ← 4.5 建报价页
step5  :: 本步判据：PASS=4  FAIL=0 :: EXIT=0      ← 子主题（含装完硬校验）
step46 :: 本步判据：PASS=9  FAIL=0 :: EXIT=0      ← 4.6 页面模板（新增）
step6  :: 本步判据：PASS=12 FAIL=0 :: EXIT=0
step7  :: 本步判据：PASS=1  FAIL=0 :: EXIT=0
step8  :: 本步判据：PASS=9  FAIL=0 :: EXIT=0
step9  :: 本步判据：PASS=11 FAIL=0 :: EXIT=0      ← 含 9.8 / 9.7.5 / 9h / 9h2
```

step46 原始读数：

```text
page8_template_before=EMPTY (rc=1) at=2026-10-29T13:35:11Z by=rehearsal-29w
页面8=[products/publish] 模板=[template-products-v4.php] body=[page-template-template-products-v4]
文案=1 de=[page-template-template-products-v4] ar=[page-template-template-products-v4]
状态码=/200 /blog/200 /get-a-quote/301（27e 未打，预期空档）
PASS: 4.6-0 守卫：页面 8 是 page/products/publish
PASS: 4.6a meta 值 = template-products-v4.php
PASS: 4.6b /products/ body 含 page-template-template-products-v4
PASS: 4.6c /products/ 出现 Every structure we can source
PASS: 4.6d /de/products/ 也是新模板
PASS: 4.6d2 /ar/products/ 也是新模板
PASS: 4.6f 首页 200
PASS: 4.6f2 /blog/ 200
PASS: 4.6f3 /get-a-quote/ 此刻仍是 301（27e 未打，预期空档）
```

### 5.2 快照

| 阶段 | 旧口径（基线口径） | ext（含 `page8_template`） |
|---|---|---|
| 复位后基线 | `8971f447510523ed468eedfaa9ceec1ac1d4762465bfd0fb006ae58dad3dd2b4`（844 行）✔ 就是要的那个数 | `43093ec5b3ddfced8f0066908646c64938d65a59ec5c892217fdce0d60971a65`（845 行） |
| 正向 | `6f941f16170827bc59f1de8732c1bf7e93aff5f71a262f9c125f1c885e570cd1`（847 行） | `cfb56417f8115c6f26d76009b0c19f8960e58c4bc7446611fc1ca240393949ca`（848 行） |
| 回滚后 | **`8971f447…`（逐字节 = 基线）** | **`43093ec5…`（= 基线，模板 meta 已还原）** |
| 反向自检后 | **`8971f447…`** | **`43093ec5…`** |

正向 vs 基线（`snapshots/fwd-vs-base.diff`，449 行 / 14 段 hunk / `+163 -160`）逐项解释：

- `-stylesheet=packrivo-static-bridge-1.2.38` / `+stylesheet=packrivo-home-v4` —— 第 5 步装并启用子主题；
- `-page8_template=NONE` / `+page8_template=template-products-v4.php` —— **这就是 4.6 的效果**，也是 ext 口径存在的理由；
- 其余全是这些步本来就该改的：主题列表（`packrivo-home-v4` 从无到有）、插件列表（`packrivo-quote`）、mu/字典的 sha256、以及 step3 导入的 134 产品页 + 8 主页面（页面标题段 `@@ -629,107 +631,107` 就是这一片）。

正向 vs 29v 正向（`snapshots/fwd-vs-29v.diff`，18 行 / 2 段 hunk）只有一处实质差异：

```diff
-5444,get-a-quote,"Get a Quote"
+6489,get-a-quote,"Get a Quote"
```

`/get-a-quote/` 是每轮 step45 新建的，自增 ID 必然不同；除此之外正向状态与 29v 逐行一致——也就是说 4.6 只加了那一条 meta，没有碰别的东西。

### 5.3 4.6e：三档横向溢出（真实 Chrome）

`evidence/rehearsal-browser.json`（`2026-10-29T17:13:17Z`，同一份正向态）：

| 页面 | 宽 | 状态 | 模板类 | `scrollWidth` | `innerWidth` | 越界元素 | chips / cards |
|---|---|---|---|---|---|---|---|
| `/products/` | 375 | 200 | 是 | 375 | 375 | 0 | 7 / 24 |
| `/products/` | 768 | 200 | 是 | 768 | 768 | 0 | 7 / 24 |
| `/products/` | 1024 | 200 | 是 | 1024 | 1024 | 0 | 7 / 24 |
| `/de/products/` | 1024 | 200 | 是 | 1024 | 1024 | 0 | 7 / 24 |
| `/ar/products/` | 1024 | 200 | 是 | 1024 | 1024 | 0 | 7 / 24 |
| `/` | 1024 | 200 | — | 1024 | 1024 | 0 | 0 / 8 |
| `/blog/` | 1024 | 200 | — | 1024 | 1024 | 0 | 7 / 0 |
| `/get-a-quote/` | 1024 | 200 | — | 1024 | 1024 | 0 | 0 / 0 |

判据实现：`document.documentElement.scrollWidth === window.innerWidth` **且** 越界元素数 0（越界元素 = 右边界超出视口 1px 以上、且不在任何 `overflow-x: auto/scroll/hidden` 容器里）。

页面 HTML 里是 `siteurl=http://nginx` 的绝对地址，宿主机解析不到；直接打 `127.0.0.1:8091` 会被 canonical 跳到 `http://127.0.0.1/products/` 然后连接被拒。判据脚本起了一个本地 HTTP 代理（`127.0.0.1:8899`）把 Host 原样带给 `127.0.0.1:8091`，浏览器全程访问 `http://nginx/...`，所以 WP 看到的 Host 与 `siteurl` 一致。截图：`evidence/rehearsal-products-{375,768,1024}.png`。

### 5.4 回滚与反向自检

```text
PASS: 9.3 /pkg/rollback/rollback-27g.json site 与本轮一致
PASS: 9.3 /pkg/rollback/rollback-27i.json site 与本轮一致
PASS: 9a before-mu.sha256 全 OK
PASS: 9b stylesheet 回到父主题
PASS: 9c 子主题目录已删
PASS: 9d 报价插件已移除
PASS: 9e 首页 200
PASS: 9f /get-a-quote/ 回到 301
PASS: 9g 报价页已删（任何状态计数 0）
PASS: 9h 页面 8 模板 meta 已还原（首装前不存在）
PASS: 9h2 页面 8 回到默认模板类
```

反向自检（3.0 守卫）：

```text
PASS: 3.0 /pkg/rollback/rollback-27g.json site 与本轮一致
PASS: 3.0 /pkg/rollback/rollback-27i.json site 与本轮一致
FAIL: 3.0 /pkg/rollback/rollback-bogus.json 非本轮产物（site=http://wrong-site.invalid，期望 http://nginx），先清空 /pkg/rollback
PASS: 3.0 守卫拦住他站 JSON，整步非零退出，apply 未执行
PASS: 反向自检没有改动站点（仍 = 基线）
```

第三行的 `FAIL` 是**守卫按设计把他站 JSON 拒掉**的检测输出（29v 同款），不是整轮失败；后面两行证明它没执行 apply、站点仍是基线。

## 6 · 字体修完后站点是什么状态

回滚完成、反向自检完成后才打的字体补丁（这样基线才能保持 `8971f447…`）。打完再取一次快照：

```text
基线   s-reset.txt          8971f447510523ed468eedfaa9ceec1ac1d4762465bfd0fb006ae58dad3dd2b4
修字体 s-after-fontfix.txt  01c1c448743c7d797d4925e3af0903e1805391bbb431159062b278ca846599b7

- 5ab5be3f85f46ce5416fdb4cafc98f54de86a1fc4ece041c56c2de40173a1d8c  packrivo-wave2.php
+ 73d43ba0262a9d74249b19a46380bc5c338f94bd9e0776baa46eddf074c9f259  packrivo-wave2.php
```

**只有这一行不同**（`snapshots/fontfix-vs-baseline.diff`）。这也解释了第一次跑的时候基线为什么变成 `01c1c448…`：当时字体补丁已经在 mu 里，而 `snapshot.sh` 会把 mu 目录全部文件的 sha256 记进去。所以本轮的处置是——**跑的时候用原版 mu 保住 `8971f447…` 基线，跑完再打补丁**。下次若从带修复的环境起跑，基线就是 `01c1c448…`，差异只有一行，别当故障。

## 7 · 生产侧仍然待办（本轮不做）

- 字体补丁**只有本地**：`deploy/patches-29w/packrivo-wave2-font-path.patch` 已在仓库里，生产那份文件 sha256 也是 `5ab5be3f…`，打上去就是 `73d43ba0…`。要落地得另开一次热修复授权（读 → `git apply` → `php -l` → 页面不再引用子主题路径 → 观测）。
- #56 与 Task 55 仍是暂停状态，本轮没碰。

## 8 · 交付物

| 路径 | 内容 |
|---|---|
| `deploy/RUNBOOK-launch.md` | 4.6（L508–L571）、9.7.5（L717–L727）、附录 B（L845）、附录 C-1 补记（L891） |
| `deploy/patches-29w/packrivo-wave2-font-path.patch` | 字体路径补丁（13 行） |
| `deploy/verification-29w/README.md` | 与 29v 的差异、怎么跑、结果表 |
| `deploy/verification-29w/scripts/` | `rehearsal.sh`（含 step46、9.7.5、9h/9h2）、`reset.sh`（含 5.5）、`snapshot.sh`（ext 口径）、`run29w.sh`、`negtest29w.sh`、`runner-entry.sh`、`29w-browser.mjs`、`ref/s-forward-29v.txt` |
| `deploy/verification-29w/logs/` | 23 份原始日志（每步一份 + step9 + negtest + run + reset + 字体验证 + 本地模板对照） |
| `deploy/verification-29w/snapshots/` | 四阶段快照（旧口径 + ext）、基线哈希、两份 diff、字体 diff |
| `deploy/verification-29w/evidence/` | `rehearsal-browser.json` + 三张 `rehearsal-products-*.png` |
| `.gitignore` | 新增 `!deploy/verification-29w/logs/*.log`（证据入库） |

## 9 · `git log -3 --stat`

```text
fa49b5b 2026-10-29 10:34:20 -0700 deploy: RUNBOOK 新增 4.6 指定页面模板与 9.7.5 回滚；29w 演练脚本+证据；字体 404 补丁
 .gitignore                                         |   3 +-
 deploy/RUNBOOK-launch.md                           |  83 +-
 deploy/patches-29w/packrivo-wave2-font-path.patch  |  13 +
 deploy/verification-29w/README.md                  | 103 +++
 deploy/verification-29w/env/Dockerfile.php         |  11 +
 deploy/verification-29w/env/README-ENV.md          |  92 +++
 .../evidence/rehearsal-browser.json                | 230 ++++++
 .../evidence/rehearsal-products-1024.png           | Bin 0 -> 59597 bytes
 .../evidence/rehearsal-products-375.png            | Bin 0 -> 41216 bytes
 .../evidence/rehearsal-products-768.png            | Bin 0 -> 64152 bytes
 .../logs/compare-local-templates.log               | Bin 0 -> 1296 bytes
 deploy/verification-29w/logs/font-fix-verify.log   |  15 +
 deploy/verification-29w/logs/fwd-step0.log         | 124 +++
 deploy/verification-29w/logs/fwd-step1.log         |  61 ++
 deploy/verification-29w/logs/fwd-step2.log         | 106 +++
 deploy/verification-29w/logs/fwd-step3.log         |  78 ++
 deploy/verification-29w/logs/fwd-step4.log         |  31 +
 deploy/verification-29w/logs/fwd-step45.log        |  39 +
 deploy/verification-29w/logs/fwd-step46.log        |  39 +
 deploy/verification-29w/logs/fwd-step5.log         |  43 ++
 deploy/verification-29w/logs/fwd-step6.log         |  70 ++
 deploy/verification-29w/logs/fwd-step7.log         |  23 +
 deploy/verification-29w/logs/fwd-step8.log         |  54 ++
 deploy/verification-29w/logs/negtest-step3.log     |  19 +
 deploy/verification-29w/logs/negtest.log           |  37 +
 deploy/verification-29w/logs/reset.log             |  33 +
 deploy/verification-29w/logs/run29w-fwd.log        | 488 ++++++++++++
 deploy/verification-29w/logs/run29w-rb.log         |  30 +
 .../verification-29w/logs/snap-after-negtest.log   |   5 +
 .../verification-29w/logs/snap-after-rollback.log  |   5 +
 deploy/verification-29w/logs/snap-forward.log      |   5 +
 deploy/verification-29w/logs/snap-reset.log        |   5 +
 deploy/verification-29w/logs/step9.log             | 147 ++++
 deploy/verification-29w/scripts/29w-browser.mjs    | 115 +++
 deploy/verification-29w/scripts/negtest29w.sh      |  43 ++
 .../verification-29w/scripts/ref/s-forward-29v.txt | 847 ++++++++++++++++++++
 deploy/verification-29w/scripts/rehearsal.sh       | 610 +++++++++++++++
 deploy/verification-29w/scripts/reset.sh           |  75 ++
 deploy/verification-29w/scripts/run29w.sh          |  94 +++
 deploy/verification-29w/scripts/runner-entry.sh    |  14 +
 deploy/verification-29w/scripts/snapshot.sh        |  50 ++
 .../verification-29w/snapshots/baseline-ext.sha256 |   1 +
 deploy/verification-29w/snapshots/baseline.sha256  |   1 +
 .../snapshots/fontfix-vs-baseline.diff             |   2 +
 deploy/verification-29w/snapshots/fwd-vs-29v.diff  |  18 +
 deploy/verification-29w/snapshots/fwd-vs-base.diff | 448 +++++++++++
 .../verification-29w/snapshots/s-after-fontfix.txt | 844 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-after-fontfix.txt.ext | 845 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-after-negtest.txt | 844 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-after-negtest.txt.ext | 845 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-after-negtest.txt.raw | 849 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-after-rollback.txt | 844 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-after-rollback.txt.ext | 845 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-after-rollback.txt.raw | 849 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-forward.txt    | 847 ++++++++++++++++++++
 .../verification-29w/snapshots/s-forward.txt.ext   | 848 ++++++++++++++++++++
 .../verification-29w/snapshots/s-forward.txt.raw   | 852 +++++++++++++++++++++
 deploy/verification-29w/snapshots/s-reset.txt      | 844 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-reset.txt.ext  | 845 ++++++++++++++++++++
 deploy/verification-29w/snapshots/s-reset.txt.raw  | 849 ++++++++++++++++++++
 .../snapshots/step-summary-fwd.txt                 |  11 +
 61 files changed, 16169 insertions(+), 2 deletions(-)

ab8755f 2026-10-29 02:06:22 -0700 29w-A 结果文档：五条判据全 PASS、8.4 缺口关闭、回滚预案为 post meta delete
 dsh-out/DSH-RESULT-2026-10-29w-A.md | 81 +++++++++++++++++++++++++++++++++++++
 1 file changed, 81 insertions(+)

8e12894 2026-10-29 02:06:09 -0700 29w-A 生产热修复：/products/ 设 template-products-v4.php（原值不存在），五条判据全 PASS；顺带补上 8.4 横向溢出缺口（18 组全 OK）
 deploy/LAUNCH-LOG-2026-10-28.md                    |  90 ++++++++++
 .../verification-29w/evidence/overflow-sweep.json  | 164 +++++++++++++++++++++
 deploy/verification-29w/evidence/products-1024.png | Bin 0 -> 201288 bytes
 deploy/verification-29w/evidence/products-375.png  | Bin 0 -> 69088 bytes
 deploy/verification-29w/evidence/products-768.png  | Bin 0 -> 355908 bytes
 deploy/verification-29w/evidence/products-ar-1024.png | Bin 0 -> 212931 bytes
 deploy/verification-29w/evidence/products-de-1024.png | Bin 0 -> 71117 bytes
 .../verification-29w/evidence/render-check.json    |  56 ++++++
 8 files changed, 310 insertions(+)
```

本文档自身的提交（`dsh-out/DSH-RESULT-2026-10-29w.md`）在 `fa49b5b` 之后，所以上面的 log 里还没有它。
