<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Cron\Workers\InactiveSubscribersMaintenance;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260623_120000_App_Test extends \MailPoetTest {
  private const OLD_EMAIL_COUNT_TYPE = 'subscribers_email_count';
  private const OLD_INACTIVE_TYPE = 'inactive_subscribers';

  /** @var Migration_20260623_120000_App */
  private $migration;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260623_120000_App($this->diContainer);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->delete(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING);
    $this->truncateEntity(ScheduledTaskEntity::class);
  }

  public function testItDeletesPendingOldTasksButKeepsCompletedHistory(): void {
    $scheduledEmailCount = $this->createTask(self::OLD_EMAIL_COUNT_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $runningInactive = $this->createTask(self::OLD_INACTIVE_TYPE, null);
    $completedInactive = $this->createTask(self::OLD_INACTIVE_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);

    $this->migration->run();
    $this->entityManager->clear();

    $this->assertNull($this->scheduledTasksRepository->findOneById((int)$scheduledEmailCount->getId()));
    $this->assertNull($this->scheduledTasksRepository->findOneById((int)$runningInactive->getId()));
    $this->assertNotNull($this->scheduledTasksRepository->findOneById((int)$completedInactive->getId()));
  }

  public function testItSeedsEmailCountBaselineFromLastCompletedLegacyTask(): void {
    $this->createTask(self::OLD_EMAIL_COUNT_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, (new Carbon())->subDays(10));
    $latestScheduledAt = (new Carbon())->subDays(3);
    $this->createTask(self::OLD_EMAIL_COUNT_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, $latestScheduledAt);

    $this->migration->run();

    $stored = $this->settings->get(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING);
    $this->assertIsString($stored);
    verify(Carbon::parse($stored)->format('Y-m-d H:i:s'))->equals($latestScheduledAt->format('Y-m-d H:i:s'));
  }

  public function testItDoesNotSeedEmailCountBaselineWithoutLegacyTask(): void {
    $this->migration->run();

    verify($this->settings->get(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING))->null();
  }

  public function testItSchedulesTheMaintenanceTask(): void {
    $this->migration->run();
    $this->entityManager->clear();

    $task = $this->scheduledTasksRepository->findOneBy(['type' => InactiveSubscribersMaintenance::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testItDoesNotScheduleDuplicateMaintenanceTasksWhenRunTwice(): void {
    $this->migration->run();
    $this->migration->run();
    $this->entityManager->clear();

    $tasks = $this->scheduledTasksRepository->findBy(['type' => InactiveSubscribersMaintenance::TASK_TYPE]);
    verify(count($tasks))->equals(1);
  }

  private function createTask(string $type, ?string $status, ?\DateTimeInterface $scheduledAt = null): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus($status);
    $task->setScheduledAt($scheduledAt ?? Carbon::now());
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    return $task;
  }
}
