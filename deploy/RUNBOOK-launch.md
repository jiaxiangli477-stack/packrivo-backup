# 上线手册 · Packrivo 一次性上线（RUNBOOK）

## 32a-3 硬闸门：11 语言翻译（所有 32a 写入之前）

仅站长获得另行上线授权后执行。先定义本手册中的 `$PKG`、`$PKGC`、`$BACKUP`、`$WPC` 和 `$WPCLI`，核验数据库备份可恢复，并让站点保持维护状态直到所有步骤及回归完成。dsh 完成翻译并合并到**本次实际待部署的** 11 份字典与报价插件包后，将脚本、完整 `32a-new-strings.csv`、最终 `i18n-merged/` 和解包后的报价插件放到 `$PKG`；路径须对应实际将部署的文件：

```sh
if ! python3 "$PKG/32a-i18n-gate.py" \
  --csv "$PKG/32a-new-strings.csv" \
  --dict-dir "$PKG/i18n-merged" \
  --quote-dir "$PKG/packrivo-quote/includes/quote-i18n" \
  --report "$BACKUP/32a-i18n-missing.csv"; then
  echo 'STOP: missing or invalid translation keys; no 32a deployment or migration'
  exit 1
fi
```

所有 11 种语言的 `missing`、`quote_missing`、`quote_invalid`、`title_missing` 均为 0 且 `GATE PASS` 才继续。报价组件通过单独的 `quote-i18n` 查找字符串，因此同时检查其三个新键及占位符；404 键必须位于 `title` 桶。闸门验证键及非空值，不代替翻译质量、其他键的正确桶位和实际语言页验收；不得以脚本通过冒充这些验收。`32a-moq-content.php` 不能单独上线。

语言路由对不存在的路径提前退出；更新后的 `001-packrivo-32a-404-title.php` 直接读取对应语种词典的 `title` 桶，缺键、空值或文件异常时回退英文。正式上线前仍须通过真实翻译字典抓取 11 种语言的 404 页面验证；不得以合成测试代替。

### 32a-3：工作区快照的单字段备份（在任何 32a 数据改动和插件替换之前）

在上述翻译闸门通过及数据库备份可恢复后，将 `32a-workspace-moq-{lib,backup,migrate,rollback}.php` 放入 `$PKG` 并复制到同一 `$PKGC` 目录。备份不得覆盖旧文件；备份 CSV 只含 revision、page ID、lane、旧 MOQ 及前后/非 MOQ 哈希，不含完整产品记录：

```sh
for name in lib backup migrate rollback; do docker cp "$PKG/32a-workspace-moq-$name.php" php:"$PKGC/32a-workspace-moq-$name.php"; done
test ! -e "$BACKUP/32a-workspace-moq.csv" || { echo 'STOP: backup already exists'; exit 1; }
$WPCLI eval-file "$PKGC/32a-workspace-moq-backup.php" > "$BACKUP/32a-workspace-moq.csv" || { echo 'STOP: workspace backup failed'; exit 1; }
test "$(wc -l < "$BACKUP/32a-workspace-moq.csv")" -gt 1 || { echo 'STOP: empty workspace backup'; exit 1; }
sha256sum "$BACKUP/32a-workspace-moq.csv"
docker cp "$BACKUP/32a-workspace-moq.csv" php:"$PKGC/32a-workspace-moq.csv"
docker exec php sha256sum "$PKGC/32a-workspace-moq.csv"
```

核对两份 CSV 哈希相同并妥善保管；备份与后续操作期间若快照增删或非 MOQ 内容改变，迁移会报错退出，须重新核对，不能覆盖旧备份。

## 32a 升级时的选项迁移（仅站长获单独上线授权后执行）

先完成数据库备份、核验备份可恢复，并部署复核通过的 32a 插件和主题。将 `deploy/32a/mu-plugins/001-packrivo-32a-404-title.php` 部署到 mu-plugins（在现有语言路由插件之前加载）。将本提交的 `deploy/32a-update-options.php` 放到 PHP 容器内，例如 `/pkg/32a-update-options.php`，然后用已配置的 `$WPCLI` 执行：

```sh
$WPCLI eval-file /pkg/32a-update-options.php
$WPCLI eval-file /pkg/32a-update-options.php
```

第一次只将 `pkq_settings.min_qty` 设为 250，并在缺失时补上 `fee_percent=15`、`min_fee=150`；已有费率值及其他键不改。另将 Yoast 的 `wpseo_titles.title-404-wpseo` 设为 `Page not found | Packrivo`。第二次应显示两个 `unchanged`。回滚时从本次数据库备份恢复对应选项。翻译清单 `dsh-out/32a-new-strings.csv` 需先交给 dsh；不得把本地验收视为上线授权。

### 32a-2：页面 MOQ 内容迁移（紧接选项迁移之后）

站长在另行取得上线授权、建立并核验数据库备份后，将本提交的 `32a-moq-content.php` 和 `32a-moq-plan.json` 一起放入宿主机 `$PKG`，再复制到容器同一目录 `$PKGC`：

```sh
docker cp "$PKG/32a-moq-content.php" php:"$PKGC/32a-moq-content.php"
docker cp "$PKG/32a-moq-plan.json" php:"$PKGC/32a-moq-plan.json"
$WPCLI eval-file "$PKGC/32a-moq-content.php"
$WPCLI eval-file "$PKGC/32a-moq-content.php"
```

脚本只对计划中指定的 page ID、slug、字段和精确旧文案执行替换，包含实际用于输出的 Yoast、主题投影及旧产品页面字段；博客、修订和明确的 500 件报价示例不改。逐项身份或原文不匹配会在写入前报错退出；遇到报错，停下核对最新导出并更新计划，不绕过检查。一次事务同时对与原 Yoast 标题/描述完全相符的对应 indexable 缓存字段做相同替换，避免旧标题继续输出；不重建全站索引。

父主题的独立 `packrivo_moq` 选项控制产品规格中的动态 MOQ；缺失时默认 500，和报价插件 `pkq_settings.min_qty` 是两个设置。本步骤在同一事务中将缺失或 500 的 `packrivo_moq` 设置为 250；若已是其他非 250 的值则停下核对，不覆盖未知设置。首次记录 `before`、`after`、`replaced`、`yoast_cache_replaced` 和 `site_moq_updated`；第二次要求三种更新数为 0。剩余的一条 `500 pieces per` 是保留的 500 件具体报价示例。然后抓取 `/about/`、两个目标产品页和 `/products/`，确认标题及正文。若需回滚，从本次备份按受影响的页面、meta、Yoast indexable 和 `packrivo_moq` 恢复（原先缺失则删除该新增选项），不覆盖其他业务变动。

### 32a-5：已发布博客的精确句子迁移（紧接页面迁移之后）

仅在另行取得生产上线授权、翻译闸门通过且完整数据库备份可恢复后执行。确认计划对应上线时的数据库快照，若已发布文章有新增、删除或变化，先重新核对清单和计划；脚本的 318 条、post ID、slug、状态及原句数量不符会整体中止。原计划中符合替换条件的命中均在正文；已发布博客 Yoast title/metadesc/indexable 的旧 MOQ 命中属于须整篇重写的 post 448，保留原样。post 448 的 slug `packaging-moq-design-planning` 不变，不增加 301。

```sh
docker cp "$PKG/32a5-blog-migrate.php" php:"$PKGC/32a5-blog-migrate.php"
docker cp "$PKG/32a5-blog-plan.json" php:"$PKGC/32a5-blog-plan.json"
BLOG_BACKUP="$PKGC/32a5-blog-$LAUNCH.json"
$WPCLI eval-file "$PKGC/32a5-blog-migrate.php" dry-run "$BLOG_BACKUP"
$WPCLI eval-file "$PKGC/32a5-blog-migrate.php" backup "$BLOG_BACKUP"
test ! -e "$BACKUP/32a5-blog-$LAUNCH.json" || { echo 'STOP: backup exists'; exit 1; }
docker cp php:"$BLOG_BACKUP" "$BACKUP/32a5-blog-$LAUNCH.json"
docker exec php sha256sum "$BLOG_BACKUP"
sha256sum "$BACKUP/32a5-blog-$LAUNCH.json"
```

核对两份备份哈希一致并保护其完整文章内容后，才可执行：

```sh
$WPCLI eval-file "$PKGC/32a5-blog-migrate.php" apply "$BLOG_BACKUP"
$WPCLI eval-file "$PKGC/32a5-blog-migrate.php" apply "$BLOG_BACKUP"
```

第一次 `changed=318`，第二次 `changed=0`。若需回滚，在维护状态下执行 `$WPCLI eval-file "$PKGC/32a5-blog-migrate.php" rollback "$BLOG_BACKUP"`；任一相关文章在迁移后被其他人修改则停止，先人工核对，不能覆盖新内容。保留报告中标为 rewrite 的文章及其链接供 dsh 重写。

32a 在生产上线并通过正常公开 URL 验证后，由站长另行把 `E:/CodexHome/automations/packrivo-03-00-packrivo-2/automation.toml` 的每日博客任务起订量从 500 改为 250；此前保持原设置。本轮没有修改定时任务。

### 32a-2：旧 mu-plugin 的生产现场核对和精确替换

`packrivo-wave1.php`、`packrivo-site-remediation-20261022.php`、产品工作区的 renderer、admin、validation 文件和父主题 `packrivo-static-bridge-1.2.38/functions.php` 不在本仓库跟踪文件或任何 Git 引用中。**不能用本地快照覆盖生产文件。** 站长应在正式授权后从当时生产容器获取原文件、备份并生成候选；以下命令使用本手册定义的 `$WPC`、`$PKG`、`$PKGC` 和 `$BACKUP`：

```sh
for name in wave1 remediation workspace admin validation parent; do test ! -e "$BACKUP/32a-$name.before.php" || { echo "STOP: existing $name backup"; exit 1; }; done
docker cp php:"$WPC/wp-content/mu-plugins/packrivo-wave1.php" "$BACKUP/32a-wave1.before.php"
docker cp php:"$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" "$BACKUP/32a-remediation.before.php"
docker cp php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-renderer.php" "$BACKUP/32a-workspace.before.php"
docker cp php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-admin.php" "$BACKUP/32a-admin.before.php"
docker cp php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-validation.php" "$BACKUP/32a-validation.before.php"
docker cp php:"$WPC/wp-content/themes/packrivo-static-bridge-1.2.38/functions.php" "$BACKUP/32a-parent.before.php"
docker cp "$BACKUP/32a-wave1.before.php" php:"$PKGC/32a-wave1.before.php"
docker cp "$BACKUP/32a-remediation.before.php" php:"$PKGC/32a-remediation.before.php"
docker cp "$BACKUP/32a-workspace.before.php" php:"$PKGC/32a-workspace.before.php"
docker cp "$BACKUP/32a-admin.before.php" php:"$PKGC/32a-admin.before.php"
docker cp "$BACKUP/32a-validation.before.php" php:"$PKGC/32a-validation.before.php"
docker cp "$BACKUP/32a-parent.before.php" php:"$PKGC/32a-parent.before.php"
docker cp "$PKG/32a-patch-legacy-moq.php" php:"$PKGC/32a-patch-legacy-moq.php"
docker exec php php "$PKGC/32a-patch-legacy-moq.php" wave1 "$PKGC/32a-wave1.before.php" "$PKGC/32a-wave1.candidate.php"
docker exec php php "$PKGC/32a-patch-legacy-moq.php" remediation "$PKGC/32a-remediation.before.php" "$PKGC/32a-remediation.candidate.php"
docker exec php php "$PKGC/32a-patch-legacy-moq.php" workspace-renderer "$PKGC/32a-workspace.before.php" "$PKGC/32a-workspace.candidate.php"
docker exec php php "$PKGC/32a-patch-legacy-moq.php" workspace-admin "$PKGC/32a-admin.before.php" "$PKGC/32a-admin.candidate.php"
docker exec php php "$PKGC/32a-patch-legacy-moq.php" workspace-validation "$PKGC/32a-validation.before.php" "$PKGC/32a-validation.candidate.php"
docker exec php php "$PKGC/32a-patch-legacy-moq.php" parent-functions "$PKGC/32a-parent.before.php" "$PKGC/32a-parent.candidate.php"
docker cp php:"$PKGC/32a-wave1.candidate.php" "$BACKUP/32a-wave1.candidate.php"
docker cp php:"$PKGC/32a-remediation.candidate.php" "$BACKUP/32a-remediation.candidate.php"
docker cp php:"$PKGC/32a-workspace.candidate.php" "$BACKUP/32a-workspace.candidate.php"
docker cp php:"$PKGC/32a-admin.candidate.php" "$BACKUP/32a-admin.candidate.php"
docker cp php:"$PKGC/32a-validation.candidate.php" "$BACKUP/32a-validation.candidate.php"
docker cp php:"$PKGC/32a-parent.candidate.php" "$BACKUP/32a-parent.candidate.php"
diff -u "$BACKUP/32a-wave1.before.php" "$BACKUP/32a-wave1.candidate.php" || true
diff -u "$BACKUP/32a-remediation.before.php" "$BACKUP/32a-remediation.candidate.php" || true
diff -u "$BACKUP/32a-workspace.before.php" "$BACKUP/32a-workspace.candidate.php" || true
diff -u "$BACKUP/32a-admin.before.php" "$BACKUP/32a-admin.candidate.php" || true
diff -u "$BACKUP/32a-validation.before.php" "$BACKUP/32a-validation.candidate.php" || true
diff -u "$BACKUP/32a-parent.before.php" "$BACKUP/32a-parent.candidate.php" || true
```

脚本要求 sticky 旧串 1 处、remediation 的三种旧串分别 2、1、1 处、renderer 两种旧串各 1 处、admin 两处、validation 三处、父主题摘要旧串 1 处；不匹配则不写候选并停下。逐行检查 diff：validation 除数值外还在公开投影中对两条已知旧 FAQ/SEO 句子做精确归一，旧记录原文不变。随后执行语法检查：

```sh
for name in wave1 remediation workspace admin validation parent; do docker exec php php -l "$PKGC/32a-$name.candidate.php" || exit 1; done
```

全部通过后核对生产文件与备份的 SHA-256：

```sh
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave1.php" "$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" "$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-renderer.php"
docker exec php sha256sum "$WPC/wp-content/themes/packrivo-static-bridge-1.2.38/functions.php"
docker exec php sha256sum "$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-admin.php" "$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-validation.php"
sha256sum "$BACKUP/32a-wave1.before.php" "$BACKUP/32a-remediation.before.php" "$BACKUP/32a-workspace.before.php" "$BACKUP/32a-parent.before.php" "$BACKUP/32a-admin.before.php" "$BACKUP/32a-validation.before.php"
```

只有六组哈希分别一致且仍处于维护状态时，才执行下列替换，然后立即执行下方快照迁移；不得以半更新状态向访客开放：

```sh
docker cp "$BACKUP/32a-wave1.candidate.php" php:"$WPC/wp-content/mu-plugins/packrivo-wave1.php"
docker cp "$BACKUP/32a-remediation.candidate.php" php:"$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php"
docker cp "$BACKUP/32a-workspace.candidate.php" php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-renderer.php"
docker cp "$BACKUP/32a-admin.candidate.php" php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-admin.php"
docker cp "$BACKUP/32a-validation.candidate.php" php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-validation.php"
docker cp "$BACKUP/32a-parent.candidate.php" php:"$WPC/wp-content/themes/packrivo-static-bridge-1.2.38/functions.php"
```

回滚使用该次 `.before.php` 备份。上述步骤不构成本轮上线授权。

### 32a-3：迁移工作区快照（紧接插件替换）

使用前面核对过哈希的同一 CSV，不得重新生成或覆盖它。迁移只改变 `record_json` 内 `commercial.moq` 的数字 token，逐条校验其余字节哈希；记录增删、哈希不符或非预期值会回滚整个事务：

```sh
$WPCLI eval-file "$PKGC/32a-workspace-moq-migrate.php" "$PKGC/32a-workspace-moq.csv"
$WPCLI eval-file "$PKGC/32a-workspace-moq-migrate.php" "$PKGC/32a-workspace-moq.csv"
```

第二次必须 `replaced=0`。然后核对 public、candidate 和历史预览的 MOQ、产品页和 REST/结构化数据；保留明确的 500 件数量例子。需要回退时先进入维护状态，执行下述数据回滚，并将上面备份的 validation/admin 源文件恢复到对应原路径；数据和代码均恢复后再核对公开版本为旧值，不能只回滚其中一部分：

```sh
$WPCLI eval-file "$PKGC/32a-workspace-moq-rollback.php" "$PKGC/32a-workspace-moq.csv"
docker cp "$BACKUP/32a-validation.before.php" php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-validation.php"
docker cp "$BACKUP/32a-admin.before.php" php:"$WPC/wp-content/plugins/packrivo-product-workspace/includes/class-product-admin.php"
```

### 32a-4：重新生成并核验 AI 摘要（仅另行授权上线时）

历史 `deploy/artifacts-29y/llms.txt` 和 `llms-full.base.txt` 仍含旧 500 件起订量，不得直接用于本轮上线。先完成翻译闸门、页面/报价迁移及其验收；确认 `$LLMS_INTERNAL_ORIGIN` 是能访问**当前已更新站点**的容器内部反向代理地址（不要使用可能仍缓存旧页的 CDN），`Host` 必须匹配站点。将本提交的三个生成脚本及 `deploy/32a4/llms.base.txt` 放入 `$PKG`；下面使用本次 `$LAUNCH` 创建一个不得重用的暂存目录：

```sh
LLMS_STAGE="$PKGC/32a4-llms-$LAUNCH"
if docker exec php test -e "$LLMS_STAGE"; then echo 'STOP: llms stage exists'; exit 1; fi
docker exec php mkdir -p "$LLMS_STAGE"
docker cp "$PKG/32a4-generate-llms.php" php:"$LLMS_STAGE/32a4-generate-llms.php"
docker cp "$PKG/32a4-generate-llms-full.php" php:"$LLMS_STAGE/32a4-generate-llms-full.php"
docker cp "$PKG/32a4-llms-text.php" php:"$LLMS_STAGE/32a4-llms-text.php"
docker cp "$PKG/32a4/llms.base.txt" php:"$LLMS_STAGE/32a4-llms.base.txt"
docker exec php php -l "$LLMS_STAGE/32a4-generate-llms.php"
docker exec php php -l "$LLMS_STAGE/32a4-generate-llms-full.php"
docker exec php php -l "$LLMS_STAGE/32a4-llms-text.php"
docker exec php php -r 'foreach (["curl","dom","SimpleXML"] as $x) { if (!extension_loaded($x)) { fwrite(STDERR,"Missing $x\n"); exit(1); } }'
$WPCLI eval-file "$LLMS_STAGE/32a4-generate-llms.php" "$LLMS_STAGE/32a4-llms.base.txt" "$LLMS_STAGE/llms.txt" "$LLMS_STAGE/llms-prices.json"
docker exec php php "$LLMS_STAGE/32a4-generate-llms-full.php" "$LLMS_INTERNAL_ORIGIN" 'www.packrivo.com' 'https://www.packrivo.com' "$LLMS_STAGE/llms-full.txt" 3
docker exec php php -r '$d=$argv[1]; require $d."/32a4-llms-text.php"; foreach (["llms.txt","llms-full.txt"] as $f) { $s=file_get_contents($d."/".$f); if ($s===false || pk32a4_old_moq($s)) { fwrite(STDERR,"STOP: stale MOQ or missing $f\n"); exit(1); } echo "$f stale_moq=0\n"; }' "$LLMS_STAGE"
```

摘要价格由当前 `PKQ_Pricing::price()` 和 `pk_from_price()` 计算，生成器要求设置为 250／15%／$150，口径明确包含协调费；`llms-full` 抓取英文 page sitemap 的去重路径，只提取服务器渲染主内容，不执行 JS 或抓取外部资源。任一页面非 200、noindex、站外、旧 MOQ 或解析失败均停止且不写完整摘要。人工核对最新页面、价格、明确的 500 件报价例子与输出哈希后，再在另行授权的维护步骤中备份并替换生产静态文件；不得仅凭本地结果发布。

### 32a-4：临时调试插件现场核查（仅另行授权上线时）

本地 31a/31b/32a 快照均含 `zz-debug-29y.php`，其注释标明临时排障用途；现有证据不能确定生产是否存在。32a 本地已禁用。站长获上线授权后，如实际生产路径存在该文件，先备份、比较哈希，再删除原文件：

```sh
if docker exec php test -f "$WPC/wp-content/mu-plugins/zz-debug-29y.php"; then
  test ! -e "$BACKUP/zz-debug-29y.php" || { echo 'STOP: debug backup exists'; exit 1; }
  docker cp php:"$WPC/wp-content/mu-plugins/zz-debug-29y.php" "$BACKUP/zz-debug-29y.php"
  docker exec php sha256sum "$WPC/wp-content/mu-plugins/zz-debug-29y.php"
  sha256sum "$BACKUP/zz-debug-29y.php"
  echo 'Compare hashes; only if equal, remove the production original in the authorized maintenance step.'
fi
```

两份哈希确认一致后，才执行 `docker exec php rm "$WPC/wp-content/mu-plugins/zz-debug-29y.php"`；之后检查页面没有 `DBG29Y`。生产文件是否存在和该删除均未在本轮核验或执行。

> **上线以本文件为准。`deploy/README.md` 里按轮次堆的 27g…28q 记录只作历史参考，命令和顺序以这里为唯一口径。**
> 本文件不含任何密码、密钥或 token：wp-cli 走服务器上已有的 SSH/容器配置，Cloudflare 清缓存走本机 `E:\破甲工作区\i18n\.cf.env`（只引用路径，不抄值）。
>
> **28q 改版要点**（相对 28o）：生产站跑在 Docker 里，本手册所有命令都标了「宿主机 / 容器内」；`$BACKUP` 第 1 步定一次、写进 `CURRENT_LAUNCH`；备份路径与回滚路径一一对应；第 4、5 步按**首次安装**写（生产上子主题与报价插件都还不存在）；数据库备份落到 `$BACKUP/db.sql`。
>
> **首装已于 2026-10-29 完成。本手册是「首装手册」，不是升级手册。**
> 那次上线的生产 ID、备份目录与逐步实测：`deploy/LAUNCH-LOG-2026-10-28.md` §37–§40、`dsh-out/DSH-RESULT-2026-10-28-prod.md`。要点：`CURRENT_LAUNCH = packrivo-launch-20261029-0335`，`/get-a-quote/` 页 ID = **3383**（本手册 4.5 建、9.8 删），两个 mu 补丁打后哈希 `887787ab…` / `f7d95f35…`。
> **以后改主题或插件不要重跑本手册**：第 0 步「子主题/报价插件未安装」两条判据在已上线的生产上必然不成立，第 4/5 步的 `--force` 覆盖安装与 4.5 建页也不该再走一遍。升级另写升级手册（只覆盖装包 + 打补丁 + 复测）。

- 目标站点：`https://www.packrivo.com`（**WordPress 7.1.4** + 父主题 `packrivo-static-bridge-1.2.38` + 子主题 `packrivo-home-v4` + 报价插件 `packrivo-quote` + mu-plugins `packrivo-i18n.php`、`packrivo-site-remediation-20261022.php`）
- 上线前生产现状（2026-10-28 实测）：活跃主题是父主题本身，**子主题与报价插件都未安装**，`/get-a-quote/` 是 mu 里那条老别名 301 到 `/inquiry/`
- 本轮上线内容：134 个产品页新标题/描述（27g）、8 个主页面（27i）、11 语种字典与路径表（27g/27i/28k/28m/28n/28o）、报价插件与子主题新包、两个 mu 补丁
- 预计总耗时：**1.5–2 小时**（含复测），建议在**访问量低**的时段做（北京时间 02:00–05:00 或周末）
- 每一步都能单独回滚；第 9 步是整体逆序回滚
- 任一步失败就停，按该步「回滚」恢复后再继续；不要跳过判据硬往前做

---

## 0 · 先读这里：宿主机 ↔ 容器

生产站不在宿主机上直接跑，而是 **Docker**：`nginx`（`nginx:alpine`）+ `php`（`kjlion/php:fpm-alpine`，PHP 8.3.31）+ `mysql` + `redis`。宿主机上**没有** `php`、**没有** `wp`。所以：

| 东西 | 宿主机（SSH 进来的那个 bash） | 容器 `php` 内 |
|---|---|---|
| WordPress 根 | `/home/web/html/www.packrivo.com/wordpress`（`$WP`） | `/var/www/html/www.packrivo.com/wordpress`（`$WPC`） |
| i18n 字典 | `$WP/wp-content/uploads/packrivo-i18n`（`$I18N`） | `$WPC/wp-content/uploads/packrivo-i18n` |
| 上传的包 | `/home/web/packrivo-packages`（`$PKG`） | `/pkg`（`$PKGC`，由 `docker cp` 灌进去） |
| 备份 | `/home/web/backups/<LAUNCH>`（`$BACKUP`） | **不可见**（需要时才 `docker cp` 进去/出来） |

三条硬规则：

1. **`wp` 只能在容器里跑**，且容器 `disable_functions` 含 `proc_open`，必须用 `-d disable_functions=` 覆盖，否则任何要起子进程的命令（`db export`、`plugin install`…）都会 `Cannot do 'Process::run'`。
2. **`php` 也只在容器里**（`php -l`、`merge-i18n.php`）。宿主机上的 `php` 一律写成 `docker exec php php …`。
3. **文件路径要看是谁在读写**：宿主机侧的 `cp/ls/sha256sum/curl` 用 `$WP`、`$PKG`、`$BACKUP`；容器侧的 `wp` 参数用 `$WPC`、`$PKGC`。`wp` **写出来的文件**（db 导出、rollback JSON）落在容器里，必须 `docker cp` 出来存进 `$BACKUP`。

### 变量（每开一个 SSH 会话都先执行这一块）

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
PKGC=/pkg
SITE=https://www.packrivo.com
SITEMAP=/sitemap_index.xml    # Yoast 站点地图；若站点只用 WP 核心地图就改成 /wp-sitemap.xml
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
# $BACKUP 不在这里生成：第 1 步定一次，之后永远从文件读
```

### 0.1 上线前检查（只读）

> **生产数据库是 MySQL，不是 MariaDB。** 2026-10-29 那次实测：`mysql` 容器跑 **MySQL Community Server 26.7.0**，账号认证插件是 **`caching_sha2_password`**；`php` 容器里的客户端是 `mariadb-client/mariadb-dump 11.4.13`，它的认证插件目录 `/usr/lib/mariadb/plugin/` 初始为空。这两件事各会产生一次报错，**导出探针要按下面的两层顺序处理**，两层都过完探针才可能有字节。
> 演练环境从 2026-10-30 起换成 **`mysql:26.7.0`**（与生产同版本）+ `caching_sha2_password` + 自签证书 TLS。同版本不只是为了报错一致：MySQL 26.7 里 **`MD5()` / `SHA1()` 已经没有**（`ERROR 1305`），只有 `SHA2()` / `CRC32()`，判据脚本用的是 `SHA2(…,256)`。


```bash
# 容器与版本
docker ps --format '{{.Names}}\t{{.Image}}' | grep -E '^(php|mysql|nginx|redis)\b'
$WPCLI core version                         # 期望 7.1.4（生产开着 WP 小版本自动更新，见下方说明）
docker exec php php -v | head -1            # 期望 PHP 8.3.x
$WPCLI option get siteurl                   # 期望 https://www.packrivo.com
$WPCLI option get template                  # 期望 packrivo-static-bridge-1.2.38
$WPCLI option get stylesheet                # 期望 与上面相同（子主题未装）

# 首次安装的现状必须确认（这两条 greps 都要「没输出」）
$WPCLI theme list  --format=csv | grep '^packrivo-home-v4,' || echo "子主题未安装 → 首次安装"
$WPCLI plugin list --format=csv | grep '^packrivo-quote,'  || echo "报价插件未安装 → 首次安装"
$WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active

# 容器里要有 mysqldump，否则 wp db export 会失败（容器重建后会丢，所以每次上线都查一遍）
docker exec php sh -c 'command -v mysqldump || apk add --no-cache mariadb-client'
docker exec php mysqldump --version

# 数据库导出探针（第一次跑几乎一定失败，这是预期，见下面「两层补救」）：
$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes
docker exec php sh -c 'wc -c /tmp/probe.sql'
docker exec php rm -f /tmp/probe.sql

# 包目录
mkdir -p "$PKG"
```

**通过判据**：`docker ps` 里的 php/mysql/nginx 都在；`core version` = `7.1.4`；PHP = `8.3.x`；`template` = `packrivo-static-bridge-1.2.38`；子主题与报价插件都「未安装」；`mysqldump --version` 有输出；**导出探针三条判据全 PASS**（见下）。
若子主题或报价插件**已经存在**，说明不是首次安装，**停下**，本手册的 4/5 步与 9 步不适用。

**导出探针的三条判据（改成三条，全 PASS 才算过）**：

| # | 判据 | 怎么查 |
|---|---|---|
| 0k-1 | 导出字节数 **> 0** | `docker exec php sh -c 'wc -c < /tmp/probe.sql'` |
| 0k-2 | 导出里的 `CREATE TABLE` 条数 **= 库里 BASE TABLE 的条数** | 导出：`docker exec php grep -c 'CREATE TABLE' /tmp/probe.sql`；库：`$WPCLI db query "SHOW FULL TABLES" \| grep -c 'BASE TABLE'`（或 `$WPCLI db tables --all-tables-with-prefix \| wc -l`） |
| 0k-3 | 文件末尾有 `-- Dump completed` | `docker exec php tail -2 /tmp/probe.sql` |

> **0k-2 的写法是 2026-10-29 当场改正的，不要再退回去。** 当时先用 `wp db query "SELECT COUNT(*) … information_schema …" --skip-column-names` 取库里的表数，**这条返回空**（`wp db query` 对这个调用不吐值），拿空串去比就误判成 FAIL；同时 `SHOW TABLES | wc -l` 会多算一行表头（40 张表得 41）。正确做法：`SHOW FULL TABLES` 只数 `BASE TABLE` 行（去掉表头那一行），或直接用 `wp db tables --all-tables-with-prefix | wc -l`；两边必须一致才算对，口径不一致时先统一口径，不要直接把两个数字放一起比。

> **生产开着 WordPress 小版本自动更新，执行当天先核版本。** 2026-10-28 那次上线就是在窗口前被 WP 自动更新从 7.1.3 升到 7.1.4（增量包，**全深度改动 48 个 core 文件**、`$wp_db_version` 保持 61833）。注意 48 是全深度口径；只 `find -maxdepth 1` 扫 WP 根目录会数成 8 个，别拿 8 当全量。如果执行当天 `core version` 又不是本节写的值，**第 0 步判 FAIL，不要临场改判据**：先按 28t 的做法在本地用官方包对齐同一版本、完整重跑一轮演练，改完本节期望值再上生产。

> **导出探针每轮都要重跑，不能靠上一轮留下的包装。** 包装装在容器 `/usr/local/bin` 里、认证插件装在 `/usr/lib/mariadb/plugin/` 里，容器一重建就都没了；29v 起演练环境的 `reset.sh` 每次复位都会把这两样拆掉，保证第 0 步真的走一遍「报错 → 补救 → 重探」。探针仍然是 0 字节就当没通过，别往下做第 1 步。

### 0.1 补救块（两层，按顺序做，不要跳）

**第 1 层 · TLS**：探针报下面两种 `TLS/SSL error` 之一时，做一次容器内的客户端包装再重探。
> - `TLS/SSL error: SSL is required, but the server does not support it` —— `mysql` 侧没开 TLS、客户端是 mariadb 11.x 时的典型症状（28q 演练环境就是这种）。
> - `TLS/SSL error: self-signed certificate in certificate chain` —— `mysql` 侧开了 TLS 但证书是自签/链不全，mariadb 11.x 客户端拒绝。**生产 2026-10-29 实测就是这句**（MySQL 26.7.0 + 自签证书 + `mariadb-dump 11.4.13`）。

```bash
docker exec php sh -c 'cat > /usr/local/bin/mariadb-dump <<EOF
#!/bin/sh
exec /usr/bin/mariadb-dump "\$@" --skip-ssl
EOF
chmod +x /usr/local/bin/mariadb-dump'
docker exec php sh -c 'cat > /usr/local/bin/mariadb <<EOF
#!/bin/sh
exec /usr/bin/mariadb "\$@" --skip-ssl
EOF
chmod +x /usr/local/bin/mariadb'
docker exec php sh -c 'command -v mariadb-dump'
# 然后重跑上面的导出探针
```

要点：`--skip-ssl` 必须**追加在参数末尾**，因为 wp-cli 传的 `--no-defaults` 必须保持在第一位，放前面会被拒。

**第 2 层 · caching_sha2**：装上包装后如果探针还是 0 字节，并且报下面这条，说明缺认证插件（不是 TLS 问题），补装 connector 再重探：

```
mariadb-dump: Got error: 1045: "Plugin caching_sha2_password could not be loaded:
Error loading shared library /usr/lib/mariadb/plugin/caching_sha2_password.so: No such file or directory"
```

```bash
docker exec php sh -c 'ls /usr/lib/mariadb/plugin | wc -l'      # 生产首装时是 0
docker exec php sh -c 'apk add --no-cache mariadb-connector-c'
docker exec php sh -c 'ls /usr/lib/mariadb/plugin'               # 期望 5–6 个 .so，含 caching_sha2_password.so
# 然后重跑上面的导出探针 → 走下面三条判据
```

两层都过完，探针仍然 0 字节或报别的错 → **停下报告**，不要往下做第 1 步。装了什么包、装了哪个版本、插件目录前后各有几个文件，都记进上线日志。

### 0.2 从本机上传（**本机**执行，只在宿主机落地，不进 web 目录）

| 文件（本机仓库 `E:\破甲工作区\packrivo-backup\` 下的路径） | 文件数 | 大小 | SHA-256 |
|---|---|---|---|
| `deploy/packrivo-home-v4.zip` | 38 | 791,169 B | `3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6` |
| `deploy/packrivo-quote.zip` | 19（`demo/` 不进包） | 54,581 B | `096d0c8e81360e7fc616e5d46c96088fc3a65ceb32daf9b78cf1e1de0bc52d93` |
| `deploy/27e-quote-redirect.patch` | — | 647 B | 见第 6 步 |
| `deploy/patches-28m/packrivo-i18n-404-viewport.patch` | — | 1018 B | 见第 6 步 |
| `deploy/i18n-merged/`（11 份 `*-dict.json` + 11 份 `*-js.json` + `paths.json`） | 23 | — | 合并底稿 |
| `deploy/tools/merge-i18n.php` | 1 | 13,108 B | 第 2 步合并工具；`4316c4c8cdc618cd228d6b54b1af3e462973263149a8127b1bbc01d39be454fa`（28q 加了 0 字节文件容错） |
| `dsh-out/batch-01.json` … `batch-05.json`、`dsh-out/hubs-27i.json` | 6 | — | 第 3 步导入输入 |
| `scripts/product-buyers-27g/import.php` + `lib.php` | 2 | — | 第 3 步 |
| `scripts/product-buyers-27e/full-inventory.csv` + `products.json` + `third.json` + `fourth.json` | 4 | — | 第 3 步 `all134` 用 |
| `scripts/hubs-27i/import.php` | 1 | — | 第 3 步 |

```powershell
# 本机：按 RUNBOOK 的目录结构传到 $PKG（不要落在 web 可访问目录）
$P='E:\破甲工作区\packrivo-backup'
scp -r "$P\deploy\packrivo-home-v4.zip" "$P\deploy\packrivo-quote.zip" "$P\deploy\27e-quote-redirect.patch" netcup:/home/web/packrivo-packages/
scp -r "$P\deploy\patches-28m" "$P\deploy\i18n-merged" "$P\deploy\tools" netcup:/home/web/packrivo-packages/
# 6 个 JSON 必须落进 $PKG/dsh-out/（第 3 步的脚本按 <包根>/dsh-out/ 读，落包根会 Cannot read /pkg/dsh-out/batch-01.json）
scp -r "$P\dsh-out\batch-01.json" "$P\dsh-out\batch-02.json" "$P\dsh-out\batch-03.json" "$P\dsh-out\batch-04.json" "$P\dsh-out\batch-05.json" "$P\dsh-out\hubs-27i.json" netcup:/home/web/packrivo-packages/dsh-out/
scp -r "$P\scripts\product-buyers-27g" "$P\scripts\product-buyers-27e" "$P\scripts\hubs-27i" netcup:/home/web/packrivo-packages/scripts/
```

> **落点清单（2026-10-29 上线时踩过一次，照这个对）**：
> - `$PKG/*.zip`、`$PKG/27e-quote-redirect.patch` —— 包根
> - `$PKG/patches-28m/`、`$PKG/i18n-merged/`、`$PKG/tools/`、`$PKG/scripts/` —— 包根下同名子目录
> - **`$PKG/dsh-out/`（6 个 JSON）** —— 必须在 `dsh-out/` 子目录里，脚本读的是 `/pkg/dsh-out/batch-01.json`；落到包根时第 3 步 plan 会直接 `Cannot read /pkg/dsh-out/batch-01.json`
> - `scp` 之前先在宿主机 `mkdir -p "$PKG/dsh-out"`（`scp` 不会替你建目录）

上传后在**宿主机**核对：

```bash
sha256sum "$PKG"/*.zip
ls "$PKG"/merged 2>/dev/null; ls "$PKG"/i18n-merged | wc -l      # 期望 23
```

---

## 1. 备份（机器：**生产 SSH（宿主机）**）

**预计 10 分钟。这一步不改任何站点文件。**

```bash
# 1.1 定下本次的 LAUNCH 名，写进 CURRENT_LAUNCH —— 之后所有会话都从这个文件读，不再用 $(date)
LAUNCH=packrivo-launch-$(date +%Y%m%d-%H%M)
mkdir -p /home/web/backups
echo "$LAUNCH" > /home/web/backups/CURRENT_LAUNCH
BACKUP=/home/web/backups/$LAUNCH
mkdir -p "$BACKUP"/{mu,i18n,db,rollback}

# 1.2 mu 与 i18n：用 "/." 摊平，保证 $BACKUP/mu/<文件名> 与 $BACKUP/i18n/<文件名>
cp -a "$WP/wp-content/mu-plugins/." "$BACKUP/mu/"
cp -a "$I18N/."                     "$BACKUP/i18n/"
sha256sum "$WP/wp-content/mu-plugins/packrivo-i18n.php" \
          "$WP/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" > "$BACKUP/before-mu.sha256"

# 1.3 数据库：wp 在容器里，导出文件先落容器 /tmp，再拉回宿主机
$WPCLI db export /tmp/packrivo-before-launch.sql --skip-plugins --skip-themes
docker cp php:/tmp/packrivo-before-launch.sql "$BACKUP/db.sql"
docker exec php rm -f /tmp/packrivo-before-launch.sql

# 1.4 现状快照（回滚时的对照基准）
$WPCLI plugin list --format=csv > "$BACKUP/before-plugin-list.csv"
$WPCLI theme  list --format=csv > "$BACKUP/before-theme-list.csv"
$WPCLI option get template   > "$BACKUP/before-template.txt"
$WPCLI option get stylesheet > "$BACKUP/before-stylesheet.txt"
$WPCLI post list --post_type=page --post_status=publish --fields=ID,post_name,post_title --format=csv > "$BACKUP/before-pages.csv"
curl -s "$SITE$SITEMAP" > "$BACKUP/before-sitemap.xml"
curl -s "$SITE/robots.txt"        > "$BACKUP/before-robots.txt"

# 1.5 首次安装的书面记录（第 9 步、以及事后审计都看这一份）
cat > "$BACKUP/INSTALL-STATE.txt" <<EOF
launch=$LAUNCH
time=$(date -u)
theme_before=$($WPCLI option get stylesheet)
parent_before=$($WPCLI option get template)
child_theme_present=$(test -d "$WP/wp-content/themes/packrivo-home-v4" && echo yes || echo no)
quote_plugin_present=$(test -d "$WP/wp-content/plugins/packrivo-quote" && echo yes || echo no)
EOF
cat "$BACKUP/INSTALL-STATE.txt"
```

**通过判据（逐条机检）**

```bash
test -s /home/web/backups/CURRENT_LAUNCH && echo "CURRENT_LAUNCH ok"
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
test -f "$BACKUP/mu/packrivo-i18n.php"                       && echo "1a mu 文件到位"
test -f "$BACKUP/mu/packrivo-site-remediation-20261022.php"   && echo "1b mu 文件到位"
test -f "$BACKUP/i18n/de-dict.json"                           && echo "1c i18n 文件到位"
[ "$(ls "$BACKUP/i18n" | wc -l)" -ge 12 ]                     && echo "1d i18n 数量 ≥12"
test -s "$BACKUP/db.sql"                                      && echo "1e db.sql 非空"
[ "$(grep -c 'CREATE TABLE' "$BACKUP/db.sql")" -gt 0 ]        && echo "1f db.sql 含 CREATE TABLE"
[ "$(wc -l < "$BACKUP/before-mu.sha256")" -eq 2 ]             && echo "1g 两个 mu 哈希"
test -s "$BACKUP/before-sitemap.xml"                          && echo "1h sitemap 快照非空"
grep -q 'child_theme_present=no' "$BACKUP/INSTALL-STATE.txt"  && echo "1i 首装状态已记录"
```

全都打印出来才算过。**备份只留在服务器这台机器上，不下载到本机。**

**回滚**：本步不产生变更。上线确认成功后再删 `$BACKUP`（删之前先确认第 9 步不再需要）。

---

## 2. 字典与路径表（机器：**生产 SSH**；底稿与脚本第 0.2 步已上传）

**预计 20–30 分钟（含人工看冲突清单）。这是整个上线里唯一不能自动判定的一步。**

原则：**以当天新读的生产字典为底**，把本轮的 134 产品页、8 个主页面、60 条界面词、28k 的 207 条、28m 的报价页、28n 的 `Instant quote` / `Sample kit` 一起合并；**同一键两边都有值时生产优先**，冲突清单必须人工看过再装。

`merge-i18n.php` 要用 PHP 跑，而 PHP 只在容器里 → 先把包和当天字典推进容器，跑完再把结果拉回宿主机。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 2.1 当天生产字典快照（落在宿主机，平铺）
cp -a "$I18N" "$BACKUP/i18n-today"
ls "$BACKUP/i18n-today" | head
grep -c '"' "$BACKUP/i18n-today/de-dict.json"        # 粗略确认不是空文件

# 2.2 包与字典推进容器（$PKG 在宿主机，容器看不见）
docker exec php mkdir -p "$PKGC"
docker cp "$PKG/." php:"$PKGC/"
docker cp "$BACKUP/i18n-today" php:/bk-i18n-today      # 合并的 --prod 输入

# 2.3 合并（生产优先、冲突写 conflicts.json）
docker exec php mkdir -p /pkg/merged
docker exec php php "$PKGC/tools/merge-i18n.php" \
    --prod=/bk-i18n-today \
    --local="$PKGC/i18n-merged" \
    --out="$PKGC/merged" \
    --require=/get-a-quote/

# 2.4 结果拉回宿主机，人工看冲突清单
mkdir -p "$PKG/merged"
docker cp php:"$PKGC/merged/." "$PKG/merged/"
cat "$PKG/merged/conflicts.json"

# 2.5 校验（容器内跑，路径都在容器侧）。每条都打印 PASS:/FAIL:，任一条 FAIL → 本步 FAIL
docker exec php sh -c 'test -f /pkg/merged/paths.json && echo "PASS: paths.json 存在" || { echo "FAIL: paths.json 缺失（常见原因：当天 i18n 快照里 *-js.json 为空或坏 JSON，合并工具没产出 js 与路径表）"; exit 1; }'
docker exec php sh -c 'grep -q "\"/get-a-quote/\"" /pkg/merged/paths.json && echo "PASS: paths 含 /get-a-quote/" || { echo "FAIL: paths.json 缺 /get-a-quote/"; exit 1; }'
docker exec php sh -c 'php -r "\$p=json_decode(file_get_contents(\"/pkg/merged/paths.json\"),true); exit((is_array(\$p)&&count(\$p)>=146)?0:1);" && echo "PASS: paths 条数 >= 146" || { echo "FAIL: paths 条数 < 146"; exit 1; }'
docker exec php sh -c '[ "$(ls /pkg/merged/*-dict.json 2>/dev/null | wc -l)" = "11" ] && echo "PASS: dict = 11" || { echo "FAIL: dict 文件数 != 11"; exit 1; }'
docker exec php sh -c '[ "$(ls /pkg/merged/*-js.json   2>/dev/null | wc -l)" = "11" ] && echo "PASS: js = 11"   || { echo "FAIL: js 文件数 != 11";   exit 1; }'
docker exec php sh -c 'for f in /pkg/merged/*.json; do php -r "\$d=json_decode(file_get_contents(\$argv[1]),true); exit((json_last_error()===JSON_ERROR_NONE)?0:1);" "$f" || echo "坏 JSON: $f"; done' > /tmp/badjson.txt
if [ -s /tmp/badjson.txt ]; then echo "FAIL: 有坏 JSON"; cat /tmp/badjson.txt; else echo "PASS: 无坏 JSON"; fi
rm -f /tmp/badjson.txt

# 2.6 装上去（宿主机侧，只覆盖同名文件；conflicts.json 不要拷进 i18n）
cp "$PKG/merged"/*-dict.json "$I18N/"
cp "$PKG/merged"/*-js.json   "$I18N/"
cp "$PKG/merged/paths.json"  "$I18N/"
ls -l "$I18N" | head
```

**通过判据**

- `*-dict.json` = **11**、`*-js.json` = **11**；
- 每个 JSON 都能 `json_decode`（判据看 `json_last_error()`，不看返回值真假——`conflicts.json` 是 `[]` 也合法，空数组在 PHP 里是 falsy，用 `or exit(1)` 会把它误判成坏 JSON），没有「坏 JSON」行；
- `paths.json` 含 `"/get-a-quote/"`，条目数 ≥ 146；
- **2.5 的每一条都要打印出 `PASS:`**；任一条打 `FAIL:` → **本步 FAIL**：按下面「回滚」把字典恢复回去并**停止**，修好合并输入后重跑第 2 步。不得把 exit=0 的静默失败当通过（2026-10-28 演练实测：`paths.json` 缺失时整步仍 exit=0）；
- `conflicts.json` 已人工逐条看过：凡是被生产值覆盖的键，都是我们**这次不打算改**的；
- 装完后 `/de/` 仍正常：`curl -s "$SITE/de/" | grep -c 'lang="de"'` ≥ 1。

**回滚**（宿主机侧，按文件逐个覆盖回去，绝不整目录塞进去）：

```bash
cp -a "$BACKUP/i18n/." "$I18N/"
```

---

## 3. 内容：134 个产品页 + 8 个主页面（机器：**生产 SSH**）

**预计 15–25 分钟（134 页批量导入）。**

导入脚本是 `wp eval-file` 形式，命令形态固定为 `<plan|apply|verify|rollback-check|rollback> <package-root> <expected-site> <rollback-json> [all134]`；`package-root` 与 `rollback-json` 都是**容器内路径**（`$PKGC`、`$PKGC/rollback/`），因为解释执行这个脚本的是容器里的 PHP。

- `plan` = dry-run，只读不写（27g 打印 `input_pages=134 preflight=PASS writes=0`，27i 打印 `hub_pages=8`）；
- `apply` = 首次在生产上**独占创建** rollback 文件（写入失败就不动数据库），再开数据库事务写入；站点地址、输入哈希、正文漂移任一不符都会中止；
- 27g 生产必须用 `all134` 范围（130 新页 + 27e 的 4 页，同一备份同一事务）。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
docker exec php mkdir -p "$PKGC/rollback"          # 2.2 已把包灌进容器

# 3.0 应用前守卫：/pkg/rollback 里若已有 JSON，它的 site 必须等于本次的 $SITE。
# 残留他站/上一轮的 JSON 会让 apply 以 site/input 不符拒写，所以先拦一次，别等 apply 报错。
for f in $(docker exec php sh -c 'ls /pkg/rollback/*.json 2>/dev/null'); do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一致" \
    || { echo "FAIL: $f 非本轮产物（site 与 $SITE 不一致），先清空 /pkg/rollback 再重跑第 3 步"; exit 1; }
done

# 3.1 dry-run
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"

# 3.2 真跑，跑完立刻把 rollback 文件拉回宿主机存进 $BACKUP
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
docker cp php:"$PKGC/rollback/." "$BACKUP/rollback/"
ls -l "$BACKUP/rollback/"
```

**通过判据**

- `plan` 输出 `input_pages=134 preflight=PASS writes=0` 与 `hub_pages=8 preflight=PASS`；
- `apply` 的**总数**对得上：`apply_updated + already_current = 134`（27g）、`= 8`（27i）。**不要要求 `apply_updated` 单独等于 134**——重复执行时 `already_current` 会吃掉其中一部分（28q 实测就是 130 + 4、7 + 1）；
- 演练环境另加一条：`apply_updated > 0`。等于 0 说明演练库的内容没有被复位，导入从未真实写入，**这一轮演练作废**；
- `apply` 输出 `URL_and_body_check=PASS`（27g）；27i 输出 `H1_body_URL_check=PASS` 与 `H1_body_URL_unchanged=8/8`；
- 出现 `Backup input/site mismatch` / `Original backup site/input mismatch` / `Expected-site guard failed` → **本步 FAIL**：先查 `/pkg/rollback` 有没有他站残留（3.0 守卫本该拦住），按下面「回滚」处理并**停止**；
- 27g `verify` 输出 `URL_and_non_H1_body_unchanged=134/134`；27i `verify` 输出 `metadata_mismatches=0`，并且有 `H1_body_URL_unchanged=8/8` 与 `inquiry_description_preserved=yes`（两个脚本的 verify 打的字段不一样，拿 27i 的字段去判 27g 会一直 FAIL）；
- **判据要在对应命令的输出里查（2026-10-29 当场改过）**：`H1_body_URL_unchanged=8/8` 是 **27i 的 `verify`** 打的，去 `apply` 的日志里 grep 会查不到 → 误判 FAIL。查之前先确认这行属于 `plan` / `apply` / `verify` 哪一个，再落到对应的输出文件上；查错了只改查法，不要重跑已经跑对的步骤；
- `$BACKUP/rollback/` 里两个 JSON 都在（27g 约 1.2 MB，含全部原值）：
  ```bash
  test -s "$BACKUP/rollback/rollback-27g.json" && echo "27g rollback ok"
  test -s "$BACKUP/rollback/rollback-27i.json" && echo "27i rollback ok"
  ```
- 抽 3 页核对新标题（每页 `grep -c '<title>'` = 1）：
  ```bash
  curl -s "$SITE/product-drawer-jewelry-box/" | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/products/"                   | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/jewelry-boxes/"              | grep -o '<title>[^<]*</title>'
  ```

**回滚**（先把宿主机那份 rollback 文件推回容器，再 rollback-check → rollback）：

```bash
docker cp "$BACKUP/rollback/rollback-27i.json" php:"$PKGC/rollback/"
docker cp "$BACKUP/rollback/rollback-27g.json" php:"$PKGC/rollback/"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
```

---

## 4. 报价插件（**首次安装**，机器：**生产 SSH**）

**预计 5 分钟。** 生产上现在没有 `packrivo-quote`，所以这是新装，不是覆盖。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
docker cp "$PKG/packrivo-quote.zip" php:"$PKGC/packrivo-quote.zip"
docker exec php sha256sum "$PKGC/packrivo-quote.zip"      # 必须 096d0c8e…

$WPCLI plugin install "$PKGC/packrivo-quote.zip" --force
$WPCLI plugin activate packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote     # 状态必须是 active
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"         # 200
```

**通过判据**

- `plugin list` 里 `packrivo-quote` 是 `active`；
- 站点 200；
- 此时 `/get-a-quote/` 仍会被 mu 里那条老 301 顶到 `/inquiry/`（第 6 步才修），**这是预期的空档期**，不算失败；
- 价格只有第 6 步打完补丁后才能验（8.2）。

**回滚**（两件事分开写，不用 `&&` 串：删掉了就回不来）：

```bash
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除（回到首装前状态）"
```

---

## 4.5 建报价页 `/get-a-quote/`（**首次安装**，机器：**生产 SSH**）

**预计 2 分钟。放在第 4 步装完插件之后、第 6 步打补丁之前。**

为什么必须有这一步：报价插件 `packrivo-quote` 只提供短代码 `[packrivo_quote]`，**它自己不会建页面**（插件里只有 `register_activation_hook` → `flush_rewrite_rules()`，没有 `add_rewrite_rule`、没有 `template_include`）。生产上线前没有这个页面，是 mu 里那条老 301 把 `/get-a-quote/` 顶到 `/inquiry/` 遮住了；第 6 步 27e 删掉 301 之后就会露出 404（2026-10-29 就是这样 FAIL 的）。演练库以前自带这个页面（ID 3220），所以演练一直没暴露——29v 起 `reset.sh` 会把它删掉。

**4.5.1 守卫：任何状态都不能已有 slug 为 `get-a-quote` 的页面**

```bash
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 直接查库（含 draft / trash / revision，全 post_type）
$WPCLI db query "SELECT ID,post_type,post_status,post_name FROM wp_posts WHERE post_name='get-a-quote'"
# 计数（注意：wp db query 不要加 --skip-column-names，它对这个调用返回空；取输出里最后一个纯数字行）
$WPCLI db query "SELECT COUNT(*) AS c FROM wp_posts WHERE post_name='get-a-quote'"
# 再走一遍 wp 接口，把草稿和回收站都显式列上
$WPCLI post list --post_type=any --post_status=any,draft,pending,private,future,trash \
                 --name=get-a-quote --fields=ID,post_type,post_name,post_status --format=csv
```

两条都要是 **0 行 / 0 条** 才继续。**已经有就停下报告，不要覆盖、不要改 slug 硬建。**

**4.5.2 建页**

```bash
$WPCLI post create --post_type=page --post_title='Get a Quote' --post_name=get-a-quote \
       --post_content='[packrivo_quote]' --post_status=publish --comment_status=closed --porcelain
# 返回的数字就是页面 ID（生产 2026-10-29 是 3383）
echo "get_a_quote_page_id=<ID> created=$(date -u +%FT%TZ)" >> "$BACKUP/INSTALL-STATE.txt"
tail -2 "$BACKUP/INSTALL-STATE.txt"
```

**通过判据**

| # | 判据 | 怎么查 |
|---|---|---|
| 4.5a | `post_name` **正好**是 `get-a-quote`（不是 `get-a-quote-2`） | `$WPCLI post get <ID> --field=post_name` |
| 4.5b | `post_content` **正好**是 `[packrivo_quote]` | `$WPCLI post get <ID> --field=post_content` |
| 4.5c | `post_status` = `publish` | `$WPCLI post get <ID> --field=post_status` |
| 4.5d | ID 已记进 `$BACKUP/INSTALL-STATE.txt` | `grep get_a_quote_page_id "$BACKUP/INSTALL-STATE.txt"` |

此时 `/get-a-quote/` 仍然 301 到 `/inquiry/`（27e 还没打），**这是预期空档期**，不算失败；第 6 步打完补丁它才会 200 并渲染出报价组件。

**回滚**

```bash
$WPCLI post delete <ID> --force          # ID 从 INSTALL-STATE.txt 读
$WPCLI post list --post_type=page --name=get-a-quote --post_status=any,draft,trash --format=count   # 期望 0
```

---

## 5. 子主题（**首次安装**，机器：**生产 SSH**）

**预计 5 分钟。**

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 5.1 先确认父主题没被换掉
$WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active     # 必须 1.2.38

# 5.2 装 + 启用
docker cp "$PKG/packrivo-home-v4.zip" php:"$PKGC/packrivo-home-v4.zip"
docker exec php sha256sum "$PKGC/packrivo-home-v4.zip"    # 必须 3279DC61…
$WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
$WPCLI theme activate packrivo-home-v4

# 5.3 核对
$WPCLI option get stylesheet      # 期望 packrivo-home-v4
$WPCLI option get template        # 期望 packrivo-static-bridge-1.2.38
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"      # 200
```

**通过判据**

- 父主题仍是 `packrivo-static-bridge-1.2.38`（若变了**停下**，先确认父子兼容）；
- `option get stylesheet` = `packrivo-home-v4`，`option get template` = 父主题目录名；
- 首页 200。

**回滚**（先把主题切回父主题，再删目录；两条分开写）：

```bash
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除（回到首装前状态）"
curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"
```

---

## 4.6 指定页面模板 `_wp_page_template`（**首次安装**，机器：**生产 SSH**）

**预计 1 分钟。编号沿用任务单的 4.6，但机器上的顺序是「第 5 步装完子主题之后、第 6 步打补丁之前」——模板文件 `template-products-v4.php` 是随子主题包一起装进来的，第 5 步之前跑 4.6.1 的守卫必然失败（文件不存在），这是预期。**

为什么必须有这一步：子主题给 `/products/` 准备了 `template-products-v4.php`，但**页面本身要有一条 `_wp_page_template` meta 才会用它**。没有这条 meta，WordPress 走 `page.php` 默认模板，把页面正文里那段 legacy HTML 原样吐出来——筛选控件挤成一串文字、内容贴左边缘、产品图撑满整宽。2026-10-29 上线后 `/products/` 就是这样错乱的，`2026-10-29w` 的生产热修复补的就是这一处（页面 8，原值**不存在**）。这类「本地库里有、RUNBOOK 没写、演练库又是从本地复制的」遗漏，和 4.5 的报价页是同一类。

> 生产 2026-10-29 实测：`post meta get 8 _wp_page_template` → 空、`rc=1`（根本没有这条 meta）；页面 meta 一共 10 条，`_packrivo_native_page` / `_packrivo_canonical` / `_yoast_wpseo_focuskw` / `_pkpw_legacy_body` 都在，唯独没有模板。全站**只有这一页**需要指定模板（`post list --meta_key=_wp_page_template --meta_compare='!=' --meta_value=default` 命中为空）。

**4.6.1 守卫：页面 8 存在、模板文件存在**

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

$WPCLI post get 8 --fields=ID,post_type,post_name,post_status --format=csv
#   期望 post_type=page、post_name=products、post_status=publish；对不上就停下报告，不要改别的页面
ls -l "$WP/wp-content/themes/packrivo-home-v4/template-products-v4.php"
#   模板在**子主题**目录里（父主题没有 template-*.php，这是正常的）；文件不在就停下（第 5 步装的主题包不对）
```

**4.6.2 先把原值记进 `INSTALL-STATE.txt`（回滚只认这里）**

```bash
V=$($WPCLI post meta get 8 _wp_page_template 2>/dev/null); rc=$?
[ -z "$V" ] && V=EMPTY
echo "page8_template_before=$V (rc=$rc) at=$(date -u +%FT%TZ)" >> "$BACKUP/INSTALL-STATE.txt"
grep page8_template_before "$BACKUP/INSTALL-STATE.txt"
```

`rc=1` + 空 = **本来没有这条 meta**（2026-10-29 生产就是这样）；此时回滚是 `post meta delete`，不是「改回某个值」。

**4.6.3 执行**

```bash
$WPCLI post meta update 8 _wp_page_template template-products-v4.php
$WPCLI post meta get 8 _wp_page_template            # 期望回显 template-products-v4.php
```

**通过判据（全 PASS 才算过；HTTP 一律带 `?cb=$(date +%s)` 破缓存）**

| # | 判据 | 怎么查 |
|---|---|---|
| 4.6a | meta 值正好 `template-products-v4.php` | `$WPCLI post meta get 8 _wp_page_template` |
| 4.6b | `/products/` 的 `<body class>` 含 `page-template-template-products-v4` | `curl -s "$SITE/products/?cb=$(date +%s)" \| grep -o '<body[^>]*>'` |
| 4.6c | 页面出现 `Every structure we can source` | `curl -s "$SITE/products/?cb=$(date +%s)" \| grep -c 'Every structure we can source'`（期望 1） |
| 4.6d | `/de/products/`、`/ar/products/` 也是这个模板（文案随语种翻译，别拿英文句子判） | 同上，只判 `page-template-template-products-v4` |
| 4.6e | 375 / 768 / 1024 三个宽度**无横向溢出** | 真实浏览器：`document.documentElement.scrollWidth === window.innerWidth`；没有浏览器就停下报告，别用肉眼代替 |
| 4.6f | `/`、`/blog/` 仍然 200；`/get-a-quote/` **此刻仍是 301**（27e 补丁在第 6 步才打，这是预期空档，第 6 步之后才该变 200） | `curl -s -o /dev/null -w '%{http_code}\n' "$SITE/blog/?cb=$(date +%s)"` |

4.6e 的现成跑法（本机 `playwright-core` + 本机 Chrome，`2026-10-29w` 用它给生产做过三宽度检查）：三个宽度各开一次 `/products/?cb=<ts>`，`evaluate` 取 `document.documentElement.scrollWidth` 与 `window.innerWidth`，两者相等且没有越界元素才算过。

**回滚**

```bash
V=$(sed -n 's/^page8_template_before=\(.*\) (rc=.*/\1/p' "$BACKUP/INSTALL-STATE.txt" | tail -1)
if [ "$V" = "EMPTY" ] || [ -z "$V" ]; then
  $WPCLI post meta delete 8 _wp_page_template        # 原值本来不存在
else
  $WPCLI post meta update 8 _wp_page_template "$V"
fi
$WPCLI post meta list 8 --keys=_wp_page_template --format=csv   # EMPTY 时只剩表头
curl -s "$SITE/products/?cb=$(date +%s)" | grep -o 'page-template-[a-z0-9-]*' | head -1   # 回到 page-template-default
```

---

## 6. mu 补丁（机器：**生产 SSH**）

**预计 10 分钟。两个补丁落在不同文件上，各自独立回滚。顺序固定：先 `27e-quote-redirect.patch`，再 `packrivo-i18n-404-viewport.patch`。**

`git apply` 在宿主机跑（宿主机有 git），`php -l` 在容器里跑。

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 6.1 27e：把 get-a-quote 从旧别名列表里删掉（只删这一项）
cd "$WP/wp-content/mu-plugins"
sha256sum packrivo-site-remediation-20261022.php
#   生产基线: ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d
#   与基线不一致就先 diff 生产文件与本机基线，确认改动范围再决定，不要盲目打补丁
git apply -p1 --check "$PKG/27e-quote-redirect.patch" && echo CHECK-OK
git apply -p1         "$PKG/27e-quote-redirect.patch"
docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php"

# 6.2 404 viewport：给「本页尚未翻译」的 404 补 <meta name="viewport">
cd "$WP"                      # 这个补丁的路径前缀是 wp-content/mu-plugins/，要在 WP 根执行
sha256sum wp-content/mu-plugins/packrivo-i18n.php
#   生产 2026-10-29 实测：打前 a2a7bdf3…，打后 f7d95f35…（只截了前 8 位，全值见 28u 的 compare-cols 日志）
#   这个文件**没有**像 27e 那样的固定基线：演练库里的副本是 3bc325503b16…（28m 之前的版本），
#   与生产那份不是同一版，但同一份补丁两边都能干净打上。所以判据是 git apply --check 通过，
#   不是"哈希等于某个值"；打完只要哈希变了就算落盘。
git apply -p1 --check "$PKG/patches-28m/packrivo-i18n-404-viewport.patch" && echo CHECK-OK
git apply -p1         "$PKG/patches-28m/packrivo-i18n-404-viewport.patch"
docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-i18n.php"
```

**通过判据**

| # | 判据 | 怎么查 |
|---|---|---|
| 6a | 6.1 打补丁**之前**的 `sha256sum` = 基线 `ac0a27538c428f2175e9596bd21f27be47069aaf57cbeabf8892ae456e2f471d` | 打之前先 `sha256sum`，与基线不一致就先 diff 再决定，不要盲目打 |
| 6b | 6.1 打补丁**之后**的 `sha256sum` ≠ 基线（说明补丁真的落盘了） | 打完再 `sha256sum` 一次 |
| 6c | 两个 `git apply --check` 都 `CHECK-OK`，两个 `php -l` 都 `No syntax errors detected` | 见上面命令输出；`--check` 失败**不要强打** |
| 6d | `/get-a-quote/` = **200**（不再是 301） | `curl -s -o /dev/null -w '%{http_code}' "$SITE/get-a-quote/?cb=$(date +%s)"` |
| 6e | `/de/get-a-quote/` = **200** | 同上，换成 `/de/get-a-quote/` |
| 6f | 报价页里有报价组件（短代码真的渲染出来了） | `curl -s "$SITE/get-a-quote/?cb=$(date +%s)" \| grep -c 'pkq-root'` → 期望 ≥ 1；`/de/get-a-quote/` 同样一条 |
| 6g | 语种 404 有 viewport | `curl -s "$SITE/ar/2026/10/26/jewelry-pouch-box-customer-handoff/?cb=$(date +%s)" \| grep -c 'name="viewport"'` → 期望 1 |

> **6b 的写法是 2026-10-29 当场改的**：那一轮拿「打补丁**前**」抓的哈希变量去比「是否已变」，当然相等 → 误判 FAIL。要判「补丁落盘」就必须**打完再取一次哈希**。
>
> **6d/6e 不过先查 4.5**：这两条 200 的前提是 `/get-a-quote/` **页面存在**（4.5 建的）。2026-10-29 首装就是在这一步 FAIL——补丁打对了，但生产从来没有这个页面，27e 把 301 删掉后直接 404。判 200 之前先确认 `$WPCLI post list --post_type=page --name=get-a-quote --format=count` = 1。
>
> **URL 判据一律带 `?cb=` 破缓存**：Cloudflare 会缓存 301/404，拿裸 URL 打两次会读到上一次的缓存结果（28u 判 6g 时就被缓存的 404 页骗成 0）。

**回滚**（两个独立，读的是第 1 步摊平后的 `$BACKUP/mu/<文件名>`）：

```bash
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"   # 回滚 27e
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"   # 回滚 404 补丁
sha256sum -c "$BACKUP/before-mu.sha256"
```

---

## 6.5 · 字体路径补丁（29w，机器：**生产 SSH**）

29w 做了这个补丁但当时没写进本手册，29z §3c 才发现漏项。补丁把 Inter woff2 的 URL 从
`get_stylesheet_directory_uri()` 换成 `get_template_directory_uri()`：字体文件在**父主题**
`packrivo-static-bridge-<ver>/packrivo-static/assets/packrivo-inter-latin-v3.woff2`（34,952 B），
子主题里没有这份文件，所以用 stylesheet 目录会 404。

**先看现状再决定打不打**（29z 实测生产返回 200，走的是子主题路径 + 缓存；补丁是把这条链钉死）：

```bash
export no_proxy='*' NO_PROXY='*'
CURL="curl -sS -k --resolve www.packrivo.com:443:127.0.0.1"   # -k：源站是 CF Origin CA 证书；直连源站，不读 Cloudflare 缓存
$CURL https://www.packrivo.com/ | grep -o 'https://[^"]*packrivo-inter-latin-v3\.woff2' | head -2
$CURL -o /dev/null -w 'code=%{{http_code}} type=%{{content_type}} size=%{{size_download}}\n' \
  https://www.packrivo.com/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2
```

**打补丁**（幂等：第二次 dry-run 会报 already applied）：

```bash
sha256sum "$PKG/packrivo-wave2-font-path.patch"   # 必须 022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d
docker cp "$PKG/packrivo-wave2-font-path.patch" php:/tmp/font-path.patch
docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w"
docker exec php sh -c "cd $WPC/wp-content/mu-plugins && patch -p1 --dry-run < /tmp/font-path.patch"
docker exec php sh -c "cd $WPC/wp-content/mu-plugins && patch -p1 < /tmp/font-path.patch"
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # 打过以后 73d43ba0262a9d74249b19a46380bc5c338f94bd9e0776baa46eddf074c9f259
```

**判据**：`grep -n 'PACKRIVO_W2_FONT_URL' packrivo-wave2.php` 里那行变成 `get_template_directory_uri()`；
首页 HTML 里的字体 URL 指向 `/wp-content/themes/packrivo-static-bridge-<ver>/packrivo-static/assets/...woff2`；
`$CURL -I` 该 URL 返回 **200** 且 `content-type: font/woff2`、`content-length: 34952`。

**回滚**：`docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w $WPC/wp-content/mu-plugins/packrivo-wave2.php"`，
再 `$WPCLI cache flush`。

## 7. 清缓存（机器：**本机**（Cloudflare）+ **生产 SSH**（transient））

**预计 5 分钟。**

> **Cloudflare 这一段：负责人亲自执行。** 凭据只存在 `E:\破甲工作区\i18n\.cf.env`，本手册只引用路径、不写任何值；执行时不要把文件内容 `cat`/`echo` 出来，也不要把 token 贴进聊天、工单或日志。

```bash
# 本机（负责人亲自执行）：Cloudflare 全站 purge + 12 路径 × 11 语种点名清 + 自动校验
python E:\破甲工作区\i18n\cf_purge.py

# 生产 SSH（宿主机）：清报价插件的 12h pricing transient
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"     # 有对象缓存插件时生效，没有就如实记录
```

**通过判据**

- `cf_purge.py` 结尾输出 `仍过期: 无 —— 全部同步`；
- `curl -sI "$SITE/de/" | head -5` 返回 200；
- 页面语言标记正确（`<html lang="de">`）。

**回滚**：缓存清了不需要回滚。

---

## 8. 上线后复测清单（机器：**本机浏览器** + **生产 SSH** 抽查）

**预计 20–30 分钟。逐条照做，任何一条不过就按对应步回滚，不要「先上着」。**

| # | 检查项 | 怎么做 | 通过判据 |
|---|---|---|---|
| 8.1 | title / H1 / 描述（首页、`/products/`、1 个产品页 × en / ar / de） | 浏览器打开 9 个 URL，看标签页标题、页面 H1、右键查看 `<meta name="description">` | 三种语种都是新的、各自语言的标题；H1 与标题不冲突；描述非空 |
| 8.2 | 报价价格 | 浏览器打开 `/get-a-quote/?style=rigid&qty=500` 和 `/ar/get-a-quote/` | 单价 **$2.83**、合计 **$1,417.43**（mailer $2.48/$1,239.04、sticker $0.96/$479.63、shipping $2.31/$1,156.69 同样逐格一致） |
| 8.3 | 询盘页 | `/inquiry/` 和 `/inquiry/?product=product-drawer-jewelry-box` | 字段齐全（姓名、邮箱、数量、产品）；带 `?product=` 时产品已预选；**只读不提交** |
| 8.4 | 横向溢出 | 375 / 768 / 1024 三个宽度各看首页、`/products/`、1 个产品页、`/get-a-quote/`、`/ar/` | 都没有横向滚动条（`document.documentElement.scrollWidth` = 视口宽）；28p 起 768/1024 也在范围内 |
| 8.5 | 每页 1 个 title | `curl -s <URL> \| grep -c '<title>'`，抽 5 个 URL | 每个都是 **1** |
| 8.6 | sitemap / robots 没变 | `curl -s "$SITE$SITEMAP"`、`robots.txt`，与第 1 步的 `before-*.xml/txt` diff | sitemap 仍有语种子图；`robots.txt` 与上线前逐字一致 |
| 8.7 | 语种报价按钮 | en / ar / de / ja 首页与 1 个产品页，看页头与页脚的报价按钮 | 文字是各自语言（`عرض سعر فوري` / `Sofortangebot` / `すぐに見積もり`），链接都指向 `/{lang}/get-a-quote/` |

固定命令部分：

```bash
BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)

# 8.5 每页 1 个 title（抽 5 个 URL）
for u in "$SITE/" "$SITE/products/" "$SITE/de/" "$SITE/ar/" "$SITE/product-drawer-jewelry-box/"; do
  echo -n "$u  title数="; curl -s "$u" | grep -c '<title>'
done

# 8.6 sitemap / robots 与第 1 步快照对比
diff "$BACKUP/before-sitemap.xml" <(curl -s "$SITE$SITEMAP") && echo "sitemap 未变"
diff "$BACKUP/before-robots.txt"  <(curl -s "$SITE/robots.txt")       && echo "robots 未变"
```

**回滚**：本步是纯检查；哪条不过就回滚对应步——8.1/8.5/8.6 → 第 3、2 步；8.2/8.7 → 第 4、6 步；8.3 → 第 5 步；8.4 → 第 5、6 步。

---

## 9. 整体回滚（逆序，机器：**生产 SSH**）

**预计 15 分钟。开局先取回本次 LAUNCH，不要重新生成。**

```bash
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
PKGC=/pkg
SITE=https://www.packrivo.com
SITEMAP=/sitemap_index.xml    # Yoast 站点地图；若站点只用 WP 核心地图就改成 /wp-sitemap.xml
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)     # ← 固定来源，绝不用 $(date) 重算
BACKUP=/home/web/backups/$LAUNCH
echo "回滚目标: $BACKUP"; test -f "$BACKUP/db.sql" && echo "备份在位"
```

```bash
# 9.8 报价页：按 INSTALL-STATE 里记的 ID 删掉（顺序在第 9.6 之前）
#     这一页是 4.5 建的，回滚到首装前状态就必须删掉，否则站点留着一个只在本次上线存在的页面
GQ_ID=$(sed -n 's/^get_a_quote_page_id=\([0-9][0-9]*\).*/\1/p' "$BACKUP/INSTALL-STATE.txt")
echo "报价页 ID = ${GQ_ID:-（没找到，先查 $BACKUP/INSTALL-STATE.txt）}"
test -n "${GQ_ID:-}" && $WPCLI post delete "$GQ_ID" --force
$WPCLI post list --post_type=page --name=get-a-quote --post_status=any,draft,trash --format=count   # 期望 0

# 9.7.5 页面模板（4.6 设的 meta）：按 INSTALL-STATE 里记的原值恢复；原值是 EMPTY 就删掉
#    顺序：在 9.8 之后、9.6 之前——删模板要早于回滚子主题，否则中间那段页面还是新模板套旧主题
TB=$(sed -n 's/^page8_template_before=\(.*\) (rc=.*/\1/p' "$BACKUP/INSTALL-STATE.txt" | tail -1)
echo "页面 8 模板原值 = ${TB:-（没找到，先查 $BACKUP/INSTALL-STATE.txt）}"
if [ "${TB:-}" = "EMPTY" ] || [ -z "${TB:-}" ]; then
  $WPCLI post meta delete 8 _wp_page_template 2>/dev/null || echo "本来就没有这条 meta"
else
  $WPCLI post meta update 8 _wp_page_template "$TB"
fi
$WPCLI post meta list 8 --keys=_wp_page_template --format=csv    # EMPTY 时只剩表头

# 9.7 缓存：无需回滚（重新生成即可）；想立即回旧内容就先清一次
#     python E:\破甲工作区\i18n\cf_purge.py      ← 本机，负责人亲自
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"

# 9.6 两个 mu 补丁（读 $BACKUP/mu/<文件名>，摊平后的路径）
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"

# 9.5 子主题：先切回父主题，再删（两条分开，不用 &&）
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除"

# 9.4 报价插件：先停用，再删
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除"

# 9.3 内容（134 产品页 + 8 主页面，先 rollback-check 再 rollback）
docker cp "$BACKUP/rollback/rollback-27i.json" php:"$PKGC/rollback/"
docker cp "$BACKUP/rollback/rollback-27g.json" php:"$PKGC/rollback/"

# 9.3 回滚前守卫：推回去的两个 JSON 必须都是本轮第 3 步生成的（site 与本轮一致），
# 否则 rollback 会以 site/input 不符拒写，而那时已经删了主题和插件，损失更大。
for f in /pkg/rollback/rollback-27g.json /pkg/rollback/rollback-27i.json; do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一致" \
    || { echo "FAIL: $f site 与 $SITE 不一致"; exit 1; }
done
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
$WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134

# 9.2 字典与路径表（按文件覆盖回去）
cp -a "$BACKUP/i18n/." "$I18N/"
```

最后再跑一遍 8.1 / 8.2 / 8.6，确认回到上线前状态。

**判据**：`sha256sum -c "$BACKUP/before-mu.sha256"` 全 OK；`option get stylesheet` 回到 `packrivo-static-bridge-1.2.38`；`plugin list | grep packrivo-quote` 无输出；27g/27i `rollback-check` 输出 `rollback_eligible` 且 rollback 后 `verify` 的 `metadata_mismatches=0`；8.1/8.2/8.6 与上线前一致。

**最后手段**（分步回滚都不灵时）：把数据库整个换成第 1 步导出的 `db.sql`。

> **这一招只回滚数据库。** 上线后新增的询盘、表单提交、评论、订单会一起消失——28r 实测：导入前新建的草稿 `28r-canary`，导入后查不到。用之前先确认这段时间没有新数据，或者先把新数据单独导出。
> **它不回滚文件。** 子主题目录、插件目录、mu 文件、i18n 字典仍是「上线后」的版本，所以最后手段必须和 9.6 / 9.5 / 9.4 / 9.2 一起做，顺序见 L3。

**前置检查（必做）**：`wp db import` 和 `wp db export` 一样走 `mariadb` 客户端、同样带 `--no-defaults`，**没有第 0.1 步那个客户端包装就会失败**。28r 实测：拆掉包装时 `db import` 退出码 1（导入没发生——表数仍是 40、`option get siteurl` 仍读得到，数据库没被导坏）；装上包装后同一份 `db.sql` 导入成功。所以先重跑 0.1 的导出探针确认包装在位：

```bash
$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes
docker exec php sh -c 'wc -c /tmp/probe.sql'      # 必须 > 0；若是 0 或报 TLS/SSL error，按 0.1 的补救块把包装装上再重探
docker exec php rm -f /tmp/probe.sql
```

```bash
LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)
BACKUP=/home/web/backups/$LAUNCH

# L1 整库导入（容器内临时文件，用完就删）
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
$WPCLI db import /tmp/db.sql
docker exec php rm -f /tmp/db.sql

# L2 导入后清 transient 与缓存
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"

# L3 文件部分：9.6 → 9.5 → 9.4 → 9.2，顺序不能反，删和恢复不要串成一行
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
cp -a "$BACKUP/i18n/." "$I18N/"
```

**判据（逐条打勾，不看 exit 码）**：

| # | 判据 | 怎么查 |
|---|---|---|
| L-1 | 表数 = `db.sql` 里 `CREATE TABLE` 的条数（演练是 40） | `grep -c 'CREATE TABLE' "$BACKUP/db.sql"` 与库里 `information_schema.tables` 的计数相等 |
| L-2 | 上线后新增的数据确实没了 | 导入前建的草稿按 slug 查：`$WPCLI post list --name=<slug> --post_status=any --format=count` 为 `0` |
| L-3 | `stylesheet` 与 `template` 都是 `packrivo-static-bridge-1.2.38` | `$WPCLI option get stylesheet` / `template` |
| L-4 | `packrivo-quote` 不再**激活** | `$WPCLI plugin list --status=active --format=csv \| grep '^packrivo-quote,'` 无输出。**只看 active**：导入后插件文件还在，`plugin list` 里当然还看得到它，要等 L3 的 9.4 删目录才彻底消失 |
| L-5 | 页面标题回到上线前 | 与 `$BACKUP/before-pages.csv`（或演练基线快照的「页面标题」一节）逐行一致 |
| L-6 | 首页 / `/products/` / `/de/` 200，`/get-a-quote/` 301 | `curl -s -o /dev/null -w '%{http_code}'`。**301 必须在 L3 的 9.6 之后判**：27e 补丁还在时 `/get-a-quote/` 是 200（渲染报价页），这不是故障是顺序 |

28r 本地实测（`deploy/verification-28r/lastresort.log`，复核在 `acceptance-recheck.log`）：L-1 / L-2 / L-3 / L-5 一次 PASS；**L-4、L-6 首次执行时打印 FAIL**，原因就是上表这两条注记——L-4 当时写成了「在不在列表里」，L-6 在 9.6 之前判。按上表改写后复核：L-4 PASS、L-6 PASS（301 → `/inquiry/`）。原始一轮 PASS=16 FAIL=2（`EXIT=1`），复核 PASS=11 FAIL=0（`EXIT=0`），L3 做完后整站快照 sha256 与 28q 基线 `4972cd81…` **逐字节一致**。

---

## 附录 A · 已知问题（不在本次上线范围）

> **28p 已修**：页头在 768 px 和 1024 px 的横向溢出。做法是把汉堡断点统一到实测得到的 W=1294（12 语种桌面页头右侧留 16 px 余量放得下的最大宽度），1024 及以下全语种进汉堡；768 那一档的溢出其实在页脚底部（`.footer-bottom` 在 768–940 之间不换行）。验证见 `deploy/verification-28p/`。

| 问题 | 实测 | 影响 | 何时处理 |
|---|---|---|---|
| `/ar/<文章路径>/` 返回 404 | 页面 `noindex,follow` 并链到英文版 | 阿语站内文章入口只能跳英文 | 等文章翻译排期 |
| `/contact/` 301 到 `/inquiry/` | 生产与本地一致 | 不构成重复标题页 | 若要做独立联系页再说 |
| 语种页副链接在 1440–1780 px 间按语种隐藏 | `.pk-actions .pk-text-link` 的按语种媒体查询 | 副链接在这些宽度不显示（不影响报价按钮） | 已按实测调过阈值，属预期行为 |

## 附录 B · 时间与顺序速查

| 步 | 内容 | 机器 | 预计 | 是否可跳过 |
|---|---|---|---|---|
| 0 | 环境检查 + 上传包 | 生产 SSH + 本机 | 10 min | 不可 |
| 1 | 备份（含 db.sql） | 生产 SSH（宿主机） | 10 min | 不可 |
| 2 | 字典 + 路径表 | 生产 SSH（容器里跑合并） | 20–30 min | 不可（本轮主体） |
| 3 | 134 产品页 + 8 主页面 | 生产 SSH（容器里 eval-file） | 15–25 min | 不可（SEO 主体） |
| 4 | 报价插件（首装） | 生产 SSH | 5 min | 可（若报价插件不变） |
| 4.5 | 建报价页 `/get-a-quote/`（首装必做） | 生产 SSH | 2 min | 不可（插件只给短代码，不会自己建页） |
| 5 | 子主题（首装） | 生产 SSH | 5 min | 可（若只改内容） |
| 4.6 | 指定页面模板 `_wp_page_template`（`/products/`，首装必做） | 生产 SSH | 1 min | 不可（没有这条 meta 页面就走默认模板，正文 legacy HTML 会排版错乱） |
| 6 | 两个 mu 补丁 | 生产 SSH | 10 min | 27e 不可省（否则报价按钮全跳询盘） |
| 7 | 清缓存 | 本机 + 生产 SSH（Cloudflare 由负责人亲自执行） | 5 min | 不可 |
| 8 | 复测 | 本机浏览器 + 生产 SSH | 20–30 min | 不可 |
| 9 | 整体回滚（**先 9.8 删报价页 → 9.7.5 还原页面模板**，再 9.7 → 9.2） | 生产 SSH（清缓存在本机） | 15 min | 只在出问题时执行 |

## 附录 C · 本地演练（28q 正反向 + 28r 最后手段，均已跑通）

本手册在 2026-10-28 于本地按「生产结构」完整演练过：WP **7.1.4**（28t 起；7.1.3 时代跑过 28q/28r/28s 三轮）+ PHP **8.3.36**、`php` / `mysql` / `nginx` 三个容器、宿主机目录 `/home/web/...`、子主题与报价插件**未安装**的首装前状态，内容也摆回上线前（27g/27i 的旧标题、旧正文）。

**判据以行为准，不看 exit 码。** 28q 当时记的「9 个步骤块全部 exit=0」现在已经不算证据：`rehearsal.sh` 每个 step 结尾都是 `set +x`，脚本恒返回 0。28r 起改成——每一步的判据打印 `PASS:` / `FAIL:`，`step_end` 统计本步 `PASS=n FAIL=m`，有 FAIL 就 `exit 1`（`bash rehearsal.sh selftest` 可自检这套机制）。

| 轮次 | 覆盖步骤 | 结果（按判据行） |
|---|---|---|
| 正向（28q） | 第 0–8 步 | 当时只记了 exit 码（已作废，见上）；可复算的证据是正向快照 `4247693f…` 与基线 `4972cd81…` 不同（第 3 步确实改了内容） |
| 回滚（28q） | 第 9 步 | 站点快照与基线**逐字节一致**（`4972cd81…`）；注意这一步**没有导入数据库**，整库回滚见 28r |
| 再正向（28q） | 第 1–8 步 | 站点快照与第一次正向**逐字节一致**（`4247693f…`），说明每步可重复执行 |
| **最后手段（28r）** | 第 9 步末尾的整库导入 + 9.6/9.5/9.4/9.2 文件部分 | 原始一轮 `PASS=16 FAIL=2 EXIT=1`（L-4/L-6 判据写法与顺序都错了，见该节注记），按改写的判据复核 `PASS=11 FAIL=0 EXIT=0`；整站快照回到基线 `4972cd81…`（逐字节一致） |
| **并入三个守卫后重跑（28s）** | 第 0–8 步正向 + 第 9 步回滚 | 见 `DSH-RESULT-2026-10-28s.md`：每步 `PASS=n FAIL=0` 且 `EXIT=0`；复位快照 `4972cd81…`、正向快照 `4247693f…`、回滚后回到 `4972cd81…`；反向自检确认 3.0 守卫拦得住他站残留 JSON |
| **本地对齐 WP 7.1.4 后重跑（28t）** | 第 0–8 步正向 + 第 9 步回滚 + 3.0 反向自检 | 见 `DSH-RESULT-2026-10-28t.md`：core 升级到 7.1.4（官方包 `a147146c…`）后每步 `PASS=n FAIL=0` 且 `EXIT=0`；复位快照作为本轮新基线，回滚后回到该基线；正向快照与 28s 正向 `4247693f…` 的 diff 只来自版本号，页面标题没有变化。RUNBOOK 提交号见结果文档（下一次生产授权锁定它） |
| **演练环境换 MySQL 后完整重跑（29v）** | 正向 0–8（含 4.5 建页）+ 最后手段（整库导入）+ 第二轮正向 + 第 9 步回滚（含 9.8 删页）+ 3.0 反向自检 | 见 `DSH-RESULT-2026-10-29v.md` 与 `deploy/verification-29v/`：`mysql` 换 **MySQL 8.4.13**（`caching_sha2_password` + 自签 TLS）、`php` 客户端钉 **mariadb 11.4.13**，第 0 步如实复现「`2026 TLS/SSL error` → 装包装 → `1045 caching_sha2` → 装 connector → 三条判据 PASS」；两轮正向各 `73 PASS / 0 FAIL`、`step9` `PASS=9 FAIL=0`、最后手段 `20 PASS / 0 FAIL`，全程 `EXIT=0`。新基线 `8971f447…`（取代 MariaDB 时代的 `4972cd81…`），正向 `eef3fd37…` 与 28s 的 `4247693f…` **只差一行**（报价页 ID 3220 → 5444）；最后手段 / 第 9 步 / 反向自检三条路径之后快照都回到基线 `8971f447…`（逐字节一致） |

### 附录 C-1 · 2026-10-29 生产实跑（首装，已上线）

第一次真上生产（`4544e0d`，Claude 线上只读复核通过）。逐字节证据：`deploy/LAUNCH-LOG-2026-10-28.md` §37–§40、`dsh-out/DSH-RESULT-2026-10-28-prod.md` §18–§31。

**那一轮两次停下等临时批准（现在都已写进正文，下次不用再批）**：

| # | 现象 | 原因 | 现在的正文位置 |
|---|---|---|---|
| 偏离 1 | 0.1 导出探针先报 `TLS/SSL error: self-signed certificate in certificate chain`（0 字节）；打上 `--skip-ssl` 包装后改报 `1045 … caching_sha2_password.so: No such file or directory`（仍 0 字节） | 生产 `mysql` 是 **MySQL 26.7.0**、认证 `caching_sha2_password`；php 容器是 `mariadb-dump 11.4.13`，`/usr/lib/mariadb/plugin/` 初始 0 个文件。当场批准 `apk add --no-cache mariadb-connector-c`（装到 3.3.10-r0，插件目录 0 → 5 个文件），探针随即导出 32,516,267 B | **0.1「补救块（两层）」** |
| 偏离 2 | 第 6 步补丁打对了，`/get-a-quote/` 却是 **404**（en/de/ar 全 404，HEAD 也 404） | 生产**从来没有** `/get-a-quote/` 页面：插件只给短代码，旧 301 一直遮着；演练库自带这个页面（ID 3220）所以从没暴露。当场批准 `post create` 建页，生产 ID **3383** | **4.5 建报价页**、**第 6 步判据 6d/6e 注记**、**9.8 删报价页** |

**那轮当场改正的三处判据写法**（都已进正文）：0k-2 用 `SHOW FULL TABLES` 数 `BASE TABLE` 并去掉表头（`SELECT COUNT(*) … --skip-column-names` 返回空、`SHOW TABLES | wc -l` 多算一行表头）；3g 的 `H1_body_URL_unchanged=8/8` 在 **verify** 输出里，不在 apply；6b 要拿**打补丁之后**的哈希去比「已变」。

**实测数字（下次可对照）**：`CURRENT_LAUNCH=packrivo-launch-20261029-0335`；`db.sql` 32,516,235 B / `sha256 1733240d…6ab6`；`CREATE TABLE` 40 = `SHOW FULL TABLES` 的 BASE TABLE 40；27g `apply_updated=134 already_current=0`、27i `7+1=8`；两个 mu 打后 `887787ab…` / `f7d95f35…`；core 全深度改动 **48** 个文件。

28q 补跑过第 0.1 步新加的**数据库导出探针**：拆掉客户端包装时探针报 `TLS/SSL error: SSL is required`、导出 0 字节；按本手册打包装后导出 36,381,501 B、40 张表。

28r 把**最后手段**跑了两遍（这就是它必须演练的理由）：
- **包装不在时** `db import` 退出码 1，导入没有发生（表数仍是 40、`option get siteurl` 仍读得到，数据库没被导坏）；
- **包装在时**同一份 `db.sql` 导入成功，40 张表，导入前建的草稿 `28r-canary` 消失（证明最后手段会丢掉上线后新增数据），页面标题一节与基线 726 行逐行一致；
- 文件部分按 9.6 → 9.5 → 9.4 → 9.2 做完后，整站快照 sha256 与 28q 基线 `4972cd81…` **逐字节一致**。

结构化摘要：`deploy/verification-28q/rehearsal-verify.json`；演练脚本（复位 / 快照 / 逐步执行）：`deploy/verification-28q/scripts/`。最后手段的完整日志、三份快照与 diff：`deploy/verification-28r/`。

**上线后补记（2026-10-29 当天与次日）**：29 号上线当场的两处偏离是「connector-c 插件缺失」和「`/get-a-quote/` 页面不存在」；次日又发现**第三处同类遗漏**——页面 8 `/products/` 没有 `_wp_page_template`，用的是默认模板，正文里的 legacy HTML 被原样吐出（筛选控件挤成文字、图片撑满宽）。三处的成因完全一样：**本地库里有的东西，RUNBOOK 没写，演练库又从本地复制**。现在的对应正文是 **0.1 补救块（两层）**、**4.5 建报价页**、**4.6 指定页面模板**，回滚分别是 **9.8** 与 **9.7.5**；生产那两次执行原文见 `deploy/LAUNCH-LOG-2026-10-28.md` §39 与 §41。

---

## 附录 D · 29z 上线顺序（撤 1688 进货价 + 4.0.4 包 + 字体补丁），2026-10-30

> 演练环境用**生产现状库**（`29z-prod.sql`：208 个 catalog 页、209 条正文含 `1688`、209 条含 `¥`）按这个顺序
> 从头跑到尾，跑完按 D.6 回滚（判据见 D.6 第 4 步），再回到 D.2 跑第二次，三遍都通过。
> **本附录就是执行顺序表：从上往下敲，不要跳步，不要并行。**
>
> 记号：`【本机】` 在你电脑上（仓库根目录）；`【宿主】` 生产宿主机 SSH 里的 shell；`【容器】` 已经进了
> `docker exec php`（不用再套一层）。`⛳` 是判据，不通过就停，不要往下走。
>
> **一条硬规矩：宿主机上每一次 HTTP 访问都必须直连源站（`$CURL`），不许直接 `curl https://www.packrivo.com`。**
> 那个域名挂在 Cloudflare 后面，直接请求会读到缓存里的旧页面：装完 4.0.4 看不见 `Example quote`、
> 回滚比对首页哈希也对不上，人会卡在半路误判。改前、改后、回滚三处用的必须是同一种直连方式。

### D.0 变量、直连探测、预检

【宿主】1) 一次性把变量钉好，**把 `$BACKUP` 记下来**（回滚只认它）：

```bash
PKG=/root/29z-pkg
PKGC=/pkg
BACKUP=/home/web/backups/29z-$(date -u +%Y%m%d-%H%M%S)

SITE=https://www.packrivo.com
export no_proxy='*' NO_PROXY='*'   # 绕开宿主的系统代理；用环境变量而不是往 $CURL 里塞引号（变量里的引号是字面量）
CURL="curl -sS -k --resolve www.packrivo.com:443:127.0.0.1"   # -k：源站是 CloudFlare Origin CA 证书，本机不认
curl --version | head -1           # 记下来：探测与判据都跑在这台机器的这个 curl 上
ORIGIN_IP=$(docker inspect php -f '{{range .NetworkSettings.Networks}}{{.Gateway}}{{end}}')   # php 容器的默认网关 = 宿主机；不用容器里的 ip 命令（镜像不一定有），也不用双引号里的 $3
CURLX="--resolve www.packrivo.com:443:$ORIGIN_IP -k"   # 给容器里的 29z-criteria.sh（它解析不到 nginx 这个名字）

echo "$BACKUP" | tee /root/29z-backup-path.txt
```

⛳ 下面两条各自打 PASS / FAIL，任一 FAIL 就停（不要带着空的 IP 往下跑）：

```bash
echo "ORIGIN_IP=$ORIGIN_IP"
echo "$ORIGIN_IP" | grep -Eq '^[0-9]+(\.[0-9]+){3}$' && echo 'PASS ORIGIN_IP 是单个 IPv4' || echo 'FAIL ORIGIN_IP 不是单个 IPv4'
code=$(docker exec php curl -sS -o /dev/null -w '%{http_code}' $CURLX https://www.packrivo.com/)
[ "$code" = "200" ] && echo "PASS 容器经 CURLX 打源站 = $code" || echo "FAIL 容器经 CURLX 打源站 = $code"
```

⛳ `ORIGIN_IP` 是一个 IPv4；容器里 `curl $CURLX https://www.packrivo.com/` 回 200。这两条专门堵两个坑：网关 IP 拿不到（空串，或 awk 因为 `$3` 被宿主机 shell 展开成空而打出整行）、容器拿不到源站页面（products-before 就会是 0 字节，后面 4/8/9 假 PASS）。

【宿主】2) 建目录（宿主机 `$PKG`、`$BACKUP`，容器内 `$PKGC`）。**D.1.1 的 `scp` 依赖这里先做完**：

```bash
mkdir -p "$PKG" "$BACKUP/29z"
docker exec php mkdir -p "$PKGC"
```

【宿主】3) 后面反复用的两行：

```bash
WPC=/var/www/html/www.packrivo.com/wordpress
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
```

【宿主】4) **直连源站探测**（不通就停，别急着装包）：

```bash
# 旧文件会让「没有某样东西」假 PASS，先删干净
rm -f /tmp/29z-probe1.html /tmp/29z-probe2.html /tmp/29z-probe-head.txt /tmp/29z-probe1.err /tmp/29z-probe2.err /tmp/29z-probe-head.err
now=$(date +%s)

echo '--- 1) 响应头（stderr 落文件，⛳ 要求为空）---'
$CURL -I "$SITE/" > /tmp/29z-probe-head.txt 2> /tmp/29z-probe-head.err
head -1 /tmp/29z-probe-head.txt
grep -i '^server:' /tmp/29z-probe-head.txt
[ -s /tmp/29z-probe-head.err ] && echo "FAIL head 的 stderr：$(cat /tmp/29z-probe-head.err)" || echo 'PASS head 的 stderr 为空'
grep -Eq '^HTTP/[0-9.]+ 200' /tmp/29z-probe-head.txt && echo 'PASS 头文件首行是 HTTP 200' || echo 'FAIL 头文件首行不是 HTTP 200'
grep -qi '^server: nginx' /tmp/29z-probe-head.txt && echo 'PASS 头文件里 server: nginx' || echo 'FAIL 头文件里没有 server: nginx'
grep -qi 'cf-ray\|cf-cache-status' /tmp/29z-probe-head.txt && echo 'FAIL 还在走 Cloudflare' || echo 'PASS 无 cf-ray / cf-cache-status'

echo '--- 2) 连取两次首页（stderr 落文件，⛳ 要求为空）---'
$CURL "$SITE/" -o /tmp/29z-probe1.html 2> /tmp/29z-probe1.err
$CURL "$SITE/" -o /tmp/29z-probe2.html 2> /tmp/29z-probe2.err
sha256sum /tmp/29z-probe1.html /tmp/29z-probe2.html
grep -c '<html' /tmp/29z-probe1.html
for i in 1 2; do
  sz=$(wc -c < /tmp/29z-probe$i.html); mt=$(stat -c %Y /tmp/29z-probe$i.html)
  [ "$sz" -gt 0 ] && echo "PASS probe$i 有内容 ${sz}B" || echo "FAIL probe$i 是 0 字节"
  [ "$mt" -ge "$now" ] && echo "PASS probe$i 是本次抓的（mtime $mt ≥ $now）" || echo "FAIL probe$i 是旧文件（mtime $mt < $now）"
  [ -s /tmp/29z-probe$i.err ] && echo "FAIL probe$i 的 stderr：$(cat /tmp/29z-probe$i.err)" || echo "PASS probe$i 的 stderr 为空"
done
```

⛳ 只要出现一个 `FAIL` 就停。必须同时成立：两个 stderr 都空；头文件首行是 `HTTP/... 200` **并且**里面有 `server: nginx`（不能只靠「没有 cf-ray」这种否命题）；两个探测文件都存在、字节 > 0、mtime 不早于 `$now`；两个哈希相同。

不通过就停，对号入座（四种，都实测过）：

| 现象 | 原因 | 处置 |
|---|---|---|
| 头里有 `cf-ray` / `cf-cache-status`，或直接 `503 Service Unavailable` + `Proxy-Connection: close` | 本机（Windows / macOS）的**系统代理**被 curl 拿走了，`--resolve` 发挥不了作用 | 开头那句 `export no_proxy='*' NO_PROXY='*'` 就是干这个的；再 `env \| grep -i proxy` 确认 |
| TLS 报 `unable to get local issuer certificate`（curl 60） | 源站挂的是 **CloudFlare Origin CA** 证书（issuer 写着 CloudFlare Origin SSL Certificate Authority），本机和容器的 CA 库都没有它的根 | `CURL` 默认已带 `-k`（只跳过证书校验，仍直连 127.0.0.1） |
| 响应头里出现 `HTTP/1.1 200 Connection established`，紧跟着 `Server: cloudflare` + `CF-RAY` | 把绕代理写进变量里会失效（引号在变量里是字面量） | 改成 `export no_proxy='*'`，不再往参数里塞星号 |
| 两次哈希不同 | 页面里有随机串（nonce / 时间戳 / AB 变体） | `diff /tmp/29z-probe1.html /tmp/29z-probe2.html` 定位；若随机串在 `<script>` 的 `nonce=` 里，比对前先 `sed -E 's/nonce="[^"]*"//g'`；仍不同就改用 `/products/` 当基准页，并在 D.6 第 4 步同步改 |
| `Connection refused` | 源站 443 没开 | `CURL="curl -sS --resolve www.packrivo.com:80:127.0.0.1"`、`SITE=http://www.packrivo.com`；若 nginx 又 301 到 https，说明只能走 443，回头查 nginx 监听 |

演练环境等价写法（**宿主侧命令必须在 Linux 里跑**，与生产同一种 shell；练过的做法是拉一个
`curlimages/curl:8.14.1` 容器挂到演练网桥上，把 `--resolve` 指到演练源站容器）：

```bash
docker run --rm --network r28q-net curlimages/curl:8.14.1 sh -c 'curl --version | head -1; curl -sS -k --resolve www.packrivo.com:443:172.19.0.5 -I https://www.packrivo.com/'
```

⛳ `curl --version` 必须是 **8.14.x**（和生产宿主同一代）；版本不对就换镜像，不要在 Windows 的 Git bash 里跑——
那台机器的 curl 对参数的处理跟生产不一样，会把坑盖住（2026-10-30 就是这么漏过绕代理那个参数的）。
`SITE` 一律 `https://…:443`，不接受任何非 443 的等价写法。

【宿主】5) 只读预检：

```bash
$WPCLI theme list --fields=name,status,version | grep packrivo
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_type='page' AND post_status='publish' AND post_name LIKE 'catalog-%'"
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%1688%'"
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')"
```

⛳ 期望 `packrivo-home-v4 active 4.0.1`、catalog `208`、含 `1688` 的 publish `209`、含 `¥` 的 publish `209`。
不是这三个数就**别往下走**——生产已经变过，先回 §0 重新取基线。

### D.1 锁定物与上传（先核对哈希，再上传）

**D.1.0 锁定物**（改一个字就重跑本节，别手工改表）：

| 物 | 路径 | 字节 | sha256 |
|---|---|---|---|
| 子主题包 4.0.4 | `deploy/packrivo-home-v4.zip` | 791169 | `3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6` |
| 去价脚本 | `deploy/patches-29z/strip-1688.php` | 23491 | `3c1be67b5c5a8298e317fb13ae1d910c64343370ebb440b0fb42d5fcb6f4f3fd` |
| 去价包装器 | `deploy/patches-29z/strip-1688.sh` | 1730 | `0deea313611ff71a760baae5646bcd0252f1cbad5bdb85d0ab3e2953e112ee1e` |
| 九条判据脚本 | `deploy/verification-29z/scripts/29z-criteria.sh` | 13079 | `1f6dd732275191c58b780af068220a40e2d85cb6226bbe90b78d9322a0dbe546` |
| 字体路径补丁 | `deploy/patches-29w/packrivo-wave2-font-path.patch` | 756 | `022c5a1666538f732bbda2911c1632b5c2285fcf393f78561efc876ce595451d` |
| 字体补丁 PHP 兜底（容器没 `patch` 时用） | `deploy/patches-29w/apply-font-patch.php` | 1840 | `64c8768e5ba3e00281fda4d5937f8c57a03cf5871818a84c2363095b0dda269b` |

包内 38 文件 + 5 目录 = 43 条目；`style.css` 与 `functions.php` 都是 `4.0.4`；
包内 `assets/img/hero.webp` = `6a55801599da73d2…`（1200×1200，33,758 B）、`hero-480.webp` = `266032c7bdfc0f51…`（480×480，8,866 B）。
`front-page.php` 里的首页说明卡片文案是 `Example quote · Rigid magnetic box · 9.5×9.5×4 cm`（价格仍由报价引擎算，没动数字）。

【本机】D.1.1 上传这 **6** 个文件（表里就这 6 个，少一个都别装）。
路径写死 `netcup:/root/29z-pkg/`：`$PKG` 是**宿主机**上的变量，本机 shell 里没有，
写成 `netcup:$PKG/` 会把文件丢到服务器根目录。所以 **D.0 第 2 步的 `mkdir -p "$PKG"` 必须先做完**。

```bash
scp deploy/packrivo-home-v4.zip                       netcup:/root/29z-pkg/
scp deploy/patches-29z/strip-1688.php                 netcup:/root/29z-pkg/
scp deploy/patches-29z/strip-1688.sh                  netcup:/root/29z-pkg/
scp deploy/patches-29w/packrivo-wave2-font-path.patch netcup:/root/29z-pkg/
scp deploy/patches-29w/apply-font-patch.php           netcup:/root/29z-pkg/
scp deploy/verification-29z/scripts/29z-criteria.sh   netcup:/root/29z-pkg/
```

【宿主】D.1.2 核对上传物：

```bash
cd "$PKG" && sha256sum packrivo-home-v4.zip strip-1688.php strip-1688.sh packrivo-wave2-font-path.patch apply-font-patch.php 29z-criteria.sh
```

⛳ 与 D.1.0 表逐个相同；`packrivo-home-v4.zip` 必须是 791169 字节。

### D.1b 前态快照（判据 4 与回滚判据都要它）

【宿主】1) 判据脚本进容器 + 取前态（首页走 `$CURL` 直连）：

```bash
docker cp "$PKG/29z-criteria.sh" php:/tmp/29z-criteria.sh
docker exec php sh -c 'mkdir -p /tmp/29z-snap/shots'
docker exec -e SITE="$SITE" -e CURLX="$CURLX" php sh /tmp/29z-criteria.sh before
$CURL "$SITE/" -o "$BACKUP/29z/home-before.html"
sha256sum "$BACKUP/29z/home-before.html" | tee "$BACKUP/29z/home-before.sha256"
```

⛳ `before` 模式自己就会打 7 条 **B1～B7**：pages 行数 ≥ 400、meta 行数 ≥ 200、index 行数 ≥ 200、`products-before.html` ≥ 100000 字节、卡片数 = 24、`products-before.err` = 0 字节、catalog ID = 208。**7 条全 PASS 才往下走**；任一 FAIL 脚本退出码 1，且 `home-before.sha256` 必须有值。这七条专门堵「空快照也算 PASS」——生产上实测踩过：php 容器里 `nginx` 解析不到，快照全空，4/8/9 全假 PASS。
刚才 D.0 第 4 步探测过「两次哈希相同」，所以这个值以后可以直接拿来比。

【本机】2) `/products/` 的「改前」截图（**必须在装包之前拍**，本机直接打生产域名，**不要 `docker cp` 进容器**）：

```bash
node deploy/verification-29z/scripts/29z-products-shots.mjs deploy/verification-29z/evidence/products-shots before https://www.packrivo.com
```

⛳ 两张图（1440 / 375）都 `status 200`、`cards` 都是 24、`overflow` false；`shots-before.json` 落盘。
演练环境：base 用 `https://www.packrivo.com`（演练源站也是 443），加 `SHOTS_MAP=www.packrivo.com`（Chrome 按域名钉 127.0.0.1、关掉系统代理），写法与生产等价。
截图不放进容器——本机的图 `docker cp` 只会进**演练容器**，生产容器里根本没有，判据也不该依赖它。

### D.1c 备份（三样，全落宿主机 `$BACKUP`，回滚只从这里取）

【宿主】1) 生产现役子主题目录（4.0.1）——**回滚的关键，别用 `theme activate` 父主题**：

```bash
docker cp -a php:"$WPC/wp-content/themes/packrivo-home-v4" "$BACKUP/themes-packrivo-home-v4-4.0.1"
find "$BACKUP/themes-packrivo-home-v4-4.0.1" -type f | wc -l | tee "$BACKUP/theme-files.count"
( cd "$BACKUP/themes-packrivo-home-v4-4.0.1" && find . -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum ) | tee "$BACKUP/theme-dir.sha256"
grep '^Version:' "$BACKUP/themes-packrivo-home-v4-4.0.1/style.css" | tee "$BACKUP/theme-version.txt"
```

⛳ 文件数 > 0；`theme-version.txt` = `Version: 4.0.1`；`theme-dir.sha256` 记下来（回滚时逐字比对）。
目录哈希算的是**相对路径**（`cd` 进去再 `find .`），回滚后换目录名也能比。

【宿主】2) mu-plugin 原文件（字体补丁之前的样子）：

```bash
docker cp php:"$WPC/wp-content/mu-plugins/packrivo-wave2.php" "$BACKUP/29z/packrivo-wave2.php.orig"
sha256sum "$BACKUP/29z/packrivo-wave2.php.orig" | tee "$BACKUP/29z/mu-orig.sha256"
docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w"
```

⛳ 宿主机这份应是 `5ab5be3f85f46ce5416fdb4cafc98f54de86a1fc4ece041c56c2de40173a1d8c`（12390 B）。
容器里那份 `.orig-29w` 同内容，是 §6.5 回滚用手的那份。

【宿主】3) 三张表（**不放容器 `/tmp`**，拷到宿主机并记字节数与 sha256）：

```bash
$WPCLI db export /tmp/29z-pre.sql --tables=wp_posts,wp_postmeta,wp_yoast_indexable
docker cp php:/tmp/29z-pre.sql "$BACKUP/29z/29z-pre.sql"
wc -c < "$BACKUP/29z/29z-pre.sql" | tee "$BACKUP/29z/29z-pre.bytes"
sha256sum "$BACKUP/29z/29z-pre.sql" | tee "$BACKUP/29z/29z-pre.sha256"
docker exec php rm -f /tmp/29z-pre.sql
```

⛳ 字节数 > 0，`.bytes` 与 `.sha256` 都在 `$BACKUP/29z/` 里。回滚导的是宿主机这份，容器里那份已删。

### D.2 装 4.0.4

【宿主】D.2.1 装包：

```bash
docker cp "$PKG/packrivo-home-v4.zip" php:"$PKGC/packrivo-home-v4.zip"
docker exec php sha256sum "$PKGC/packrivo-home-v4.zip"     # ⛳ 必须 3279dc6121cee305dbbf620beb7b7da45334886b779d7d2ece68004ceda4aed6
$WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
$WPCLI theme activate packrivo-home-v4
$WPCLI theme list --fields=name,status,version | grep packrivo     # ⛳ active 4.0.4
docker exec php sh -c "grep -c 'Example quote · Rigid magnetic box' $WPC/wp-content/themes/packrivo-home-v4/front-page.php"   # ⛳ 1
```

`$PKGC` 是**容器里的包目录**（`/pkg`，D.0 第 2 步已 `mkdir`），装完的代码在 `$WPC/wp-content/themes/packrivo-home-v4`。
两者不是同一个目录，别混。

⛳ 首页卡片文案生效（**走 `$CURL` 直连，不能读缓存**）：

```bash
$CURL "$SITE/" | grep -o 'Example quote · Rigid magnetic box · 9.5×9.5×4 cm' | head -1
```

【宿主】D.2.2 字体补丁（同 §6.5，装完包紧接着打）：

```bash
docker cp "$PKG/packrivo-wave2-font-path.patch" php:/tmp/29w.patch
docker exec php sh -c "cd $WPC && patch -p1 --dry-run < /tmp/29w.patch"     # ⛳ 无 FAILED、无 .rej
docker exec php sh -c "cd $WPC && patch -p1 < /tmp/29w.patch"
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # ⛳ 73d43ba0…
$WPCLI cache flush
$CURL "$SITE/" | grep -o 'https://[^"]*packrivo-inter-latin-v3.woff2' | head -1
```

**容器里没有 `patch` 的话**（本机演练镜像就是，会报 `patch: not found`），用 PHP 兜底，同样对齐 §6.5 的哈希锁：

```bash
docker cp "$PKG/apply-font-patch.php" php:/tmp/apply-font-patch.php
docker exec php php /tmp/apply-font-patch.php          # 幂等：第二次打印 already applied
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"   # ⛳ 73d43ba0…
```

⛳ 字体 URL 指向 `/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2`，
直连该 URL 返回 `200` / `font/woff2` / `34952` 字节：

```bash
$CURL -o /dev/null -w 'code=%{http_code} type=%{content_type} size=%{size_download}
'   "$SITE/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2"
```

### D.3 去价：干跑、真跑

【宿主】D.3.1 干跑（不写库）：

```bash
docker cp "$PKG/strip-1688.php" php:/tmp/strip-1688.php
docker exec -e STRIP1688_DRY=1 php sh -c "php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root eval-file /tmp/strip-1688.php"
```

⛳ `changes` = 210、`leftover_pages` = 0、`metadesc_unique` = 208、`metadesc_maxlen` ≤ 155。

【宿主】D.3.2 真跑 + 取审计：

```bash
docker exec -e STRIP1688_DRY=0 -e STRIP1688_AUDIT=/tmp/audit-29z.json php sh -c "php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root eval-file /tmp/strip-1688.php"
docker cp php:/tmp/audit-29z.json "$BACKUP/29z/audit-29z.json"
```

⛳ `changes` = 210；**紧接着再跑一次必须 `changes` = 0**（幂等）；审计里 34 句被删的原文人工过一遍。

`wp eval-file` 会把 `--dry-run` 当参数错误拦下，所以开关只有环境变量 `STRIP1688_DRY`，
或者用包装器 `bash deploy/patches-29z/strip-1688.sh --dry-run`。

它做什么：规格表 `Lowest listed tier / Historical 1688 reference` 整行删、换成 `Get a price →`；
hub 对照表里**任何**带价格的 `<td>` 换同一个链接（生产表头写 `Confirm stock minimum`，按表头匹配不到，靠这一层兜）；
`What this tells you` 的 `<h2>` + 那段价格话整块删；正文 `<p>/<li>` 里价格句逐句删（写进审计）；
208 页 meta 换成「产品名 — 结构/材质. Custom logo printing, MOQ 500 per design. Samples available.」≤155 字符、两两不同；
`/products/` 正文 158 个 `<p>Listed at ¥…</p>` 删掉；博客里 `detail.1688.com` 的 `<a>` 去链接留文字。

### D.4 重建 indexable + 清缓存

【宿主】

```bash
$WPCLI db query "UPDATE wp_yoast_indexable i JOIN wp_postmeta m ON m.post_id=i.object_id AND m.meta_key='_yoast_wpseo_metadesc' SET i.description=m.meta_value, i.open_graph_description=m.meta_value"
$WPCLI cache flush
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_yoast_indexable i JOIN wp_postmeta m ON m.post_id=i.object_id AND m.meta_key='_yoast_wpseo_metadesc' WHERE i.description<>m.meta_value"
```

⛳ 最后一条必须是 `0`。D.3 的脚本其实已经写过这三列，这一步是兜底（Yoast 自己刷新过 indexable、
或者中间手工改过 meta 时用）。

`wp db query` 的 SQL 是**位置参数**，不要加 `-e`（`wp` 会把 `-e` 之后当成多余参数报
`Too many positional arguments`）。

### D.5 容器内复测（compare-noshots，不含截图）

【宿主】

```bash
docker cp "$PKG/29z-criteria.sh" php:/tmp/29z-criteria.sh
docker exec -e SITE="$SITE" -e CURLX="$CURLX" php sh /tmp/29z-criteria.sh compare-noshots
```

⛳ `PASS=32 FAIL=0 SKIP=1`（v3 判据：0a–0f 六条输入非空、2c/7b/8b 三条正向条件）。`SKIP` 那条固定是 **4f 截图**——截图在本机拍、在本机判，容器里没有也不该有，
所以这一条在这里**既不算 PASS 也不算 FAIL**（旧版把「容器里没图」记成 FAIL，会把人卡住）。

判据 4 的口径是「卡片数量/名称/链接逐一相同 + 渲染里 `¥`、`¥`、`1688` 计数为 0」，
不是「渲染字节完全相同」——生产模板把正文塞进页面 `<script>` 的 JSON，删价格必然改字节。

### D.5.5 【停】清 Cloudflare 缓存 → 本机判 4f → 线上复测

走到这里**停下来**，把 `$BACKUP` 路径和 D.0～D.5 每条 ⛳ 的输出发给负责人。清缓存由负责人执行，不在本流程里：

```bash
python E:\破甲工作区\i18n\cf_purge.py          # 负责人平时用这份（10-25 版）；--verify 只验证不清
```

清完缓存后【本机】按顺序做三件事：

```bash
# 1) 改后截图（缓存已清，拍到的是新页面）
node deploy/verification-29z/scripts/29z-products-shots.mjs deploy/verification-29z/evidence/products-shots after https://www.packrivo.com
#    （演练：SHOTS_MAP=www.packrivo.com + base 也用 https://www.packrivo.com）

# 2) 判据 4f（四张图齐 + 卡片数与改前一致 + 无横向溢出）
node deploy/verification-29z/scripts/29z-shots-check.mjs deploy/verification-29z/evidence/products-shots

# 3) 全站复测（只读，不写库）
node deploy/verification-29z/scripts/29z-crawl-prod.mjs deploy/verification-29z/evidence/crawl-29z-after-cf.json
node deploy/verification-29z/scripts/29z-old-url-sources.mjs deploy/verification-29z/evidence/old-url-sources-after-cf.json
```

⛳ 三条都过：`29z-shots-check.mjs` 打印 `PASS 4f`（4/4 图、卡片 24 = 24、无溢出、改后渲染 `¥` 0 处）；
60 页里除 11 条多语种博客 404 外没有非 200；`/rigid-gift-boxes/ /resources/ /packaging-examples/ /pricing/ /sustainability/`
引用 0 条；首页字体 200；抽 5 个 catalog 页渲染里 `¥` 0 处。
`4f` 加上容器里那 23 条，合计 **24 PASS / 0 FAIL / 0 SKIP**。

### D.6 回滚（真出事才走这一段）

前提：`packrivo-home-v4` **保持激活**。**不要** `theme activate packrivo-static-bridge-1.2.38`——那会切回父主题，
整站变回上线前的样子（上一版 D.6 就是这里写错）。

【宿主】1) 主题目录退回 4.0.1（覆盖回去，slug 不变，激活状态不变）：

```bash
docker exec php sh -c "rm -rf $WPC/wp-content/themes/packrivo-home-v4"
docker cp -a "$BACKUP/themes-packrivo-home-v4-4.0.1" php:"$WPC/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --fields=name,status,version | grep packrivo        # ⛳ active 4.0.1
docker cp -a php:"$WPC/wp-content/themes/packrivo-home-v4" "$BACKUP/rollback-check"
( cd "$BACKUP/rollback-check" && find . -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum )
```

⛳ 目录哈希与 `theme-dir.sha256` 逐字相同，`theme-version.txt` 是 4.0.1。

【宿主】2) 字体补丁回滚（把 §6.5 那份 `.orig-29w` 换回去）：

```bash
docker exec php sh -c "cp -a $WPC/wp-content/mu-plugins/packrivo-wave2.php.orig-29w $WPC/wp-content/mu-plugins/packrivo-wave2.php"
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"    # ⛳ 5ab5be3f…
```

⛳ 与 `$BACKUP/29z/mu-orig.sha256` 相同（`5ab5be3f85f46ce5416fdb4cafc98f54de86a1fc4ece041c56c2de40173a1d8c`）。

【宿主】3) 数据库退回（导宿主机那份三表备份）：

```bash
docker cp "$BACKUP/29z/29z-pre.sql" php:/tmp/29z-pre.sql
$WPCLI db import /tmp/29z-pre.sql
$WPCLI cache flush
docker exec php rm -f /tmp/29z-pre.sql
```

【宿主】4) 回滚判据（四条全中才算回干净，**首页仍然用 `$CURL` 直连**，和 D.1b 同一种方式）：

```bash
$WPCLI theme list --fields=name,status,version | grep packrivo                                     # ⛳ 4.0.1 active
$WPCLI db query --skip-column-names "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')"   # ⛳ 209
$CURL "$SITE/" -o /tmp/home-after-rollback.html
sha256sum /tmp/home-after-rollback.html; cat "$BACKUP/29z/home-before.sha256"                      # ⛳ 两个哈希相同
docker exec php sha256sum "$WPC/wp-content/mu-plugins/packrivo-wave2.php"                          # ⛳ 5ab5be3f…
```

⛳ 四条：`4.0.1 active` / `¥` = `209` / 首页 HTML 哈希等于 `home-before.sha256` / mu 文件哈希等于原文件。

回滚后若要重来，从 D.2 继续（`$BACKUP` 里那三份备份不要删）。
