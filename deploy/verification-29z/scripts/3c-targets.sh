#!/bin/sh
# 3c 收口：本次改动的目标链接逐一验证 + 旧链接确认消失
echo "== 存在性 =="
for u in /blog/ /compare/ /customization/ /get-a-quote/ /inquiry/ /resources/ /packaging-examples/ /pricing/ /rigid-gift-boxes/ /sustainability/ /guides/ /cases/; do
  out=$(curl -s -o /dev/null -w '%{http_code}' "http://nginx$u")
  loc=$(curl -s -o /dev/null -w '%{redirect_url}' "http://nginx$u")
  echo "  $u -> $out $loc"
done
echo "== 首页/服务页/产品页 里这些链接的引用次数 =="
for u in /resources/ /packaging-examples/ /pricing/ /rigid-gift-boxes/ /sustainability/; do
  n=$(curl -s http://nginx/ | grep -c "$u")
  echo "  首页 $u : $n"
done
echo "== 导航里的新文案 =="
curl -s http://nginx/ | grep -o 'Price calculator' | head -1
curl -s http://nginx/ | grep -o 'href="[^"]*">Guides' | head -2
echo "== /products/ 渲染哈希（改动前后比对用）=="
curl -s http://nginx/products/ | sha256sum
