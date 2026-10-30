#!/usr/bin/env bash
# 29z strip-1688 包装器：给运维一个能用字面量 --dry-run / --apply 的入口。
# 在宿主上跑（会自己进 php 容器），也可以在容器里直接跑。
#
#   bash strip-1688.sh --dry-run
#   bash strip-1688.sh --apply
#
# 环境变量（可选）：WP_PATH 覆盖 WordPress 路径；PHP_SVC 覆盖容器名（默认 php）。
set -u

WP_PATH="${WP_PATH:-/var/www/html/www.packrivo.com/wordpress}"
PHP_SVC="${PHP_SVC:-php}"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

mode=""
for a in "$@"; do
  case "$a" in
    --dry-run) mode="dry-run" ;;
    --apply)   mode="apply" ;;
    -v|--verbose) export STRIP1688_VERBOSE=1 ;;
    *) echo "未知参数: $a（只认 --dry-run / --apply / --verbose）" >&2; exit 2 ;;
  esac
done
if [ -z "$mode" ]; then
  echo "必须显式给 --dry-run 或 --apply（默认不跑，避免误写库）" >&2
  exit 2
fi
if [ "$mode" = "dry-run" ]; then
  export STRIP1688_DRY=1
fi

DOCKER="${DOCKER:-docker}"
# 宿主机路径（脚本在宿主跑时用）或容器内路径（脚本在容器跑时用）
if [ -f "$HERE/strip-1688.php" ] && command -v "$DOCKER" >/dev/null 2>&1 && [ "$(uname -s 2>/dev/null)" != "Linux" ]; then
  "$DOCKER" cp "$HERE/strip-1688.php" "$PHP_SVC":/tmp/strip-1688.php >/dev/null
  exec "$DOCKER" exec -e STRIP1688_DRY="${STRIP1688_DRY:-0}" -e STRIP1688_VERBOSE="${STRIP1688_VERBOSE:-0}" \
    "$PHP_SVC" sh -c "php -d disable_functions= /usr/local/bin/wp --path='$WP_PATH' --allow-root eval-file /tmp/strip-1688.php"
fi

exec env STRIP1688_DRY="${STRIP1688_DRY:-0}" STRIP1688_VERBOSE="${STRIP1688_VERBOSE:-0}" \
  php -d disable_functions= /usr/local/bin/wp --path="$WP_PATH" --allow-root eval-file "$HERE/strip-1688.php"
