#!/bin/bash
# 补跑：RUNBOOK 第 0.1 步新加的「数据库导出探针 + 必要时包装客户端」
# 先拆掉包装证明探针会失败，再按 RUNBOOK 的补救块装上包装，重探必须成功
set -u
WPCLI="docker exec php php -d disable_functions= /usr/local/bin/wp --path=/var/www/html/www.packrivo.com/wordpress --allow-root"

echo "############### STEP 0.1 补跑：数据库导出探针 ###############"
set -x

echo "== 0.1a 先拆掉上次的包装，回到未处理的容器 =="
docker exec php sh -c 'rm -f /usr/local/bin/mariadb-dump /usr/local/bin/mariadb; ls /usr/local/bin | grep -c mariadb || echo "包装已拆除"'

echo "== 0.1b 裸探针（预期：TLS/SSL error，导出 0 字节）=="
$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes 2>&1 | tail -2
docker exec php sh -c 'ls -l /tmp/probe.sql 2>/dev/null || echo "没有生成文件"'
docker exec php rm -f /tmp/probe.sql

echo "== 0.1c 按 RUNBOOK 打包装（--skip-ssl 追加在参数末尾）=="
docker exec php sh -c 'cat > /usr/local/bin/mariadb-dump <<EOF
#!/bin/sh
exec /usr/bin/mariadb-dump "\$@" --skip-ssl
EOF
chmod +x /usr/local/bin/mariadb-dump'
docker exec php sh -c 'cat > /usr/local/bin/mariadb <<EOF
#!/bin/sh
exec /usr/bin/mariadb "\$@" --skip-ssl
EOF
chmod +x /usr/local/bin/mariadb'
docker exec php sh -c 'command -v mariadb-dump; command -v mariadb'

echo "== 0.1d 重探（预期：导出成功，字节数 > 0）=="
$WPCLI db export /tmp/probe.sql --skip-plugins --skip-themes 2>&1 | tail -2
docker exec php sh -c 'ls -l /tmp/probe.sql; grep -c "CREATE TABLE" /tmp/probe.sql'
docker exec php rm -f /tmp/probe.sql

set +x
echo "############### STEP 0.1 补跑结束 ###############"
