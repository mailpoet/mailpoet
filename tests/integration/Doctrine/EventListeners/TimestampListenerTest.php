<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use Carbon\Carbon;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/TimestampEntity.php';

class TimestampListenerTest extends \MailPoetTest {
  /** @var Carbon */
  private $now;

  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $table_name;

  function _before() {
    $timestamp = time();
    $this->now = Carbon::createFromTimestamp($timestamp);
    $this->wp = $this->make(WPFunctions::class, [
      'currentTime' => $timestamp,
    ]);

    $this->entity_manager = $this->createEntityManager();
    $this->table_name = $this->entity_manager->getClassMetadata(TimestampEntity::class)->getTableName();
    $this->connection->executeUpdate("DROP TABLE IF EXISTS $this->table_name");
    $this->connection->executeUpdate("
      CREATE TABLE $this->table_name (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        created_at timestamp NULL,
        updated_at timestamp NULL,
        name varchar(255) NOT NULL
      )
    ");
  }

  function testItSetsTimestampsOnCreate() {
    $entity = new TimestampEntity();
    $entity->setName('Created');

    $this->entity_manager->persist($entity);
    $this->entity_manager->flush();

    expect($entity->getCreatedAt())->equals($this->now);
    expect($entity->getUpdatedAt())->equals($this->now);
  }

  function testItSetsTimestampOnUpdate() {
    $this->connection->executeUpdate("
      INSERT INTO $this->table_name (id, created_at, updated_at, name) VALUES (
        123,
        '2000-01-01 12:00:00',
        '2000-01-01 12:00:00',
        'Created'
      )
    ");

    $entity = $this->entity_manager->find(TimestampEntity::class, 123);
    $entity->setName('Updated');
    $this->entity_manager->flush();

    expect($entity->getCreatedAt()->format('Y-m-d H:i:s'))->equals('2000-01-01 12:00:00');
    expect($entity->getUpdatedAt())->equals($this->now);
  }

  function _after() {
    parent::_after();
    $this->connection->executeUpdate("DROP TABLE IF EXISTS $this->table_name");
  }

  private function createEntityManager() {
    $configuration_factory = new ConfigurationFactory();
    $configuration = $configuration_factory->createConfiguration();

    $metadata_driver = $configuration->newDefaultAnnotationDriver([__DIR__], false);
    $configuration->setMetadataDriverImpl($metadata_driver);
    $configuration->setMetadataCacheImpl(new ArrayCache());

    $timestamp_listener = new TimestampListener($this->wp);
    $validation_listener = new ValidationListener();
    $entity_manager_factory = new EntityManagerFactory($this->connection, $configuration, $timestamp_listener, $validation_listener);
    return $entity_manager_factory->createEntityManager();
  }
}
