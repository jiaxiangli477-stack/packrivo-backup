# 27g 本地导入与字典合并

输入是已审的 `dsh-out/batch-01.json` … `batch-05.json`、`ui-strings.json`。脚本不生成或改写文案。

## 已执行的本地命令

在 `E:\破甲工作区\packrivo-live`，Docker CLI 为 `D:\Docker\App\resources\bin\docker.exe`。沿用 27c 的本地工作主题/插件/父主题别名及报价重定向测试覆盖。

```powershell
$env:PATH='D:\Docker\App\resources\bin;'+$env:PATH
docker compose -f docker-compose.yml -f 'E:\破甲工作区\.tmp\packrivo-27c-compose.yml' run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup:/package' cli wp eval-file /package/scripts/product-buyers-27g/import.php plan /package http://localhost:8081
docker compose -f docker-compose.yml -f 'E:\破甲工作区\.tmp\packrivo-27c-compose.yml' run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup:/package' cli wp eval-file /package/scripts/product-buyers-27g/import.php apply /package http://localhost:8081
docker compose -f docker-compose.yml -f 'E:\破甲工作区\.tmp\packrivo-27c-compose.yml' run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup:/package' cli wp eval-file /package/scripts/product-buyers-27g/import.php verify /package http://localhost:8081
```

`apply` 第一次先以独占创建方式写出 `deploy/rollback-27g.json`，再在数据库事务内更新。只改 post_title、H1 内部文字及指定 SEO/meta 产品名称与简介字段；slug、parent、status 和 H1 以外正文必须保持一致。第二次运行只跳过已一致的记录；不覆盖最初备份。备份带输入文件哈希和站点地址，任何输入/站点/正文漂移会拒绝继续。

`rollback-check` 只核对可回滚性，未执行回滚：

```powershell
docker compose -f docker-compose.yml -f 'E:\破甲工作区\.tmp\packrivo-27c-compose.yml' run --rm --no-deps -v 'E:\破甲工作区\packrivo-backup:/package' cli wp eval-file /package/scripts/product-buyers-27g/import.php rollback-check /package http://localhost:8081
```

## 字典

```powershell
node E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\merge-i18n.mjs --root E:\破甲工作区\packrivo-backup --base E:\破甲工作区\.tmp\packrivo-27e-dictionaries
```

- `prior-i18n-27e.json` 固定保留前四页的既有增量。
- title 用完整 ` | Packrivo` 英文键，H1 放 block/text，description 放 block/attr。
- 60 条界面字符串的分区见 `ui-partitions.json`；图片 alt 放 attr，Search 同时覆盖 placeholder，卡片名称同时覆盖 alt。花括号变量保持原值。
- 完整字典保留生产基线中所有既有分区键值。若同一英文键在生产已有不同译文，也不通过新分区覆盖生产译文；冲突记录在 `deploy/i18n-conflicts-27g.json`。
- 统计同时给出新增「分区键」和新增「英文键」，不把四个分区的重复键计作四个不同英文词。
- `i18n-delta` 保存已审提案，`deploy/i18n-merged` 是应用生产优先冲突规则后的完整文件。两者在冲突项上有意不同。
- 生产基线来自 27e 的只读下载，SHA-256 见 `deploy/i18n-baseline-27g.json`。本轮没有再次连接生产。

## 验证

```powershell
php E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\test-transform.php
node --test E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\test-merge.mjs
node E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\verify-english-http.mjs
node E:\破甲工作区\packrivo-backup\scripts\product-buyers-27g\verify-http.cjs
```

浏览器脚本复用 v2 的 playwright-core/sharp 和本机 Chrome，不安装依赖。临时目录须沿用 E 盘配置。只访问本地页面；可只读加载 www.packrivo.com 的公开图片，其他外部请求被拦截。

当前结果：134 页字段一致、Sourcing 为 0、跨页面重复标题为 0；15 个语言样本字段一致。但发现既有主题问题：133 个英文页面同时输出两个相同 title 标签，阿语页面的负坐标 skip-link 引发横向溢出。脚本明确记录两类事实；`verify-http.cjs` 对横向溢出返回非零。没有修改主题来隐藏失败。

阿语截图为实际未修改页面的可见右侧 375×900 区域。Chromium 在 10,374px RTL 画布上直接 viewport 截图会落到空白区，所以脚本从完整截图提取右侧可见区域；此过程不修改 DOM 或样式。截图及提取方式在 `deploy/verification-27g/http-results.json` 中记录。

生产包可选范围 `all134` 会从既有四页清单加载原文，不重写文案。由于当前本地只导入了新 130 页，all134 本轮仅跑 plan，未写数据库；生产命令和单独备份路径见 deploy README。执行 rollback 必须使用与 apply 相同的范围参数，不能把本地 130 页备份用于生产 134 页。
