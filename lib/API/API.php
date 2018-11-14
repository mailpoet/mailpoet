<?php

namespace MailPoet\API;

use MailPoet\Dependencies\Symfony\Component\DependencyInjection\Container;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

if(!defined('ABSPATH')) exit;

class API {

  /** @var Container */
  private static $container;

  static function injectContainer(Container $container) {
    self::$container = $container;
  }

  static function JSON() {
    self::checkContainer();
    return self::$container->get(JSON\API::class);
  }

  static function MP($version) {
    self::checkContainer();
    $api_class = sprintf('%s\MP\%s\API', __NAMESPACE__, $version);
    try {
      return self::$container->get($api_class);
    } catch (ServiceNotFoundException $e) {
      throw new \Exception(__('Invalid API version.', 'mailpoet'));
    }
  }

  private static function checkContainer() {
    if(!self::$container) {
      throw new \Exception(__('Api was not initialized properly. Is MailPoet plugin active?.', 'mailpoet'));
    }
  }
}
