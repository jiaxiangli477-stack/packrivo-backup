# 迁移 · 新增 · 覆盖 —— 让转化链路闭合，同时把 GEO/SEO 拉起来

对象：现站 `packrivo.com`（WP 7.1.3 + 自研 mu-plugin 多语言，149 页 / 315 篇博客 / 11 语种 × 6,169 条译文）
参照：`packrivo-v2`（Next 复刻站）与 `packrivo-quote`（WP 插件）
证据来源：`docs/wp-migration/` 五份报告（抓取时间 2026-10-26）

---

## 0. 一句话结论

现站强在**入口**（315 篇博客、11 语种、hreflang）和**合规**（不吹认证、标 AI 概念图）；
复刻站强在**中段**（价格公式、配置器、结构对比、阶梯价）；
缺的是**末段**（报价 → 订单 → 付款）。

所以不是搬整站，是**补中段、通末段、守住入口**。

---

## 1. 漏斗四段，逐段对账

### A. 认知层（入口）—— 现站更强，只补不改

| 项 | 现站 | 复刻站 | 动作 |
|---|---|---|---|
| 博客 | 315 篇 / 5 主题 | 10 篇指南 | **不迁**。把 v2 的 10 篇当选题库，扩到现站 |
| 多语言 | 11 语种 × 6,169 条 + 13 条 hreflang | 7 语种仅 chrome | **不迁**。以现站为准 |
| canonical / hreflang | 每页齐全 | 无 | **反向补**：v2 的 metadata 组织方式可借，别覆盖现站 |
| robots AI 白名单 | GPTBot / ClaudeBot / PerplexityBot 等逐条 Allow | allow all | 保留现站策略，这是 GEO 资产 |

### B. 评估层 —— 复刻站明显更强，**这是要搬的重点**

| 功能 | 现站 | 复刻站 | 动作 | 落地 | SEO/GEO 影响 |
|---|---|---|---|---|---|
| 价格公式（九结构 × 八材料 × 四印刷 × 七工艺 × 六档） | 只有参考区间 + 免责 | 可复算，PHP/TS 双端一致 | **迁**（已做成 PHP 类） | `packrivo-quote/includes/class-pkq-pricing.php` | 可直接生成 `AggregateOffer` 价格区间，补上 schema 里 0 个 offer 的窟窿 |
| 五步配置器 | 素材选择器（明说不是报价器） | 三步：结构+尺寸 / 规格 / 联系人 | **替换** | 短代码 `[packrivo_quote]` | 停留时长、表单完成率；页面加 FAQ/HowTo 结构化 |
| 阶梯价表 | 无 | 500→25,000 六档 | **新增** | 产品模板插入 | 长尾词「500 pcs price」「bulk discount」直接命中 |
| 结构对比表 | 有 /compare/ | 有 /pricing 全表 | **覆盖**（合并两边的维度） | 新建 `/pricing/` 页 | 抢「mailer vs carton」「which box for X」类问答 |
| 行业页 8 个 | 有品类页，无「翻车点」结构 | 每页写「通常哪里会出问题」 | **新增** | 复用 8 个 industry 文案骨架 | 主题权威度（topical authority） |
| 材料矩阵 | 无 | 8 基材 × 回收含量 × 寿命 × 认证 × 代价 | **新增** | `/sustainability/` 表格 | GEO 最吃这种可引用表格 |

### C. 决策层 —— 两边都弱，**补一个**

| 功能 | 现站 | 复刻站 | 动作 |
|---|---|---|---|
| 样品申请 | 有表单 | 有独立页 + 表单 | **迁移为独立落地页**（`/samples/`），带「盒里有什么」清单 |
| 报价单永久链接 | 无 | `/quote/[id]` 可打印可转发 | **新增**：插件生成 `?ref=PKQ-XXXX` 只读页，客户可回看 |
| 工程师预约 | 无 | `/contact` 承诺 1 个工作日 | **新增**：Cal.com/Calendly 嵌入，减少来回邮件 |

### D. 成交层 —— 现站完全没有，**新建**

| 功能 | 动作 | 说明 |
|---|---|---|
| 订单落库 | **已交付**（`pkq_order`） | 后台可查、可导出 CSV |
| 订单 → Woo 草稿订单 | **下一步** | 客户提交后自动建 Woo 订单，发付款链接，链路闭合 |
| 定金/尾款 | 需业务确认 | 现站是 8–15% 协调费模式，收款口径要先定 |
| 订单状态通知 | 插件已留 `notify_customer` 开关 | 接 Resend/SES 即用 |

---

## 2. GEO（生成式引擎优化）清单

现站已有底子（AI 爬虫白名单、清晰实体、11 语种），缺的是**可被引用的答案块**：

1. **答案前置段**：每个 H2 下面先写 60–80 字结论，再展开。AI 摘要优先抓这段。
2. **价格给区间而非「联系销售」**：`$0.27–0.98 at 500 pcs` 这种现成格式是对的，把它写进 `AggregateOffer`。
3. **结构化数据补齐**：Product + Offer/AggregateOffer、FAQPage、HowTo（配置步骤）、BreadcrumbList；现站 schema 有 WebPage/ImageObject，**没有 offer**。
4. **`llms.txt` + `llms-full.txt`**：给 AI 一份站点地图式摘要（产品线、MOQ、交期、认证状态、联系方式）。放在根目录。
5. **可引用来源**：认证、测试标准（ISTA/ASTM）、法规（EU 10/2011）标出处，AI 更愿意引用带出处的页面。
6. **实体一致性**：Organization 的 `sameAs`（LinkedIn / Alibaba / Made-in-China）、地址、邮箱全站一致；现站 WhatsApp +86 与「Shenzhen UTC+8」要对齐。
7. **新鲜度**：价格/交期页加 `dateModified`，季度更新一次。

## 3. SEO 清单（按投入产出排序）

1. **产品 schema 补 Offer**（半天，直接吃 rich result）
2. **配置器页面给 FAQ + HowTo schema**（半天）
3. **`/pricing/` 新页 + 内链**：从 18 个产品页、8 个行业页链过去（1 天）
4. **图片 alt 与文件名规范化**：现站是 `P01-xxx.webp`，改成 `magnetic-jewelry-box-95x95.webp`（半天）
5. **报价页 noindex**：`/quote/`、`?ref=` 只读页别进索引，避免薄页稀释（现站已有 `/quote/` 类似处理）
6. **博客内链到产品页**：315 篇里挑 40 篇高曝光，补上下文内链（2 天，最高 ROI）
7. **Core Web Vitals**：WP + CF 已经在跑，重点压首图（webp + 预连接），配置器 JS 只有 18KB 无依赖

## 4. 建议的落地顺序（两周）

| 周 | 做什么 | 产出 |
|---|---|---|
| 第 1 周 | 装插件 → 改真实价格 → 产品页嵌配置器 → 产品 schema 补 Offer | 转化链路通到「提交订单」 |
| 第 1 周 | `/pricing/` 页 + 8 个行业页补「翻车点」段 | 评估层补齐，长尾开始吃量 |
| 第 2 周 | 订单 → Woo 草稿订单 → 付款链接 | 链路闭合到收款 |
| 第 2 周 | llms.txt、FAQ/HowTo schema、40 篇博客内链 | GEO/SEO 生效 |

## 5. 不要做的事

- **不要用复刻站替换现站**：315 篇博客 + 11 语种路径体系会丢。
- **不要把复刻站的认证徽标、评价、案例数字搬过去**：全是占位。
- **不要给报价配置器做 index**：它是工具页，索引价值低、重复度高。
- **不要把 9 个结构的 MOQ 都写成 500**：卷标 5,000、贴纸 1,000，与现站「统一 500/design」冲突，先核真。

---

## 6. 与代码的对应关系

| 文档里的功能 | 代码位置 |
|---|---|
| 价格公式 | `packrivo-wp/packrivo-quote/includes/class-pkq-pricing.php` |
| 配置器前端 | `packrivo-wp/packrivo-quote/assets/pkq-quote.{js,css}` |
| 订单入库 / REST | `includes/class-pkq-rest.php` |
| 后台与 CSV | `includes/class-pkq-admin.php` |
| 设计令牌 | `packrivo-v2/app/globals.css`（`@theme` 段，可整段导出） |
| 内容骨架 | `packrivo-v2/data/{products,industries,resources,case-studies}.ts` |
