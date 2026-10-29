#!/bin/bash
# 29v · 在 MySQL 上补跑 RUNBOOK 第 9 步的「最后手段」（整库导入）+ 文件部分回滚
# 用法：bash lastresort29v.sh <H_START 正向快照 sha256> <H_BASE 基线快照 sha256>
set -u
D=/home/web/rehearsal-29v
S=/opt/x29v
H_START="${1:?需要正向快照 sha256}"
H_BASE="${2:?需要基线快照 sha256}"
mkdir -p "$D"

WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKGC=/pkg
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"

NP=0; NF=0
ckp() { NP=$((NP+1)); echo "PASS: $1"; }
ckf() { NF=$((NF+1)); echo "FAIL: $1"; }
ck()  { local d="$1"; shift; if "$@" >/dev/null 2>&1; then ckp "$d"; else ckf "$d"; fi; }

echo "############ 29v 最后手段（MySQL）开始 $(date -u +%Y-%m-%dT%H:%M:%SZ) ############"
LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)
BACKUP=/home/web/backups/$LAUNCH
echo "CURRENT_LAUNCH=$LAUNCH"
echo "起点期望 = $H_START"
echo "基线       = $H_BASE"

echo "=========== 阶段 0 · 起点确认 ==========="
bash "$S/snapshot.sh" "$D/lsr-s-start.txt"
HS=$(sha256sum "$D/lsr-s-start.txt" | cut -d' ' -f1)
echo "起点快照 sha256 = $HS"
if [ "$HS" = "$H_START" ]; then ckp "起点快照 = 本轮正向快照"; else ckf "起点快照 != 本轮正向快照（$HS）"; fi

echo "-- db.sql --"
ls -l "$BACKUP/db.sql"
sha256sum "$BACKUP/db.sql" | tee "$D/lsr-db.sql.sha256"
DBSZ=$(stat -c %s "$BACKUP/db.sql")
DBCT=$(grep -c 'CREATE TABLE' "$BACKUP/db.sql")
echo "db.sql 字节数 = $DBSZ；CREATE TABLE 行数 = $DBCT"
[ "$DBSZ" -gt 0 ] && ckp "db.sql 非空（$DBSZ B）" || ckf "db.sql 为空"
[ "$DBCT" -gt 0 ] && ckp "db.sql 含 CREATE TABLE（$DBCT 条）" || ckf "db.sql 不含 CREATE TABLE"

echo "=========== 阶段 1 · 造一条上线后新数据（canary）==========="
set -x
CANARY_ID=$($WPCLI post create --post_type=page --post_status=draft --post_title=29v-canary --post_name=29v-canary --porcelain | tail -1 | tr -d ' \r')
set +x
echo "canary post ID = $CANARY_ID"
case "$CANARY_ID" in ''|*[!0-9]*) ckf "canary 创建（ID=$CANARY_ID）" ;; *) ckp "canary 创建（ID=$CANARY_ID）" ;; esac
echo "$CANARY_ID" > "$D/lsr-canary.id"
echo "报价页（4.5 建的）当前条数：$($WPCLI post list --post_type=page --post_status=any,draft,trash --name=get-a-quote --format=count)"

echo "=========== 阶段 2 · 最后手段（包装不在）==========="
TBL_BEFORE=$(docker exec mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='"'"'wp'"'"' AND table_type='"'"'BASE TABLE'"'"';"' 2>/dev/null | tr -d ' \r')
echo "导入前 BASE TABLE = $TBL_BEFORE"
set -x
docker exec php sh -c 'rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb; ls /usr/local/bin | grep -c mariadb || echo "包装已拆除"'
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
set +x
$WPCLI db import /tmp/db.sql; RC_NOWRAP=$?
echo "db import（无包装）退出码 = $RC_NOWRAP"
docker exec php rm -f /tmp/db.sql
TBL_NOWRAP=$(docker exec mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='"'"'wp'"'"' AND table_type='"'"'BASE TABLE'"'"';"' 2>/dev/null | tr -d ' \r')
echo "表数（导入失败后）= $TBL_NOWRAP"
$WPCLI option get siteurl 2>&1 | tail -1
if [ "$RC_NOWRAP" -ne 0 ]; then ckp "无包装时 db import 失败（rc=$RC_NOWRAP）—— 导入依赖包装"; else ckf "无包装时 db import 成功（rc=0）—— 结论要改写"; fi
if [ "${TBL_NOWRAP:-x}" = "${TBL_BEFORE:-y}" ]; then
  ckp "导入失败没有把库导坏（表数仍 $TBL_NOWRAP）"
else
  ckf "导入失败后表数变了（$TBL_BEFORE → $TBL_NOWRAP）"
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
$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes 2>&1 | tail -2
set +x
PSZ=$(docker exec php sh -c 'wc -c < /tmp/probe.sql' 2>/dev/null | tr -d ' ')
docker exec php rm -f /tmp/probe.sql
echo "探针导出字节数 = $PSZ"
case "$PSZ" in ''|0) ckf "导出探针 > 0 字节（实际 $PSZ）" ;; *) ckp "导出探针 > 0 字节（$PSZ）" ;; esac

echo "=========== 阶段 4 · 原样再执行最后手段（包装在）==========="
set -x
docker cp "$BACKUP/db.sql" php:/tmp/db.sql
$WPCLI db import /tmp/db.sql; RC_WRAP=$?
set +x
echo "db import（有包装）退出码 = $RC_WRAP"
docker exec php rm -f /tmp/db.sql
[ "$RC_WRAP" -eq 0 ] && ckp "有包装时 db import 成功（rc=0）" || ckf "有包装时 db import 仍失败（rc=$RC_WRAP）"

echo "=========== 阶段 5 · 导入后清理 ==========="
set -x
$WPCLI transient delete --all
$WPCLI cache flush || echo "no object cache plugin"
set +x

echo "=========== 阶段 6 · 验收（L-1 … L-6）==========="
TBL2=$(docker exec mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='"'"'wp'"'"' AND table_type='"'"'BASE TABLE'"'"';"' 2>/dev/null | tr -d ' \r')
echo "L-1 库里 BASE TABLE = $TBL2；db.sql 里 CREATE TABLE = $DBCT"
[ "$TBL2" = "$DBCT" ] && ckp "L-1 表数 = db.sql 的 CREATE TABLE 数（$TBL2）" || ckf "L-1 表数 $TBL2 ≠ CREATE TABLE 数 $DBCT"

set -x
$WPCLI post list --post_type=page,post --post_status=any --name=29v-canary --fields=ID,post_title --format=csv
set +x
CAN_LEFT=$($WPCLI post list --post_type=page,post --post_status=any --name=29v-canary --format=count | tr -d ' \r')
GQ_LEFT=$($WPCLI post list --post_type=page --post_status=any,draft,trash --name=get-a-quote --format=count | tr -d ' \r')
echo "canary 剩余 = $CAN_LEFT（期望 0）；报价页剩余 = $GQ_LEFT（期望 0，整库导入顺带把它删了）"
[ "$CAN_LEFT" = "0" ] && ckp "L-2 canary 被整库导入覆盖（证明会丢上线后新数据）" || ckf "L-2 canary 还在（$CAN_LEFT 条）"
[ "$GQ_LEFT" = "0" ] && ckp "L-2b 报价页已被整库导入删除" || ckf "L-2b 报价页还在（$GQ_LEFT 条）"

STY=$($WPCLI option get stylesheet); TPL=$($WPCLI option get template)
echo "stylesheet=$STY template=$TPL"
[ "$STY" = "packrivo-static-bridge-1.2.38" ] && [ "$TPL" = "packrivo-static-bridge-1.2.38" ] \
  && ckp "L-3 stylesheet/template 都是父主题 1.2.38" || ckf "L-3 不是父主题（$STY / $TPL）"

QACT=$($WPCLI plugin list --status=active --format=csv | grep -c '^packrivo-quote,' | tr -d ' \r')
echo "L-4 激活状态的 packrivo-quote 条数 = $QACT（期望 0；只看 active，文件还在属正常）"
[ "$QACT" = "0" ] && ckp "L-4 packrivo-quote 未激活" || ckf "L-4 packrivo-quote 仍激活"

bash "$S/snapshot.sh" "$D/lsr-s-after-import.txt"
awk '/^== 页面标题/,/^== 关键选项/' "$D/lsr-s-after-import.txt" | grep -v '^== 关键选项' > "$D/lsr-titles-after.txt"
awk '/^== 页面标题/,/^== 关键选项/' "$D/s-reset.txt"         | grep -v '^== 关键选项' > "$D/lsr-titles-base.txt"
if diff -q "$D/lsr-titles-base.txt" "$D/lsr-titles-after.txt" >/dev/null; then
  ckp "L-5 页面标题一节与基线逐行一致（$(wc -l < "$D/lsr-titles-base.txt") 行）"
else
  ckf "L-5 页面标题一节与基线不一致"
  diff "$D/lsr-titles-base.txt" "$D/lsr-titles-after.txt" > "$D/lsr-titles.diff" 2>&1 || true
  head -20 "$D/lsr-titles.diff"
fi

set -x
for u in "/" "/products/" "/de/"; do curl -s -o /dev/null -w "%{http_code} $u\n" "http://nginx$u"; done
set +x
[ "$(curl -s -o /dev/null -w '%{http_code}' http://nginx/)" = "200" ] && ckp "首页 200" || ckf "首页不是 200"
[ "$(curl -s -o /dev/null -w '%{http_code}' http://nginx/products/)" = "200" ] && ckp "/products/ 200" || ckf "/products/ 不是 200"
[ "$(curl -s -o /dev/null -w '%{http_code}' http://nginx/de/)" = "200" ] && ckp "/de/ 200" || ckf "/de/ 不是 200"

echo "=========== 阶段 7 · 文件部分（9.6 / 9.5 / 9.4 / 9.2）==========="
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
$WPCLI plugin deactivate packrivo-quote
$WPCLI plugin delete packrivo-quote
$WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除"
cp -a "$BACKUP/i18n/." "$I18N/"
set +x

echo "-- L-6：/get-a-quote/ 必须在 9.6（mu 恢复）之后判 --"
C4=$(curl -s -o /dev/null -w '%{http_code}' "http://nginx/get-a-quote/?cb=$(date +%s)")
echo "/get-a-quote/ = $C4（期望 301）"
[ "$C4" = "301" ] && ckp "L-6 /get-a-quote/ 301（27e 已回滚，页面已删）" || ckf "L-6 /get-a-quote/ = $C4"

echo "=========== 阶段 8 · 最终快照 vs 基线 ==========="
bash "$S/snapshot.sh" "$D/lsr-s-final.txt"
HF=$(sha256sum "$D/lsr-s-final.txt" | cut -d' ' -f1)
echo "最终 sha256 = $HF"
echo "基线   sha256 = $H_BASE"
if [ "$HF" = "$H_BASE" ]; then
  ckp "最终快照 = 基线（逐字节一致）"
else
  ckf "最终快照 ≠ 基线"
  diff "$D/s-reset.txt" "$D/lsr-s-final.txt" > "$D/lsr-final-vs-baseline.diff" 2>&1 || true
  head -40 "$D/lsr-final-vs-baseline.diff"
fi
diff "$D/lsr-s-start.txt" "$D/lsr-s-after-import.txt" > "$D/lsr-start-vs-after-import.diff" 2>&1 || true
diff "$D/lsr-s-after-import.txt" "$D/lsr-s-final.txt" > "$D/lsr-after-vs-final.diff" 2>&1 || true

echo "############ 汇总：PASS=$NP FAIL=$NF ############"
[ "$NF" -eq 0 ] || exit 1
