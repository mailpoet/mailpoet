<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Carbon\CarbonImmutable;

class StatisticsOpensRepositoryTest extends \MailPoetTest {
  /** @var StatisticsOpensRepository */
  private $repository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(StatisticsOpensRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function testItLeavesScoreWhenNoData() {
    $subscriber = $this->createSubscriber();
    $segment = $this->createSegment();
    $this->createSubscriberSegment($subscriber, $segment);
    $this->entityManager->flush();
    $this->repository->recalculateSubscriberScore($subscriber);
    $this->repository->recalculateSegmentScore($segment);
    $newSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getEngagementScore())->null();
    expect($newSubscriber->getEngagementScoreUpdatedAt())->notNull();
    $newSegment = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $newSegment);
    expect($newSegment->getAverageEngagementScore())->null();
    expect($newSegment->getAverageEngagementScoreUpdatedAt())->notNull();
  }

  public function testItUpdatesScoreTimeWhenNotEnoughNewsletters() {
    $subscriber = $this->createSubscriber();
    $segment = $this->createSegment();
    $this->createSubscriberSegment($subscriber, $segment);
    $subscriber->setEngagementScoreUpdatedAt((new CarbonImmutable())->subDays(4));
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber);
    $this->entityManager->flush();
    $this->repository->recalculateSubscriberScore($subscriber);
    $this->repository->recalculateSegmentScore($segment);
    $newSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getEngagementScore())->null();
    expect($newSubscriber->getEngagementScoreUpdatedAt())->notNull();
    $updated = $newSubscriber->getEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();
    $newSegment = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $newSegment);
    expect($newSegment->getAverageEngagementScore())->null();
    expect($newSegment->getAverageEngagementScoreUpdatedAt())->notNull();
    $updated = $newSegment->getAverageEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $averageScoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($averageScoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();
  }

  public function testItUpdatesScore() {
    $subscriber = $this->createSubscriber();
    $segment = $this->createSegment();
    $this->createSubscriberSegment($subscriber, $segment);
    $subscriber->setEngagementScoreUpdatedAt((new CarbonImmutable())->subDays(4));
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber);
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber);
    $statisticsNewsletter = $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber);
    $newsletter = $statisticsNewsletter->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $newsletter->getQueues()->first();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();

    $this->repository->recalculateSubscriberScore($subscriber);
    $this->repository->recalculateSegmentScore($segment);

    $newSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getEngagementScore())->equals(33, 1);
    expect($newSubscriber->getEngagementScoreUpdatedAt())->notNull();
    $updated = $newSubscriber->getEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();

    $newSegment = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $newSegment);
    expect($newSegment->getAverageEngagementScore())->equals(33, 1);
    expect($newSegment->getAverageEngagementScoreUpdatedAt())->notNull();
    $updated = $newSegment->getAverageEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();
  }

  public function testItUpdatesScoreOnlyForTwelveMonths(): void {
    $subscriber = $this->createSubscriber();
    $subscriber2 = $this->createSubscriber();
    $segment = $this->createSegment();
    $this->createSubscriberSegment($subscriber, $segment);
    $this->createSubscriberSegment($subscriber2, $segment);
    $subscriber->setEngagementScoreUpdatedAt((new CarbonImmutable())->subDays(4));
    $sentAt = (new Carbon())->subMonths(13);
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber, $sentAt);
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber);
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber);
    $statisticsNewsletter = $this->createStatisticsNewsletter($this->createNewsletter($sentAt), $subscriber);
    $newsletter = $statisticsNewsletter->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $newsletter->getQueues()->first();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    $subscriber2->setEngagementScoreUpdatedAt((new CarbonImmutable())->subDays(4));
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber2);
    $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber2);
    $statisticsNewsletter = $this->createStatisticsNewsletter($this->createNewsletter(), $subscriber2);
    $newsletter = $statisticsNewsletter->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $newsletter->getQueues()->first();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber2);
    $this->entityManager->persist($open);
    $this->entityManager->flush();

    $this->repository->recalculateSubscriberScore($subscriber);
    $this->repository->recalculateSubscriberScore($subscriber2);
    $this->repository->recalculateSegmentScore($segment);

    $newSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getEngagementScore())->equals(0.0);
    expect($newSubscriber->getEngagementScoreUpdatedAt())->notNull();
    $updated = $newSubscriber->getEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();

    $newSubscriber2 = $this->subscribersRepository->findOneById($subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber2);
    expect($newSubscriber2->getEngagementScore())->equals(33, 1);
    expect($newSubscriber2->getEngagementScoreUpdatedAt())->notNull();
    $updated = $newSubscriber2->getEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();

    $newSegment = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $newSegment);
    expect($newSegment->getAverageEngagementScore())->equals(16.6, 0.1);
    expect($newSegment->getAverageEngagementScoreUpdatedAt())->notNull();
    $updated = $newSegment->getAverageEngagementScoreUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    $scoreUpdatedAt = new CarbonImmutable($updated->format('Y-m-d H:i:s'));
    expect($scoreUpdatedAt->isAfter((new CarbonImmutable())->subMinutes(5)))->true();
  }

  public function testForWooCommerce() {
    $subscriber = $this->createSubscriber();
    $subscriber->setEngagementScoreUpdatedAt((new CarbonImmutable())->subDays(4));
    $this->createStatisticsNewsletter($this->createWooNewsletter(), $subscriber);
    $this->createStatisticsNewsletter($this->createWooNewsletter(), $subscriber);
    $statisticsNewsletter = $this->createStatisticsNewsletter($this->createWooNewsletter(), $subscriber);
    $newsletter = $statisticsNewsletter->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $newsletter->getQueues()->first();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
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

  private function createWooNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Newsletter');
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createSegment(): SegmentEntity {
    $segment = new SegmentEntity('Segment', NewsletterEntity::TYPE_STANDARD, 'description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function createSubscriberSegment(SubscriberEntity $subscriber, SegmentEntity $segment): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }

  private function createNewsletter(?Carbon $sentAt = null): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Newsletter');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSentAt($sentAt ?: new \DateTime());
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createStatisticsNewsletter(
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber,
    ?Carbon $sentAt = null
  ): StatisticsNewsletterEntity {
    $task = $this->createSendingTask($subscriber);
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);
    $statisticsNewsletter = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $statisticsNewsletter->setSentAt($sentAt ?: new \DateTime());
    $this->entityManager->persist($statisticsNewsletter);
    return $statisticsNewsletter;
  }

  private function createSendingTask(SubscriberEntity $subscriber): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(Sending::TASK_TYPE);
    $this->entityManager->persist($task);
    $sub = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($sub);
    return $task;
  }
}
