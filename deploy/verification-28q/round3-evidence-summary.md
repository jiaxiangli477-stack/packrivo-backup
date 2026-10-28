# 28q 第三遍演练（17:04:32 UTC 重启后）关键证据摘录
# 来源：E:\破甲工作区\.tmp\28q\rehearsal\home\web\rehearsal-28q\forward1.log（30861 B，已被并行链于本地 10:23:42 截断重跑）
# 本文件仅保存证据，不动任何他人文件。

## reset.log（10:07:30 本地 = 17:07:30 UTC，新轮）
== 5) 内容回到上线前（27g/27i 的原始行）==
Error: '/pkg/prep-restore.php' does not exist.
Error: '/pkg/prep-restore.php' does not exist.
== 6) 状态确认 ==
packrivo-static-bridge-1.2.38
子主题未安装 ✓ / 报价插件未安装 ✓
get-a-quote 301（期望 301）✓ / 首页 200（期望 200）✓

## step1（17:10:26 起，exit=0）
+ docker exec php php ... wp db export /tmp/packrivo-before-launch.sql --skip-plugins --skip-themes
Success: Exported to '/tmp/packrivo-before-launch.sql'.
-> backup/db.sql = 32384985 B（TLS 2026 缺陷本轮未复现；命令无 --skip-ssl，包装疑为容器态遗留）

## step2（17:12:30 起，exit=0）
paths.json：生产 144 + 本地 146 → 合并 146（本轮补 0：无）冲突清单：[] → /pkg/merged/conflicts.json
+ docker exec php sh -c 'ls /pkg/merged/*-dict.json | wc -l' -> 11
+ docker exec php sh -c 'ls /pkg/merged/*-js.json   | wc -l' -> 11
+ 坏 JSON: /pkg/merged/conflicts.json   <-- 校验循环假阳性（conflicts.json=[]，json_decode 返回 falsy 触发 exit(1)；非硬失败）
paths ok / paths count ok（paths.json 缺失缺陷本轮未复现；merge-i18n 空文件容错生效）

## step3（17:12:46 起，exit=0）——核心：首轮 apply_updated=0
input_pages=134 preflight=PASS writes=0        (plan 27g)
hub_pages=8 preflight=PASS inquiry_existing_description=preserved writes=0   (plan 27i)
input_pages=134 preflight=PASS backup=created rows=134
apply_updated=0 already_current=134 URL_and_body_check=PASS   (apply 27g)
hub_pages=8 preflight=PASS backup=created rows=8
apply_updated=0 already_current=8 H1_body_URL_check=PASS      (apply 27i)
verified_pages=134 sourcing_titles=0 duplicate_titles=0 mismatches=0  (verify 27g)
verified_hubs=8 metadata_mismatches=0 H1_body_URL_unchanged=8/8 ...   (verify 27i)
-> 内容层未复位（DB 仍是上线后值），按 supplement2 D.2.3「首轮 apply_updated>0」判据该轮无效；rehearsal.sh 无 3.0 守卫，step3 仍 exit=0

## step4（17:14:23，exit=0）plugin install+activate packrivo-quote 1.0.1 -> 200
## step5（17:15:11，exit=0）theme install home-v4 + "Success: Switched to 'Packrivo Home v4 (child)' theme."；stylesheethome-v4
## step6（17:16:33，exit=0）两个 mu 补丁 git apply + php -l 无错；get-a-quote 200；viewport 检查 1
## step7（17:16:49，exit=0）transients/cache flush
## step8（17:17:37，exit=0）title 计数、sitemap 未变、robots 未变、pkq-root=1、inquiry 200

## 后置快照（run-all step3）：s-forward1.txt.raw 17:18:44 起卡在「== 插件 ==」后的 wp plugin list（≥4.5 分钟无新输出，仅 75 行 vs 基线 845 行），rollback.log 保持 0 字节 → run-all 未到第 9 步；并行链 17:23:42 UTC 截断三份日志重启新一轮（第四遍）

## 并行链诊断（10:17:28–10:20:08 本地）
diag-baseline2.txt: seed rollback-27g.json site=http://localhost:8081（依据为 8081 时代 ID）
diag-baseline.txt: apply 生成的 rollback-27g.json site=http://nginx rows=134（0 行改变）
diag-row.txt: seed 里 clear-display-box id=257 post_title="Custom Clear Display Box Sourcing"（上线前旧值）
