# 29v 演练证据（MySQL 环境，正向 / 最后手段 / 回滚 / 反向自检各一轮）

本轮目的：把 10-29 上线时**临时批准**的两处偏离和**当场修正**的三处判据写回 RUNBOOK，并让演练环境不再掩盖它们。

- 环境改造说明：`env/README-ENV.md`（MySQL 8.4.13 + `caching_sha2_password` + 自签 TLS；客户端钉 11.4.13；connector 3.4.6-r0）
- 脚本：`scripts/`（`run29v.sh` 驱动、`rehearsal.sh` 逐步、`reset.sh` 复位、`lastresort29v.sh` 最后手段、`negtest29v.sh` 反向自检、`snapshot.sh` 状态指纹）
- 日志：`logs/`；快照：`snapshots/`

## 1 · 关键哈希

| 东西 | 值 |
|---|---|
| 本轮基线（MySQL 环境，`snapshots/s-reset.txt`） | `8971f447510523ed468eedfaa9ceec1ac1d4762465bfd0fb006ae58dad3dd2b4` |
| 正向快照（`snapshots/s-forward.txt`） | `eef3fd37a8cbe2a4079c89fb6836d2518a06c6cf2ee4bd8d0f95ac75d729a33a` |
| 最后手段后（`snapshots/s-after-import.txt` 经文件部分回滚 → `lsr-s-final.txt`） | 回到基线 `8971f447…`（逐字节一致） |
| 第 9 步回滚后（`snapshots/s-after-rollback.txt`） | 回到基线 `8971f447…`（逐字节一致） |
| 反向自检后（`snapshots/s-after-negtest.txt`） | 仍是基线 `8971f447…` |
| 28s 时代的 MariaDB 基线 / 正向（仅作对照） | `4972cd81…` / `4247693f…` |

> 换 MySQL 后基线必须重取：`8971f447…` 取代 `4972cd81…`。正向快照也跟着换成了 `eef3fd37…`，与 28s 的 `4247693f…` 的差异只有一行（见下）。

## 2 · 正向快照与 28s 正向快照的差异（`logs/forward-vs-28s.diff`）

```
@@ -598,7 +598,6 @@
-3220,get-a-quote,"Get a Quote"
@@ -666,6 +665,7 @@
+5444,get-a-quote,"Get a Quote"
```

只有报价页的 ID 变了：**3220**（28s 时代演练库自带的报价页 ID；本轮 MySQL 种子里它仍在，首轮作废轮的 `reset.sh` 删掉后、交付轮 `reset.log` 里被删的是首轮 4.5 建的 5183）→ **5444**（本轮 4.5 `post create` 新建）。
页面标题、插件、主题、`siteurl`/`home` 全部逐行一致——说明第 3/4/5/45/6 步在 MySQL 上产生的站点状态与 MariaDB 时代相同。

（`fwd2` 那一轮 4.5 建出来的页也是 **5444**：最后手段做过整库导入，`wp_posts` 的 AUTO_INCREMENT 跟着回到备份里的值，所以第二次插回同一个 ID。）

## 3 · 每步判据（两轮正向完全一致）

| 步骤 | fwd（第一轮） | fwd2（第二轮） |
|---|---|---|
| step0（0.1 两层补救 + 三条探针判据） | PASS=10 FAIL=0 | PASS=10 FAIL=0 |
| step1 备份 | PASS=10 FAIL=0 | PASS=10 FAIL=0 |
| step2 字典 | PASS=8 FAIL=0 | PASS=8 FAIL=0 |
| step3 内容 134+8 | PASS=11 FAIL=0 | PASS=11 FAIL=0 |
| step4 报价插件 | PASS=3 FAIL=0 | PASS=3 FAIL=0 |
| **step4.5 建报价页** | **PASS=5 FAIL=0** | **PASS=5 FAIL=0** |
| step5 子主题 | PASS=4 FAIL=0 | PASS=4 FAIL=0 |
| step6 两个 mu 补丁 | PASS=12 FAIL=0 | PASS=12 FAIL=0 |
| step7 清缓存 | PASS=1 FAIL=0 | PASS=1 FAIL=0 |
| step8 复测 | PASS=9 FAIL=0 | PASS=9 FAIL=0 |
| **合计** | **73 / 0** | **73 / 0** |
| step9 回滚（含 9.8 删页） | — | **PASS=9 FAIL=0** |

`run29v.log` 的结尾是 `EXIT=0`。

## 4 · 第 0 步：两层补救的真实序列（`logs/fwd-step0.log`）

```
第 1 次裸探 → mariadb-dump: Got error: 2026: "TLS/SSL error: self-signed certificate in certificate chain"   字节数 = 0
装 --skip-ssl 包装 → mariadb-dump: Got error: 1045: "Plugin caching_sha2_password could not be loaded:
                      Error loading shared library /usr/lib/mariadb/plugin/caching_sha2_password.so: …"      字节数 = 0
apk add --no-cache mariadb-connector-c → (1/1) Installing mariadb-connector-c (3.4.6-r0) → 插件目录出现 caching_sha2_password.so
最终探针：字节=47374862  CREATE TABLE=40  BASE TABLE=40  DumpCompleted=1（fwd2 轮 47377810）
PASS: 0k-L1 / 0k-L2 / 0k-1 / 0k-2 / 0k-3
```

两条报错原文与生产 2026-10-29 的**逐字节相同**（生产是 `2026` + `1045`，见 `docs/LAUNCH-LOG` 的 §37）。
`fwd2` 那一轮重复出现同一条序列——因为 `reset.sh` 每轮都会 `apk del mariadb-connector-c` 并删掉 `/usr/local/bin` 里的包装。

## 5 · 最后手段（整库导入，MySQL）：20 PASS / 0 FAIL（`logs/lastresort.log`）

- 起点快照 = 本轮正向快照 `eef3fd37…`；
- `db.sql` 47,374,862 B / `CREATE TABLE` 40 条；
- **包装不在时** `db import` 退出码 1，表数仍 40（库没被导坏）；
- 装上包装后探针 47,230,679 B，`db import` 退出码 0；
- L-1 表数 40 = `CREATE TABLE` 40；L-2 canary（ID 5445）消失；L-2b 报价页 5444 也随导入消失；
- L-3 主题回父主题；L-4 `packrivo-quote` 未激活；L-5 页面标题 725 行与基线逐行一致；
- L-6 `/get-a-quote/` 301（在 9.6 之后判）；文件部分 9.6→9.5→9.4→9.2 做完后 **最终快照 = 基线，逐字节一致**。

## 6 · 第 9 步回滚与 3.0 反向自检

- step9 先做 **9.8 删报价页**（读 `$BACKUP/INSTALL-STATE.txt` 的 `get_a_quote_page_id=5444`），再走 9.7→9.2；9g 判据「任何状态计数 0」PASS，9f `/get-a-quote/` 回到 301；
- 回滚后快照 `8971f447…` = 基线，逐字节一致；
- 反向自检（`logs/negtest.log`）：往 `/pkg/rollback/` 放 `site=http://wrong-site.invalid` 的 JSON 后单跑 step3 → `EXIT=1`、打印 `FAIL: 3.0 … rollback-bogus.json 非本轮产物`、`apply_updated` 计数 0（apply 未执行），删掉该 JSON 后快照仍是基线。

## 7 · 本轮发现并修掉的一处演练判据错误

第一轮 step6 曾经 `PASS=11 FAIL=1`，失败项是我新加的 `6b2`：它拿生产的 `a2a7bdf3…` 去比演练库里的 `packrivo-i18n.php`，而演练习作一直是 `3bc32550…`（28s 那轮就是这个值，不是本轮改动引入的）——两个文件不是同一版本，但同一份补丁两边都能 `git apply --check` 通过。

按规则整轮作废、从复位重跑；判据改成「打前哈希 = 本步 `$BACKUP/before-mu.sha256` 里记的值」「打后 ≠ 打前」，这是环境无关的写法。RUNBOOK 第 6.2 节也补了注记：这个文件没有固定基线，判据是 `--check` 通过。
