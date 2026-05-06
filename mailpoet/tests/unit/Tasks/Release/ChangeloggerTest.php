<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks\Release;

use MailPoetTasks\Release\Changelogger;

class ChangeloggerTest extends \MailPoetUnitTest {
  /** @var string */
  private $tempDir;

  public function _before() {
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-changelog-' . uniqid('', true) . '/';
    mkdir($this->tempDir, 0777, true);
  }

  public function _after() {
    foreach (glob($this->tempDir . '*') ?: [] as $file) {
      unlink($file);
    }
    rmdir($this->tempDir);
  }

  public function testItRemovesTrailingPunctuationWhenCompilingChangelog() {
    $this->createChangelogEntry(
      '2026-01-01-10-00-00-added-duplicate-action.md',
      'Added',
      'Duplicate action for automations.'
    );
    $this->createChangelogEntry(
      '2026-01-01-10-01-00-fixed-duplicate-punctuation.md',
      'Fixed',
      'Avoid duplicate punctuation;'
    );

    $changelog = (new Changelogger($this->tempDir))->compileChangelog('1.2.3');

    verify($changelog)->equals(
      "= 1.2.3 - " . date('Y-m-d') . " =\n" .
      "* Added: Duplicate action for automations;\n" .
      "* Fixed: Avoid duplicate punctuation."
    );
  }

  private function createChangelogEntry(string $filename, string $type, string $description): void {
    file_put_contents(
      $this->tempDir . $filename,
      "# Type: $type\n\n# Description\n\n$description\n"
    );
  }
}
