<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\CronHelper;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class MailPoetTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    // cron trigger is by default set to 'WordPress'; when it runs and does not
    // detect any queues to process, it deletes the daemon setting, so Supervisor that's
    // called by the MailPoet cron trigger does not work. for that matter, we need to set
    // the trigger setting to anything but 'WordPress'.
    $this->settings = SettingsController::getInstance();
    $this->settings->set('cron_trigger', [
      'method' => 'none',
    ]);
  }

  public function testItCanRun() {
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    $this->diContainer->get(MailPoet::class)->run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->notEmpty();
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
