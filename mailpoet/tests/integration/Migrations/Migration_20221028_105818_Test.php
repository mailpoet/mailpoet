<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use Codeception\Stub;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Util\Notices\ChangedTrackingNotice;
use MailPoet\WP\Functions;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20221028_105818_Test extends \MailPoetTest {
  /** @var Migration_20221028_105818 */
  private $migration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20221028_105818($this->diContainer);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  public function testItMigratesEmailMachineOpensFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $data = ['newsletter_id' => '1'];
    $id = $this->createSegmentFilter(EmailAction::ACTION_OPENED, $data, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filter = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    expect($filter->getFilterData()->getAction())->equals(EmailAction::ACTION_OPENED);
    expect($filter->getFilterData()->getData())->equals(['newsletters' => [1], 'operator' => 'any']);
  }

  public function testItMigratesEmailOpensFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $data = ['newsletter_id' => '1'];
    $id = $this->createSegmentFilter(EmailAction::ACTION_MACHINE_OPENED, $data, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filter = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    expect($filter->getFilterData()->getAction())->equals(EmailAction::ACTION_MACHINE_OPENED);
    expect($filter->getFilterData()->getData())->equals(['newsletters' => [1], 'operator' => 'any']);
  }

  public function testItMigratesEmailNotOpenedFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $data = ['newsletter_id' => '1'];
    $id = $this->createSegmentFilter(EmailAction::ACTION_NOT_OPENED, $data, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filter = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    expect($filter->getFilterData()->getAction())->equals(EmailAction::ACTION_OPENED);
    expect($filter->getFilterData()->getData())->equals(['newsletters' => [1], 'operator' => 'none']);
  }

  public function testItMigratesEmailClicksFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $dataNoLink = ['newsletter_id' => '1'];
    $id1 = $this->createSegmentFilter(EmailAction::ACTION_CLICKED, $dataNoLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $dataWithLink = ['newsletter_id' => '1', 'link_id' => '2'];
    $id2 = $this->createSegmentFilter(EmailAction::ACTION_CLICKED, $dataWithLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filterNoLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id1);
    $filterLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id2);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterNoLink);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterLink);
    expect($filterNoLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    expect($filterNoLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'any', 'link_ids' => []]);
    expect($filterLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    expect($filterLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'any', 'link_ids' => [2]]);
  }

  public function testItMigratesEmailNotClickedFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $dataNoLink = ['newsletter_id' => '1'];
    $id1 = $this->createSegmentFilter(EmailAction::ACTION_NOT_CLICKED, $dataNoLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $dataWithLink = ['newsletter_id' => '1', 'link_id' => '2'];
    $id2 = $this->createSegmentFilter(EmailAction::ACTION_NOT_CLICKED, $dataWithLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filterNoLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id1);
    $filterLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id2);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterNoLink);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterLink);
    expect($filterNoLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    expect($filterNoLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'none', 'link_ids' => []]);
    expect($filterLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    expect($filterLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'none', 'link_ids' => [2]]);
  }

  public function testItDoesNotUpdateInactiveSubscribersFrequencyValuesOtherThanThePreviousDefault() {
    $this->settings->delete('deactivate_subscriber_after_inactive_days');
    $nonDefaultOptions = [ '', '90', '365' ];
    foreach ($nonDefaultOptions as $option) {
      $this->settings->set('db_version', '3.78.0');
      $this->settings->set('deactivate_subscriber_after_inactive_days', $option);
      $this->migration->run();
      $this->assertEquals($option, $this->settings->get('deactivate_subscriber_after_inactive_days'));
    }
  }

  public function testItDoesUpdateInactiveSubscribersFrequencyPreviousDefaultValue() {
    $this->settings->delete('deactivate_subscriber_after_inactive_days');
    $this->settings->set('db_version', '3.78.0');
    $this->settings->set('deactivate_subscriber_after_inactive_days', '180');
    $this->migration->run();
    $this->assertEquals('365', $this->settings->get('deactivate_subscriber_after_inactive_days'));
  }

  public function testItDoesNotRunInactiveSubscribersFrequencyMigrationForUnexpectedVersions() {
    $this->settings->delete('deactivate_subscriber_after_inactive_days');
    $versions = ['3.78.1', '3.900.2', '4.8.0'];
    foreach ($versions as $version) {
      $this->settings->set('db_version', $version);
      $this->settings->set('deactivate_subscriber_after_inactive_days', '180');
      $this->assertEquals('180', $this->settings->get('deactivate_subscriber_after_inactive_days'));
      $this->migration->run();
      $this->assertEquals('180', $this->settings->get('deactivate_subscriber_after_inactive_days'));
    }
  }

  public function testItDoesNotRunInactiveSubscribersFrequencyMigrationForNewInstalls() {
    $this->settings->delete('deactivate_subscriber_after_inactive_days');
    $this->settings->delete('db_version');
    $this->migration->run();
    $setting = $this->settings->get('deactivate_subscriber_after_inactive_days', 'not-set');
    $this->assertEquals('not-set', $setting);
  }

  public function testItCreatesInactiveSubscribersTaskIfOneNotAlreadyScheduled() {
    $this->settings->delete('deactivate_subscriber_after_inactive_days');
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
    $this->settings->set('db_version', '3.78.0');
    $this->settings->set('deactivate_subscriber_after_inactive_days', '180');
    $this->migration->run();

    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $task = $scheduledTasksRepository->findOneBy([
      'type' => InactiveSubscribers::TASK_TYPE,
    ]);
    $this->assertNotNull($task);
    $this->assertEquals($currentTime->subMinute(), $task->getScheduledAt());
  }

  public function testItReschedulesScheduledInactiveSubscribersTask() {
    $this->settings->delete('deactivate_subscriber_after_inactive_days');
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
    $this->settings->set('db_version', '3.78.0');
    $this->settings->set('deactivate_subscriber_after_inactive_days', '180');
    $this->migration->run();

    $this->assertEquals($currentTime->subMinute(), $existingTask->getScheduledAt());
  }

  public function testItMigratesTrackingSettings() {
    $wp = $this->diContainer->get(Functions::class);
    $wp->deleteTransient(ChangedTrackingNotice::OPTION_NAME);
    // WooCommerce disabled and Tracking enabled
    $this->settings->set('db_version', '3.74.1');
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', null);
    $this->migration->run();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce disabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', null);
    $this->migration->run();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_BASIC);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie enabled and Tracking enabled
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "1");
    $this->migration->run();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie disabled and Tracking enabled
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "");
    $this->migration->run();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_PARTIAL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie disabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "");
    $this->migration->run();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_BASIC);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie enabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "1");
    $this->migration->run();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->equals(true);
  }

  private function createSegmentFilter(string $action, array $data, string $type, $segmentId = 1): int {
    $filterTable = $this->entityManager->getClassMetadata(DynamicSegmentFilterEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeQuery(
      "INSERT into $filterTable (segment_id, filter_data, action, filter_type)
     VALUES (:segment, :filter_data, :action, :filter_type)",
      [
        'segment' => $segmentId,
        'action' => $action,
        'filter_data' => \serialize($data),
        'filter_type' => $type,
      ]
    );
    return (int)$this->entityManager->getConnection()->lastInsertId();
  }
}
