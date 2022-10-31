<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoetTest;

// testing migration files
require_once __DIR__ . '/TestMigrations/Migration_20221024_080348.php';
require_once __DIR__ . '/TestMigrations/Migration_20221025_120345.php';
require_once __DIR__ . '/TestMigrations/Migration_20221026_160151.php';
require_once __DIR__ . '/TestMigrations/Migration_20221027_180501.php';
require_once __DIR__ . '/TestMigrationsFail/Migration_20221023_040819.php';

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
    $this->assertSame(0, (int)$data['retries']);
    $this->assertNull($data['error']);

    // failed
    $data = $status[1];
    $this->assertSame('Migration_20221025_120345', $data['name']);
    $this->assertSame('failed', $data['status']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['completed_at']);
    $this->assertSame(0, (int)$data['retries']);
    $this->assertSame($data['error'], 'test-error');

    // started
    $data = $status[2];
    $this->assertSame('Migration_20221026_160151', $data['name']);
    $this->assertSame('started', $data['status']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['started_at']);
    $this->assertNull($data['completed_at']);
    $this->assertSame(0, (int)$data['retries']);
    $this->assertNull($data['error']);

    // new
    $data = $status[3];
    $this->assertSame('Migration_20221027_180501', $data['name']);
    $this->assertSame('new', $data['status']);
    $this->assertNull($data['started_at']);
    $this->assertNull($data['completed_at']);
    $this->assertNull($data['retries']);
    $this->assertNull($data['error']);

    // unknown completed (unknown = stored in DB but missing in the file system)
    $data = $status[4];
    $this->assertSame('Migration_20221028_200226', $data['name']);
    $this->assertSame('completed', $data['status']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, (string)$data['completed_at']);
    $this->assertSame(0, (int)$data['retries']);
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
      'retries' => null,
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

  public function testItRunsAllMigrations(): void {
    $migrator = $this->createMigrator();
    $this->assertEmpty($this->store->getAll());
    $migrator->run();

    $processed = $this->store->getAll();
    $this->assertCount(4, $processed);

    $migrations = [
      'Migration_20221024_080348',
      'Migration_20221025_120345',
      'Migration_20221026_160151',
      'Migration_20221027_180501',
    ];

    foreach ($processed as $i => $data) {
      $this->assertSame(strval($i + 1), $data['id']);
      $this->assertSame($migrations[$i], $data['name']);
      $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
      $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
      $this->assertSame(0, (int)$data['retries']);
      $this->assertNull($data['error']);
    }
  }

  public function testItRunsNewMigrations(): void {
    $this->store->startMigration('Migration_20221024_080348');
    $this->store->completeMigration('Migration_20221024_080348');
    $this->store->startMigration('Migration_20221026_160151');
    $this->store->completeMigration('Migration_20221026_160151');

    $this->assertCount(2, $this->store->getAll());
    $migrator = $this->createMigrator();
    $migrator->run();

    $processed = $this->store->getAll();
    $this->assertCount(4, $processed);

    $migrations = [
      'Migration_20221024_080348',
      'Migration_20221026_160151',
      'Migration_20221025_120345',
      'Migration_20221027_180501',
    ];

    foreach ($processed as $i => $data) {
      $this->assertSame($migrations[$i], $data['name']);
      $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
      $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
      $this->assertSame(0, (int)$data['retries']);
      $this->assertNull($data['error']);
    }
  }

  public function testItCallsLoggerWhenRunningMigrations(): void {
    $migrator = $this->createMigrator();

    $migrator->run($this->makeEmpty(Logger::class, [
      'logBefore' => $this->exactly(1),
      'logMigrationStarted' => $this->exactly(5),
      'logMigrationCompleted' => $this->exactly(4),
      'logAfter' => $this->exactly(1),
    ]));

    $processed = $this->store->getAll();
    $this->assertCount(4, $processed);
  }

  public function testItRetriesWhenRunningMigrationExists(): void {
    $this->store->startMigration('Migration_20221025_120345');

    $migrator = $this->createMigrator();
    $migrator->run();

    $processed = $this->store->getAll();
    $this->assertCount(4, $processed);

    $data = $processed[0];
    $this->assertSame('Migration_20221025_120345', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
    $this->assertSame(1, (int)$data['retries']);
    $this->assertNull($data['error']);
  }

  public function testItRetriesWhenFailedMigrationExists(): void {
    $this->store->startMigration('Migration_20221026_160151');
    $this->store->failMigration('Migration_20221026_160151', 'test-error');

    $migrator = $this->createMigrator();
    $migrator->run();

    $processed = $this->store->getAll();
    $this->assertCount(4, $processed);

    $data = $processed[0];
    $this->assertSame('Migration_20221026_160151', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
    $this->assertSame(1, (int)$data['retries']);
    $this->assertSame('test-error', $data['error']);
  }

  public function testItFailsBrokenMigration(): void {
    $this->expectException(MigratorException::class);
    $this->expectExceptionMessage('Migration "MailPoet\Migrations\Migration_20221023_040819" failed. Details: Testing failing migration.');
    $migrator = $this->createMigrator(__DIR__ . '/TestMigrationsFail');
    $migrator->run();

    $processed = $this->store->getAll();
    $this->assertCount(1, $processed);

    $data = $processed[0];
    $this->assertSame('Migration_20221023_040819', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
    $this->assertNull($data['retries']);
    $this->assertSame($data['error'], 'Testing failing migration.');
  }

  /** @return Migrator */
  private function createMigrator(string $migrationsDir = __DIR__ . '/TestMigrations'): Migrator {
    $repository = $this->getServiceWithOverrides(Repository::class, [
      'migrationsDir' => $migrationsDir,
    ]);

    $runner = $this->getServiceWithOverrides(Runner::class, [
      'store' => $this->store,
    ]);

    return $this->getServiceWithOverrides(Migrator::class, [
      'repository' => $repository,
      'runner' => $runner,
      'store' => $this->store,
    ]);
  }

  public function _after() {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->table}");
  }
}
