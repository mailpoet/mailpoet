<?php

namespace MailPoet\DI;

use MailPoet\Dependencies\Symfony\Component\Config\FileLocator;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerFactory {

  /** @var ContainerBuilder */
  private static $container;

  static function getContainer() {
    if(!self::$container) {
      self::createContainer();
    }
    return self::$container;
  }

  static function createContainer() {
    self::$container = new ContainerBuilder();
    self::$container->autowire(\MailPoet\Config\AccessControl::class);
    self::$container->autowire(\MailPoet\Cron\Daemon::class);
    self::$container->autowire(\MailPoet\Cron\DaemonHttpRunner::class);
    self::$container->autowire(\MailPoet\Router\Endpoints\CronDaemon::class);
    self::$container->autowire(\MailPoet\Router\Endpoints\Subscription::class);
    self::$container->autowire(\MailPoet\Router\Endpoints\Track::class);
    self::$container->autowire(\MailPoet\Router\Endpoints\ViewInBrowser::class);
    return self::$container;
  }
}
