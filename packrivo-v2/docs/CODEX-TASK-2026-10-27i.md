# Codex 任务单 · 2026-10-27i · 导入 8 个主页面的 title / 描述 + 底部按钮译文

**范围很小，一轮做完。** 文案已由 Claude 写好、dsh 翻译，你不改文案。生产只读，不部署。完成后提交到 `E:\破甲工作区\packrivo-backup` 并 push，结果写到 `docs\CODEX-RESULT-2026-10-27i.md`，附命令和原样输出。

## 0. 前置检查（不通过就停下）

- `dsh-out\hubs-27i.json` 存在、能解析，8 个 path × 11 语种齐全，外加 1 条 `ui`。
- 英文 title / description 和 `packrivo-v2\docs\DSH-TASK-2026-10-27i.md` 表格里的定稿**逐字一致**（`/inquiry/` 除外，它保留现有英文）。
- `dsh-out\README.md` 有"27i"一节。

## 1. 为什么改

本地 HTTP 现状（Claude 已核对）：
- 首页 title `Custom Packaging Sourcing for Small Brands`，描述里有 `compare supplier quotations`。这是写给供应商看的，阿语首页 title 也带"توريد（代采）"。
- `/products/`、`/jewelry-boxes/`、`/samples/` **没有 meta description**。
- `/blog/` 的 title 是 `Blog – Packrivo`。
- 阿语产品页底部固定按钮仍是英文 `Get a quote · MOQ 500`。它在页面脚本里用 `a1.textContent=` 写入，多语言插件没有翻译到。

## 2. 导入

- 先查清这 8 个页面的 title / description 各自存在哪里（`_packrivo_document_title`、`_packrivo_description`、Yoast 字段、`native-pages.json`、remediation 代码等）。贴出每页的来源。
- 写一个**可重复运行**的脚本，沿用 27g `import.php` 的做法：
  - 先导出原值到 `deploy\rollback-27i.json`；
  - 只改 title 和 description；
  - **H1、正文、URL 都不动**（首页 H1 `Custom packaging for jewelry, candles & gifts` 必须保持）。
- 如果某页的 title 写死在父主题、插件或 JSON 文件里，不要改那些文件。在结果里列出来，并写明上线时怎么处理，由 Claude 决定。

## 3. 字典

- 8 页 × 11 语种写进 `i18n-delta\<lang>-dict.json`，分区沿用 27g：title 放 `title`，description 放 `block` / `attr`。
- 重新生成 `deploy\i18n-merged\`。规则和 27g 一样：生产已有的译文优先，冲突列出。
- 底部按钮 `Get a quote · MOQ 500`：
  - 先确认多语言插件的前端脚本能不能翻译 `textContent` 写进去的文字。
  - 能的话，只加字典键；
  - 不能的话，在**子主题**里用最小的改动让它可以被翻译（例如输出时就带上可翻译的文字），不改插件和 mu-plugin。

## 4. 验收（curl 原始 HTML）

- 8 个页面 × en + ar / de / ja：每页 1 个 `<title>`，有 meta description，和 dsh 文件一致。贴表格。
- 8 个页面的 title 里，`sourcing`、`توريد`、`Beschaffung`、`代采` 命中 0 次。
- 首页 H1 没变。
- 阿语产品页 375 px：底部按钮显示阿语，`scrollWidth <= 375`，截 1 张图。
- 更新 `deploy\README.md` 的上线顺序：内容一步加上 27i 的执行和回滚命令。
- 提交并 push。不部署，不改 www.packrivo.com。
