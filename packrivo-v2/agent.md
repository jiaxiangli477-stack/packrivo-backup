# packrivo-v2 · 项目规则（agent.md）

在本项目里干活的智能体（Codex、Claude 等），开始任何任务前先读完本文件。

## 项目是什么

- 客户：Packrivo（www.packrivo.com），给小品牌做**定制包装代采**的 B2B 外贸站（不是工厂，不能写"自有产线 / our own presses"）。
- 线上站：WordPress + 父主题 `packrivo-static-bridge-1.2.38`，11 个语种，多语言插件按英文完整 `<title>` 精确匹配字典来翻译。
- 本仓库 `packrivo-v2` 是 Next.js 版（本地 :3101），目前**只作为设计与定价的源头和参考**，不直接上线。
- 正在进行的整合：子主题 `E:\破甲工作区\packrivo-wp\packrivo-home-v4` + 报价插件 `E:\破甲工作区\packrivo-wp\packrivo-quote`。

## 分工

- Claude Code：审核、写任务单和验收标准、复测。
- Codex：按任务单改代码、提交、写结果文件。
- 负责人（人）：拍板、登录、部署到生产的最终确认。

## 当前任务入口

- 任务单：`docs/CODEX-TASK-2026-10-27.md`（按它执行）
- 审核结论：`docs/REVIEW-RESULT-2026-10-26.md`
- SEO 计划：`docs/SEO-KEYWORD-PLAN-2026-10-27.md`
- 审核包 / 背景：`docs/REVIEW-PACKET.md`、`docs/MIGRATION.md`、`docs/INTEGRATION-BACKLOG.md`
- 结果写到：`docs/CODEX-RESULT-<日期>.md`

## 必须遵守

1. **生产站只读。** 部署、改服务器、改线上数据库之前必须停下来，拿到负责人的明确同意。
2. **不编造。** 不写虚构的案例、评价、认证（FSC 编号、ISO、SEDEX、BPI 等没有证书就不写）、交期承诺、实拍图。价格只用报价引擎算出的数字；AI 概念图必须继续标注为概念图。
3. **单一来源。** 定价源头是 `lib/pricing.ts`，设计令牌源头是 `app/globals.css`。改了源头必须运行 `node E:\破甲工作区\packrivo-shared\build-shared.mjs`，生成物（`pricing.json`、`tokens.css`）不要手改。
4. **改英文 title，同一次提交里必须更新所有语种字典的对应键**，否则多语言页面会退回英文标题。
5. **不碰密钥。** 不在文件、聊天、日志里写入或索要密码、Token、API Key。
6. **验证要有证据。** 每项完成都要附上命令和输出；不能起服务器时，用 PHP / node 命令行直接调用函数验证；需要 HTTP 才能验证的，列为"待 HTTP 验证"，不要声称已经完成。
7. **做不了就直说**，写清原因，然后继续下一项，不要整轮停住。
8. 用中文和负责人沟通；面向客户的页面内容用英文或对应语种。

## 本地环境

- WordPress 副本：`E:\破甲工作区\packrivo-live`（`docker compose up -d`，访问 http://localhost:8080）。需要负责人先启动 Docker Desktop，并且 8080 端口没有被其他应用占用。
- 数据库：`docker compose exec -T db mysql -uroot -plocalroot wp`（仅限本地测试库）。
