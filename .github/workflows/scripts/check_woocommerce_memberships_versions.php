<?php

require_once __DIR__ . '/helpers.php';

// Memberships has no tags. Each release is a commit in woocommerce/all-plugins
// whose message ends with the version, e.g. "woocommerce-memberships:958589 1.30.1".
$repository = 'woocommerce/all-plugins';
$zipPath = 'product-packages/woocommerce-memberships/woocommerce-memberships.zip';
$downloadCommand = 'download:woo-commerce-memberships-zip';
$configParameterName = 'woo_memberships_version';
$versionsFilenameSuffix = 'woocommerce_memberships_version.txt';

replacePrivatePluginVersion($repository, $downloadCommand, $configParameterName, $versionsFilenameSuffix, function (int $page) use ($repository, $zipPath): array {
  return fetchGitHubCommitVersions($repository, $zipPath, $page);
});
