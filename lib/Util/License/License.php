<?php
namespace MailPoet\Util\License;

class License {
  const CHECK_PERMISSION = 'mailpoet_premium_feature_permission';
  const FEATURE_NAMESPACE = 'MailPoet\Util\License\Features\\';

  function init() {
    add_action(self::CHECK_PERMISSION, array($this, 'checkFeaturePermission'));
  }

  static function getLicense() {
    return (defined('MAILPOET_PREMIUM_LICENSE')) ?
      MAILPOET_PREMIUM_LICENSE :
      false;
  }

  function checkFeaturePermission($feature) {
    $feature = self::FEATURE_NAMESPACE . $feature;
    if(class_exists($feature)) {
      $feature = new $feature();
      $feature->check();
    }
  }
}