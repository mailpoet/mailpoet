<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Migrator\DbMigration;
use MailPoetVendor\Doctrine\DBAL\Connection;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20221024_080348 extends DbMigration {
  public function run(): void {
    echo 'Migration run called!';
    verify($this->connection)->instanceOf(Connection::class);
  }
}
