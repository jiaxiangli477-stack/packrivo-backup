#!/bin/bash
# 29v 驱动：复位 → 基线快照 → 正向 0–8（含 4.5）→ 快照 → 最后手段（整库导入，MySQL）→ 复位
#          → 正向 0–8 → 第 9 步回滚（含 9.8 删页）→ 快照 → 3.0 反向自检 → 快照
set -u
D=/home/web/rehearsal-29v
S=/opt/x29v
mkdir -p "$D"

echo "################ 29v 开始 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"

run_steps() {
  local tag="$1"
  local SUMMARY="$D/step-summary-$tag.txt"
  : > "$SUMMARY"
  for st in step0 step1 step2 step3 step4 step45 step5 step6 step7 step8; do
    echo "----- [$tag] $st 开始 $(date -u +%H:%M:%S) -----"
    bash "$S/rehearsal.sh" "$st" > "$D/$tag-$st.log" 2>&1
    local rc=$?
    local line
    line=$(grep -E '^本步判据' "$D/$tag-$st.log" | tail -1)
    [ -n "$line" ] || line="本步判据：（缺失）"
    echo "$st :: $line :: EXIT=$rc" | tee -a "$SUMMARY"
  done
  echo "--- [$tag] 逐步汇总 ---"
  cat "$SUMMARY"
}

echo "===== [1] 复位（reset.sh）$(date -u +%H:%M:%S) ====="
bash "$S/reset.sh" > "$D/reset.log" 2>&1
echo "reset 退出码 = $?"

echo "===== [2] 复位后快照 = 本轮基线 $(date -u +%H:%M:%S) ====="
bash "$S/snapshot.sh" "$D/s-reset.txt"
H_BASE=$(sha256sum "$D/s-reset.txt" | cut -d' ' -f1)
echo "$H_BASE" > "$D/baseline.sha256"
echo "基线 sha256 = $H_BASE（写进 $D/baseline.sha256；29s 的 4972cd81… 是 MariaDB 时代的基线，本轮换成 MySQL 后重取）"

echo "===== [3] 正向 0–8（含 4.5 建页）$(date -u +%H:%M:%S) ====="
run_steps fwd
bash "$S/snapshot.sh" "$D/s-forward.txt"
H_FWD=$(sha256sum "$D/s-forward.txt" | cut -d' ' -f1)
echo "正向快照 sha256 = $H_FWD"
[ "$H_FWD" != "$H_BASE" ] && echo "PASS: 正向快照与基线不同（第 3/4/5/45 步确实改了东西）" || echo "FAIL: 正向快照与基线相同 → 正向没生效"
echo "--- 与 28s 正向快照（4247693f…，MariaDB 时代）的 diff 统计 ---"
diff "$S/ref/s-forward-28s.txt" "$D/s-forward.txt" > "$D/forward-vs-28s.diff" || true
echo "diff 行数 = $(wc -l < "$D/forward-vs-28s.diff")；新增/删除行 = +$(grep -c '^>' "$D/forward-vs-28s.diff") / -$(grep -c '^<' "$D/forward-vs-28s.diff")"

echo "===== [4] 最后手段：整库导入（MySQL）$(date -u +%H:%M:%S) ====="
bash "$S/lastresort29v.sh" "$H_FWD" "$H_BASE" > "$D/lastresort.log" 2>&1
RC_LSR=$?
echo "lastresort 退出码 = $RC_LSR"
grep -E '^(PASS|FAIL): |^结果|^===== 结果' "$D/lastresort.log" | tail -25

echo "===== [5] 再复位一次，跑第 9 步回滚 $(date -u +%H:%M:%S) ====="
bash "$S/reset.sh" > "$D/reset2.log" 2>&1
echo "reset2 退出码 = $?"
bash "$S/snapshot.sh" "$D/s-reset2.txt"
H2=$(sha256sum "$D/s-reset2.txt" | cut -d' ' -f1)
[ "$H2" = "$H_BASE" ] && echo "PASS: 二次复位快照 = 基线" || echo "FAIL: 二次复位快照 != 基线（$H2）"

echo "===== [6] 正向 0–8（第二轮）$(date -u +%H:%M:%S) ====="
run_steps fwd2

echo "===== [7] 第 9 步回滚（含 9.8 删报价页）$(date -u +%H:%M:%S) ====="
bash "$S/rehearsal.sh" step9 > "$D/step9.log" 2>&1
RC9=$?
grep -E '^本步判据|^(PASS|FAIL): ' "$D/step9.log" | tail -20
echo "step9 :: $(grep -E '^本步判据' "$D/step9.log" | tail -1) :: EXIT=$RC9"
bash "$S/snapshot.sh" "$D/s-after-rollback.txt"
H_RB=$(sha256sum "$D/s-after-rollback.txt" | cut -d' ' -f1)
echo "回滚后快照 sha256 = $H_RB"
[ "$H_RB" = "$H_BASE" ] && echo "PASS: 回滚后快照 = 基线（逐字节一致）" || { echo "FAIL: 回滚后快照 != 基线"; diff "$D/s-reset.txt" "$D/s-after-rollback.txt" | head -20; }

echo "===== [8] 3.0 守卫反向自检 $(date -u +%H:%M:%S) ====="
bash "$S/negtest29v.sh" > "$D/negtest.log" 2>&1
RC_NEG=$?
grep -E '^PASS: |^FAIL: ' "$D/negtest.log"
echo "negtest 退出码 = $RC_NEG"

echo "################ 29v 结束 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"
