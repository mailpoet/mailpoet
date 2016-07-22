<?php
namespace MailPoet\Cron;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class CronTrigger {
  public $current_method;
  public static $available_methods = array(
    'mailpoet' => 'MailPoet',
    'wordpress' => 'WordPress'
  );
  const SETTING_NAME = 'cron_trigger';

  function __construct() {
    $this->current_method = self::getCurrentMethod();
    if(!in_array($this->current_method, self::$available_methods)) {
      throw new \Exception(__('Task scheduler is not configured'));
    }
  }

  function init() {
    try {
      // configure cron trigger only outside of cli environment
      if(php_sapi_name() === 'cli') return;
      $trigger_class = __NAMESPACE__ . '\Triggers\\' . $this->current_method;
      return (class_exists($trigger_class)) ?
        $trigger_class::run() :
        false;
    } catch(\Exception $e) {
      // cron exceptions should not prevent the rest of the site from loading
    }
  }

  static function getAvailableMethods() {
    return self::$available_methods;
  }

  static function getCurrentMethod() {
    return Setting::getValue(self::SETTING_NAME . '.method');
  }
}