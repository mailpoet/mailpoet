<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\Events;

require_once __DIR__ . '/EventListenersBaseTest.php';
require_once __DIR__ . '/TimestampEntity.php';

class TimestampListenerTest extends EventListenersBaseTest {
  /** @var Carbon */
  private $now;

  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $tableName;

  /** @var TimestampListener */
  private $timestampListener;

  public function _before() {
    $timestamp = time();
    $this->now = Carbon::createFromTimestamp($timestamp);
    $this->wp = $this->make(WPFunctions::class, [
      'currentTime' => $timestamp,
    ]);
    $this->timestampListener = new TimestampListener($this->wp);
    $originalListener = $this->diContainer->get(TimestampListener::class);
    $this->replaceListeners($originalListener, $this->timestampListener);

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
    $entity = new TimestampEntity();
    $entity->setName('Created');

    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    expect($entity->getCreatedAt())->equals($this->now);
    expect($entity->getUpdatedAt())->equals($this->now);
  }

  public function testItSetsTimestampOnUpdate() {
    $this->connection->executeStatement("
      INSERT INTO $this->tableName (id, created_at, updated_at, name) VALUES (
        123,
        '2000-01-01 12:00:00',
        '2000-01-01 12:00:00',
        'Created'
      )
    ");

    $entity = $this->entityManager->find(TimestampEntity::class, 123);
    $this->assertInstanceOf(TimestampEntity::class, $entity); // PHPStan
    $entity->setName('Updated');
    $this->entityManager->flush();

    $createdAt = $entity->getCreatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
    expect($createdAt->format('Y-m-d H:i:s'))->equals('2000-01-01 12:00:00');
    expect($entity->getUpdatedAt())->equals($this->now);
  }

  public function testItUsesDifferentTimesWhenCreatingDifferentEntities() {
    $entity1 = new TimestampEntity();
    $entity1->setName('Entity 1');

    $this->entityManager->persist($entity1);
    $this->entityManager->flush();

    $createdAt1 = $entity1->getCreatedAt();
    $this->assertInstanceOf(Carbon::class, $createdAt1);
    $createdAt1->subMonth();

    $entity2 = new TimestampEntity();
    $entity2->setName('Entity 2');

    $this->entityManager->persist($entity2);
    $this->entityManager->flush();

    $this->assertEquals($this->now, $entity2->getCreatedAt());
  }

  public function _after() {
    parent::_after();
    $originalListener = $this->diContainer->get(TimestampListener::class);
    $this->replaceListeners($this->timestampListener, $originalListener);
    $this->connection->executeStatement("DROP TABLE IF EXISTS $this->tableName");
  }
}
