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
      // for the free plugin, in the premium, we don't use the changelog file
      $this->updateReadmeWithChangelogFile($heading, $changesList);
    }
    $this->addChangelogEntryToFile($heading, $changesList, $this->readmeFile);
  }

  private function addChangelogEntryToFile($heading, $changesList, $fileName) {
    $headingPrefix = explode(self::HEADING_GLUE, $heading)[0];
    $headersDelimiter = "\n";

    if (strpos($fileName, '.md') !== false) {
      $headersDelimiter .= "\n";
      $changesList = preg_replace("/^\*/m", "-", $changesList);
    }

    $fileContents = file_get_contents($fileName);
    $changelog = "$heading$headersDelimiter$changesList";

    if (strpos($fileContents, $headingPrefix) !== false) {
      $start = preg_quote($headingPrefix);
      $fileContents = preg_replace("/$start.*?(?:\r*\n){2}([=\[])/us", "$changelog\n\n$1", $fileContents);
    } else {
      $fileContents = preg_replace("/== Changelog ==\n/u", "== Changelog ==\n\n$changelog\n", $fileContents);
    }
    file_put_contents($fileName, $fileContents);
  }

  private function updateReadmeWithChangelogFile($heading, $changesList) {
    $this->addChangelogEntryToFile($heading, $changesList, dirname($this->readmeFile) . DIRECTORY_SEPARATOR . 'CHANGELOG.md');
    $this->removePreviousChangelogFromReadmeFile();
  }

  private function removePreviousChangelogFromReadmeFile() {
    $readme = file_get_contents($this->readmeFile);
    $pattern = '/== Changelog ==(.*)\[See the changelog for all versions.\]/s';
    $readme = preg_replace($pattern, "== Changelog ==\n\n[See the changelog for all versions.]", $readme);
    file_put_contents($this->readmeFile, $readme);
  }
}
