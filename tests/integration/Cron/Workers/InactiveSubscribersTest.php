<?php
namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\InactiveSubscribersController;

class InactiveSubscribersTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    $this->settings = new SettingsController();
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    $this->settings->set('tracking.enabled', true);
    parent::_before();
  }

  function testItReactivateInactiveSubscribersWhenIntervalIsSetToNever() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 0);
    $controller_mock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::never(),
      'markActiveSubscribers' => Stub\Expected::never(),
      'reactivateInactiveSubscribers' => Stub\Expected::once(),
    ], $this);

    $worker = new InactiveSubscribers($controller_mock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));

    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findOne();

    expect($task)->isInstanceOf(ScheduledTask::class);
    expect($task->scheduled_at)->greaterThan(new Carbon());
  }

  function testItDoesNotRunWhenTrackingIsDisabled() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 10);
    $this->settings->set('tracking.enabled', false);
    $controller_mock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::never(),
      'markActiveSubscribers' => Stub\Expected::never(),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = new InactiveSubscribers($controller_mock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));
  }

  function testItSchedulesNextRunWhenFinished() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controller_mock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::once(1),
      'markActiveSubscribers' => Stub\Expected::once(1),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = new InactiveSubscribers($controller_mock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));

    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findOne();

    expect($task)->isInstanceOf(ScheduledTask::class);
    expect($task->scheduled_at)->greaterThan(new Carbon());
  }

  function testRunBatchesUntilItIsFinished() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controller_mock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub::consecutive(InactiveSubscribers::BATCH_SIZE, InactiveSubscribers::BATCH_SIZE, 1, 'ok'),
      'markActiveSubscribers' => Stub::consecutive(InactiveSubscribers::BATCH_SIZE, 1, 'ok'),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = new InactiveSubscribers($controller_mock, $this->settings);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));

    expect($controller_mock->markInactiveSubscribers(5, 1000))->equals('ok');
    expect($controller_mock->markActiveSubscribers(5, 1000))->equals('ok');
  }

  function testThrowsAnExceptionWhenTimeIsOut() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $controller_mock = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Stub\Expected::once(InactiveSubscribers::BATCH_SIZE),
      'markActiveSubscribers' => Stub\Expected::never(),
      'reactivateInactiveSubscribers' => Stub\Expected::never(),
    ], $this);

    $worker = new InactiveSubscribers($controller_mock, $this->settings, microtime(true) - (CronHelper::DAEMON_EXECUTION_LIMIT - 1));
    sleep(1);
    $this->setExpectedException(\Exception::class, 'Maximum execution time has been reached.');
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));
  }
}
