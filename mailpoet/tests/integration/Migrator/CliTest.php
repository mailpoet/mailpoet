<?php declare(strict_types = 1);

namespace MailPoet\Test\Migrator;

use MailPoet\Migrator\Cli;
use MailPoet\Migrator\Migrator;

class CliTest extends \MailPoetTest {
  public function testItFormatsAStatusRowForTheTable(): void {
    $cli = $this->diContainer->get(Cli::class);
    $row = $cli->formatStatusRow([
      'name' => 'Migration_20221025_120345',
      'level' => 'db',
      'status' => Migrator::MIGRATION_STATUS_FAILED,
      'started_at' => '2026-10-20 10:00:00',
      'completed_at' => null,
      'retries' => 2,
      'error' => "Exception: boom in /plugin/Migration.php:12\nStack trace:\n#0 ...",
      'unknown' => true,
    ]);

    verify($row)->equals([
      'name' => 'Migration_20221025_120345 (unknown)',
      'level' => 'db',
      'status' => Migrator::MIGRATION_STATUS_FAILED,
      'started_at' => '2026-10-20 10:00:00',
      'completed_at' => '',
      'retries' => 2,
      'error' => 'Exception: boom in /plugin/Migration.php:12',
    ]);
  }
}
