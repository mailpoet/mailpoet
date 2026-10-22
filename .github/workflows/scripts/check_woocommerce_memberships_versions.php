<?php

require_once __DIR__ . '/helpers.php';

// Memberships has no usable tags. Each release is a commit in woocommerce/all-plugins
// whose message ends with the version, e.g. "woocommerce-memberships:958589 1.30.1".
$repository = 'woocommerce/all-plugins';
$zipPath = 'product-packages/woocommerce-memberships/woocommerce-memberships.zip';
$configFile = __DIR__ . '/../../../.circleci/config.yml';

$command = sprintf(
  'gh api %s --jq %s 2>&1',
  escapeshellarg(sprintf('repos/%s/commits?path=%s&per_page=1', $repository, $zipPath)),
  escapeshellarg('.[0] | .sha + " " + (.commit.message | split("\n")[0])')
);
$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);

// Expected line: "<40-char sha> woocommerce-memberships:<id> <version>"
if ($exitCode !== 0 || !preg_match('/^([0-9a-f]{40}) .* (\d+\.\d+\.\d+)$/', implode("\n", $output), $matches)) {
  fwrite(STDERR, "Failed to fetch the latest commit for '$zipPath': " . implode("\n", $output) . "\n");
  exit(1);
}
[, $latestSha, $latestVersion] = $matches;

echo "Latest version: $latestVersion ($latestSha)\n";
echo "Replacing the latest version in the config file...\n";

// Matches the version comment line and the download command that follows it; the SHA is 40 hex characters.
replaceVersionInFile(
  $configFile,
  '/(# WooCommerce Memberships )\d+\.\d+\.\d+( \(woocommerce\/all-plugins commit\)\n\s*\.\/do download:woo-commerce-memberships-zip )[0-9a-f]{40}/',
  '${1}' . $latestVersion . '${2}' . $latestSha
);
