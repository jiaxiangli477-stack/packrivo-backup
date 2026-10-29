#!/bin/bash
# 28r · 演练 RUNBOOK 第 9 步的「最后手段」（整库导入）+ 文件部分回滚
# 判据全部打印 PASS/FAIL，不拿 exit 码当证据
set -u
D=/home/web/rehearsal-28r
BASE_DIR=/home/web/rehearsal-28q
mkdir -p "$D"

WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKGC=/pkg
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"

H_START=4247693fa3a31f35477dcb059ad79e4ae5c13de861f94dfcc3848fe57f81b392
H_BASE=4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb

NP=0; NF=0
ckp() { NP=$((NP+1)); echo "PASS: $1"; }
ckf() { NF=$((NF+1)); echo "FAIL: $1"; }
# ck <描述> <命令...>：命令成功即 PASS
ck() { local d="$1"; shift; if "$@" >/dev/null 2>&1; then ckp "$d"; else ckf "$d"; fi; }

echo "############ 28r 开始 $(date -u +%Y-%m-%dT%H:%M:%SZ) ############"
set -x

echo "=========== 阶段 0 · 起点确认 ==========="
LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)
BACKUP=/home/web/backups/$LAUNCH
echo "CURRENT_LAUNCH=$LAUNCH"
echo "BACKUP=$BACKUP"
set +x

echo "-- 起点快照 --"
bash /opt/x28q/snapshot.sh "$D/s-start.txt"
HS=$(sha256sum "$D/s-start.txt" | cut -d' ' -f1)
echo "起点整份 sha256 = $HS"
if [ "$HS" = "$H_START" ]; then
  ckp "起点快照 = $H_START"
else
  ckf "起点快照 = $H_START（实际 $HS）→ 按任务单要求停下，不复位"
  echo "===== 结果：起点不一致，停止 ====="
  exit 2
fi

echo "-- db.sql --"
set -x
ls -l "$BACKUP/db.sql"
sha256sum "$BACKUP/db.sql" | tee "$D/db.sql.sha256"
set +x
DBSZ=$(stat -c %s "$BACKUP/db.sql")
DBCT=$(grep -c 'CREATE TABLE' "$BACKUP/db.sql")
echo "db.sql 字节数 = $DBSZ；CREATE TABLE 行数 = $DBCT"
[ "$DBSZ" -gt 0 ] && ckp "db.sql 非空（$DBSZ B）" || ckf "db.sql 为空"
[ "$DBCT" -gt 0 ] && ckp "db.sql 含 CREATE TABLE（$DBCT 条）" || ckf "db.sql 不含 CREATE TABLE"

echo "=========== 阶段 1 · 造一条上线后新数据（canary）==========="
set -x
CANARY_ID=$($WPCLI post create --post_type=page --post_status=draft --post_title=28r-canary --post_name=28r-canary --porcelain)
set +x
echo "canary post ID = $CANARY_ID"
case "$CANARY_ID" in ''|*[!0-9]*) ckf "canary 创建（ID=$CANARY_ID）" ;; *) ckp "canary 创建（ID=$CANARY_ID）" ;; esac
echo "$CANARY_ID" > "$D/canary.id"

echo "=========== 阶段 2 · 最后手段（包装不在）==========="
set -x
docker exec php sh -c 'rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb; ls /usr/local/bin | grep -c mariadb || echo "包装已拆除"'
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
set +x
echo "-- 原样执行 RUNBOOK 最后手段 3 条（第 1 条已在上方）--"
set -x
$WPCLI db import /tmp/db.sql; RC_NOWRAP=$?
set +x
echo "db import（无包装）退出码 = $RC_NOWRAP"
set -x
docker exec php rm -f /tmp/db.sql
set +x

echo "-- 导入失败后：数据库有没有被导到一半 --"
# 口令从 mysql 容器自己的环境变量取，不落到命令行也不打印
TBL=$(docker exec mysql sh -c 'mysql -uroot -p"$MARIADB_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='"'wp'"';"' 2>/dev/null)
echo "表数（导入失败后）= $TBL"
$WPCLI option get siteurl 2>&1 | tail -1
if [ "$RC_NOWRAP" -ne 0 ]; then
  ckp "无包装时 db import 失败（rc=$RC_NOWRAP）—— 证明导入依赖包装"
else
  ckf "无包装时 db import 成功（rc=0）—— 导入不依赖包装，RUNBOOK 要改写结论"
fi

echo "=========== 阶段 3 · 按 0.1 补救块装上包装 ==========="
set -x
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
docker exec php sh -c 'command -v mariadb-dump; command -v mariadb'
set +x
echo "-- 重探导出（判据：字节数 > 0）--"
set -x
$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes 2>&1 | tail -2
set +x
PSZ=$(docker exec php sh -c 'wc -c < /tmp/probe.sql' 2>/dev/null | tr -d ' ')
echo "探针导出字节数 = $PSZ"
case "$PSZ" in ''|0) ckf "导出探针 > 0 字节（实际 $PSZ）" ;; *) ckp "导出探针 > 0 字节（$PSZ）" ;; esac
set -x
docker exec php rm -f /tmp/probe.sql
set +x

echo "-- 原样再执行最后手段 3 条（包装在）--"
set -x
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
$WPCLI db import /tmp/db.sql; RC_WRAP=$?
set +x
echo "db import（有包装）退出码 = $RC_WRAP"
set -x
docker exec php rm -f /tmp/db.sql
set +x
[ "$RC_WRAP" -eq 0 ] && ckp "有包装时 db import 成功（rc=0）" || ckf "有包装时 db import 仍失败（rc=$RC_WRAP）"

echo "=========== 阶段 4 · 导入后清理 ==========="
set -x
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"
set +x

echo "=========== 阶段 5 · 验收（逐条 PASS/FAIL）==========="
TBL2=$(docker exec mysql sh -c 'mysql -uroot -p"$MARIADB_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='"'wp'"';"' 2>/dev/null)
echo "验收用表数 = $TBL2；db.sql 里 CREATE TABLE = $DBCT"
[ "$TBL2" = "$DBCT" ] && ckp "表数 = db.sql 的 CREATE TABLE 数（$TBL2）" || ckf "表数 $TBL2 ≠ CREATE TABLE 数 $DBCT"

set -x
$WPCLI post list --post_type=page,post --post_status=any --name=28r-canary --fields=ID,post_title --format=csv
CAN_LEFT=$($WPCLI post list --post_type=page,post --post_status=any --name=28r-canary --format=count)
set +x
echo "canary 剩余条数 = $CAN_LEFT（期望 0）"
[ "$CAN_LEFT" = "0" ] && ckp "canary 已被整库导入覆盖掉（证明会丢上线后新数据）" || ckf "canary 还在（$CAN_LEFT 条）"

STY=$($WPCLI option get stylesheet); TPL=$($WPCLI option get template)
echo "stylesheet=$STY template=$TPL"
[ "$STY" = "packrivo-static-bridge-1.2.38" ] && [ "$TPL" = "packrivo-static-bridge-1.2.38" ] \
  && ckp "stylesheet/template 都是父主题 1.2.38" || ckf "stylesheet/template 不是父主题（$STY / $TPL）"

set -x
$WPCLI plugin list --format=csv | grep '^packrivo-quote,' >/dev/null 2>&1; QACT=$?
set +x
[ "$QACT" -ne 0 ] && ckp "packrivo-quote 不在插件列表（未激活）" || ckf "packrivo-quote 仍在插件列表"

echo "-- 导入后快照 --"
bash /opt/x28q/snapshot.sh "$D/s-after-import.txt"
awk '/^== 页面标题/,/^== 关键选项/' "$D/s-after-import.txt" | grep -v '^== 关键选项' > "$D/titles-after-import.txt"
awk '/^== 页面标题/,/^== 关键选项/' "$BASE_DIR/s-baseline.txt"   | grep -v '^== 关键选项' > "$D/titles-baseline.txt"
if diff -q "$D/titles-baseline.txt" "$D/titles-after-import.txt" >/dev/null; then
  ckp "页面标题一节与基线逐行一致（$(wc -l < "$D/titles-baseline.txt") 行）"
else
  ckf "页面标题一节与基线不一致"
  diff "$D/titles-baseline.txt" "$D/titles-after-import.txt" > "$D/titles.diff" 2>&1 || true
  head -20 "$D/titles.diff"
fi

set -x
for u in "/" "/products/" "/de/"; do curl -s -o /dev/null -w "%{http_code} $u\n" "http://nginx$u"; done
curl -s -o /dev/null -w "%{http_code} /get-a-quote/\n" http://nginx/get-a-quote/
set +x
C1=$(curl -s -o /dev/null -w '%{http_code}' http://nginx/)
C2=$(curl -s -o /dev/null -w '%{http_code}' http://nginx/products/)
C3=$(curl -s -o /dev/null -w '%{http_code}' http://nginx/de/)
C4=$(curl -s -o /dev/null -w '%{http_code}' http://nginx/get-a-quote/)
[ "$C1" = "200" ] && ckp "首页 200" || ckf "首页 $C1"
[ "$C2" = "200" ] && ckp "/products/ 200" || ckf "/products/ $C2"
[ "$C3" = "200" ] && ckp "/de/ 200" || ckf "/de/ $C3"
[ "$C4" = "301" ] && ckp "/get-a-quote/ 301" || ckf "/get-a-quote/ $C4"

echo "=========== 阶段 6 · 文件部分（9.6 / 9.5 / 9.4 / 9.2）==========="
set -x
cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
sha256sum -c "$BACKUP/before-mu.sha256"
set +x
[ $? -eq 0 ] && ckp "9.6 mu 恢复且 before-mu.sha256 全 OK" || ckf "9.6 mu 恢复后 sha256 校验不过"

set -x
$WPCLI theme activate packrivo-static-bridge-1.2.38
rm -r "$WP/wp-content/themes/packrivo-home-v4"
$WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除"
set +x

set -x
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除"
set +x

set -x
cp -a "$BACKUP/i18n/." "$I18N/"
set +x

echo "=========== 阶段 7 · 最终快照 vs 基线 ==========="
bash /opt/x28q/snapshot.sh "$D/s-final.txt"
HF=$(sha256sum "$D/s-final.txt" | cut -d' ' -f1)
echo "最终整份 sha256 = $HF"
echo "基线整份 sha256 = $H_BASE"
if [ "$HF" = "$H_BASE" ]; then
  ckp "最终快照 = 基线 $H_BASE"
else
  ckf "最终快照 ≠ 基线（$HF）"
  diff "$BASE_DIR/s-baseline.txt" "$D/s-final.txt" > "$D/final-vs-baseline.diff" 2>&1 || true
  echo "--- diff 前 40 行 ---"
  head -40 "$D/final-vs-baseline.diff"
fi

diff "$D/s-start.txt" "$D/s-after-import.txt" > "$D/start-vs-after-import.diff" 2>&1 || true
diff "$D/s-after-import.txt" "$D/s-final.txt" > "$D/after-import-vs-final.diff" 2>&1 || true

echo "############ 汇总：PASS=$NP FAIL=$NF ############"
echo "CURRENT_LAUNCH=$LAUNCH"
[ "$NF" -eq 0 ] || exit 1
