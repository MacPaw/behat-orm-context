#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
# PHPUnit 10+ treats runner warnings as failures unless these flags are set (PHPUnit 9 ignores them).
if vendor/bin/phpunit --version 2>/dev/null | grep -qE '^PHPUnit (10\.|1[1-9]\.|[2-9][0-9]\.)'; then
  exec vendor/bin/phpunit --do-not-fail-on-phpunit-warning --do-not-fail-on-phpunit-deprecation "$@"
fi
exec vendor/bin/phpunit "$@"
