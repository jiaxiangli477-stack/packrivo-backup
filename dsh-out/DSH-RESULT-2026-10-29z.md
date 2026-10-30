# DSH 结果 · 2026-10-29z

任务单：`packrivo-v2/docs/DSH-TASK-2026-10-29z.md` @ `c62bc88`。
执行方式：只动本地仓库与演练环境（`E:\破甲工作区\.tmp\28q\rehearsal`，`0.0.0.0:8091`）。**没有碰生产**：没 SSH、没登录、没导出生产库。第 0 节第 1 步按要求停等授权，第 4 节没开始。

## 0. 一句话状态

| 节 | 状态 | 一句话 |
|---|---|---|
| §0 导生产库 + 基线 + meta 来源 | **⏸ 停等授权** | 需要业主一句放行；本地只能给出 seed 库同口径基线（208 / 1 / 209，与任务单一致） |
| §1 `strip-1688.php` | ✅ 交付 | 脚本 + 包装器 + 干跑/真跑/幂等/复位全部在演练库跑通 |
| §2 九条判据 | ⚠️ 17 PASS / 1 FAIL | 唯一 FAIL 是「`/products/` 渲染哈希不变」，它与「正文 `¥` 归零」互斥，见 §5 |
| §3b 首页大图 | ✅ 2/2 PASS | 两档尺寸 + alt + 三处哈希 + 版本 4.0.3 + 重新打包 |
| §3c 死链 + 字体 | ⚠️ 主题层 0 死链；60 页爬取受演练媒体缺失所限 | 五个旧链接全站 0 引用；字体 200 `font/woff2`；爬取的 298 条非 200 已分类，见 §7 |
| 4.0.3 包 + RUNBOOK | ✅ 锁定 | zip `7d792ad9…945f7`，RUNBOOK 末尾新增附录 D（步骤/判据/回滚） |

## 1. §0 停在哪、需要什么授权

任务单 §0 第 1 步是「把生产库导出来」。这一步只在生产 SSH 上做，所以按你的规矩停住，等你一句放行。

放行后我跑的就是这四段（对生产只读，不写表、不动文件、不清缓存）：

```bash
# 1) 只读导出到容器 /tmp（不落生产盘）
$WPCLI db export /tmp/29z-prod.sql --skip-plugins --skip-themes
docker exec php sh -c 'wc -c /tmp/29z-prod.sql'        # 必须 > 0

# 2) 拉回本机演练目录
docker cp php:/tmp/29z-prod.sql E:/破甲工作区/.tmp/28q/29z-prod.sql
curl -s -o /dev/null -w '%{http_code}\n' https://www.packrivo.com/   # 顺手确认站点活着

# 3) 灌进演练库（已有 /tmp/29z-db-before.sql 可回滚）
docker exec php sh -c 'wp --path=... --allow-root db import /tmp/29z-prod.sql'

# 4) 打印基线计数（口径见 §3）
```

## 2. §0.3「`<meta name="description">` 到底哪来的」——本地已查到的部分

演练库实测（证据 `deploy/verification-29z/evidence/baseline-counts-seed.txt`）：

- `wp_postmeta._yoast_wpseo_metadesc`：catalog 208 页**全部有值**，形如
  `Reference SKU: Ring Box — Clear Window, Flip Lid, Storage. 500 per design (custom print), lowest tier ¥0.03. …`
- `wp_postmeta._yoast_wpseo_opengraph-description`：**0 条**（字段全空）。
- `wp_yoast_indexable.description`：与 postmeta 逐字一致（189 条带 `¥`）。
- 但这一版演练渲染出的 catalog 页 **一条 `<meta name="description">` 都没有**（`grep -c` = 0），`<title>` 出现两次。

所以「HTML 里的 meta description」不是这三处直出的；生产那 208 条 `Historical 1688 reference…` 一定还有第四个来源。导完生产库跑这三条就能钉到文件行/表字段：

```bash
# a) mu-plugin 里有没有 wp_head 过滤器自己打 meta
docker exec php sh -c 'grep -rn "name=\"description\"\|wpseo_metadesc\|wp_head" \
  /var/www/html/www.packrivo.com/wordpress/wp-content/mu-plugins/*.php | grep -v "\.bak" | head'

# b) 生产 Yoast 表结构（有没有 open_graph_description 列）
docker exec php sh -c 'wp --path=... --allow-root db query "DESCRIBE wp_yoast_indexable" --skip-column-names | cut -f1 | tr "\n" " "'

# c) 生产那一页的头部长什么样（只读 curl，不登录后台）
curl -s https://www.packrivo.com/catalog-ring-box-o1cn01ahes/ | grep -o '<meta name="description"[^>]*>'
```

脚本已经同时覆盖 postmeta + indexable（`description` / `open_graph_description` / `twitter_description` 按实际列名自适应）；若 §0.3 查出第四来源是 mu-plugin 过滤器，再补一条过滤器的写法即可，前面不用重跑。

## 3. 基线计数（演练 seed 库，口径与任务单一致）

| 项 | 任务单给的数 | 本地实测 | 结论 |
|---|---|---|---|
| `catalog-` 开头的 publish 页 | 208 | **208** | 一致 |
| `post_content` 含 `1688` 的 publish 行 | 1 | **1** | 一致 |
| `post_content` 含 `¥` 的 publish 行 | 209 | **209** | 一致 |
| `post_content` 含 `detail.1688.com` | — | **1** | 与上面那 1 条同源（博客里的 `<a>`） |
| `_yoast_wpseo_metadesc` 含 `1688`/`¥` | — | **189** | 新增口径 |
| `_yoast_wpseo_opengraph-description` 含 `1688`/`¥` | — | **0**（全空） | 新增口径 |
| `wp_yoast_indexable.description` 含 `1688`/`¥` | — | **189** | 与 postmeta 一致 |

生产库导进来后用同一段 SQL 再打一遍，两列一比就知道生产上多出哪些页。

## 4. §1 交付：`deploy/patches-29z/strip-1688.php`

`wp eval-file` 会把 `--dry-run` 当参数错误拦掉，所以干跑开关是环境变量 `STRIP1688_DRY=1`；另给包装器 `strip-1688.sh --dry-run|--apply`。

**干跑实测（演练库，未写库）**：

```
catalog 页 208 / 正文被改 208 / 删价格行 192 / 删 What this tells you 块 192
对照表价格单元格改链接 222（表头 16 处）/ 正文删价格句 22
/products/ 删 <p> 158 / 博客去链 <a> 1
meta 要写 208（长度 84–155，>155 的 0 条，去重后 208/208）
indexable 要更新 208 行 / 正文仍有 1688 痕迹 0 页
```

它按真实标记处理，不是固定串替换：

1. 规格表 `<th>Lowest listed tier / Historical 1688 reference</th>` 整行删，原位换 `<th scope="row">Price</th><td><a href="/get-a-quote/">Get a price →</a></td>`；
2. 分类 hub 页对照表：表头 `Lowest tier` → `Price`，该列价格单元格换报价链接（16 张表 222 格）；
3. `What this tells you` 的 `<h2>` + 后面讲价格那段整块删（只删与价格有关的，无关段落不动）；
4. 正文 `<p>/<li>` 里带 `¥ / yuan / RMB / CNY / ex-works / US$0.0 / 1688` 的**句子**逐句删，删掉的 22 句全进审计 `evidence/strip-1688-audit.json`（带页面 ID 与 slug），可以一条条看回来；
5. 208 页 meta 重写成「产品名 — 结构/材质. `Custom logo printing, MOQ 500 per design. Samples available.`」，≤155 字符、两两不同；同时写 `_yoast_wpseo_metadesc`、`_yoast_wpseo_opengraph-description`、`wp_yoast_indexable`（按实际列自适应：演练这套表没有 `og_description`，有 `open_graph_description`）；
6. `/products/` 正文 158 个 `<p>Listed at ¥…</p>` 删掉；博客里 `detail.1688.com` 的 `<a>` 去链接留文字 `a supplier size sheet (kept on file)`。

**幂等**：第二次真跑打印「正文被改 0 / meta 要写 0 / 改动合计 0」。
**复位**：`db import /tmp/29z-db-before.sql` 之后 `/products/` 哈希回到 `e2dc30ec…`、catalog 页 3037 回到 `afdc24a0…`。

## 5. §2 九条判据（演练库实测 17 PASS / 1 FAIL）

脚本 `deploy/verification-29z/scripts/29z-criteria.sh before|compare`，日志 `evidence/criteria-29z-seed.log`。

| # | 判据 | 结果 |
|---|---|---|
| 1 | publish 正文里 `1688`/`¥`/`detail.1688.com` 归零 | **PASS**（0 / 0 / 0） |
| 2 | catalog meta 无价格/来源词、≤155、208 条互不相同、带固定尾句 | **PASS**（0 / 0 / 208 / 208） |
| 3 | slug 与 robots 没被动过（ID/slug/status 集合、noindex 集合 md5） | **PASS**（两次 md5 相同） |
| 4 | `/products/` 渲染哈希逐字节一致 | **FAIL**（`e2dc30ec…` → `60727d53…`） |
| 5 | 被改的对象只能是 208 catalog + `/products/` + 那 1 篇博客 | **PASS**（210 个，集合外 0 个） |
| 6 | 非 catalog 对象的 meta 一条都没动 | **PASS**（0 个 id 变化） |
| 7 | indexable 与 postmeta 一致 | **PASS**（不一致 0 条） |
| 8 | 页面上 `¥` 归零（`/products/` 与 catalog 页） | **PASS**（0 / 0） |
| 9 | 报价链接可点（`/get-a-quote/` 301→`/inquiry/` 200；页面里 `Get a price` 在） | **PASS** |

**第 4 条为什么必 FAIL（证据，不是猜）**：`template-products-v4` 不是「忽略 `post_content`」——它把正文塞进页面里的那段 `<script>` JSON。删掉 158 个价格 `<p>` 后：

- 数据库正文 264,777 → 254,238 字节；
- 渲染 HTML 534,303 → 522,438 字节（`evidence/products-before.html` / `products-after.html`），哈希必然变。

也就是说「正文 `¥` 归零」和「`/products/` 渲染逐字节不变」在同一个模板上互斥，只能取一。现在取归零（判据 8 两个 PASS，任务单 §1 明确要求这些块必须删）。要取相反只能改模板（渲染时过滤），那是改代码不是改内容，需要你点头。
`/products/` 里还有 1 处 `Listed at` 是模板自带静态文案，不含数字，没删。

## 6. §3b 首页大图（2/2 PASS）

| 判据 | 结果 | 证据 |
|---|---|---|
| 1440 与 375 各一张截图 | **PASS** | `evidence/screens/home-29z-1440.png`（1440×900，271,310 B）、`home-29z-375.png`（375×812，104,483 B）：两档 HTTP 200、无横向溢出、hero 加载成功（naturalWidth 640 / 345），判定数据 `evidence/29z-home-shots.json` |
| zip / mirror 树 / 装好后三处哈希一致 | **PASS** | 见下表；另：装好的主题 38 个文件与 zip 逐字节一致（0 missing / 0 mismatch） |

| 文件 | zip 内 | mirror 树 | 演练装好后 |
|---|---|---|---|
| `hero.webp` | `6a55801599da73d2bd8148f152f04b469c2bc8076f57b36f1dc64ecc8e623e1e` | 同 | 同 |
| `hero-480.webp` | `266032c7bdfc0f51351bb1e7b0607ca66e07bb95fc7af1c1a9c6ab5075ee839d` | 同 | 同 |

alt = `Two small cream gift boxes with brown satin ribbon, held in open hands`（图里真实可见：两只米色礼盒、棕色缎带、捧在手上）；全站无 `Supplier product photo`；渲染的 `<img>` 带 `srcset` 480w/1200w。
像素溯源：`hero.webp` 放大回 1254 与源图 RMSE 2.98、`hero-480` RMSE 5.02 —— 同一张图，不是换图。
4.0.3 包：791,053 B / 43 条目（38 文件 + 5 目录）/ `7d792ad9…945f7`；`style.css` 与 `functions.php` 都是 4.0.3；`deploy/` 与 `packrivo-wp/` 两份 zip 同哈希。

## 7. §3c 死链与字体

**主题层（本次改动范围）0 死链**：

| 原链接 | 现在 | 实测 |
|---|---|---|
| `/resources/`（导航 Resources、面板 Guides） | `/blog/` | `/blog/` 200；首页 `/resources/` 出现 0 次 |
| `Packaging examples` `<li>` | 删除 | 全站 0 次引用 |
| `Price index` → `/pricing/` | `/get-a-quote/`，文案 `Price calculator` | 301 → `/inquiry/` 200；首页 `/pricing/` 0 次 |
| `Rigid gift boxes`（产品分组） | 删除 | 0 次引用 |
| `Materials and sustainability →` `/sustainability/` | `/customization/` | `/customization/` 200；首页 `/sustainability/` 0 次 |
| Inter woff2 | 走 29w 补丁（`get_template_directory_uri()`） | 200，`font/woff2`，34,952 B |

`Preisrechner`（de）与 `Price calculator`（en）都在渲染里；11 份字典合并进 uploads（`Price calculator` 11/11）。

**60 页爬取**（12 语种 × 首页/`products`/`services`/博客/产品页；脚本 `scripts/29z-browser.mjs`，原始数据 `evidence/crawl-29z.json`）：

- 站内链接去重 **2,146** 个，非 200 共 **298**：
  - **284** 条 `/wp-content/uploads/…` —— 演练**没有媒体库**（`uploads/` 只有 `2026/` 与 `packrivo-i18n/`，缺 `catalog-final-20261026`(158)、`packrivo-scale-20261004`(100)、`r4/product-images`(19)、`2026/10`(5)、`packrivo-scheduled-10-20261018`(2)）。这些文件生产上有，属演练缺口，不是死链结论。
  - **13** 条 `/assets/concept-*.webp` —— 旧静态站导入内容里带的图，全库 754 行引用（首页/产品/案例页都有）。**存量问题**，不在任务单列的五个链接里，要不要本轮一起清请你定。
  - **1** 条 `/#customization` —— 我脚本归一化的锅（把 `#fragment` 当 URL 请求了），不是站上的链接。
- 12 个语种的博客 URL 都是 404（`/de/2026/09/21/…`），英文那篇在 `/2026/09/21/jewelry-box-insert-fit-measurements/` 是 200 —— 多语种博客本来没做，既存状态。
- 字体我第一次脚本把 `//nginx/…` 这种协议相对路径拼错了，报了 404；单独直取是 **200 / `font/woff2` / 34,952 B**（§7 表末行）。

结论：**主题与导航层 0 死链 PASS**；「60 页非 200 = 0」在演练环境**不可判定**（媒体库不在演练里）。生产上跑同一条爬取命令才有意义（导完生产库，或直接对 `www.packrivo.com` 只读爬），脚本已写好，改 host/端口即可。

## 8. 交付物与哈希

| 物 | 路径 | sha256 |
|---|---|---|
| 子主题包 4.0.3 | `deploy/packrivo-home-v4.zip` | `7d792ad9f4e63025e2fce8a640856710ede5fe3b925e9bdaa372a6002bb945f7` |
| 去价脚本 | `deploy/patches-29z/strip-1688.php` | `bb96e87306948dd74039e35d125e637ad7e6d6beb14cf87ee80fd96212a83308` |
| 去价包装器 | `deploy/patches-29z/strip-1688.sh` | `0deea313611ff71a760baae5646bcd0252f1cbad5bdb85d0ab3e2953e112ee1e` |
| 九条判据脚本 | `deploy/verification-29z/scripts/29z-criteria.sh` | `367644719940c0f5e97b37f9a6ae7620ca33e723c47950d1903a19ab05727762` |
| 打包脚本 | `deploy/verification-29z/scripts/pack-theme-29z.py` | — |
| 大图脚本 | `deploy/verification-29z/scripts/make-hero-29z.py` | — |
| 爬取脚本 | `deploy/verification-29z/scripts/29z-browser.mjs` | — |
| 机制演练记录 | `evidence/strip-1688-mech.log` | — |
| 判据日志 | `evidence/criteria-29z-seed.log` | — |
| 删除句子审计 | `evidence/strip-1688-audit.json`（22 句） | — |
| 基线计数 | `evidence/baseline-counts-seed.txt` | — |
| 爬取数据 | `evidence/crawl-29z.json` | — |
| `/products/` 前后 HTML | `evidence/products-before.html` / `products-after.html` | — |
| 3b 哈希核对 | `evidence/3b-hashes-verify.txt` | — |
| 首页两档截图数据 | `evidence/29z-home-shots.json` | — |

RUNBOOK：`deploy/RUNBOOK-launch.md` 末尾新增 **附录 D · 29z**（装包、去价、九条判据、死链/字体对照、回滚）；zip 哈希在 §5 表与附录 D.1 两处锁定。

## 9. 还没做 / 需要你拍板

1. **§0 第 1 步**：生产只读导库 —— 等你一句「可以导」。
2. **判据 4**：`/products/` 渲染哈希与「`¥` 归零」互斥，取哪个你定（当前取归零）。
3. **13 条 `/assets/concept-*.webp`**：存量死图，要不要本轮一起清。
4. **多语种博客 404**：既存状态，不在本轮范围。
5. **§4 生产上线**：没开始。

## 10. 真跑过的命令（可复现）

```powershell
# 装包 + 激活（演练）
docker cp deploy/packrivo-home-v4.zip php:/pkg/packrivo-home-v4.zip
docker exec php sh -c 'sha256sum /pkg/packrivo-home-v4.zip'              # 7d792ad9…
docker exec php sh -c 'wp ... theme install /pkg/packrivo-home-v4.zip --force --activate'
docker exec php sh -c 'wp ... theme list --fields=name,status,version'    # packrivo-home-v4 active 4.0.3

# 去价：前态快照 → apply → 判据 → 幂等 → 复位
docker cp deploy/verification-29z/scripts/strip-1688-rehearsal.sh php:/tmp/29z-run.sh
docker exec php sh /tmp/29z-run.sh

# 九条判据（干净窗口：复位 → before → apply → compare）
docker cp deploy/verification-29z/scripts/29z-criteria.sh php:/tmp/29z-criteria.sh
docker exec php sh /tmp/run-criteria.sh

# 3b 截图 + 3c 爬取
node deploy/verification-29z/scripts/29z-browser.mjs <evidence-dir>
```
