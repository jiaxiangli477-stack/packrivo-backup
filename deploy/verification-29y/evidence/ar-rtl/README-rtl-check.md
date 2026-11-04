# ar RTL（从右往左）渲染抽查报告 · 2026-11-03

## 一、抽查方式（可复现）

用演练环境的 selenium 容器（`29y-sel`，与站点同在一个 docker 网络）里的 headless Chrome 截图：

```sh
google-chrome --headless=new --no-sandbox --disable-gpu --disable-dev-shm-usage \
  --hide-scrollbars --ignore-certificate-errors \
  --host-resolver-rules="MAP www.packrivo.com <web容器IP>" \
  --window-size=1440,2400 --virtual-time-budget=20000 \
  --run-all-compositor-stages-before-draw \
  --screenshot=/tmp/xx.png "https://www.packrivo.com/ar/…"
```

- 窗口 1440×2400，`--virtual-time-budget=20000` 等页面稳定后截全屏；
- 截图原件：`evidence/ar-rtl/`（before/after 成对）
- 三张主抽查页：`/ar/`（首页）、`/ar/product-paper-merchandise-sleeve-bag/`（品牌 + 价格）、`/ar/product-sos-paper-gift-bag/`（长正文 + 表格）
- 另补两张：`/ar/services/`、`/ar/product-regular-slotted-shipping-carton/`

## 二、结论：品牌名与数字夹在阿语句子里，显示顺序正常

| 检查点 | 修后实渲染 | 判定 |
|---|---|---|
| 单价 + 数量 | `للوحدة عند 1,000 قطعة` | 正常：`1,000` 作为 LTR run 嵌在 RTL 句中，数字未被拆散 |
| 卡片起价 | `ابتداءً من $2.83 للوحدة`、`ابتداءً من $1.63 للوحدة` | 正常：`$2.83` 整体左到右显示 |
| 尺寸规格 | `500 قطعة · 22×18×22 سم · طباعة كاملة الألوان` | 正常：`22×18×22` 与 `سم` 边界正确 |
| 品牌名 | `تضع Novolex أكياس البضائع ضمن مجموعة أكياس القاع المقروص لديها…` | 正常：品牌拉丁词作为 LTR run 嵌在阿语句中，语序不乱 |
| 品牌所有格 | 修前 `Paper Mart's صفحة…` / `Novolex's …` → 修后 `خاص بـ Paper Mart` / `Novolex` | 已修：不再出现英文撇号 s |

## 三、发现一处 bidi 视觉错序（主题层，词典改不了 → 记入 31b）

首页统计区（`packrivo-wp/packrivo-home-v4/front-page.php` L107–112，硬编码英文 + PHP 插值价格）：

```
<dt>From · 500 pcs</dt><dd>$… / unit</dd>
<dt>MOQ</dt><dd>500 / design</dd>
<dt>Production</dt><dd>7–30 days</dd>
<dt>Sourcing fee</dt><dd>8–15%</dd>
```

阿拉伯语页面上的实际视觉：

- `7–30 days` → 显示成 **`days 30–7`**
- `8–15%` → 显示成 **`15%–8`**

原因：这两个 dd 是「数字–数字 + 空格 + 拉丁词」的混排串，整段处在 RTL 上下文里，Unicode bidi 以首个强方向字符（拉丁词 `days`）定段方向，于是前面的数字区间被反向排列。这不是翻译缺失，是**没有做方向隔离**（正确做法是给这类 dd 加 `dir="ltr"` / `<bdi>`，或整段改成阿拉伯文标签）。

同一位置还有 `MOQ`、`/ unit`、`/ design` 三个纯英文标签，各语种（ar/ja/ko/nl 全部）都还是英文——因为 dd 里带 PHP 插值价格，词典键无法稳定命中。

**处理**：按既定口径「主题硬编码登记 31b、不动主题」——本批不改主题文件。这一条（统计区 5 个标签 + 2 处 bidi 错序）进 31b 清单。

## 四、before / after 对比（截图 md5）

| 页面 | before | after | 结论 |
|---|---|---|---|
| `/ar/` | `ar-home-before.png` | `ar-home-after.png` | **CHANGED**：hero 价格文案、卡片分类标签变阿语 |
| `/ar/products/` | `ar-products-before.png` | `ar-products-after.png` | IDENTICAL：该页本轮无残留 |
| `/ar/product-sos-paper-gift-bag/` | `ar-sos-bag-before.png` | `ar-sos-bag-after.png` | IDENTICAL：该页扫描未报残留（可见英文只有独立 `The`，已在第三批后核对为 0） |

> 说明：`products` / `sos-bag` 的 before/after 字节相同，是因为它们本来就不在 40 页残留名单里；真正有变化的是 `/ar/`。正文段落的修复另有扫描证据（`29y-ar-final-scan.jsonl` + 基线 `ar-base.jsonl`）。
