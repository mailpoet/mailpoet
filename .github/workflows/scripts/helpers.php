<?php

/**
 * Function replacing versions in a file by the regex pattern.
 */
function replaceVersionInFile(string $filePath, string $pattern, string $replacement): void {
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

/**
 * Function to filter stable versions from a list of versions.
 */
function filterStableVersions(array $versions): array {
  return array_filter($versions, function($version) {
    // Only include stable versions (exclude versions with -rc, -beta, -alpha, etc.)
    return preg_match('/^\d+\.\d+\.\d+$/', $version);
  });
}

/**
 * Function to get the latest and previous minor/major versions from a list of versions.
 */
function getLatestAndPreviousMinorMajorVersions(array $versions): array {
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

function getMinorMajorVersion(string $version): string {
  $parts = explode('.', $version);
  return $parts[0] . '.' . $parts[1];
}

/**
 * Function to fetch tags from a GitHub repository.
 */
function fetchGitHubTags(string $repo, string $token): array {
  $url = "https://api.github.com/repos/$repo/tags";
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');  // GitHub API requires a user agent
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $token"
  ]);
  $response = curl_exec($ch);
  curl_close($ch);

  if ($response === false) {
    die("Failed to fetch tags from GitHub.");
  }

  $data = json_decode($response, true);

  if (isset($data['message']) && $data['message'] == 'Not Found') {
    die("Repository not found or access denied.");
  }

  return array_column($data, 'name');
}

/**
 * Function saving versions to a temporary files.
 * File containing latest version is prefixed with 'latest_' and previous version is prefixed with 'previous_'.
 */
function saveVersionsToFiles(string $latestVersion, string $previousVersion, string $fileNameSuffix): void {
  file_put_contents("/tmp/latest_{$fileNameSuffix}", $latestVersion);
  file_put_contents("/tmp/previous_{$fileNameSuffix}", $previousVersion);
}

function replaceLatestVersion(string $latestVersion, string $downloadCommand): void {
  replaceVersionInFile(
    __DIR__ . '/../../../.circleci/config.yml',
    '/(.\/do ' . $downloadCommand . ' )\d+\.\d+\.\d+/',
    '${1}' . $latestVersion
  );
}

function replacePreviousVersion(string $previousVersion, string $configParameterName): void {
  replaceVersionInFile(
    __DIR__ . '/../../../.circleci/config.yml',
    '/(' . $configParameterName . ': )\d+\.\d+\.\d+/',
    '${1}' . $previousVersion
  );
}

/**
 * Function replacing the latest and previous versions of a private plugin in the config file.
 * The function fetches the tags from the GitHub repository, filters stable versions,
 * gets the latest and previous minor/major versions, and replaces the versions in the CircleCI config file.
 */
function replacePrivatePluginVersion(
  string $repository,
  string $downloadCommand,
  string $configParameterName,
  string $versionsFilename
): void {
// Read the GitHub token from environment variable
  $token = getenv('GH_TOKEN');
  if (!$token) {
    die("GitHub token not found. Make sure it's set in the environment variable 'GH_TOKEN'.");
  }

  $allVersions = fetchGitHubTags($repository, $token);
  $stableVersions = filterStableVersions($allVersions);
  [$latestVersion, $previousVersion] = getLatestAndPreviousMinorMajorVersions($stableVersions);

  echo "Latest version: $latestVersion\n";
  echo "Previous version: $previousVersion\n";

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

  saveVersionsToFiles($latestVersion, $previousVersion, $versionsFilename);
}
