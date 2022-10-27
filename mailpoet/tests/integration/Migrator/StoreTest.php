<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoetTest;

class StoreTest extends MailPoetTest {
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
  }

  public function testItCreatesMigrationsTable(): void {
    $result = $this->connection->executeQuery("SHOW TABLES LIKE '{$this->table}'")->fetchAllNumeric();
    $this->assertEmpty($result);

    // create migrations table
    $this->store->ensureMigrationsTable();
    $result = $this->connection->executeQuery("SHOW TABLES LIKE '{$this->table}'")->fetchAllNumeric();
    $this->assertNotEmpty($result);

    // do not fail if migrations table already exists
    $this->store->ensureMigrationsTable();
    $result = $this->connection->executeQuery("SHOW TABLES LIKE '{$this->table}'")->fetchAllNumeric();
    $this->assertNotEmpty($result);
  }

  public function testItStartsAMigration(): void {
    $this->store->ensureMigrationsTable();
    $this->store->startMigration('TestingMigration');

    $migrations = $this->connection->executeQuery("SELECT * FROM {$this->table}")->fetchAllAssociative();
    $this->assertCount(1, $migrations);

    $data = $migrations[0];
    $this->assertSame('TestingMigration', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, strval($data['started_at']));
    $this->assertNull($data['completed_at']);
    $this->assertNull($data['error']);
  }

  public function testItCompletesAMigration(): void {
    $this->store->ensureMigrationsTable();
    $this->store->startMigration('TestingMigration');
    $this->store->completeMigration('TestingMigration');

    $migrations = $this->connection->executeQuery("SELECT * FROM {$this->table}")->fetchAllAssociative();
    $this->assertCount(1, $migrations);

    $data = $migrations[0];
    $this->assertSame('TestingMigration', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, strval($data['started_at']));
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, strval($data['completed_at']));
    $this->assertNull($data['error']);
  }

  public function testItFailsAMigration(): void {
    $this->store->ensureMigrationsTable();
    $this->store->startMigration('TestingMigration');
    $this->store->failMigration('TestingMigration', 'test-error');

    $migrations = $this->connection->executeQuery("SELECT * FROM {$this->table}")->fetchAllAssociative();
    $this->assertCount(1, $migrations);

    $data = $migrations[0];
    $this->assertSame('TestingMigration', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, strval($data['started_at']));
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, strval($data['completed_at']));
    $this->assertSame($data['error'], 'test-error');
  }

  public function testItListsAllMigrations(): void {
    $this->store->ensureMigrationsTable();
    $this->store->startMigration('Started');
    $this->store->startMigration('Completed');
    $this->store->completeMigration('Completed');
    $this->store->startMigration('Failed');
    $this->store->failMigration('Failed', 'test-error');

    $migrations = $this->store->getAll();
    $this->assertCount(3, $migrations);

    $data = $migrations[0];
    $this->assertSame('Started', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertNull($data['completed_at']);
    $this->assertNull($data['error']);

    $data = $migrations[1];
    $this->assertSame('Completed', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
    $this->assertNull($data['error']);

    $data = $migrations[2];
    $this->assertSame('Failed', $data['name']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['started_at']);
    $this->assertStringMatchesFormat(self::DATE_TIME_FORMAT, $data['completed_at']);
    $this->assertSame($data['error'], 'test-error');
  }

  public function _after() {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->table}");
  }
}
