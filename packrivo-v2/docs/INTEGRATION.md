# 融合与去重（INTEGRATION）

目标：三份产物（复刻站 `packrivo-v2`、WP 插件 `packrivo-quote`、子主题 `packrivo-home-v4`）之间不再各写一份同样的东西。
原则：**谁是源头就留谁，其余全部变成生成物**，生成物带「不要手改」头注释。

## 一、已经收敛的四处

| 重复项 | 之前 | 现在 | 单一来源 |
|---|---|---|---|
| 设计令牌 | v2 `globals.css @theme` + 子主题 `site.css :root` 各写一套色值 | 子主题改为 `@import "tokens.css"`，色值来自生成文件 | `packrivo-v2/app/globals.css` |
| 定价表 | `lib/pricing.ts` + `class-pkq-pricing.php` 常量 + demo 里再抄一份 | PHP 类读 `pricing.json`，demo 与插件默认值同源 | `packrivo-v2/lib/pricing.ts` |
| 品牌字标 | 三处各写 `packrivo` / `Packrivo` | 统一 `Packrivo`（域名邮箱保持小写） | 人工约定，已全站替换 |
| 文档 | 7 份，`comparison-matrix` 与 `current-site-evidence` 重叠，`migration-plan` 与三份 `wp-*` 重叠 | 合并为 `docs/MIGRATION.md`，原件进 `docs/archive/` | 本目录 |

## 二、生成关系

```
packrivo-v2/app/globals.css  ──┐
packrivo-v2/lib/pricing.ts   ──┤
                               └─ node packrivo-shared/build-shared.mjs
                                     ├─ packrivo-shared/tokens.css
                                     │    └─ copy → packrivo-home-v4/assets/tokens.css
                                     ├─ packrivo-shared/pricing.json
                                     │    └─ copy → packrivo-quote/includes/pricing.json
                                     └─ console 报告（数量 + 指纹）
```

改了 `globals.css` 或 `pricing.ts` 之后必须跑一次 `node packrivo-shared/build-shared.mjs`，否则子主题和插件仍是旧值。

## 三、验证（都是跑过的，不是估的）

```bash
# 1. 生成
node packrivo-shared/build-shared.mjs
# → tokens: 24 vars | styles: 9 | materials: 7 | prints: 4 | finishes: 7
# → tiers: 500,1000,2500,5000,10000,25000

# 2. PHP 语法
docker run --rm -v "<plugin>:/app" php:8.2-cli sh -c \
  "php -l /app/packrivo-quote.php; php -l /app/includes/class-pkq-pricing.php; \
   php -l /app/demo/index.php; php -l /app/demo/order.php"
# → 4 个文件全部 No syntax errors

# 3. 定价一致性（PHP 读 JSON vs 复刻站 TS 接口）
node demo/parity.php > php-parity.json
node parity-check.mjs
# → mailer 2.01/2.01 · rigid 4.02/4.02 · carton 1.13/1.13 · pouch 0.58/0.58
# → max delta 0.0000 → PASS

# 4. 配置器端到端（走完五步并落库）
node scripts/wp-widget-e2e.mjs
# → unit $4.25 · total $10,632.52 · ref PKQ-2D5BF · 0 page error
```

## 四、仍然存在的「重复」，是有意的

| 项 | 为什么保留两份 |
|---|---|
| 两套配置器（React / 原生 JS） | 复刻站要 React 生态，WP 侧不能引入构建链。共享的是**定价数据与公式**，不是代码 |
| 两套首页（`app/page.tsx` / `front-page.php`） | 一套是设计原型，一套是上线实现。模板结构一致，内容各自从自己的数据源取 |
| 6 张 `assets/img/*.webp` | WP 不能跨站引用本地文件；换成媒体库实拍时直接覆盖同名文件 |
| 定价常量 fallback | `class-pkq-pricing.php` 里保留一份兜底，防止 JSON 丢失导致白屏；注释已标明不得手改 |

## 五、下一步（部署前）

1. `node packrivo-shared/build-shared.mjs` 再跑一次，确保产物最新
2. 把 `packrivo-quote-1.0.0.zip` 与 `packrivo-home-v4.zip` 上传到测试站
3. 后台「设置 → Packrivo Quote」把基准价改成真实成本（覆盖 JSON 默认值，不改生成物）
4. 用 Claude 复核时，重点看：`packrivo-shared/pricing.json` 与 `lib/pricing.ts` 是否等值、`tokens.css` 是否与 `globals.css` 一致
