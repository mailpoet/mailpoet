<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\Segment;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20260514_120000_Db.php';

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260514_120000_Db_Test extends \MailPoetTest {
  /** @var Migration_20260514_120000_Db */
  private $migration;

  /** @var SegmentEntity */
  private $segment;

  /** @var string */
  private $segmentsTable;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260514_120000_Db($this->diContainer);
    $this->segmentsTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $this->segment = (new Segment())
      ->withDescription('Existing description')
      ->withPublicDescription('This is removed before migration')
      ->create();

    $this->entityManager->getConnection()->executeStatement("
      ALTER TABLE `{$this->segmentsTable}`
      DROP COLUMN `public_description`
    ");
  }

  public function testItAddsEmptyPublicDescriptionWithoutCopyingDescription(): void {
    $this->migration->run();

    $publicDescription = $this->entityManager->getConnection()->fetchOne(
      "SELECT public_description FROM `{$this->segmentsTable}` WHERE id = ?",
      [$this->segment->getId()]
    );
    verify($publicDescription)->equals('');
  }

  public function testItAddsANonNullableColumn(): void {
    $this->migration->run();

    $column = $this->entityManager->getConnection()->fetchAssociative(
      "SHOW COLUMNS FROM `{$this->segmentsTable}` LIKE 'public_description'"
    );
    $this->assertIsArray($column);
    verify($column['Null'])->equals('NO');
    verify($column['Type'])->stringContainsString('text');
  }

  public function testItRepairsExistingNullableColumnWithNullValues(): void {
    $this->entityManager->getConnection()->executeStatement("
      ALTER TABLE `{$this->segmentsTable}`
      ADD COLUMN `public_description` text NULL
    ");

    $this->migration->run();

    $publicDescription = $this->entityManager->getConnection()->fetchOne(
      "SELECT public_description FROM `{$this->segmentsTable}` WHERE id = ?",
      [$this->segment->getId()]
    );
    verify($publicDescription)->equals('');

    $column = $this->entityManager->getConnection()->fetchAssociative(
      "SHOW COLUMNS FROM `{$this->segmentsTable}` LIKE 'public_description'"
    );
    $this->assertIsArray($column);
    verify($column['Null'])->equals('NO');
  }
}
