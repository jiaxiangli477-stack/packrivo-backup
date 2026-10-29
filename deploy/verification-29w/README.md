# verification-29w · 4.6 页面模板 + 9.7.5 回滚的本地演练

对应任务：`DSH-TASK-2026-10-29w.md` B 部分（本地，不碰生产）。

## 这一轮相比 29v 改了什么

| 位置 | 改动 | 为什么 |
|---|---|---|
| `scripts/rehearsal.sh` 新增 `step46` | 跑 RUNBOOK 4.6：守卫（页面 8 = `products`/`publish`、模板文件在子主题里）→ 把原值写进 `INSTALL-STATE.txt` → `post meta update` → 判据 4.6a–4.6f | 生产首装漏了这条 meta，`/products/` 走了 `page.php` 默认模板 |
| `scripts/rehearsal.sh` `step9` 新增 9.7.5 | 按 `INSTALL-STATE.txt` 里的 `page8_template_before` 还原；原值 EMPTY 就 `post meta delete`；顺序在 9.8 之后、9.6 之前 | 回滚必须回到「首装前根本没有这条 meta」 |
| `scripts/rehearsal.sh` `step9` 新增 9h / 9h2 | 判据：meta 已不存在；`/products/` 回到 `page-template-default` | 回滚可验证，不靠感觉 |
| `scripts/rehearsal.sh` `step5` | 装完子主题后硬校验文件、缺了就幂等重装（最多 3 次） | 2026-10-29w 本机实测过一次「装完目录消失」（Windows 绑定挂载 `rm -rf` 后容器重建同名目录），当时 5c FAIL |
| `scripts/reset.sh` 新增 5.5 | 每轮复位都删掉页面 8 的 `_wp_page_template` | 演练库是从本地库复制的，本地库带着这条 meta，掩盖了生产缺失 |
| `scripts/reset.sh` `4)` | 主题/插件删除改成「容器内 + 宿主机」两侧都删 | 同上，挂载视图不一致 |
| `scripts/snapshot.sh` | 多出一份 `*.txt.ext` = 旧口径 + 一行 `page8_template=` | 旧口径不含 post meta，模板设置看不见；`.ext` 让正向 diff 能显示它，同时保住 `8971f447…` 这个旧口径基线 |
| `scripts/run29w.sh` | 分两段：`fwd`（复位→基线→正向 0–8）与 `rb`（第 9 步→反向自检） | 中间留空档给宿主机的真实浏览器判 4.6e |
| `scripts/29w-browser.mjs` | 本地 HTTP 代理（`127.0.0.1:8899`，Host 原样带给 `127.0.0.1:8091`）驱动真实 Chrome 判三档溢出 | 站点 `siteurl=http://nginx`，宿主机解析不到；直接访问 127.0.0.1 会被 canonical 跳到 `http://127.0.0.1/products/` |

## 怎么跑

```powershell
$d='D:\Docker\App\resources\bin\docker.exe'
$web='E:\破甲工作区\.tmp\28q\rehearsal\home\web'
$scr='E:\破甲工作区\packrivo-backup\deploy\verification-29w\scripts'
# 段 1
& $d run -d --name r29w-runner --network r28q-net -v "${web}:/home/web" -v "${scr}:/opt/x29w:ro" `
  -v '/var/run/docker.sock:/var/run/docker.sock' --entrypoint bash r28q-host:11 /opt/x29w/runner-entry.sh fwd
# 段 1 结束后（日志出现「段1 结束」），趁站点还在正向态判 4.6e
node "$scr\29w-browser.mjs" 'E:\破甲工作区\packrivo-backup\deploy\verification-29w\evidence'
# 段 2
& $d run -d --name r29w-runner --network r28q-net -v "${web}:/home/web" -v "${scr}:/opt/x29w:ro" `
  -v '/var/run/docker.sock:/var/run/docker.sock' --entrypoint bash r28q-host:11 /opt/x29w/runner-entry.sh rb
```

## 2026-10-29 实跑结果

正向（`13:11:35Z → 13:43:49Z`）：

| 步 | 判据 |
|---|---|
| step0–step4 | PASS=10 / 10 / 8 / 11 / 3，FAIL=0 |
| step45（4.5 建报价页） | PASS=5 FAIL=0 |
| step5（子主题） | PASS=4 FAIL=0（含硬校验） |
| **step46（4.6 页面模板）** | **PASS=9 FAIL=0** |
| step6 / step7 / step8 | PASS=12 / 1 / 9，FAIL=0 |

```text
页面8=[products/publish] 模板=[template-products-v4.php] body=[page-template-template-products-v4]
文案=1 de=[page-template-template-products-v4] ar=[page-template-template-products-v4]
状态码= /200 /blog/200 /get-a-quote/301（27e 未打，预期空档）
page8_template_before=EMPTY (rc=1) at=2026-10-29T13:35:11Z by=rehearsal-29w
```

4.6e（真实 Chrome，`evidence/rehearsal-browser.json`，2026-10-29T17:13:17Z）：

| 页面 | 宽 | 状态 | 模板类 | `scrollWidth` | `innerWidth` | 越界元素 |
|---|---|---|---|---|---|---|
| `/products/` | 375 | 200 | 是 | 375 | 375 | 0 |
| `/products/` | 768 | 200 | 是 | 768 | 768 | 0 |
| `/products/` | 1024 | 200 | 是 | 1024 | 1024 | 0 |
| `/de/products/` | 1024 | 200 | 是 | 1024 | 1024 | 0 |
| `/ar/products/` | 1024 | 200 | 是 | 1024 | 1024 | 0 |

回滚（`17:13:44Z → 17:27:50Z`）：step9 `PASS=11 FAIL=0`（含 9h / 9h2）；反向自检拦住他站 JSON，站点未被改动。

## 快照

| 阶段 | 旧口径（基线可比） | ext（含 `page8_template`） |
|---|---|---|
| 复位后基线 | `8971f447510523ed468eedfaa9ceec1ac1d4762465bfd0fb006ae58dad3dd2b4`（844 行） | `43093ec5b3ddfced8f0066908646c64938d65a59ec5c892217fdce0d60971a65`（845 行） |
| 正向 | `6f941f16170827bc59f1de8732c1bf7e93aff5f71a262f9c125f1c885e570cd1`（847 行） | `cfb56417f8115c6f26d76009b0c19f8960e58c4bc7446611fc1ca240393949ca`（848 行） |
| 回滚后 | **= 基线** | **= 基线** |
| 反向自检后 | **= 基线** | **= 基线** |
| 修完字体（轮外） | `01c1c448743c7d797d4925e3af0903e1805391bbb431159062b278ca846599b7` | `91db54e0c1d8c5538192d384d3dab926f361b805d1e653b2d2da3e00d89cad63` |

正向 vs 基线：`snapshots/fwd-vs-base.diff`，449 行 / 14 段 hunk，`+163 / -160`；其中与本次改动直接相关的就两处：

```diff
-stylesheet=packrivo-static-bridge-1.2.38
+stylesheet=packrivo-home-v4
-page8_template=NONE
+page8_template=template-products-v4.php
```

其余是内容步（step3 的 134 产品页 + 8 主页面）、插件/主题列表、字典 sha256 这些原本就会变的部分。

正向 vs 29v 正向（`snapshots/fwd-vs-29v.diff`，2 段 hunk / 18 行）只有一处实质差异：`/get-a-quote/` 的页面 ID 每轮新建，29v 是 `5444`，本轮是 `6489`。

## 字体 404

`logs/font-fix-verify.log`：修的是 **mu 插件** `wp-content/mu-plugins/packrivo-wave2.php` 第 35 行
`get_stylesheet_directory_uri()` → `get_template_directory_uri()`（补丁 `deploy/patches-29w/packrivo-wave2-font-path.patch`）。
子主题 zip **不含**这段代码，所以 `deploy/packrivo-home-v4.zip` 没动，仍是 `5974ae0c208d03016701698c45dfbd467f7c24c6036fba120dccc01f72034bf9`。

修完只动一行指纹：`- 5ab5be3f… packrivo-wave2.php` / `+ 73d43ba0… packrivo-wave2.php`，删掉 `s-reset.txt` 与 `s-after-fontfix.txt` 之外没有别的差异。

## 目录

- `scripts/`：本轮全部脚本（`rehearsal.sh` `reset.sh` `snapshot.sh` `run29w.sh` `negtest29w.sh` `runner-entry.sh` `29w-browser.mjs` `ref/s-forward-29v.txt`）
- `logs/`：每一步的原始输出（`fwd-step*.log`、`step9.log`、`negtest*.log`、`run29w-*.log`、`reset.log`、`font-fix-verify.log`、`compare-local-templates.log`）
- `snapshots/`：快照与 diff
- `evidence/`：`rehearsal-browser.json` 与三张 `rehearsal-products-*.png`
- `env/`：镜像与源站说明（沿用 29v）
