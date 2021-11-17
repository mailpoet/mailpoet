<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SubscribersStatsReport;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Services\Bridge;
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
  private $wordpressTrigger;

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
    $future = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp') + 600);
    $this->_addScheduledTask(Beamer::TASK_TYPE, ScheduledTask::STATUS_SCHEDULED, $future);
    $this->_addScheduledTask(SubscribersStatsReport::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $future);
    $this->wordpressTrigger = $this->diContainer->get(WordPress::class);
  }

  public function testItDoesNotRunIfRunIntervalIsNotElapsed() {
    $runInterval = 10;
    WPFunctions::get()->addFilter('mailpoet_cron_trigger_wordpress_run_interval', function () use ($runInterval) {
      return $runInterval;
    });
    $currentTime = time();
    $this->settings->set(WordPress::LAST_RUN_AT_SETTING, $currentTime);
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    expect($this->wordpressTrigger->run())->equals(false);
    expect($this->settings->get(WordPress::LAST_RUN_AT_SETTING))->equals($currentTime);
    WPFunctions::get()->removeAllFilters('mailpoet_cron_trigger_wordpress_run_interval');
  }

  public function testItRunsIfRunIntervalIsElapsed() {
    $runInterval = 10;
    WPFunctions::get()->addFilter('mailpoet_cron_trigger_wordpress_run_interval', function () use ($runInterval) {
      return $runInterval;
    });
    $timeInThePast = (time() - $runInterval) - 1;
    $this->settings->set(WordPress::LAST_RUN_AT_SETTING, $timeInThePast);
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    expect($this->wordpressTrigger->run())->notEmpty();
    expect($this->settings->get(WordPress::LAST_RUN_AT_SETTING))->greaterThan($timeInThePast);
    WPFunctions::get()->removeAllFilters('mailpoet_cron_trigger_wordpress_run_interval');
  }

  public function testItCanResetRunInterval() {
    $currentTime = time();
    $this->settings->set(WordPress::LAST_RUN_AT_SETTING, $currentTime);
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    WordPress::resetRunInterval();
    expect($this->settings->get(WordPress::LAST_RUN_AT_SETTING))->isEmpty();
    expect($this->wordpressTrigger->run())->notEmpty();
  }

  public function testItRequiresScheduledQueuesToExecute() {
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
    $this->_addQueue($status = SendingQueue::STATUS_SCHEDULED);
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
  }

  public function testItRequiresRunningQueuesToExecute() {
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
    // status of 'null' indicates that queue is running
    $this->_addQueue($status = null);
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
  }

  public function testItFailsExecutionRequiremenetsCheckWhenQueueStatusIsCompleted() {
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
    $this->_addQueue($status = 'completed');
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
  }

  public function testItRequiresSendingLimitNotToBeReachedToExecute() {
    $this->_addQueue($status = null);
    $this->_addMTAConfigAndLog($sent = null);
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
    $this->_addMTAConfigAndLog($sent = 1);
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
  }

  public function testItRequiresSendingNotToBePausedToExecute() {
    $this->_addQueue($status = null);
    $this->_addMTAConfigAndLog($sent = null);
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
    $this->_addMTAConfigAndLog($sent = 0, $status = MailerLog::STATUS_PAUSED);
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
  }

  public function testItExecutesWhenMigrationIsNotPresent() {
    $this->_enableMigration();
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
  }

  public function testItExecutesWhenMigrationIsDue() {
    $this->_enableMigration();
    $this->_addScheduledTask(MigrationWorker::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
  }

  public function testItExecutesWhenAuthorizedEmailsCheckIsDue() {
    $this->_enableMigration();
    $this->_addScheduledTask(AuthorizedSendingEmailsCheck::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
  }

  public function testItExecutesWhenBeamerTaskIsDue() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    $this->_addScheduledTask(Beamer::TASK_TYPE, $status = ScheduledTask::STATUS_SCHEDULED);
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
  }

  public function testItDoesNotExecuteWhenMigrationIsCompleted() {
    $this->_enableMigration();
    $this->_addScheduledTask(MigrationWorker::TASK_TYPE, $status = ScheduledTask::STATUS_COMPLETED);
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
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
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
  }

  public function testItCanDeactivateRunningDaemon() {
    $this->settings->set(CronHelper::DAEMON_SETTING, ['status' => CronHelper::DAEMON_STATUS_ACTIVE]);
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
    $this->wordpressTrigger->stop();
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  public function testItRunsWhenExecutionRequirementsAreMet() {
    // status of 'null' indicates that queue is running
    $this->_addQueue($status = null);
    // check that cron daemon does not exist
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    $this->wordpressTrigger->run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->notNull();
  }

  public function testItDeactivatesCronDaemonWhenExecutionRequirementsAreNotMet() {
    $this->settings->set(CronHelper::DAEMON_SETTING, ['status' => CronHelper::DAEMON_STATUS_ACTIVE]);
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
    $this->wordpressTrigger->run();
    expect($this->settings->get(CronHelper::DAEMON_SETTING)['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  public function testItDoesNotTriggerCronWhenFutureStatsReportIsScheduled() {
    $future = Carbon::now()->addHour();
    $statsJobType = SubscribersStatsReport::TASK_TYPE;
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    $scheduledTaskTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("DELETE FROM $scheduledTaskTable WHERE type = '$statsJobType';");
    $this->settings->set(Bridge::API_KEY_SETTING_NAME, 'asdfgh');
    expect($this->wordpressTrigger->checkExecutionRequirements())->true();
    $this->_addScheduledTask(SubscribersStatsReport::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $future);
    expect($this->wordpressTrigger->checkExecutionRequirements())->false();
  }

  public function _addMTAConfigAndLog($sent, $status = null) {
    $mtaConfig = [
      'frequency' => [
        'emails' => 1,
        'interval' => 1,
      ],
    ];
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      $mtaConfig
    );
    $mtaLog = [
      'sent' => $sent,
      'started' => time(),
      'status' => $status,
    ];
    $this->settings->set(
      MailerLog::SETTING_NAME,
      $mtaLog
    );
  }

  public function _addQueue($status) {
    $queue = SendingTask::create();
    $queue->hydrate(
      [
        'newsletter_id' => 1,
        'status' => $status,
        'scheduled_at' => ($status === SendingQueue::STATUS_SCHEDULED) ?
          Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')) :
          null,
      ]
    );
    return $queue->save();
  }

  public function _addScheduledTask($type, $status, $scheduledAt = null) {
    if (!$scheduledAt && $status === ScheduledTask::STATUS_SCHEDULED) {
      $scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    }
    $task = ScheduledTask::create();
    $task->hydrate(
      [
        'type' => $type,
        'status' => $status,
        'scheduled_at' => $scheduledAt,
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
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
