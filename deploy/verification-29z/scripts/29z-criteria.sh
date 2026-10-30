#!/bin/sh
# 29z §2 九条判据（v3，2026-10-30 按负责人第三轮复核：每条"计数为 0 就 PASS"都要配一个证明输入是真的正向条件）
# 用法：sh 29z-criteria.sh <before|compare|compare-noshots>
#   before          —— 只取前态快照，并逐条打印 6 条"输入非空"判据（任一 FAIL 退出码 1）
#   compare         —— 打完补丁后跑，逐条打印 PASS/FAIL（含 4f 四张截图，仅演练环境用）
#   compare-noshots —— 生产用：4f 打成 SKIP（截图在本机拍、本机判），不计入 PASS 也不计入 FAIL
set -u
MODE=${1:-compare}
NOSHOTS=0
[ "$MODE" = "compare-noshots" ] && NOSHOTS=1
WP="php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
SITE=${SITE:-http://nginx}
# 直连源站时由调用方给 --resolve + -k（生产 php 容器拿不到 nginx 证书链，且解析不到 nginx 这个名字）
CURLX=${CURLX:-}
C() { curl -sS $CURLX "$@"; }
SNAP=/tmp/29z-snap
mkdir -p $SNAP

PASS=0; FAIL=0; SKIP=0
ck() {
  if [ "$2" = "$3" ]; then echo "  PASS  $1（期望 $2，实际 $3）"; PASS=$((PASS+1));
  else echo "  FAIL  $1（期望 $2，实际 $3）"; FAIL=$((FAIL+1)); fi
}
ge() {   # ge <说明> <下限> <实际>：实际必须 ≥ 下限
  v=${3:-0}
  if [ "$v" -ge "$2" ] 2>/dev/null; then echo "  PASS  $1（≥ $2，实际 $3）"; PASS=$((PASS+1));
  else echo "  FAIL  $1（要求 ≥ $2，实际 ${3:-空}）"; FAIL=$((FAIL+1)); fi
}
lines() { wc -l < "$1" 2>/dev/null | tr -d ' ' | sed 's/^$/0/' || echo 0; }
bytes() { wc -c < "$1" 2>/dev/null | tr -d ' ' | sed 's/^$/0/' || echo 0; }

# 产品卡片：按 pk-product-card 切块，每块取第一个 href（反转义 \/）与 pk-card-title 文本，保持文档顺序
CARDS_PHP='
$h = file_get_contents($argv[1]);
$parts = preg_split("~class=\"pk-product-card\"~", $h);
array_shift($parts);
foreach ($parts as $p) {
    $link = preg_match("~href=\"([^\"]+)\"~", $p, $m) ? str_replace("\\/", "/", $m[1]) : "-";
    $name = preg_match("~class=\"pk-card-title\"[^>]*>([^<]*)<~", $p, $n) ? trim($n[1]) : "-";
    echo $link . "|" . $name . "\n";
}
'

snapshot() {
  tag=$1
  $WP db query "SELECT CONCAT_WS('|', p.ID, p.post_name, p.post_status,
      COALESCE((SELECT meta_value FROM wp_postmeta m WHERE m.post_id=p.ID AND m.meta_key='_yoast_wpseo_meta-robots-noindex' LIMIT 1),'-'),
      SHA2(p.post_content,256))
    FROM wp_posts p WHERE p.post_type IN ('page','post') AND p.post_status='publish' ORDER BY p.ID" --skip-column-names > $SNAP/pages-$tag.txt
  $WP db query "SELECT CONCAT_WS('|', post_id, meta_key, SHA2(meta_value,256)) FROM wp_postmeta
    WHERE meta_key IN ('_yoast_wpseo_metadesc','_yoast_wpseo_opengraph-description') ORDER BY post_id, meta_key" --skip-column-names > $SNAP/meta-$tag.txt
  $WP db query "SELECT CONCAT_WS('|', object_id, SHA2(COALESCE(description,''),256), SHA2(COALESCE(open_graph_description,''),256))
    FROM wp_yoast_indexable WHERE object_type='post' ORDER BY object_id" --skip-column-names > $SNAP/index-$tag.txt
  C "$SITE/products/" > $SNAP/products-$tag.html 2> $SNAP/products-$tag.err
  php -r "$CARDS_PHP" $SNAP/products-$tag.html > $SNAP/cards-$tag.txt 2> /dev/null
  echo "  快照 $tag: pages $(lines $SNAP/pages-$tag.txt) / meta $(lines $SNAP/meta-$tag.txt) / index $(lines $SNAP/index-$tag.txt) / cards $(lines $SNAP/cards-$tag.txt) / products $(bytes $SNAP/products-$tag.html)B / curl_stderr $(bytes $SNAP/products-$tag.err)B"
}

if [ "$MODE" = "before" ]; then
  echo "== 取前态 =="
  snapshot before
  echo "== D.1b 输入非空判据（任一 FAIL 就不要往下装包）=="
  ge "B1 pages 快照行数（publish 对象）" 400 "$(lines $SNAP/pages-before.txt)"
  ge "B2 meta 快照行数" 200 "$(lines $SNAP/meta-before.txt)"
  ge "B3 index 快照行数" 200 "$(lines $SNAP/index-before.txt)"
  ge "B4 products-before.html 字节数" 100000 "$(bytes $SNAP/products-before.html)"
  ck "B5 /products/ 卡片数" 24 "$(lines $SNAP/cards-before.txt)"
  ck "B6 products-before 的 curl stderr 字节数" 0 "$(bytes $SNAP/products-before.err)"
  $WP db query "SELECT ID FROM wp_posts WHERE post_name LIKE 'catalog-%' AND post_status='publish'" --skip-column-names | grep -v '^[[:space:]]*$' | sort > $SNAP/cat-ids.txt
  ncat=$(lines $SNAP/cat-ids.txt)
  ck "B7 catalog 页 ID 条数" 208 "$ncat"
  echo "== 前态汇总 =="
  echo "  PASS=$PASS  FAIL=$FAIL"
  [ "$FAIL" -eq 0 ] || exit 1
  exit 0
fi

echo "== 取后态 =="
snapshot after

echo "== §2 判据前置：快照非空（所有「计数为 0 就 PASS」的判据都由这一层担保输入是真的）=="
pb=$(lines $SNAP/pages-before.txt); pa=$(lines $SNAP/pages-after.txt)
if [ "$pb" = "$pa" ] && [ "${pa:-0}" -ge 400 ] 2>/dev/null; then
  echo "  PASS  0a pages 快照非空且前后条数相同（before $pb / after $pa）"; PASS=$((PASS+1))
else
  echo "  FAIL  0a pages 快照非空且前后条数相同（before $pb / after $pa，要求 ≥400 且相等）"; FAIL=$((FAIL+1))
fi
ge "0b meta-after 快照行数" 200 "$(lines $SNAP/meta-after.txt)"
ge "0c index-after 快照行数" 200 "$(lines $SNAP/index-after.txt)"
ge "0d products-before.html 字节数" 100000 "$(bytes $SNAP/products-before.html)"
ge "0e products-after.html 字节数" 100000 "$(bytes $SNAP/products-after.html)"
ck "0f products-after 的 curl stderr 字节数" 0 "$(bytes $SNAP/products-after.err)"

echo "== §2 九条判据 =="

# --- 1 正文归零
n1=$($WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%1688%'" --skip-column-names | tr -d ' ')
n2=$($WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE CONCAT('%',CHAR(194,165),'%')" --skip-column-names | tr -d ' ')
n3=$($WP db query "SELECT COUNT(*) FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%detail.1688.com%'" --skip-column-names | tr -d ' ')
ck "1a 正文含 1688 的 publish 行" 0 "$n1"
ck "1b 正文含 ¥ 的 publish 行" 0 "$n2"
ck "1c 正文含 detail.1688.com 的 publish 行" 0 "$n3"

# --- 2 meta 规格
Q_META="FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id WHERE pm.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish'"
bad=$($WP db query "SELECT COUNT(*) $Q_META AND (pm.meta_value REGEXP '1688|yuan|RMB|CNY|ex-works|lowest tier|US[$]')" --skip-column-names | tr -d ' ')
ck "2a catalog meta 里还有价格/来源词" 0 "$bad"
long=$($WP db query "SELECT COUNT(*) $Q_META AND CHAR_LENGTH(pm.meta_value) > 155" --skip-column-names | tr -d ' ')
ck "2b catalog meta 超过 155 字符" 0 "$long"
tot=$($WP db query "SELECT COUNT(*) $Q_META" --skip-column-names | tr -d ' ')
uniq=$($WP db query "SELECT COUNT(DISTINCT pm.meta_value) $Q_META" --skip-column-names | tr -d ' ')
ck "2c catalog meta 行数（正向：必须正好 208，0 也算 FAIL）" 208 "$tot"
ck "2d catalog meta 去重后条数（与 2c 相同）" "$tot" "$uniq"
tailn=$($WP db query "SELECT COUNT(*) $Q_META AND pm.meta_value LIKE '%Custom logo printing, MOQ 500 per design. Samples available.'" --skip-column-names | tr -d ' ')
ck "2e catalog meta 带固定尾句" "$tot" "$tailn"
ogsync=$($WP db query "SELECT COUNT(*) FROM wp_postmeta a JOIN wp_postmeta b ON b.post_id=a.post_id AND b.meta_key='_yoast_wpseo_opengraph-description' JOIN wp_posts p ON p.ID=a.post_id
  WHERE a.meta_key='_yoast_wpseo_metadesc' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish' AND COALESCE(b.meta_value,'') <> a.meta_value" --skip-column-names | tr -d ' ')
ck "2f og-description 与 metadesc 同步（不一致条数）" 0 "$ogsync"

# --- 3 slug / robots 未变
a=$(cut -d'|' -f1-3 $SNAP/pages-before.txt | md5sum | cut -d' ' -f1)
b=$(cut -d'|' -f1-3 $SNAP/pages-after.txt | md5sum | cut -d' ' -f1)
ck "3a ID/slug/status 集合" "$a" "$b"
a=$(cut -d'|' -f4 $SNAP/pages-before.txt | md5sum | cut -d' ' -f1)
b=$(cut -d'|' -f4 $SNAP/pages-after.txt | md5sum | cut -d' ' -f1)
ck "3b noindex 集合" "$a" "$b"

# --- 4 /products/：卡片逐一相同 + 价格归零 + 两档截图
ck "4a /products/ 产品卡片数（前后各 24）" 24 "$(lines $SNAP/cards-after.txt)"
ck "4b /products/ 卡片 链接+名称 逐条相同" "$(md5sum $SNAP/cards-before.txt | cut -d' ' -f1)" "$(md5sum $SNAP/cards-after.txt | cut -d' ' -f1)"
ck "4c 渲染里 ¥ 计数" 0 "$(grep -o '¥' $SNAP/products-after.html | wc -l)"
ck "4d 渲染里 \\u00a5 计数" 0 "$(grep -o '\\u00a5' $SNAP/products-after.html | wc -l)"
ck "4e 渲染里 1688 计数" 0 "$(grep -o '1688' $SNAP/products-after.html | wc -l)"
# 4f 截图：容器里有图就判（演练环境），没有就打 SKIP（生产：本机 29z-shots-check.mjs 判）
if [ "$NOSHOTS" = "1" ]; then
  echo "  SKIP  4f 改前改后 x 1440/375 四张截图 —— 本机判（29z-shots-check.mjs），容器里不计"
  SKIP=$((SKIP+1))
else
  shots=0
  for s in before-1440 after-1440 before-375 after-375; do
    if [ -s "$SNAP/shots/$s.png" ]; then shots=$((shots+1)); echo "    截图 $s.png $(bytes $SNAP/shots/$s.png) B"; fi
  done
  ck "4f 改前改后 x 1440/375 四张截图" 4 "$shots"
fi
echo "    改前 /products/: ¥ $(grep -o '¥' $SNAP/products-before.html | wc -l) / \\u00a5 $(grep -o '\\u00a5' $SNAP/products-before.html | wc -l) / 1688 $(grep -o '1688' $SNAP/products-before.html | wc -l)"

# --- 5 被改对象集合
awk -F'|' 'NR==FNR{a[$1]=$5; next} { if (a[$1] != $5) print $1"|"$2 }' $SNAP/pages-before.txt $SNAP/pages-after.txt > $SNAP/changed.txt
n_changed=$(lines $SNAP/changed.txt)
ck "5a 内容被改的对象数（预期 210 = 208 catalog + /products/ + 1 篇博客）" 210 "$n_changed"
cut -d'|' -f1 $SNAP/changed.txt | grep -v '^[[:space:]]*$' | sort > $SNAP/changed-ids.txt
# 允许集合：208 个 catalog 页 + products(ID 8) + 那篇博客(ID 90)
awk -F'|' '$2=="products"||$2=="jewelry-box-insert-fit-measurements"{print $1}' $SNAP/changed.txt | grep -v '^[[:space:]]*$' | sort > $SNAP/allowed-extra.txt
cat $SNAP/cat-ids.txt $SNAP/allowed-extra.txt | grep -v '^[[:space:]]*$' | sort -u > $SNAP/allowed-ids.txt
comm -23 $SNAP/changed-ids.txt $SNAP/allowed-ids.txt > $SNAP/unexpected-ids.txt
ck "5b 集合外被改的对象数" 0 "$(lines $SNAP/unexpected-ids.txt)"
if [ -s $SNAP/unexpected-ids.txt ]; then echo "    越界 ID: $(tr '\n' ' ' < $SNAP/unexpected-ids.txt)"; fi

# --- 6 非 catalog 对象的 meta 未动
awk -F'|' 'NR==FNR{a[$1"|"$2]=$3; next} { if (a[$1"|"$2] != $3) print $1 }' $SNAP/meta-before.txt $SNAP/meta-after.txt | sort -u > $SNAP/meta-changed-ids.txt
comm -23 $SNAP/meta-changed-ids.txt $SNAP/allowed-ids.txt > $SNAP/meta-unexpected.txt
ck "6a 非 catalog/products/博客 的 meta 被改条数" 0 "$(lines $SNAP/meta-unexpected.txt)"
ck "6b meta 被改对象数（只有 208 个 catalog 页有 metadesc 行；products 与博客本来没有这行）" 208 "$(lines $SNAP/meta-changed-ids.txt)"

# --- 7 indexable 与 postmeta 一致
mis=$($WP db query "SELECT COUNT(*) FROM wp_yoast_indexable i JOIN wp_postmeta pm ON pm.post_id=i.object_id AND pm.meta_key='_yoast_wpseo_metadesc'
  JOIN wp_posts p ON p.ID=i.object_id WHERE i.object_type='post' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish'
  AND COALESCE(i.description,'') <> pm.meta_value" --skip-column-names | tr -d ' ')
ck "7 indexable.description 与 postmeta 不一致条数" 0 "$mis"
rows7=$($WP db query "SELECT COUNT(*) FROM wp_yoast_indexable i JOIN wp_postmeta pm ON pm.post_id=i.object_id AND pm.meta_key='_yoast_wpseo_metadesc'
  JOIN wp_posts p ON p.ID=i.object_id WHERE i.object_type='post' AND p.post_name LIKE 'catalog-%' AND p.post_status='publish'" --skip-column-names | tr -d ' ')
ck "7b 被比的 indexable 行数（正向：必须 208，否则 7 是空集合上的 PASS）" 208 "$rows7"

# --- 8 catalog 页渲染里没有 ¥（抽 5 页；每页必须 http=200 且字节 > 0）
echo "    8 逐页证据（页名 / http / 字节 / ¥ 计数）："
yen_total=0; bad8=0
for s in catalog-ring-box-o1cn01ahes catalog-wooden-box-o1cn01rnjift1k catalog-velvet-box catalog-paper-box catalog-gift-box; do
  f=$SNAP/p8-$s.html
  code=$(C -o "$f" -w '%{http_code}' "$SITE/$s/" 2> $SNAP/p8-$s.err | tr -d ' ')
  b8=$(bytes $f)
  c=$(grep -o '¥' "$f" 2>/dev/null | wc -l | tr -d ' ')
  echo "      $s http=${code:-空} bytes=$b8 ¥=$c"
  if [ "$code" != "200" ] || [ "${b8:-0}" -lt 10000 ] 2>/dev/null; then bad8=$((bad8+1)); fi
  yen_total=$((yen_total + c))
done
ck "8 抽查 5 个 catalog 页渲染里 ¥ 总数" 0 "$yen_total"
ck "8b 5 页里 http=200 且 bytes≥10000 的页数" 5 "$((5 - bad8))"

# --- 9 报价链接
code=$(C -o /dev/null -w '%{http_code}' -L "$SITE/get-a-quote/" | tr -d ' ')
ck "9a /get-a-quote/ 跟随跳转后状态" 200 "$code"
gp=$(grep -o 'Get a price' $SNAP/products-after.html | wc -l | tr -d ' ')
if [ "$gp" -gt 0 ]; then echo "  PASS  9b /products/ 页里 Get a price 链接（$gp 处）"; PASS=$((PASS+1)); else echo "  FAIL  9b /products/ 页里 Get a price 链接（0 处）"; FAIL=$((FAIL+1)); fi

echo "== 汇总 =="
echo "  PASS=$PASS  FAIL=$FAIL  SKIP=$SKIP"
[ "$FAIL" -eq 0 ] || exit 1
