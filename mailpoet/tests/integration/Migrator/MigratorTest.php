<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoetTest;

// testing migration files
require_once __DIR__ . '/TestMigrations/Migration_20221024_080348.php';
require_once __DIR__ . '/TestMigrations/Migration_20221025_120345.php';
require_once __DIR__ . '/TestMigrations/Migration_20221026_160151.php';
require_once __DIR__ . '/TestMigrations/Migration_20221027_180501.php';;

class MigratorTest extends MailPoetTest {
  private const DATE_TIME_FORMAT = '%d%d-%d%d-%d%d %d%d:%d%d:%d%d';

  /** @var string */
  private $table;

  /** @var Store */
  private $store;

  public function _before() {
    parent::_before();
    $this->table = Env::$dbPrefix . 'testing_migrations';
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->table}");
    $this->store = $this->getServiceWithOverrides(Store::class, [
      'table' => $this->table,
    ]);
    $this->store->ensureMigrationsTable();
  }

  public function testItReturnsCorrectStatus(): void {
    $this->store->startMigration('Migration_20221024_080348');
    $this->store->completeMigration('Migration_20221024_080348');
    $this->store->startMigration('Migration_20221025_120345');
    $this->store->failMigration('Migration_20221025_120345', 'test-error');
    $this->store->startMigration('Migration_20221026_160151');
    $this->store->startMigration('Migration_20221028_200226');
    $this->store->completeMigration('Migration_20221028_200226');

    $migrator = $this->createMigrator();
    $status = $migrator->getStatus();

    // completed
    $data = $status[0];
    $this->assertSame('Migration_20221024_080348', $data['name']);
    $this->assertSame('completed', $data['status']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['completed_at']);
    $this->assertNull($data['error']);

    // failed
    $data = $status[1];
    $this->assertSame('Migration_20221025_120345', $data['name']);
    $this->assertSame('failed', $data['status']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['completed_at']);
    $this->assertSame($data['error'], 'test-error');

    // started
    $data = $status[2];
    $this->assertSame('Migration_20221026_160151', $data['name']);
    $this->assertSame('started', $data['status']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['started_at']);
    $this->assertNull($data['completed_at']);
    $this->assertNull($data['error']);

    // new
    $data = $status[3];
    $this->assertSame('Migration_20221027_180501', $data['name']);
    $this->assertSame('new', $data['status']);
    $this->assertNull($data['started_at']);
    $this->assertNull($data['completed_at']);
    $this->assertNull($data['error']);

    // unknown completed (unknown = stored in DB but missing in the file system)
    $data = $status[4];
    $this->assertSame('Migration_20221028_200226', $data['name']);
    $this->assertSame('completed', $data['status']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['completed_at']);
    $this->assertNull($data['error']);
  }

  public function testItReturnsEmptyStatusWhenNoMigrationsExist(): void {
    // no defined & no processed migrations
    $this->assertSame([], $this->createMigrator(__DIR__ . '/TestMigrationsEmpty')->getStatus());
  }

  public function testItReturnsStatusForUnprocessedMigrations(): void {
    $newMigrationFields = [
      'status' => 'new',
      'started_at' => null,
      'completed_at' => null,
      'error' => null,
    ];

    $migrator = $this->createMigrator();
    $this->assertSame([
      ['name' => 'Migration_20221024_080348'] + $newMigrationFields,
      ['name' => 'Migration_20221025_120345'] + $newMigrationFields,
      ['name' => 'Migration_20221026_160151'] + $newMigrationFields,
      ['name' => 'Migration_20221027_180501'] + $newMigrationFields,
    ], $migrator->getStatus());
  }

  /** @return Migrator */
  private function createMigrator(string $migrationsDir = __DIR__ . '/TestMigrations'): Migrator {
    $repository = $this->getServiceWithOverrides(Repository::class, [
      'migrationsDir' => $migrationsDir,
    ]);

    return $this->getServiceWithOverrides(Migrator::class, [
      'repository' => $repository,
      'store' => $this->store,
    ]);
  }

  public function _after() {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->table}");
  }
}
