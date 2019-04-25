<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\EntityManager;

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
    return EntityManager::create($this->connection, $this->configuration);
  }
}
