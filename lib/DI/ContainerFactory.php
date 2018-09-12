<?php

namespace MailPoet\DI;

use MailPoet\API\JSON\API as JsonApi;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Activator;
use MailPoet\Config\Capabilities;
use MailPoet\Config\Changelog;
use MailPoet\Config\Database;
use MailPoet\Config\DeactivationSurvey;
use MailPoet\Config\Env;
use MailPoet\Config\Hooks as ConfigHooks;
use MailPoet\Config\Localizer;
use MailPoet\Config\Menu;
use MailPoet\Config\PersonalDataErasers;
use MailPoet\Config\PersonalDataExporters;
use MailPoet\Config\PHPVersionWarnings;
use MailPoet\Config\PrivacyPolicy;
use MailPoet\Config\Renderer;
use MailPoet\Config\RequirementsChecker;
use MailPoet\Config\Shortcodes;
use MailPoet\Cron\CronTrigger;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoet\Router\Router;
use MailPoet\Settings\Pages;
use MailPoet\Util\ConflictResolver;

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

    self::$container->setParameter('mailpoet.assets_url', Env::$assets_url);
    self::$container->setParameter('mailpoet.caching_enabled', !WP_DEBUG);
    self::$container->setParameter('mailpoet.debugging_enabled', WP_DEBUG);

    // Services
    self::$container
      ->register('initializer', \MailPoet\Config\Initializer::class)
      ->addArgument(self::$container)
      ->addArgument('%mailpoet.plugin_data%');

    self::$container
      ->register(Renderer::class)
      ->addArgument('%mailpoet.caching_enabled%')
      ->addArgument('%mailpoet.debugging_enabled%');

    self::$container->autowire(JsonApi::class);
    self::$container->autowire(AccessControl::class);
    self::$container->autowire(Capabilities::class);
    self::$container->autowire(Localizer::class);
    self::$container->autowire(RequirementsChecker::class);
    self::$container->autowire(Activator::class);
    self::$container->autowire(Database::class);
    self::$container->autowire(Shortcodes::class);
    self::$container->autowire(Router::class);
    self::$container->autowire(Changelog::class);
    self::$container->autowire(CronTrigger::class);
    self::$container->autowire(ConflictResolver::class);
    self::$container->autowire(DeactivationSurvey::class);
    self::$container->autowire(Pages::class);
    self::$container->autowire(ConfigHooks::class);
    self::$container->autowire(PrivacyPolicy::class);
    self::$container->autowire(PersonalDataExporters::class);
    self::$container->autowire(PersonalDataErasers::class);
    self::$container->autowire(PHPVersionWarnings::class);
    self::$container->autowire(Menu::class)->setArgument('$assets_url', '%mailpoet.assets_url%');
  }
}
