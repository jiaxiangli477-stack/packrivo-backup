#!/bin/sh
# 29z §1 机制演练（在 seed 库上，不是 prod 库）：前态快照 → apply → 六条判据 → 幂等 → 复位
set -u
WP="php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
BK=/tmp/29z-db-before.sql

echo "=== 0) 前态快照 ==="
$WP db export $BK --quiet && echo "  导出 $(wc -c < $BK) 字节 -> $BK"
$WP db query "SELECT COUNT(*) AS catalog FROM wp_posts WHERE post_type='page' AND post_status='publish' AND post_name LIKE 'catalog-%'" --skip-column-names
echo "  /products/ 渲染哈希: $(curl -s http://nginx/products/ | sha256sum | cut -d' ' -f1)"
echo "  首页 渲染哈希:       $(curl -s http://nginx/ | sha256sum | cut -d' ' -f1)"
echo "  catalog 页 3037 哈希:$(curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | sha256sum | cut -d' ' -f1)"
$WP db query "SELECT SUM(post_content LIKE '%1688%') AS c1688, SUM(post_content LIKE '%¥%') AS cyen FROM wp_posts WHERE post_status='publish'" --skip-column-names

echo "=== 1) apply ==="
STRIP1688_DRY=0 STRIP1688_AUDIT=/tmp/audit-29z.json $WP eval-file /tmp/strip-1688.php 2>&1 | tail -c 1600

echo "=== 2) 六条判据 ==="
echo -n "  判据1 正文含 1688/¥/detail.1688.com 的 publish 行数: "
$WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND (post_content LIKE '%1688%' OR post_content LIKE '%¥%')" --skip-column-names
echo -n "  判据2 meta 含 1688/¥/supplier/listing 的条数: "
$WP db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key IN ('_yoast_wpseo_metadesc','_yoast_wpseo_opengraph-description') AND (meta_value LIKE '%1688%' OR meta_value LIKE '%¥%' OR meta_value LIKE '%supplier%' OR meta_value LIKE '%listing%')" --skip-column-names
echo -n "  判据3 catalog 页 meta 总数/去重数: "
$WP db query "SELECT COUNT(*), COUNT(DISTINCT meta_value) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish'" --skip-column-names
echo -n "  判据4 meta 最长字符数: "
$WP db query "SELECT MAX(CHAR_LENGTH(meta_value)) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%'" --skip-column-names
echo -n "  判据5 含 MOQ 500 per design 的 meta 条数: "
$WP db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_yoast_wpseo_metadesc' AND meta_value LIKE '%MOQ 500 per design%'" --skip-column-names
echo "  判据6 /products/ 渲染哈希: $(curl -s http://nginx/products/ | sha256sum | cut -d' ' -f1)"
echo "        catalog 页 3037 哈希: $(curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | sha256sum | cut -d' ' -f1)"
echo "  catalog 页 3037 的 meta description:"
curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | grep -o '<meta name="description" content="[^"]*"' | head -1
echo "  catalog 页 3037 里 Get a price 链接数: $(curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | grep -c 'Get a price')"

echo "=== 3) 幂等（再跑一次 apply）==="
STRIP1688_DRY=0 $WP eval-file /tmp/strip-1688.php 2>&1 | grep -E '改动合计|正文被改的页|meta 要写的条数'

echo "=== 4) 复位 ==="
$WP db import $BK --quiet && echo "  已导入 $BK"
$WP cache flush 2>/dev/null | head -1
echo "  复位后 catalog 页 3037 哈希: $(curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | sha256sum | cut -d' ' -f1)"
echo "  复位后 /products/ 哈希:      $(curl -s http://nginx/products/ | sha256sum | cut -d' ' -f1)"
$WP db query "SELECT SUM(post_content LIKE '%1688%') AS c1688, SUM(post_content LIKE '%¥%') AS cyen FROM wp_posts WHERE post_status='publish'" --skip-column-names
