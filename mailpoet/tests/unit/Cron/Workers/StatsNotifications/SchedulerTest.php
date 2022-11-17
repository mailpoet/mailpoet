<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;

class SchedulerTest extends \MailPoetUnitTest {

  /** @var Scheduler */
  private $statsNotifications;

  /** @var SettingsController& MockObject */
  private $settings;

  /** @var EntityManager & MockObject */
  private $entityManager;

  /** @var StatsNotificationsRepository & MockObject */
  private $repository;

  /** @var TrackingConfig & MockObject */
  private $trackingConfig;

  public function _before() {
    parent::_before();
    $this->settings = $this->createMock(SettingsController::class);
    $this->trackingConfig = $this->createMock(TrackingConfig::class);
    $this->entityManager = $this->createMock(EntityManager::class);
    $this->entityManager->method('flush');
    $this->repository = $this->createMock(StatsNotificationsRepository::class);
    $this->statsNotifications = new Scheduler(
      $this->settings,
      $this->entityManager,
      $this->repository,
      $this->trackingConfig
    );
  }

  public function testShouldSchedule() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $newsletterId = 5;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->entityManager
      ->expects($this->exactly(2))
      ->method('persist');
    $this->entityManager
      ->expects($this->at(0))
      ->method('persist')
      ->with($this->isInstanceOf(ScheduledTaskEntity::class));
    $this->entityManager
      ->expects($this->at(1))
      ->method('flush');
    $this->entityManager
      ->expects($this->at(2))
      ->method('persist')
      ->with($this->isInstanceOf(StatsNotificationEntity::class));
    $this->entityManager
      ->expects($this->at(3))
      ->method('flush');

    $this->repository
      ->expects($this->once())
      ->method('findOneByNewsletterId')
      ->with($newsletterId)
      ->willReturn([]);

    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldScheduleForNotificationHistory() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $newsletterId = 4;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);

    $this->entityManager
      ->expects($this->exactly(2))
      ->method('persist');
    $this->entityManager
      ->expects($this->at(0))
      ->method('persist')
      ->with($this->isInstanceOf(ScheduledTaskEntity::class));
    $this->entityManager
      ->expects($this->at(1))
      ->method('flush');
    $this->entityManager
      ->expects($this->at(2))
      ->method('persist')
      ->with($this->isInstanceOf(StatsNotificationEntity::class));
    $this->entityManager
      ->expects($this->at(3))
      ->method('flush');

    $this->repository
      ->expects($this->once())
      ->method('findOneByNewsletterId')
      ->with($newsletterId)
      ->willReturn([]);

    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldNotScheduleIfTrackingIsDisabled() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(false));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletterId = 13;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldNotScheduleIfDisabled() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => false, 'address' => 'email@example.com']],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletterId = 6;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldNotScheduleIfSettingsMissing() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, []],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletterId = 7;

    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldNotScheduleIfEmailIsMissing() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true]],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletterId = 8;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldNotScheduleIfEmailIsEmpty() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => '']],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletterId = 9;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldNotScheduleIfAlreadyScheduled() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
      ]));
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $newsletterId = 10;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->repository
      ->expects($this->once())
      ->method('findOneByNewsletterId')
      ->with($newsletterId)
      ->willReturn(new StatsNotificationEntity(new NewsletterEntity(), new ScheduledTaskEntity()));
    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $this->statsNotifications->schedule($newsletter);
  }

  public function testShouldNotScheduleIfInvalidType() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
      ]));
    $this->entityManager
      ->expects($this->never())
      ->method('persist');
    $this->trackingConfig
      ->method('isEmailTrackingEnabled')
      ->will($this->returnValue(true));

    $newsletterId = 11;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletterId);
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $this->statsNotifications->schedule($newsletter);
  }
}
