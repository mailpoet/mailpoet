<?php
namespace MailPoet\Cron;

use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;

if(!defined('ABSPATH')) exit;

class CronTrigger {
  public $current_method;
  public static $available_methods = array(
    'mailpoet' => 'MailPoet',
    'wordpress' => 'WordPress',
    'linux_cron' => 'Linux Cron',
    'none' => 'Disabled'
  );
  const DEFAULT_METHOD = 'WordPress';
  const SETTING_NAME = 'cron_trigger';

  function __construct(SettingsController $settingsController) {
    $this->current_method = $settingsController->get(self::SETTING_NAME . '.method');
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
}
