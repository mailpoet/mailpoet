<?php declare(strict_types = 1);

namespace MailPoet\Test\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrations\Db\Migration_20261002_093000_Db;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20261002_093000_Db_Test extends \MailPoetTest {
  /** @var string */
  private $tableName;

  public function _before() {
    parent::_before();
    $this->tableName = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    if ($this->columnExists()) {
      $this->entityManager->getConnection()->executeStatement("ALTER TABLE `{$this->tableName}` DROP COLUMN `source_url`");
    }
  }

  public function _after() {
    parent::_after();
    (new Migration_20261002_093000_Db($this->diContainer))->run();
  }

  public function testItAddsSourceUrlColumnAndCanBeRerun(): void {
    $migration = new Migration_20261002_093000_Db($this->diContainer);
    $migration->run();
    $migration->run();
    verify($this->columnExists())->true();
  }

  private function columnExists(): bool {
    return (bool)$this->entityManager->getConnection()
      ->fetchAssociative("SHOW COLUMNS FROM `{$this->tableName}` LIKE 'source_url'");
  }
}
