#!/bin/sh
# 29y 第 1 项负向验证：证明检查器不是靠"计数为 0"过关。
#   A) 空输入 → 必须 FAIL
#   B) 把 llms 里的 500 件价格改成旧的无来源口径 0.27 → 必须 FAIL
#   C) 删掉整段价格表 → 必须 FAIL
# 任一项返回 0（PASS）就说明检查器坏了。
set -u
CHK=/tmp/check-prices-29y.sh
OK=/tmp/llms-ok.txt
FAILS=0

run_negative() { # $1 名称  $2 输入文件
    echo "---- $1"
    if sh "$CHK" "$2" >/tmp/neg.out 2>&1; then
        echo "  异常：负向输入竟然全 PASS"
        FAILS=$((FAILS+1))
    else
        n=$(grep -c '^FAIL' /tmp/neg.out)
        echo "  预期失败：FAIL=$n 条"
        grep '^FAIL' /tmp/neg.out | head -3 | sed 's/^/    /'
        [ "$n" -ge 1 ] || { echo "  异常：退出非 0 但没有 FAIL 行"; FAILS=$((FAILS+1)); }
    fi
}

: > /tmp/bad-empty.txt
run_negative "A 空输入" /tmp/bad-empty.txt

sed 's/| 500 | \$2\.83 |/| 500 | $0.27 |/' "$OK" > /tmp/bad-price.txt
grep -q '0.27' /tmp/bad-price.txt || { echo "  前置失败：篡改没落到文件里"; exit 2; }
run_negative "B 价格篡改为 0.27" /tmp/bad-price.txt

awk '/^## Reference prices \(from the quote builder\)/{skip=1} skip&&/^\*\*Basis/{skip=0} !skip' "$OK" > /tmp/bad-nosection.txt
if cmp -s "$OK" /tmp/bad-nosection.txt; then echo "  前置失败：删段没生效"; exit 2; fi
run_negative "C 删掉价格段" /tmp/bad-nosection.txt

echo "========================================"
if [ "$FAILS" -eq 0 ]; then echo "PASS 三个负向输入全部按预期失败"; else echo "FAIL $FAILS 个负向用例没失败"; exit 1; fi
