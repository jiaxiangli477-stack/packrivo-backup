#!/bin/bash
# 29x 跑手入口：bash runner-entry.sh <fwd|rb>
#   fwd：清空日志目录后跑段 1（复位 → 基线 → 正向）
#   rb ：保留段 1 的日志，接着跑段 2（第 9 步回滚 → 反向自检）
P="${1:-fwd}"
mkdir -p /home/web/rehearsal-29x
if [ "$P" = "fwd" ]; then
  rm -rf /home/web/rehearsal-29x
  mkdir -p /home/web/rehearsal-29x
fi
bash /opt/x29x/run29x.sh "$P" > "/home/web/rehearsal-29x/run29x-$P.log" 2>&1
rc=$?
echo "EXIT=$rc" >> "/home/web/rehearsal-29x/run29x-$P.log"
exit "$rc"
