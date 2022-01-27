<?php

namespace MailPoet\Test\Migrations;

use Codeception\Stub;
use MailPoet\Config\Activator;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class MigrateDefaultInactiveSubscriberFrequencyTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settingsController;

  /** @var Activator */
  private $activator;

  public function _before() {
    parent::_before();
    $this->activator = $this->diContainer->get(Activator::class);
    $this->settingsController = $this->diContainer->get(SettingsController::class);
    $this->settingsController->delete('deactivate_subscriber_after_inactive_days');
  }

  public function testItDoesNotUpdateValuesOtherThanThePreviousDefault() {
    $nonDefaultOptions = [ '', '90', '365' ];
    foreach ($nonDefaultOptions as $option) {
      $this->settingsController->set('db_version', '3.78.0');
      $this->settingsController->set('deactivate_subscriber_after_inactive_days', $option);
      $this->activator->activate();
      $this->assertEquals($option, $this->settingsController->get('deactivate_subscriber_after_inactive_days'));
    }
  }

  public function testItDoesUpdatePreviousDefaultValue() {
    $this->settingsController->set('db_version', '3.78.0');
    $this->settingsController->set('deactivate_subscriber_after_inactive_days', '180');
    $this->activator->activate();
    $this->assertEquals('365', $this->settingsController->get('deactivate_subscriber_after_inactive_days'));
  }

  public function testItDoesNotRunForUnexpectedVersions() {
    $versions = ['3.78.1', '3.900.2', '4.8.0'];
    foreach ($versions as $version) {
      $this->settingsController->set('db_version', $version);
      $this->settingsController->set('deactivate_subscriber_after_inactive_days', '180');
      $this->assertEquals('180', $this->settingsController->get('deactivate_subscriber_after_inactive_days'));
      $this->activator->activate();
      $this->assertEquals('180', $this->settingsController->get('deactivate_subscriber_after_inactive_days'));
    }
  }

  public function testItDoesNotRunForNewInstalls() {
      $this->settingsController->delete('db_version');
      $this->activator->activate();
      $setting = $this->settingsController->get('deactivate_subscriber_after_inactive_days', 'not-set');
      $this->assertEquals('not-set', $setting);
  }

  public function testItCreatesInactiveSubscribersTaskIfOneNotAlreadyScheduled() {
    $currentTime = Carbon::now()->microsecond(0);

    /** @var WPFunctions $wpStub */
    $wpStub = Stub::make(new WPFunctions(), [
      'currentTime' => asCallable(function() use ($currentTime) {
        return $currentTime->getTimestamp();
      }),
    ]);
    WPFunctions::set($wpStub);

    // Double check there isn't already a task in the DB
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $shouldBeNull = $scheduledTasksRepository->findOneBy([
      'type' => InactiveSubscribers::TASK_TYPE,
    ]);
    $this->assertNull($shouldBeNull);

    // Run the migration
    $this->settingsController->set('db_version', '3.78.0');
    $this->settingsController->set('deactivate_subscriber_after_inactive_days', '180');
    $this->activator->activate();

    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $task = $scheduledTasksRepository->findOneBy([
      'type' => InactiveSubscribers::TASK_TYPE,
    ]);
    $this->assertNotNull($task);
    $this->assertEquals($currentTime->subMinute(), $task->getScheduledAt());
  }

  public function testItReschedulesScheduledInactiveSubscribersTask() {
    $currentTime = Carbon::now()->microsecond(0);

    /** @var WPFunctions $wpStub */
    $wpStub = Stub::make(new WPFunctions(), [
      'currentTime' => asCallable(function() use ($currentTime) {
        return $currentTime->getTimestamp();
      }),
    ]);
    WPFunctions::set($wpStub);
    $twoHoursFromNow = $currentTime->copy()->addHours(2);

    // Create existing task scheduled for the future
    $existingTask = new ScheduledTaskEntity();
    $existingTask->setType(InactiveSubscribers::TASK_TYPE);
    $existingTask->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $existingTask->setScheduledAt($twoHoursFromNow);
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $scheduledTasksRepository->persist($existingTask);
    $scheduledTasksRepository->flush();

    // Run the migration
    $this->settingsController->set('db_version', '3.78.0');
    $this->settingsController->set('deactivate_subscriber_after_inactive_days', '180');
    $this->activator->activate();

    $this->assertEquals($currentTime->subMinute(), $existingTask->getScheduledAt());
  }
}
