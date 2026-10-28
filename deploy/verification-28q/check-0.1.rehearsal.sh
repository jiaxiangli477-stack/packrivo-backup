#!/bin/bash
# 28q 演练环境 0.1 上线前检查（本地同构版）
# 与 deploy/RUNBOOK-launch.md @6f4aac1 §0.1 逐条对应；siteurl 按本地演练适配为 http://nginx
set -u
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
PKG=/home/web/packrivo-packages
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"

echo "== [0.1] 容器与版本 =="
docker ps --format '{{.Names}}\t{{.Image}}' | grep -E '^(php|mysql|nginx|redis)'
echo "-- core version (期望 7.1.3):"
$WPCLI core version
echo "-- php version (期望 8.3.x):"
docker exec php php -v | head -1
echo "-- siteurl (本地演练适配为 http://nginx):"
$WPCLI option get siteurl
echo "-- template (期望 packrivo-static-bridge-1.2.38):"
$WPCLI option get template
echo "-- stylesheet (期望与 template 相同=子主题未装):"
$WPCLI option get stylesheet

echo "== [0.1] 首次安装现状确认（两条 grep 都应无输出） =="
$WPCLI theme list  --format=csv | grep '^packrivo-home-v4,' || echo "子主题未安装 → 首次安装"
$WPCLI plugin list --format=csv | grep '^packrivo-quote,'  || echo "报价插件未安装 → 首次安装"
echo "-- 活跃主题（父主题可回切）:"
$WPCLI theme list --format=csv | grep packrivo-static-bridge | grep active

echo "== [0.1] mysqldump 与目录 =="
docker exec php sh -c 'command -v mysqldump || apk add --no-cache mariadb-client'
docker exec php mysqldump --version
mkdir -p "$PKG"
echo "-- 宿主侧目录:"
ls -d "$WP" "$I18N" "$PKG" /home/web/backups
echo "-- i18n 字典文件数 (期望 23):"
ls "$I18N" | wc -l
echo "-- 宿主无 php/wp（RUNBOOK 前提）:"
( command -v php || echo "NO-PHP-ON-HOST" )
( command -v wp  || echo "NO-WP-ON-HOST"  )

echo "== [1.5] INSTALL-STATE 判定依据 =="
echo "theme_before=$($WPCLI option get stylesheet)"
echo "parent_before=$($WPCLI option get template)"
echo "child_theme_present=$(test -d "$WP/wp-content/themes/packrivo-home-v4" && echo yes || echo no)"
echo "quote_plugin_present=$(test -d "$WP/wp-content/plugins/packrivo-quote" && echo yes || echo no)"
