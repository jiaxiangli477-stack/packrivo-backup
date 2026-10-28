# Codex 任务单 · 2026-10-27h · 修 2 个上线阻断项（重复 title、阿语横向溢出）+ Template

**范围很小，一轮做完。** 只改子主题 `E:\破甲工作区\packrivo-wp\packrivo-home-v4`，不改父主题、不改插件、不改 mu-plugin、不改文案和字典。

规则：生产只读，不部署。完成后同步到 `E:\破甲工作区\packrivo-backup` 并 push，结果写到 `docs\CODEX-RESULT-2026-10-27h.md`，附命令和原样输出。

## 1. 每页只能有一个 `<title>`

**现象（Claude 已本地复现）：**

```
curl -s http://localhost:8081/ar/product-pillow-jewelry-box/ | grep -o '<title>[^<]*</title>'
<title>علب وسادة للمجوهرات | Packrivo</title>
<title>Custom Pillow Jewelry Boxes | Packrivo</title>
```

**原因：**
- 子主题 `functions.php` 里 `add_theme_support('title-tag')`，于是 WP 核心 `_wp_render_title_tag`（wp_head 优先级 1）会输出一个 title。
- 父主题 `packrivo_output_page_metadata`（wp_head 优先级 2）在原生页面、没有 Yoast 时也会 echo 一个 title。
- 多语言插件 `packrivo-i18n.php` 第 433 行替换 `<title>` 时 `limit=1`，只翻译**第一个**。所以第二个永远是英文，搜索引擎在非英文页上会看到英文标题。

**要求：**
- 在子主题里处理，让**每个页面只输出一个 `<title>`**。
- 建议做法：在 `wp` 钩子里，判断父主题这一页会自己输出 title（`is_singular('page')`、`_packrivo_native_page === '1'`、`!defined('WPSEO_VERSION')`、`has_action('wp_head','packrivo_output_page_metadata')`）。满足时 `remove_action('wp_head','_wp_render_title_tag',1)`。
- 已有的那段"两个渲染器都不在时补回核心 title"的逻辑要保留，不能出现 0 个 title 的页面。
- 你有更干净的做法也可以，但不要改父主题、插件或 mu-plugin。

**验收（用 curl 读原始 HTML，不要用浏览器 DOM）：**
- 134 个产品页 × en，每页 `<title>` 数 = 1，内容和 27g 已审清单一致。
- 另外抽这些页面，每页 title 数 = 1：
  - 首页、`/products/`、`/jewelry-boxes/`、博客列表、任意 1 篇文章、`/get-a-quote/`、一个 404
- 27g 的 5 页 × de / ja / ar 重跑一遍，title 数 = 1 且是译文。
- meta description 和 JSON-LD 数量不变（改前改后各数一次，贴对比）。

## 2. 阿语（RTL）横向溢出

**原因：** `assets/site.css` 里 `.pk-skip { position:absolute; left:-9999px }`。在 RTL 页面上，它把文档宽度撑到 10374 px。

**要求：** 改成不依赖 left/right 的隐藏写法，例如：

```css
.pk-skip {
  position: absolute; top: 0;
  width: 1px; height: 1px; overflow: hidden;
  clip: rect(0 0 0 0); clip-path: inset(50%); white-space: nowrap;
  /* 其余配色、padding、z-index 保留 */
}
.pk-skip:focus {
  width: auto; height: auto; overflow: visible;
  clip: auto; clip-path: none;
  inset-inline-start: 12px; top: 12px;
}
```

**验收：**
- 27g 那 5 个阿语页在 375 px 下 `document.documentElement.scrollWidth <= 375`。
- 首页 ar 和 `/products/` ar 也一样。
- 按 Tab 时，跳转链接在可见区域内出现，截图 1 张。
- 重新截 2 张阿语 375 px 截图，**用默认视口直接截**，不再从大画布裁右侧。

## 3. 子主题 Template

- 27e 已只读确认生产父主题目录是 `packrivo-static-bridge-1.2.38`。
- 把子主题 `style.css` 的 `Template:` 改成 `packrivo-static-bridge-1.2.38`。
- 去掉本地测试用的临时别名挂载，确认本地不靠别名也能正常加载（首页 200，子主题样式生效）。
- 重新打包 `packrivo-home-v4.zip`，并放进 `packrivo-backup\deploy\`。

## 4. 结束

- 更新 `deploy\README.md` 的阻断清单：这 3 项标为已在本地修复，并写明验证命令。
- 剩下的阻断项照实列出，不要删：首页 5/8 卡片、报价重定向补丁（等上线时执行）。
- 提交并 push。不部署，不改 www.packrivo.com。
