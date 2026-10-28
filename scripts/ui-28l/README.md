# 28l 验证说明

脚本基于已有 localhost:8081 容器、已挂载的工作子主题和报价插件。浏览器限制 GET/HEAD；不会点击发送或提交询盘。`verify-inquiry.mjs` 与 `compare-options.mjs` 会只读访问生产询盘页面，其他浏览器验证拦截非本地网络（图片 GET 除外）。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-inquiry.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-inquiry-keyboard.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-layout.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-table.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-languages.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\quote-ui-test.mjs after
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\quote-prices.mjs after
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-engine.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\verify-unmapped.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-inventory.mjs after
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-validate.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-eleven.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-legacy.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\cta-quote-flow.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28l\compare-options.mjs
E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe E:\破甲工作区\packrivo-backup\scripts\ui-28l\build-zips.py
```

`quote-*-before.json`、`cta-before.json`、`table-before-cells.json` 和 `price-function-before.txt` 为改前证据，不要在当前已改环境重新采集并覆盖。`quote-prices.mjs` 调用同目录证据中的 `quote-price.php`，比较 Next.js `priceQuote` 和 PHP 价格实现；`verify-engine.mjs` 对比改前的浏览器 price 函数和三个引擎文件哈希、四组价格以及五步摘要文本。JS 其他位置为无样式产品增加了先选款的保护，因此 JS 全文件哈希有意变化。

`verify-inquiry.mjs` 将生产和本地 form/select/input/textarea 数量分别比较，option 数量单独列示；它不是生产选项的同步脚本。`compare-options.mjs` 验证本地 162 个值是生产 354 个值的子集；生产只读 GET 的结果可能随时间变化。各文件不保存真实询盘或凭据。

本地容器沿用早前已经存在的 27c 测试 MU 覆盖；本轮未应用新的父主题、插件或 MU 补丁。生产英文报价重定向仍须另经授权处理。
