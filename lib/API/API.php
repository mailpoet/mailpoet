<?php

namespace MailPoet\API;

use MailPoet\Config\AccessControl;

if(!defined('ABSPATH')) exit;

class API {
  static function JSON(AccessControl $access_control) {
    return new \MailPoet\API\JSON\API($access_control);
  }

  static function MP($version) {
    $api_class = sprintf('%s\MP\%s\API', __NAMESPACE__, $version);
    if(class_exists($api_class)) {
      return new $api_class();
    }
    throw new \Exception(__('Invalid API version.', 'mailpoet'));
  }
}