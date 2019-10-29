<?php

namespace MailPoet\Test\Cron;

use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class DaemonTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
  }

  function testItCanRun() {
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemon = new Daemon($this->createWorkersFactoryMock(), $this->cron_helper);
    $daemon->run($data);
  }

  function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
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

    ]);
  }

  private function createSimpleWorkerMock() {
    return $this->makeEmpty(SimpleWorker::class, [
      'process' => Expected::once(),
    ]);
  }
}
