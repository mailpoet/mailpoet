<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

class ChangelogController {

  const FALLBACK_RECORD = "* Improved: minor changes and fixes.";
  const HEADING_GLUE = ' - ';

  /** @var string */
  private $readmeFile;

  /** @var JiraController */
  private $jira;

  public function __construct(
    JiraController $jira,
    $readmeFile
  ) {
    $this->jira = $jira;
    $this->readmeFile = $readmeFile;
  }

  public function update($versionName = null) {
    $changelogData = $this->get($versionName);
    $this->updateReadme($changelogData[0], $changelogData[1]);
    return $changelogData;
  }

  public function get($versionName = null) {
    $version = $this->jira->getVersion($versionName);
    $issues = $this->jira->getIssuesDataForVersion($version);
    $heading = $this->renderHeading($version);
    $changelog = $this->renderList($issues, JiraController::CHANGELOG_FIELD_ID);
    if (!$changelog) {
      $changelog = self::FALLBACK_RECORD;
    }
    $notes = $this->renderList($issues, JiraController::RELEASENOTE_FIELD_ID);
    return [$heading, $changelog, $notes];
  }

  private function renderHeading(array $version) {
    $date = empty($version['releaseDate']) ? date('Y-m-d') : $version['releaseDate'];
    return "= {$version['name']}" . self::HEADING_GLUE . "$date =";
  }

  private function renderList(array $issues, $field) {
    $messages = [];
    foreach ($issues as $issue) {
      if (
        !isset($issue['fields'][$field])
        || ($issue['fields']['resolution']['id'] === JiraController::WONT_DO_RESOLUTION_ID)
      ) {
        continue;
      }
      $messages[] = "* " . $this->sanitizePunctuation($issue['fields'][$field], ';');
    }
    if (empty($messages)) {
      return null;
    }
    $list = implode("\n", $messages);
    return empty($list) ? $list : $this->sanitizePunctuation($list, '.');
  }

  private function sanitizePunctuation($message, $fallback) {
    $validPunctuation = ['?', '.', '!'];
    $message = rtrim($message, ';, ');
    if (!in_array(substr($message, -1), $validPunctuation)) {
      $message .= $fallback;
    }
    return $message;
  }

  private function updateReadme($heading, $changesList) {
    if (file_exists(dirname($this->readmeFile) . DIRECTORY_SEPARATOR . 'CHANGELOG.md')) {
      $this->updateReadmeWitchChangelogFile($heading, $changesList);
    } else {
      $this->addChangelogEntryToReadmeFile($heading, $changesList);
    }
  }

  private function addChangelogEntryToReadmeFile($heading, $changesList) {
    $headingPrefix = explode(self::HEADING_GLUE, $heading)[0];
    $readme = file_get_contents($this->readmeFile);
    $changelog = "$heading\n$changesList";

    if (strpos($readme, $headingPrefix) !== false) {
      $start = preg_quote($headingPrefix);
      $readme = preg_replace("/$start.*?(?:\r*\n){2}/us", "$changelog\n\n", $readme);
    } else {
      $readme = preg_replace("/== Changelog ==\n/u", "== Changelog ==\n\n$changelog\n", $readme);
    }
    file_put_contents($this->readmeFile, $readme);
  }

  private function updateReadmeWitchChangelogFile($heading, $changesList) {
    $this->copyPreviousChangelogFromReadmeFile();
    $this->removePreviousChangelogFromReadmeFile();
    $this->addChangelogEntryToReadmeFile($heading, $changesList);
  }

  private function copyPreviousChangelogFromReadmeFile() {
    $changelogFile = dirname($this->readmeFile) . DIRECTORY_SEPARATOR . 'CHANGELOG.md';
    $readme = file_get_contents($this->readmeFile);
    $pattern = '/== Changelog ==(.*)\[See the changelog for all versions.\]/s';
    preg_match($pattern, $readme, $matches);
    if (!isset($matches[1]) || strlen($matches[1]) < 1) {
      throw new \Exception('Changelog not found in readme file');
    }
    $changelog = trim($matches[1]);

    $newContent = preg_replace("/== Changelog ==\n/u", "== Changelog ==\n\n$changelog\n", file_get_contents($changelogFile));

    file_put_contents($changelogFile, $newContent);
  }

  private function removePreviousChangelogFromReadmeFile() {
    $readme = file_get_contents($this->readmeFile);
    $pattern = '/== Changelog ==(.*)\[See the changelog for all versions.\]/s';
    $readme = preg_replace($pattern, "== Changelog ==\n\n[See the changelog for all versions.]", $readme);
    file_put_contents($this->readmeFile, $readme);
  }
}
