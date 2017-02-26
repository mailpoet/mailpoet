<?php

use Carbon\Carbon;
use MailPoet\API\Endpoints\Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;

class WordPressCronTriggerTest extends MailPoetTest {
  function _before() {
    // cron trigger is by default set to 'WordPress'; when it runs and does not
    // detect any queues to process, it deletes the daemon setting, so Supervisor that's
    // called by the MailPoet cron trigger does not work. for that matter, we need to set
    // the trigger setting to anything but 'WordPress'.
    Setting::setValue('cron_trigger', array(
      'method' => 'none'
    ));
  }

  function testItRequiresScheduledQueuesToExecute() {
    expect(WordPress::checkExecutionRequirements())->false();
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    expect(WordPress::checkExecutionRequirements())->true();
  }

  function testItRequiresRunningQueuesToExecute() {
    expect(WordPress::checkExecutionRequirements())->false();
    // status of 'null' indicates that queue is running
    $this->_addQueue($status = null);
    expect(WordPress::checkExecutionRequirements())->true();
  }


  function testItFailsExecutionRequiremenetsCheckWhenQueueStatusIsCompleted() {
    expect(WordPress::checkExecutionRequirements())->false();
    $this->_addQueue($status = 'completed');
    expect(WordPress::checkExecutionRequirements())->false();
  }

  function testItRequiresSendingLimitNotToBeReachedToExecute() {
    $this->_addQueue($status = null);
    $this->_addMTAConfigAndLog($sent = null);
    expect(WordPress::checkExecutionRequirements())->true();
    $this->_addMTAConfigAndLog($sent = 1);
    expect(WordPress::checkExecutionRequirements())->false();
  }

  function testItRequiresSendingNotToBePausedToExecute() {
    $this->_addQueue($status = null);
    $this->_addMTAConfigAndLog($sent = null);
    expect(WordPress::checkExecutionRequirements())->true();
    $this->_addMTAConfigAndLog($sent = 0, $status = MailerLog::STATUS_PAUSED);
    expect(WordPress::checkExecutionRequirements())->false();
  }

  function testItCanDeleteRunningDaemon() {
    Setting::setValue(CronHelper::DAEMON_SETTING, true);
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->notNull();
    WordPress::cleanup();
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->null();
  }

  function testItRunsWhenExecutionRequirementsAreMet() {
    // status of 'null' indicates that queue is running
    $this->_addQueue($status = null);
    // check that cron daemon does not exist
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->null();
    WordPress::run();
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->notNull();
  }

  function testItDeletesCronDaemonWhenExecutionRequirementsAreNotMet() {
    Setting::setValue(CronHelper::DAEMON_SETTING, true);
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->notNull();
    WordPress::run();
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->null();
  }

  function _addMTAConfigAndLog($sent, $status = null) {
    $mta_config = array(
      'frequency' => array(
        'emails' => 1,
        'interval' => 1
      )
    );
    Setting::setValue(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      $mta_config
    );
    $mta_log = array(
      'sent' => $sent,
      'started' => time(),
      'status' => $status
    );
    Setting::setValue(
      MailerLog::SETTING_NAME,
      $mta_log
    );
  }

  function _addQueue($status) {
    $queue = SendingQueue::create();
    $queue->hydrate(
      array(
        'newsletter_id' => 1,
        'status' => $status,
        'scheduled_at' => ($status === SendingQueue::STATUS_SCHEDULED) ?
          Carbon::createFromTimestamp(current_time('timestamp')) :
          null
      )
    );
    return $queue->save();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}