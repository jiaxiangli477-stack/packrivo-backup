#!/bin/sh
# 用生产现状库的原始 dump 建一个只读快照库 29z_base，在上面打基线计数（不碰当前演练库）
set -u
WP="php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
U=$($WP config get DB_USER)
P=$($WP config get DB_PASSWORD)
H=$($WP config get DB_HOST)
CLI="mariadb -h$H -u$U -p$P"
command -v mariadb >/dev/null 2>&1 || CLI="mysql -h$H -u$U -p$P"
echo "client=$CLI"
$CLI -e "DROP DATABASE IF EXISTS 29z_base; CREATE DATABASE 29z_base DEFAULT CHARACTER SET utf8mb4;" 2>/dev/null
$CLI 29z_base < /tmp/29z-prod.sql 2>/dev/null && echo "导入 29z_base 完成"
q() { $CLI -N -B 29z_base -e "$1" 2>/dev/null; }
echo "catalog_pages=$(q "SELECT COUNT(*) FROM wp_posts WHERE post_type='page' AND post_status='publish' AND post_name LIKE 'catalog-%'")"
echo "publish_1688=$(q "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%1688%'")"
echo "publish_yen=$(q "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')")"
echo "publish_detail1688=$(q "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%detail.1688.com%'")"
echo "metadesc_1688_or_yen=$(q "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_yoast_wpseo_metadesc' AND (meta_value LIKE '%1688%' OR meta_value LIKE CONCAT('%',CHAR(194,165),'%'))")"
echo "ogdesc_1688_or_yen=$(q "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_yoast_wpseo_opengraph-description' AND (meta_value LIKE '%1688%' OR meta_value LIKE CONCAT('%',CHAR(194,165),'%'))")"
echo "indexable_1688_or_yen=$(q "SELECT COUNT(*) FROM wp_yoast_indexable WHERE object_type='post' AND (description LIKE '%1688%' OR description LIKE CONCAT('%',CHAR(194,165),'%'))")"
echo "catalog_metadesc_rows=$(q "SELECT COUNT(*) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish'")"
echo "catalog_content_1688=$(q "SELECT COUNT(*) FROM wp_posts WHERE post_name LIKE 'catalog-%' AND post_status='publish' AND post_content LIKE '%1688%'")"
echo "catalog_content_yen=$(q "SELECT COUNT(*) FROM wp_posts WHERE post_name LIKE 'catalog-%' AND post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')")"
echo "publish_total=$(q "SELECT COUNT(*) FROM wp_posts WHERE post_type IN ('page','post') AND post_status='publish'")"
echo "products_page_id=$(q "SELECT ID FROM wp_posts WHERE post_name='products' LIMIT 1")"
echo "blog_post_id=$(q "SELECT ID FROM wp_posts WHERE post_name='jewelry-box-insert-fit-measurements' LIMIT 1")"
echo "products_yen_paras=$(q "SELECT (CHAR_LENGTH(post_content)-CHAR_LENGTH(REPLACE(post_content,'Listed at','')))/CHAR_LENGTH('Listed at') FROM wp_posts WHERE post_name='products'")"
