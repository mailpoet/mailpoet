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
  const DEFAULT_METHOD = 'WordPress';
  const SETTING_NAME = 'cron_trigger';

  function __construct() {
    $this->current_method = self::getCurrentMethod();
  }

  function init() {
    try {
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