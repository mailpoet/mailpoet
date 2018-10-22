<?php
namespace MailPoet\DI;

use MailPoet\Dependencies\League\Container\Container;
use MailPoet\Dependencies\League\Container\ReflectionContainer;

class ContainerFactory {
  /** @var Container */
  private static $container;

  static function getContainer() {
    if(!self::$container) {
      self::$container = self::createContainer();
    }
    return self::$container;
  }

  private static function createContainer() {
    $container = new Container();

    // register the reflection container as a delegate to enable auto wiring
    $container->delegate(
      new ReflectionContainer()
    );
    return $container;
  }
}
