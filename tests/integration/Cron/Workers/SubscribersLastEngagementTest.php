<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\ScheduledTask;
use MailPoetVendor\Carbon\Carbon;

class SubscribersLastEngagementTest extends \MailPoetTest {
  /** @var SubscribersLastEngagement */
  private $worker;

  /** @var array */
  private $orderIds = [];

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscribersLastEngagement::class);
    $this->orderIds = [];
  }

  public function testItCanSetLastEngagementFromOpens() {
    $openTime = new Carbon('2021-08-10 12:13:14');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($openTime, $newsletter, $subscriber);

    $this->worker->processTaskStrategy(ScheduledTask::create(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($openTime);
  }

  public function testItCanSetLastEngagementFromClicks() {
    $clickTime = new Carbon('2021-08-10 13:14:15');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($clickTime, $newsletter, $subscriber);

    $this->worker->processTaskStrategy(ScheduledTask::create(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($clickTime);
  }

  public function testItCanSetLastEngagementFromWooOrder() {
    $orderTime = new Carbon('2021-08-10 16:17:18');
    $subscriber = $this->createSubscriber();
    $this->createWooOrder($orderTime, $subscriber->getEmail());

    $this->worker->processTaskStrategy(ScheduledTask::create(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($orderTime);
  }

  public function testItPicksLatestTimeFromClick() {
    $openTime = new Carbon('2021-08-10 12:13:14');
    $clickTime = new Carbon('2021-08-10 13:14:15');
    $wooOrderTime = new Carbon('2021-08-10 12:14:15');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($openTime, $newsletter, $subscriber);
    $this->createClick($clickTime, $newsletter, $subscriber);
    $this->createWooOrder($wooOrderTime, $subscriber->getEmail());

    $this->worker->processTaskStrategy(ScheduledTask::create(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($clickTime);
  }

  public function testItPicksLatestTimeFromOrder() {
    $openTime = new Carbon('2021-08-10 12:13:14');
    $clickTime = new Carbon('2021-08-10 13:14:15');
    $wooOrderTime = new Carbon('2021-08-10 14:14:15');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($openTime, $newsletter, $subscriber);
    $this->createClick($clickTime, $newsletter, $subscriber);
    $this->createWooOrder($wooOrderTime, $subscriber->getEmail());

    $this->worker->processTaskStrategy(ScheduledTask::create(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($wooOrderTime);
  }

  public function testItPicksLatestTimeFromOpen() {
    $openTime = new Carbon('2021-08-10 14:13:14');
    $clickTime = new Carbon('2021-08-10 13:14:15');
    $wooOrderTime = new Carbon('2021-08-10 11:14:15');
    $subscriber = $this->createSubscriber();
    $newsletter = $this->createSentNewsletter();
    $this->createOpen($openTime, $newsletter, $subscriber);
    $this->createClick($clickTime, $newsletter, $subscriber);
    $this->createWooOrder($wooOrderTime, $subscriber->getEmail());

    $this->worker->processTaskStrategy(ScheduledTask::create(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($openTime);
  }

  public function testItKeepsNullIfNoTimeFound() {
    $subscriber = $this->createSubscriber();
    $this->worker->processTaskStrategy(ScheduledTask::create(), microtime(true));
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->null();
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('last-engagement@test.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createOpen(Carbon $time, NewsletterEntity $newsletter, SubscriberEntity $subscriber): StatisticsOpenEntity {
    $open = new StatisticsOpenEntity($newsletter, $newsletter->getLatestQueue(), $subscriber);
    $open->setCreatedAt($time);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }

  private function createClick(Carbon $time, NewsletterEntity $newsletter, SubscriberEntity $subscriber): StatisticsClickEntity {
    $link = new NewsletterLinkEntity($newsletter, $newsletter->getLatestQueue(), 'http://example.com', 'hash123');
    $this->entityManager->persist($link);
    $click = new StatisticsClickEntity($newsletter, $newsletter->getLatestQueue(), $subscriber, $link, 1);
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

  private function createWooOrder(Carbon $postDate, string $email): void {
    $this->orderIds[] = wp_insert_post([
      'post_type' => 'shop_order',
      'post_date' => $postDate->toDateTimeString(),
      'meta_input' => [
        '_billing_email' => $email,
      ],
    ]);
  }

  public function _after(): void {
    foreach ($this->orderIds as $orderId) {
      wp_delete_post($orderId);
    }
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(StatisticsClickEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
  }
}
