<?php
namespace MailPoet\Test\Cron;

use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;

class DaemonTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = new SettingsController();
  }

  function testItCanRun() {
    $data = [
      'token' => 123,
    ];
    $this->settings->set(CronHelper::DAEMON_SETTING, $data);
    $daemon = new Daemon($this->createWorkersFactoryMock());
    $daemon->run($data);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }

  private function createWorkersFactoryMock(array $workers = []) {
    return $this->make(WorkersFactory::class, $workers + [
      'createScheduleWorker' => $this->createSimpleWorkerMock(),
      'createQueueWorker' => $this->createSimpleWorkerMock(),
      'createStatsNotificationsWorker' => $this->createSimpleWorkerMock(),
      'createSendingServiceKeyCheckWorker' => $this->createSimpleWorkerMock(),
      'createPremiumKeyCheckWorker' => $this->createSimpleWorkerMock(),
      'createBounceWorker' => $this->createSimpleWorkerMock(),
      'createMigrationWorker' => $this->createSimpleWorkerMock(),
      'createWooCommerceSyncWorker' => $this->createSimpleWorkerMock(),
      'createExportFilesCleanupWorker' => $this->createSimpleWorkerMock(),
      'createInactiveSubscribersWorker' => $this->createSimpleWorkerMock(),
      'createAuthorizedSendingEmailsCheckWorker' => $this->createSimpleWorkerMock(),
      'createWooCommerceOrdersWorker' => $this->createSimpleWorkerMock(),
    ]);
  }

  private function createSimpleWorkerMock() {
    return $this->makeEmpty(SimpleWorker::class, [
      'process' => Expected::once(),
    ]);
  }
}
