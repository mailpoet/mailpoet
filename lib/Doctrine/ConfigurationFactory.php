<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class ConfigurationFactory {
  const ENTITY_DIR = __DIR__ . '/../Doctrine/Entities';

  /** @var bool */
  private $is_dev_mode;

  function __construct() {
    $this->is_dev_mode = WP_DEBUG;
  }

  function createConfiguration() {
    $configuration = new Configuration();
    $configuration->setNamingStrategy(new UnderscoreNamingStrategy());
    $configuration->setMetadataDriverImpl($configuration->newDefaultAnnotationDriver([self::ENTITY_DIR]));

    if (!$this->is_dev_mode) {
      $configuration->ensureProductionSettings();
    }
    return $configuration;
  }
}
