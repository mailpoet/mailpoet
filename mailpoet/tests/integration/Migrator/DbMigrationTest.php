<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoetTest;

class DbMigrationTest extends MailPoetTest {
  private $migration;
  /** @var string */
  private $tableName = 'test_users';

  public function _before() {
    parent::_before();
    $container = ContainerWrapper::getInstance();

    // Subclass with public accessors for testing
    $this->migration = new class($container) extends DbMigration {
      public function run(): void {
      }

      public function publicCreateTable(string $tableName, array $attributes): void {
        $this->createTable($tableName, $attributes);
      }

      public function publicTableExists(string $tableName): bool {
        return $this->tableExists($tableName);
      }

      public function publicColumnExists(string $tableName, string $columnName): bool {
        return $this->columnExists($tableName, $columnName);
      }

      public function publicIndexExists(string $tableName, string $indexName): bool {
        return $this->indexExists($tableName, $indexName);
      }
    };
  }

  public function _after(): void {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS {$this->tableName};");
  }

  public function testCreateTable(): void {
    $attributes = [
      'id INT PRIMARY KEY AUTO_INCREMENT',
      'name VARCHAR(255) NOT NULL',
    ];
    $this->migration->publicCreateTable($this->tableName, $attributes);

    // Verify that the table was created by querying the database
    $tableExists = $this->connection->executeQuery("SHOW TABLES LIKE ?", [Env::$dbPrefix . $this->tableName])->rowCount() > 0;

    // Assert that the table exists
    $this->assertTrue($tableExists, "The table '$this->tableName' was not created.");
  }

  public function testTableExists(): void {
    $attributes = [
      'id INT PRIMARY KEY AUTO_INCREMENT',
      'name VARCHAR(255) NOT NULL',
    ];
    $this->migration->publicCreateTable($this->tableName, $attributes);

    $this->assertTrue($this->migration->publicTableExists(Env::$dbPrefix . $this->tableName));
    $this->assertFalse($this->migration->publicTableExists('non_existent_table'));
  }

  public function testColumnExists(): void {
    $attributes = [
      'id INT PRIMARY KEY AUTO_INCREMENT',
      'name VARCHAR(255) NOT NULL',
    ];
    $this->migration->publicCreateTable($this->tableName, $attributes);

    $this->assertTrue($this->migration->publicColumnExists(Env::$dbPrefix . $this->tableName, 'name'));
    $this->assertFalse($this->migration->publicColumnExists(Env::$dbPrefix . $this->tableName, 'non_existent_column'));
  }

  public function testIndexExists(): void {
    $attributes = [
      'id INT PRIMARY KEY AUTO_INCREMENT',
      'name VARCHAR(255) NOT NULL',
    ];
    $this->migration->publicCreateTable($this->tableName, $attributes);

    // Add an index for testing
    $this->connection->executeStatement("CREATE INDEX test_index ON " . Env::$dbPrefix . $this->tableName . " (name);");

    $this->assertTrue($this->migration->publicIndexExists(Env::$dbPrefix . $this->tableName, 'test_index'));
    $this->assertFalse($this->migration->publicIndexExists(Env::$dbPrefix . $this->tableName, 'non_existent_index'));
  }
}
