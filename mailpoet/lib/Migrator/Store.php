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
