<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoetTest;

class StoreTest extends MailPoetTest {
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

  public function _after() {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->table}");
  }
}
