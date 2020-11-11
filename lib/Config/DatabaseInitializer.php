<?php

namespace MailPoet\Config;

use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Psr\Container\ContainerInterface;

class DatabaseInitializer {
  private $diContainer;

  public function __construct(ContainerInterface $diContainer) {
    $this->diContainer = $diContainer;
  }

  public function initializeConnection() {
    $connection = $this->diContainer->get(Connection::class);

    // pass the same PDO connection to legacy Database object
    $database = new Database();
    $database->init($connection->getWrappedConnection()->getConnection());
  }
}
