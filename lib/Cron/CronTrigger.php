<?php

namespace MailPoet\Cron;

use MailPoet\Settings\SettingsController;

class CronTrigger {
  /** @var SettingsController */
  private $settings;

  const METHOD_LINUX_CRON = 'Linux Cron';
  const METHOD_MAILPOET = 'MailPoet';
  const METHOD_WORDPRESS = 'WordPress';

  public static $available_methods = [
    'mailpoet' => self::METHOD_MAILPOET,
    'wordpress' => self::METHOD_WORDPRESS,
    'linux_cron' => self::METHOD_LINUX_CRON,
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
