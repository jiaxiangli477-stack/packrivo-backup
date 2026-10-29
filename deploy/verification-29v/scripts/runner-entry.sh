#!/bin/bash
# 29v 一次性跑手：容器入口。日志落在宿主机 /home/web/rehearsal-29v/run29v.log
rm -rf /home/web/rehearsal-29v
mkdir -p /home/web/rehearsal-29v
bash /opt/x29v/run29v.sh > /home/web/rehearsal-29v/run29v.log 2>&1
echo "EXIT=$?" >> /home/web/rehearsal-29v/run29v.log
