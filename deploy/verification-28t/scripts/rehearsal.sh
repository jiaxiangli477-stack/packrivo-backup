#!/bin/bash
# 28q 本地演练驱动：逐字照 deploy/RUNBOOK-launch.md 的命令执行。
# 与生产环境的唯一差别（演练固定替换）：
#   SITE=http://nginx              （演练站点，nginx 容器）
#   SITEMAP=/wp-sitemap.xml        （演练站没有 Yoast，用 WP 核心地图）
#   第 7 步只做服务器端（Cloudflare 那段由负责人亲自执行，演练跳过）
# 用法：bash rehearsal.sh <step0|step1|...|step9|verify>
set -u
STEP="${1:-}"

# ---- RUNBOOK 变量块（逐字，仅 SITE/SITEMAP 换成演练值）----
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
PKGC=/pkg
SITE=http://nginx
SITEMAP=/wp-sitemap.xml
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"

hr() { echo; echo "############### $* ###############"; echo; }

# ---- 判据记账（28r 改）：每一步的判据都打印 PASS/FAIL，本步有 FAIL 就 exit 1 ----
# 以前每个 step 结尾都是 set +x，脚本恒返回 0，exit 码证明不了任何事。
NP=0; NF=0
ckp() { NP=$((NP+1)); echo "PASS: $1"; }
ckf() { NF=$((NF+1)); echo "FAIL: $1"; }
ck()  { local d="$1"; shift; if "$@" >/dev/null 2>&1; then ckp "$d"; else ckf "$d"; fi; }
step_end() {
  local rc=0
  if [ $((NP+NF)) -eq 0 ]; then
    echo "本步判据：0 条（该步没有可机检判据）"
  else
    echo "本步判据：PASS=$NP FAIL=$NF"
    [ "$NF" -eq 0 ] || rc=1
  fi
  NP=0; NF=0
  return $rc
}

if [ "$STEP" = "selftest" ]; then
  echo "-- 自检：应当 PASS 的判据 --"
  ck "必然成立（true）" true
  echo "-- 自检：应当 FAIL 的判据 --"
  ck "必然不成立（false）" false
  echo "小结：PASS=$NP FAIL=$NF（期望 PASS=1 FAIL=1）"
  if step_end; then
    echo "!! step_end 未报错，机制有问题"
    exit 1
  fi
  echo "step_end 按预期返回非零 → 机制可用"
  exit 0
fi

case "$STEP" in

step0)
  hr "STEP 0.1 上线前检查"
  set -x
  docker ps --format '{{.Names}}\t{{.Image}}' | grep -E '^(php|mysql|nginx|redis)\b'
  $WPCLI core version
  docker exec php php -v | head -1
  $WPCLI option get siteurl
  $WPCLI option get template
  $WPCLI option get stylesheet
  $WPCLI theme list  --format=csv | grep '^packrivo-home-v4,' || echo "子主题未安装 → 首次安装"
  $WPCLI plugin list --format=csv | grep '^packrivo-quote,'  || echo "报价插件未安装 → 首次安装"
  $WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active
  docker exec php sh -c 'command -v mysqldump || apk add --no-cache mariadb-client'
  docker exec php mysqldump --version
  mkdir -p "$PKG"
  set +x

  # 0.1 导出探针：包装在 /usr/local/bin，容器重建就没了，每轮都从这一步开始
  hr "STEP 0.1 导出探针（判据：字节数 > 0）"
  set -x
  $WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes 2>&1 | tail -2
  set +x
  PSZ=$(docker exec php sh -c 'wc -c < /tmp/probe.sql 2>/dev/null' | tr -d ' ')
  docker exec php rm -f /tmp/probe.sql
  echo "探针字节数 = ${PSZ:-0}"
  if [ "${PSZ:-0}" -gt 0 ]; then
    ckp "0f 导出探针 > 0 字节（$PSZ，包装本来就在）"
  else
    echo "观察: 探针没通过（${PSZ:-0} 字节）→ 按 0.1 补救块装包装后重探。这一条不算 FAIL，判据是补救后的结果"
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
    set +x
    $WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes 2>&1 | tail -2
    PSZ2=$(docker exec php sh -c 'wc -c < /tmp/probe.sql 2>/dev/null' | tr -d ' ')
    docker exec php rm -f /tmp/probe.sql
    echo "装包装后探针字节数 = ${PSZ2:-0}"
    if [ "${PSZ2:-0}" -gt 0 ]; then
      ckp "0f 装包装后导出探针 > 0 字节（$PSZ2）"
    else
      ckf "0f 装包装后探针仍是 0 字节（${PSZ2:-0}）—— 第 1 步的备份会失败，停下"
    fi
  fi
  set +x
  hr "STEP 0.2 上传清单核对（演练环境用本地目录代替 scp）"
  set -x
  sha256sum "$PKG"/*.zip
  ls "$PKG"/i18n-merged | wc -l
  ls "$PKG"/dsh-out | wc -l
  ls "$PKG"/scripts
  set +x
  hr "STEP 0 通过判据"
  ck "0a core version = 7.1.4"   test "$($WPCLI core version)" = "7.1.4"
  ck "0b PHP = 8.3.x"            docker exec php php -r 'exit(PHP_VERSION_ID>=80300 && PHP_VERSION_ID<80400 ? 0 : 1);'
  ck "0c 首装：子主题未安装"      test ! -d "$WP/wp-content/themes/packrivo-home-v4"
  ck "0d 首装：报价插件未安装"    test ! -d "$WP/wp-content/plugins/packrivo-quote"
  ck "0e mysqldump 可用"         docker exec php mysqldump --version
  step_end || exit 1
  ;;

step1)
  hr "STEP 1 备份"
  set -x
  LAUNCH=packrivo-launch-$(date +%Y%m%d-%H%M)
  mkdir -p /home/web/backups
  echo "$LAUNCH" > /home/web/backups/CURRENT_LAUNCH
  BACKUP=/home/web/backups/$LAUNCH
  mkdir -p "$BACKUP"/{mu,i18n,db,rollback}
  cp -a "$WP/wp-content/mu-plugins/." "$BACKUP/mu/"
  cp -a "$I18N/."                     "$BACKUP/i18n/"
  sha256sum "$WP/wp-content/mu-plugins/packrivo-i18n.php" \
            "$WP/wp-content/mu-plugins/packrivo-site-remediation-20261022.php" > "$BACKUP/before-mu.sha256"
  $WPCLI db export /tmp/packrivo-before-launch.sql --skip-plugins --skip-themes
  docker cp php:/tmp/packrivo-before-launch.sql "$BACKUP/db.sql"
  docker exec php rm -f /tmp/packrivo-before-launch.sql
  $WPCLI plugin list --format=csv > "$BACKUP/before-plugin-list.csv"
  $WPCLI theme  list --format=csv > "$BACKUP/before-theme-list.csv"
  $WPCLI option get template   > "$BACKUP/before-template.txt"
  $WPCLI option get stylesheet > "$BACKUP/before-stylesheet.txt"
  $WPCLI post list --post_type=page --post_status=publish --fields=ID,post_name,post_title --format=csv > "$BACKUP/before-pages.csv"
  curl -s "$SITE$SITEMAP" > "$BACKUP/before-sitemap.xml"
  curl -s "$SITE/robots.txt"        > "$BACKUP/before-robots.txt"
  cat > "$BACKUP/INSTALL-STATE.txt" <<EOF
launch=$LAUNCH
time=$(date -u)
theme_before=$($WPCLI option get stylesheet)
parent_before=$($WPCLI option get template)
child_theme_present=$(test -d "$WP/wp-content/themes/packrivo-home-v4" && echo yes || echo no)
quote_plugin_present=$(test -d "$WP/wp-content/plugins/packrivo-quote" && echo yes || echo no)
EOF
  cat "$BACKUP/INSTALL-STATE.txt"
  set +x
  hr "STEP 1 通过判据"
  set -x
  BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
  set +x
  hr "STEP 1 通过判据"
  ck "1a mu 文件到位"          test -f "$BACKUP/mu/packrivo-i18n.php"
  ck "1b mu 文件到位"          test -f "$BACKUP/mu/packrivo-site-remediation-20261022.php"
  ck "1c i18n 文件到位"        test -f "$BACKUP/i18n/de-dict.json"
  ck "1d i18n 数量 >= 12"      test "$(ls "$BACKUP/i18n" | wc -l)" -ge 12
  ck "1e db.sql 非空"          test -s "$BACKUP/db.sql"
  ck "1f db.sql 含 CREATE TABLE" test "$(grep -c 'CREATE TABLE' "$BACKUP/db.sql")" -gt 0
  ck "1g 两个 mu 哈希"         test "$(wc -l < "$BACKUP/before-mu.sha256")" -eq 2
  ck "1h sitemap 快照非空"     test -s "$BACKUP/before-sitemap.xml"
  ck "1i 首装状态已记录"       grep -q 'child_theme_present=no' "$BACKUP/INSTALL-STATE.txt"
  ck "1j CURRENT_LAUNCH 可读"  test -s /home/web/backups/CURRENT_LAUNCH
  step_end || exit 1
  ;;

step2)
  hr "STEP 2 字典与路径表"
  set -x
  BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
  cp -a "$I18N" "$BACKUP/i18n-today"
  ls "$BACKUP/i18n-today" | head
  grep -c '"' "$BACKUP/i18n-today/de-dict.json"
  docker exec php mkdir -p "$PKGC"
  docker cp "$PKG/." php:"$PKGC/"
  docker cp "$BACKUP/i18n-today" php:/bk-i18n-today
  docker exec php rm -rf "$PKGC/merged"
  docker exec php mkdir -p /pkg/merged
  docker exec php php "$PKGC/tools/merge-i18n.php" \
      --prod=/bk-i18n-today \
      --local="$PKGC/i18n-merged" \
      --out="$PKGC/merged" \
      --require=/get-a-quote/
  mkdir -p "$PKG/merged"
  docker cp php:"$PKGC/merged/." "$PKG/merged/"
  cat "$PKG/merged/conflicts.json"
  docker exec php sh -c 'ls /pkg/merged/*-dict.json | wc -l'
  docker exec php sh -c 'ls /pkg/merged/*-js.json   | wc -l'
  docker exec php sh -c 'for f in /pkg/merged/*.json; do php -r "\$d=json_decode(file_get_contents(\$argv[1]),true); exit((json_last_error()===JSON_ERROR_NONE)?0:1);" "$f" || echo "坏 JSON: $f"; done'
  docker exec php sh -c 'grep -q "\"/get-a-quote/\"" /pkg/merged/paths.json && echo "paths ok"'
  docker exec php sh -c 'php -r "\$p=json_decode(file_get_contents(\"/pkg/merged/paths.json\"),true); exit((is_array(\$p)&&count(\$p)>=146)?0:1);" && echo "paths count ok"'
  cp "$PKG/merged"/*-dict.json "$I18N/"
  cp "$PKG/merged"/*-js.json   "$I18N/"
  cp "$PKG/merged/paths.json"  "$I18N/"
  ls -l "$I18N" | head
  set +x
  hr "STEP 2 通过判据"
  set -x
  curl -s "$SITE/de/" | grep -c 'lang="de"'
  set +x
  ck "2a paths.json 存在"        docker exec php test -f /pkg/merged/paths.json
  ck "2b paths 含 /get-a-quote/"  docker exec php grep -q "/get-a-quote/" /pkg/merged/paths.json
  ck "2c paths 条数 >= 146"       test "$(docker exec php php -r 'echo count((array)json_decode(file_get_contents("/pkg/merged/paths.json"), true));' | tr -d ' ')" -ge 146
  ck "2d dict = 11"              test "$(docker exec php sh -c 'ls /pkg/merged/*-dict.json | wc -l' | tr -d ' ')" = "11"
  ck "2e js = 11"                test "$(docker exec php sh -c 'ls /pkg/merged/*-js.json | wc -l' | tr -d ' ')" = "11"
  docker exec php sh -c 'for f in /pkg/merged/*.json; do php -r "\$d=json_decode(file_get_contents(\$argv[1]),true); exit((json_last_error()===JSON_ERROR_NONE)?0:1);" "$f" || echo "坏 JSON: $f"; done' > /tmp/badjson.txt
  ck "2f 无坏 JSON"              test ! -s /tmp/badjson.txt
  cat /tmp/badjson.txt 2>/dev/null; rm -f /tmp/badjson.txt
  ck "2g 冲突 0"                 test "$(docker exec php php -r '$c=json_decode(file_get_contents("/pkg/merged/conflicts.json"), true); echo is_array($c)?count($c):-1;')" = "0"
  ck "2h de 站 lang=de"          sh -c "curl -s \"$SITE/de/\" | grep -q 'lang=\"de\"'"
  step_end || exit 1
  ;;

step3)
  hr "STEP 3 134 产品页 + 8 主页面"
  set -x
  BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
  docker exec php mkdir -p "$PKGC/rollback"

  # 3.0 应用前守卫：/pkg/rollback 里已有的 JSON，site 必须等于本轮 $SITE
  hr "STEP 3.0 应用前守卫"
  set +x
  GUARD30=0
  for f in $(docker exec php sh -c 'ls /pkg/rollback/*.json 2>/dev/null'); do
    if docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE"; then
      ckp "3.0 $f site 与本轮一致"
    else
      S=$(docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); echo $j["site"] ?? "(无)";' "$f")
      ckf "3.0 $f 非本轮产物（site=$S，期望 $SITE），先清空 /pkg/rollback"
      GUARD30=1
    fi
  done
  [ "$GUARD30" -eq 0 ] || { echo "3.0 守卫拦截，apply 不执行"; step_end || exit 1; }
  set -x
  $WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
  $WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          plan "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
  $WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134 2>&1 | tee /tmp/a3g.txt
  $WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
  $WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          apply  "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json" 2>&1 | tee /tmp/a3i.txt
  $WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
  docker cp php:"$PKGC/rollback/." "$BACKUP/rollback/"
  ls -l "$BACKUP/rollback/"
  test -s "$BACKUP/rollback/rollback-27g.json" && echo "27g rollback ok"
  test -s "$BACKUP/rollback/rollback-27i.json" && echo "27i rollback ok"
  curl -s "$SITE/product-drawer-jewelry-box/" | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/products/"                   | grep -o '<title>[^<]*</title>'
  curl -s "$SITE/jewelry-boxes/"              | grep -o '<title>[^<]*</title>'
  set +x
  V3G=$($WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134 2>&1 | tail -1)
  echo "27g verify: $V3G"
  case "$V3G" in *URL_and_non_H1_body_unchanged=134/134*) ckp "3a 27g verify URL_and_non_H1_body_unchanged=134/134" ;; *) ckf "3a 27g verify（$V3G）" ;; esac
  V3I=$($WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php" verify "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json" 2>&1 | tail -1)
  echo "27i verify: $V3I"
  case "$V3I" in *metadata_mismatches=0*) ckp "3b 27i verify metadata_mismatches=0" ;; *) ckf "3b 27i verify（$V3I）" ;; esac
  A3G=$(grep -o 'apply_updated=[0-9]* already_current=[0-9]*' /tmp/a3g.txt | head -1)
  A3I=$(grep -o 'apply_updated=[0-9]* already_current=[0-9]*' /tmp/a3i.txt | head -1)
  echo "27g apply: $A3G"
  echo "27i apply: $A3I"
  U3G=$(echo "$A3G" | sed -n 's/.*apply_updated=\([0-9]*\).*/\1/p'); U3G=${U3G:-0}
  C3G=$(echo "$A3G" | sed -n 's/.*already_current=\([0-9]*\).*/\1/p'); C3G=${C3G:-0}
  U3I=$(echo "$A3I" | sed -n 's/.*apply_updated=\([0-9]*\).*/\1/p'); U3I=${U3I:-0}
  C3I=$(echo "$A3I" | sed -n 's/.*already_current=\([0-9]*\).*/\1/p'); C3I=${C3I:-0}
  echo "27g: apply_updated=$U3G already_current=$C3G 合计=$((U3G+C3G))"
  echo "27i: apply_updated=$U3I already_current=$C3I 合计=$((U3I+C3I))"
  ck "3·27g 总数 = 134（$U3G+$C3G=$((${U3G}+${C3G}))）" test "$((U3G+C3G))" = "134"
  ck "3·27i 总数 = 8（$U3I+$C3I=$((${U3I}+${C3I}))）"   test "$((U3I+C3I))" = "8"
  ck "3·27g apply_updated > 0（演练要求真实写入）"      test "$U3G" -gt 0
  ck "3·27i apply_updated > 0（演练要求真实写入）"      test "$U3I" -gt 0
  ck "3·27g URL_and_body_check=PASS"                    grep -q 'URL_and_body_check=PASS' /tmp/a3g.txt
  ck "3·27i H1_body_URL_check=PASS"                     grep -q 'H1_body_URL_check=PASS' /tmp/a3i.txt
  ck "3c 27g rollback 依据非空" test -s "$BACKUP/rollback/rollback-27g.json"
  ck "3d 27i rollback 依据非空" test -s "$BACKUP/rollback/rollback-27i.json"
  ck "3e 产品页 200"           test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/product-drawer-jewelry-box/")" = "200"
  step_end || exit 1
  ;;

step4)
  hr "STEP 4 报价插件（首次安装）"
  set -x
  BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
  docker cp "$PKG/packrivo-quote.zip" php:"$PKGC/packrivo-quote.zip"
  docker exec php sha256sum "$PKGC/packrivo-quote.zip"
  $WPCLI plugin install "$PKGC/packrivo-quote.zip" --force
  $WPCLI plugin activate packrivo-quote
  $WPCLI plugin list --format=csv | grep packrivo-quote
  curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"
  set +x
  ck "4a 插件已激活"   sh -c "$WPCLI plugin list --format=csv | grep -q '^packrivo-quote,active'"
  ck "4b 插件目录存在" test -d "$WP/wp-content/plugins/packrivo-quote"
  ck "4c 首页 200"     test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/")" = "200"
  step_end || exit 1
  ;;

step5)
  hr "STEP 5 子主题（首次安装）"
  set -x
  BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
  $WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active
  docker cp "$PKG/packrivo-home-v4.zip" php:"$PKGC/packrivo-home-v4.zip"
  docker exec php sha256sum "$PKGC/packrivo-home-v4.zip"
  $WPCLI theme install  "$PKGC/packrivo-home-v4.zip" --force
  $WPCLI theme activate packrivo-home-v4
  $WPCLI option get stylesheet
  $WPCLI option get template
  curl -s -o /dev/null -w '%{http_code}\n' "$SITE/"
  set +x
  ck "5a stylesheet = packrivo-home-v4"      test "$($WPCLI option get stylesheet)" = "packrivo-home-v4"
  ck "5b template = packrivo-static-bridge-1.2.38" test "$($WPCLI option get template)" = "packrivo-static-bridge-1.2.38"
  ck "5c 子主题目录存在"                      test -d "$WP/wp-content/themes/packrivo-home-v4"
  ck "5d 首页 200"                            test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/")" = "200"
  step_end || exit 1
  ;;

step6)
  hr "STEP 6 两个 mu 补丁"
  set -x
  BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
  cd "$WP/wp-content/mu-plugins"
  sha256sum packrivo-site-remediation-20261022.php
  git apply -p1 --check "$PKG/27e-quote-redirect.patch" && echo CHECK-OK
  git apply -p1         "$PKG/27e-quote-redirect.patch"
  docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php"
  cd "$WP"
  sha256sum wp-content/mu-plugins/packrivo-i18n.php
  git apply -p1 --check "$PKG/patches-28m/packrivo-i18n-404-viewport.patch" && echo CHECK-OK
  git apply -p1         "$PKG/patches-28m/packrivo-i18n-404-viewport.patch"
  docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-i18n.php"
  set +x
  hr "STEP 6 通过判据"
  set -x
  curl -sI "$SITE/get-a-quote/"    | head -3
  curl -sI "$SITE/de/get-a-quote/" | head -3
  curl -s "$SITE/ar/2026/10/26/jewelry-pouch-box-customer-handoff/" | grep -c 'name="viewport"'
  set +x
  ck "6a /get-a-quote/ 200"     test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/get-a-quote/")" = "200"
  ck "6b /de/get-a-quote/ 200"  test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/de/get-a-quote/")" = "200"
  ck "6c ar 文章页有 viewport"  sh -c "curl -s \"$SITE/ar/2026/10/26/jewelry-pouch-box-customer-handoff/\" | grep -q 'name=\"viewport\"'"
  ck "6d mu 语法 OK"            docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-i18n.php"
  ck "6e 27e 语法 OK"           docker exec php php -l "$WPC/wp-content/mu-plugins/packrivo-site-remediation-20261022.php"
  step_end || exit 1
  ;;

step7)
  hr "STEP 7 清缓存（演练：跳过 Cloudflare，只做服务器端）"
  set -x
  $WPCLI transient delete --all
  $WPCLI cache flush || echo "no object cache plugin"
  $WPCLI transient delete --all 2>&1 | tail -1
  curl -sI "$SITE/de/" | head -5
  curl -s "$SITE/de/" | grep -c 'lang="de"'
  set +x
  ck "7a /de/ 200"  test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/de/")" = "200"
  step_end || exit 1
  ;;

step8)
  hr "STEP 8 复测"
  set -x
  BACKUP=/home/web/backups/$(cat /home/web/backups/CURRENT_LAUNCH)
  for u in "$SITE/" "$SITE/products/" "$SITE/de/" "$SITE/ar/" "$SITE/product-drawer-jewelry-box/"; do
    echo -n "$u  title数="; curl -s "$u" | grep -c '<title>'
  done
  diff "$BACKUP/before-sitemap.xml" <(curl -s "$SITE$SITEMAP") && echo "sitemap 未变"
  diff "$BACKUP/before-robots.txt"  <(curl -s "$SITE/robots.txt")       && echo "robots 未变"
  curl -s "$SITE/get-a-quote/?style=rigid&qty=500" | grep -c 'pkq-root'
  curl -s -o /dev/null -w 'inquiry %{http_code}\n' "$SITE/inquiry/"
  set +x
  for u in / /products/ /de/ /ar/ /product-drawer-jewelry-box/; do
    ck "8· $u 200" test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE$u")" = "200"
  done
  TMP_S=$(mktemp); curl -s "$SITE$SITEMAP" > "$TMP_S"
  ck "8· sitemap 未变"         diff -q "$BACKUP/before-sitemap.xml" "$TMP_S"
  rm -f "$TMP_S"
  TMP_R=$(mktemp); curl -s "$SITE/robots.txt" > "$TMP_R"
  ck "8· robots 未变"          diff -q "$BACKUP/before-robots.txt" "$TMP_R"
  rm -f "$TMP_R"
  ck "8· 报价页渲染 pkq-root"  sh -c "curl -s \"$SITE/get-a-quote/?style=rigid&qty=500\" | grep -q 'pkq-root'"
  ck "8· /inquiry/ 200"        test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/inquiry/")" = "200"
  step_end || exit 1
  ;;

step9)
  hr "STEP 9 整体回滚"
  set -x
  LAUNCH=$(cat /home/web/backups/CURRENT_LAUNCH)
  BACKUP=/home/web/backups/$LAUNCH
  echo "回滚目标: $BACKUP"; test -f "$BACKUP/db.sql" && echo "备份在位"
  $WPCLI transient delete --all
  $WPCLI cache flush || echo "no object cache plugin"
  cp -a "$BACKUP/mu/packrivo-i18n.php"                      "$WP/wp-content/mu-plugins/"
  cp -a "$BACKUP/mu/packrivo-site-remediation-20261022.php" "$WP/wp-content/mu-plugins/"
  sha256sum -c "$BACKUP/before-mu.sha256"
  $WPCLI theme activate packrivo-static-bridge-1.2.38
  rm -r "$WP/wp-content/themes/packrivo-home-v4"
  $WPCLI theme list --format=csv | grep packrivo-home-v4 || echo "子主题已移除"
  $WPCLI plugin deactivate packrivo-quote
  $WPCLI plugin delete packrivo-quote
  $WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件已移除"
  docker cp "$BACKUP/rollback/rollback-27i.json" php:"$PKGC/rollback/"
  docker cp "$BACKUP/rollback/rollback-27g.json" php:"$PKGC/rollback/"

  # 9.3 回滚前守卫：两个 JSON 的 site 必须等于本轮 $SITE
  hr "STEP 9.3 回滚前守卫"
  set +x
  GUARD93=0
  for f in /pkg/rollback/rollback-27g.json /pkg/rollback/rollback-27i.json; do
    if docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE"; then
      ckp "9.3 $f site 与本轮一致"
    else
      S=$(docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); echo $j["site"] ?? "(无)";' "$f")
      ckf "9.3 $f site=$S 与本轮 $SITE 不一致"
      GUARD93=1
    fi
  done
  [ "$GUARD93" -eq 0 ] || { echo "9.3 守卫拦截，rollback 不执行"; step_end || exit 1; }
  set -x
  $WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
  $WPCLI eval-file "$PKGC/scripts/hubs-27i/import.php"          rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27i.json"
  $WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback-check "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
  $WPCLI eval-file "$PKGC/scripts/product-buyers-27g/import.php" rollback       "$PKGC" "$SITE" "$PKGC/rollback/rollback-27g.json" all134
  cp -a "$BACKUP/i18n/." "$I18N/"
  set +x
  hr "STEP 9 判据"
  set -x
  sha256sum -c "$BACKUP/before-mu.sha256"
  $WPCLI option get stylesheet
  $WPCLI plugin list --format=csv | grep packrivo-quote || echo "报价插件不在列表里"
  for u in "$SITE/" "$SITE/products/" "$SITE/de/"; do echo -n "$u  title数="; curl -s "$u" | grep -c '<title>'; done
  curl -s "$SITE/product-drawer-jewelry-box/" | grep -o '<title>[^<]*</title>'
  set +x
  ck "9a before-mu.sha256 全 OK"  sha256sum -c "$BACKUP/before-mu.sha256"
  ck "9b stylesheet 回到父主题"    test "$($WPCLI option get stylesheet)" = "packrivo-static-bridge-1.2.38"
  ck "9c 子主题目录已删"          test ! -d "$WP/wp-content/themes/packrivo-home-v4"
  ck "9d 报价插件已移除"          sh -c "! $WPCLI plugin list --format=csv | grep -q '^packrivo-quote,'"
  ck "9e 首页 200"                test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/")" = "200"
  ck "9f /get-a-quote/ 回到 301"  test "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/get-a-quote/")" = "301"
  step_end || exit 1
  ;;

*)
  echo "用法: bash rehearsal.sh <step0|step1|...|step9>"
  exit 2
  ;;
esac
