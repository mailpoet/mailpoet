<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\Common\EventManager;
use MailPoetVendor\Doctrine\DBAL\Configuration;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Driver;

class SerializableConnection extends Connection {
  private $params;
  private $driver;
  private $config;
  private $eventManager;

  public function __construct(
    array $params,
    Driver $driver,
    Configuration $config = null,
    EventManager $eventManager = null
  ) {
    $this->params = $params;
    $this->driver = $driver;
    $this->config = $config;
    $this->eventManager = $eventManager;
    parent::__construct($params, $driver, $config, $eventManager);
  }

  public function __sleep() {
    return ['params', 'driver', 'config', 'eventManager'];
  }

  public function __wakeup() {
    parent::__construct($this->params, $this->driver, $this->config, $this->eventManager);
  }
}
