<?php

require_once __DIR__ . '/helpers.php';

/**
 * We try to get the current available official Docker images for WordPress.
 */
function getWordpressVersions(int $page = 1, int $pageSize = 100): array {
  $url = "https://registry.hub.docker.com/v2/repositories/library/wordpress/tags?page_size={$pageSize}&page={$page}";
  $response = file_get_contents($url);
  $data = json_decode($response, true);
  return array_column($data['results'], 'name');
}

/**
 * We prefer the latest patch versions of WordPress with specified PHP versions.
 * For example: 6.5.4-php8.3
 */
function filterVersions(array $versions): array {
  return array_filter($versions, fn($version) => preg_match('/^\d+\.\d+\.\d+-php\d+\.\d+$/', $version));
}

/**
 * We sort the versions by WordPress version and PHP version.
 * The expected output is:
 *    - 6.5.4-php8.3
 *    - 6.5.4-php8.2
 *    - 6.5.3-php8.3
 *    - 6.5.3-php8.2
 */
function sortVersions(&$versions) {
  usort($versions, function($a, $b) {
    [$wpA, $phpA] = explode('-php', $a);
    [$wpB, $phpB] = explode('-php', $b);

    $wpCompare = version_compare($wpB, $wpA);
    return $wpCompare !== 0 ? $wpCompare : version_compare($phpB, $phpA);
  });
}

/**
 * This function group docker tags by the WordPress version and returns the latest with the higher PHP version
 * abd the previous with the lower PHP version.
 */
function getLatestAndPreviousVersions(array $sortedVersions): array {
  $uniqueVersions = [];
  foreach ($sortedVersions as $version) {
    [$wpVersion] = explode('-php', $version);
    $majorMinorVersion = preg_replace('/\.\d+$/', '', $wpVersion);
    $uniqueVersions[$majorMinorVersion][] = $version;
  }

  krsort($uniqueVersions);
  $latestVersionGroup = reset($uniqueVersions);
  $previousVersionGroup = next($uniqueVersions);

  $latestVersion = $latestVersionGroup === false ? null : reset($latestVersionGroup);
  $previousVersion = $previousVersionGroup === false ? null : end($previousVersionGroup);

  return [$latestVersion, $previousVersion];
}

/**
 * We specify the latest WordPress version only in the docker-compose file for the tests.
 */
function replaceLatestWordPressVersion(string $latestVersion): void {
  replaceVersionInFile(
    __DIR__ . './../../../mailpoet/tests/docker/docker-compose.yml',
    '/(wordpress:\${WORDPRESS_IMAGE_VERSION:-\s*)\d+\.\d+\.?\d*-php\d+\.\d+(})/',
    '${1}' . $latestVersion . '${2}'
  );
}

/**
 * We use the previous WordPress version only in the CircleCI config file.
 */
function replacePreviousWordPressVersion(string $previousVersion): void {
  replaceVersionInFile(
    __DIR__ . './../../../.circleci/config.yml',
    '/(wordpress_image_version: )\d+\.\d+\.?\d*-php\d+\.\d+/',
    '${1}' . $previousVersion
  );
}

$allVersions = [];
$page = 1;
$maxPages = 4;
$latestVersion = null;
$previousVersion = null;

echo "Fetching WordPress versions...\n";

// We fetch the versions until we find the latest and previous versions. But there is a limit of 4 pages.
while (($latestVersion === null || $previousVersion === null) && $page <= $maxPages) {
  $versions = getWordpressVersions($page);
  $allVersions = array_merge($allVersions, $versions);
  $allVersions = filterVersions($allVersions);
  sortVersions($allVersions);
  [$latestVersion, $previousVersion] = getLatestAndPreviousVersions($allVersions);
  $page++;
}

echo "Latest version: $latestVersion\n";
echo "Previous version: $previousVersion\n";

if ($latestVersion) {
  echo "Replacing the latest version in the docker file...\n";
  replaceLatestWordPressVersion($latestVersion);
} else {
  echo "No latest version found.\n";
}

if ($previousVersion) {
  echo "Replacing the previous version in the config file...\n";
  replacePreviousWordPressVersion($previousVersion);
} else {
  echo "No previous version found.\n";
}

saveVersionsToFile($latestVersion, $previousVersion, 'wordpress_versions.txt');
