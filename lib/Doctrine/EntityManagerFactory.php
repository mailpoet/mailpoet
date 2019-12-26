<?php

namespace MailPoet\Doctrine;

use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\Tracy\DoctrinePanel\DoctrinePanel;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Events;
use Tracy\Debugger;

class EntityManagerFactory {

  /** @var Connection */
  private $connection;

  /** @var Configuration */
  private $configuration;

  /** @var TimestampListener */
  private $timestamp_listener;

  /** @var ValidationListener */
  private $validation_listener;

  public function __construct(
    Connection $connection,
    Configuration $configuration,
    TimestampListener $timestamp_listener,
    ValidationListener $validation_listener
  ) {
    $this->connection = $connection;
    $this->configuration = $configuration;
    $this->timestamp_listener = $timestamp_listener;
    $this->validation_listener = $validation_listener;
  }

  public function createEntityManager() {
    $entity_manager = EntityManager::create($this->connection, $this->configuration);
    $this->setupListeners($entity_manager);
    if (class_exists(Debugger::class)) {
      DoctrinePanel::init($entity_manager);
    }
    return $entity_manager;
  }

  private function setupListeners(EntityManager $entity_manager) {
    $entity_manager->getEventManager()->addEventListener(
      [Events::prePersist, Events::preUpdate],
      $this->timestamp_listener
    );

    $entity_manager->getEventManager()->addEventListener(
      [Events::onFlush],
      $this->validation_listener
    );
  }
}
