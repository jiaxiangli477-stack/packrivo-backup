#!/bin/bash
# 28s 驱动：复位 → 拆包装 → 快照 → 正向 0–8（逐步记录 PASS/FAIL/EXIT）→ 快照 → 回滚 9 → 快照
set -u
D=/home/web/rehearsal-28s
mkdir -p "$D"
H_BASE=4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
H_FWD=4247693fa3a31f35477dcb059ad79e4ae5c13de861f94dfcc3848fe57f81b392

echo "################ 28s 开始 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"

echo "===== [1] 复位（reset.sh）====="
bash /opt/x28q/reset.sh > "$D/reset.log" 2>&1
echo "reset 退出码 = $?"
cat "$D/reset.log"

echo "===== [2] 拆掉 mariadb 包装 ====="
docker exec php sh -c 'rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb; ls /usr/local/bin | grep -c mariadb || echo "包装已拆除"'

echo "===== [3] 复位后快照（必须 = 基线 4972cd81…）====="
bash /opt/x28q/snapshot.sh "$D/s-reset.txt"
H1=$(sha256sum "$D/s-reset.txt" | cut -d' ' -f1)
echo "复位快照 sha256 = $H1"
[ "$H1" = "$H_BASE" ] && echo "PASS: 复位快照 = 基线" || echo "FAIL: 复位快照 != 基线（实际 $H1）"

echo "===== [4] 正向 0–8 ====="
SUMMARY="$D/step-summary.txt"
: > "$SUMMARY"
for st in step0 step1 step2 step3 step4 step5 step6 step7 step8; do
  echo "----- $st 开始 $(date -u +%H:%M:%S) -----"
  bash /opt/x28r/rehearsal.sh "$st" > "$D/$st.log" 2>&1
  rc=$?
  line=$(grep -E '^本步判据' "$D/$st.log" | tail -1)
  [ -n "$line" ] || line="本步判据：（缺失）"
  echo "$st :: $line :: EXIT=$rc" | tee -a "$SUMMARY"
  echo "----- $st 原始输出 -----"
  cat "$D/$st.log"
done

echo "===== [5] 正向后快照（应当 = 4247693f…）====="
bash /opt/x28q/snapshot.sh "$D/s-forward.txt"
H2=$(sha256sum "$D/s-forward.txt" | cut -d' ' -f1)
echo "正向快照 sha256 = $H2"
[ "$H2" = "$H_FWD" ] && echo "PASS: 正向快照 = 4247693f…" || echo "FAIL: 正向快照 != 4247693f…（实际 $H2）"

echo "===== [6] 回滚 第 9 步 ====="
bash /opt/x28r/rehearsal.sh step9 > "$D/step9.log" 2>&1
rc9=$?
line9=$(grep -E '^本步判据' "$D/step9.log" | tail -1)
echo "step9 :: $line9 :: EXIT=$rc9" | tee -a "$SUMMARY"
echo "----- step9 原始输出 -----"
cat "$D/step9.log"

echo "===== [7] 回滚后快照（必须 = 基线 4972cd81…）====="
bash /opt/x28q/snapshot.sh "$D/s-after-rollback.txt"
H3=$(sha256sum "$D/s-after-rollback.txt" | cut -d' ' -f1)
echo "回滚后快照 sha256 = $H3"
[ "$H3" = "$H_BASE" ] && echo "PASS: 回滚后快照 = 基线" || echo "FAIL: 回滚后快照 != 基线（实际 $H3）"

echo "===== 逐步汇总 ====="
cat "$SUMMARY"
echo "################ 28s 结束 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"
