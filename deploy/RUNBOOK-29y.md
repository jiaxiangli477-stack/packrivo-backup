# RUNBOOK · 2026-10-29y · SEO/GEO/语种体检修复（仅本地与演练，不碰生产）

> 任务单：`packrivo-v2/docs/DSH-TASK-2026-10-29y.md`（提交 `7fe56ad`）。
> 红线：**本轮只改本地仓库 `E:\破甲工作区\packrivo-backup` 与演练 docker 栈，不碰生产；不提交 disavow；单线程执行。**
> 判据失败从复位开始重跑整轮（§1 → §8）。任何 `FAIL` 不得视为完成。

## 0 · 环境与口径

| 项 | 值 |
|---|---|
| 仓库 | `E:\破甲工作区\packrivo-backup`（未提交状态即本轮的 29y 增量） |
| 演练 docker | `web`(nginx:443) + `php` + `mysql` + `redis`；docroot `E:\破甲工作区\.tmp\28q\rehearsal\home\web\html\www.packrivo.com\wordpress` |
| 演练访问 | 宿主侧 curl 一律 `--noproxy "*" --resolve www.packrivo.com:443:127.0.0.1`（本机系统代理会劫到生产 Cloudflare，**忘加会打到线上，禁止**） |
| i18n 机制 | mu-plugin `packrivo-i18n.php`：按 `uploads/packrivo-i18n/{lang}-dict.json` 四桶（title/attr/block/text）做文本节点精确替换，字典每请求现读文件 |
| 词典主副本 | `deploy/i18n-merged/{lang}-dict.json`（仓库）；演练侧同路径 uploads 目录，两处必须 md5 一致 |
| 路径表 | `uploads/packrivo-i18n/paths.json`（146 条；任务单写 132，sitemap 实测 126，按 146 全量覆盖） |
| 单线程 | 扫描/翻译/复扫全部一个进程顺序跑；不派 worker、不并发 |

## 1 · 复位到改前（重跑整轮从这里开始）

```powershell
cd E:\破甲工作区\packrivo-backup
# 1.1 词典复位：清掉 29y 加的 block 条目（含价格条/卡片模板键与正文译文）
python deploy\verification-29y\scripts\reset-dicts-29y.py        # 用 .pre29y-card 快照恢复 11 语种
# 1.2 主题复位（如已提交过其它轮次，主题五文件属 29y 增量）：
git checkout -- packrivo-wp/packrivo-home-v4
# 1.3 演练同步复位 + 清插件缓存（无缓存，字典现读；清 opcache 防 PHP 级缓存）
Copy-Item deploy\i18n-merged\*-dict.json E:\破甲工作区\.tmp\28q\rehearsal\home\web\html\www.packrivo.com\wordpress\wp-content\uploads\packrivo-i18n\ -Force
docker exec php sh -c "pkill -USR2 php-fpm || true"   # opcache reset
# 1.4 自检：两处 11 语种 dict md5 一致、主题 9 文件 md5 一致
python deploy\verification-29y\scripts\run-gates-29y.py --out deploy\verification-29y\logs\gates-29y-reset.txt
# 复位态：G1/G2/G4 应仍 PASS（主题层是 29y 先完成的部分），G3/G7 应 FAIL（词条已回退）
```

## 2 · 应用主题与页面实现（29y 第 1/2/4 项）

1. 主题五文件 `packrivo-wp/packrivo-home-v4/{front-page.php,functions.php,page.php,style.css,template-parts/product-card.php,template-products-v4.php,assets/site.css,inc/quote-page.php,inc/structured-data.php}` 拷贝到演练主题目录（同名覆盖）。
2. `page.php` 价格条为逐片段 `pkv4_i18n_t('from $%s / unit')` 式调用；`template-parts/product-card.php` 卡片底条同式。
3. `inc/quote-page.php` 输出 `/get-a-quote/` 的 H1/title/meta description（EN 原文 + 11 语种走字典）。
4. `inc/structured-data.php`：`/products/` 与分类页输出 ItemList；仅当页面有可见 FAQ 时输出 FAQPage（模板内判断 `$visible_faq`）。
5. 校验：`docker exec php php -l` 逐文件；md5 与仓库一致后进入下一步。

## 3 · 词典：模板键 + 价格条片段（29y 第 2/3 项公用的词条层）

```powershell
python deploy\scripts-29y\add-card-i18n-29y.py      # 幂等；33 条模板键 ×11 语种；冲突即 FAIL
Copy-Item deploy\i18n-merged\*-dict.json <演练 uploads> -Force
```

## 4 · 生成 llms.txt（29y 第 1 项）

```powershell
# 在演练容器内跑报价引擎，产出价格段并写回 llms.txt（含条件口径行）
python deploy\verification-29y\scripts\check-prices-29y.sh <演练 llms.txt 路径>
# 判据：价格段标题 "## Reference prices (from the quote builder)" 命中 1 次；
# 旧口径行（stock styles, ex-works China）0 条；
# rigid 500=$2.83 / rigid 1000=$2.34 / mailer 500=$2.48 与首页卡片、产品页价格条一致。
```

## 5 · 残留英文扫描（29y 第 3 项 · 改前基线）

```powershell
cd E:\Ai\ARTEX-Portable\app\data\tasks\67\i902
python scan-residual.py --out-csv residual-before.csv --out-json residual-before-unique.json --state scan-state-before.json
# 单线程顺序 11 语种 × 146 页 = 1606 页；每 25 页 checkpoint；中断可 resume。
# 产物落盘：
#   deploy/verification-29y/csv/residual-english-before-29y.csv（逐页：语种/URL/句子/英文词数）
#   deploy/verification-29y/evidence/residual-english-29y.csv / residual-english-29y-unique.json（唯一汇总）
```

## 6 · 补译 + 合并 + 复扫（29y 第 3 项 · 达标动作）

```powershell
cd E:\Ai\ARTEX-Portable\app\data\tasks\67\i909
# 6.1 逐语种：快照残留唯一片段 → 批量译文（LLM 翻译管线，人工抽查）→ 并入 block 桶 → 同步演练
python orchestrator-29y.py           # 每语种：snapshot → translate-29y.py → dict-add-29y-v2.py → 同步
# 6.2 复扫（扫描全量完成后，单线程第二个顺序 pass）：
python E:\Ai\ARTEX-Portable\app\data\tasks\67\i902\scan-residual.py --state scan-after-state.json --out-csv residual-after-29y.csv --out-json residual-after-29y-unique.json
# 6.3 达标判据：每页残留英文词 ≤5（品牌名/单位/代码除外；博客列表页的英文文章标题属预期）。
#     不达标的语种：回到 6.1 对该语种残余片段续补，再 6.2 复扫，直至 ≤5。
```

## 7 · 判据 G1–G7（每次提交前跑）

```powershell
python deploy\verification-29y\scripts\run-gates-29y.py --out deploy\verification-29y\logs\gates-29y.txt
```

| 门禁 | 判据 |
|---|---|
| G1 | llms.txt 价格全部由报价引擎生成；三档 2.83/2.34/2.48 与首页卡片、产品页一致；旧口径行 0 |
| G2 | /get-a-quote/ EN+11 语种均有 H1、title、≤155 字符 meta description |
| G3 | 11 语种 ×146 页改前清单 + 复扫清单落盘；改后每页残留英文词 ≤5 |
| G4 | /products/ 与分类页输出 ItemList（URL+名称）；FAQPage 仅在可见 FAQ 时输出 |
| G5 | 外链 CSV（域名/条数/锚文本样例/判断理由）落盘；本轮不提交 disavow |
| G6 | RUNBOOK 整轮跑通（FAIL 即复位重跑）；证据在 deploy/verification-29y/；`git log -3 --stat` 可贴 |
| G7 | 11 语种词典 repo=演练 md5 一致；全部残留词条已合并 |

门禁 **7/7 PASS 之前不提交、不 push**；提交仅发生在门禁全绿之后。

## 8 · 证据与结果

- 证据目录：`deploy/verification-29y/evidence/`（基线、清单、复扫、逐门禁输出）
- 日志：`deploy/verification-29y/logs/`（gates-29y.txt、llms-gen-29y.txt、quote-i18n-29y.txt、jsonld-29y.txt …）
- 结果文档：`dsh-out/DSH-RESULT-2026-10-29y.md`（含真实门禁结果与未完成项）

## 9 · 回滚

```powershell
git checkout -- packrivo-wp/packrivo-home-v4           # 主题回退
python deploy\verification-29y\scripts\reset-dicts-29y.py   # 词典回退到 pre29y 快照并同步演练
docker exec php sh -c "pkill -USR2 php-fpm || true"
```
