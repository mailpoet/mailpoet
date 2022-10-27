<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoetTest;

// testing migration files
require_once __DIR__ . '/TestMigrations/Migration_20221024_080348.php';
require_once __DIR__ . '/TestMigrationsFail/Migration_20221023_040819.php';
require_once __DIR__ . '/TestMigrationsInvalid/Migration_20221022_021304.php';

class RunnerTest extends MailPoetTest {
  private const DATE_TIME_FORMAT = '%d%d-%d%d-%d%d %d%d:%d%d:%d%d';

  /** @var string */
  private $table;

  /** @var Store */
  private $store;

  /** @var Runner */
  private $runner;

  public function _before() {
    parent::_before();
    $this->table = Env::$dbPrefix . 'testing_migrations';
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->table}");
    $this->store = $this->getServiceWithOverrides(Store::class, [
      'table' => $this->table,
    ]);
    $this->runner = $this->getServiceWithOverrides(Runner::class, [
      'store' => $this->store,
    ]);
    $this->store->ensureMigrationsTable();
  }

  public function testItRunsMigration(): void {
    ob_start();
    $this->runner->runMigration('Migration_20221024_080348');
    $output = ob_get_clean();
    $this->assertSame('Migration run called!', $output);

    $processed = $this->store->getAll();
    $this->assertCount(1, $processed);

    $data = $processed[0];
    $this->assertSame('Migration_20221024_080348', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
    $this->assertNull($data['error']);
  }

  public function testItFailsWithNonExistentMigration(): void {
    $this->expectException(MigratorException::class);
    $this->expectExceptionMessage('MailPoet\Migrations\MigrationThatDoesntExist" not found.');
    $this->runner->runMigration('MigrationThatDoesntExist');
    $this->assertEmpty($this->store->getAll());
  }

  public function testItFailsWithInvalidMigrationClass(): void {
    $this->expectException(MigratorException::class);
    $this->expectExceptionMessage('Migration class "MailPoet\Migrations\Migration_20221022_021304" is not a subclass of "MailPoet\Migrator\Migration".');
    $this->runner->runMigration('Migration_20221022_021304');
    $this->assertEmpty($this->store->getAll());
  }

  public function testItFailsWithBrokenMigration(): void {
    $this->expectException(MigratorException::class);
    $this->expectExceptionMessage('Migration "MailPoet\Migrations\Migration_20221023_040819" failed. Details: Testing failing migration.');
    $this->runner->runMigration('Migration_20221023_040819');

    $processed = $this->store->getAll();
    $this->assertCount(1, $processed);

    $data = $processed[0];
    $this->assertSame('Migration_20221023_040819', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
    $this->assertSame($data['error'], 'Testing failing migration.');
  }

  public function _after() {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->table}");
  }
}
