<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20260815_120000_Db.php';

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260815_120000_Db_Test extends \MailPoetTest {
  /** @var Migration_20260815_120000_Db */
  private $migration;

  /** @var string */
  private $subscribersTable;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260815_120000_Db($this->diContainer);
    $this->subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    // The migration has normally already run against the test database, so
    // undo it to get a realistic "before" state.
    $connection = $this->entityManager->getConnection();
    if ($connection->fetchAllAssociative("SHOW INDEX FROM `{$this->subscribersTable}` WHERE Key_name = 'tracking_consent'")) {
      $connection->executeStatement("ALTER TABLE `{$this->subscribersTable}` DROP INDEX `tracking_consent`");
    }
  }

  public function testItAddsTheIndex(): void {
    $this->migration->run();

    $index = $this->entityManager->getConnection()->fetchAllAssociative(
      "SHOW INDEX FROM `{$this->subscribersTable}` WHERE Key_name = 'tracking_consent'"
    );
    verify(count($index))->equals(1);
    verify($index[0]['Column_name'])->equals('tracking_consent');
  }

  public function testItCanRunTwice(): void {
    $this->migration->run();
    $this->migration->run();

    $index = $this->entityManager->getConnection()->fetchAllAssociative(
      "SHOW INDEX FROM `{$this->subscribersTable}` WHERE Key_name = 'tracking_consent'"
    );
    verify(count($index))->equals(1);
  }
}
