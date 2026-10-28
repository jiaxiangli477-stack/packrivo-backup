# 28k 合并与本地验证

`merge-i18n.mjs` 使用 27g 的 `mergeKeepingProduction`，读取 `dsh-in/untranslated-27j.json` 的 kind 与逐节点记录，合并 `dsh-out/untranslated-27j.json` 的已审内容。文本使用 block/text 两个匹配桶，属性使用 attr；当同一英文属性还被记录为文字节点时也写入文字桶。原 delta、合并基线的所有旧值、原 runtime exact 和 patterns 保留；冲突输出在 `deploy/i18n-conflicts-28k.json`。

本地执行示例（先保存 27i 基线；输入和输出均在 E:）：

```powershell
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\merge-i18n.mjs --root E:\破甲工作区\packrivo-backup --base E:\破甲工作区\.tmp\packrivo-28k\baseline\i18n-merged --js-base E:\破甲工作区\.tmp\packrivo-28k\baseline\i18n-merged --delta-base E:\破甲工作区\.tmp\packrivo-28k\baseline\i18n-delta
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-merge.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-pattern-runtime.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-nav-runtime.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-node-counts.mjs
node E:\破甲工作区\packrivo-backup\scripts\ui-28k\probe-quote.mjs
E:\破甲工作区\packrivo-backup\scripts\ui-28k\verify-routes.ps1
```

最后一条包含生产网页的只读 GET，其余浏览器脚本拦截非 localhost 网络请求。`verify-node-counts.mjs` 为获取实际改前值，会短暂将本地已绑定的 22 个字典/runtime 文件切回 `baseline/i18n-merged`，再恢复合并后快照并逐个比较 SHA-256；`finally` 也会执行恢复。它统计桌面默认状态的实际可见文字节点，允许相同文字在多个节点分别计数；同形的已审当地词、品牌、价格数字和单位、通用缩写、文章标题和正文排除。参考英文节点来自 27j 的实际页面采集；这是对该参照集的复测，不是对任意未知英文的语言识别。

文章正式语言路径仍 404，因此实际文章内容通过本地 WP 原生查询 `/{lang}/?p=2937` 补查，并断言 body 的 post ID；结果同时记录正式路径 404，不能据此声称路由正常。日期 runtime 使用四个当前文章分类、12 个月份和有/无阅读时间两种形式，共每语种 96 个受限规则，未覆盖新分类；日期形态从已审样本和 Node Intl 月份数据组合。动态菜单映射针对 `nav.js` 的前缀剥离和 Open/Close 拼接，值取现有有效译文，不更改主题或插件代码。
