<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoetVendor\Carbon\CarbonImmutable;

class StatisticsOpensRepositoryTest extends \MailPoetTest {
  /** @var StatisticsOpensRepository */
  private $repository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(StatisticsOpensRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItLeavesScoreWhenNoData() {
    $subscriber = $this->createSubscriber();
    $this->entityManager->flush();
    $this->repository->recalculateSubscriberScore($subscriber);
    $newSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getEngagementScore())->null();
    expect($newSubscriber->getEngagementScoreUpdatedAt())->notNull();
  }

  public function testItUpdatesScoreTimeWhenNotEnoughNewsletters() {
    $subscriber = $this->createSubscriber();
    $subscriber->setEngagementScoreUpdatedAt((new CarbonImmutable())->subDays(4));
    $this->createSendingTask($subscriber);
    $this->entityManager->flush();
    $this->repository->recalculateSubscriberScore($subscriber);
    $newSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getEngagementScore())->null();
    expect($newSubscriber->getEngagementScoreUpdatedAt())->notNull();
    $updated = $newSubscriber->getEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();
  }

  public function testItUpdatesScore() {
    $subscriber = $this->createSubscriber();
    $subscriber->setEngagementScoreUpdatedAt((new CarbonImmutable())->subDays(4));
    $this->createSendingTask($subscriber);
    $this->createSendingTask($subscriber);
    $task = $this->createSendingTask($subscriber);
    $newsletter = new NewsletterEntity();
    $this->entityManager->persist($newsletter);
    $queue = new SendingQueueEntity();
    $this->entityManager->persist($queue);
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $newsletter->getQueues()->add($queue);
    $newsletter->setSubject('newsletter 1');
    $newsletter->setStatus('sent');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();

    $this->repository->recalculateSubscriberScore($subscriber);

    $newSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getEngagementScore())->equals(33, 1);
    expect($newSubscriber->getEngagementScoreUpdatedAt())->notNull();
    $updated = $newSubscriber->getEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail('subscriber' . rand(0, 10000) . '@example.com');
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  private function createSendingTask(SubscriberEntity $subscriber): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(Sending::TASK_TYPE);
    $this->entityManager->persist($task);
    $sub = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($sub);
    return $task;
  }

  private function cleanup(): void {
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
  }
}
