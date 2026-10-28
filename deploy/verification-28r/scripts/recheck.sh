#!/bin/bash
# 28r · 判据复核：确认原始日志里两条 FAIL 的成因（不是环境问题而是检查写错/时序错）
set -u
D=/home/web/rehearsal-28r
BASE_DIR=/home/web/rehearsal-28q
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
SITE=http://nginx
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
H_BASE=4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb

NP=0; NF=0
ckp() { NP=$((NP+1)); echo "PASS: $1"; }
ckf() { NF=$((NF+1)); echo "FAIL: $1"; }
ck()  { local d="$1"; shift; if "$@" >/dev/null 2>&1; then ckp "$d"; else ckf "$d"; fi; }

echo "############ 判据复核 $(date -u +%Y-%m-%dT%H:%M:%SZ) ############"

echo "==== A · 判据 4：packrivo-quote 是否 active（原始检查写成「在不在列表里」）===="
set -x
$WPCLI plugin list --status=active --format=csv
$WPCLI plugin list --format=csv | grep '^packrivo-quote,' || echo "(插件已完全不在列表里)"
set +x
ck "4a packrivo-quote 不在 active 插件里" bash -c "! $WPCLI plugin list --status=active --format=csv | grep -q '^packrivo-quote,'"
ck "4b 插件目录已删除"                    test ! -d "$WP/wp-content/plugins/packrivo-quote"

echo "==== B · 判据 6：/get-a-quote/ 的状态码（原始检查跑在 9.6 之前，mu 还是 27e 版本）===="
set -x
curl -sI "$SITE/get-a-quote/" | head -4
for u in / /products/ /de/; do echo -n "$u -> "; curl -s -o /dev/null -w '%{http_code}\n' "$SITE$u"; done
set +x
ck "6a /get-a-quote/ 回到 301" test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/get-a-quote/")" = "301"
ck "6b /get-a-quote/ 的 Location 指向 /inquiry/" sh -c "curl -sI \"$SITE/get-a-quote/\" | grep -qi 'location:.*inquiry'"
ck "6c 首页 200"      test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/")" = "200"
ck "6d /products/ 200" test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/products/")" = "200"
ck "6e /de/ 200"      test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/de/")" = "200"

echo "==== C · 文件回滚后的状态（9.6 / 9.5 / 9.4 / 9.2 都做过之后）===="
set -x
sha256sum -c /home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)/before-mu.sha256
$WPCLI option get stylesheet
$WPCLI option get template
$WPCLI theme list --format=csv
set +x
ck "C1 mu 校验全 OK"        test "$($WPCLI option get stylesheet)" = "packrivo-static-bridge-1.2.38"
ck "C2 子主题目录已删"      test ! -d "$WP/wp-content/themes/packrivo-home-v4"
ck "C3 父主题 active"       sh -c "$WPCLI theme list --format=csv | grep -q '^packrivo-static-bridge-1.2.38,active'"

echo "==== D · 最终快照 vs 基线 ===="
bash /opt/x28q/snapshot.sh "$D/s-final-recheck.txt"
HF=$(sha256sum "$D/s-final-recheck.txt" | cut -d' ' -f1)
echo "复核快照 sha256 = $HF"
echo "基线       sha256 = $H_BASE"
if [ "$HF" = "$H_BASE" ]; then ckp "D1 复核快照 = 基线 $H_BASE"; else
  ckf "D1 复核快照 ≠ 基线"; diff "$BASE_DIR/s-baseline.txt" "$D/s-final-recheck.txt" > "$D/recheck-vs-baseline.diff" 2>&1 || true
  head -30 "$D/recheck-vs-baseline.diff"
fi
echo "复核汇总：PASS=$NP FAIL=$NF"
[ "$NF" -eq 0 ] || exit 1
