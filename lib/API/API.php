<?php

namespace MailPoet\API;

use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

if(!defined('ABSPATH')) exit;

class API {
  static function MP($version) {
    $api_class = sprintf('%s\MP\%s\API', __NAMESPACE__, $version);
    try {
      return ContainerWrapper::getInstance()->get($api_class);
    } catch (ServiceNotFoundException $e) {
      throw new \Exception(__('Invalid API version.', 'mailpoet'));
    }
  }
}
