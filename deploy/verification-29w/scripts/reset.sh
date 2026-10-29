#!/bin/bash
# 演练环境复位：回到"首装前 + 上线前内容"的状态（不属于 RUNBOOK 步骤）
# 29w 起：
#   1) 删掉演练库里的 get-a-quote 页（生产首装前本来就没有，演练库以前自带 ID 3220 掩盖了这个问题）
#   2) 拆掉 mariadb-connector-c（保证每轮第 0 步都会真的走「TLS 报错 → 装包装 → 1045 → 装插件」）
#   3) 拆掉 /usr/local/bin 里的 --skip-ssl 包装
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
# 删除也在容器内做一遍：Windows 绑定挂载上「宿主机删、容器建」偶发不一致（29w 实测），两侧都删更稳
$WPCLI theme delete packrivo-home-v4 --force 2>/dev/null
docker exec php rm -rf "$WPC/wp-content/themes/packrivo-home-v4" "$WPC/wp-content/plugins/packrivo-quote"
rm -rf "$WP/wp-content/themes/packrivo-home-v4" "$WP/wp-content/plugins/packrivo-quote"

echo "== 5) 删掉报价页（任何状态），让首装前状态与生产一致 =="
$WPCLI post list --post_type=page --post_status=any,draft,pending,private,future,trash \
                 --name=get-a-quote --fields=ID,post_status --format=csv
for id in $($WPCLI post list --post_type=page --post_status=any,draft,pending,private,future,trash \
                          --name=get-a-quote --field=ID 2>/dev/null); do
  echo "  删除报价页 ID=$id"
  $WPCLI post delete "$id" --force
done
echo "  剩余 get-a-quote 页面数: $($WPCLI post list --post_type=page --post_status=any,draft,trash --name=get-a-quote --format=count)"

echo "== 5.5) 删掉页面 8 的 _wp_page_template（29w 起：生产首装前没有这条 meta）=="
P8T=$($WPCLI post meta get 8 _wp_page_template 2>/dev/null); echo "  复位前 = ${P8T:-（无）}"
$WPCLI post meta delete 8 _wp_page_template 2>/dev/null || echo "  本来就没有这条 meta"
$WPCLI post meta list 8 --keys=_wp_page_template --format=csv
$WPCLI post meta get 8 _wp_page_template >/dev/null 2>&1; echo "  复位后 post meta get rc=$?（期望 1）"

echo "== 6) 拆掉 mariadb-connector-c 与 --skip-ssl 包装（让第 0 步重走两层补救）=="
docker exec php sh -c 'apk del mariadb-connector-c 2>/dev/null | tail -2'
docker exec php sh -c 'rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb'
echo "  认证插件目录文件数: $(docker exec php sh -c 'ls /usr/lib/mariadb/plugin 2>/dev/null | wc -l')"
echo "  /usr/local/bin 里的 mariadb 包装: $(docker exec php sh -c 'ls /usr/local/bin | grep -c mariadb || true')"

echo "== 7) 清掉上一轮遗留的 rollback 依据，重灌干净的包 =="
docker exec php sh -c 'rm -rf /pkg && mkdir -p /pkg'
docker cp /home/web/packrivo-packages/. php:/pkg/

echo "== 8) 内容回到上线前（按 slug 写回依据里的旧值）=="
docker cp /home/web/seed-rollback/rollback-27g.json php:/pkg/rb27g.json
docker cp /home/web/seed-rollback/rollback-27i.json php:/pkg/rb27i.json
docker cp /home/web/prep-restore-slug.php php:/pkg/prep-restore-slug.php
$WPCLI eval-file /pkg/prep-restore-slug.php /pkg/rb27g.json 2>&1 | tail -2
$WPCLI eval-file /pkg/prep-restore-slug.php /pkg/rb27i.json 2>&1 | tail -2

echo "== 9) 状态确认 =="
$WPCLI option get stylesheet
$WPCLI theme list --format=csv | grep '^packrivo-home-v4,' || echo "子主题未安装 ✓"
$WPCLI plugin list --format=csv | grep '^packrivo-quote,' || echo "报价插件未安装 ✓"
$WPCLI post list --post_type=page --name=clear-display-box --field=post_title 2>&1 | tail -1
curl -s -o /dev/null -w 'get-a-quote %{http_code}（期望 301）\n' http://nginx/get-a-quote/
curl -s -o /dev/null -w '首页 %{http_code}（期望 200）\n' http://nginx/
