<?php

namespace MailPoet\DI;

use MailPoet\Dependencies\Symfony\Component\DependencyInjection\ContainerBuilder;

class ContainerFactory {

  /** @var ContainerBuilder */
  private static $container;

  static function getContainer() {
    if(!self::$container) {
      self::createContainer();
    }
    return self::$container;
  }

  private static function createContainer() {
    self::$container = new ContainerBuilder();

    // Parameters
    self::$container->setParameter('mailpoet.plugin_data', [
      'version' => '1.0.0',
      'file' => ''
    ]);

    // Services
    self::$container
      ->register('initializer', \MailPoet\Config\Initializer::class)
      ->addArgument(self::$container)
      ->addArgument('%mailpoet.plugin_data%');
  }
}
