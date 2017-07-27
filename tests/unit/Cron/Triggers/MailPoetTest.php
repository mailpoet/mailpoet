<?php
namespace MailPoet\Cron\Triggers;

use MailPoet\API\JSON\Endpoints\Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Triggers\MailPoet;
use MailPoet\Models\Setting;

class MailPoetTest extends \MailPoetTest {
  function _before() {
    // cron trigger is by default set to 'WordPress'; when it runs and does not
    // detect any queues to process, it deletes the daemon setting, so Supervisor that's
    // called by the MailPoet cron trigger does not work. for that matter, we need to set
    // the trigger setting to anything but 'WordPress'.
    Setting::setValue('cron_trigger', array(
      'method' => 'none'
    ));
  }

  function testItCanRun() {
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->null();
    MailPoet::run();
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->notEmpty();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}