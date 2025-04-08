<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

class ChangelogController {

  const FALLBACK_RECORD = "* Improved: minor changes and fixes.";
  const HEADING_GLUE = ' - ';
  const NEW_CHANGELOG_TEMPLATE = '= x.x.x - YYYY-MM-DD =';

  /** @var string */
  private $readmeFile;

  public function __construct($readmeFile) {
    $this->readmeFile = $readmeFile;
  }

  public function update(string $version) {
    if (!$version) {
      throw new \Exception('Version is required');
    }
    $changelogData = $this->get($version);
    $this->updateReadme($changelogData[0], $changelogData[1]);
    return $changelogData;
  }

  public function get(string $version) {
    if (!$version) {
      throw new \Exception('Version is required');
    }
    $changelog = $this->getChangelogFromReadme();
    if (!$this->containsNewChangelog($changelog)) {
      $changelog = self::NEW_CHANGELOG_TEMPLATE . "\n" . self::FALLBACK_RECORD;
    }
    $changelog = $this->updateHeading($changelog, $version);
    return $changelog;
  }

  private function getChangelogFromReadme() {
    $readme = file_get_contents($this->readmeFile);
    $pattern = '/== Changelog ==\n\n(.*?)(?:\n\n= [0-9]+\.[0-9]+\.[0-9]+ - |\n\[See the changelog)/s';
    if (preg_match($pattern, $readme, $matches)) {
      return trim($matches[1]);
    }
    return '';
  }

  private function containsNewChangelog(string $changelog) {
    return strpos($changelog, self::NEW_CHANGELOG_TEMPLATE) !== false;
  }

  private function updateHeading(string $changelog, string $version) {
    $date = date('Y-m-d');
    $heading = "= $version" . self::HEADING_GLUE . "$date =";
    return str_replace(self::NEW_CHANGELOG_TEMPLATE, $heading, $changelog);
  }

  private function updateReadme($heading, $changesList) {
    if (file_exists(dirname($this->readmeFile) . DIRECTORY_SEPARATOR . 'changelog.txt')) {
      // for the free plugin, in the premium, we don't use the changelog file
      $this->addChangelogEntryToFile($heading, $changesList, dirname($this->readmeFile) . DIRECTORY_SEPARATOR . 'changelog.txt');
      $this->removePreviousChangelogFromReadmeFile();
    }
    $this->addChangelogEntryToFile($heading, $changesList, $this->readmeFile);
  }

  private function addChangelogEntryToFile($heading, $changesList, $fileName) {
    $headingPrefix = explode(self::HEADING_GLUE, $heading)[0];
    $headersDelimiter = "\n";

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

  private function removePreviousChangelogFromReadmeFile() {
    $readme = file_get_contents($this->readmeFile);
    $pattern = '/== Changelog ==(.*)\[See the changelog for all versions.\]/s';
    $readme = preg_replace($pattern, "== Changelog ==\n\n[See the changelog for all versions.]", $readme);
    file_put_contents($this->readmeFile, $readme);
  }
}
