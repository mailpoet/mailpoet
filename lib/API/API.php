<?php

namespace MailPoet\API;

use MailPoetVendor\Symfony\Component\DependencyInjection\Container;
use MailPoetVendor\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use MailPoet\DI\ContainerFactory;

if(!defined('ABSPATH')) exit;

class API {

  /** @var Container */
  private static $container;

  static function injectContainer(Container $container) {
    self::$container = $container;
  }

  static function JSON() {
    return self::$container->get(JSON\API::class);
  }

  static function MP($version) {
    self::ensureContainerIsLoaded();
    $api_class = sprintf('%s\MP\%s\API', __NAMESPACE__, $version);
    try {
      return self::$container->get($api_class);
    } catch (ServiceNotFoundException $e) {
      throw new \Exception(__('Invalid API version.', 'mailpoet'));
    }
  }

  /**
   * MP API is used by third party plugins so we have to ensure that container is loaded
   * @see https://kb.mailpoet.com/article/195-add-subscribers-through-your-own-form-or-plugin
   */
  private static function ensureContainerIsLoaded() {
    if(!self::$container) {
      $factory = new ContainerFactory();
      self::$container = $factory->getContainer();
    }
  }
}
