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
