#!/usr/bin/env bash
set -euo pipefail

HOST="${HOST:-0.0.0.0}"
PORT="${PORT:-8000}"

if [[ -n "${PHP_BIN:-}" ]]; then
  candidates=("$PHP_BIN")
else
  candidates=(
    "/opt/homebrew/opt/php@8.3/bin/php"
    "/usr/bin/php8.3"
    "php8.3"
    "php"
  )
fi

php_bin=""
for candidate in "${candidates[@]}"; do
  if [[ "$candidate" == */* ]]; then
    [[ -x "$candidate" ]] && php_bin="$candidate" && break
  elif command -v "$candidate" >/dev/null 2>&1; then
    php_bin="$(command -v "$candidate")"
    break
  fi
done

if [[ -z "$php_bin" ]]; then
  echo "未找到 PHP 8.0-8.3，请先安装 PHP 8.3。" >&2
  exit 1
fi

version_id="$($php_bin -r 'echo PHP_MAJOR_VERSION * 10000 + PHP_MINOR_VERSION * 100 + PHP_RELEASE_VERSION;')"
if (( version_id < 70000 || version_id >= 80400 )); then
  echo "当前 PHP 版本不兼容：$($php_bin -r 'echo PHP_VERSION;')。请使用 PHP 8.0-8.3。" >&2
  echo "可通过 PHP_BIN=/path/to/php8.3 ./scripts/dev-server.sh 指定版本。" >&2
  exit 1
fi

echo "使用 ${php_bin}（$($php_bin -r 'echo PHP_VERSION;')）启动 http://${HOST}:${PORT}"
# think run 会再次从 PATH 调用 `php -S`，可能悄悄换回系统 PHP 8.5。
# 直接使用已确认版本的二进制启动，确保实际运行时与检测结果一致。
exec "$php_bin" -S "${HOST}:${PORT}" -t public public/router.php
