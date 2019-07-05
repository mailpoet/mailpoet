<?php

namespace MailPoet\Doctrine;

use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Events;

class EntityManagerFactory {

  /** @var Connection */
  private $connection;

  /** @var Configuration */
  private $configuration;

  function __construct(Connection $connection, Configuration $configuration) {
    $this->connection = $connection;
    $this->configuration = $configuration;
  }

  function createEntityManager() {
    $entity_manager = EntityManager::create($this->connection, $this->configuration);
    $this->setupTimestampListener($entity_manager);
    return $entity_manager;
  }

  private function setupTimestampListener(EntityManager $entity_manager) {
    $entity_manager->getEventManager()->addEventListener(
      [Events::prePersist, Events::preUpdate],
      new TimestampListener()
    );
  }
}
