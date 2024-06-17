<?php

require_once __DIR__ . '/helpers.php';

function getWooCommerceVersions() {
  $url = "https://api.wordpress.org/plugins/info/1.0/woocommerce.json";
  $response = file_get_contents($url);
  $data = json_decode($response, true);

  if (!isset($data['versions'])) {
    die("Failed to fetch WooCommerce versions.");
  }

  return array_keys($data['versions']);
}

function replacePreviousVersion($previousVersion) {
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

echo "Replacing the previous version in the config file...\n";
replacePreviousVersion($previousVersion);
