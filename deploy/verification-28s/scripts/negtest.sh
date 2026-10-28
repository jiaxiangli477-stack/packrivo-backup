#!/bin/bash
# 28s 反向自检：往 /pkg/rollback/ 放一个 site 不一致的 JSON，确认 3.0 守卫拦得住
set -u
D=/home/web/rehearsal-28s
H_BASE=4972cd8195d7b40a10dac4140273ba7faef4e152b2b886489bfcc624f2773deb

echo "################ 28s 反向自检 $(date -u +%Y-%m-%dT%H:%M:%SZ) ################"
echo "===== [1] 现状：/pkg/rollback 里有什么 ====="
docker exec php sh -c 'ls -l /pkg/rollback/ 2>/dev/null || echo "(空)"'

echo "===== [2] 放一个 site 不一致的 JSON ====="
docker exec php sh -c 'printf "%s" "{\"site\":\"http://wrong-site.invalid\",\"note\":\"28s negative guard test\"}" > /pkg/rollback/rollback-bogus.json'
docker exec php sh -c 'ls -l /pkg/rollback/; php -r "\$j=json_decode(file_get_contents(\"/pkg/rollback/rollback-bogus.json\"),true); echo \"bogus site=\", \$j[\"site\"], PHP_EOL;"'

echo "===== [3] 单独跑第 3 步（期望：3.0 守卫 FAIL、整步 EXIT!=0、apply 没执行）====="
bash /opt/x28r/rehearsal.sh step3 > "$D/negtest-step3.log" 2>&1
RC=$?
echo "negtest step3 EXIT=$RC"
echo "--- 该次的 PASS/FAIL 行 ---"
grep -E '^(PASS|FAIL): ' "$D/negtest-step3.log"
echo "--- 汇总行 ---"
grep -E '^本步判据|守卫拦截' "$D/negtest-step3.log"
echo "--- apply 是否执行过（应无输出，期望计数 0）---"
grep -c 'apply_updated' "$D/negtest-step3.log" || true

if [ "$RC" -ne 0 ] && grep -q '^FAIL: 3.0 .*site=http://wrong-site.invalid' "$D/negtest-step3.log" && [ "$(grep -c 'apply_updated' "$D/negtest-step3.log")" = "0" ]; then
  echo "PASS: 3.0 守卫拦住他站 JSON，整步非零退出，apply 未执行"
else
  echo "FAIL: 3.0 守卫没起到作用（EXIT=$RC）"
fi

echo "===== [4] 删掉这个文件 ====="
docker exec php rm -f /pkg/rollback/rollback-bogus.json
docker exec php sh -c 'ls -l /pkg/rollback/ 2>/dev/null || echo "(空)"'

echo "===== [5] 再取一次快照（必须仍是基线）====="
bash /opt/x28q/snapshot.sh "$D/s-after-negtest.txt"
H=$(sha256sum "$D/s-after-negtest.txt" | cut -d' ' -f1)
echo "反向自检后快照 sha256 = $H"
[ "$H" = "$H_BASE" ] && echo "PASS: 反向自检没有改动站点（仍 = 基线）" || echo "FAIL: 反向自检后快照 != 基线（$H）"
echo "################ 反向自检结束 ################"
