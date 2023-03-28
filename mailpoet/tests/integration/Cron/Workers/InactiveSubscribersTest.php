<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoetVendor\Carbon\Carbon;

class InactiveSubscribersTest extends \MailPoetTest {
  public $cronHelper;

  /** @var SettingsController */
  private $settings;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    $this->settings = SettingsController::getInstance();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $this->cronHelper = ContainerWrapper::getInstance()->get(CronHelper::class);
    parent::_before();
  }

  public function testItReactivateInactiveSubscribersWhenIntervalIsSetToNever() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 0);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::never(),
      'markActiveSubscribers' => Stub\Expected::never(),
      'reactivateInactiveSubscribers' => Stub\Expected::once(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribers::class, [
      'inactiveSubscribersController' => $controllerMock,
    ]);
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $task = $this->scheduledTasksRepository->findOneBy(
      ['type' => InactiveSubscribers::TASK_TYPE, 'status' => ScheduledTaskEntity::STATUS_SCHEDULED]
    );

    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
    expect($task->getScheduledAt())->greaterThan(new Carbon());
  }

  public function testItDoesNotRunWhenTrackingIsDisabled() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 10);
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::never(),
      'markActiveSubscribers' => Stub\Expected::never(),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribers::class, [
      'inactiveSubscribersController' => $controllerMock,
    ]);
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
  }

  public function testItSchedulesNextRunWhenFinished() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::once(1),
      'markActiveSubscribers' => Stub\Expected::once(1),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribers::class, [
      'inactiveSubscribersController' => $controllerMock,
    ]);
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $task = $this->scheduledTasksRepository->findOneBy(
      ['type' => InactiveSubscribers::TASK_TYPE, 'status' => ScheduledTaskEntity::STATUS_SCHEDULED]
    );

    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
    expect($task->getScheduledAt())->greaterThan(new Carbon());
  }

  public function testRunBatchesUntilItIsFinished() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub::consecutive(InactiveSubscribers::BATCH_SIZE, InactiveSubscribers::BATCH_SIZE, 1, 0),
      'markActiveSubscribers' => Stub::consecutive(InactiveSubscribers::BATCH_SIZE, 1, 0),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribers::class, [
      'inactiveSubscribersController' => $controllerMock,
    ]);
    $task = new ScheduledTaskEntity();
    $task->setMeta(['max_subscriber_id' => 2001 /* 3 iterations of BATCH_SIZE in markInactiveSubscribers */]);
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    $worker->processTaskStrategy($task, microtime(true));

    expect($controllerMock->markInactiveSubscribers(5, 1000))->equals(0);
    expect($controllerMock->markActiveSubscribers(5, 1000))->equals(0);
  }

  public function testItCanStopDeactivationIfMarkInactiveSubscribersReturnsFalse() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::once(false),
      'markActiveSubscribers' => Stub\Expected::once(1),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $task = new ScheduledTaskEntity();

    $worker = $this->getServiceWithOverrides(InactiveSubscribers::class, [
      'inactiveSubscribersController' => $controllerMock,
    ]);
    $worker->processTaskStrategy($task, microtime(true));

    $meta = $task->getMeta();
    expect(isset($meta['last_subscriber_id']))->equals(false);
  }

  public function testThrowsAnExceptionWhenTimeIsOut() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::once(InactiveSubscribers::BATCH_SIZE),
      'markActiveSubscribers' => Stub\Expected::never(),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribers::class, [
      'inactiveSubscribersController' => $controllerMock,
    ]);

    $this->expectException(\Exception::class);
    $this->expectExceptionCode(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true) - $this->cronHelper->getDaemonExecutionLimit());
  }
}
