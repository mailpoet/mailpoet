<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsNewsletterEntity;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20260914_143939_Db.php';

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260914_143939_Db_Test extends \MailPoetTest {
  /** @var Migration_20260914_143939_Db */
  private $migration;

  /** @var string */
  private $table;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260914_143939_Db($this->diContainer);
    $this->table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $this->dropIndexIfExists();
    $this->dropColumnIfExists();
  }

  public function _after() {
    $this->migration->run();
    parent::_after();
  }

  public function testItAddsTheColumnDefaultingToTracked(): void {
    $this->migration->run();

    $column = $this->entityManager->getConnection()->fetchAssociative(
      "SHOW COLUMNS FROM `{$this->table}` LIKE 'sent_with_tracking'"
    );
    $this->assertIsArray($column);
    verify($column['Type'])->stringContainsString('tinyint(1)');
    verify($column['Null'])->equals('NO');
    verify($column['Default'])->equals('1');
  }

  public function testItAddsTheIndex(): void {
    $this->migration->run();

    verify($this->getIndexColumns())->equals(['newsletter_id', 'sent_with_tracking', 'queue_id']);
  }

  public function testItAddsTheIndexWhenTheColumnAlreadyExists(): void {
    $this->entityManager->getConnection()->executeStatement(
      "ALTER TABLE `{$this->table}` ADD COLUMN `sent_with_tracking` tinyint(1) NOT NULL DEFAULT 1"
    );

    $this->migration->run();

    verify($this->getIndexColumns())->equals(['newsletter_id', 'sent_with_tracking', 'queue_id']);
  }

  public function testItCanRunTwice(): void {
    $this->migration->run();
    $this->migration->run();

    verify($this->getIndexColumns())->equals(['newsletter_id', 'sent_with_tracking', 'queue_id']);
  }

  /** @return string[] */
  private function getIndexColumns(): array {
    $columns = $this->entityManager->getConnection()->fetchFirstColumn(
      "SELECT column_name FROM information_schema.statistics
       WHERE table_schema = DATABASE() AND table_name = ? AND index_name = 'newsletter_id_sent_with_tracking'
       ORDER BY seq_in_index",
      [$this->table]
    );
    return array_values(array_filter($columns, 'is_string'));
  }

  private function dropIndexIfExists(): void {
    $connection = $this->entityManager->getConnection();
    if ($connection->fetchAllAssociative("SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'newsletter_id_sent_with_tracking'")) {
      $connection->executeStatement("ALTER TABLE `{$this->table}` DROP INDEX `newsletter_id_sent_with_tracking`");
    }
  }

  private function dropColumnIfExists(): void {
    $connection = $this->entityManager->getConnection();
    if ($connection->fetchAssociative("SHOW COLUMNS FROM `{$this->table}` LIKE 'sent_with_tracking'")) {
      $connection->executeStatement("ALTER TABLE `{$this->table}` DROP COLUMN `sent_with_tracking`");
    }
  }
}
