#!/bin/bash
# 演练环境复位：回到"首装前 + 上线前内容"的状态（不属于 RUNBOOK 步骤）
set -u
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"

echo "== 1) 站点地址指回演练站 =="
$WPCLI option update siteurl http://nginx >/dev/null
$WPCLI option update home    http://nginx >/dev/null
$WPCLI core update-db >/dev/null 2>&1
$WPCLI option get siteurl

echo "== 2) mu 回到打补丁前 =="
cp -a /home/web/pristine-mu/packrivo-site-remediation-20261022.php "$WP/wp-content/mu-plugins/"
cp -a /home/web/pristine-mu/packrivo-i18n.php                      "$WP/wp-content/mu-plugins/"

echo "== 3) 字典回到演练前（pristine-i18n）=="
rm -rf "$I18N"
cp -a /home/web/pristine-i18n "$I18N"
echo "i18n 文件数: $(ls "$I18N" | wc -l)"

echo "== 4) 主题与插件回到首装前 =="
$WPCLI theme activate packrivo-static-bridge-1.2.38 >/dev/null 2>&1
$WPCLI plugin deactivate packrivo-quote >/dev/null 2>&1
rm -rf "$WP/wp-content/themes/packrivo-home-v4" "$WP/wp-content/plugins/packrivo-quote"

echo "== 5) 清掉上一轮遗留的 rollback 依据，重灌干净的包 =="
docker exec php sh -c 'rm -rf /pkg && mkdir -p /pkg'
docker cp /home/web/packrivo-packages/. php:/pkg/

echo "== 6) 内容回到上线前（按 slug 写回依据里的旧值）=="
docker cp /home/web/seed-rollback/rollback-27g.json php:/pkg/rb27g.json
docker cp /home/web/seed-rollback/rollback-27i.json php:/pkg/rb27i.json
docker cp /home/web/prep-restore-slug.php php:/pkg/prep-restore-slug.php
$WPCLI eval-file /pkg/prep-restore-slug.php /pkg/rb27g.json 2>&1 | tail -2
$WPCLI eval-file /pkg/prep-restore-slug.php /pkg/rb27i.json 2>&1 | tail -2

echo "== 7) 状态确认 =="
$WPCLI option get stylesheet
$WPCLI theme list --format=csv | grep '^packrivo-home-v4,' || echo "子主题未安装 ✓"
$WPCLI plugin list --format=csv | grep '^packrivo-quote,' || echo "报价插件未安装 ✓"
$WPCLI post list --post_type=page --name=clear-display-box --field=post_title 2>&1 | tail -1
curl -s -o /dev/null -w 'get-a-quote %{http_code}（期望 301）\n' http://nginx/get-a-quote/
curl -s -o /dev/null -w '首页 %{http_code}（期望 200）\n' http://nginx/
