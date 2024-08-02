<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use DateTimeImmutable;

require_once __DIR__ . '/EventListenersBaseTest.php';
require_once __DIR__ . '/TimestampEntity.php';

class TimestampListenerTest extends EventListenersBaseTest {
  /** @var string */
  private $tableName;

  public function _before() {
    $this->tableName = $this->entityManager->getClassMetadata(TimestampEntity::class)->getTableName();
    $this->connection->executeStatement("DROP TABLE IF EXISTS $this->tableName");
    $this->connection->executeStatement("
      CREATE TABLE $this->tableName (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        created_at timestamp NULL,
        updated_at timestamp NULL,
        name varchar(255) NOT NULL
      )
    ");
  }

  public function testItSetsTimestampsOnCreate() {
    $now = new DateTimeImmutable();
    $entity = new TimestampEntity();
    $entity->setName('Created');

    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    $createdAt = $entity->getCreatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
    verify($createdAt->getTimestamp())->equalsWithDelta($now->getTimestamp(), 1);
    verify($entity->getUpdatedAt()->getTimestamp())->equalsWithDelta($now->getTimestamp(), 1);
  }

  public function testItSetsTimestampOnUpdate(): void {
    $this->connection->executeStatement("
      INSERT INTO $this->tableName (id, created_at, updated_at, name) VALUES (
        123,
        '2000-01-01 12:00:00',
        '2000-01-01 12:00:00',
        'Created'
      )
    ");
    $now = new DateTimeImmutable();
    $entity = $this->entityManager->find(TimestampEntity::class, 123);
    $this->assertInstanceOf(TimestampEntity::class, $entity); // PHPStan
    $entity->setName('Updated');
    $this->entityManager->flush();

    $createdAt = $entity->getCreatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
    verify($createdAt->format('Y-m-d H:i:s'))->equals('2000-01-01 12:00:00');
    verify($entity->getUpdatedAt()->getTimestamp())->equalsWithDelta($now->getTimestamp(), 1);
  }

  public function testItUsesDifferentTimesWhenCreatingDifferentEntities(): void {
    $entity1 = new TimestampEntity();
    $entity1->setName('Entity 1');

    $this->entityManager->persist($entity1);
    $this->entityManager->flush();

    $entity2 = new TimestampEntity();
    $entity2->setName('Entity 2');

    $this->entityManager->persist($entity2);
    $this->entityManager->flush();

    $this->assertNotSame($entity1->getCreatedAt(), $entity2->getCreatedAt());
  }

  public function testItStoresUTCTimestampEvenWithGmtOffset(): void {
    $originalOffset = get_option('gmt_offset');
    update_option('gmt_offset', -10);

    $entity2 = new TimestampEntity();
    $entity2->setName('Entity 2');

    $now = new DateTimeImmutable();
    $this->entityManager->persist($entity2);
    $this->entityManager->flush();

    $this->entityManager->refresh($entity2);
    $createdAt = $entity2->getCreatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
    verify($createdAt->getTimestamp())->equalsWithDelta($now->getTimestamp(), 1);
    update_option('gmt_offset', $originalOffset);
  }

  public function _after() {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS $this->tableName");
  }
}
