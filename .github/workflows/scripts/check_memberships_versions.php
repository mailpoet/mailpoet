<?php

require_once __DIR__ . '/helpers.php';

// Read the GitHub token from environment variable
$token = getenv('GH_TOKEN');
if (!$token) {
  die("GitHub token not found. Make sure it's set in the environment variable 'GH_TOKEN'.");
}


function replaceLatestVersion($previousVersion) {
  replaceVersionInFile(
    __DIR__ . './../../../.circleci/config.yml',
    '/(.\/do download:woo-commerce-memberships-zip )\d+\.\d+\.\d+/',
    '${1}' . $previousVersion
  );
}

function replacePreviousVersion($previousVersion) {
  replaceVersionInFile(
    __DIR__ . './../../../.circleci/config.yml',
    '/(woo_memberships_version: )\d+\.\d+\.\d+/',
    '${1}' . $previousVersion
  );
}

$repository = 'woocommerce/woocommerce-memberships';

$allVersions = fetchGitHubTags($repository, $token);
$stableVersions = filterStableVersions($allVersions);
[$latestVersion, $previousVersion] = getLatestAndPreviousMinorMajorVersions($stableVersions);

echo "Latest WooCommerce Memberships version: $latestVersion\n";
echo "Previous  WooCommerce Memberships version: $previousVersion\n";

echo "Replacing the latest version in the config file...\n";
replaceLatestVersion($latestVersion);
echo "Replacing the previous version in the config file...\n";
replacePreviousVersion($previousVersion);
