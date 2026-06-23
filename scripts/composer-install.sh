#!/usr/bin/env bash
set -euo pipefail

# Runs composer install in mailpoet/ and, if present, mailpoet-premium/.
# Intended to be called from the repo root's package.json postinstall hook
# and from scripts/setup.sh.

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

echo "==> composer install in mailpoet/"
(cd "$ROOT_DIR/mailpoet" && composer install --no-interaction)

if [ -f "$ROOT_DIR/mailpoet-premium/composer.json" ]; then
  echo "==> composer install in mailpoet-premium/"
  (cd "$ROOT_DIR/mailpoet-premium" && composer install --no-interaction)
else
  echo "==> skipping mailpoet-premium/ (no composer.json found)"
fi
