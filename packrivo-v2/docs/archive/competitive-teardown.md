# 六站拆解 → Packrivo v2 的取舍

数据来源：`research/crawl.mjs` 在 2026-10-25 抓取六个站点首页/品类页的原始 HTML（`research/*.html`）与结构摘要（`research/sites.json`）。下表里的判断都基于抓取结果，不是印象。

---

## 1. 逐站结论

### Packlane — 配置器体验的标杆
- H1「Create custom packaging and boxes」，首屏直接给 8 个结构入口 + 「GET STARTED NOW」。
- 强项：**结构 → 尺寸 → 数量 → 即时价**的线性流程；账号体系有 Saved Designs / My Orders / My Quotes；FAQ 与「How it works」并列在首屏下方。
- 弱项：报价要进账号才能保存；价格对未登录用户不透明。
- 抓到的信任要素：7–15 个工作日生产、低起订、Econoflex 软包装线。

### Arka — 价格透明 + 平台集成
- H1「Eco-Friendly Custom Packaging for Your Brand」，产品卡直接标 **$1.08/unit、$2.83/unit**，把「单价」当货架语言。
- 强项：单价前置、7–10 天生产、免费样品、Shopify / BigCommerce / Square 集成、1,000,000+ 商家背书、评价组件、行业入口。
- 弱项：品类页信息密度高，首屏 CTA 与导航竞争。

### Packhelp — 编辑器 + 内容厚度
- H1「Custom packaging designed to fit your business」，案例（customer stories）占首屏权重。
- 强项：在线编辑器（Design in Editor）、从试单到批量的 MOQ 分层、案例与行业内容成体系。
- 弱项：首页把「设计工具」和「买包装」两条动线混在一起，新用户要在两个 CTA 之间做选择。

### noissue — 可持续叙事
- 站点是 JS 渲染（抓取时 h1/h2 为空，正文靠客户端水合），SEO 上靠 sitemap 与大量落地页补。
- 强项：**材料即卖点**（再生纸、堆肥、FSC），定制贴纸/胶带/ Tissue 的低门槛组合，品牌调性统一。
- 弱项：技术层面对爬虫不友好；价格不前置。

### PakFactory — 结构工程与「选项目录」
- H1「Stunning custom folding carton packaging」，把 **材料 / 涂层 / 印刷 / 纸重 / 特殊工艺** 做成并列的 option library。
- 强项：结构库 + 工艺库的深度、行业页齐全、3,000+ 品牌、样品先行（Get a Sample）、Instagram 作品墙。
- 弱项：信息架构偏 B2B 老派，移动端密度高。

### PackagingBee — 品类 SEO 的极端打法
- 单页覆盖食包全品类，H2/H3 密集铺关键词（37 个 h2），面包屑 + 相关品类 + 报价区块。
- 强项：**一个品类一页**的长尾覆盖、Free shipping in USA、Beat My Quote、博客导流。
- 弱项：文案重复、设计陈旧、几乎没有交互式报价。

---

## 2. 取哪些、怎么落地

| 能力 | 来源 | 在本站的实现 |
|---|---|---|
| 即时报价，价格随配置实时变 | Packlane | `components/QuoteBuilder.tsx` + `lib/pricing.ts`，右侧 sticky 面板同步单价/总价/阶梯表 |
| 单价前置，商品卡就报「from $X」 | Arka | `lib/catalog.ts` 用同一套模型在 2,500 件档算 from 价，`ProductCard` 展示 |
| 免费/低价样品盒作为转化钩子 | Arka、PakFactory | `/samples` + `SampleForm`，写入 leads（type=sample-kit），后台可见 |
| 结构库 + 工艺库分层 | PakFactory | `data/products.ts` 的 stocks / finishes / sizes 三组字段，详情页三栏并列 |
| 行业方案页 | Arka、PakFactory | `/industries` + 8 个详情页，每页带「通常哪里会出问题」而不是泛泛卖点 |
| 案例带数字 | Packhelp | `/work` 4 个案例，指标是破損率/运费/单均包装成本，不是「客户很满意」 |
| 可持续材料矩阵 | noissue | `/sustainability` 八种基材对照表，含「代价」列与认证口径 |
| 长尾品类 SEO | PackagingBee | 12 个结构页 + 8 个行业页 + 6 篇指南，`sitemap.ts` 全量输出，JSON-LD Organization/Article |
| 报价后账号/存档 | Packlane | 不做账号，改成**报价单永久链接** `/quote/[id]`，可打印可转发，少一层注册摩擦 |
| 编辑器 | Packhelp | 不做完整编辑器，改为**参数化 SVG 实时预览**（结构/承印色/品牌字样即时变化），把「看效果」的成本降到 0 |
| 平台集成 | Arka | 预留 `/api/quotes` 作为集成入口，Shopify App 可直接 POST；未接真实店铺 |
| 信任背书 | Arka、PakFactory | 首页评价带具体数字、页脚认证徽标、Hero 侧栏「Live configurator preview」 |

## 3. 商品体系：从「抄品类」改成「自己成系列」

六站的品类重叠度很高（邮寄盒、瓦楞运输盒、精装盒、折叠彩盒、袋、贴纸），但组织方式不同：Packlane 按结构、Arka 按用途、PakFactory 按「产品线 + 工艺库」、PackagingBee 按单品类堆页面。直接合并会得到一张 400 项的目录，没有任何识别度。

做法是把覆盖面重新切成六个自有系列，每个系列内部解决一类约束：

| 系列 | 解决的约束 | SKU 数 | 对标覆盖 |
|---|---|---|---|
| Ridge | 运输损坏、抛重、开箱速度 | 4 | Packlane 邮寄盒/运输盒、Arka 运输盒与 poly mailer |
| Halo | 礼盒档次、开合手感、内衬 | 4 | Packlane 精装、PakFactory rigid、noissue 礼盒 |
| Fold | 零售货架、开窗、手提 | 4 | PakFactory 折叠纸盒、PackagingBee 食品盒 |
| Seal | 阻隔、脱气、货架站立 | 2 | PakFactory pouch、noissue 食品包装 |
| Loop | 门店拎走、复用曝光 | 2 | PackagingBee 纸袋、noissue 袋类 |
| Mark | 最低成本的品牌触点 | 2 | noissue 贴纸、Packlane 贴纸 |

命名不用竞品的「Custom Mailer Box」式品类名，而是「系列 + 结构」：Ridge roll-end mailer、Halo magnetic box、Fold gable box。好处是同一结构可以有多个变体（Ridge 既有瓦楞也有气泡袋替代），而用户仍能按结构搜索到。

### 价格怎么对齐的

抓取到的公开价作为锚点：

| 参照 | 公开价 | 我们的落点（2,500 件档） |
|---|---|---|
| Arka 定制运输盒 | US$1.08/件起 | Ridge shipper US$1.44 |
| Arka poly mailer | US$2.83/件 | Ridge poly US$0.69 |
| Arka 低量定制运输盒 | US$7.49/件 | 500 件档 Ridge shipper US$2.31 |
| Packrivo 现有口径 | US$0.54 @1,000 件 | Fold tuck-end US$0.58 @1,000 |
| Packlane | 低起订、7–15 天 | MOQ 500、10–14 天 |

我们的数字普遍低一档，原因是模型里没有把「打样费 + 设计服务」摊进单价，这部分在报价流程里单独出现（setup 220+120，≥2,500 件免）。这是有意的：让单价可比，增值服务单独计价。

### 起订量与档位的处理

- 每个 SKU 有自己的 MOQ（贴纸 1,000、卷标 5,000、其余 500）。
- 商品卡「from $X」取 `max(2,500, MOQ)` 档，低于 MOQ 的档位在 `/pricing` 显示 “—”，不编一个买不到的价格。
- 配置器仍允许滑到 500，但会提示实际起订量（服务端同样校验）。

## 4. 明确没做的事

- 没有复制任何一站的文案、图片、代码或模板；`research/*.html` 只作结构取证，未进产物。
- 没有做 400 页的品类矩阵（PackagingBee 那种），因为内容会立刻变薄；改为 12 个真实结构 + 8 个真实行业。
- 没有做拖拽编辑器：SVG 预览已经能覆盖「我的 logo 印上去什么样」，编辑器的边际收益低于维护成本。
- 报价不含运费与税，界面直说；不把运费摊进单价做「看起来更便宜」的数字。

## 5. 与现有 Packrivo 资产的衔接

- 现有站是 WordPress + 自研 mu-plugin 多语言（`packrivo-handoff/`），已覆盖 11 语种 6,169 条字符串。
- 迁移路径：把 `dict3/{lang}-dict.json` 灌进 `lib/i18n.ts` 的字典结构（key 换成源字符串），即可复用既有译文，不用重翻。
- 商品数据（P01/P02、MOQ 500、1,000 件 $0.54 / 2,000 件 $0.52）可直接替换 `data/products.ts` 的对应条目与 `lib/pricing.ts` 的基准价。
- 新站可以作为独立 Node 服务挂在同域子路径（`/shop`），或整体替换 WP 前台、保留 WP 只做内容源。
