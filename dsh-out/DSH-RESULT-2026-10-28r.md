# 28r 结果：补跑第 9 步「最后手段」（整库导入）两次 + 判据改为 PASS/FAIL

工作台 #56 | 监工 kimi-k3.1-pro | 执行 deepseek-v4-pro | 状态 已完成（按本轮要求单线程，#56 保持 paused 不派工）

本轮不碰生产：无 SSH、无生产写入、未读未打印任何密码/密钥/token，`.cf.env` 未打开。

## 1 结论

1. **最后手段跑了两遍**：拆掉包装时 `db import` 直接失败（`TLS/SSL error`，退出码 1，**导入没有发生**、数据没被导坏）；装上包装后同一份 `db.sql` 导入成功。所以 RUNBOOK 的最后手段**依赖 0.1 步那个客户端包装**，已写成前置检查。
2. **导入确实会丢上线后新数据**：导入前建的空草稿 `28r-canary`（ID 3883）导入后查不到，判据 PASS。
3. **整库导入 + 文件部分做完，整站快照回到 28q 基线 `4972cd81…`，845 行逐字节一致。**
4. **判据不再看 exit 码**：`rehearsal.sh` 每步打印 `PASS:` / `FAIL:`、`step_end` 汇总本步 `PASS=n FAIL=m`、有 FAIL 就 `exit 1`，自带 `selftest`。本轮原始一轮是 `PASS=16 FAIL=2 EXIT=1`——两条 FAIL 都是**我的判据写错了对象/顺序**，按改写后的判据复核 `PASS=11 FAIL=0 EXIT=0`（见 §4）。
5. 28q 结果文档里「`db import` 成功」这句不实的表述已删掉。

## 2 起点

| 项 | 值 |
|---|---|
| `CURRENT_LAUNCH` | `packrivo-launch-20261028-1743` |
| 起点快照 | `s-start.txt`，sha256 **`4247693f…`** = 任务单要求的值（PASS，67,774 B） |
| `$BACKUP/db.sql` | 35,409,451 B，sha256 `c5486baf0797e8e70bbaedaf11402789ebfdebecd15b260f86470bad1da0d9a7`，`CREATE TABLE` **40** 条 |
| canary | `wp post create --post_type=page --post_status=draft --post_title=28r-canary` → ID **3883** |

起点哈希正是 `4247693f…`，所以按任务单没有复位、直接开跑。

## 3 最后手段两遍

### 3.1 包装不在（第一次）

拆掉包装（`rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb`），原样执行 RUNBOOK 的三条命令：

```
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
$WPCLI db import /tmp/db.sql
docker exec php rm -f /tmp/db.sql
```

结果：

```
Error: Failed to get current SQL modes. Reason: ERROR 2026 (HY000):
TLS/SSL error: SSL is required, but the server does not support it
db import（无包装）退出码 = 1
表数（导入失败后）= 40
$WPCLI option get siteurl → http://nginx
```

**判定：失败，且失败得很干净。** 它挂在「取当前 SQL modes」这一步，还没开始灌数据，所以 40 张表全在、`siteurl` 读得到，不需要救。这条结果正是 RUNBOOK 要写的前提：**没有包装，最后手段根本用不了**。

### 3.2 包装在（第二次）

按 0.1 的补救块装上 `mariadb-dump` / `mariadb` 包装（`--skip-ssl` **追加在参数末尾**，`--no-defaults` 留在第一位），先重探导出：

```
导出探针字节数 = 35,265,982   → PASS（> 0）
```

再原样执行同样三条命令：

```
Success: Imported from '/tmp/db.sql'.
db import（有包装）退出码 = 0
```

导入后清理：`transient delete --all` → `Success: 2 transients deleted`；`cache flush` 执行通过。

## 4 验收逐条

### 4.1 原始一轮（`lastresort.log`，`PASS=16 FAIL=2 EXIT=1`）

| # | 判据 | 原始结果 | 证据 |
|---|---|---|---|
| 1 | 表数 = `db.sql` 的 `CREATE TABLE` 数 | **PASS**（40 = 40） | `information_schema.tables` 计数 |
| 2 | `28r-canary` 已不存在 | **PASS**（`--format=count` = 0） | 证明会丢上线后新数据 |
| 3 | `stylesheet` / `template` = `packrivo-static-bridge-1.2.38` | **PASS**（都是父主题） | 导入把 `active_theme` 一起回滚了 |
| 4 | `packrivo-quote` 不在 active 插件里 | **FAIL** | 见 4.2 ① |
| 5 | 页面标题一节与基线逐行一致 | **PASS**（726 行 `diff` 为空） | `titles-baseline.txt` vs `titles-after-import.txt`，两份都是 60,698 B |
| 6 | 首页 / `/products/` / `/de/` 200、`/get-a-quote/` 301 | 前三 **PASS**；`/get-a-quote/` **FAIL**（200） | 见 4.2 ② |

### 4.2 两条 FAIL 的成因（不是环境问题，是我的判据写错了）

**① L-4 判据写错了对象。** 我写的是 `wp plugin list --format=csv | grep '^packrivo-quote,'`——列表里包含**已安装但未激活**的插件，而此刻文件回滚（9.4 删目录）还没做，插件目录还在，当然看得到。任务单要的是「不在 **active** 插件里」。复核用正确写法：`--status=active` 过滤后无输出 → **PASS**；9.4 删完目录后再看，列表里也彻底没有了 → **PASS**。

**② L-6 判据跑错了顺序。** `/get-a-quote/` 回到 301 要等 **9.6 把 mu 恢复成 27e 之前的版本**；在这之前 mu 还是 27e 版本，它让 `/get-a-quote/` 直接渲染报价页（200）。所以「文件还没回滚就判 301」必然 FAIL。9.6 做完后复核：`HTTP/1.1 301 Moved Permanently`，`Location` 指向 `/inquiry/` → **PASS**。

所以 RUNBOOK 的 L-4 / L-6 两条判据已按实测改写：L-4 明确「只看 active」并说明插件目录要等 9.4 删完才从列表消失；L-6 明确「301 必须在 9.6 之后判」，并写清在这之前看到 200 是顺序问题。

### 4.3 复核（`acceptance-recheck.log`，`PASS=11 FAIL=0 EXIT=0`）

```
PASS: 4a packrivo-quote 不在 active 插件里
PASS: 4b 插件目录已删除
PASS: 6a /get-a-quote/ 回到 301
PASS: 6b /get-a-quote/ 的 Location 指向 /inquiry/
PASS: 6c 首页 200
PASS: 6d /products/ 200
PASS: 6e /de/ 200
PASS: C1 mu 校验全 OK
PASS: C2 子主题目录已删
PASS: C3 父主题 active
PASS: D1 复核快照 = 基线 4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
复核汇总：PASS=11 FAIL=0   EXIT=0
```

## 5 文件部分回滚与快照

按 9.6 → 9.5 → 9.4 → 9.2 的顺序执行，删和恢复各占一行：

```
9.6  cp -a "$BACKUP/mu/packrivo-i18n.php" ...  + remediation  → sha256sum -c before-mu.sha256：两个都是 OK
9.5  theme activate packrivo-static-bridge-1.2.38 → rm -r themes/packrivo-home-v4 → 子主题已移除
9.4  plugin deactivate packrivo-quote → plugin delete packrivo-quote → 报价插件已移除
9.2  cp -a "$BACKUP/i18n/." "$I18N/"
```

| 快照 | 大小 | sha256 |
|---|---|---|
| `s-start.txt`（起点，第二次正向之后） | 67,774 B | `4247693f…` |
| `s-after-import.txt`（整库导入之后，文件未回滚） | 69,322 B | 与起点不同（多出子主题/插件条目） |
| `s-final.txt`（文件也回滚之后） | 69,238 B / 845 行 | **`4972cd81…` = 基线，逐字节一致** |
| `s-final-recheck.txt`（复核脚本再取一次） | 69,238 B | `4972cd81…`，再次一致 |

三份快照两两 `diff` 已落盘：

- `start-vs-after-import.diff`（26,377 B）：整库导入把主题选项也回滚了——`packrivo-home-v4,active` → `inactive`、`stylesheet=packrivo-home-v4` → `packrivo-static-bridge-1.2.38`。
- `after-import-vs-final.diff`（6,650 B）：文件部分回滚——mu 两个文件哈希回到上线前（`packrivo-i18n.php` `3bc32550…`、`packrivo-site-remediation-20261022.php` `ac0a2753…`）、字典覆盖回去、子主题与插件目录消失。

**没有出现需要判断「该改 RUNBOOK 还是该改快照脚本」的不一致**：最终快照与基线逐字节相同，快照脚本不用动。

## 6 改的文件（`git diff --stat`）

```
 deploy/RUNBOOK-launch.md                     |  67 +++++++++++++---
 deploy/verification-28q/scripts/rehearsal.sh | 109 ++++++++++++++++++++++++---
 dsh-out/DSH-RESULT-2026-10-28q.md            |   2 +-
 3 files changed, 157 insertions(+), 21 deletions(-)
```

RUNBOOK（603 → 650 行）：

1. **最后手段一节重写**：加「只回滚数据库、不回滚文件」两条警告 + 前置检查（重跑 0.1 导出探针确认包装在位，不在就按补救块装上）+ `L1` 整库导入 / `L2` 清 transient 与缓存 / `L3` 文件部分（9.6 → 9.5 → 9.4 → 9.2，顺序固定、删与恢复不串行）+ 判据表 `L-1…L-6`。
2. **附录 C**：把「exit=0」的说法改成「以判据行为准」，说明 28q 当时只有 exit 码、已作废；补 28r 最后手段那一行（原始 `PASS=16 FAIL=2`、复核 `PASS=11 FAIL=0`、快照回到基线）。

`DSH-RESULT-2026-10-28q.md` §6：删掉「`db import` 成功」，改成「第 9 步的分步回滚没有导入数据库，28q 的 `reset.sh` 也不导入；这条最后手段 28r 已补跑」。

`rehearsal.sh`（256 → 354 行）：见下节。

## 7 `rehearsal.sh` 的 exit 码问题（选了「统计 FAIL 并 exit 1」）

以前每个 step 结尾都是 `set +x`，脚本恒返回 0，所以「18 块全 exit=0」什么也证明不了。改法：

- 加 `ckp` / `ckf` / `ck <描述> <命令...>` ：判据打成 `PASS:` / `FAIL:` 并计数；
- 每个 step 结束调 `step_end`：打印 `本步判据：PASS=n FAIL=m`，**有 FAIL 返回 1**，各 step 写成 `step_end || exit 1`；
- 把 0/1/2/3/4/5/6/7/8/9 每步的判据都补成机检（0 步 5 条、1 步 10 条、2 步 5 条、3 步 5 条、4 步 3 条、5 步 4 条、6 步 5 条、7 步 1 条、8 步 9 条、9 步 6 条）；
- 加 `selftest` 子命令自证机制可用。

证据：

- 机制自检：`bash rehearsal.sh selftest` → `PASS: 必然成立` / `FAIL: 必然不成立` / `本步判据：PASS=1 FAIL=1` / `step_end 按预期返回非零 → 机制可用`。
- 真实一步：`bash rehearsal.sh step0`（只读，跑在最终基线状态上）→ `PASS: 0a…0e` 5 条、`本步判据：PASS=5 FAIL=0`、`EXIT=0`；跑完再取一次快照仍是 `4972cd81…`，说明 step0 没改状态（`step0-proof.log`）。
- 反向：`lastresort.log` 里那一轮因为两条 FAIL 而 `EXIT=1`，说明 FAIL 真的能让整步非零退出。

## 8 结束状态

- 演练环境停在「最后手段 + 文件部分回滚」之后 = **基线状态**，整站快照 `4972cd81…`。
- 当前 `CURRENT_LAUNCH=packrivo-launch-20261028-1743`。
- 生产未动：仍是 WP 7.1.3 + 父主题 `packrivo-static-bridge-1.2.38`，无子主题、无报价插件，`/get-a-quote/` 301 → `/inquiry/`。

## 9 交付物

| 路径 | 内容 |
|---|---|
| `deploy/verification-28r/lastresort.log` | 两遍最后手段 + 验收 + 文件回滚的全部命令与原始输出（10,436 B） |
| `deploy/verification-28r/acceptance-recheck.log` | 判据复核（11 PASS / 0 FAIL / EXIT=0） |
| `deploy/verification-28r/step0-proof.log` | 新判据机制在真实一步上的证明（5 PASS / 0 FAIL / EXIT=0） |
| `deploy/verification-28r/s-start.txt` / `s-after-import.txt` / `s-final.txt` / `s-final-recheck.txt` | 四份快照 |
| `deploy/verification-28r/start-vs-after-import.diff` / `after-import-vs-final.diff` | 快照两两 diff |
| `deploy/verification-28r/titles-baseline.txt` / `titles-after-import.txt` | 页面标题一节（726 行）逐行一致的原始材料 |
| `deploy/verification-28r/canary.id` / `db.sql.sha256` | canary ID 3883；`db.sql` 哈希 |
| `deploy/verification-28r/scripts/lastresort.sh` / `recheck.sh` | 本轮用的两个脚本 |

## 10 复现

```bash
# 判据机制自检
docker exec r28q-host bash /opt/x28r/rehearsal.sh selftest

# 最后手段（注意：会丢上线后新增数据）
docker exec r28q-host bash /opt/x28r/lastresort.sh

# 判据复核
docker exec r28q-host bash /opt/x28r/recheck.sh
```
