<?php

namespace MailPoet\Config;

use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Psr\Container\ContainerInterface;

if (!defined('ABSPATH')) exit;

class DatabaseInitializer {
  private $di_container;

  function __construct(ContainerInterface $di_container) {
    $this->di_container = $di_container;
  }

  function initializeConnection() {
    $connection = $this->di_container->get(Connection::class);

    // pass the same PDO connection to legacy Database object
    $database = new Database();
    $database->init($connection->getWrappedConnection());
  }
}
