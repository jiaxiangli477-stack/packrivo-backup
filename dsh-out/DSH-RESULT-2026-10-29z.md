# DSH 结果 · 2026-10-29z（生产现状库完整重跑版）

任务单：`packrivo-v2/docs/DSH-TASK-2026-10-29z.md` @ `c62bc88`。
本轮按负责人 2026-10-30 复核意见执行：先修 RUNBOOK（最高优先级）、再按新口径改写判据 4、然后用**生产现状库**从复位开始把 §0 基线 / 九条判据 / 3b / 3c 全部重跑一遍。
生产侧只做只读：`wp db export` 一份 + 三组 `grep`/`DESCRIBE`/`curl`。**没有 import、没有 search-replace、没改任何文件、没装主题插件、没清生产缓存。**

工作台 #63 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 已完成（并行 worker 已停，本轮单线程）

## 0. 状态表

| 项 | 结果 |
|---|---|
| RUNBOOK 修复 | ✅ 从 `c62bc88` 恢复，892 → 1016 行，32 → 39 代码块，0 处 U+FFFD，§5.2 `theme install` 独占一行 |
| 42 个未提交文件的归属 | ✅ 查清：全部来自 ARTEX 并行 worker `work#3`（意图 #864/#867/#868），处置见 §7 |
| `baseline-counts-seed.txt` 乱码 | ✅ 已删除，改为 `baseline-counts-prod.txt`（数字全部来自生产 dump） |
| 新判据（每次提交前跑） | ✅ `check-commit-utf8-29z.py`：G1 UTF-8/U+FFFD、G2 行数代码块不减、G3 §5.2 单独成行、G4 附录 D 哈希对齐 |
| §0 生产只读导出 | ✅ 33,029,963 B，`b8e9429f…3679`，2026-10-30 04:22 UTC |
| §0.3 meta description 第四个来源 | ✅ 已定位：`themes/packrivo-home-v4/functions.php:90` 的 `wpseo_metadesc` 过滤器；实际输出由 Yoast 走 `_yoast_wpseo_metadesc`，见 §2 |
| 九条判据（生产现状库） | ✅ **24 PASS / 0 FAIL** |
| 判据 4 新口径 | ✅ 卡片逐一相同 + `¥`/`\u00a5`/`1688` 归零 + 四张截图（1440/375 改前改后） |
| 3b | ✅ 2/2 PASS（zip/mirror/装好后三处哈希一致；首页 1440 与 375 各一张） |
| 3c | ✅ 主题层 0 死链（本地 4.0.3 实测 0 引用）；生产侧 5 条旧链接 + 1 处字体 404 定位到**生产仍是子主题 4.0.1 且没打字体补丁**，见 §6 |
| 4.0.3 包 | ✅ 未变：791,053 B / 43 条目 / `7d792ad9…945f7` |

## 1. §0 生产只读导出（按授权执行，做完即退）

| 项 | 值 |
|---|---|
| 目录 | `/home/web/backups/packrivo-29z-export-20261030-032248/` |
| 文件 | `29z-prod.sql` |
| 字节 | 33,029,963 |
| sha256 | `b8e9429f1e357ef3f2d9afbd5b962ff9cb2cfdf79e3025ed37722fc5f9e03679`（容器内 / 宿主机落盘 / 本机下载 三处一致） |
| 执行时间 | 2026-10-30 04:22:48 UTC（目录名由 `date -u +%Y%m%d-%H%M%S` 生成） |
| 命令 | `$WPCLI db export /tmp/29z-prod.sql --skip-plugins --skip-themes` → `docker cp php:/tmp/29z-prod.sql <dir>/` → `scp` 回本机 |
| 附带只读拷贝 | `wordpress-seo.tar.gz` 4,119,804 B `28880014b381e71846b54b89fc3080ac8bf529a6527c18e8c672822db83d7dcf`（为了让演练环境能真渲染 meta 标签，把生产插件原样拷过来；生产侧只是 `tar czf` 读） |

授权的四组只读查询全跑了：`grep -rn 'name="description"\|Historical'`（mu-plugins + themes）、`DESCRIBE wp_yoast_indexable`、`curl` catalog 页取 `<head>`、`wp db export`。生产写入类操作一个都没做。

## 2. §0.3 `<meta name="description">` 的第四个来源（结论 + 文件行号）

**结论**：生产 HTML 里的 `<meta name="description">` 和 `<meta property="og:description">` 都由 **wordpress-seo（Yoast）** 渲染，取值来自该页 `wp_postmeta._yoast_wpseo_metadesc`；两处与数据库里的值**逐字相同**。

证据链（三条独立对得上）：

1. 生产 `<head>` 抓下来是
   `<meta name="description" content="Ring Box — Clear Window, Flip Lid, Storage. Historical 1688 reference: ¥0.03. Custom print MOQ 500 per design. Confirm current terms." />`
2. 同一页 `_yoast_wpseo_metadesc` 的值就是这段文字（含 `¥0.03`）。
3. 演练环境把生产 dump 灌进去 + 补上 `wordpress-seo` 之后，渲染出的 head 一模一样。

那个「第四个来源」的候选也查清了，它**不参与** catalog 页：

- `/home/web/html/www.packrivo.com/wordpress/wp-content/themes/packrivo-home-v4/functions.php:90`
  ```php
  add_filter('wpseo_metadesc', function ($description) {
      $id = pkv4_hub_metadata_id();
      $value = $id ? get_post_meta($id, '_packrivo_description', true) : '';
      return is_string($value) && $value !== '' ? $value : $description;
  }, 1100);
  ```
  它只在对象带 `_packrivo_description` 时顶掉 Yoast 的值。生产库 `_packrivo_description` 共 499 条，与 catalog 页交集 **0 条**，含 `1688` **0 条** —— catalog 页不走它。
- 同文件 `:103` 还有一段自己 `echo '<meta name="description" …>'` 的代码，入口条件是 `if (!$id || defined('WPSEO_VERSION')) return;`；生产 `wordpress-seo` 是激活状态，`WPSEO_VERSION` 已定义，这段**永不执行**。
- `wp_yoast_indexable.description` 是**旧的**（189 条还带 `¥`），与 postmeta 不同步 —— 所以改完 postmeta 必须重建或直接覆写 indexable，脚本写的就是那三列。

## 3. §0 基线计数（生产 dump，只读快照库 `29z_base`）

| 项 | 任务单 | 生产实测 |
|---|---|---|
| `post_type=page` + publish + `catalog-*` | 208 | **208** |
| `post_content` 含 `1688` 的 publish 行 | 1 | **209** |
| `post_content` 含 `¥` 的 publish 行 | 209 | **209** |
| `post_content` 含 `detail.1688.com` | — | **1** |
| catalog 页有 `_yoast_wpseo_metadesc` | — | **208** |
| 全库 metadesc 含 `1688`/`¥` | — | **226**（含草稿；publish catalog 部分是 208） |
| `_yoast_wpseo_opengraph-description` 命中 | — | **0** |
| `wp_yoast_indexable.description` 命中 | — | **189**（旧值） |
| publish 总数（page+post） | — | **728** |
| `/products/` 正文 `Listed at` 段数 | — | **158** |

**差异要说清**：任务单写「含 1688 的页 = 1」，生产实测是 209 —— 208 个 catalog 页的规格表里各有一条 `Historical 1688 reference`，加上那 1 篇博客；「1」应该只统计了非 catalog 页。这条写进 `evidence/baseline-counts-prod.txt`，没有为了对齐改数字。

## 4. §1 `strip-1688.php`（生产现状库真跑）

包装器 `deploy/patches-29z/strip-1688.sh --dry-run|--apply`；`wp eval-file` 会把 `--dry-run` 当参数错误拦下，直跑时用 `STRIP1688_DRY=1`。

**真跑汇总（`evidence/strip-1688-apply-prod.log`）**：

```
catalog 页 208 / 正文被改 208 / 删价格行 192 / 删 What this tells you 块 192
对照表价格单元格改链接 222（表头 0 处）   <- 生产表头是 Confirm stock minimum，走兜底 <td> 规则
正文里删掉的价格句子 34 / 正文仍有 1688 痕迹 0 页
/products/ 删 <p> 158 / 博客去链 1
meta 写 208（长度 74-153，>155 的 0 条，去重 208/208）
indexable 更新 208 行 / 改动合计 210
```

**幂等**：紧接着再跑一次，`正文被改的页 0 / 改动合计 0`，`changes: 0`。
**审计**：删掉的 34 句全在 `evidence/strip-1688-audit-provdb.json`（带页面 ID 与原文），可以逐条核对。

本轮相对上一版的改动：加了 `s1688_fix_price_cells_any()` —— 只要 `<td>` 里还有 `¥`/`US$0.0` 就换成 `Get a price →`。上一版按表头 `Lowest tier` 匹配，生产表头写的是 `Confirm stock minimum`，导致 16 个 hub 页残留（`leftover_pages: 16`）；加兜底后 **`leftover_pages: 0`**。

## 5. §2 九条判据：24 PASS / 0 FAIL（判据 4 已按新口径改写）

日志 `evidence/criteria-29z-prod.log`。脚本 `deploy/verification-29z/scripts/29z-criteria.sh`，`before` 取前态、`compare` 逐条判。

| # | 判据 | 结果 |
|---|---|---|
| 1a/1b/1c | 正文 `1688` / `¥` / `detail.1688.com` 归零 | PASS 0 / 0 / 0 |
| 2a | catalog meta 还有价格/来源词 | PASS 0 |
| 2b | catalog meta >155 字符 | PASS 0 |
| 2c | 208 条 meta 两两不同 | PASS 208 / 208 |
| 2d | 都带 `Custom logo printing, MOQ 500 per design. Samples available.` | PASS 208 |
| 2e | og-description 与 metadesc 同步 | PASS 0 处不一致 |
| 3a/3b | ID/slug/status 集合、noindex 集合 | PASS（md5 `0b9937b5…` / `36c1d1f0…` 前后相同） |
| **4a** | `/products/` 产品卡片数量 | **PASS 24 / 24** |
| **4b** | 卡片「链接 + 名称」逐条相同 | **PASS md5 `abfa829e…` 前后相同** |
| **4c/4d/4e** | 渲染里 `¥` / `\u00a5` / `1688` 计数 | **PASS 0 / 0 / 0**（改前 `¥` 0、`\u00a5` 20、`1688` 0） |
| **4f** | 1440 与 375 改前改后四张截图 | **PASS 4 张**（73,287 / 73,287 / 33,178 / 33,178 B） |
| 5a | 被改对象数（208 catalog + `/products/` + 1 博客） | PASS 210 |
| 5b | 集合外被改 | PASS 0 |
| 6a | 非 catalog/products/博客 的 meta 被改 | PASS 0 |
| 6b | meta 被改对象数 | PASS 208（`/products/` 与博客本来没有 metadesc 行，所以不是 210） |
| 7 | indexable 与 postmeta 一致 | PASS 0 处不一致 |
| 8 | 抽查 5 个 catalog 页渲染里 `¥` 总数 | PASS 0 |
| 9a | `/get-a-quote/` 跟随跳转后状态 | PASS 200 |
| 9b | `/products/` 里 `Get a price` 链接 | PASS 51 处 |

为什么换口径：生产模板把 `post_content` 塞进页面 `<script>` 的 JSON，删价格必然改字节（308,947 → 301,693 B，差 7,254 B 正好是 158 个 `<p>`），原来那条「渲染哈希逐字节不变」和「`¥` 归零」互斥。新口径保住了「卡片不能变」，把价格归零单独判。

`/products/` 卡片清单已落盘：`evidence/products-cards-before.txt` / `products-cards-after.txt`（各 1,880 B，md5 相同），渲染快照 `products-before-prod.html`（308,947 B）/ `products-after-prod.html`（301,693 B）。

## 6. §3b 与 §3c

### 3b 首页大图：2/2 PASS

| 物 | zip 内 | mirror 树 | 演练装好后 |
|---|---|---|---|
| `hero.webp` | `6a55801599da73d2bd8148f152f04b469c2bc8076f57b36f1dc64ecc8e623e1e` | 同 | 同 |
| `hero-480.webp` | `266032c7bdfc0f51351bb1e7b0607ca66e07bb95fc7af1c1a9c6ab5075ee839d` | 同 | 同 |

首页 1440×900（260,938 B）与 375×812（103,864 B）各一张，`evidence/home-shots/`，两档 HTTP 200、无横向溢出、hero 加载成功；截图里可见两只米色礼盒 + 棕色缎带捧在手上，alt 是 `Two small cream gift boxes with brown satin ribbon, held in open hands`，全站无 `Supplier product photo`。zip 791,053 B / 43 条目 / `7d792ad9…945f7`，`style.css` 与 `functions.php` 都是 4.0.3。

### 3c 死链 + 字体

**本地（4.0.3 + 29w 字体补丁）**：`/`、`/de/`、`/products/`、`/services/` 四个页面对 `/rigid-gift-boxes/ /resources/ /packaging-examples/ /pricing/ /sustainability/` 的引用全是 **0**；首页字体 URL 指向父主题 `…/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2`，请求 **200 / `font/woff2` / 34,952 B**；`/de/` 渲染出 `Preisrechner`。

本地 60 页爬取（`evidence/crawl-29z.json`）：60 页抓到，11 页 404 同样是多语种博客；站内链接去重 **285** 个（页面渲染更慢，超时上调到 60 s、并发降到 6），非 200 **26** 条，全部是演练环境的缺口、不是死链：

- **24 条 `/wp-content/uploads/...`**（`packrivo-scale-20261004` 18 条、`2026/10` 6 条、`r4/product-images` 1 条）—— 演练没灌媒体库；这些 URL 在生产上都是 200；
- **1 条 `/assets/concept-4-960.webp`** —— 生产 200，演练缺文件，按你的意见本轮不处理；
- **1 条 `/#customization`** —— 我脚本把 `#fragment` 当 URL 请求的锅，不是站上的链接；
- 字体那一行显示 404 是**脚本的已知 bug**（把协议相对路径拼成了 `//nginx/...`）；直接 `curl` 同一个 URL 是 **200 / `font/woff2` / 34,952 B**。

**生产侧 60 页爬取（`evidence/crawl-29z-prod.json`）**：60 页全部抓到，**11 页 404 全是 `/<lang>/2026/09/21/jewelry-box-insert-fit-measurements/`**（多语种博客本来没做）；站内链接去重 **1,069** 个，非 200 **7** 条：

| 非 200 | 状态 | 实际原因 |
|---|---|---|
| `/rigid-gift-boxes/` | 404 | 生产跑的是**子主题 4.0.1** 的导航，49 个页面还带着这条；4.0.3 已删 |
| `/resources/` | 404 | 同上（导航 `Resources` + 面板 `Guides`） |
| `/packaging-examples/` | 404 | 同上（面板里那条 `<li>`） |
| `/pricing/` | 404 | 同上（`Price index`） |
| `/sustainability/` | 404 | 子主题 4.0.1 首页 `Materials and sustainability →`，12 个语种首页都有；4.0.3 已改成 `/customization/` |
| `/wp-content/themes/packrivo-home-v4/packrivo-static/assets/packrivo-inter-latin-v3.woff2` | 404 | **生产字体是坏的**：mu 里用 `get_stylesheet_directory_uri()`，子主题没这份文件；父主题那条是 200。29w 补丁（RUNBOOK §6.5）改成 `get_template_directory_uri()` 即修 |
| `/xmlrpc.php?rsd` | 403 | WP 标准 `rel="EditURI"`，生产禁了 xmlrpc，属正常，不算死链 |

定位证据 `evidence/old-url-sources-prod.json`：每条带命中页面与上下文，例如首页里
`<a class="pk-nav-link" href="https://www.packrivo.com/resources/">Resources</a>`、`<li><a href="https://www.packrivo.com/rigid-gift-boxes/">Rigid gift boxes</a></li>`。

结论：**这 6 处在 4.0.3 + 29w 补丁落地后就没了**（本地已实测 0 引用 + 字体 200）。今天还在，是因为生产还是 4.0.1、也没打字体补丁。

`/assets/concept-*.webp` 按你的意见本轮不处理（生产 200，演练缺文件）；`/get-a-quote/` 继续用，生产直连 200、演练 301→`/inquiry/` 是环境差异。

## 7. 42 个未提交文件：谁写的、怎么处置

`git status` + 文件内容 + 时间戳定位：全部是 ARTEX 并行 worker `work#3` 在 19:40–19:56 写的（在我的提交 `ca2433d` 之后），对应意图 #864（dry-run 记录）、#867（并发写 DSH-RESULT）、#868（哈希锁定）。处置：

| 文件 | 谁写的 | 处置 |
|---|---|---|
| `dsh-out/DSH-RESULT-2026-10-29z.md`（被追加 48 行第二份报告） | worker #867 | **回退**（`git checkout --`），再重写成你现在看的这份 |
| `evidence/3c-pages/*.html`（60 个，含 11 个被改的 products 页） | worker，在 **seed 库**上抓 | **删除**（已被本轮生产库重跑覆盖，留着是两份口径） |
| `evidence/strip-1688-dryrun-audit.json`、`strip-1688-dryrun-raw.out` | worker #864 | **删除**（本轮 `strip-1688-audit-provdb.json` 取代） |
| `deploy/verification-29z/hash-lock-29z.md` | worker #868 | **取值后删除**：字体补丁 `022c5a16…`、打过补丁的 mu `73d43ba0…` 已并入 RUNBOOK §6.5 |
| `deploy/verification-29z/strip-1688-dryrun-2026-10-29z.md` | worker #864 | **删除**（superseded） |
| `evidence/29z-home-shots.json`、`3b-hashes-verify.txt`、`hero-29z-outputs.json`、`screens/*.png`、`scripts/29z-home-shots.mjs`、`3c-linkcrawl.mjs`、`29z-strip-1688-dryrun.sh` | worker | **保留提交**（与我的结论一致，是独立证据，不冲突） |

## 8. RUNBOOK 修复（最高优先级，已落地）

做法：`git show c62bc88:deploy/RUNBOOK-launch.md` 取干净底稿，只用 Python 做三件事，写入用 `io.open(..., encoding='utf-8', newline='')`，全程不碰 `Set-Content`/`Out-File`。脚本 `deploy/verification-29z/scripts/rebuild-runbook-29z.py`（可重跑，自带自检）。

| 指标 | c62bc88 底稿 | 现在 |
|---|---|---|
| 行数 | 892 | **1016** |
| ``` 代码块 | 32 个 | **39 个**（底稿 32 个按顺序全保留，行数逐个相同） |
| U+FFFD | 0 | **0** |
| §5.2 `theme install` | 独占一行 | **独占一行**（第 482 行） |

改动只有三处：

1. 两处 `49ddbe6f…` → `7d792ad9…`、`796,514` → `791,053`（表里一处、§5.2 注释一处）；
2. 新增 **§6.5 字体路径补丁（29w）**：为什么打、补丁与打过补丁的 mu 的 sha256（`022c5a16…` / `73d43ba0…`）、`patch -p1 --dry-run` 先验、判据（`grep PACKRIVO_W2_FONT_URL` + 首页字体 URL + `curl` 200 `font/woff2` 34,952 B）、回滚（`cp -a …orig-29w`）；
3. 末尾 **附录 D · 29z**：D.1 锁定表（zip/脚本/判据脚本/字体补丁 sha256，脚本指纹生成时现算）、D.2 装包、D.3 去价（含 `wp eval-file` 不认 `--dry-run` 的坑与兜底 `<td>` 规则）、D.4 meta 来源结论、D.5 复测（判据 4 新口径）、D.6 回滚。

第 5.2 步现在长这样：

```
479: # 5.2 装 + 启用
480: docker cp "$PKG/packrivo-home-v4.zip" php:"$PKGC/packrivo-home-v4.zip"
481: docker exec php sha256sum "$PKGC/packrivo-home-v4.zip"    # 必须 7D792AD9…
482: $WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
483: $WPCLI theme activate packrivo-home-v4
```

## 9. 提交前门禁（新增，每次提交都跑）

`deploy/verification-29z/scripts/check-commit-utf8-29z.py`，`git add` 之后跑，逐条打印：

- **G1a/G1b**：本次提交里所有 `.md/.php/.json/.csv/.sh/.txt/.html` 严格 UTF-8 解码 + 无 U+FFFD；
- **G2a/G2b**：RUNBOOK 行数与每个 ``` 代码块行数相对 `c62bc88` 只增不减（子序列匹配，允许中间插新块）；
- **G3a/G3b**：§5.2 的 `theme install` 独占一行，且上一行是 `7D792AD9`；
- **G4**：附录 D.1 锁定的 sha256 与磁盘真实文件逐一相同（防「手册写一个版本、仓库里是另一个」）。

## 10. 交付物与哈希

| 物 | 路径 | 字节 | sha256 |
|---|---|---|---|
| 子主题包 4.0.3 | `deploy/packrivo-home-v4.zip` | 791,053 | `7d792ad9f4e63025e2fce8a640856710ede5fe3b925e9bdaa372a6002bb945f7` |
| 去价脚本 | `deploy/patches-29z/strip-1688.php` | 见 RUNBOOK D.1 | 本轮改过（新增 `<td>` 兜底），D.1 已同步 |
| 去价包装器 | `deploy/patches-29z/strip-1688.sh` | 1,730 | `0deea313611ff71a760baae5646bcd0252f1cbad5bdb85d0ab3e2953e112ee1e` |
| 九条判据脚本 | `deploy/verification-29z/scripts/29z-criteria.sh` | 见 RUNBOOK D.1 | 判据 4 已改写 |
| 生产导出记录 | `evidence/prod-export-29z.txt` | — | 见 §1 |
| 基线计数 | `evidence/baseline-counts-prod.txt` | 2,907 | — |
| 真跑日志 | `evidence/strip-1688-apply-prod.log` | 6,204 | — |
| 判据日志 | `evidence/criteria-29z-prod.log` | 4,537 | — |
| 删除句子审计 | `evidence/strip-1688-audit-provdb.json` | 10,451 | — |
| 生产爬取 | `evidence/crawl-29z-prod.json` | 12,941 | — |
| 旧链接来源定位 | `evidence/old-url-sources-prod.json` | 1,367 | — |
| `/products/` 前/后 | `evidence/products-before-prod.html` / `products-after-prod.html` | 308,947 / 301,693 | — |
| 卡片清单 | `evidence/products-cards-before.txt` / `products-cards-after.txt` | 1,880 / 1,880 | — |
| 四张判据截图 | `evidence/products-shots/{before,after}-{1440,375}.png` | — | — |
| 首页两档截图 | `evidence/home-shots/home1440x375-{1440,375}.png` | — | — |

## 11. 还没做 / 需要你拍板

1. 生产上线 4.0.3 + 打 29w 字体补丁 + 跑 RUNBOOK D.3 去价 —— 都没做，等你放行。
2. 3c 的 5 条旧链接与字体 404 是「生产还停在 4.0.1」造成的，本轮按要求只修主题层，没动生产内容。
3. `/assets/concept-*.webp`：按你的意见本轮不处理。
4. 多语种博客 404：既存状态，不在 29z 范围。
5. **没有开始 29y**。
