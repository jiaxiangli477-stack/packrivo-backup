# DSH-RESULT · 2026-10-29w **A 部分**（生产热修复：`/products/` 模板）

> 授权：负责人本人发出，范围仅限本单 A 部分（只读记录 → 一次 `post meta update` → 五条判据 → 任何 FAIL 回滚并停 → `cache flush` + `transient delete --all` → 记日志并 push）。Cloudflare 缓存由负责人本人清。
> 执行：`ssh netcup` → 容器 `php`。**没读任何凭据文件，没碰其他页面。**
> 结论：**`/products/` 已切到 `template-products-v4.php`，五条判据全部 PASS，未回滚。**

记录已追加到 `deploy/LAUNCH-LOG-2026-10-28.md` 第 **41** 节（提交 `8e12894`）。

## 1 · 动手前的只读取证

| 项 | 实测 |
|---|---|
| 页面 8 | `page` / `products` / `publish` / `Custom Packaging Products` |
| **`_wp_page_template` 原值** | **不存在**（`post meta get` 空、`rc=1`；`--keys` 列表只有表头；meta 总数 10） |
| 模板文件 | `packrivo-home-v4/template-products-v4.php`，7,768 B，`Oct 29 03:43` |
| 生产所有非 default 模板 | **0 条** → 没有第三处遗漏（B 部分对比用） |
| 阅读设置 | `show_on_front=page`、`page_on_front=7`、`page_for_posts=53` |
| 改前 | `/products/`、`/de/products/`、`/ar/products/` 均 200、`page-template-default`、无那句文案 |

**回滚预案**：原值不存在 ⇒ 回滚命令是 `post meta delete 8 _wp_page_template`。

## 2 · 执行

```
post meta update 8 _wp_page_template template-products-v4.php
→ Success: Updated custom field '_wp_page_template'.   rc=0
post meta get 8 _wp_page_template
→ template-products-v4.php                             rc=0
```

## 3 · 五条判据（全 PASS）

源站直连（`curl -sk --resolve www.packrivo.com:443:127.0.0.1`，绕过 CF 看真值）：

| URL | 状态 | body class | 关键文案 |
|---|---|---|---|
| `/products/` | 200 | `page-template-template-products-v4` | 有（1 次） |
| `/de/products/` | 200 | `page-template-template-products-v4` | 该语种翻译文案 |
| `/ar/products/` | 200 | `page-template-template-products-v4` | 同上 |
| `/` | 200 | `page-template-default` | — |
| `/blog/` | 200 | 无 `page-template-*`（`is_home`，正常） | — |
| `/get-a-quote/` | 200 | `page-template-default` | — |

真实 Chrome（headless，playwright-core 驱动本机 Chrome，全部带 `?cb=`）：

| 宽度 | scrollWidth / innerWidth | 越界元素 | 分类 chip | 卡片 | 排序表单 / 搜索框 | 分页 |
|---|---|---|---|---|---|---|
| 375 | **375 / 375** | 0 | 7 | 24 | 1 / 1 | 1 |
| 768 | **768 / 768** | 0 | 7 | 24 | 1 / 1 | 1 |
| 1024 | **1024 / 1024** | 0 | 7 | 24 | 1 / 1 | 1 |

H1 三宽度都是 `Every structure we can source`；de 是 `Alle Verpackungsformen, die wir beschaffen können`、ar 是 `كل التصاميم الهيكلية التي يمكننا توريدها`。
截图与结构化结果：`deploy/verification-29w/evidence/`（`products-375|768|1024.png`、`products-de|ar-1024.png`、`render-check.json`）。

页面结构复核（不是只看一句话）：`pk-catalog-hero` 的 H1 + eyebrow「CATALOGUE · 134 STRUCTURES」、`pk-filterbar` 7 个 `pk-chip`、`form.pk-sort`（`#pk-search` + 排序下拉 + 按钮）、`pk-cards pk-cards-4` 24 张卡（17 张带图）、`pk-pager`。改前的默认模板渲染的是页面正文里那段 legacy HTML（筛选控件挤成文字、图片撑满宽），现在整体被模板接管。

## 4 · 收尾

```
cache flush            → Success: The cache was flushed.     rc=0
transient delete --all → Success: 11 transients deleted.     rc=0
transient list --format=count → 0
```

清完再回读：状态与 class 全部不变。Cloudflare 缓存由负责人本人清。

## 5 · 顺带关掉 §40.3 的缺口 8.4

上一轮记「8.4 未执行：本机没有 playwright」。本轮本机已有 `playwright-core`（`E:\破甲工作区\packrivo-v2\node_modules`）+ 本机 Chrome，于是跑了 6 页 × 3 宽度共 **18 组**：`/`、`/products/`、`/de/`、`/ar/`、`/product-drawer-jewelry-box/`、`/get-a-quote/` —— 全部 `scrollWidth == innerWidth`、越界元素 0、200，**FAIL 数 0**（`evidence/overflow-sweep.json`）。全程只读，没改任何东西。

仍未关的缺口：§40.3 的缺口 2（sticker 合计 479.63、`?product=` 预选）需要点开报价页交互，不在 A 授权范围。

## 6 · 边界

- 生产只改了一处：页面 8 的 `_wp_page_template`；
- 未读 `.cf.env` 或任何凭据文件；未执行 B 部分（RUNBOOK 4.6 / 9.7.5、演练重跑、子主题字体路径与重打包）；
- #56 与 Task 55 保持 paused。

```
工作台 #59 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 已完成
```
