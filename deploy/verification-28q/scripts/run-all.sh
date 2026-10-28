#!/bin/bash
# 演练全流程：复位 → 基线快照 → 正向 0-8 → 快照 → 回滚 9 → 快照 → 再正向 1-8 → 快照
set -u
D=/home/web/rehearsal-28q
mkdir -p "$D"

echo "[$(date -u +%H:%M:%S)] 1) 复位到首装前"
: > "$D/forward1.log"
: > "$D/rollback.log"
: > "$D/forward2.log"
bash /root/reset.sh > "$D/reset.log" 2>&1
tail -6 "$D/reset.log"

echo "[$(date -u +%H:%M:%S)] 2) 基线快照"
bash /root/snapshot.sh "$D/s-baseline.txt"

echo "[$(date -u +%H:%M:%S)] 3) 正向 0-8"
for s in step0 step1 step2 step3 step4 step5 step6 step7 step8; do
  { echo "================= $s 开始 $(date -u +%H:%M:%S) ================="
    bash /root/rehearsal.sh "$s" 2>&1
    echo "================= $s 结束 exit=$? ================="
  } >> "$D/forward1.log" 2>&1
done
echo "   forward1 行数 $(wc -l < "$D/forward1.log")"
bash /root/snapshot.sh "$D/s-forward1.txt"

echo "[$(date -u +%H:%M:%S)] 4) 整体回滚 9"
{ echo "================= step9 开始 $(date -u +%H:%M:%S) ================="
  bash /root/rehearsal.sh step9 2>&1
  echo "================= step9 结束 exit=$? ================="
} > "$D/rollback.log" 2>&1
echo "   rollback 行数 $(wc -l < "$D/rollback.log")"
bash /root/snapshot.sh "$D/s-after-rollback.txt"

echo "[$(date -u +%H:%M:%S)] 5) 再正向 1-8"
for s in step1 step2 step3 step4 step5 step6 step7 step8; do
  { echo "================= $s 开始 $(date -u +%H:%M:%S) ================="
    bash /root/rehearsal.sh "$s" 2>&1
    echo "================= $s 结束 exit=$? ================="
  } >> "$D/forward2.log" 2>&1
done
echo "   forward2 行数 $(wc -l < "$D/forward2.log")"
bash /root/snapshot.sh "$D/s-forward2.txt"

echo "[$(date -u +%H:%M:%S)] 6) 对比"
{
  echo "=== 回滚后 vs 基线（应无差异）==="
  diff "$D/s-baseline.txt" "$D/s-after-rollback.txt" && echo "IDENTICAL ✓"
  echo
  echo "=== 第二次正向 vs 第一次正向（应无差异）==="
  diff "$D/s-forward1.txt" "$D/s-forward2.txt" && echo "IDENTICAL ✓"
} > "$D/diff.txt" 2>&1
cat "$D/diff.txt"
echo "[$(date -u +%H:%M:%S)] DONE"
