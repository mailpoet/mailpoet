<?php
namespace MailPoet\MP\API;

if(!defined('ABSPATH')) exit;

class API {
  static function __callStatic($version, $arguments) {
    $api_class = sprintf('%s\%s\API', __NAMESPACE__, $version);
    if(class_exists($api_class)) {
      $api = new $api_class();
      return $api;
    }
    throw new \Exception(__('Invalid API version.', 'mailpoet'));
  }
}