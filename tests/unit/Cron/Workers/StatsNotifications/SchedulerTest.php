<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SchedulerTest extends \MailPoetUnitTest {

  /** @var Scheduler */
  private $stats_notifications;

  /** @var SettingsController|\PHPUnit_Framework_MockObject_MockObject */
  private $settings;

  /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
  private $entityManager;

  /** @var StatsNotificationsRepository|\PHPUnit_Framework_MockObject_MockObject */
  private $repository;

  function _before() {
    parent::_before();
    $this->settings = $this->createMock(SettingsController::class);
    $this->entityManager = $this->createMock(EntityManager::class);
    $this->entityManager->method('flush');
    $this->repository = $this->createMock(StatsNotificationsRepository::class);
    $this->stats_notifications = new Scheduler(
      $this->settings,
      $this->entityManager,
      $this->repository
    );
  }

  function testShouldSchedule() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
        ['tracking.enabled', null, true],
      ]));

    $newsletter_id = 5;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
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
      ->method('findByNewsletterId')
      ->with($newsletter_id)
      ->willReturn([]);

    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldScheduleForNotificationHistory() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
        ['tracking.enabled', null, true],
      ]));

    $newsletter_id = 4;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
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
      ->method('findByNewsletterId')
      ->with($newsletter_id)
      ->willReturn([]);

    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldNotScheduleIfTrackingIsDisabled() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
        ['tracking.enabled', null, false],
      ]));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletter_id = 13;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldNotScheduleIfDisabled() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => false, 'address' => 'email@example.com']],
        ['tracking.enabled', null, true],
      ]));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletter_id = 6;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldNotScheduleIfSettingsMissing() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, []],
        ['tracking.enabled', null, true],
      ]));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletter_id = 7;

    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldNotScheduleIfEmailIsMissing() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true]],
        ['tracking.enabled', null, true],
      ]));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletter_id = 8;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldNotScheduleIfEmailIsEmpty() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => '']],
        ['tracking.enabled', null, true],
      ]));

    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletter_id = 9;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldNotScheduleIfAlreadyScheduled() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
        ['tracking.enabled', null, true],
      ]));

    $newsletter_id = 10;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);

    $this->repository
      ->expects($this->once())
      ->method('findByNewsletterId')
      ->with($newsletter_id)
      ->willReturn([new ScheduledTaskEntity()]);
    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $this->stats_notifications->schedule($newsletter);
  }

  function testShouldNotScheduleIfInvalidType() {
    $this->settings
      ->method('get')
      ->will($this->returnValueMap([
        [Worker::SETTINGS_KEY, null, ['enabled' => true, 'address' => 'email@example.com']],
        ['tracking.enabled', null, true],
      ]));
    $this->entityManager
      ->expects($this->never())
      ->method('persist');

    $newsletter_id = 11;
    $newsletter = new NewsletterEntity();
    $newsletter->setId($newsletter_id);
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $this->stats_notifications->schedule($newsletter);
  }

}
