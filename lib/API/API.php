<?php

namespace MailPoet\API;

use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoetVendor\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

if(!defined('ABSPATH')) exit;

class API {

  /** @var ContainerInterface */
  private static $container;

  static function injectContainer(ContainerInterface $container) {
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
      self::$container = ContainerWrapper::getInstance();
    }
  }
}
