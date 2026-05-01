<?php declare(strict_types = 1);

namespace MailPoet\Test\Migrations\Db;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20260430_120000.php';

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrations\Db\Migration_20260430_120000;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260430_120000_Test extends \MailPoetTest {
  private const COLUMNS = [
    'time_zone_updated_at',
    'time_zone_confidence',
    'time_zone_source',
    'time_zone',
  ];

  /** @var Migration_20260430_120000 */
  private $migration;

  /** @var string */
  private $tableName;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260430_120000($this->diContainer);
    $this->tableName = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    foreach (self::COLUMNS as $column) {
      if ($this->columnExists($column)) {
        $this->entityManager->getConnection()->executeStatement("ALTER TABLE `{$this->tableName}` DROP COLUMN `{$column}`");
      }
    }
  }

  public function testItAddsSubscriberTimeZoneColumns(): void {
    $this->migration->run();

    foreach (self::COLUMNS as $column) {
      verify($this->columnExists($column))->true();
    }
  }

  public function testItCanBeRerunSafely(): void {
    $this->migration->run();
    $this->migration->run();

    foreach (self::COLUMNS as $column) {
      verify($this->columnExists($column))->true();
    }
  }

  private function columnExists(string $column): bool {
    if (!in_array($column, self::COLUMNS, true)) {
      throw new \InvalidArgumentException('Unsupported column name.');
    }

    return (bool)$this->entityManager->getConnection()
      ->fetchAssociative("SHOW COLUMNS FROM `{$this->tableName}` LIKE '{$column}'");
  }
}
