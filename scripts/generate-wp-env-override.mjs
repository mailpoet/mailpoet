#!/usr/bin/env node
// Generates / updates .wp-env.override.json based on the filesystem.
//
// Rules:
//   - Always include "./mailpoet"
//   - Include "./mailpoet-premium" if the directory exists
//   - Include every extracted plugin directory in mailpoet/tests/plugins/
//     (the .zip files there are extracted into sibling directories by
//     scripts/setup.sh; wp-env's plugins array does not accept local zip
//     paths, so we reference the directories instead)
//   - Preserve any non-plugin keys an operator added manually to
//     .wp-env.override.json
//
// The file is gitignored. This script is idempotent.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const overridePath = path.join(repoRoot, '.wp-env.override.json');

const plugins = ['./mailpoet'];

if (fs.existsSync(path.join(repoRoot, 'mailpoet-premium'))) {
  plugins.push('./mailpoet-premium');
}

const pluginsDir = path.join(repoRoot, 'mailpoet/tests/plugins');
if (fs.existsSync(pluginsDir)) {
  const extracted = fs
    .readdirSync(pluginsDir, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name)
    // Skip empty directories (left over from a failed zip download/extract).
    // wp-env would otherwise fail `wp plugin activate` with "plugin could not be found".
    .filter((name) => {
      const contents = fs.readdirSync(path.join(pluginsDir, name));
      return contents.some((f) => f.endsWith('.php'));
    })
    // WooCommerce must be activated before its dependents
    // (Subscriptions, Memberships, AutomateWoo), so it leads the list.
    .sort((a, b) => {
      if (a === 'woocommerce') return -1;
      if (b === 'woocommerce') return 1;
      return a.localeCompare(b);
    });
  for (const dir of extracted) {
    plugins.push(`./mailpoet/tests/plugins/${dir}`);
  }
}

let override = { $schema: 'https://schemas.wp.org/trunk/wp-env.json' };
if (fs.existsSync(overridePath)) {
  override = JSON.parse(fs.readFileSync(overridePath, 'utf8'));
  if (!override.$schema) {
    override.$schema = 'https://schemas.wp.org/trunk/wp-env.json';
  }
}

override.plugins = plugins;

fs.writeFileSync(overridePath, JSON.stringify(override, null, 2) + '\n');

console.log(`Updated ${overridePath}`);
console.log(`  plugins (${plugins.length}):`);
for (const p of plugins) console.log(`    - ${p}`);
