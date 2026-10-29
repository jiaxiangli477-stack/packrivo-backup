#!/bin/bash
# 29w 跑手入口：bash runner-entry.sh <fwd|rb>
#   fwd：清空日志目录后跑段 1（复位 → 基线 → 正向）
#   rb ：保留段 1 的日志，接着跑段 2（第 9 步回滚 → 反向自检）
P="${1:-fwd}"
mkdir -p /home/web/rehearsal-29w
if [ "$P" = "fwd" ]; then
  rm -rf /home/web/rehearsal-29w
  mkdir -p /home/web/rehearsal-29w
fi
bash /opt/x29w/run29w.sh "$P" > "/home/web/rehearsal-29w/run29w-$P.log" 2>&1
rc=$?
echo "EXIT=$rc" >> "/home/web/rehearsal-29w/run29w-$P.log"
exit "$rc"
