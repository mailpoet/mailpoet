<?php

namespace MailPoet\Cron;

use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;

class CronTrigger {
  const METHOD_LINUX_CRON = 'Linux Cron';
  const METHOD_WORDPRESS = 'WordPress';
  const METHOD_ACTION_SCHEDULER = 'Action Scheduler';

  const METHODS = [
    'wordpress' => self::METHOD_WORDPRESS,
    'linux_cron' => self::METHOD_LINUX_CRON,
    'action_scheduler' => self::METHOD_ACTION_SCHEDULER,
    'none' => 'Disabled',
  ];

  const DEFAULT_METHOD = self::METHOD_ACTION_SCHEDULER;
  const SETTING_NAME = 'cron_trigger';

  /** @var WordPress */
  private $wordpressTrigger;

  /** @var SettingsController */
  private $settings;

  /** @var DaemonActionSchedulerRunner */
  private $cronActionScheduler;

  public function __construct(
    WordPress $wordpressTrigger,
    SettingsController $settings,
    DaemonActionSchedulerRunner $cronActionScheduler
  ) {
    $this->wordpressTrigger = $wordpressTrigger;
    $this->settings = $settings;
    $this->cronActionScheduler = $cronActionScheduler;
  }

  public function init() {
    $currentMethod = $this->settings->get(self::SETTING_NAME . '.method');
    try {
      if ($currentMethod === self::METHOD_WORDPRESS) {
        return $this->wordpressTrigger->run();
      } elseif ($currentMethod === self::METHOD_ACTION_SCHEDULER) {
        // register Action Scheduler
        require_once __DIR__ . '/../../vendor/woocommerce/action-scheduler/action-scheduler.php';
        $this->cronActionScheduler->init();
      }
      return false;
    } catch (\Exception $e) {
      // cron exceptions should not prevent the rest of the site from loading
    }
  }
}
