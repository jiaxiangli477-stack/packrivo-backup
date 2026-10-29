# 29x 两份 CSV 核验结论（意图 829）

核验时间 2026-10-29；核验人：29x 执行意图（id 829）。只读核验 + 本地重新生成对比，未写数据库、未改词典与 zip。

## 1. uncategorized-mapping.csv（未分类映射表）

结论：**完整且与库一致，无需补扫。**

- 文件：`deploy/verification-29x/csv/uncategorized-mapping.csv`，142 行 = 表头 + **141 篇数据行**。
- 对账（库）：`wp term list category` → `uncategorized (term 1) count=141`；`wp post list --category__in=1 --post_status=publish --format=ids` → 141 个 ID，与 CSV 的 141 个 ID **逐一对齐（差异 0 / 0）**。
- 对账（导出）：`.tmp/29x/uncat-raw.json` = 141 篇（全部 publish），ID 集合与 CSV 完全一致。
- 5 分类取值合法（即库中 5 个分类的显示名，与 `wp term list category` 一致）：`Structures & fit`=51、`Materials & print`=36、`Samples & approval`=25、`Supplier briefs`=18、`Shipping & rules`=11（合计 141）。
- 每行 Evidence keywords 非空：0 行缺失；无重复 ID。
- 重新生成对比：`node classify-uncat.js uncat-raw.json` 输出与已提交文件 **SHA256 逐字节一致**（F09F2711…），旁证无数据缺失。
- 相关 `uncategorized-mapping-detail.json`（58 359B）同样与重新生成输出 SHA256 一致（508B3065…）。

## 2. fictional-terms.csv（fictional 字眼清单）

结论：**完整、全站扫描覆盖、无截断。**

- 文件：`deploy/verification-29x/csv/fictional-terms.csv`，**2055 行** = 表头 + 2054 数据行（与任务要求 2055 一致）。
- 全站扫描输入 `.tmp/29x/all-raw.json` = **333 篇 publish 文章**，与库 `wp post list --post_type=post --post_status=publish` 计数 **333 完全一致**（扫描覆盖全站，无遗漏）。
- 命中分布：2054 行覆盖 **324 个不同文章 ID**，全部 ∈ 333 篇全站集合（0 个越界）；9 篇文章无命中属正常（正文/摘要不含 5 个目标词）。
- 无截断：末行完整（`…not establish a supplier offer or tested result."`，字段闭合、句号收尾），行解析失败 0。
- 重新生成对比：`node scan-terms.js all-raw.json` 输出与已提交文件 **SHA256 逐字节一致**（9E043352…），旁证无截断、无数据缺失。

## 3. .tmp/29x/classify.err（7MB）判定

结论：**是首轮失败的陈旧产物（PowerShell 显示 + 编码错误），不是数据缺失；无需补扫。**

- 内容性质：开头为 PowerShell 对原生命令 stderr 的包裹头（`node : <anonymous_script>:1 … NativeCommandError`），其后是 UTF-16 编码 `uncat-raw.json` 被按字节回显产生的乱码文本（每字符间插空格的 `"{"I"D…`，PowerShell 把 stderr 按本机编码渲染所致），约占 7MB 的绝大部分。
- 真实错误在文件末尾：`SyntaxError: Unexpected token '', ""[{ "I"... is not valid JSON at JSON.parse (…classify-uncat.js:6:20)`——即首轮运行时 `uncat-raw.json` 还是 UTF-16（BOM `FF FE`），Node 按 UTF-8 读取导致 JSON.parse 失败。
- 修复轨迹：此后导出脚本重跑为 UTF-8（现 `uncat-raw.json` 头字节 `5b7b224944` = `[{"ID`，纯 ASCII/UTF-8），分类脚本重新执行成功；`classify.out` 0 字节同样是那次失败运行的产物。
- 验证：现文件 + 现脚本重新生成与已提交 CSV 逐字节一致（见上），证明数据完整、结论可信。

## 附：证据命令（要点）

```
docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root term list category --fields=term_id,name,slug,count
# … 1 未分类 uncategorized 141 | 9 materials-print 82 | 10 samples-approval 64 | 12 shipping-rules 34 | 11 structures-fit 40 | 13 supplier-briefs 73
docker exec php … wp post list --category__in=1 --post_status=publish --format=ids   # 141 IDs，与 CSV 集合差 0/0
node classify-uncat.js uncat-raw.json <out>  && node scan-terms.js all-raw.json <out>
# 重生成 3 文件与已提交 3 文件 SHA256 全部一致
```
