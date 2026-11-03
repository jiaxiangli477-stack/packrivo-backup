# 31a 本地彩排与复核说明

- 范围：英文路由、目录链接、博客分页 canonical、sitemap 和已确认的孤岛页处理。未部署、未连接生产、未清 Cloudflare。
- 数据基线：`29y-prod.sql`，导出完成于 2026-10-30 17:45:26 洛杉矶时间（2026-10-31 00:45:26 UTC）；本机 SHA-256 与导出回执一致。上线前须按 RUNBOOK 使用届时最新的生产导出重新彩排。
- 工作区：独立 worktree `E:\破甲工作区\packrivo-backup-31a`，分支 `31a`，从 `1726bb8006180334589375b5f1c082914c970025` 建立；未在 main 工作区切换分支。
- 彩排：新建 `packrivo-31a-db` 独立数据库卷与容器，以及 `packrivo-31a-wp` 容器，WordPress/数据库位于 Docker 内部网络；主机 `127.0.0.1:8081` 由本地转发器访问该实例。未复用 dsh 的容器或卷。
- 当前本地 WordPress 运行文件来自既有快照，不能视为 2026-11-02 线上代码；未加载 i18n 词典。`/zh-CN/about/` 的本地 HTTP 验收因此返回 404，仍须在具备获授权多语言基线的隔离环境重验。
- 本地 `/blog/?pg=2` 的 Yoast `rel=next` 仍指向旧式 `/blog/page/2/`；该旧地址现返回 301。canonical 已改为自身，但此相邻分页标记尚未改写，需在复核中处理。

## 孤岛页

按已确认的 17 页表，五个 `catalog-*` 页保持原状；本次导出含 208 个已发布 catalog 页面，其中 Yoast noindex 元数据为 1 的数量为 0，不能据此认定 30a 已落地。

旧 `/collapsible-magnetic-gift-box/` 已在本地代码中设置到 `/product-collapsible-magnetic-gift-box/` 的 301，并从 Yoast page sitemap 排除。旧快照原来返回 200；线上体检报告记载为 301，属于不同时间基线，不作生产状态推断。

遵照用户要求，复用现有卡片中的文字添加以下链接：

- `/foldable-gift-boxes/` 的 `candles` → `/candle-gift-set-box/`
- `/jewelry-boxes/` 的 `drawer jewelry box` → `/drawer-box-with-ribbon/`
- `/inserts/` 的 `thank-you message` → `/paper-insert-card/`

三页渲染后的可见正文文字与修改前一致。以下八页未发现合适的现有卡片文字，按用户决定保持未完成，不添加可见文案：

`/corrugated-wine-shipper/`、`/eco-kraft-cosmetic-box/`、`/jewelry-pouch-and-box-set/`、`/kraft-stand-up-pouch/`、`/magnetic-book-style-box/`、`/printed-tissue-paper/`、`/soap-box-with-window/`、`/subscription-mailer-with-insert/`。

## 验收输出

以下输出来自 `127.0.0.1:8081`，并非生产验证。`zh-CN` 为本轮真实失败项。

```text
/PRIVACY/ 301 http://localhost:8081/privacy/
/Catalog-Pouch/ 301 http://localhost:8081/catalog-pouch/
/privacy/ 200
/zh-CN/about/ 404
/de/ABOUT/ 404
/blog/page/2/ 301 http://localhost:8081/blog/?pg=2
/this-should-404-xyz123 404
<link rel="canonical" href="http://localhost:8081/blog/?pg=2" />
```

无 JS 的本地抓取输出（脚本先检查 sitemap，再遍历首页可达链接；HTTP 301 跟随至站内目标；缺失 robots 指令按默认允许索引处理）：

```text
SITEMAP_ENGLISH_TOTAL 674
CHECKED 100
CHECKED 200
CHECKED 300
CHECKED 400
CHECKED 500
CHECKED 600
SITEMAP_ANOMALIES 0
REACHABLE 29 FETCHING 1
REACHABLE 231 FETCHING 40
REACHABLE 489 FETCHING 20
REACHABLE 558 FETCHING 13
REACHABLE 618 FETCHING 13
REACHABLE 660 FETCHING 4
REACHABLE 702 FETCHING 4
REACHABLE 734 FETCHING 4
REACHABLE 757 FETCHING 1
CATALOG_UPPERCASE_HREF_COUNT 0
HOME_REACHABLE_COUNT 761
ORPHANS_EXCLUDING_C 8
ORPHAN /corrugated-wine-shipper/
ORPHAN /eco-kraft-cosmetic-box/
ORPHAN /jewelry-pouch-and-box-set/
ORPHAN /kraft-stand-up-pouch/
ORPHAN /magnetic-book-style-box/
ORPHAN /printed-tissue-paper/
ORPHAN /soap-box-with-window/
ORPHAN /subscription-mailer-with-insert/
```

9 项定向 HTTP 测试通过；PHP 静态语法检查通过。针对语言路由改写的独立 PHP 测试和目录链接属性/外部域名边界测试通过。main 状态原始快照位于 `E:\破甲工作区\.tmp\31a-main-status-before.txt`，结束时另行比对。完整本地输出位于 worktree 的 `.rehearsal-31a`，该目录含彩排副本，不纳入提交。
