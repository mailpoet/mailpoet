<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;

class SubscribersLastEngagementTest extends \MailPoetTest {
  /** @var SubscribersLastEngagement */
  private $worker;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscribersLastEngagement::class);
  }

  public function testItCanSetLastEngagementFromOpens() {
    $openTime = new Carbon('2021-08-10 12:13:14');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($openTime, $newsletter, $subscriber);

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($openTime);
  }

  public function testItCanSetLastEngagementFromClicks() {
    $clickTime = new Carbon('2021-08-10 13:14:15');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($clickTime, $newsletter, $subscriber);

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($clickTime);
  }

  public function testItPicksLatestTimeFromClick() {
    $openTime = new Carbon('2021-08-10 12:13:14');
    $clickTime = new Carbon('2021-08-10 13:14:15');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($openTime, $newsletter, $subscriber);
    $this->createClick($clickTime, $newsletter, $subscriber);

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($clickTime);
  }

  public function testItPicksLatestTimeFromOpen() {
    $openTime = new Carbon('2021-08-10 14:13:14');
    $clickTime = new Carbon('2021-08-10 13:14:15');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($openTime, $newsletter, $subscriber);
    $this->createClick($clickTime, $newsletter, $subscriber);

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($openTime);
  }

  public function testItKeepsNullIfNoTimeFound() {
    $subscriber = $this->createSubscriber();
    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->null();
  }

  public function testItReturnsTrueWhenCompleted() {
    $this->createSubscriber();
    $task = new ScheduledTaskEntity();
    $result = $this->worker->processTaskStrategy($task, microtime(true));
    expect($result)->true();
  }

  public function testItInterruptsProcessIfExecutionLimitReachedIsReachedAndFinishesOnSecondRun() {
    $this->createSubscriber();
    $exception = null;
    $task = new ScheduledTaskEntity();
    try {
      $this->worker->processTaskStrategy($task, 0);
    } catch (\Exception $e) {
      $exception = $e;
    }
    $this->assertInstanceOf(\Exception::class, $exception);
    expect($exception->getMessage())->startsWith('The maximum execution time');
    $result = $this->worker->processTaskStrategy($task, microtime(true));
    expect($result)->true();
  }

  public function testItProcessMultipleBatches() {
    $subscriberInFirstBatch = $this->createSubscriber('last-engagement1@test.com');
    $this->createSubscriber('last-engagement2@test.com');
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("UPDATE $subscribersTable SET id = 1001 WHERE email='last-engagement2@test.com'");
    $subscriberInSecondBatch = $this->entityManager->find(SubscriberEntity::class, 1001);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberInSecondBatch);
    $firstOpenTime = new Carbon('2021-08-10 12:13:14');
    $secondOpenTime = new Carbon('2021-08-10 14:13:14');
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($firstOpenTime, $newsletter, $subscriberInFirstBatch);
    $this->createOpen($secondOpenTime, $newsletter, $subscriberInSecondBatch);

    $task = new ScheduledTaskEntity();
    $result = $this->worker->processTaskStrategy($task, microtime(true));
    expect($result)->true();
    $this->entityManager->refresh($subscriberInFirstBatch);
    $this->entityManager->refresh($subscriberInSecondBatch);
    expect($subscriberInFirstBatch->getLastEngagementAt())->equals($firstOpenTime);
    expect($subscriberInSecondBatch->getLastEngagementAt())->equals($secondOpenTime);
    expect($task->getMeta())->equals(['nextId' => 2001]);
  }

  private function createSubscriber($email = null): SubscriberEntity {
    if ($email === null) {
      $email = sprintf("last-engagement-%s@test.com", $this->tester->uniqueId());
    }
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createOpen(Carbon $time, NewsletterEntity $newsletter, SubscriberEntity $subscriber): StatisticsOpenEntity {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $open->setCreatedAt($time);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }

  private function createClick(Carbon $time, NewsletterEntity $newsletter, SubscriberEntity $subscriber): StatisticsClickEntity {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $link = new NewsletterLinkEntity($newsletter, $queue, 'http://example.com', 'hash123');
    $this->entityManager->persist($link);
    $click = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
    $click->setCreatedAt($time);
    $this->entityManager->persist($click);
    $this->entityManager->flush();
    return $click;
  }

  private function createSentNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $task = new ScheduledTaskEntity();
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setNewsletterRenderedBody(['html' => 'html', 'text' => 'text']);
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('subject');
    $newsletter->setSentAt(Carbon::now()->subMonth());
    $newsletter->getQueues()->add($queue);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }
}
