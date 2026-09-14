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

    verify($this->getIndexColumns())->equals(['newsletter_id', 'sent_with_tracking']);
  }

  public function testItAddsTheIndexWhenTheColumnAlreadyExists(): void {
    $this->entityManager->getConnection()->executeStatement(
      "ALTER TABLE `{$this->table}` ADD COLUMN `sent_with_tracking` tinyint(1) NOT NULL DEFAULT 1"
    );

    $this->migration->run();

    verify($this->getIndexColumns())->equals(['newsletter_id', 'sent_with_tracking']);
  }

  public function testItCanRunTwice(): void {
    $this->migration->run();
    $this->migration->run();

    verify($this->getIndexColumns())->equals(['newsletter_id', 'sent_with_tracking']);
  }

  /** @return string[] */
  private function getIndexColumns(): array {
    $rows = $this->entityManager->getConnection()->fetchAllAssociative(
      "SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'newsletter_id_sent_with_tracking'"
    );
    usort($rows, fn($a, $b) => (int)$a['Seq_in_index'] <=> (int)$b['Seq_in_index']);
    return array_column($rows, 'Column_name');
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
