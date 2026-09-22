<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsNewsletterEntity;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20260922_082011_Db.php';

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260922_082011_Db_Test extends \MailPoetTest {
  private const NEW_INDEX = 'subscriber_id_sent_at_newsletter_id';

  /** @var Migration_20260922_082011_Db */
  private $migration;

  /** @var string */
  private $table;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260922_082011_Db($this->diContainer);
    $this->table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $this->restoreOldIndex();
  }

  public function _after() {
    $this->migration->run();
    parent::_after();
  }

  public function testItReplacesTheSubscriberIndexWithTheWiderOne(): void {
    $this->migration->run();

    verify($this->getIndexColumns(self::NEW_INDEX))->equals(['subscriber_id', 'sent_at', 'newsletter_id']);
    verify($this->getIndexColumns('subscriber_id'))->equals([]);
  }

  public function testItAddsTheIndexWhenTheOldOneIsMissing(): void {
    $this->entityManager->getConnection()->executeStatement("ALTER TABLE `{$this->table}` DROP INDEX `subscriber_id`");

    $this->migration->run();

    verify($this->getIndexColumns(self::NEW_INDEX))->equals(['subscriber_id', 'sent_at', 'newsletter_id']);
  }

  public function testItCanRunTwice(): void {
    $this->migration->run();
    $this->migration->run();

    verify($this->getIndexColumns(self::NEW_INDEX))->equals(['subscriber_id', 'sent_at', 'newsletter_id']);
  }

  /** @return string[] */
  private function getIndexColumns(string $indexName): array {
    $columns = $this->entityManager->getConnection()->fetchFirstColumn(
      "SELECT column_name FROM information_schema.statistics
       WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
       ORDER BY seq_in_index",
      [$this->table, $indexName]
    );
    return array_values(array_filter($columns, 'is_string'));
  }

  private function restoreOldIndex(): void {
    $connection = $this->entityManager->getConnection();
    if ($this->getIndexColumns(self::NEW_INDEX)) {
      $connection->executeStatement("ALTER TABLE `{$this->table}` DROP INDEX `" . self::NEW_INDEX . "`");
    }
    if (!$this->getIndexColumns('subscriber_id')) {
      $connection->executeStatement("ALTER TABLE `{$this->table}` ADD INDEX `subscriber_id` (`subscriber_id`)");
    }
  }
}
