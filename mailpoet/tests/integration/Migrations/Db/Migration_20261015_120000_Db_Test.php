<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20261015_120000_Db.php';

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20261015_120000_Db_Test extends \MailPoetTest {
  private const SCORE_INDEX = 'deleted_at_engagement_score';
  private const SUBSCRIBED_INDEX = 'deleted_at_last_subscribed';

  /** @var Migration_20261015_120000_Db */
  private $migration;

  /** @var string */
  private $table;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20261015_120000_Db($this->diContainer);
    $this->table = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->dropIndexes();
  }

  public function _after() {
    $this->migration->run();
    parent::_after();
  }

  public function testItAddsBothIndexes(): void {
    $this->migration->run();

    verify($this->getIndexColumns(self::SCORE_INDEX))->equals(['deleted_at', 'engagement_score']);
    verify($this->getIndexColumns(self::SUBSCRIBED_INDEX))->equals(['deleted_at', 'last_subscribed_at']);
  }

  public function testItAddsOnlyTheMissingIndex(): void {
    $this->entityManager->getConnection()->executeStatement(
      "ALTER TABLE `{$this->table}` ADD INDEX `" . self::SCORE_INDEX . "` (`deleted_at`, `engagement_score`)"
    );

    $this->migration->run();

    verify($this->getIndexColumns(self::SCORE_INDEX))->equals(['deleted_at', 'engagement_score']);
    verify($this->getIndexColumns(self::SUBSCRIBED_INDEX))->equals(['deleted_at', 'last_subscribed_at']);
  }

  public function testItCanRunTwice(): void {
    $this->migration->run();
    $this->migration->run();

    verify($this->getIndexColumns(self::SCORE_INDEX))->equals(['deleted_at', 'engagement_score']);
    verify($this->getIndexColumns(self::SUBSCRIBED_INDEX))->equals(['deleted_at', 'last_subscribed_at']);
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

  private function dropIndexes(): void {
    $connection = $this->entityManager->getConnection();
    foreach ([self::SCORE_INDEX, self::SUBSCRIBED_INDEX] as $index) {
      if ($this->getIndexColumns($index)) {
        $connection->executeStatement("ALTER TABLE `{$this->table}` DROP INDEX `{$index}`");
      }
    }
  }
}
