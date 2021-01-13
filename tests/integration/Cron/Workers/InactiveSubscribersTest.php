<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class InactiveSubscribersTest extends \MailPoetTest {
  public $cronHelper;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    $this->settings = SettingsController::getInstance();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    $this->settings->set('tracking.enabled', true);
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

    $worker = new InactiveSubscribers($controllerMock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]), microtime(true));

    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findOne();

    assert($task instanceof ScheduledTask);
    expect($task)->isInstanceOf(ScheduledTask::class);
    expect($task->scheduledAt)->greaterThan(new Carbon());
  }

  public function testItDoesNotRunWhenTrackingIsDisabled() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 10);
    $this->settings->set('tracking.enabled', false);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::never(),
      'markActiveSubscribers' => Stub\Expected::never(),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = new InactiveSubscribers($controllerMock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]), microtime(true));
  }

  public function testItSchedulesNextRunWhenFinished() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::once(1),
      'markActiveSubscribers' => Stub\Expected::once(1),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = new InactiveSubscribers($controllerMock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]), microtime(true));

    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findOne();

    assert($task instanceof ScheduledTask);
    expect($task)->isInstanceOf(ScheduledTask::class);
    expect($task->scheduledAt)->greaterThan(new Carbon());
  }

  public function testRunBatchesUntilItIsFinished() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub::consecutive(InactiveSubscribers::BATCH_SIZE, InactiveSubscribers::BATCH_SIZE, 1, 'ok'),
      'markActiveSubscribers' => Stub::consecutive(InactiveSubscribers::BATCH_SIZE, 1, 'ok'),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = new InactiveSubscribers($controllerMock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate(
      ['meta' => ['max_subscriber_id' => 2001 /* 3 iterations of BATCH_SIZE in markInactiveSubscribers */]]
    ), microtime(true));

    expect($controllerMock->markInactiveSubscribers(5, 1000))->equals('ok');
    expect($controllerMock->markActiveSubscribers(5, 1000))->equals('ok');
  }

  public function testItCanStopDeactivationIfMarkInactiveSubscribersReturnsFalse() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controllerMock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::once(false),
      'markActiveSubscribers' => Stub\Expected::once(1),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $task = ScheduledTask::createOrUpdate([]);

    $worker = new InactiveSubscribers($controllerMock, $this->settings);
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

    $worker = new InactiveSubscribers($controllerMock, $this->settings);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Maximum execution time has been reached.');
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]), microtime(true) - $this->cronHelper->getDaemonExecutionLimit());
  }
}
