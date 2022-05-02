<?php

namespace MailPoet\Cron;

use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;

class CronTrigger {
  const METHOD_LINUX_CRON = 'Linux Cron';
  const METHOD_WORDPRESS = 'WordPress';

  const METHODS = [
    'wordpress' => self::METHOD_WORDPRESS,
    'linux_cron' => self::METHOD_LINUX_CRON,
    'none' => 'Disabled',
  ];

  const DEFAULT_METHOD = 'WordPress';
  const SETTING_NAME = 'cron_trigger';

  /** @var WordPress */
  private $wordpressTrigger;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    WordPress $wordpressTrigger,
    SettingsController $settings
  ) {
    $this->wordpressTrigger = $wordpressTrigger;
    $this->settings = $settings;
  }

  public function init() {
    $currentMethod = $this->settings->get(self::SETTING_NAME . '.method');
    try {
      if ($currentMethod === self::METHOD_WORDPRESS) {
        return $this->wordpressTrigger->run();
      }
      return false;
    } catch (\Exception $e) {
      // cron exceptions should not prevent the rest of the site from loading
    }
  }
}
