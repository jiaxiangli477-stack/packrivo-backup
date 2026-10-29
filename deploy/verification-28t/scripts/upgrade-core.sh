#!/bin/bash
# 28t 演练环境 core 升级到 7.1.4（官方包），前后各取一次 core 清单做对照
set -u
WPC=/var/www/html/www.packrivo.com/wordpress
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"
D=/home/web/rehearsal-28t
mkdir -p "$D"

echo "############### core 升级 7.1.3 → 7.1.4  $(date -u +%FT%TZ) ###############"

echo "--- 升级前 ---"
$WPCLI core version
docker exec php php -r 'include "/var/www/html/www.packrivo.com/wordpress/wp-includes/version.php"; echo "wp_version=$wp_version  wp_db_version=$wp_db_version\n";'

echo "--- 升级前 core 清单（wp-admin + wp-includes + 根 php）---"
docker exec php sh -c 'cd /var/www/html/www.packrivo.com/wordpress && find wp-admin wp-includes -type f -name "*.php" -o -type f -name "*.js" | sort | xargs sha256sum 2>/dev/null; sha256sum *.php 2>/dev/null' > "$D/core-manifest-7.1.3.txt"
echo "清单行数: $(wc -l < "$D/core-manifest-7.1.3.txt")"

echo "--- 官方包 ---"
ls -l /home/web/wordpress-7.1.4.zip
sha256sum /home/web/wordpress-7.1.4.zip
docker cp /home/web/wordpress-7.1.4.zip php:/tmp/wordpress-7.1.4.zip

echo "--- wp core update（本地 zip，离线）---"
$WPCLI core update /tmp/wordpress-7.1.4.zip --force 2>&1 | tail -12

echo "--- 升级后 ---"
$WPCLI core version
docker exec php php -r 'include "/var/www/html/www.packrivo.com/wordpress/wp-includes/version.php"; echo "wp_version=$wp_version  wp_db_version=$wp_db_version\n";'
$WPCLI core update-db 2>&1 | tail -3

echo "--- 升级后 core 清单 ---"
docker exec php sh -c 'cd /var/www/html/www.packrivo.com/wordpress && find wp-admin wp-includes -type f -name "*.php" -o -type f -name "*.js" | sort | xargs sha256sum 2>/dev/null; sha256sum *.php 2>/dev/null' > "$D/core-manifest-7.1.4.txt"
echo "清单行数: $(wc -l < "$D/core-manifest-7.1.4.txt")"

echo "--- 变动的 core 文件 ---"
diff "$D/core-manifest-7.1.3.txt" "$D/core-manifest-7.1.4.txt" | grep '^[<>]' | awk '{print $1, $3}' | sort -k2 | sed 's/^</旧 /; s/^>/新 /' | awk '{print $2}' | sort -u > "$D/core-changed-files.txt"
echo "变动文件数: $(wc -l < "$D/core-changed-files.txt")"
cat "$D/core-changed-files.txt"

echo "--- 升级前后 mu-plugin / 主题 / 插件目录未动确认 ---"
ls -l "$WPC/wp-content/mu-plugins" 2>/dev/null | head -6
docker exec php rm -f /tmp/wordpress-7.1.4.zip

echo "############### 升级结束 ###############"
exit 0
