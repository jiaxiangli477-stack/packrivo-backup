# Packrivo Quote & Order — WordPress 插件

把复刻站的「五步配置器 + 实时报价 + 下单入库」搬进现有 WordPress，不改主题、不动现有页面结构。

- 短代码 `[packrivo_quote]` 即可在任意页面/文章里放一个报价下单器
- 前端实时算价（纯 JS，无依赖、无构建），提交时**服务端用 PHP 重算**，前端数字不作数
- 订单存成 `pkq_order` 自定义文章类型，后台可看详情、改状态、导出 CSV
- 价格、起订量、版费、加急费率、通知邮箱都在「设置 → Packrivo Quote」里改，不用碰代码

## 安装

1. 上传 `packrivo-quote-1.0.0.zip` → 插件 → 安装 → 启用
2. 新建一个页面，插入短代码：

```
[packrivo_quote]
```

可选参数：

```
[packrivo_quote style="rigid" qty="1000"]
```

`style` 支持 `mailer / product / shipping / rigid / carton / pouch / poly / bag / sticker`，`qty` 是初始数量。
3. 到「设置 → Packrivo Quote」把九种结构的基准价改成你们的真实成本价，填通知邮箱。

## 数据与接口

| 项 | 值 |
|---|---|
| 提交接口 | `POST /wp-json/packrivo/v1/order` |
| 试算接口 | `POST /wp-json/packrivo/v1/price`（只算不存） |
| 订单类型 | `pkq_order`（后台菜单「Quote orders」） |
| 导出 | 设置页底部「Download all orders as CSV」（UTF-8 BOM，Excel 直开） |

订单字段：参考号、公司、联系人、邮箱、国家、电话、结构、材料、印刷、工艺、数量、尺寸、加急、单价、总价、版费、备注、状态、来源 IP。

## 计价公式（前后端一致）

```
unit = (基准价 × 材料系数 × 尺寸系数 + 印刷附加 + 工艺附加) × 数量系数 × 加急系数 + 版费 / 数量
```

- 尺寸系数 = `(表面积 / 30×20×10 参考表面积)^0.6`
- 数量系数：500→1.00、1000→0.93、2500→0.85、5000→0.78、10000→0.72、25000→0.66（段内线性插值）
- 版费：印刷 220 + 刀模 120；≥2500 免印刷版费、≥5000 免刀模
- 加急：+22%

**已做一致性验证**：同一组输入，PHP 与 Next 站的 TypeScript 模型输出 4/4 完全一致（最大偏差 0.0000）。改参数时两边都改，否则预览与落单会不一致。

## 安全

- REST 提交校验 `X-WP-Nonce`（`wp_create_nonce('wp_rest')`），并做服务端重算
- 每 IP 每小时 20 次限流（transient 计数）
- 隐藏蜜罐字段 `website`，填了直接拒
- 所有字段 `sanitize_*`，备注 `sanitize_textarea_field`
- 后台操作 `current_user_can('manage_options')` + `check_admin_referer`

## 已知边界

- 不接支付。这是「报价/下单请求」，和你们现在代采模式的询盘一致；要真收款，建议把订单转成 WooCommerce 订单再走支付网关。
- 不发客户确认邮件，除非在设置里勾选（默认只通知内部邮箱）。
- 前端文案目前英文；如需与 11 语种站一致，把 `assets/pkq-quote.js` 里的 `i18n` 换成 `wp_localize` 注入的翻译即可（`render_shortcode()` 里已预留 `i18n` 数组）。
- `demo/` 目录是本地测试用的独立跑法，不参与插件运行，打包时已排除。

## 本地验证记录

```
PHP 语法      php -l ×5 全部通过
价格一致性    4/4 case，PHP vs TS 偏差 0.0000
端到端        Playwright：选 rigid → 24×18×7 → soft-touch → 2,500 →
              提交 → 返回 PKQ-ED156，unit 4.25 / total 10,632.52，orders.jsonl 落库
```

本地跑 demo：

```powershell
docker run --rm -p 8899:8899 -e PHP_CLI_SERVER_WORKERS=4 `
  -v "E:/破甲工作区/packrivo-wp/packrivo-quote:/app" `
  php:8.2-cli php -S 0.0.0.0:8899 -t /app
# 打开 http://127.0.0.1:8899/demo/
```
