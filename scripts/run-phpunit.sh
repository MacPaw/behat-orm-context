#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
exec vendor/bin/phpunit \
  --do-not-fail-on-phpunit-warning \
  --do-not-fail-on-phpunit-deprecation \
  "$@"
