# Codex 任务单 · 2026-10-28k · 合并 207 条子主题译文

**一轮做完。** 译文已生成，Claude 已审核（207 条 × 11 语种齐全、数字和品牌一致、经营说法译得准确）。你**不改译文**，只合并字典和验证。生产只读，不部署。完成后提交到 `E:\破甲工作区\packrivo-backup` 并 push，结果写到 `docs\CODEX-RESULT-2026-10-28k.md`，附命令和原样输出。

## 0. 输入

- 译文：`dsh-out\untranslated-27j.json`（207 个英文键，每个 11 语种）。它现在还没进 git，本轮一起提交。
- 分区依据：`dsh-in\untranslated-27j.json` 里每条的 `kind`（block / text / attr）。
- 底：27i 之后的 `deploy\i18n-merged\`。规则不变：生产已有不同译文时**保留生产的**，列进冲突清单。

## 1. 合并

- 写进 `i18n-delta\<lang>-dict.json` 和 `deploy\i18n-merged\<lang>-dict.json`，沿用 27g / 27i 的脚本和分区写法。
- 文章卡片上的日期行（例如 `Materials & print · 6 min · Oct 20, 2026`）每篇一个键，换文章就失效。如果多语言插件支持 patterns（27i 里你提到 `packrivo-i18n.php:517–543` 有 exact / patterns），把"分类 · N min · 日期"做成一条 pattern，按语种输出对应日期格式。不支持就照原样逐条加，并在结果里说明。
- 输出统计：语种 × 新增键 × 冲突。

## 2. 验证（浏览器，等前端翻译脚本跑完）

- 页面：首页、`/products/`、`/jewelry-boxes/`、`/blog/`、1 篇文章、1 个产品页，× 11 语种。
- 统计每页**可见的英文文字节点**。排除：Packrivo、价格和单位、DDP / FOB / MOQ 这类通用缩写、文章标题和正文。
- 贴出 改前 → 改后 的表（页面 × 语种）。改后每页应为 0；不为 0 的逐条列出原因。
- 首页 ar 和 zh-CN 375 px 各截 1 张图。

## 3. 记录但不修

本地 `/ar/` 页面上的报价按钮都指向英文的 `/get-a-quote/`。`/ar/get-a-quote/` 在本地和生产都是 404，生产阿语首页的按钮指向 `/ar/inquiry/`。

- 这一轮**不改**，只在 `deploy\README.md` 的上线前事项里写清楚现状。
- 再列一下：报价页如果要做多语言，需要改哪些文件（路径白名单、报价插件里的字符串数量）。站长决定后再做。

## 4. 结束

- 提交并 push。不部署，不改 www.packrivo.com。
