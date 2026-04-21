#!/usr/bin/env bash
set -euo pipefail

# Bootstrap the MailPoet wp-env dev environment.
# Run once after cloning (or after pulling changes that add plugins).
#
# Prereqs:
#   - Node per .nvmrc (run: nvm use)
#   - pnpm (via: corepack enable pnpm)
#   - PHP 8.4 (matches .wp-env.json phpVersion)
#   - gh authenticated for private WooCommerce plugin downloads (run: gh auth login)
#     External contributors without gh access can ignore the download warnings.

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "==> [1/5] Installing Node + PHP dependencies (pnpm install + postinstall)"
pnpm install

echo "==> [2/5] Verifying mailpoet/vendor-prefixed/ is populated"
if [ -z "$(ls -A mailpoet/vendor-prefixed 2>/dev/null)" ]; then
  echo "  ! vendor-prefixed/ is empty — running prefixer and post-install fixers"
  (cd mailpoet \
    && bash prefixer/process.sh \
    && php ./tasks/fix-guzzle.php \
    && php ./tasks/fix-php82-deprecations.php \
    && php ./tasks/FixPhp84Deprecations.php)
else
  echo "  ✓ vendor-prefixed/ populated"
fi

echo "==> [3/5] Downloading and extracting WooCommerce test plugins (requires gh auth for private ones)"
cd mailpoet
./do download:woo-commerce-zip latest || echo "  ! WooCommerce (free) download failed — continuing"
./do download:woo-commerce-subscriptions-zip latest || echo "  ! Subscriptions download failed (requires gh auth + repo access) — continuing"
./do download:automate-woo-zip latest || echo "  ! AutomateWoo download failed (requires gh auth + repo access) — continuing"
./do download:woo-commerce-memberships-zip || echo "  ! Memberships download failed (requires gh auth + repo access) — continuing"
cd "$ROOT_DIR"
# wp-env's plugins array doesn't accept local zip paths, so extract each
# downloaded zip into a sibling directory that we then reference.
if ls mailpoet/tests/plugins/*.zip >/dev/null 2>&1; then
  cd mailpoet/tests/plugins
  for z in *.zip; do
    dir="${z%.zip}"
    echo "  ✓ Extracting $z -> $dir/"
    rm -rf "$dir"
    unzip -q -o "$z"
  done
  cd "$ROOT_DIR"
fi

echo "==> [4/5] Generating .wp-env.override.json"
node scripts/generate-wp-env-override.mjs

echo "==> [5/5] Compiling assets"
cd mailpoet
./do compile:all
cd "$ROOT_DIR"

echo ""
echo "Setup complete."
echo "Next steps:"
echo "  1. Run: pnpm env:start"
echo "  2. Open:"
echo "       WordPress     http://localhost:8888"
echo "       phpMyAdmin    http://localhost:8081"
echo "       Mailpit       http://localhost:8082"
