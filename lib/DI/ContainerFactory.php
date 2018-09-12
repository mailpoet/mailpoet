<?php

namespace MailPoet\DI;

use MailPoet\Dependencies\Symfony\Component\DependencyInjection\ContainerBuilder;

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
  }
}
