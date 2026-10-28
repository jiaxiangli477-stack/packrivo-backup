# 27j 本地只读验证

先启动既有 localhost:8081 WordPress 容器和当前 i18n 挂载。本目录使用已安装的 Playwright / Chrome，无需安装依赖。只允许浏览器请求 localhost / 127.0.0.1，截图和输出显式保存到 E:。

```powershell
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\scan.mjs
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\supplement.mjs
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\finalize-inventory.mjs
node E:\破甲工作区\packrivo-backup\scripts\homepage-27j\verify-home.mjs
E:\CodexHome\packages\tavily-cli-0.1.8\Scripts\python.exe E:\破甲工作区\packrivo-backup\scripts\homepage-27j\verify-source.py
```

前三条按顺序完整重跑。`scan` 从修改后的英文 DOM 建立逐页参照，等待 DOMContentLoaded、networkidle（至多 2 秒）及额外 800 毫秒，然后检查 1280 / 375px 的普通状态、展开 FAQ、展开导航。后续状态给现有 MutationObserver 留 250–350 毫秒处理。英文字段必须与对应英文页面参照完全一致，不能把所有拉丁字母都判成英文。统计按页面/语言内不同原文计数，跨状态/位置去重。

文章标题、正文、摘要、目录中抽取的原文章标题、封面标题 alt 排除；文章导航、分类、日期旁的阅读时间、相关产品和 CTA 仍在范围内。主清单只含观察到的剩余英文。价格/尺寸/单位值、纯品牌/联系方式及部分本地语言同形词排除，不改其内容。单条原文同时用于属性和节点时 `kind` 优先 attr；全部位置类型和来源保留于 `scan-occurrences.json`。

`supplement` 不修改路径配置：正式语言文章路径和 `/get-a-quote/` 共 22 次 404，分别用现有 WP 原生 `/{lang}/?p=2937` 和 `/{lang}/?page_id=3220` 补查。每次断言 HTTP 200 以及实际 body ID，确保没有把首页当成文章/报价页。`scan-statistics.json` 保留正式 status 和 fallback 实际 URL；CSV 为补查后的真实页面英文数，不能理解为正式路径已经可达。

`verify-source.py` 固定比较任务起点 dc3cf19，按原 Windows CRLF 形式重建基线，允许四处词语替换和 H1 适配；其他源字节必须保持一致。另检查工作主题、备份源码及 ZIP 的 36 个文件相等。脚本不写生产、不导入数据或字典。
