<?php
namespace MailPoet\API;

if(!defined('ABSPATH')) exit;

class API {
  static function JSON() {
    return new \MailPoet\API\JSON\API();
  }

  static function MP($version) {
    $api_class = sprintf('%s\MP\%s\API', __NAMESPACE__, $version);
    if(class_exists($api_class)) {
      return new $api_class();
    }
    throw new \Exception(__('Invalid API version.', 'mailpoet'));
  }
}