<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\API\JSON\Endpoints\Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WordPressTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var WordPress */
  private $wordpress_trigger;

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
    ScheduledTask::where('type', Beamer::TASK_TYPE)->deleteMany();
    $this->_addScheduledTask(Beamer::TASK_TYPE, ScheduledTask::STATUS_SCHEDULED, Carbon::createFromTimestamp(current_time('timestamp') + 600));
    $this->wordpress_trigger = $this->di_container->get(WordPress::class);
  }

  public function testItDoesNotRunIfRunIntervalIsNotElapsed() {
    $run_interval = 10;
    WPFunctions::get()->addFilter('mailpoet_cron_trigger_wordpress_run_interval', function () use ($run_interval) {
      return $run_interval;
    });
    $current_time = time();
    $this->settings->set(WordPress::LAST_RUN_AT_SETTING, $current_time);
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    expect($this->wordpress_trigger->run())->equals(false);
    expect($this->settings->get(WordPress::LAST_RUN_AT_SETTING))->equals($current_time);
    WPFunctions::get()->removeAllFilters('mailpoet_cron_trigger_wordpress_run_interval');
  }

  public function testItRunsIfRunIntervalIsElapsed() {
    $run_interval = 10;
    WPFunctions::get()->addFilter('mailpoet_cron_trigger_wordpress_run_interval', function () use ($run_interval) {
      return $run_interval;
    });
    $time_in_the_past = (time() - $run_interval) - 1;
    $this->settings->set(WordPress::LAST_RUN_AT_SETTING, $time_in_the_past);
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    expect($this->wordpress_trigger->run())->notEmpty();
    expect($this->settings->get(WordPress::LAST_RUN_AT_SETTING))->greaterThan($time_in_the_past);
    WPFunctions::get()->removeAllFilters('mailpoet_cron_trigger_wordpress_run_interval');
  }

  public function testItCanResetRunInterval() {
    $current_time = time();
    $this->settings->set(WordPress::LAST_RUN_AT_SETTING, $current_time);
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    WordPress::resetRunInterval();
    expect($this->settings->get(WordPress::LAST_RUN_AT_SETTING))->isEmpty();
    expect($this->wordpress_trigger->run())->notEmpty();
  }

  public function testItRequiresScheduledQueuesToExecute() {
    expect($this->wordpress_trigger->checkExecutionRequirements())->false();
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
  }

  public function testItRequiresRunningQueuesToExecute() {
    expect($this->wordpress_trigger->checkExecutionRequirements())->false();
    // status of 'null' indicates that queue is running
    $this->_addQueue($status = null);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
  }


  public function testItFailsExecutionRequiremenetsCheckWhenQueueStatusIsCompleted() {
    expect($this->wordpress_trigger->checkExecutionRequirements())->false();
    $this->_addQueue($status = 'completed');
    expect($this->wordpress_trigger->checkExecutionRequirements())->false();
  }

  public function testItRequiresSendingLimitNotToBeReachedToExecute() {
    $this->_addQueue($status = null);
    $this->_addMTAConfigAndLog($sent = null);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
    $this->_addMTAConfigAndLog($sent = 1);
    expect($this->wordpress_trigger->checkExecutionRequirements())->false();
  }

  public function testItRequiresSendingNotToBePausedToExecute() {
    $this->_addQueue($status = null);
    $this->_addMTAConfigAndLog($sent = null);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
    $this->_addMTAConfigAndLog($sent = 0, $status = MailerLog::STATUS_PAUSED);
    expect($this->wordpress_trigger->checkExecutionRequirements())->false();
  }

  public function testItExecutesWhenMigrationIsNotPresent() {
    $this->_enableMigration();
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
  }

  public function testItExecutesWhenMigrationIsDue() {
    $this->_enableMigration();
    $this->_addScheduledTask(MigrationWorker::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
  }

  public function testItExecutesWhenAuthorizedEmailsCheckIsDue() {
    $this->_enableMigration();
    $this->_addScheduledTask(AuthorizedSendingEmailsCheck::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
  }

  public function testItExecutesWhenBeamerTaskIsDue() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    $this->_addScheduledTask(Beamer::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
  }

  public function testItDoesNotExecuteWhenMigrationIsCompleted() {
    $this->_enableMigration();
    $this->_addScheduledTask(MigrationWorker::TASK_TYPE, $status = ScheduledTask::STATUS_COMPLETED);
    expect($this->wordpress_trigger->checkExecutionRequirements())->false();
  }

  public function testItExecutesWhenBounceIsActive() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, [
      'method' => Mailer::METHOD_MAILPOET,
      'frequency' => [
        'emails' => SettingsController::DEFAULT_SENDING_FREQUENCY_EMAILS,
        'interval' => SettingsController::DEFAULT_SENDING_FREQUENCY_INTERVAL,
      ],
    ]);
    $this->_addScheduledTask(BounceWorker::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect($this->wordpress_trigger->checkExecutionRequirements())->true();
  }

  public function testItCanDeactivateRunningDaemon() {
    $this->settings->set(CronHelper::DAEMON_SETTING, ['status' => CronHelper::DAEMON_STATUS_ACTIVE]);
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
    $this->wordpress_trigger->stop();
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  public function testItRunsWhenExecutionRequirementsAreMet() {
    // status of 'null' indicates that queue is running
    $this->_addQueue($status = null);
    // check that cron daemon does not exist
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    $this->wordpress_trigger->run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->notNull();
  }

  public function testItDeactivatesCronDaemonWhenExecutionRequirementsAreNotMet() {
    $this->settings->set(CronHelper::DAEMON_SETTING, ['status' => CronHelper::DAEMON_STATUS_ACTIVE]);
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
    $this->wordpress_trigger->run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  public function _addMTAConfigAndLog($sent, $status = null) {
    $mta_config = [
      'frequency' => [
        'emails' => 1,
        'interval' => 1,
      ],
    ];
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      $mta_config
    );
    $mta_log = [
      'sent' => $sent,
      'started' => time(),
      'status' => $status,
    ];
    $this->settings->set(
      MailerLog::SETTING_NAME,
      $mta_log
    );
  }

  public function _addQueue($status) {
    $queue = SendingTask::create();
    $queue->hydrate(
      [
        'newsletter_id' => 1,
        'status' => $status,
        'scheduled_at' => ($status === SendingQueue::STATUS_SCHEDULED) ?
          Carbon::createFromTimestamp(current_time('timestamp')) :
          null,
      ]
    );
    return $queue->save();
  }

  public function _addScheduledTask($type, $status, $scheduled_at = null) {
    if (!$scheduled_at && $status === ScheduledTask::STATUS_SCHEDULED) {
      $scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    }
    $task = ScheduledTask::create();
    $task->hydrate(
      [
        'type' => $type,
        'status' => $status,
        'scheduled_at' => $scheduled_at,
      ]
    );
    return $task->save();
  }

  public function _enableMigration() {
    // Migration can be triggered only if cron execution method is selected
    // and is not "none"
    $this->settings->set('cron_trigger', [
      'method' => 'WordPress',
    ]);
  }

  public function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
