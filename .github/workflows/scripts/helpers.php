<?php

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

function filterStableVersions($versions) {
  return array_filter($versions, function($version) {
    // Only include stable versions (exclude versions with -rc, -beta, -alpha, etc.)
    return preg_match('/^\d+\.\d+\.\d+$/', $version);
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

function fetchGitHubTags($repo, $token) {
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

function saveVersionsToFile($latestVersion, $previousVersion, $fileName): void {
  $value = "";
  if ($latestVersion) {
    $value .= "- latest version: {$latestVersion}\n";
  }
  if ($previousVersion) {
    $value .= "- previous version: {$previousVersion}\n";
  }
  file_put_contents("/tmp/{$fileName}", $value);
}
