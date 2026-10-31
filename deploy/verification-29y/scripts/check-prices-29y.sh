#!/bin/sh
# 29y 第 1 项判据：llms.txt 的价格段与首页（卡片/hero/FAQ）、产品页三处逐条对齐。
#
# 跑法（在 php 容器里，容器内没有代理，不会像宿主机那样被 http_proxy 劫到线上）：
#   sh check-prices-29y.sh /var/www/html/www.packrivo.com/wordpress/llms.txt
#   sh check-prices-29y.sh /tmp/bad-empty.txt      # 负向：空输入必须 FAIL
#
# 每条判据先证明输入是真的（HTTP 200 + 字节下限 + 命中行数下限），再谈计数为 0 的结论。
set -u
LLMS="${1:-}"
GW=$(ip route | awk '/default/ {print $3; exit}')
BASE="https://www.packrivo.com"
PASS=0
FAIL=0
ok()  { PASS=$((PASS+1)); printf 'PASS  %s\n' "$1"; }
ng()  { FAIL=$((FAIL+1)); printf 'FAIL  %s\n' "$1"; }
chk() { if [ "$2" = "$3" ]; then ok "$1（实测 $2）"; else ng "$1（期望 $3，实测 $2）"; fi; }

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
fetch() { # $1 path, $2 out —— 演练的 php-fpm 短时间连打会掉 worker（502），重试三次并把重试次数打出来
    _try=0
    while [ "$_try" -lt 3 ]; do
        _code=$(curl -sS -k --resolve "www.packrivo.com:443:$GW" -o "$2" -w '%{http_code}' "$BASE$1")
        [ "$_code" = "200" ] && break
        _try=$((_try+1))
        [ "$_try" -lt 3 ] && sleep 2
    done
    [ "$_try" -gt 0 ] && printf 'INFO  %s 第 %d 次重试后才 200\n' "$1" "$_try"
    echo "$_code"
}

echo "== 0) 输入自证 =="
[ -n "$LLMS" ] && [ -f "$LLMS" ] && ok "llms 文件存在（$(wc -c < "$LLMS") 字节）" || { ng "llms 文件不存在: $LLMS"; echo "--- PASS=$PASS FAIL=$FAIL"; exit 1; }
LLMS_BYTES=$(wc -c < "$LLMS")
if [ "$LLMS_BYTES" -ge 4000 ]; then ok "llms 字节数 $LLMS_BYTES ≥ 4000（不是空壳）"; else ng "llms 字节数 $LLMS_BYTES < 4000（空输入）"; fi

code=$(fetch /llms.txt "$TMP/llms.http"); chk "演练 /llms.txt 可达" "$code" "200"
http_bytes=$(wc -c < "$TMP/llms.http")
if [ "$http_bytes" -eq "$LLMS_BYTES" ]; then ok "HTTP 取回字节与落盘文件一致（$http_bytes）"; else ng "HTTP 取回 $http_bytes 与落盘 $LLMS_BYTES 不一致"; fi
cmp -s "$LLMS" "$TMP/llms.http" && ok "HTTP 取回内容与落盘文件逐字节相同" || ng "HTTP 取回内容与落盘文件不同"

sec=$(grep -c '^## Reference prices (from the quote builder)' "$LLMS")
chk "价格段标题命中 1 次" "$sec" "1"
old=$(grep -c 'stock styles, ex-works China' "$LLMS")
chk "旧的无来源价格行已删除" "$old" "0"
rowof() { # $1 行内结构关键词
    grep -E "$1" "$LLMS" | head -1 | sed -E 's/.*\| \$([0-9.]+) \|$/\1/'
}
PR_RIGID500=$(rowof '^\| Rigid magnetic jewelry box .*\| 500 \| \$')
PR_RIGID1000=$(rowof '^\| Rigid magnetic jewelry box .*\| 1,000 \| \$')
PR_MAILER500=$(rowof '^\| Printed mailer box .*\| 500 \| \$')
for v in PR_RIGID500 PR_RIGID1000 PR_MAILER500; do
    eval "val=\$$v"
    if echo "$val" | grep -qE '^[0-9]+\.[0-9]{2}$'; then ok "llms $v = \$$val"; else ng "llms $v 解析失败（得到 '$val'）"; fi
done

echo "== 1) 首页 / =="
code=$(fetch / "$TMP/home.html"); chk "首页可达" "$code" "200"
HB=$(wc -c < "$TMP/home.html")
if [ "$HB" -ge 80000 ]; then ok "首页字节数 $HB ≥ 80000"; else ng "首页字节数 $HB < 80000"; fi
cards=$(grep -c 'from \$[0-9.]* / unit' "$TMP/home.html")
if [ "$cards" -ge 5 ]; then ok "首页卡片价格命中 $cards 处 ≥ 5"; else ng "首页卡片价格命中 $cards < 5"; fi

HERO=$(grep -o 'pk-price">\$[0-9.]*' "$TMP/home.html" | head -1 | sed 's/.*\$//')
if [ -n "$HERO" ]; then ok "首页 hero 卡片价解析 = \$$HERO（1,000 件）"; else ng "首页 hero 卡片价没解析出"; fi
chk "hero 卡片价 == llms 1,000 件" "$HERO" "$PR_RIGID1000"

FAQ_RIGID=$(grep -o 'rigid jewelry box is about \$[0-9]\+\.[0-9][0-9]' "$TMP/home.html" | head -1 | sed 's/.*\$//')
FAQ_MAILER=$(grep -o 'mailer box about \$[0-9]\+\.[0-9][0-9]' "$TMP/home.html" | head -1 | sed 's/.*\$//')
if [ -n "$FAQ_RIGID" ]; then ok "首页 FAQ 刚性格解析 = \$$FAQ_RIGID（500 件）"; else ng "首页 FAQ 刚性格没解析出"; fi
if [ -n "$FAQ_MAILER" ]; then ok "首页 FAQ 邮寄盒解析 = \$$FAQ_MAILER（500 件）"; else ng "首页 FAQ 邮寄盒没解析出"; fi
chk "FAQ 刚性格 == llms 500 件" "$FAQ_RIGID" "$PR_RIGID500"
chk "FAQ 邮寄盒 == llms 500 件" "$FAQ_MAILER" "$PR_MAILER500"

echo "== 2) 产品页（8 个）=="
for slug in product-drawer-jewelry-box product-lid-and-base-jewelry-box product-jewelry-box-with-insert \
            product-window-lid-rigid-gift-box product-hinged-nonmagnetic-rigid-box \
            product-corrugated-roll-end-hinged-lid product-euro-tote-paper-carrier product-sos-paper-gift-bag; do
    code=$(fetch "/$slug/" "$TMP/p.html")
    if [ "$code" != "200" ]; then ng "$slug 打不开（$code）"; continue; fi
    prod=$(grep -o 'pk-price-bar[^§]*from \$[0-9.]* / unit' "$TMP/p.html" | head -1 | sed 's/.*from \$//;s/ \/ unit//')
    [ -z "$prod" ] && prod=$(sed -n '/pk-price-bar/,/\/p>/p' "$TMP/p.html" | grep -o 'from \$[0-9.]* / unit' | head -1 | sed 's/from \$//;s/ \/ unit//')
    llms=$(grep -oE "\- \[[^]]*\]\(https?://[^)]*/$slug/\).*from \\\$[0-9.]+ per unit" "$LLMS" | head -1 | sed -E 's/.*from \$([0-9.]+) per unit/\1/')
    if [ -z "$prod" ]; then ng "$slug 产品页没有价格条"; continue; fi
    if [ -z "$llms" ]; then ng "$slug 在 llms.txt 里没有对应条目"; continue; fi
    chk "$slug 产品页价 == llms" "$prod" "$llms"
done

echo "== 3) llms 里的每个价格都要在页面上找得到（不留孤儿数字）=="
grep -oE '\$[0-9]+\.[0-9]{2}' "$LLMS" | tr -d '$' | sort -u > "$TMP/llms_prices.txt"
cat "$TMP/home.html" "$TMP/p.html" > "$TMP/pages.html"
grep -oE '\$[0-9]+\.[0-9]{2}' "$TMP/pages.html" | tr -d '$' | sort -u > "$TMP/page_prices.txt"
ORPHAN=$(comm -23 "$TMP/llms_prices.txt" "$TMP/page_prices.txt" | tr '\n' ' ')
n_llms=$(wc -l < "$TMP/llms_prices.txt")
if [ "$n_llms" -ge 3 ]; then ok "llms 里解析出 $n_llms 个不同价格 ≥ 3"; else ng "llms 里只解析出 $n_llms 个价格（输入可疑）"; fi
if [ -z "$ORPHAN" ]; then ok "llms 的每个价格都能在首页/产品页找到"; else ng "llms 有孤儿价格：$ORPHAN"; fi

echo "== 4) 报价引擎与页面一致（引擎直算，独立于 HTML）=="
ENG=$(php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root eval '
$s = PKQ_Plugin::settings();
$r1 = PKQ_Pricing::price(["style"=>"rigid","material"=>"rigidboard","print"=>"full-color","finish"=>"matte","quantity"=>500,"length"=>9.5,"width"=>9.5,"height"=>4,"rush"=>false], $s);
$r2 = PKQ_Pricing::price(["style"=>"rigid","material"=>"rigidboard","print"=>"full-color","finish"=>"matte","quantity"=>1000,"length"=>9.5,"width"=>9.5,"height"=>4,"rush"=>false], $s);
$r3 = PKQ_Pricing::price(["style"=>"mailer","material"=>"kraft","print"=>"full-color","finish"=>"matte","quantity"=>500,"length"=>30,"width"=>22,"height"=>12,"rush"=>false], $s);
printf("%s %s %s", number_format((float)$r1["unit"],2), number_format((float)$r2["unit"],2), number_format((float)$r3["unit"],2));
' 2>/dev/null)
set -- $ENG
chk "引擎 500 件刚性格 == llms" "${1:-none}" "$PR_RIGID500"
chk "引擎 1,000 件刚性格 == llms" "${2:-none}" "$PR_RIGID1000"
chk "引擎 500 件邮寄盒 == llms" "${3:-none}" "$PR_MAILER500"

echo "----------------------------------------"
echo "PASS=$PASS FAIL=$FAIL"
[ "$FAIL" -eq 0 ] || exit 1
