#!/bin/bash
# 演练状态指纹：把"站点状态"压成一份可逐字对比的清单
set -u
WP=/home/web/html/www.packrivo.com/wordpress
WPC=/var/www/html/www.packrivo.com/wordpress
I18N=$WP/wp-content/uploads/packrivo-i18n
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root"

out="${1:-/home/web/rehearsal-28q/snapshot.txt}"
mkdir -p "$(dirname "$out")"

{
  echo "== mu 文件（排序后 sha256）=="
  ( cd "$WP/wp-content/mu-plugins" && sha256sum $(ls | grep -E '\.php$' | sort) )
  echo
  echo "== i18n 字典（排序后 sha256）=="
  ( cd "$I18N" && sha256sum $(ls | grep -E '\.(json|js)$' | sort) )
  echo
  echo "== 主题 =="
  $WPCLI theme list --format=csv
  echo "template=$($WPCLI option get template)"
  echo "stylesheet=$($WPCLI option get stylesheet)"
  echo "子主题目录存在=$(test -d "$WP/wp-content/themes/packrivo-home-v4" && echo yes || echo no)"
  echo
  echo "== 插件 =="
  $WPCLI plugin list --format=csv
  echo "报价插件目录存在=$(test -d "$WP/wp-content/plugins/packrivo-quote" && echo yes || echo no)"
  echo
  echo "== 页面标题（ID,slug,title 排序）=="
  $WPCLI post list --post_type=page,post --post_status=publish --fields=ID,post_name,post_title --format=csv | sort
  echo
  echo "== 关键选项 =="
  echo "home=$($WPCLI option get home)"
  echo "siteurl=$($WPCLI option get siteurl)"
} > "$out.raw" 2>/dev/null

# 去掉每次都会变的行（主题/插件版本号之外没有时间戳，但 wp 会在某些字段写 modified）
grep -v -E '^\s*$' "$out.raw" > "$out"
echo "snapshot: $out"
echo "行数: $(wc -l < "$out")"
echo "整份 sha256: $(sha256sum "$out" | cut -d' ' -f1)"

# 29w 起：指纹不含 post meta，所以模板设置看不见。为了保留 8971f447… 这个基线可比的旧口径，
# 另出一份 .ext（= 旧口径 + 一行 page8_template），两份哈希都打出来。
#   旧口径 s-*.txt   → 基线 8971f447…（29w 仍是它）
#   新口径 s-*.txt.ext → 基线 → 正向会多出一行模板值，回滚后必须回到基线
P8T=$($WPCLI post meta get 8 _wp_page_template 2>/dev/null | tr -d '\r'); [ -z "${P8T:-}" ] && P8T="NONE"
{ cat "$out"; echo "page8_template=$P8T"; } > "$out.ext"
echo "snapshot(ext): $out.ext"
echo "整份 sha256(ext): $(sha256sum "$out.ext" | cut -d' ' -f1)"
