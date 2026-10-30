#!/bin/sh
# 29z strip-1688 dry-run 验收驱动（本机演练环境 php 容器内运行）
# 只做 dry-run：STRIP1688_DRY=1，脚本内部所有写库操作都被 $dry 门控，不写库。
# 输出：dry-run 前基线计数 -> dry-run 完整输出 -> dry-run 后基线计数（应与前一致）-> 逐项 PASS/FAIL。
set -u
WP="php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"

baseline() {
    echo "  catalog-* publish 页数:"
    $WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='page' AND post_status='publish' AND post_name LIKE 'catalog-%'" --skip-column-names
    echo "  post_content 含 1688 的 publish 页数 / 文章数:"
    $WP db query "SELECT (SELECT COUNT(*) FROM wp_posts WHERE post_type='page' AND post_status='publish' AND post_content LIKE '%1688%'), (SELECT COUNT(*) FROM wp_posts WHERE post_type='post' AND post_status='publish' AND post_content LIKE '%1688%')" --skip-column-names
    echo "  post_content 含 ¥ 的 publish 页数:"
    $WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='page' AND post_status='publish' AND post_content LIKE '%¥%'" --skip-column-names
    echo "  _yoast_wpseo_metadesc / opengraph-description 含 1688 或 ¥ 的条数:"
    $WP db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key IN ('_yoast_wpseo_metadesc','_yoast_wpseo_opengraph-description') AND (meta_value LIKE '%1688%' OR meta_value LIKE '%¥%')" --skip-column-names
    echo "  wp_yoast_indexable description 含 1688 或 ¥ 的条数（表不存在则空）:"
    $WP db query "SELECT COUNT(*) FROM wp_yoast_indexable WHERE description LIKE '%1688%' OR description LIKE '%¥%'" --skip-column-names 2>/dev/null || echo "    （wp_yoast_indexable 不存在或查询失败）"
    echo "  catalog 页 3037 渲染哈希:"
    curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | sha256sum | cut -d' ' -f1
    echo "  /products/ 渲染哈希:"
    curl -s http://nginx/products/ | sha256sum | cut -d' ' -f1
}

echo "##### 0) 执行物指纹（容器内 /tmp 副本）#####"
sha256sum /tmp/strip-1688.php /tmp/strip-1688.sh 2>/dev/null || echo "  /tmp 下暂无副本（待 docker cp）"

echo
echo "##### 1) dry-run 前基线 #####"
baseline

echo
echo "##### 2) strip-1688 dry-run（字面量 wrapper：bash /tmp/strip-1688.sh --dry-run）#####"
cd /tmp
STRIP1688_AUDIT=/tmp/29z-dryrun-audit.json bash /tmp/strip-1688.sh --dry-run 2>&1 | tee /tmp/29z-dryrun.out
echo
echo "##### 3) dry-run 后基线（应与前一致）#####"
baseline

echo
echo "##### 4) dry-run 写库检查（结果必须全 0）#####"
echo "  正文含 1688/¥ 的 publish 行数（应与前基线一致，dry-run 不改）:"
$WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND (post_content LIKE '%1688%' OR post_content LIKE '%¥%')" --skip-column-names
echo "  postmeta 里 Get a price 相关改动痕迹（应为 0）:"
$WP db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_yoast_wpseo_metadesc' AND meta_value LIKE '%MOQ 500 per design%'" --skip-column-names
echo "  audit 文件内容（dry-run 的审计快照，不写库）:"
cat /tmp/29z-dryrun-audit.json 2>/dev/null | head -c 2000
echo
echo "##### 5) dry-run 输出摘要（tee 到 /tmp/29z-dryrun.out）#####"
grep -E '=====|catalog 页|正文被改的页|改动合计|meta 要写的条数|meta 长度|meta 去重' /tmp/29z-dryrun.out || true
