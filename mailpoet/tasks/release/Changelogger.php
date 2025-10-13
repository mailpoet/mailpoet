<?php declare(strict_types = 1);

namespace MailPoetTasks\Release;

class Changelogger {
  const CHANGELOG_DIR = __DIR__ . '/../../changelog/';
  const VALID_TYPES = ['Added', 'Updated', 'Improved', 'Changed', 'Fixed', 'Removed'];

  /** @var string */
  private $changelogDir;

  public function __construct(
    $changelogDir = null
  ) {
    $this->changelogDir = $changelogDir ?: self::CHANGELOG_DIR;
  }

  /**
   * Compiles all changelog entries into a single changelog string
   */
  public function compileChangelog(string $version): string {
    $entries = $this->getChangelogEntries();
    if (empty($entries)) {
      return $this->getFallbackChangelog($version);
    }

    $date = date('Y-m-d');
    $heading = "= $version - $date =\n";

    // Group entries by type and order by importance
    $groupedEntries = $this->groupEntriesByType($entries);

    $compiledEntries = [];
    foreach (self::VALID_TYPES as $type) {
      if (isset($groupedEntries[$type])) {
        foreach ($groupedEntries[$type] as $entry) {
          $compiledEntries[] = "* {$entry['type']}: {$entry['description']}";
        }
      }
    }

    $changelogContent = implode(";\n", $compiledEntries) . ".";

    return $heading . $changelogContent;
  }

  /**
   * Groups entries by their type
   */
  private function groupEntriesByType(array $entries): array {
    $grouped = [];
    foreach ($entries as $entry) {
      $type = $entry['type'];
      if (!isset($grouped[$type])) {
        $grouped[$type] = [];
      }
      $grouped[$type][] = $entry;
    }
    return $grouped;
  }

  /**
   * Gets all changelog entries from individual files
   */
  private function getChangelogEntries(): array {
    if (!is_dir($this->changelogDir)) {
      return [];
    }

    $entries = [];
    $files = glob($this->changelogDir . '*.md');

    foreach ($files as $file) {
      $entry = $this->parseChangelogFile($file);
      if ($entry) {
        $entries[] = $entry;
      }
    }

    // Sort by timestamp (filename contains timestamp)
    usort($entries, function($a, $b) {
      return strcmp($a['timestamp'], $b['timestamp']);
    });

    return $entries;
  }

  /**
   * Parses a single changelog file
   */
  public function parseChangelogFile(string $filePath): ?array {
    $content = file_get_contents($filePath);
    if (!$content) {
      return null;
    }

    $lines = explode("\n", $content);
    $entry = [
      'timestamp' => $this->extractTimestampFromFilename(basename($filePath)),
      'type' => null,
      'description' => null,
    ];

    $currentSection = null;
    $sectionContent = [];

    foreach ($lines as $line) {
      $line = trim($line);
      if (preg_match('/^# Type:\s*(.+)$/', $line, $matches)) {
        $entry['type'] = trim($matches[1]);
        $currentSection = 'type';
      } elseif (preg_match('/^# Description$/', $line)) {
        $currentSection = 'description';
        $sectionContent = [];
      } elseif ($line && $currentSection === 'description' && $line[0] !== '#') {
        $sectionContent[] = $line;
      }
    }

    if (!empty($sectionContent)) {
      $entry['description'] = implode(' ', $sectionContent);
    }

    // Validate entry
    if (!$entry['type'] || !$entry['description'] || !in_array($entry['type'], self::VALID_TYPES)) {
      return null;
    }

    return $entry;
  }

  /**
   * Extracts timestamp from filename
   */
  private function extractTimestampFromFilename(string $filename): string {
    if (preg_match('/^(\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2})/', $filename, $matches)) {
      return $matches[1];
    }
    return '0000-00-00-00-00-00';
  }

  /**
   * Returns fallback changelog when no entries exist
   */
  private function getFallbackChangelog(string $version): string {
    $date = date('Y-m-d');
    return "= $version - $date =\n* Improved: minor changes and fixes.";
  }

  /**
   * Clears all changelog entries after compilation
   */
  public function clearChangelogEntries(): void {
    if (!is_dir($this->changelogDir)) {
      return;
    }

    $files = glob($this->changelogDir . '*.md');
    foreach ($files as $file) {
      unlink($file);
    }
  }

  /**
   * Creates a new changelog entry file
   */
  public function createChangelogEntry(string $type, string $description): string {
    if (!in_array($type, self::VALID_TYPES)) {
      throw new \Exception("Invalid changelog type: $type");
    }

    // Trim whitespace and remove trailing punctuation
    $description = trim($description);
    $description = rtrim($description, '.!?;,');
    // Ensure description starts with a capital letter
    $description = ucfirst($description);

    if (empty($description)) {
      throw new \Exception("Description cannot be empty");
    }

    if (!is_dir($this->changelogDir)) {
      mkdir($this->changelogDir, 0755, true);
    }

    $timestamp = date('Y-m-d-H-i-s');
    $filename = $this->sanitizeFilename($description);
    $filePath = $this->changelogDir . $timestamp . '-' . strtolower($type) . '-' . $filename . '.md';

    $content = "# Type: $type\n\n";
    $content .= "# Description\n\n$description\n";

    file_put_contents($filePath, $content);
    return $filePath;
  }

  /**
   * Sanitizes filename for changelog entry
   */
  private function sanitizeFilename(string $description): string {
    $filename = strtolower($description);
    $filename = preg_replace('/[^a-z0-9\s-]/', '', $filename);
    $filename = preg_replace('/\s+/', '-', $filename);
    $filename = trim($filename, '-');
    return substr($filename, 0, 50); // Limit length
  }
}
