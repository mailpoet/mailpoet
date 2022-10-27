<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoetVendor\Doctrine\DBAL\Connection;

class Store {
  /** @var Connection */
  private $connection;

  /** @var string */
  private $table;

  public function __construct(
    Connection $connection
  ) {
    $this->connection = $connection;
    $this->table = Env::$dbPrefix . 'migrations';
  }

  public function getMigrationsTable(): string {
    return $this->table;
  }

  public function startMigration(string $name): void {
    $this->connection->executeStatement("
      INSERT INTO {$this->table} (name, started_at)
      VALUES (?, now())
    ", [$name]);
  }

  public function completeMigration(string $name): void {
    $this->connection->executeStatement("
      UPDATE {$this->table}
      SET completed_at = current_timestamp()
      WHERE name = ?
    ", [$name]);
  }

  public function failMigration(string $name, string $error): void {
    $this->connection->executeStatement("
      UPDATE {$this->table}
      SET
        completed_at = current_timestamp(),
        error = ?
      WHERE name = ?
    ", [$error ?: 'Unknown error', $name]);
  }

  public function getAll(): array {
    return $this->connection->fetchAllAssociative("
      SELECT *
      FROM {$this->table}
      ORDER BY id ASC
    ");
  }

  public function ensureMigrationsTable(): void {
    $collate = Env::$dbCharsetCollate;
    $this->connection->executeStatement("
      CREATE TABLE IF NOT EXISTS {$this->table} (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        started_at timestamp NOT NULL,
        completed_at timestamp NULL,
        error text NULL,
        PRIMARY KEY (id),
        UNIQUE KEY (name)
      ) Engine=InnoDB {$collate};
    ");
  }
}
