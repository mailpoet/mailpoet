<?php declare(strict_types = 1);

namespace MailPoetTasks\Release;

class Changelogger {
  const CHANGELOG_DIR = __DIR__ . '/../../changelog/';
  const VALID_TYPES = ['Added', 'Improved', 'Fixed', 'Changed', 'Updated', 'Removed'];

  /** @var string */
  private $changelogDir;

  /**
   * Initializes the Changelogger with the specified changelog directory.
   *
   * If no directory is provided, the default changelog directory is used.
   *
   * @param string|null $changelogDir Optional path to the changelog directory.
   */
  public function __construct(
    $changelogDir = null
  ) {
    $this->changelogDir = $changelogDir ?: self::CHANGELOG_DIR;
  }

  /**
   * Compiles all changelog entries into a formatted changelog string for a given version.
   *
   * If no changelog entries are found, returns a fallback changelog for the specified version.
   *
   * @param string $version The version number to include in the changelog heading.
   * @return string The compiled changelog as a string.
   */
  public function compileChangelog(string $version): string {
    $entries = $this->getChangelogEntries();
    if (empty($entries)) {
      return $this->getFallbackChangelog($version);
    }

    $date = date('Y-m-d');
    $heading = "= $version - $date =\n";

    $compiledEntries = [];
    foreach ($entries as $entry) {
      $compiledEntries[] = "* {$entry['type']}: {$entry['description']}";
    }

    return $heading . implode("\n", $compiledEntries);
  }

  /**
   * Retrieves and parses all valid changelog entries from markdown files in the changelog directory.
   *
   * Each entry is parsed and filtered for validity, then sorted by timestamp extracted from the filenames.
   *
   * @return array An array of changelog entries, each containing at least a timestamp, type, and description.
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
   * Parses a changelog markdown file and extracts the timestamp, type, and description.
   *
   * Returns an associative array with keys `timestamp`, `type`, and `description` if the file is valid and contains a recognized type and non-empty description. Returns `null` if the file is invalid, unreadable, or does not conform to the expected format.
   *
   * @param string $filePath Path to the changelog markdown file.
   * @return array|null Parsed changelog entry or null if invalid.
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
   * Extracts a timestamp in the format YYYY-MM-DD-HH-MM-SS from the beginning of a filename.
   *
   * @param string $filename The filename to extract the timestamp from.
   * @return string The extracted timestamp, or '0000-00-00-00-00-00' if no valid timestamp is found.
   */
  private function extractTimestampFromFilename(string $filename): string {
    if (preg_match('/^(\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2})/', $filename, $matches)) {
      return $matches[1];
    }
    return '0000-00-00-00-00-00';
  }

  /**
   * Generates a default changelog entry for the specified version when no individual entries are present.
   *
   * @param string $version The version number for which to generate the fallback changelog.
   * @return string The fallback changelog string.
   */
  private function getFallbackChangelog(string $version): string {
    $date = date('Y-m-d');
    return "= $version - $date =\n* Improved: minor changes and fixes.";
  }

  /**
   * Deletes all changelog entry markdown files from the changelog directory.
   *
   * Removes every `.md` file in the configured changelog directory if it exists.
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
   * Creates a new changelog entry file with the specified type and description.
   *
   * Validates the changelog type, generates a timestamped and sanitized filename, and writes the entry as a markdown file in the changelog directory.
   *
   * @param string $type The changelog entry type (must be one of the valid types).
   * @param string $description The description of the changelog entry.
   * @return string The full path to the created changelog file.
   * @throws \Exception If the provided type is not valid.
   */
  public function createChangelogEntry(string $type, string $description): string {
    if (!in_array($type, self::VALID_TYPES)) {
      throw new \Exception("Invalid changelog type: $type");
    }

    if (!is_dir($this->changelogDir)) {
      mkdir($this->changelogDir, 0755, true);
    }

    $timestamp = date('Y-m-d-H-i-s');
    $filename = $this->sanitizeFilename($description);
    $filePath = $this->changelogDir . $timestamp . '-' . strtolower($type) . '-' . $filename . '.md';

    $content = "# Type: $type\n\n";
    $content .= "# Description\n$description\n";

    file_put_contents($filePath, $content);
    return $filePath;
  }

  /**
   * Converts a changelog description into a sanitized, lowercase string suitable for use as a filename.
   *
   * Removes invalid characters, replaces whitespace with hyphens, trims hyphens, and limits the result to 50 characters.
   *
   * @param string $description The changelog entry description to sanitize.
   * @return string The sanitized filename string.
   */
  private function sanitizeFilename(string $description): string {
    $filename = strtolower($description);
    $filename = preg_replace('/[^a-z0-9\s-]/', '', $filename);
    $filename = preg_replace('/\s+/', '-', $filename);
    $filename = trim($filename, '-');
    return substr($filename, 0, 50); // Limit length
  }
}
