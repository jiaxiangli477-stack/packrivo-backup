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
| 4.0.4 包 | ✅ 791,169 B / 43 条目 / `3279dc61…aed6`（首页卡片改成 `Example quote · …`，11 语种词典同步） |
| 附录 D | ✅ 重排成一条从上到下照着敲的顺序：D.0 预检 → D.1 锁定/上传 → D.1b 前态 → D.1c 备份 → D.2 装 4.0.4（含字体补丁）→ D.3 去价 → D.4 indexable → D.5 compare → D.5.5 停→ D.6 回滚 |
| 演练三连跑 | ✅ 正向 24/0 → 回滚四条判据全中 → 再正向 24/0（可重复执行） |

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
| 子主题包 4.0.4 | `deploy/packrivo-home-v4.zip` | 791,169 | `3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6` |
| 字体补丁 PHP 兜底 | `deploy/patches-29w/apply-font-patch.php` | 1,840 | `64c8768e5ba3e00281fda4d5937f8c57a03cf5871818a84c2363095b0dda269b` |
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
## 12. 复核第 2 轮（2026-10-30）：附录 D 重排、子主题 4.0.4、演练三连跑

负责人指出 5 处要改，逐条落地：

### 12.1 D.6 回滚写错了 —— 已改

旧版 `theme activate packrivo-static-bridge-1.2.38` 会把站点切回父主题（整站变成上线前的样子），这是真的错。
新 D.6 不再碰 `theme activate`，改成「目录级回滚 + 激活状态不变」：

1. `rm -rf $WPC/wp-content/themes/packrivo-home-v4`，再用 D.1c 的备份 `docker cp -a` 覆盖回去（slug 不变，仍 active）；
2. 目录哈希核对：`cd 备份目录 && find . -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum`，回滚后再算一次，两侧逐字相同；
3. 字体补丁回滚：`cp -a packrivo-wave2.php.orig-29w packrivo-wave2.php`，哈希回到 `5ab5be3f…`；
4. 最后 `db import` + `cache flush`。

### 12.2 三张表备份不放容器 /tmp —— 已改

D.1c 第 3 步：`db export` 到容器 `/tmp/29z-pre.sql` 后**立刻** `docker cp` 到宿主机 `$BACKUP/29z/`，
记 `29z-pre.bytes` 与 `29z-pre.sha256`，再把容器里那份 `rm -f`。回滚导的是宿主机这份。
演练实测：`29,149,411 B`，`sha256 65240db93303b32ee0a64adef87cff99ac5b6d606d449051e5d9b5b57dd72b02`。

### 12.3 附录 D 重排成一条顺序 —— 已改

现在的顺序（每一步都标了【本机】/【宿主】/【容器】）：

```
D.0   变量与预检（theme list + catalog 208 + 1688 209 + ¥ 209）
D.1   锁定物表（6 个文件，sha256）+ 上传 + 核对
D.1b  前态快照（criteria before + 首页哈希 + /products/ 改前截图）
D.1c  备份三样（4.0.1 主题目录 + mu 原文件 + 三张表，全落宿主机 $BACKUP）
D.2   装 4.0.4（D.2.1 装包 / D.2.2 字体补丁）
D.3   去价：干跑 → 真跑 → 幂等复跑
D.4   重建 indexable + cache flush
D.5   compare（24 条判据）
D.5.5 【停】等负责人跑 cf_purge.py，再线上复测
D.6   回滚（四条判据）
```

`deploy/...` 上传清单写死在 D.1.1：`packrivo-home-v4.zip`、`strip-1688.php`、`strip-1688.sh`、
`packrivo-wave2-font-path.patch`、`apply-font-patch.php`、`29z-criteria.sh`，一共 6 个，全部先 `scp` 到 `$PKG`。

执行中踩到两个坑，已经写进手册：

- `wp db query` 的 SQL 是**位置参数**，加 `-e` 会报 `Too many positional arguments` —— D.4 与 D.6 的 SQL 全部改掉；
- 演练镜像里**没有 `patch`**（`patch: not found`），所以给 §6.5 / D.2.2 加了 PHP 兜底 `apply-font-patch.php`
  （只做那一行替换、幂等、打完校验 `73d43ba0…`）。生产容器有没有 `patch` 未知，手册先试 `patch`、没有就走兜底。

### 12.4 首页说明卡片文案 —— 已改

`packrivo-wp/packrivo-home-v4/front-page.php` 第 77 行：

- 改前：`<p class="pk-mono">Rigid magnetic box · 9.5×9.5×4 cm</p>`
- 改后：`<p class="pk-mono">Example quote · Rigid magnetic box · 9.5×9.5×4 cm</p>`

价格那一段**一个字没动**：仍是 `PKQ_Pricing::price([...9.5×9.5×4, 1000 pcs...])` 算出来的 `$x.xx` + `per unit at 1,000 pieces`。
演练里首页渲染出 `Example quote · Rigid magnetic box · 9.5×9.5×4 cm`，价格块与 `per unit at 1,000 pieces` 都在。

11 个语种词典各加一条（`deploy/i18n-merged/*-dict.json`，锚点 `"Rigid magnetic box · 9.5×9.5×4 cm"` 行之后）：

| 语种 | 译文 |
|---|---|
| de | Beispielangebot · Feste Magnetbox · 9.5×9.5×4 cm |
| fr | Devis indicatif · Boîte rigide magnétique · 9.5×9.5×4 cm |
| es | Presupuesto orientativo · Caja rígida magnética · 9.5×9.5×4 cm |
| it | Preventivo indicativo · Scatola rigida magnetica · 9.5×9.5×4 cm |
| nl | Voorbeeldopgave · Stijve magneetdoos · 9.5×9.5×4 cm |
| pt | Orçamento de exemplo · Caixa rígida magnética · 9.5×9.5×4 cm |
| ar | عرض سعر تقديري · صندوق صلب مغناطيسي · 9.5×9.5×4 سم |
| ja | 参考見積 · マグネット式硬質箱 · 9.5×9.5×4 cm |
| ko | 예시 견적 · 자석 경질 박스 · 9.5×9.5×4 cm |
| zh-CN | 示例报价 · 磁吸硬盒 · 9.5×9.5×4 厘米 |
| zh-TW | 範例報價 · 磁吸硬盒 · 9.5×9.5×4 公分 |

脚本 `deploy/verification-29z/scripts/add-hero-example-quote-i18n.py`（行级插入、不重新序列化，插完 `json.loads` 自检），
实测 11/11 插入、0 跳过。

### 12.5 子主题升 4.0.4 —— 已改

`style.css` 与 `functions.php`（`PKV4_VERSION`）都到 `4.0.4`，重新打包（STAMP 改 2026-10-30）：
**791,169 B / 43 条目（38 文件 + 5 目录）/ `3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6`**。
两处锁定都换了：§5 的包表（`791,169 B` + `3279dc61…`、§5.2 注释改成 `3279DC61…`）与附录 D.1.0 表。

### 12.6 演练三连跑（生产现状库 + 4.0.1 起步）

起点按生产现状摆：`29z-prod.sql` 灌回去、`home/siteurl=http://nginx`、子主题从 git `7fe56ad` 取 **4.0.1** 装进去、
mu 还原成未打补丁的 `5ab5be3f…`。日志：`deploy/verification-29z/evidence/rehearsal-29z-sequence.log`（691 行）。

**① 正向（D.0 → D.5）**

| 步骤 | 结果 |
|---|---|
| D.0 预检 | `packrivo-home-v4 active 4.0.1`、catalog 208、1688 209、¥ 209 |
| D.1b 前态 | 首页哈希 `c0312a45…`，`/products/` 改前截图 24 卡 / 无溢出 |
| D.1c 备份 | 主题目录 38 文件、目录哈希 `c02a9324…`、`Version: 4.0.1`；mu `5ab5be3f…`；三表 29,149,411 B `65240db9…` |
| D.2.1 装包 | 包哈希对得上，`Installed 1 of 1 themes`，`active 4.0.4`，`front-page` 里 `Example quote` 命中 1 处，首页渲染出该文案 |
| D.2.2 字体 | 容器没 `patch` → PHP 兜底 `applied`，mu `73d43ba0…`（与 §6.5 锁一致），首页字体 URL 变成父主题路径、`200 / font/woff2 / 34952` |
| D.3 | 干跑 `changes 210`、真跑 `changes 210`、再跑 `changes 0`（幂等），审计 10,451 B |
| D.4 | indexable 更新 + `mismatch=0` |
| D.5 | **PASS=24 FAIL=0**，四张截图齐 |

**② 回滚（D.6）**

| 判据 | 结果 |
|---|---|
| ① 版本 | `active 4.0.1`，目录哈希 `c02a9324…` 与备份时**逐字相同** |
| ② `¥` / `1688` | 回到 `209` / `209` |
| ③ 首页 HTML | `c0312a45…`，与 `home-before.sha256` **相同** |
| ④ mu 原文件 | `5ab5be3f…`，与备份时相同 |
| 附 | 回滚后子主题字体路径重新 **404 `text/html`** —— 生产那个症状在演练里复现了，正是 29w 补丁要修的东西 |

**③ 再正向（D.2 → D.5）**

装包 `active 4.0.4`、字体 `applied` + `73d43ba0…`、干跑 `210`、真跑 `210`、复跑 `0`、`mismatch=0`、compare **PASS=24 FAIL=0**。
→ 同一套步骤可以重复执行。

### 12.7 提交前门禁

`check-commit-utf8-29z.py` 本轮 **7/7 PASS**（G1a/G1b/G2a/G2b/G3a/G3b/G4）。
G4 特意做成「附录 D.1.0 的 sha256 与磁盘真实文件逐一比对」，这轮就因为改了判据脚本、脚本一改 D.1.0 就对不上，
被它拦了一次，重跑生成器才对齐。

### 12.8 与生产的差异（如实记）

- 演练里的 4.0.1 来自 git `7fe56ad`，**生产那份 4.0.1 的目录文件数可能不同**（生产没跑过 `git archive`）；
  D.1c 备份的是生产现场那份，D.6 回滚也是那份，所以不依赖 git。
- 生产容器的 `patch` 有没有，本轮没验证（没碰生产）。手册写的是「先试 patch、没有就走 PHP 兜底」。
- 演练首页字体那条 404 在打补丁**之后**消失、回滚**之后**复现，与生产现状一致。
## 13. 复核第 3 轮（2026-10-30）：五处「只在演练环境成立」的生产化修正

### 13.1 全部 HTTP 改成直连源站（`$CURL`）

宿主机上直接 `curl https://www.packrivo.com` 会读 Cloudflare 缓存，装完 4.0.4 看不见 `Example quote`、
回滚比对首页哈希也会错。现在 D.0 第 1 步统一钉死：

```bash
SITE=https://www.packrivo.com
CURL="curl -sS --noproxy '*' --resolve www.packrivo.com:443:127.0.0.1"
```

`--noproxy '*'` 是实测加上的：本机 Windows 系统代理 `ProxyEnable=1 / ProxyServer=127.0.0.1:10808`，
curl 会走它并返回 `503 Service Unavailable` + `Proxy-Connection: close`，`--resolve` 完全失效。
**这个坑是 D.0 探测第一次跑就抓到的**，不是猜的。

D.0 第 4 步新增只读探测（不通过就停）：

```bash
$CURL -I "$SITE/" | tee /tmp/29z-probe-head.txt
grep -qi 'cf-ray\|cf-cache-status' /tmp/29z-probe-head.txt && echo FAIL || echo OK
$CURL "$SITE/" -o /tmp/29z-probe1.html
$CURL "$SITE/" -o /tmp/29z-probe2.html
sha256sum /tmp/29z-probe1.html /tmp/29z-probe2.html
grep -c '<html' /tmp/29z-probe1.html
```

⛳ 无 CF 标记 + 两次哈希相同 + `<html>` ≥ 1。附录里配了四种失败对号入座：
代理劫持（→ `--noproxy '*'`，已默认带）、证书主体不符（→ `-k`）、页面含随机串（→ 先 `sed` 去 nonce，
或用 `/products/` 当基准页）、443 没开（→ 改 80）。

改前（D.1b）、装完（D.2.1 文案、D.2.2 字体）、回滚（D.6）四处全部换成 `$CURL`，写法一致；§6.5 的字体检查同步改。

### 13.2 scp 写死路径

`$PKG` 是宿主机变量，本机 shell 里没有，`netcup:$PKG/` 会把文件丢到服务器根目录。D.1.1 六个文件全部写死
`netcup:/root/29z-pkg/`，并在正文注明「D.0 第 2 步的 `mkdir -p "$PKG"` 必须先做完」。

### 13.3 补 `PKGC=/pkg`

D.0 第 1 步加 `PKGC=/pkg`，第 2 步 `docker exec php mkdir -p "$PKGC"`。D.2.1 三条命令恢复成
`docker cp … php:"$PKGC/packrivo-home-v4.zip"` → `sha256sum "$PKGC/…"` → `theme install "$PKGC/…" --force`。
`front-page.php` 的 grep 改成真实代码路径 `$WPC/wp-content/themes/packrivo-home-v4/front-page.php`，
上一版那句「`$PKGC` 是主题目录」的错误说明已删（`$PKGC` 是包目录 `/pkg`，与主题目录不是一个东西）。

### 13.4 截图：base 参数、本机判 4f、容器不再判图

- `29z-products-shots.mjs` 加第 3 个位置参数 base URL；不给就走演练反代（原行为），给了就直接访问。
- 改前截图放 D.1b（本机、`https://www.packrivo.com`）；改后截图移到 **D.5.5 负责人清完缓存之后**。
- 新增 `29z-shots-check.mjs`：四张图齐 + `status 200` + 卡片数与改前一致 + 无横向溢出 + 改后 `¥` 0，
  打印单独一行 `PASS 4f` / `FAIL 4f`，退出码 0/1。
- 容器里 `29z-criteria.sh` 增加 `compare-noshots`：4f 打 **SKIP**（既不算 PASS 也不算 FAIL），
  汇总行变成 `PASS=23 FAIL=0 SKIP=1`；旧版把「容器里没图」记成 FAIL。
- 【本机】那几条 `docker cp … php:` 全部删掉——本机拷图只会进演练容器，生产容器里根本没有。

### 13.5 `cf_purge.py` 路径

D.5.5 改成 `python E:\破甲工作区\i18n\cf_purge.py`（负责人平时那份，10-25 版），去掉 handoff 目录那个旧路径。

### 13.6 演练三轮（29z-prod.sql，按新附录 D）

演练里「源站」= 演练 nginx：宿主机 `--resolve www.packrivo.com:8091:127.0.0.1`，容器内 `/etc/hosts` 把
`www.packrivo.com` 指到 `host.docker.internal`（走宿主机发布的 8091），WP 的 `home/siteurl` 设成
`http://www.packrivo.com:8091`——两侧 Host 相同，和生产「同一域名 + 直连源站」等价。
日志：`deploy/verification-29z/evidence/rehearsal-29z-sequence-v3.log`（495 行）。

| | 结果 |
|---|---|
| D.0 探测 | `200 OK`、无 `cf-ray`/`cf-cache-status`、两次哈希同为 `715c6587…`、`<html` 命中 1 |
| D.0 预检 | `active 4.0.1`、catalog 208、1688 209、¥ 209 |
| D.1b 前态 | 快照 729 页 / 804 meta / 774 index / cards 24 / products 311,426 B；`home-before` = `715c6587…`；改前截图两档 `cards 24`、无溢出 |
| D.1c 备份 | 主题 38 文件 + 目录哈希 `c02a9324…` + `Version: 4.0.1`；mu `5ab5be3f…`；三表 29,149,459 B `a82aa274…` |
| **① 正向 D.2→D.5** | `active 4.0.4`、首页渲染 `Example quote · Rigid magnetic box · 9.5×9.5×4 cm`；字体 `applied`→`73d43ba0…`、`200 / font/woff2 / 34952`；干跑 210 → 真跑 210 → 复跑 0；`mismatch=0`；**PASS=23 FAIL=0 SKIP=1**；本机 **PASS 4f** |
| **② 回滚 D.6** | `active 4.0.1`、目录哈希 `c02a9324…` 一致；`¥=209`、`1688=209`；首页 `715c6587…` 与 `home-before` 相同；mu `5ab5be3f…`；子主题字体路径重新 `404 text/html` |
| **③ 再正向** | `active 4.0.4`、字体 `applied`→`73d43ba0…`、210/210/0、`mismatch=0`、**PASS=23 FAIL=0 SKIP=1**、本机 **PASS 4f** |

`4f` 在容器里恒为 SKIP，靠本机那条补上，合计 **24 PASS / 0 FAIL / 0 SKIP**。

有一处演练观察如实记：`/products/` 两档截图在改前改后字节相同（105,346 / 45,134 B），
因为视口内是 hero + 卡片网格，被删掉的 `Listed at ¥…` 段落在这两档视口之外；视觉对比看 `products-shots/`，
「内容没被动过」由判据 4a/4b（卡片数 24 = 24、链接+名称 md5 相同）保证。