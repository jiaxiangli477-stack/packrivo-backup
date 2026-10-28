# 27e / 27g 上线前检查与回滚方案（未执行）

**状态：27g 文案导入和字典包已完成本地准备，尚未部署。** 134 个产品的本地字段已一致，60 条界面字符串和 11 语种完整字典已合并；19 个翻译冲突按生产优先保留。上线前仍需处理下述主题目录、报价重定向，以及新发现的重复 title 标签和 RTL skip-link 溢出。首页仍为 5/8 张卡片，本轮不扩展页面设计。

只读核验：生产主题目录 `packrivo-static-bridge-1.2.38` 存在、`packrivo-static-bridge` 不存在。将来构建子主题包时，`style.css` 的 `Template:` 应为 `packrivo-static-bridge-1.2.38`，并在部署前重新核验。当前源码尚未改此值，本地测试通过临时挂载别名工作。

2026-10-27 的只读 HTTP 检查显示 `/get-a-quote/` 301 到 `/inquiry/`。生产 MU 文件当前含这条重定向；`27e-quote-redirect.patch` 只移除该路径，其他别名保留。补丁基于只读下载文件的 SHA-256 `ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d`；变更前须重新下载、比对 hash，使用 `git apply --check`，任何不匹配都重新审查。不要在生产直接运行本轮本地测试覆盖文件。

## 获得独立部署授权后的顺序

1. **备份**：先记录当前主题、插件、MU 文件、11 个字典及目标产品的 post_title、post_content 和相关 meta；验证备份可恢复。核对产品和字典范围，保护无关内容。
2. **字典合并**：以部署前新读的生产字典为基线，检查 `i18n-delta/` 仅覆盖经审核键；备份旧文件，检查 text/block/attr 冲突、11 语种完整性及实际页面。27g 增量现覆盖全部 134 页和 60 条界面字符串；具体合并和回滚命令见下节。
3. **插件**：经审核后部署报价插件，核对版本与文件哈希、测试 style/qty/product，保留旧包。
4. **子主题**：核对父主题真实目录后设置 Template，测试前台、移动端、产品筛选和博客；保留旧主题包。
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
```

`PKG` 必须是审核过的完整包路径；确认 BACKUP 是新的独立目录，保留数据库与主题/MU 原文件备份。操作者使用对目标站点有相应权限的账号。以上路径是未来命令示例，不是本轮上传结果。

### 字典合并、安装与回滚

```bash
node "$PKG/scripts/product-buyers-27g/merge-i18n.mjs" --root "$PKG" --base "$BACKUP/i18n"
# 审阅 i18n-statistics-27g.json 和 i18n-conflicts-27g.json 后，才进行以下安装。
for lang in ar de es fr it ja ko nl pt zh-CN zh-TW; do
  cp "$PKG/deploy/i18n-merged/$lang-dict.json" "$WP/wp-content/uploads/packrivo-i18n/$lang-dict.json"
done
```

冲突保留部署时生产基线，不能直接用增量替换完整字典。完整字典与增量在冲突键上有意不同；原有 paths.json 和运行时文件不在这段安装命令的替换范围内。安装后核对目标文件权限及 11 语种实际输出。

字典回滚命令（使用部署前实际字典备份）：

```bash
for lang in ar de es fr it ja ko nl pt zh-CN zh-TW; do
  cp "$BACKUP/i18n/$lang-dict.json" "$WP/wp-content/uploads/packrivo-i18n/$lang-dict.json"
done
```

### 内容计划、应用、验证与回滚

生产此前未部署过 27e 的四页，因此完整上线使用 `all134`，它将前四页已审清单与 dsh 的 130 页合为同一备份和事务。**本轮本地仅执行默认的 pending130；all134 仅执行了 plan（0 写入）。**

```bash
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" plan "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" apply "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" verify "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
```

应用前脚本先写回滚文件，后开启数据库事务；要求目标 WordPress 表支持事务。若源文件、旧标题、站点、slug 或正文发生不符合预期的漂移，停止应用并重新审阅，不要删备份强行重跑。重复应用已一致的内容跳过，不覆盖原备份。

回滚先检查，再由操作者决定执行；只恢复本次备份的标题、H1/原内容和所列 meta，不覆盖其他页面：

```bash
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback-check "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
wp --path="$WP" eval-file "$PKG/scripts/product-buyers-27g/import.php" rollback "$PKG" "$SITE" "$BACKUP/rollback-27g.json" all134
```

本地回滚命令的原理相同，但必须用本地站点地址和本地 rollback-27g.json，保持 `pending130` 范围。本轮只运行了 rollback-check，没有执行 rollback。

## 27g 本地验证发现的既有代码问题

本轮按范围只导入内容和合并字典，未改主题/MU 代码来隐藏以下问题。这些发现须在真正上线前独立处理：

1. **重复 head title 标签**：134 个页面的浏览器主标题全部与已审字段一致且互不重复，但 133 页实际 HTML 内有两个相同的 head title。父主题 `packrivo_output_page_metadata` 的 title 与子主题核心 renderer 补偿逻辑重叠；阿语等翻译仅处理第一个 title，第二个仍是英文。完整证据见 `verification-27g/english-134.json`、`http-results.json`。这不同于「不同产品使用相同标题」；两项统计分别记录。
2. **RTL 隐藏 skip-link 溢出**：`.pk-skip { left:-9999px }` 在 RTL 页面把滚动宽度扩到 10,374px；可见主内容坐标仍位于 375px 视口内。5 个阿语样本方向为 RTL，但均检测到横向溢出。应后续将该链接改成不扩展文档宽度的可访问隐藏方式，并保留键盘聚焦显示行为；本轮未执行该主题修复。
3. 阿语两张 375px 截图均来自未修改的本地页面；因上述 Chromium RTL 截图偏移，脚本从完整画布提取右侧可见视口，保存 375×900 图像，并在验证 JSON 中记录截图方法。截图不是媒体完整性验收，本地快照的部分原有图片资源未完整同步。
