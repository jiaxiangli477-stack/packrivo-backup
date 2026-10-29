#!/bin/bash
# 29w 驱动（分两段，中间留一个空档给宿主机的真实浏览器判 4.6e）：
#   段 1 `fwd`：复位 → 基线快照 → 正向 0–8（step0..4, 45, 5, 46, 6, 7, 8）→ 正向快照 + 与基线的 ext diff
#   段 2 `rb` ：第 9 步回滚（含 9.8 删页、9.7.5 还原模板）→ 快照 vs 基线 → 3.0 反向自检
set -u
D=/home/web/rehearsal-29w
S=/opt/x29w
BASE_LEGACY=8971f447510523ed468eedfaa9ceec1ac1d4762465bfd0fb006ae58dad3dd2b4
PHASE="${1:-fwd}"
mkdir -p "$D"

snap() {  # $1 = 前缀
  bash "$S/snapshot.sh" "$D/s-$1.txt" > "$D/snap-$1.log" 2>&1
  L=$(sha256sum "$D/s-$1.txt"     | cut -d' ' -f1)
  E=$(sha256sum "$D/s-$1.txt.ext" | cut -d' ' -f1)
  echo "  快照[$1]  旧口径=$L"
  echo "  快照[$1]  ext   =$E"
}

if [ "$PHASE" = "fwd" ]; then
  echo "################ 29w 段1（fwd）开始 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"

  echo "===== [1] 复位 $(date -u +%H:%M:%S) ====="
  bash "$S/reset.sh" > "$D/reset.log" 2>&1; echo "reset 退出码 = $?"

  echo "===== [2] 基线快照 $(date -u +%H:%M:%S) ====="
  snap reset
  H_BASE=$(sha256sum "$D/s-reset.txt" | cut -d' ' -f1)
  E_BASE=$(sha256sum "$D/s-reset.txt.ext" | cut -d' ' -f1)
  echo "$H_BASE" > "$D/baseline.sha256"
  echo "$E_BASE" > "$D/baseline-ext.sha256"
  if [ "$H_BASE" = "$BASE_LEGACY" ]; then
    echo "PASS: 基线（旧口径）= $BASE_LEGACY（任务单要求的基线）"
  else
    echo "FAIL: 基线（旧口径）不是任务单要求的 $BASE_LEGACY，实际 $H_BASE"
  fi

  echo "===== [3] 正向 0–8（step0..4, 45, 5, 46, 6, 7, 8）$(date -u +%H:%M:%S) ====="
  SUMMARY="$D/step-summary-fwd.txt"; : > "$SUMMARY"
  for st in step0 step1 step2 step3 step4 step45 step5 step46 step6 step7 step8; do
    echo "----- [fwd] $st 开始 $(date -u +%H:%M:%S) -----"
    bash "$S/rehearsal.sh" "$st" > "$D/fwd-$st.log" 2>&1
    rc=$?
    line=$(grep -E '^本步判据' "$D/fwd-$st.log" | tail -1)
    [ -n "$line" ] || line="本步判据：（缺失）"
    echo "$st :: $line :: EXIT=$rc" | tee -a "$SUMMARY"
  done

  echo "===== [4] 正向快照 + 与基线的 ext diff $(date -u +%H:%M:%S) ====="
  snap forward
  H_FWD=$(sha256sum "$D/s-forward.txt" | cut -d' ' -f1)
  E_FWD=$(sha256sum "$D/s-forward.txt.ext" | cut -d' ' -f1)
  echo "正向 ext = $E_FWD"
  [ "$H_FWD" != "$H_BASE" ] && echo "PASS: 正向旧口径快照与基线不同" || echo "FAIL: 正向旧口径快照与基线相同"
  diff "$D/s-reset.txt.ext" "$D/s-forward.txt.ext" > "$D/fwd-vs-base.diff" || true
  echo "--- 正向 ext vs 基线 ext 的全部差异 ---"
  cat "$D/fwd-vs-base.diff"
  echo "--- 与 29v 正向快照的比较（旧口径）---"
  diff "$S/ref/s-forward-29v.txt" "$D/s-forward.txt" > "$D/fwd-vs-29v.diff" || true
  echo "diff 行数 = $(wc -l < "$D/fwd-vs-29v.diff")"

  echo "################ 29w 段1 结束 $(date -u +%Y-%m-%dT%H:%M:%SZ)；等宿主机 4.6e 浏览器检查 ################"
  exit 0
fi

if [ "$PHASE" = "rb" ]; then
  echo "################ 29w 段2（回滚）开始 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"
  H_BASE=$(cat "$D/baseline.sha256")
  E_BASE=$(cat "$D/baseline-ext.sha256")

  echo "===== [5] 第 9 步回滚（9.8 → 9.7.5 → 9.7 → 9.6 … 9.2）$(date -u +%H:%M:%S) ====="
  bash "$S/rehearsal.sh" step9 > "$D/step9.log" 2>&1
  RC9=$?
  grep -E '^本步判据|^(PASS|FAIL): ' "$D/step9.log" | tail -20
  echo "step9 :: $(grep -E '^本步判据' "$D/step9.log" | tail -1) :: EXIT=$RC9"

  snap after-rollback
  H_RB=$(sha256sum "$D/s-after-rollback.txt" | cut -d' ' -f1)
  E_RB=$(sha256sum "$D/s-after-rollback.txt.ext" | cut -d' ' -f1)
  [ "$H_RB" = "$H_BASE" ] && echo "PASS: 回滚后旧口径快照 = 基线（逐字节一致）" || { echo "FAIL: 回滚后旧口径 != 基线"; diff "$D/s-reset.txt" "$D/s-after-rollback.txt" | head -20; }
  [ "$E_RB" = "$E_BASE" ] && echo "PASS: 回滚后 ext 快照 = 基线（模板 meta 已还原）" || { echo "FAIL: 回滚后 ext != 基线"; diff "$D/s-reset.txt.ext" "$D/s-after-rollback.txt.ext" | head -20; }

  echo "===== [6] 3.0 守卫反向自检 $(date -u +%H:%M:%S) ====="
  bash "$S/negtest29w.sh" > "$D/negtest.log" 2>&1
  grep -E '^PASS: |^FAIL: ' "$D/negtest.log"
  echo "negtest 退出码 = $?"
  snap after-negtest

  echo "################ 29w 段2 结束 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"
  exit 0
fi

echo "用法: bash run29w.sh <fwd|rb>"
exit 2
