<?php

/**
 * Function replacing versions in a file by the regex pattern.
 */
function replaceVersionInFile(string $filePath, string $pattern, string $replacement): void {
  $content = file_get_contents($filePath);

  if ($content === false) {
    fwrite(STDERR, "Failed to read the file at $filePath.\n");
    exit(1);
  }

  $updatedContent = preg_replace($pattern, $replacement, $content);

  if ($updatedContent === null || $updatedContent === $content) {
    echo "Nothing to update in $filePath\n";
    return;
  }

  if (file_put_contents($filePath, $updatedContent) === false) {
    fwrite(STDERR, "Failed to write the updated file at $filePath.\n");
    exit(1);
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
 * Function to fetch tags from a GitHub repository using the gh CLI.
 */
function fetchGitHubTags(string $repo, int $page = 1, int $limit = 50): array {
  $apiPath = sprintf('repos/%s/tags', $repo);
  $ghToken = getenv('GH_TOKEN');
  if ($ghToken) {
    putenv('GH_TOKEN=' . $ghToken); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- needed to authenticate gh CLI for private repos
  }
  $url = sprintf('%s?per_page=%d&page=%d', $apiPath, $limit, $page);
  $command = sprintf(
    'gh api %s --jq %s 2>&1',
    escapeshellarg($url),
    escapeshellarg('.[].name')
  );
  $output = [];
  $exitCode = 0;
  exec($command, $output, $exitCode);

  if ($exitCode !== 0) {
    fwrite(STDERR, "Failed to fetch tags for '$repo' (page $page): " . implode("\n", $output) . "\n");
    exit(1);
  }

  return array_filter($output, function ($line) {
    return $line !== '';
  });
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
  $page = 1;
  $latestVersion = null;
  $previousVersion = null;
  $allVersions = [];
  while (($latestVersion === null || $previousVersion === null) && $page < 10) {
    $allVersions = array_merge($allVersions, fetchGitHubTags($repository, $page));
    $stableVersions = filterStableVersions($allVersions);
    [$latestVersion, $previousVersion] = getLatestAndPreviousMinorMajorVersions($stableVersions);
    $page++;
  }

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
}
