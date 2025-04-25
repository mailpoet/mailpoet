<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

class ChangelogController {

  const FALLBACK_RECORD = "* Improved: minor changes and fixes.";
  const HEADING_GLUE = ' - ';
  const NEW_CHANGELOG_TEMPLATE = '= x.x.x - YYYY-MM-DD =';

  /** @var string */
  private $readmeFile;

  public function __construct(
    $readmeFile
  ) {
    $this->readmeFile = $readmeFile;
  }

  public function update(string $version) {
    if (!$version) {
      throw new \Exception('Version is required');
    }
    $changelog = $this->get($version);
    $this->updateChangelogTxt($changelog);
    $this->updateReadmeTxt($changelog, $version);
    return $changelog;
  }

  public function get(string $version) {
    if (!$version) {
      throw new \Exception('Version is required');
    }
    $changelog = $this->getChangelogFromReadme();
    if (!$this->containsNewChangelogOrVersionExists($changelog, $version)) {
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

  private function containsNewChangelogOrVersionExists(string $changelog, string $version) {
    return (
      strpos($changelog, self::NEW_CHANGELOG_TEMPLATE) !== false ||
      strpos($changelog, "= $version") !== false
    );
  }

  private function updateHeading(string $changelog, string $version) {
    $date = date('Y-m-d');
    $heading = "= $version" . self::HEADING_GLUE . "$date =";
    return str_replace(self::NEW_CHANGELOG_TEMPLATE, $heading, $changelog);
  }

  private function updateChangelogTxt($changelog) {
    $changelogTxtFile = dirname($this->readmeFile) . DIRECTORY_SEPARATOR . 'changelog.txt';
    if (!file_exists($changelogTxtFile)) {
      return;
    }

    $fileContents = file_get_contents($changelogTxtFile);
    $header = "== Changelog ==\n\n";
    $fileContents = str_replace($header, $header . $changelog . "\n\n", $fileContents);
    file_put_contents($changelogTxtFile, $fileContents);
  }

  private function updateReadmeTxt($changelog, $version) {
    $fileContents = file_get_contents($this->readmeFile);

    if (!$this->containsNewChangelogOrVersionExists($fileContents, $version)) {
      // In the free plugin, remove the previous changelog before adding a new one.
      // Premium plugin doesn't contain link to full changelog.
      $pattern = '/== Changelog ==(.*)\[See the changelog for all versions.\]/s';
      $fileContents = preg_replace($pattern, "== Changelog ==\n\n[See the changelog for all versions.]", $fileContents);

      // Add the new changelog in both plugins.
      $fileContents = preg_replace("/== Changelog ==\n/u", "== Changelog ==\n\n$changelog\n", $fileContents);

    }
    $fileContents = $this->updateHeading($fileContents, $version);

    file_put_contents($this->readmeFile, $fileContents);
  }
}
