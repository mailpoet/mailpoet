<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Migrator\DbMigration;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20221024_080348 extends DbMigration {
  public function run(): void {
    echo 'Migration run called!';
    expect($this->connection)->isInstanceOf(Connection::class);
    expect($this->entityManager)->isInstanceOf(EntityManager::class);
    expect($this->container)->isInstanceOf(ContainerWrapper::class);
  }
}
