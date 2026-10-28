#!/bin/bash
# 守卫命令静态验证（不跑整步）：先验证 site 检查对合法 JSON 给 PASS，对他站 JSON 给 FAIL
SITE=http://nginx
echo "=== /pkg/rollback 现状 ==="
docker exec php sh -c 'ls -l /pkg/rollback/ 2>/dev/null || echo "(空)"'

echo
echo "=== [A] 合法 JSON：期望 PASS×N ==="
for f in $(docker exec php sh -c 'ls /pkg/rollback/*.json 2>/dev/null'); do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一致" \
    || { echo "FAIL: $f 非本轮产物"; }
done

echo
echo "=== [B] 放一个他站 JSON，期望对它 FAIL、对原有两个仍 PASS ==="
docker exec php sh -c 'printf "%s" "{\"site\":\"http://wrong-site.invalid\"}" > /pkg/rollback/rollback-bogus.json'
for f in $(docker exec php sh -c 'ls /pkg/rollback/*.json 2>/dev/null'); do
  docker exec php php -r '$j=json_decode(file_get_contents($argv[1]),true); exit((($j["site"]??"")===$argv[2])?0:1);' "$f" "$SITE" \
    && echo "PASS: $f site 与本轮一致" \
    || { echo "FAIL: $f 非本轮产物"; }
done

echo
echo "=== [C] 拆掉空目录的场景（模拟 /pkg/rollback 空）==="
docker exec php rm -f /pkg/rollback/rollback-bogus.json
docker exec php sh -c 'mkdir -p /pkg/rollback-empty; mv /pkg/rollback/*.json /pkg/rollback-empty/ 2>/dev/null; ls /pkg/rollback/ | wc -l'
echo "空目录时 ls 输出：[$(docker exec php sh -c 'ls /pkg/rollback/*.json 2>/dev/null')]（应为空）"
docker exec php sh -c 'mv /pkg/rollback-empty/*.json /pkg/rollback/ 2>/dev/null; rmdir /pkg/rollback-empty 2>/dev/null; ls /pkg/rollback/'
