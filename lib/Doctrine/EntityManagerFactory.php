<?php

namespace MailPoet\Doctrine;

use MailPoet\Doctrine\EventListeners\EmojiEncodingListener;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\Tracy\ApiPanel\ApiPanel;
use MailPoet\Tracy\DIPanel\DIPanel;
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
  private $timestampListener;

  /** @var ValidationListener */
  private $validationListener;

  /** @var EmojiEncodingListener */
  private $emojiEncodingListener;

  public function __construct(
    Connection $connection,
    Configuration $configuration,
    TimestampListener $timestampListener,
    ValidationListener $validationListener,
    EmojiEncodingListener $emojiEncodingListener
  ) {
    $this->connection = $connection;
    $this->configuration = $configuration;
    $this->timestampListener = $timestampListener;
    $this->validationListener = $validationListener;
    $this->emojiEncodingListener = $emojiEncodingListener;
  }

  public function createEntityManager(): EntityManager {
    $entityManager = EntityManager::create($this->connection, $this->configuration);
    $this->setupListeners($entityManager);
    if (
      class_exists(Debugger::class)
      && class_exists(DoctrinePanel::class)
    ) {
      DoctrinePanel::init($entityManager);
    }
    return $entityManager;
  }

  private function setupListeners(EntityManager $entityManager) {
    $entityManager->getEventManager()->addEventListener(
      [Events::prePersist, Events::preUpdate],
      $this->timestampListener
    );

    $entityManager->getEventManager()->addEventListener(
      [Events::onFlush],
      $this->validationListener
    );

    $entityManager->getEventManager()->addEventListener(
      [Events::prePersist, Events::preUpdate],
      $this->emojiEncodingListener
    );
  }
}
