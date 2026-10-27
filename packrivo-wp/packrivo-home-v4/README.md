# Packrivo Home v4（导航 + 首页）

一个子主题，只接管 **header** 和 **首页**，其余页面沿用父主题 `packrivo-static-bridge`。
目的是把现站的导航 bug 修掉、把首页换成能承载转化的结构，而不动 315 篇博客和 11 语种的任何东西。

## 装法（两种，选一）

**A. 子主题（推荐，可回滚）**

1. 把 `packrivo-home-v4/` 整个目录传到 `wp-content/themes/`
2. 后台 → 外观 → 主题 → 启用 `Packrivo Home v4 (child)`
3. 外观 → 菜单 → 新建菜单，挂到 **Primary (v4)**；不挂也能跑，会用内置的 IO 兜底
4. 首页设置：设置 → 阅读 → 首页显示「静态页面」，如果已有首页页面，`front-page.php` 会自动接管

回滚：重新启用父主题即可，子主题目录不用删。

**B. 直接覆盖父主题（快，但先备份）**

```bash
cp wp-content/themes/packrivo-static-bridge/header.php  wp-content/themes/packrivo-static-bridge/header.php.bak
cp wp-content/themes/packrivo-static-bridge/front-page.php wp-content/themes/packrivo-static-bridge/front-page.php.bak
cp packrivo-home-v4/header.php packrivo-home-v4/front-page.php  wp-content/themes/packrivo-static-bridge/
cp -r packrivo-home-v4/assets  wp-content/themes/packrivo-static-bridge/
```

并把 `functions.php` 里的 `pkv4_assets()` / `pkv4_menus()` / `pkv4_product_groups()` 三段合并进父主题 `functions.php`。
注意把 `get_stylesheet_directory_uri()` 换成 `get_template_directory_uri()`。

## 修掉的三个现站问题

| 问题 | 现站实测 | v4 做法 |
|---|---|---|
| 桌面一级导航点击不跳转（只 hover 展开） | 点 Products 后 URL 仍在 `/` | 一级项是真 `<a href>`，旁边独立 caret 按钮控制面板；点标题跳转，点箭头展开 |
| 手机菜单里 Blog 点不动 | 链接被 `<summary>` 盖住，tap 超时 | 自建 drawer，`<button>` + `aria-expanded`，无 `<details>` 覆盖问题 |
| Cookie 横幅压住内容 | `elementFromPoint` 命中 `.pr-consent` | 不在本主题范围内，但 CSS 给了 `--pk-wrap` 安全内边距；建议把横幅改成左下角小条 |

无障碍：`Escape` 关闭、点击外部关闭、`aria-expanded` 同步、焦点可见环、`Skip to content`。

## 首页板块（对应现站真实内容，不是占位）

| 板块 | 数据来源 |
|---|---|
| Hero | 现站 H1「Custom packaging for jewelry, candles & gifts.」+ 真实口径（MOQ 500、7–30 天、8–15% 服务费）|
| 五个品类 | 现站真实分类：jewelry / foldable gift / mailer / labels / inserts |
| 四步流程 | 现站代采模式：Brief → Sample → Produce → Deliver |
| 价格透明 | 阶梯价表（数据可接 `packrivo-quote` 插件的定价类）|
| 材料表 | 板种 / 回收含量 / 用途 |
| 期刊 | `get_posts()` 取最新 3 篇，走现站 315 篇 |
| FAQ | 5 问，同时输出 `FAQPage` 结构化数据 |
| 底部 CTA | 指向 `/get-a-quote/` |

## 改内容的位置

- 品类卡片、四步、FAQ：`front-page.php` 顶部三个数组（`$categories` / `$steps` / `$faq`）
- 导航链接：`header.php` 直接改，或在后台用菜单覆盖
- 产品面板分组：`functions.php` 的 `pkv4_product_groups()`，带 `pkv4_product_groups` filter
- 颜色/字体：`assets/site.css` 顶部 `:root` 变量
- 图片：`assets/img/*.webp`，换成你们的实拍或 AI 概念图即可，文件名不用改

## 验证记录

```
PHP 语法      php -l header.php / front-page.php / functions.php 全部通过
桌面交互      caret 点击 → aria-expanded=true，面板可见；Escape → 关闭
一级导航      点击 title 走 href=/products/（不再是死链）
移动端        390px：Menu 打开 drawer，Products 展开全部子项，无报错
控制台        0 个 page error
```

预览：`preview/index.html`（纯静态，不依赖 WP，双击可看）
