<?php

require_once __DIR__ . '/helpers.php';

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

function replaceLatestVersion($latestVersion): void {
  replaceVersionInFile(
    __DIR__ . './../../../.circleci/config.yml',
    '/(.\/do download:woo-commerce-zip )\d+\.\d+\.\d+/',
    '${1}' . $latestVersion
  );
}

function replacePreviousVersion($previousVersion): void {
  replaceVersionInFile(
    __DIR__ . './../../../.circleci/config.yml',
    '/(woo_core_version: )\d+\.\d+\.?\d*/',
    '${1}' . $previousVersion
  );
}

$allVersions = getWooCommerceVersions();
$stableVersions = filterStableVersions($allVersions);
[$latestVersion, $previousVersion] = getLatestAndPreviousMinorMajorVersions($stableVersions);

echo "Latest WooCommerce version: $latestVersion\n";
echo "Previous  WooCommerce version: $previousVersion\n";

if ($latestVersion) {
  echo "Replacing the latest version in the config file...\n";
  replaceLatestVersion($latestVersion);
} else {
  echo "No latest version found.\n";
}

if ($previousVersion) {
  echo "Replacing the previous version in the config file...\n";
  replacePreviousVersion($previousVersion);
} else {
  echo "No previous version found.\n";
}

saveVersionsToFile($latestVersion, $previousVersion, 'woocommerce_versions.txt');
