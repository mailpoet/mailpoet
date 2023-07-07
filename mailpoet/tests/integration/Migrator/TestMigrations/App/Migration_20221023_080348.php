<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Migrator\AppMigration;
use MailPoetVendor\Doctrine\ORM\EntityManager;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20221023_080348 extends AppMigration {
  public function run(): void {
    echo 'Migration run called!';
    expect($this->entityManager)->isInstanceOf(EntityManager::class);
    expect($this->container)->isInstanceOf(ContainerWrapper::class);
  }
}
