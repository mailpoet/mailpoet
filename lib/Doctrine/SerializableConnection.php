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
  private $event_manager;

  function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $event_manager = null) {
    $this->params = $params;
    $this->driver = $driver;
    $this->config = $config;
    $this->event_manager = $event_manager;
    parent::__construct($params, $driver, $config, $event_manager);
  }

  function __sleep() {
    return ['params', 'driver', 'config', 'event_manager'];
  }

  function __wakeup() {
    parent::__construct($this->params, $this->driver, $this->config, $this->event_manager);
  }
}
