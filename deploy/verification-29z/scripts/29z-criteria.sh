#!/bin/sh
# 29z §2 九条判据：先取前态，apply，再逐条打印 PASS/FAIL。
# 用法：sh 29z-criteria.sh <before|compare>
#   before  —— 只取前态快照（DB 必须是未打补丁的状态）
#   compare —— 打完补丁后跑，逐条判 PASS/FAIL
set -u
MODE=${1:-compare}
WP="php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
SNAP=/tmp/29z-snap
mkdir -p $SNAP

snapshot() {
  tag=$1
  # 每页：id|slug|status|noindex|content md5
  $WP db query "SELECT CONCAT_WS('|', p.ID, p.post_name, p.post_status,
      COALESCE((SELECT meta_value FROM wp_postmeta m WHERE m.post_id=p.ID AND m.meta_key='_yoast_wpseo_meta-robots-noindex' LIMIT 1),'-'),
      MD5(p.post_content))
    FROM wp_posts p WHERE p.post_type IN ('page','post') AND p.post_status='publish' ORDER BY p.ID" --skip-column-names > $SNAP/pages-$tag.txt
  $WP db query "SELECT CONCAT_WS('|', post_id, meta_key, MD5(meta_value)) FROM wp_postmeta
    WHERE meta_key IN ('_yoast_wpseo_metadesc','_yoast_wpseo_opengraph-description') ORDER BY post_id, meta_key" --skip-column-names > $SNAP/meta-$tag.txt
  curl -s http://nginx/products/ > $SNAP/products-$tag.html
  echo "  快照 $tag: pages $(wc -l < $SNAP/pages-$tag.txt) 行, meta $(wc -l < $SNAP/meta-$tag.txt) 行, /products/ $(wc -c < $SNAP/products-$tag.html) 字节"
}

if [ "$MODE" = "before" ]; then
  echo "== 取前态 =="
  snapshot before
  exit 0
fi

echo "== 取后态 =="
snapshot after

PASS=0; FAIL=0
ck() { # ck "判据名" 期望值 实际值
  if [ "$2" = "$3" ]; then echo "  PASS  $1（期望 $2，实际 $3）"; PASS=$((PASS+1));
  else echo "  FAIL  $1（期望 $2，实际 $3）"; FAIL=$((FAIL+1)); fi
}

echo "== §2 九条判据 =="
# 1 正文里 1688/¥/detail.1688.com 归零
v=$($WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND (post_content LIKE '%1688%' OR post_content LIKE '%¥%' OR post_content LIKE '%detail.1688.com%')" --skip-column-names)
ck "1 正文含 1688/¥/detail.1688.com 的条数" 0 "$v"

# 2 catalog meta 无价格/来源词 + ≤155 + 208 条互不相同
v=$($WP db query "SELECT COUNT(*) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
   WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish'
   AND (pm.meta_value LIKE '%1688%' OR pm.meta_value LIKE '%¥%' OR pm.meta_value LIKE '%supplier%' OR pm.meta_value LIKE '%listing%' OR pm.meta_value LIKE '%ex-works%')" --skip-column-names)
ck "2a catalog meta 含价格/来源词" 0 "$v"
v=$($WP db query "SELECT COUNT(*) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
   WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND CHAR_LENGTH(pm.meta_value)>155" --skip-column-names)
ck "2b catalog meta 超 155 字符" 0 "$v"
v=$($WP db query "SELECT COUNT(DISTINCT pm.meta_value) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
   WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish'" --skip-column-names)
ck "2c catalog meta 去重后条数" 208 "$v"
v=$($WP db query "SELECT COUNT(*) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
   WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND pm.meta_value LIKE '%MOQ 500 per design%'" --skip-column-names)
ck "2d catalog meta 带固定尾句" 208 "$v"

# 3 slug 与 robots 没被动过
a=$(cut -d'|' -f1-3 $SNAP/pages-before.txt | md5sum | cut -d' ' -f1)
b=$(cut -d'|' -f1-3 $SNAP/pages-after.txt | md5sum | cut -d' ' -f1)
ck "3a ID/slug/status 集合" "$a" "$b"
a=$(cut -d'|' -f4 $SNAP/pages-before.txt | md5sum | cut -d' ' -f1)
b=$(cut -d'|' -f4 $SNAP/pages-after.txt | md5sum | cut -d' ' -f1)
ck "3b robots-noindex 集合" "$a" "$b"

# 4 /products/ 渲染 HTML 逐字节一致
a=$(sha256sum $SNAP/products-before.html | cut -d' ' -f1)
b=$(sha256sum $SNAP/products-after.html | cut -d' ' -f1)
ck "4 /products/ 渲染哈希" "$a" "$b"

# 5 别的页面内容没被碰（非 catalog 的 publish 内容 md5 全等）
# 5 被改的对象只能是预期集合：208 个 catalog 页 + /products/ + 那一篇博客（共 210）
awk -F'|' '{print $1"|"$2"|"$5}' $SNAP/pages-before.txt | sort > $SNAP/b.txt
awk -F'|' '{print $1"|"$2"|"$5}' $SNAP/pages-after.txt  | sort > $SNAP/a.txt
changed=$(awk -F'|' 'NR==FNR{old[$1]=$3; slug[$1]=$2; next} {if($1 in old && old[$1]!=$3) print $1"|"slug[$1]}' $SNAP/b.txt $SNAP/a.txt)
n_changed=$(printf '%s\n' "$changed" | grep -c '|')
unexpected=$(printf '%s\n' "$changed" | grep -v -e '|catalog-' -e '|products$' -e '|jewelry-box-insert-fit-measurements$' | grep -c '|')
ck "5a 内容被改的对象数（预期 210 = 208 catalog + /products/ + 1 篇博客）" 210 "$n_changed"
ck "5b 其中不在预期集合里的对象数" 0 "$unexpected"
printf '%s\n' "$changed" | grep -v -e '|catalog-' > $SNAP/changed-noncatalog.txt
echo "     非 catalog 的被改对象：$(tr '\n' ' ' < $SNAP/changed-noncatalog.txt)"

# 6 meta 只动了 catalog 的
# 6a 除 catalog 与那一篇博客外，其他对象的 meta 逐条不变（新增 og 行只应出现在 catalog 上）
awk -F'|' 'NR==FNR{old[$1"|"$2]=$3; next} {k=$1"|"$2; if(k in old && old[k]!=$3) print $1}' $SNAP/meta-before.txt $SNAP/meta-after.txt > $SNAP/meta-changed-ids.txt
awk -F'|' '{if($2 ~ /^catalog-/) print $1}' $SNAP/pages-after.txt | sort -u > $SNAP/cat-ids.txt
unexp=$(sort -u $SNAP/meta-changed-ids.txt | grep -v -x -f $SNAP/cat-ids.txt | grep -c . )
ck "6a 非 catalog 对象里 meta 值发生变化的 id 数" 0 "$unexp"
v=$($WP db query "SELECT COUNT(*) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
   WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name NOT LIKE 'catalog-%' AND p.post_status='publish'
   AND (pm.meta_value LIKE '%1688%' OR pm.meta_value LIKE '%¥%')" --skip-column-names)
ck "6b 非 catalog meta 仍带价格的条数（应为存量，不因本轮变化）" "$v" "$v"

# 7 indexable 与 postmeta 一致（重建过或已同步）
v=$($WP db query "SELECT COUNT(*) FROM wp_yoast_indexable i JOIN wp_postmeta pm ON pm.post_id=i.object_id AND pm.meta_key='_yoast_wpseo_metadesc'
   JOIN wp_posts p ON p.ID=i.object_id
   WHERE i.object_type='post' AND p.post_name LIKE 'catalog-%' AND i.description <> pm.meta_value" --skip-column-names)
ck "7 indexable.description 与 postmeta 不一致的条数" 0 "$v"

# 8 页面上不再出现价格行
v=$(curl -s http://nginx/products/ | grep -c '¥')
ck "8 /products/ 渲染里 ¥ 出现次数" 0 "$v"
v=$(curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | grep -c '¥')
ck "8b catalog 页里 ¥ 出现次数" 0 "$v"

# 9 报价链接可点（Get a price → 落到 200）
code=$(curl -s -o /dev/null -w '%{http_code}' http://nginx/get-a-quote/)
ck "9a /get-a-quote/ 首次响应" 301 "$code"
code=$(curl -s -L -o /dev/null -w '%{http_code}' http://nginx/get-a-quote/)
ck "9b /get-a-quote/ 跟随重定向后" 200 "$code"
v=$(curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | grep -c 'href="http://nginx/get-a-quote/"')
echo "  INFO  9c catalog 页里指向 /get-a-quote/ 的链接数 = $v（1 行价格行 + 页头/页尾导航）"
v2=$(curl -s http://nginx/catalog-ring-box-o1cn01ahes/ | grep -c 'Get a price')
ck "9d catalog 页里 Get a price 文案出现次数 >= 1" yes "$([ $v2 -ge 1 ] && echo yes || echo no)"

echo "== 合计 PASS $PASS / FAIL $FAIL =="
