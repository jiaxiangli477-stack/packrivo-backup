# Codex 结果 · 2026-10-27h

## 结论

三项均已在本地修复并验证：每页单一 title、RTL skip-link 不撑宽且可键盘聚焦、Template 指向真实父主题目录并移除别名挂载。产品代码只改子主题 `functions.php`、`assets/site.css`、`style.css`。没有修改父主题、插件、MU、文案或字典，没有部署或修改 www.packrivo.com。

子主题源码和新包已同步到备份仓库。首页仍为 5/8 张卡片；报价重定向补丁仍待获授权上线时执行，未删去这些剩余项。

## 1. 一个页面一个 title

修复在 `wp` 优先级 99 执行。原生页面、无 Yoast 且父主题元数据 callback 仍存在时，只撤销核心 title callback，不撤销父主题元数据 callback；这样不影响 description 或 JSON-LD。没有父主题/产品工作区 title owner 时保留核心兜底，并覆盖产品目录与首饰总页，避免它们原先的 0 个 title。Yoast 分支不由此逻辑干预。

计数完全来自 `curl.exe` 原始 HTTP 响应，不使用浏览器 DOM。验证脚本在 `deploy/verification-27h/scripts/verify-curl.mjs`，原始改前/改后提取结果在 `curl-before.json`、`curl-after.json`。

运行：

```powershell
node E:\破甲工作区\.tmp\packrivo-27h\verify-curl.mjs before
node E:\破甲工作区\.tmp\packrivo-27h\verify-curl.mjs after
```

改前原样汇总：

```text
products-en: pages=134 titleCounts={"1":1,"2":133}
products-i18n: pages=15 titleCounts={"2":15}
other: pages=7 titleCounts={"0":2,"1":4,"2":1}
```

改后（已移除父主题别名后再次执行）原样汇总：

```text
products-en: pages=134 titleCounts={"1":134}
products-i18n: pages=15 titleCounts={"1":15}
other: pages=7 titleCounts={"1":7}
curl raw HTML checks=156 failures=0
```

134 个英文产品页的 title 与 27g 已审数据一致；15 个语言样本与 dsh 对应译文一致。其余页面检查如下，全部恰好一个 title：

| 页面 | HTTP | title 数 |
| --- | ---: | ---: |
| `/` | 200 | 1 |
| `/products/` | 200 | 1 |
| `/jewelry-boxes/` | 200 | 1 |
| `/blog/` | 200 | 1 |
| `/2026/10/26/jewelry-pouch-box-customer-handoff/` | 200 | 1 |
| `/get-a-quote/` | 200 | 1 |
| `/__27h-missing-page__/` | 404 | 1 |

### Meta description 和 JSON-LD 改前/改后

| 范围 | URL 数 | description 改前 → 改后 | JSON-LD 改前 → 改后 |
| --- | ---: | ---: | ---: |
| 英文产品 | 134 | 134 → 134 | 136 → 136 |
| 5 页 × de/ja/ar | 15 | 15 → 15 | 15 → 15 |
| 其他页面 | 7 | 1 → 1 | 4 → 4 |

逐 URL 比较的变更数为 0；不只是总量相等。目录页原本无 description/JSON-LD，本轮也没有新增或改写。

### 15 个译文 title 的 curl 原始提取

```powershell
node E:\破甲工作区\.tmp\packrivo-27h\raw-title-tags.mjs
```

```text
http://localhost:8081/de/product-pillow-jewelry-box/ count=1
<title>Kissenschachteln für Schmuck | Packrivo</title>
http://localhost:8081/ja/product-pillow-jewelry-box/ count=1
<title>ピローボックス ジュエリー用 | Packrivo</title>
http://localhost:8081/ar/product-pillow-jewelry-box/ count=1
<title>علب وسادة للمجوهرات | Packrivo</title>
http://localhost:8081/de/product-double-wall-tray-lid/ count=1
<title>Wellpappe-Schalen mit Deckel | Packrivo</title>
http://localhost:8081/ja/product-double-wall-tray-lid/ count=1
<title>段ボール トレー箱 蓋付き | Packrivo</title>
http://localhost:8081/ar/product-double-wall-tray-lid/ count=1
<title>صواني مموجة بغطاء | Packrivo</title>
http://localhost:8081/de/product-hinged-nonmagnetic-rigid-box/ count=1
<title>Hartschachteln mit Klappdeckel | Packrivo</title>
http://localhost:8081/ja/product-hinged-nonmagnetic-rigid-box/ count=1
<title>オリジナル 開き蓋 貼り箱 マグネットなし | Packrivo</title>
http://localhost:8081/ar/product-hinged-nonmagnetic-rigid-box/ count=1
<title>علب صلبة بغطاء مفصلي | Packrivo</title>
http://localhost:8081/de/molded-pulp-insert/ count=1
<title>Faserguss-Einlagen | Packrivo</title>
http://localhost:8081/ja/molded-pulp-insert/ count=1
<title>オリジナル パルプモールド 中仕切り | Packrivo</title>
http://localhost:8081/ar/molded-pulp-insert/ count=1
<title>حشوات لب مقولب للتغليف | Packrivo</title>
http://localhost:8081/de/product-hinged-thermoformed-clamshell/ count=1
<title>Klappverpackungen (Clamshell) bedruckt | Packrivo</title>
http://localhost:8081/ja/product-hinged-thermoformed-clamshell/ count=1
<title>オリジナル クラムシェル 印刷パッケージ | Packrivo</title>
http://localhost:8081/ar/product-hinged-thermoformed-clamshell/ count=1
<title>عبوات مفصلية (Clamshell) | Packrivo</title>
```

PHP 分支测试及语法检查原样输出：

```text
PASS: parent title replaces core
PASS: product fallback restored
PASS: directory fallback restored
PASS: non-native core retained
PASS: post core retained
PASS: custom renderer retained
PASS: Yoast core path retained
No syntax errors detected in E:\破甲工作区\packrivo-wp\packrivo-home-v4\functions.php
```

分支测试为隔离 PHP 桩测试，真实 HTTP 结果以上述 curl 为准。

## 2. RTL 和键盘：完成

子主题 `.pk-skip` 改为尺寸/裁剪隐藏，不再使用 left/right 负坐标；聚焦时解除裁剪并使用 `inset-inline-start:12px`。原配色、padding、z-index 保留。

```powershell
node E:\破甲工作区\.tmp\packrivo-27h\verify-rtl.cjs
```

已切换真实父主题后的原样输出：

```text
{"url":"http://localhost:8081/ar/product-pillow-jewelry-box/","status":200,"viewport":375,"scrollWidth":375,"dir":"rtl","direction":"rtl"}
keyboard_skip_link={"active":true,"x":217.328125,"y":12,"width":145.671875,"height":44.796875,"clip":"auto","clipPath":"none","withinViewport":true,"tabs":1}
{"url":"http://localhost:8081/ar/product-double-wall-tray-lid/","status":200,"viewport":375,"scrollWidth":375,"dir":"rtl","direction":"rtl"}
{"url":"http://localhost:8081/ar/product-hinged-nonmagnetic-rigid-box/","status":200,"viewport":375,"scrollWidth":375,"dir":"rtl","direction":"rtl"}
{"url":"http://localhost:8081/ar/molded-pulp-insert/","status":200,"viewport":375,"scrollWidth":375,"dir":"rtl","direction":"rtl"}
{"url":"http://localhost:8081/ar/product-hinged-thermoformed-clamshell/","status":200,"viewport":375,"scrollWidth":375,"dir":"rtl","direction":"rtl"}
{"url":"http://localhost:8081/ar/","status":200,"viewport":375,"scrollWidth":375,"dir":"rtl","direction":"rtl"}
{"url":"http://localhost:8081/ar/products/","status":200,"viewport":375,"scrollWidth":375,"dir":"rtl","direction":"rtl"}
RTL checks=7 failures=0
```

两张阿语 375×900 截图均由 `page.screenshot({path})` 默认视口直接取得；没有 fullPage 大画布提取、裁切、注入样式或改 DOM：

- `deploy/verification-27h/product-pillow-jewelry-box-ar-375.png`
- `deploy/verification-27h/product-double-wall-tray-lid-ar-375.png`
- 首次 Tab 聚焦截图：`deploy/verification-27h/ar-375-skip-link-focus.png`

截图用于 RTL 及焦点检查；本地原有媒体尚未全部同步，未借本轮扩大到媒体修复。

## 3. Template、真实加载及 ZIP：完成

`style.css` 的 Template 为 `packrivo-static-bridge-1.2.38`。本地通过 `switch_theme('packrivo-home-v4')` 更新活动主题的 template 配置，然后从临时 Compose 覆盖中移除了 wp/cli 的父主题别名挂载；没有修改父主题目录或任何父主题文件。

```text
stylesheet=packrivo-home-v4
template=packrivo-static-bridge-1.2.38
parent_directory=/var/www/html/wp-content/themes/packrivo-static-bridge-1.2.38
```

`docker inspect packrivo-live-wp-1` 的 mounts 中不再有 `/var/www/html/wp-content/themes/packrivo-static-bridge` 目标；只有现有 themes 根挂载和工作子主题挂载。首页和实际入队 CSS 验证：

```text
{"home_status":200,"child_css":"http://localhost:8081/wp-content/themes/packrivo-home-v4/assets/site.css?ver=4.0.0","css_status":200,"child_css_matches_source":true,"header_position":"sticky","skip_clip_path":"inset(50%)"}
```

重新打包命令：

```powershell
Compress-Archive -LiteralPath 'E:\破甲工作区\packrivo-wp\packrivo-home-v4' -DestinationPath 'E:\破甲工作区\packrivo-wp\packrivo-home-v4.zip' -Force
Copy-Item -LiteralPath 'E:\破甲工作区\packrivo-wp\packrivo-home-v4.zip' -Destination 'E:\破甲工作区\packrivo-backup\deploy\packrivo-home-v4.zip'
```

ZIP 逐文件核对输出：

```text
zip source files: 36 mismatches: 0
Template: Template: packrivo-static-bridge-1.2.38
zip sha256: 097a76266dff116e6778457aa1a55a35cca2a1efed677301c039bd783d1ccb56
```

## 范围保护和独立审查

父主题、相关插件、MU、字典和已审源文件的 135 个保护文件哈希完全不变：

```text
protected_files=135 changed=0
```

134 个产品页内容及全部 meta 的改前/改后指纹也完全一致：

```text
product_pages=134 content_and_meta_sha256=76e6c833d36af862f7daee6d1de1237357135ffc98c535ba36691ea0ee5db7f1
```

本轮数据库只切换了本地活动主题所需的配置；没有导入或更改页面文案。完整保护清单和指纹文件位于 `deploy/verification-27h/`。

提交前独立只读代码审查未发现可证实的 Critical / Important 问题。审查者检查了三文件差异及既有验证证据，没有另外访问网站/数据库或改文件。

## 4. 上线说明及剩余项

`deploy/README.md` 已把 title、RTL、Template 三项标为「已在本地修复」，列出验证命令和包哈希；保留 27g 历史证据，并明确这些修复没有部署到生产。

以下剩余项保留，没有删去或伪称完成：

- 首页仍为 **5/8 张卡片**；本轮未新增卡片、改文案或字典。
- **报价重定向补丁仍待上线时执行**。本地沿用早先的专用测试覆盖使报价 URL 可访问；不把该本地 200 当成生产重定向已经修复。

只有三个子主题代码文件发生改动；报告、验收证据、部署说明和 ZIP 为交付产物。没有部署或修改 www.packrivo.com。

## 提交和推送

```text
[main cf595fd] Fix child-theme title ownership, RTL skip link and parent Template
 29 files changed, 6556 insertions(+), 12 deletions(-)
To https://github.com/jiaxiangli477-stack/packrivo-backup.git
   17632ae..cf595fd  main -> main
```

主交付提交 `cf595fd`。本回执随后作为文档提交同步推送；工作源、子主题包和备份仓库一致。
