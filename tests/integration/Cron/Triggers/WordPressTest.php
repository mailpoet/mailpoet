<?php
namespace MailPoet\Cron\Triggers;

use Carbon\Carbon;
use MailPoet\API\JSON\Endpoints\Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;

class WordPressTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    // cron trigger is by default set to 'WordPress'; when it runs and does not
    // detect any queues to process, it deletes the daemon setting, so Supervisor that's
    // called by the MailPoet cron trigger does not work. for that matter, we need to set
    // the trigger setting to anything but 'WordPress'.
    $this->settings = new SettingsController();
    $this->settings->set('cron_trigger', array(
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

  function testItExecutesWhenMigrationIsNotPresent() {
    $this->_enableMigration();
    expect(WordPress::checkExecutionRequirements())->true();
  }

  function testItExecutesWhenMigrationIsDue() {
    $this->_enableMigration();
    $this->_addScheduledTask(MigrationWorker::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect(WordPress::checkExecutionRequirements())->true();
  }

  function testItDoesNotExecuteWhenMigrationIsCompleted() {
    $this->_enableMigration();
    $this->_addScheduledTask(MigrationWorker::TASK_TYPE, $status = ScheduledTask::STATUS_COMPLETED);
    expect(WordPress::checkExecutionRequirements())->false();
  }

  function testItCanDeactivateRunningDaemon() {
    $this->settings->set(CronHelper::DAEMON_SETTING, ['status' => CronHelper::DAEMON_STATUS_ACTIVE]);
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
    WordPress::stop();
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  function testItRunsWhenExecutionRequirementsAreMet() {
    // status of 'null' indicates that queue is running
    $this->_addQueue($status = null);
    // check that cron daemon does not exist
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    WordPress::run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->notNull();
  }

  function testItDeactivatesCronDaemonWhenExecutionRequirementsAreNotMet() {
    $this->settings->set(CronHelper::DAEMON_SETTING, ['status' => CronHelper::DAEMON_STATUS_ACTIVE]);
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
    WordPress::run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  function _addMTAConfigAndLog($sent, $status = null) {
    $mta_config = array(
      'frequency' => array(
        'emails' => 1,
        'interval' => 1
      )
    );
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      $mta_config
    );
    $mta_log = array(
      'sent' => $sent,
      'started' => time(),
      'status' => $status
    );
    $this->settings->set(
      MailerLog::SETTING_NAME,
      $mta_log
    );
  }

  function _addQueue($status) {
    $queue = SendingTask::create();
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

  function _addScheduledTask($type, $status) {
    $task = ScheduledTask::create();
    $task->hydrate(
      array(
        'type' => $type,
        'status' => $status,
        'scheduled_at' => ($status === ScheduledTask::STATUS_SCHEDULED) ?
          Carbon::createFromTimestamp(current_time('timestamp')) :
          null
      )
    );
    return $task->save();
  }

  function _enableMigration() {
    // Migration can be triggered only if cron execution method is selected
    // and is not "none"
    $this->settings->set('cron_trigger', array(
      'method' => 'WordPress'
    ));
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
