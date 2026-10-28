# 27e / 27g / 27h / 27i 上线前检查与回滚方案（未执行）

**状态：27i 主页面元数据和按钮字典已完成本地准备，尚未部署。** 包含既有 134 个产品、60 条界面词及本轮 8 个 hub/1 条按钮译文；主字典累计 33 个冲突（27g 的 19 个 + 本轮新增 14 个 title 冲突）按生产优先保留，runtime 新键无冲突。32 个 hub 样本中 27 个严格 dsh 一致，5 个为 inquiry/生产译文保留例外，详见本轮结果。27h 已在本地修复重复 title、RTL skip-link 溢出及 Template 三项，并重新打包子主题；修复没有部署到生产。首页仍为 5/8 张卡片，报价重定向补丁仍待获授权上线时执行。

只读核验：生产主题目录 `packrivo-static-bridge-1.2.38` 存在、`packrivo-static-bridge` 不存在。将来构建子主题包时，`style.css` 的 `Template:` 应为 `packrivo-static-bridge-1.2.38`，并在部署前重新核验。27h 当前源码已改为该值，本地测试已移除临时父主题别名挂载，实际加载同名父主题目录。

2026-10-27 的只读 HTTP 检查显示 `/get-a-quote/` 301 到 `/inquiry/`。生产 MU 文件当前含这条重定向；`27e-quote-redirect.patch` 只移除该路径，其他别名保留。补丁基于只读下载文件的 SHA-256 `ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d`；变更前须重新下载、比对 hash，使用 `git apply --check`，任何不匹配都重新审查。不要在生产直接运行本轮本地测试覆盖文件。

## 获得独立部署授权后的顺序

1. **备份**：先记录当前主题、插件、MU 文件、11 个字典及目标产品的 post_title、post_content 和相关 meta；验证备份可恢复。核对产品和字典范围，保护无关内容。
2. **字典合并**：以部署前新读的生产字典为基线，检查 `i18n-delta/` 仅覆盖经审核键；备份旧文件，检查 text/block/attr 冲突、11 语种完整性及实际页面。27g 增量现覆盖全部 134 页和 60 条界面字符串；具体合并和回滚命令见下节。
3. **插件**：经审核后部署报价插件，核对版本与文件哈希、测试 style/qty/product，保留旧包。
4. **子主题**：使用本目录当前 27i 重打的 `packrivo-home-v4.zip`（保留 27h 修复，增加八个 hub 的元数据读取）；Template 已指向 `packrivo-static-bridge-1.2.38`，部署前仍需核对生产目录没有漂移。测试前台、移动端、产品筛选和博客；保留旧主题包。
5. **MU 补丁**：比对生产基线和补丁，移除 `get-a-quote` 这一项，不动其他重定向；保留并验证原文件恢复方式。
6. **内容**：27g 已审文案通过 `scripts/product-buyers-27g/import.php` 批量应用；执行前以实际生产原值创建新的 rollback-27g.json，不能用本地备份覆盖生产。具体命令见下节。媒体 ID 和附件均为本地记录，不能在生产复用 ID。
7. **清缓存**：按实际站点缓存方案执行；无可用凭据时如实记录，不假定清除成功。
8. **复测**：检查 quote 首步选中与数量、真实价格及订单链路、每种语言 title/H1/meta、正确 canonical/robots、各设备布局、FAQ/schema 一致、全部产品链接/图片。
9. **回滚**：按内容→MU→主题→插件→字典逆序，使用第 1 步快照逐项恢复；恢复后核对输出和状态。只恢复本次记录范围，不覆盖部署后其他工作。

## 27g 内容与字典的具体命令（仅准备，未在生产执行）

下面是取得后续独立部署授权后供操作者核对的命令。当前仓库的 `deploy/rollback-27g.json` 来自 **localhost:8081 的 130 页**，不得当作生产备份。生产第一次应用必须在单独备份目录创建自己的同名文件；脚本会校验站点地址、输入哈希和正文，禁止跨站复用备份。

### 变量和备份

```bash
PKG=/home/web/packrivo-27g-reviewed
WP=/home/web/html/www.packrivo.com/wordpress
SITE=https://www.packrivo.com
BACKUP=/home/web/backups/packrivo-27g-before
mkdir -p "$BACKUP"
cp -a "$WP/wp-content/uploads/packrivo-i18n" "$BACKUP/i18n"
wp --path="$WP" eval-file "$PKG/scripts/hubs-27i/import.php" backup "$PKG" "$SITE" "$BACKUP/rollback-27i.json"
```

`PKG` 必须是审核过的完整包路径；确认 BACKUP 是新的独立目录，保留数据库与主题/MU 原文件备份。操作者使用对目标站点有相应权限的账号。以上路径是未来命令示例，不是本轮上传结果。

### 字典合并、安装与回滚

```bash
node "$PKG/scripts/hubs-27i/merge-i18n.mjs" --root "$PKG" --base "$BACKUP/i18n" --js-base "$BACKUP/i18n" --paths-base "$BACKUP/i18n/paths.json" --metadata-backup "$BACKUP/rollback-27i.json"
# 审阅 i18n-statistics-27i.json 和 i18n-conflicts-27i.json 后，才进行以下安装。
for lang in ar de es fr it ja ko nl pt zh-CN zh-TW; do
  cp "$PKG/deploy/i18n-merged/$lang-dict.json" "$WP/wp-content/uploads/packrivo-i18n/$lang-dict.json"
  cp "$PKG/deploy/i18n-merged/$lang-js.json" "$WP/wp-content/uploads/packrivo-i18n/$lang-js.json"
done
cp "$PKG/deploy/i18n-merged/paths.json" "$WP/wp-content/uploads/packrivo-i18n/paths.json"
```

冲突保留部署时生产基线，不能直接用增量替换完整字典。完整字典与增量在冲突键上有意不同；27i 同时安装保留原值后的 runtime exact 字典及路径表：每语种仅加底部按钮键，路径仅补 /blog/。安装后核对目标文件权限及 11 语种实际输出。

字典回滚命令（使用部署前实际字典备份）：

```bash
for lang in ar de es fr it ja ko nl pt zh-CN zh-TW; do
  cp "$BACKUP/i18n/$lang-dict.json" "$WP/wp-content/uploads/packrivo-i18n/$lang-dict.json"
  cp "$BACKUP/i18n/$lang-js.json" "$WP/wp-content/uploads/packrivo-i18n/$lang-js.json"
done
cp "$BACKUP/i18n/paths.json" "$WP/wp-content/uploads/packrivo-i18n/paths.json"
```

### 内容计划、应用、验证与回滚

生产此前未部署过 27e 的四页，因此完整上线使用 `all134`，它将前四页已审清单与 dsh 的 130 页合为同一备份和事务。**本轮本地仅执行默认的 pending130；all134 仅执行了 plan（0 写入）。**

```bash
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" plan "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" apply "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" verify "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
wp --path="$WP" eval-file "$PKG/scripts/hubs-27i/import.php" plan "$PKG" "$SITE" "$BACKUP/rollback-27i.json"
wp --path="$WP" eval-file "$PKG/scripts/hubs-27i/import.php" apply "$PKG" "$SITE" "$BACKUP/rollback-27i.json"
wp --path="$WP" eval-file "$PKG/scripts/hubs-27i/import.php" verify "$PKG" "$SITE" "$BACKUP/rollback-27i.json"
```

应用前脚本先写回滚文件，后开启数据库事务；要求目标 WordPress 表支持事务。若源文件、旧标题、站点、slug 或正文发生不符合预期的漂移，停止应用并重新审阅，不要删备份强行重跑。重复应用已一致的内容跳过，不覆盖原备份。

回滚先检查，再由操作者决定执行；只恢复本次备份的标题、H1/原内容和所列 meta，不覆盖其他页面：

```bash
wp --path="$WP" eval-file "$PKG/scripts/hubs-27i/import.php" rollback-check "$PKG" "$SITE" "$BACKUP/rollback-27i.json"
wp --path="$WP" eval-file "$PKG/scripts/hubs-27i/import.php" rollback "$PKG" "$SITE" "$BACKUP/rollback-27i.json"
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback-check "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
```

本地回滚命令的原理相同，但必须用本地站点地址和本地 rollback-27g.json，保持 `pending130` 范围。本轮只运行了 rollback-check，没有执行 rollback。

## 27g 本地验证发现的既有代码问题（历史记录，27h 已在本地修复）

以下保留 27g 当时的发现及证据。27h 已按专门任务单仅在子主题中完成 title 和 RTL 修复，当前验收见下一节；生产尚未应用修复。

1. **重复 head title 标签**：134 个页面的浏览器主标题全部与已审字段一致且互不重复，但 133 页实际 HTML 内有两个相同的 head title。父主题 `packrivo_output_page_metadata` 的 title 与子主题核心 renderer 补偿逻辑重叠；阿语等翻译仅处理第一个 title，第二个仍是英文。完整证据见 `verification-27g/english-134.json`、`http-results.json`。这不同于「不同产品使用相同标题」；两项统计分别记录。
2. **RTL 隐藏 skip-link 溢出**：`.pk-skip { left:-9999px }` 在 RTL 页面把滚动宽度扩到 10,374px；可见主内容坐标仍位于 375px 视口内。5 个阿语样本方向为 RTL，但均检测到横向溢出。应后续将该链接改成不扩展文档宽度的可访问隐藏方式，并保留键盘聚焦显示行为；本轮未执行该主题修复。
3. 阿语两张 375px 截图均来自未修改的本地页面；因上述 Chromium RTL 截图偏移，脚本从完整画布提取右侧可见视口，保存 375×900 图像，并在验证 JSON 中记录截图方法。截图不是媒体完整性验收，本地快照的部分原有图片资源未完整同步。

## 27h 当前阻断项状态（本地验证，不是生产部署）

| 项目 | 状态 | 本地证据 |
| --- | --- | --- |
| 重复 / 缺失 title | 已在本地修复 | curl 原始 HTML：134 个英文产品页、15 个语言样本及 7 个其他页面均恰好 1 个 title；149 个已审标题内容匹配 |
| RTL skip-link 横向溢出 | 已在本地修复 | 5 个阿语产品页 + 阿语首页/产品目录，375px 下 scrollWidth 全部为 375；首次 Tab 跳转链接可见 |
| 子主题 Template / 父主题别名依赖 | 已在本地修复 | Template 为 packrivo-static-bridge-1.2.38；本地同名目录实际加载，临时别名挂载已移除；首页及子主题 CSS 均 200 |
| 首页 5/8 张卡片 | 仍保留 | 本轮未增加卡片或修改文案 |
| 报价页旧重定向 | 补丁待上线时执行 | 沿用 27e 的只读核验与补丁，生产未应用；本地仍沿用报价测试专用覆盖，不把本地 200 视为生产已修复 |

子主题包：`deploy/packrivo-home-v4.zip`。36 个文件逐一与工作源核对，0 个差异。SHA-256：

```text
097a76266dff116e6778457aa1a55a35cca2a1efed677301c039bd783d1ccb56
```

验证命令（只访问本地；标题数量以 curl 获取的原始响应统计，不依赖浏览器 DOM）：

```powershell
node E:\破甲工作区\.tmp\packrivo-27h\verify-curl.mjs after
node E:\破甲工作区\.tmp\packrivo-27h\raw-title-tags.mjs
node E:\破甲工作区\.tmp\packrivo-27h\verify-rtl.cjs
node E:\破甲工作区\.tmp\packrivo-27h\verify-runtime.cjs
```

对应脚本副本、改前改后数据、meta/JSON-LD 数量、两张默认视口阿语截图及 Tab 聚焦截图保存在 `deploy/verification-27h/`。本次截图由默认 375×900 视口直接取得，没有裁切大画布。

父主题、插件、MU、字典及已审文案相关 135 个保护文件哈希未变；134 个产品页内容与 meta 的指纹也未变。为移除别名，本地仅重新激活了子主题，使本地 template 配置指向真实父目录；没有修改生产配置。

## 27i：八个主页面及动态按钮

本地 metadata 导入、合并和验证已完成，未部署。27i 的 `backup/apply/verify/rollback-check/rollback` 命令已补入上方内容步骤；主页面原值使用独立 `rollback-27i.json`。仓库内的该文件仅适用于 localhost:8081，生产必须在备份步骤先生成自己的同名文件。

当前子主题包已重打，含 27h 修复及只针对八页的元数据字段读取，未改 H1、正文或 URL。当前 ZIP SHA-256：

```text
1446420DE7263EAFA962859FC0CE9B6B245AF403B540663FD3BBD499DD177F33
```

上节 27h 的 ZIP 哈希是历史包的记录，当前以本节为准。父主题、插件和 MU 源码未变。动态按钮由已有多语言 runtime 的 MutationObserver 翻译，不另加子主题按钮脚本；每语种 `*-js.json` 的 exact 只新增 `Get a quote · MOQ 500`。完整保留原 exact/patterns。生产当前路径表无 `/blog/`，包内路径表仅补该项，其余 144 项不动。

验收边界：32 个 en/ar/de/ja 响应都为一个 title、一个 description，标题禁用词为 0，八页英文 H1 未变；27 个响应与 dsh 完全一致，5 个存在明确保留例外。`/inquiry/` 英文现有描述多一句附件说明，按任务例外保留，使用生产现有完整译文；jewelry de 及 inquiry 的部分 title 译文按生产优先保留。不要把该 5 行冒称为逐字 dsh 一致。冲突及例外详见 27i 结果文件和 `verification-27i/comparison.json`，实际值表供 Claude 复核。

原有剩余项仍然保留：首页 5/8 卡片；报价重定向补丁等获授权上线时执行。本轮没有执行上方生产应用或回滚命令。

## 27j：四处首页术语与 H1 翻译适配

当前子主题 ZIP 已更新，36 个文件与工作源、备份源码逐一一致。SHA-256：`d94407937960f52d0a7a4376dbda28ea0dcbafe5483c156eadd7db2bab242baa`。上文 27h / 27i 的哈希保留为历史记录，当前以本节为准。

仅 front-page.php 的四处指定术语和 H1 适配发生代码变化，经营说法保留。H1 直接调用现有字典查找函数，英文保留强调 span，11 种译文输出完整已审句子。本轮不合并或安装新译文；`dsh-in/untranslated-27j.json` 是后续翻译输入，共 207 个去重英文键。

本地浏览器扫描 7 类页面 × 11 语种：55 个正式路径 200；文章和 /get-a-quote/ 的 22 个正式语言路径仍 404，已用现有原生 WP 查询地址补查同一文档并验证 ID，不代表正式路由修复。父主题、插件、MU、字典未改；详细证据见 verification-27j 和 packrivo-v2/docs/CODEX-RESULT-2026-10-27j.md。

该包没有部署。首页 5/8 卡片、报价重定向补丁、语言路径缺项等原有剩余项继续保留；上线仍需另行授权。
