<?php

require_once __DIR__ . '/helpers.php';

$downloadCommand = 'download:woo-commerce-zip';
$configParameterName = 'woo_core_version';
$versionsFilenameSuffix = 'woocommerce_version.txt';

/**
 * We get the official WooCommerce versions from the WordPress API.
 */
function getWooCommerceVersions(): array {
  $url = "https://api.wordpress.org/plugins/info/1.0/woocommerce.json";
  $response = file_get_contents($url);
  $data = json_decode($response, true);

  if (!isset($data['versions'])) {
    die("Failed to fetch WooCommerce versions.");
  }

  return array_keys($data['versions']);
}

$allVersions = getWooCommerceVersions();
$stableVersions = filterStableVersions($allVersions);
[$latestVersion, $previousVersion] = getLatestAndPreviousMinorMajorVersions($stableVersions);

echo "Latest WooCommerce version: $latestVersion\n";
echo "Previous  WooCommerce version: $previousVersion\n";

if ($latestVersion) {
  echo "Replacing the latest version in the config file...\n";
  replaceLatestVersion($latestVersion, $downloadCommand);
} else {
  echo "No latest version found.\n";
}

if ($previousVersion) {
  echo "Replacing the previous version in the config file...\n";
  replacePreviousVersion($previousVersion, $configParameterName);
} else {
  echo "No previous version found.\n";
}

saveVersionsToFiles($latestVersion, $previousVersion, $versionsFilenameSuffix);
