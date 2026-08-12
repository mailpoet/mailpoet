<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsNewsletterEntity;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20260812_120000_Db.php';

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260812_120000_Db_Test extends \MailPoetTest {
  /** @var Migration_20260812_120000_Db */
  private $migration;

  /** @var string */
  private $statisticsTable;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260812_120000_Db($this->diContainer);
    $this->statisticsTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();

    // The migration has normally already run against the test database, so
    // undo it to get a realistic "before" state.
    $connection = $this->entityManager->getConnection();
    if ($connection->fetchAllAssociative("SHOW INDEX FROM `{$this->statisticsTable}` WHERE Key_name = 'newsletter_id_tracking_allowed'")) {
      $connection->executeStatement("ALTER TABLE `{$this->statisticsTable}` DROP INDEX `newsletter_id_tracking_allowed`");
    }
    if ($connection->fetchAllAssociative("SHOW COLUMNS FROM `{$this->statisticsTable}` LIKE 'tracking_allowed'")) {
      $connection->executeStatement("ALTER TABLE `{$this->statisticsTable}` DROP COLUMN `tracking_allowed`");
    }
  }

  public function testItAddsTheColumnDefaultingExistingRowsToTracked(): void {
    $connection = $this->entityManager->getConnection();
    $connection->executeStatement(
      "INSERT INTO `{$this->statisticsTable}` (newsletter_id, subscriber_id, queue_id, sent_at) VALUES (1, 1, 1, NOW())"
    );
    $rowId = (int)$connection->lastInsertId();

    $this->migration->run();

    // Everything sent before per-subscriber opt-out existed was tracked, so
    // pre-existing rows must land on 1 — not 0, which would zero every
    // historical campaign's coverage.
    $trackingAllowed = $connection->fetchOne(
      "SELECT tracking_allowed FROM `{$this->statisticsTable}` WHERE id = ?",
      [$rowId]
    );
    verify((int)$trackingAllowed)->equals(1);
  }

  public function testItAddsANotNullColumnDefaultingToOne(): void {
    $this->migration->run();

    $column = $this->entityManager->getConnection()->fetchAssociative(
      "SHOW COLUMNS FROM `{$this->statisticsTable}` LIKE 'tracking_allowed'"
    );
    $this->assertIsArray($column);
    verify($column['Null'])->equals('NO');
    verify($column['Default'])->equals('1');
    verify($column['Type'])->stringContainsString('tinyint(1)');
  }

  public function testItAddsTheCoverageIndex(): void {
    $this->migration->run();

    $index = $this->entityManager->getConnection()->fetchAllAssociative(
      "SHOW INDEX FROM `{$this->statisticsTable}` WHERE Key_name = 'newsletter_id_tracking_allowed'"
    );
    verify(count($index))->equals(2);
    verify($index[0]['Column_name'])->equals('newsletter_id');
    verify($index[1]['Column_name'])->equals('tracking_allowed');
  }

  public function testItCanRunTwice(): void {
    $this->migration->run();
    $this->migration->run();

    $columns = $this->entityManager->getConnection()->fetchAllAssociative(
      "SHOW COLUMNS FROM `{$this->statisticsTable}` LIKE 'tracking_allowed'"
    );
    verify(count($columns))->equals(1);

    $index = $this->entityManager->getConnection()->fetchAllAssociative(
      "SHOW INDEX FROM `{$this->statisticsTable}` WHERE Key_name = 'newsletter_id_tracking_allowed'"
    );
    verify(count($index))->equals(2);
  }
}
