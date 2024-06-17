<?php

function fetchData($url): array {
  $response = file_get_contents($url);
  return json_decode($response, true);
}

function getWordpressVersions($page = 1, $pageSize = 100): array {
  $url = "https://registry.hub.docker.com/v2/repositories/library/wordpress/tags?page_size={$pageSize}&page={$page}";
  $data = fetchData($url);
  return array_column($data['results'], 'name');
}

function filterVersions($versions): array {
  return array_filter($versions, fn($version) => preg_match('/^\d+\.\d+\.\d+-php\d+\.\d+$/', $version));
}

function sortVersions(&$versions) {
  usort($versions, function($a, $b) {
    [$wpA, $phpA] = explode('-php', $a);
    [$wpB, $phpB] = explode('-php', $b);

    $wpCompare = version_compare($wpB, $wpA);
    return $wpCompare !== 0 ? $wpCompare : version_compare($phpB, $phpA);
  });
}

function getLatestAndPreviousVersions($sortedVersions) {
  $uniqueVersions = [];
  foreach ($sortedVersions as $version) {
    [$wpVersion] = explode('-php', $version);
    $majorMinorVersion = preg_replace('/\.\d+$/', '', $wpVersion);
    $uniqueVersions[$majorMinorVersion][] = $version;
  }

  krsort($uniqueVersions);
  $latestVersionGroup = reset($uniqueVersions);
  $previousVersionGroup = next($uniqueVersions);

  if ($previousVersionGroup === false) {
    return [$latestVersionGroup[0], null];
  }

  return [reset($latestVersionGroup), end($previousVersionGroup)];
}

function replaceVersionInFile($filePath, $pattern, $replacement) {
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

function replaceLatestVersion($latestVersion) {
  replaceVersionInFile(
    __DIR__ . './../../../mailpoet/tests/docker/docker-compose.yml',
    '/(wordpress:\${WORDPRESS_IMAGE_VERSION:-\s*)\d+\.\d+\.?\d*-php\d+\.\d+(})/',
    '${1}' . $latestVersion . '${2}'
  );
}

function replacePreviousVersion($previousVersion) {
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

echo "Replacing the latest version in the docker file...\n";
replaceLatestVersion($latestVersion);
echo "Replacing the previous version in the config file...\n";
replacePreviousVersion($previousVersion);
