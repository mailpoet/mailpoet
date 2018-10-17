<?php

namespace MailPoet\DI;

use MailPoet\Dependencies\Symfony\Component\Config\FileLocator;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerFactory {

  /** @var ContainerBuilder */
  private static $container;

  static function getContainer() {
    if (!self::$container) {
      self::createContainer();
    }
    return self::$container;
  }

  private static function createContainer() {
    self::$container = new ContainerBuilder();
    $loader = new YamlFileLoader(self::$container, new FileLocator(__DIR__));
    $loader->load('services.yml');
  }
}
