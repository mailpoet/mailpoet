<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\API\JSON\Endpoints\Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class MailPoetTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  function _before() {
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

  function testItCanRun() {
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    $this->di_container->get(MailPoet::class)->run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->notEmpty();
  }

  function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
  }
}
