<?php
namespace MailPoet\Cron;

use MailPoet\Settings\SettingsController;

if (!defined('ABSPATH')) exit;

class CronTrigger {
  /** @var SettingsController */
  private $settings;

  public static $available_methods = [
    'mailpoet' => 'MailPoet',
    'wordpress' => 'WordPress',
    'linux_cron' => 'Linux Cron',
    'none' => 'Disabled',
  ];
  const DEFAULT_METHOD = 'WordPress';
  const SETTING_NAME = 'cron_trigger';

  function __construct(SettingsController $settings) {
    $this->settings = $settings;
  }

  function init() {
    $current_method = $this->settings->get(self::SETTING_NAME . '.method');
    try {
      $trigger_class = __NAMESPACE__ . '\Triggers\\' . $current_method;
      return (class_exists($trigger_class)) ?
        $trigger_class::run() :
        false;
    } catch (\Exception $e) {
      // cron exceptions should not prevent the rest of the site from loading
    }
  }

  static function getAvailableMethods() {
    return self::$available_methods;
  }
}
