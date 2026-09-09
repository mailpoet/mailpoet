<?php declare(strict_types = 1);

namespace MailPoet\Test\Migrations\Db;

use MailPoet\Entities\LogEntity;
use MailPoet\Migrations\Db\Migration_20260907_120000_Db;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260907_120000_Db_Test extends \MailPoetTest {
  private const INDEX_NAME = 'idx_log_name_created_at';
  private const PREEXISTING_INDEX_NAME = 'test_preexisting_name_index';

  /** @var Migration_20260907_120000_Db */
  private $migration;

  /** @var string */
  private $tableName;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260907_120000_Db($this->diContainer);
    $this->tableName = $this->entityManager->getClassMetadata(LogEntity::class)->getTableName();
    $this->dropIndex(self::INDEX_NAME);
    $this->dropIndex(self::PREEXISTING_INDEX_NAME);
  }

  public function _after() {
    // The suite's shared schema has the migration's index in place; put it back
    // so the tests that drop or skip it leave the database as they found it.
    $this->dropIndex(self::PREEXISTING_INDEX_NAME);
    $this->migration->run();
    parent::_after();
  }

  public function testItCreatesPrefixedIndexOnNameAndCreatedAt(): void {
    $this->migration->run();

    verify($this->getIndexColumns(self::INDEX_NAME))->equals(['name(191)', 'created_at']);
  }

  public function testItCanBeRerunSafely(): void {
    $this->migration->run();
    $this->migration->run();

    verify($this->getIndexColumns(self::INDEX_NAME))->equals(['name(191)', 'created_at']);
  }

  public function testItSkipsWhenAnotherIndexAlreadyStartsWithName(): void {
    $this->entityManager->getConnection()->executeStatement(
      "CREATE INDEX `" . self::PREEXISTING_INDEX_NAME . "` ON `{$this->tableName}` (`name`(191))"
    );

    $this->migration->run();

    verify($this->getIndexColumns(self::INDEX_NAME))->equals([]);
    verify($this->getIndexColumns(self::PREEXISTING_INDEX_NAME))->equals(['name(191)']);
  }

  /**
   * Index columns in order, with the prefix length appended for prefixed columns, e.g. "name(191)".
   *
   * @return string[]
   */
  private function getIndexColumns(string $index): array {
    $columns = $this->entityManager->getConnection()->fetchFirstColumn(
      "SELECT IF(sub_part IS NULL, column_name, CONCAT(column_name, '(', sub_part, ')'))
       FROM information_schema.statistics
       WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index
       ORDER BY seq_in_index",
      ['table' => $this->tableName, 'index' => $index]
    );
    return array_values(array_filter($columns, 'is_string'));
  }

  private function dropIndex(string $index): void {
    if ($this->getIndexColumns($index) === []) {
      return;
    }
    $this->entityManager->getConnection()->executeStatement("ALTER TABLE `{$this->tableName}` DROP INDEX `{$index}`");
  }
}
