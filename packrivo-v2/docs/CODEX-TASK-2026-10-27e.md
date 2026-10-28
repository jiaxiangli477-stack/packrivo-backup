# Codex 任务单 · 2026-10-27e · 27c 复核后的修正 + 产品页买家词

Claude 复核了 27c（提交到 `4bddcbd`）的代码，并在本地 http://localhost:8081 打开页面实看。27c 主体可以接受，下面这些必须修。按顺序执行，不需要再向我确认方案。规则和 27c 一样：

- 生产站只读。
- 价格只从报价引擎来。
- 每节同步到 `E:\破甲工作区\packrivo-backup`，提交一次，最后 `git push`。
- 结果写到 `docs\CODEX-RESULT-2026-10-27e.md`，附命令和原样输出。

图片标注和"不提 AI"站长已经确认过，照 27c 执行，不要再列为待决定。

## 1. FAQ 里的固定价格是错的（P0）

`front-page.php:38` 写着 "about $0.27 for a small rigid jewelry box at 500 pieces"。可是引擎算出来，9.5×9.5×4 的 rigid 首饰盒 500 件是 **$2.83**，差了 10 倍。站长已决定：**FAQ 可以改，价格必须是引擎算出来的**。

- 这一条的答案改为下面这句。花括号里的变量用 `PKQ_Pricing::price()` 实时算：
  `At 500 pieces, a 9.5×9.5×4 cm rigid jewelry box is about ${rigid500} per unit and a 30×22×12 cm printed mailer box about ${mailer500}. The unit price drops as quantity goes up — the quote builder shows the full curve for your size.`
  - `rigid500`：rigid / rigidboard / full-color / matte / 500 件 / 9.5×9.5×4
  - `mailer500`：mailer / kraft / full-color / matte / 500 件 / 30×22×12
- FAQPage schema 用同一个变量，必须和页面上的文字一致。
- 其余 4 条 FAQ 不动。

验收：
- 贴出页面 HTML 和 JSON-LD 里这条答案的原样文字，两处一致。
- 再用 grep 证明 `front-page.php` 里没有任何 `$` 加数字的价格字面量了。

## 2. 首页其他问题

1. **英雄区的 From 和价格卡互相矛盾：** 统计写 `From $2.48 / unit`，按 500 件算；旁边的价格卡写 `$2.34 per unit at 1,000`，比 "From" 还便宜。把统计的标签从 `From` 改为 `From · 500 pcs`。
2. **样品区的 4 个小格是技术词**（Greyboard 2 mm、Kraft E-flute，这是 27c 任务单写错了）。逐字改为：
   - `Ring & necklace boxes / Rigid, magnetic, drawer`
   - `Jewelry pouches / Velvet, cotton, suede`
   - `Gift boxes / Lid-and-base, window`
   - `Mailer boxes / Printed inside and out`
3. **卡片标题标错了：** 第 2 张卡对应 `product-lid-and-base-jewelry-box`，显示的却是 `Custom Jewelry Boxes`，应该是 `Custom Lid-and-Base Jewelry Boxes`。检查全部卡片标题和对应产品是否一致。
4. **卡片简介是写给供应商看的：** 现在显示的是 `Specify jewelry layout, tray movement and pull detail; include quantity and destination for supplier review.`，买家看不懂，也带不来点击。改法见第 3 节；第 3 节完成后，卡片自动显示新的买家简介。
5. **首页凑满 8 张卡：** 先做第 4 节扩充映射，再从映射里有价格的首饰 / 礼盒产品中补齐。缺封面的，从 `codex-output` 里选同类、并且已经逐张和原图比对通过的图（做法同 27c F 节）。补不满就写明还差什么。
6. 产品页标签 `Reference prices on every page` 和"没有映射就不显示价格"矛盾，改为 `Live prices in the quote builder`。

## 3. 产品页去掉 Sourcing，改成买家词（27b 遗留，P0）

现在 134 个产品页的 title/H1 全是 `Custom Drawer Jewelry Box Sourcing` 这种写法；简介是给供应商的需求说明。买家不会搜 "sourcing"，也带不来询盘。

1. **title / H1**：去掉 `Sourcing`，按买家词改写。规则和 27d 第 3.0 节一致：
   - 用买家修饰词：custom / with logo / wholesale / bulk / for small business
   - 不放技术参数词
   - 不和子品类、首饰总品类页抢主词
   - 例：`Custom Drawer Jewelry Boxes with Logo`、`Wholesale Jewelry Pouches with Logo`、`Custom Printed Mailer Boxes for Small Business`
   - title 加上 ` | Packrivo` 后不超过 60 字符，134 个不许重复
2. **`_packrivo_description`（卡片简介，也是 meta description）**：
   - 首饰、礼盒、mailer、袋子这几组改写成 1–2 句买家文案：它是什么 + 适合谁 / 放什么 + 能印 logo、MOQ 500 + 行动号召
   - 不超过 155 字符，每页唯一
   - 其他组先只去掉 "supplier review" 这类面向供应商的句子
3. **URL 不改。**
4. **11 语种字典（硬规则）：** 改了的 title/H1/描述，同一次提交里补上各语种的当地买家词译文。
   - 先**只读**取得当前生产字典：服务器路径 `/home/web/html/www.packrivo.com/wordpress/wp-content/uploads/packrivo-i18n/`，用 scp/sftp 下载，只读不写。拿不到访问权限的话，以 `E:\破甲工作区\packrivo-handoff\live` 里 2026-10-23 的快照为底，并在结果里标明"以快照为底，上线前需与生产字典合并"。
   - 输出的增量文件放在 `packrivo-backup\i18n-delta\`，按语种分文件，便于上线时合并。
   - 27c 结果里列出的待补译字符串，这一节一并补完。

验收：
- 一张表：slug → 旧 title → 新 title → 新描述，全部 134 行。
- title 去重数为 0。
- 禁用词扫描命中 0 次。
- 字典增量统计：语种 × 键数。
- 任选 3 个页面，用本地 HTTP 贴出 title、H1、meta。

## 4. 扩充引擎映射（只加明确对应的）

下面这些现在是 null，但引擎里有对应结构，加上映射：

| 产品页 | 映射到 |
|---|---|
| `product-jewelry-box-with-insert` | rigid |
| `product-foldable-lid-base-box` | rigid |
| `product-finished-folding-gift-box` | carton |
| `product-folding-gift-box` | carton |
| `product-tuck-end-gift-box` | carton |
| `product-pillow-jewelry-box` | carton |
| `product-mailer-with-insert` | mailer |
| `product-lightweight-mailer` | mailer |

- 其余仍按"引擎没有对应结构就是 null"的原则，**不要**给布袋、标签卷、金属罐、展示架硬套 style。
- 验收：贴出新增映射的 from 价，并任选 2 个，和 node `priceQuote()` 同参数对照。

## 5. 上线前阻断项（写进上线包，不执行）

1. **报价页重定向：** 本地 `packrivo-site-remediation-20261022.php` 把 `/get-a-quote/` 301 到 `/inquiry/`。
   - 只读核对生产上同名文件里有没有这条，`curl -I https://www.packrivo.com/get-a-quote/` 也可以。
   - 在 `packrivo-backup\deploy\` 里准备补丁：只删这一条重定向，附上回滚方法。
2. **父主题目录名：** 子主题声明的是 `packrivo-static-bridge`，本地实际目录叫 `packrivo-static-bridge-1.2.38`。只读核对生产上的实际目录名，在上线包里写明 `Template:` 应该填什么。
3. 在 `packrivo-backup\deploy\README.md` 里列出上线顺序：
   - 备份
   - 字典合并
   - 插件
   - 子主题
   - mu-plugin 补丁
   - 内容（title / 描述）
   - 清缓存
   - 复测清单
   - 回滚
4. **不执行任何一步。**

## 6. 结束

- 结果文件按节写状态。
- 列出"上线前站长要做的事"。
- **不部署生产，不改 www.packrivo.com。**
