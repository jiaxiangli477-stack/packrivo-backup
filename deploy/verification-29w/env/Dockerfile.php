# 29v 演练 php 镜像：把 mariadb 客户端降到与生产一致的 11.4.13
# 生产 php 容器实测：mariadb-client 11.4.13（Alpine 3.22 仓库），客户端会先做 TLS 再走认证，
# 11.8.9 会先报插件缺失、看不到 TLS 那一层，演练就复现不出生产的第一种报错。
FROM r28q-php:8.3

RUN apk add --no-cache \
        --repository https://dl-cdn.alpinelinux.org/alpine/v3.22/main \
        mariadb-client=11.4.13-r0 \
        mariadb-common=11.4.13-r0 \
 && mariadb --version \
 && mariadb-dump --version
