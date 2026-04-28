<?php declare(strict_types = 1);

namespace MailPoet\Test\Migrations\Db;

use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Migrations\Db\Migration_20260428_120000;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260428_120000_Test extends \MailPoetTest {
  private const COLUMNS = [
    'reason_submitted_at',
    'reason_text',
    'reason',
  ];

  /** @var Migration_20260428_120000 */
  private $migration;

  /** @var string */
  private $tableName;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260428_120000($this->diContainer);
    $this->tableName = $this->entityManager->getClassMetadata(StatisticsUnsubscribeEntity::class)->getTableName();

    foreach (self::COLUMNS as $column) {
      if ($this->columnExists($column)) {
        $this->entityManager->getConnection()->executeStatement("ALTER TABLE `{$this->tableName}` DROP COLUMN `{$column}`");
      }
    }
  }

  public function testItAddsUnsubscribeReasonColumns(): void {
    $this->migration->run();

    verify($this->columnExists('reason'))->true();
    verify($this->columnExists('reason_text'))->true();
    verify($this->columnExists('reason_submitted_at'))->true();
  }

  private function columnExists(string $column): bool {
    if (!in_array($column, self::COLUMNS, true)) {
      throw new \InvalidArgumentException('Unsupported column name.');
    }

    return (bool)$this->entityManager->getConnection()
      ->fetchAssociative("SHOW COLUMNS FROM `{$this->tableName}` LIKE '{$column}'");
  }
}
