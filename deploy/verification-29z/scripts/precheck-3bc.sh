#!/bin/sh
# 29z 预检：首页 alt / hero 图 / 字体 / 导航文案
WP="php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"
echo "== 首页 hero img =="
curl -s http://nginx/ | grep -o '<img[^>]*hero[^>]*>' | head -2
echo "== 首页 alt 文本 =="
curl -s http://nginx/ | grep -o 'Two small cream gift boxes[^"]*' | head -2
echo "== 字体 preload / @font-face 里的 URL =="
curl -s http://nginx/ | grep -o '[^"'"'"']*packrivo-inter-latin-v3\.woff2' | head -3
echo "== 字体请求 =="
FONT=$(curl -s http://nginx/ | grep -o '/wp-content/[^"'"'"']*packrivo-inter-latin-v3\.woff2' | head -1)
echo "font path = $FONT"
curl -s -o /dev/null -w 'code=%{http_code} type=%{content_type} size=%{size_download}\n' "http://nginx$FONT"
echo "== 德语首页导航里的 Price calculator =="
curl -s http://nginx/de/ | grep -o 'Preisrechner' | head -2
echo "== 英文首页导航 =="
curl -s http://nginx/ | grep -o 'Price calculator' | head -2
echo "== 死链是否还在首页 =="
for u in /resources/ /packaging-examples/ /pricing/ /rigid-gift-boxes/ /sustainability/; do
  n=$(curl -s http://nginx/ | grep -c "$u")
  echo "  $u 出现 $n 次"
done
