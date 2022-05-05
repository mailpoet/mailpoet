<?php

namespace MailPoet\Cron;

use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class CronTrigger {
  const METHOD_LINUX_CRON = 'Linux Cron';
  const METHOD_WORDPRESS = 'WordPress';

  const CRON_TRIGGER_ACTION = 'mailpoet_start_daemon_http_runner';

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

  /** @var WPFunctions */
  private $wp;

  /** @var CronHelper */
  private $cronHelper;

  public function __construct(
    WordPress $wordpressTrigger,
    SettingsController $settings,
    WPFunctions $wp,
    CronHelper $cronHelper
  ) {
    $this->wordpressTrigger = $wordpressTrigger;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->cronHelper = $cronHelper;
  }

  public function init() {
    $this->wp->addAction(self::CRON_TRIGGER_ACTION, [
      $this,
      'startDaemonHttpRunner',
    ]);
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

  public function startDaemonHttpRunner(string $token) {
    $this->cronHelper->accessDaemon($token);
  }
}
