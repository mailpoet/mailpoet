<?php

namespace MailPoet\Test\Cron;

use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class DaemonTest extends \MailPoetTest {
  public $cronHelper;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->cronHelper = ContainerWrapper::getInstance()->get(CronHelper::class);
  }

  public function testItCanRun() {
    $cronWorkerRunner = $this->make(CronWorkerRunner::class, [
      'run' => null,
    ]);
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemon = new Daemon($this->cronHelper, $cronWorkerRunner, $this->createWorkersFactoryMock());
    $daemon->run($data);
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }

  private function createWorkersFactoryMock(array $workers = []) {
    return $this->make(WorkersFactory::class, $workers + [
      'createScheduleWorker' => $this->createSimpleWorkerMock(),
      'createQueueWorker' => $this->createSimpleWorkerMock(),
      'createStatsNotificationsWorker' => $this->createSimpleWorkerMock(),
      'createStatsNotificationsWorkerForAutomatedEmails' => $this->createSimpleWorkerMock(),
      'createSendingServiceKeyCheckWorker' => $this->createSimpleWorkerMock(),
      'createPremiumKeyCheckWorker' => $this->createSimpleWorkerMock(),
      'createBounceWorker' => $this->createSimpleWorkerMock(),
      'createMigrationWorker' => $this->createSimpleWorkerMock(),
      'createWooCommerceSyncWorker' => $this->createSimpleWorkerMock(),
      'createExportFilesCleanupWorker' => $this->createSimpleWorkerMock(),
      'createInactiveSubscribersWorker' => $this->createSimpleWorkerMock(),
      'createAuthorizedSendingEmailsCheckWorker' => $this->createSimpleWorkerMock(),
      'createWooCommercePastOrdersWorker' => $this->createSimpleWorkerMock(),
      'createBeamerkWorker' => $this->createSimpleWorkerMock(),
      'createUnsubscribeTokensWorker' => $this->createSimpleWorkerMock(),
      'createSubscriberLinkTokensWorker' => $this->createSimpleWorkerMock(),
      'createSubscribersEngagementScoreWorker' => $this->createSimpleWorkerMock(),
    ]);
  }

  private function createSimpleWorkerMock() {
    return $this->makeEmpty(SimpleWorker::class, [
      'process' => Expected::once(),
    ]);
  }
}
