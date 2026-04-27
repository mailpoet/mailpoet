<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\UnconfirmedSubscribersCleanup;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class UnconfirmedSubscribersCleanupTest extends \MailPoetTest {
  /** @var UnconfirmedSubscribersCleanup */
  private $worker;

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(UnconfirmedSubscribersCleanup::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function _after() {
    Carbon::setTestNow();
    parent::_after();
  }

  public function testItDoesNothingWhenSettingIsDisabled(): void {
    $this->settings->set('delete_unconfirmed_subscribers_after_days', '');
    $subscriber = $this->createOldUnconfirmedSubscriber('disabled-cleanup@example.com');

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    verify($this->subscribersRepository->findOneById($subscriber->getId()))->notNull();
  }

  public function testItDoesNothingWhenSettingIsInvalid(): void {
    $this->settings->set('delete_unconfirmed_subscribers_after_days', '7');
    $subscriber = $this->createOldUnconfirmedSubscriber('invalid-cleanup@example.com');

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    verify($this->subscribersRepository->findOneById($subscriber->getId()))->notNull();
  }

  public function testItDeletesEligibleSubscribersWhenEnabled(): void {
    $this->settings->set('delete_unconfirmed_subscribers_after_days', '30');
    $eligible = $this->createOldUnconfirmedSubscriber('enabled-cleanup@example.com');
    $recent = (new SubscriberFactory())
      ->withEmail('recent-cleanup@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt(Carbon::now()->subDays(10))
      ->create();

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->entityManager->clear();

    verify($this->subscribersRepository->findOneById($eligible->getId()))->null();
    verify($this->subscribersRepository->findOneById($recent->getId()))->notNull();
  }

  public function testItSchedulesNextRunWithExplicitDailyCadence(): void {
    Carbon::setTestNow(Carbon::create(2026, 4, 27, 10, 0, 0));
    $this->settings->set('delete_unconfirmed_subscribers_after_days', '');

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $task = $this->scheduledTasksRepository->findOneBy([
      'type' => UnconfirmedSubscribersCleanup::TASK_TYPE,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->assertInstanceOf(\DateTimeInterface::class, $task->getScheduledAt());
    verify($task->getScheduledAt()->format('Y-m-d H:i:s'))->equals('2026-04-28 10:00:00');
  }

  private function createOldUnconfirmedSubscriber(string $email): SubscriberEntity {
    return (new SubscriberFactory())
      ->withEmail($email)
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt(Carbon::now()->subDays(31))
      ->create();
  }
}
