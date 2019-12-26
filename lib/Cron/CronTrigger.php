<?php

namespace MailPoet\Cron;

use MailPoet\Cron\Triggers\MailPoet;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;

class CronTrigger {
  const METHOD_LINUX_CRON = 'Linux Cron';
  const METHOD_MAILPOET = 'MailPoet';
  const METHOD_WORDPRESS = 'WordPress';

  const METHODS = [
    'mailpoet' => self::METHOD_MAILPOET,
    'wordpress' => self::METHOD_WORDPRESS,
    'linux_cron' => self::METHOD_LINUX_CRON,
    'none' => 'Disabled',
  ];

  const DEFAULT_METHOD = 'WordPress';
  const SETTING_NAME = 'cron_trigger';

  /** @var MailPoet */
  private $mailpoet_trigger;

  /** @var WordPress */
  private $wordpress_trigger;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    MailPoet $mailpoet_trigger,
    WordPress $wordpress_trigger,
    SettingsController $settings
  ) {
    $this->mailpoet_trigger = $mailpoet_trigger;
    $this->wordpress_trigger = $wordpress_trigger;
    $this->settings = $settings;
  }

  public function init() {
    $current_method = $this->settings->get(self::SETTING_NAME . '.method');
    try {
      if ($current_method === self::METHOD_MAILPOET) {
        return $this->mailpoet_trigger->run();
      } elseif ($current_method === self::METHOD_WORDPRESS) {
        return $this->wordpress_trigger->run();
      }
      return false;
    } catch (\Exception $e) {
      // cron exceptions should not prevent the rest of the site from loading
    }
  }
}
