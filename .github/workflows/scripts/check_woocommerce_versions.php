<?php

function getWooCommerceVersions() {
  $url = "https://api.wordpress.org/plugins/info/1.0/woocommerce.json";
  $response = file_get_contents($url);
  $data = json_decode($response, true);

  if (!isset($data['versions'])) {
    die("Failed to fetch WooCommerce versions.");
  }

  return array_keys($data['versions']);
}

function filterStableVersions($versions) {
  return array_filter($versions, function($version) {
    // Only include stable versions (exclude versions with -rc, -beta, -alpha, etc.)
    return !preg_match('/-(rc|beta|alpha|dev|nightly|pl)/i', $version);
  });
}

function getLatestAndPreviousMinorMajorVersions($versions) {
  usort($versions, 'version_compare');
  $currentVersion = end($versions);

  $previousVersion = null;
  foreach (array_reverse($versions) as $version) {
    if (version_compare($version, $currentVersion, '<') && getMinorMajorVersion($version) !== getMinorMajorVersion($currentVersion)) {
      $previousVersion = $version;
      break;
    }
  }

  return [$currentVersion, $previousVersion];
}

function getMinorMajorVersion($version) {
  $parts = explode('.', $version);
  return $parts[0] . '.' . $parts[1];
}

function replaceVersionInFile($filePath, $pattern, $replacement): void {
  $content = file_get_contents($filePath);

  if ($content === false) {
    die("Failed to read the file at $filePath.");
  }

  $updatedContent = preg_replace($pattern, $replacement, $content);

  if ($updatedContent === null || $updatedContent === $content) {
    echo "Nothing to update in $filePath\n";
    return;
  }

  if (file_put_contents($filePath, $updatedContent) === false) {
    die("Failed to write the updated file at $filePath.");
  }
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
list($latestVersion, $previousVersion) = getLatestAndPreviousMinorMajorVersions($stableVersions);


echo "Latest WooCommerce version: $latestVersion\n";
echo "Previous  WooCommerce version: $previousVersion\n";

echo "Replacing the previous version in the config file...\n";
replacePreviousVersion($previousVersion);
