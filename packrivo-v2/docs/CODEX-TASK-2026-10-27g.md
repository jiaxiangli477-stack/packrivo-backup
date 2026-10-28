# Codex 任务单 · 2026-10-27g · 导入 dsh 文案 + 合并 11 语种字典

**范围很小，一轮做完。** 文案已经由 dsh 写好，Claude 审核过，**你不需要再写或改任何文案**，只负责导入、合并和验证。

规则：生产只读，不部署。完成后提交到 `E:\破甲工作区\packrivo-backup` 并 push。结果写到 `docs\CODEX-RESULT-2026-10-27g.md`，附命令和原样输出。

## 0. 前置检查（不通过就停下）

```
python -I E:\破甲工作区\packrivo-backup\scripts\dsh-validate.py
```

- 必须输出 `pages done: 130/130` 和 `RESULT PASS`。
- `dsh-out\README.md` 末尾必须有"修正记录"一节（对应 `docs\DSH-FIX-2026-10-27f.md` 的 8 条）。没有的话，说明 dsh 还没改完，**停下**。

## 1. 导入本地页面（130 个）

- 输入：`dsh-out\batch-01.json` … `batch-05.json`。
- 用你在 27e 改前 4 个页面的同一套方法（`scripts\product-buyers-27e\update-local.php` 那一套），写一个**批量、可重复运行**的脚本，更新每个 slug 的：
  - title（包括 Yoast / SEO 插件里的 title，如果有）
  - H1
  - `_packrivo_description` / meta description
- **URL 不改，页面正文不动。**
- 脚本先导出改动前的值，存到 `deploy\rollback-27g.json`，作为回滚依据。

## 2. 合并字典

- 把 130 页 × 11 语种的 title / H1 / description，写进 `i18n-delta\<lang>-dict.json`。
  - 键的分区（`title` / `block` / `text` / `attr`）和 27e 前 4 页的写法一致。
  - title 的键是**带 ` | Packrivo` 的完整英文 title**。
- 把 `dsh-out\ui-strings.json` 的 60 条，按插件的匹配方式放进对应分区。界面文字一般放 `text` 或 `block`，alt / placeholder 放 `attr`。
- 以 27e 里只读拿到的**生产字典**为底，生成一份**合并后的完整字典**，放到 `deploy\i18n-merged\<lang>-dict.json`。
  - 同一个键在生产字典里已经有不同译文的，**保留生产的**，并列进冲突清单。
- 输出一张统计表：语种 × 新增键数 × 冲突数。

## 3. 本地验证

1. 全部 134 个产品页：
   - title 含 `Sourcing` 的数量为 0
   - title 重复数为 0
   - 每页 title 和 dsh 文件逐条一致（贴脚本输出的比对结果）
2. 任选 5 个页面 × 3 个语种（de / ja / ar），用本地 HTTP 打开，贴出 `<title>`、H1、meta description，证明译文生效。插件需要本地字典的话，就把 `deploy\i18n-merged` 挂载到本地的 uploads 字典路径。
3. 抽 2 个页面截图：375 px 宽，ar 语种（检查右到左排版）。

## 4. 上线包

在 `deploy\README.md` 的上线顺序里，"内容"和"字典合并"两步补上：

- 具体命令
- 回滚命令（用 `rollback-27g.json`）

**不执行。**

## 5. 结束

- 提交并 push。
- 结果文件写清楚每一节的状态。
- 不部署，不改 www.packrivo.com。
