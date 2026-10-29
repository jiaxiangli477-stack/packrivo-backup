#!/bin/bash
# 28t 驱动：复位 → 拆包装 → 快照（本轮新基线）→ 正向 0–8 → 快照 → 回滚 9 → 快照
set -u
D=/home/web/v28t
mkdir -p "$D"
H_BASE_28S=4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb
H_FWD_28S=4247693fa3a31f35477dcb059ad79e4ae5c13de861f94dfcc3848fe57f81b392

echo "################ 28t 开始 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"

echo "===== [0] 本轮环境版本（升级后）====="
docker exec php sh -c 'grep -m1 "^\$wp_version" /var/www/html/www.packrivo.com/wordpress/wp-includes/version.php'

echo "===== [1] 复位（reset.sh）====="
bash /opt/x28q/reset.sh > "$D/reset.log" 2>&1
echo "reset 退出码 = $?"
tail -5 "$D/reset.log"

echo "===== [2] 拆掉 mariadb 包装 ====="
docker exec php sh -c 'rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb; ls /usr/local/bin | grep -c mariadb || echo "包装已拆除"'

echo "===== [3] 复位后快照（本轮基线）====="
bash /opt/x28q/snapshot.sh "$D/s-reset.txt"
H1=$(sha256sum "$D/s-reset.txt" | cut -d' ' -f1)
echo "复位快照 sha256 = $H1  （28s 基线 $H_BASE_28S）"
if [ "$H1" = "$H_BASE_28S" ]; then
  echo "观察: 复位快照与 28s 基线相同"
else
  echo "观察: 复位快照与 28s 基线不同 —— 本轮以 $H1 为新基线，差异原因见 diff"
fi

echo "===== [4] 正向 0–8 ====="
SUMMARY="$D/step-summary.txt"
: > "$SUMMARY"
for st in step0 step1 step2 step3 step4 step5 step6 step7 step8; do
  echo "----- $st 开始 $(date -u +%H:%M:%S) -----"
  bash /home/web/v28t/rehearsal.sh "$st" > "$D/$st.log" 2>&1
  rc=$?
  line=$(grep -E '^本步判据' "$D/$st.log" | tail -1)
  [ -n "$line" ] || line="本步判据：（缺失）"
  echo "$st :: $line :: EXIT=$rc" | tee -a "$SUMMARY"
done

echo "===== [5] 正向后快照 ====="
bash /opt/x28q/snapshot.sh "$D/s-forward.txt"
H2=$(sha256sum "$D/s-forward.txt" | cut -d' ' -f1)
echo "正向快照 sha256 = $H2  （28s 正向 $H_FWD_28S）"

echo "===== [6] 回滚 第 9 步 ====="
bash /home/web/v28t/rehearsal.sh step9 > "$D/step9.log" 2>&1
rc9=$?
line9=$(grep -E '^本步判据' "$D/step9.log" | tail -1)
echo "step9 :: $line9 :: EXIT=$rc9" | tee -a "$SUMMARY"

echo "===== [7] 回滚后快照（必须 = 本轮基线 $H1）====="
bash /opt/x28q/snapshot.sh "$D/s-after-rollback.txt"
H3=$(sha256sum "$D/s-after-rollback.txt" | cut -d' ' -f1)
echo "回滚后快照 sha256 = $H3"
if [ "$H3" = "$H1" ]; then echo "PASS: 回滚后快照 = 本轮基线"; else echo "FAIL: 回滚后快照 != 本轮基线（$H3 vs $H1）"; fi

echo "===== 逐步汇总 ====="
cat "$SUMMARY"
echo "################ 28t 结束 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"
