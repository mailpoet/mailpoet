<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscribersLifetimeEmailCountTest extends \MailPoetTest {

  /** @var SubscribersEmailCount */
  private $worker;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscribersEmailCount::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->newsletter = new NewsletterEntity();
    $this->newsletter->setSubject('Subject');
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($this->newsletter);
    $this->entityManager->flush();
  }

  public function testItDoesntWorkIfInactiveSubscribersIsDisabled() {
    $settings = SettingsController::getInstance();
    $settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $settings->set('deactivate_subscriber_after_inactive_days', 0);

    expect($this->worker->checkProcessingRequirements())->equals(false);
  }

  public function testItCalculatesTotalSubscribersEmailCountsOnFirstRun() {
    $subscriber1 = $this->createSubscriber('s1@email.com', 100);
    $this->createCompletedSendingTasksForSubscriber($subscriber1, 80, 90);
    $subscriber2 = $this->createSubscriber('s2@email.com', 90);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 8, 80);

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmailCount())->equals(80);
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2->getEmailCount())->equals(8);
  }

  public function testItStartsFromLastIdInTaskMeta() {
    $subscriber1 = $this->createSubscriber('s1@email.com', 100);
    $this->createCompletedSendingTasksForSubscriber($subscriber1, 80, 90);
    $subscriber2 = $this->createSubscriber('s2@email.com', 90);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 8, 80);

    $task = new ScheduledTaskEntity();
    $meta = ['highest_subscriber_id' => $subscriber2->getId(), 'last_subscriber_id' => $subscriber2->getId()];
    $task->setMeta($meta);
    $this->worker->processTaskStrategy($task, microtime(true));

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmailCount())->equals(0);
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2->getEmailCount())->equals(8);
  }

  public function testItWorksIfTaskMetaIsString() {
    // Test backwards fix for [MAILPOET-4282]
    $subscriber1 = $this->createSubscriber('s1@email.com', 100);
    $this->createCompletedSendingTasksForSubscriber($subscriber1, 80, 90);
    $subscriber2 = $this->createSubscriber('s2@email.com', 90);
    $this->createCompletedSendingTasksForSubscriber($subscriber2, 8, 80);

    $task = new ScheduledTaskEntity();
    $meta = ['highest_subscriber_id' => $subscriber2->getId(), 'last_subscriber_id' => $subscriber2->getId()];
    $task->setMeta($meta);
    $this->worker->processTaskStrategy($task, microtime(true));

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmailCount())->equals(0);
    $subscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2->getEmailCount())->equals(8);
  }

  public function testItUpdatesSubscribersEmailCountsAfterFirstRun() {
    $subscriber1 = $this->createSubscriber('s1@email.com', 100, SubscriberEntity::STATUS_SUBSCRIBED, 80);

    // create previous completed task
    $previousEmailCountsTask = $this->createRunningTask();
    $previousEmailCountsTask->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $previousEmailCountsTask->setCreatedAt(Carbon::now()->subDays(2));
    $previousEmailCountsTask->setScheduledAt(Carbon::now()->subDays(1));
    $previousEmailCountsTask->setUpdatedAt(Carbon::now()->subDays(1));
    $this->entityManager->persist($previousEmailCountsTask);
    $this->entityManager->flush();

    // Emails to be added on next run
    $this->createCompletedSendingTasksForSubscriber($subscriber1, 1, 2);

    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->entityManager->clear();
    $subscriber1 = $this->subscribersRepository->findOneById($subscriber1->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmailCount())->equals(81);

  }

  public function testItSchedulesNextRunWhenFinished() {
    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $task = $this->scheduledTasksRepository->findOneBy(
      ['type' => SubscribersEmailCount::TASK_TYPE, 'status' => ScheduledTaskEntity::STATUS_SCHEDULED]
    );

    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
    expect($task->getScheduledAt())->greaterThan(new Carbon());
  }

  private function createRunningTask(): ScheduledTaskEntity {
    return $this->scheduledTaskFactory->create(
      SubscribersEmailCount::TASK_TYPE,
      null,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }

  private function createSubscriber(
    string $email,
    int $createdDaysAgo = 0,
    string $status = SubscriberEntity::STATUS_SUBSCRIBED,
    int $emailCount = 0
  ): SubscriberEntity {
    $createdAt = (new Carbon())->subDays($createdDaysAgo);
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    $subscriber->setCreatedAt($createdAt);
    $subscriber->setEmailCount($emailCount);
    $this->entityManager->persist($subscriber);
    // we need to set lastSubscribeAt after persist due to LastSubscribedAtListener
    $subscriber->setLastSubscribedAt($createdAt);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createCompletedSendingTasksForSubscriber(SubscriberEntity $subscriber, int $numTasks = 1, int $processedDaysAgo = 0): void {
    for ($i = 0; $i < $numTasks; $i++) {
      [$task] = $this->createCompletedSendingTask($processedDaysAgo);
      $this->addSubscriberToTask($subscriber, $task);
    }
  }

  private function createCompletedSendingTask(int $processedDaysAgo = 0): array {
    $processedAt = (new Carbon())->subDays($processedDaysAgo)->addHours(2);
    $task = new ScheduledTaskEntity();
    $task->setType(Sending::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setCreatedAt($processedAt);
    $task->setProcessedAt($processedAt);
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($this->newsletter);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();
    return [$task, $queue];
  }

  private function addSubscriberToTask(
    SubscriberEntity $subscriber,
    ScheduledTaskEntity $task,
    int $daysAgo = 0
  ): ScheduledTaskSubscriberEntity {
    $createdAt = (new Carbon())->subDays($daysAgo);
    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $taskSubscriber->setCreatedAt($createdAt);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();
    return $taskSubscriber;
  }
}
