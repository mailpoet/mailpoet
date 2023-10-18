<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron;

use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class DaemonHttpRunnerTest extends \MailPoetTest {
  public $cronHelper;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
  }

  public function testItConstructs() {
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      []
    );
    $daemon = $this->diContainer->get(DaemonHttpRunner::class);
    verify(strlen((string)$daemon->timer))->greaterThanOrEqual(5);
    verify(strlen($daemon->token))->greaterThanOrEqual(5);
  }

  public function testItDoesNotRunWithoutRequestData() {
    $daemon = $this->make(
      DaemonHttpRunner::class,
      [
        'abortWithError' => function($message) {
          return $message;
        },
      ]
    );
    verify($daemon->run(false))->equals('Invalid or missing request data.');
  }

  public function testItDoesNotRunWhenThereIsInvalidOrMissingToken() {
    $daemon = $this->make(
      DaemonHttpRunner::class,
      [
        'abortWithError' => function($message) {
          return $message;
        },
      ]
    );
    $daemon->settingsDaemonData = [
      'token' => 123,
    ];
    verify($daemon->run(['token' => 456]))->equals('Invalid or missing token.');
  }

  public function testItStoresErrorMessageAndContinuesExecutionWhenWorkersThrowException() {
    $data = [
      'token' => 123,
    ];

    $matcher = $this->any();
    $cronWorkerRunnerMock = $this->createMock(CronWorkerRunner::class);
    $cronWorkerRunnerMock
      ->expects($matcher)
      ->method('run')
      ->willReturnCallback(function () use ($matcher) {
        if ($matcher->getInvocationCount() === 1) {
          throw new \Exception('Message');
        } else if ($matcher->getInvocationCount() === 2) {
          throw new \Exception();
        }
      });

    $daemon = new Daemon($this->cronHelper, $cronWorkerRunnerMock, $this->createWorkersFactoryMock(), $this->diContainer->get(LoggerFactory::class));
    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => null,
      'callSelf' => null,
    ]);
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
    $updatedDaemon = $this->settings->get(CronHelper::DAEMON_SETTING);
    verify($updatedDaemon['last_error'][0]['message'])->equals('Message');
    verify($updatedDaemon['last_error'][1]['message'])->equals('');
  }

  public function testItCanPauseExecution() {
    $daemon = $this->makeEmpty(Daemon::class);
    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => Expected::exactly(1, function($pauseDelay) {
        expect($pauseDelay)->lessThan($this->cronHelper->getDaemonExecutionLimit());
        verify($pauseDelay)->greaterThan($this->cronHelper->getDaemonExecutionLimit() - 1);
      }),
      'callSelf' => null,
      'terminateRequest' => null,
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
  }

  public function testItTerminatesExecutionWhenDaemonIsDeleted() {
    $daemon = $this->make(Daemon::class, [
      'run' => function () {
        $this->settings->delete(CronHelper::DAEMON_SETTING);
      },
    ]);

    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => null,
      'terminateRequest' => Expected::exactly(1),
      'callSelf' => Expected::never(),
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);

    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
  }

  public function testItTerminatesExecutionWhenDaemonTokenChangesAndKeepsChangedToken() {
    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => null,
      'terminateRequest' => Expected::exactly(1),
      'callSelf' => Expected::never(),
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);

    $daemon = $this->make(Daemon::class, [
      'run' => function () {
        $this->settings->set(
          CronHelper::DAEMON_SETTING,
          ['token' => 567]
        );
      },
    ]);
    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
    $dataAfterRun = $this->settings->get(CronHelper::DAEMON_SETTING);
    verify($dataAfterRun['token'])->equals(567);
  }

  public function testItTerminatesExecutionWhenDaemonIsDeactivated() {
    $daemon = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => null,
      'terminateRequest' => Expected::exactly(1),
      'callSelf' => Expected::never(),
    ]);
    $data = [
      'token' => 123,
      'status' => CronHelper::DAEMON_STATUS_INACTIVE,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($this->makeEmpty(Daemon::class), $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemon->run($data);
  }

  public function testItTerminatesExecutionWhenWPTriggerStopsCron() {
    $daemon = $this->make(Daemon::class, [
      'run' => null,
    ]);
    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'checkWPTriggerExecutionRequirements' => false,
      'pauseExecution' => null,
      'terminateRequest' => Expected::exactly(1),
      'callSelf' => Expected::never(),
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $this->settings->set(CronTrigger::SETTING_NAME . '.method', 'WordPress');
    WPFunctions::get()->addFilter('mailpoet_cron_enable_self_deactivation', '__return_true');
    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
    WPFunctions::get()->removeAllFilters('mailpoet_cron_enable_self_deactivation');
  }

  public function testItUpdatesDaemonTokenDuringExecution() {
    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => null,
      'callSelf' => null,
      'terminateRequest' => null,
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $cronWorkerRunner = $this->make(CronWorkerRunner::class, [
      'run' => null,
    ]);
    $daemon = new Daemon($this->cronHelper, $cronWorkerRunner, $this->createWorkersFactoryMock(), $this->diContainer->get(LoggerFactory::class));
    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
    $updatedDaemon = $this->settings->get(CronHelper::DAEMON_SETTING);
    verify($updatedDaemon['token'])->equals($daemonHttpRunner->token);
  }

  public function testItUpdatesTimestampsDuringExecution() {
    $matcher = $this->any();
    $cronWorkerRunnerMock = $this->createMock(CronWorkerRunner::class);
    $cronWorkerRunnerMock
      ->expects($matcher)
      ->method('run')
      ->willReturnCallback(function () use ($matcher) {
        if ($matcher->getInvocationCount() === 1) {
          sleep(2);
        } else if ($matcher->getInvocationCount() === 2) {
          throw new \Exception();
        }
      });
    $daemon = new Daemon($this->cronHelper, $cronWorkerRunnerMock, $this->createWorkersFactoryMock(), $this->diContainer->get(LoggerFactory::class));
    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => null,
      'callSelf' => null,
    ]);
    $data = [
      'token' => 123,
    ];
    $now = time();
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
    $updatedDaemon = $this->settings->get(CronHelper::DAEMON_SETTING);
    verify($updatedDaemon['run_started_at'])->greaterThanOrEqual($now);
    expect($updatedDaemon['run_started_at'])->lessThan($now + 2);
    verify($updatedDaemon['run_completed_at'])->greaterThanOrEqual($now + 2);
    expect($updatedDaemon['run_completed_at'])->lessThan($now + 4);
  }

  public function testItCanRun() {
    ignore_user_abort(false);
    verify(ignore_user_abort())->equals(false);
    $daemonHttpRunner = $this->make(DaemonHttpRunner::class, [
      'pauseExecution' => null,
      // daemon should call itself
      'callSelf' => Expected::exactly(1),
      'terminateRequest' => null,
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);

    $cronWorkerRunnerMock = $this->make(CronWorkerRunner::class, [
      'run' => null,
    ]);
    $daemon = new Daemon($this->cronHelper, $cronWorkerRunnerMock, $this->createWorkersFactoryMock(), $this->diContainer->get(LoggerFactory::class));
    $daemonHttpRunner->__construct($daemon, $this->cronHelper, SettingsController::getInstance(), $this->diContainer->get(WordPress::class));
    $daemonHttpRunner->run($data);
    verify(ignore_user_abort())->equals(true);
  }

  public function testItRespondsToPingRequest() {
    $daemon = $this->make(DaemonHttpRunner::class, [
      'terminateRequest' => Expected::exactly(1, function($message) {
        verify($message)->equals('pong');
      }),
    ]);
    $daemon->ping();
  }

  private function createWorkersFactoryMock(array $workers = []) {
    $worker = $this->makeEmpty(SimpleWorker::class, [
      'process' => null,
    ]);
    return $this->make(WorkersFactory::class, $workers + [
      'createScheduleWorker' => $worker,
      'createQueueWorker' => $worker,
      'createStatsNotificationsWorker' => $worker,
      'createStatsNotificationsWorkerForAutomatedEmails' => $worker,
      'createSendingServiceKeyCheckWorker' => $worker,
      'createPremiumKeyCheckWorker' => $worker,
      'createSubscribersStatsReportWorker' => $worker,
      'createBounceWorker' => $worker,
      'createWooCommerceSyncWorker' => $worker,
      'createExportFilesCleanupWorker' => $worker,
      'createSubscribersEmailCountsWorker' => $worker,
      'createInactiveSubscribersWorker' => $worker,
      'createAuthorizedSendingEmailsCheckWorker' => $worker,
      'createWooCommercePastOrdersWorker' => $worker,
      'createBeamerkWorker' => $worker,
      'createUnsubscribeTokensWorker' => $worker,
      'createSubscriberLinkTokensWorker' => $worker,
      'createSubscribersEngagementScoreWorker' => $worker,
      'createSubscribersLastEngagementWorker' => $worker,
      'createSubscribersCountCacheRecalculationWorker' => $worker,
      'createReEngagementEmailsSchedulerWorker' => $worker,
      'createNewsletterTemplateThumbnailsWorker' => $worker,
      'createAbandonedCartWorker' => $worker,
      'createBackfillEngagementDataWorker' => $worker,
      'createMixpanelWorker' => $worker,
      ]);
  }
}
