<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;

class EmailOpensAbsoluteCountActionTest extends \MailPoetTest {
  /** @var EmailOpensAbsoluteCountAction */
  private $action;

  public function _before() {
    $this->cleanData();
    $this->action = $this->diContainer->get(EmailOpensAbsoluteCountAction::class);
    $newsletter1 = $this->createNewsletter();
    $newsletter2 = $this->createNewsletter();
    $newsletter3 = $this->createNewsletter();
    $this->entityManager->flush();

    $subscriber = $this->createSubscriber('opened-3-newsletters@example.com');

    $this->createStatsNewsletter($subscriber, $newsletter1);
    $open = $this->createStatisticsOpens($subscriber, $newsletter1);
    $open->setCreatedAt(CarbonImmutable::now()->subMinutes(5));
    $this->createStatsNewsletter($subscriber, $newsletter2);
    $open = $this->createStatisticsOpens($subscriber, $newsletter2);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(1));
    $this->createStatsNewsletter($subscriber, $newsletter3);
    $open = $this->createStatisticsOpens($subscriber, $newsletter3);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(2));

    $subscriber = $this->createSubscriber('opened-old-opens@example.com');

    $this->createStatsNewsletter($subscriber, $newsletter1);
    $open = $this->createStatisticsOpens($subscriber, $newsletter1);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(3));
    $this->createStatsNewsletter($subscriber, $newsletter2);
    $open = $this->createStatisticsOpens($subscriber, $newsletter2);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(4));
    $this->createStatsNewsletter($subscriber, $newsletter3);
    $open = $this->createStatisticsOpens($subscriber, $newsletter3);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(5));

    $subscriber = $this->createSubscriber('opened-less-opens@example.com');

    $this->createStatsNewsletter($subscriber, $newsletter1);
    $open = $this->createStatisticsOpens($subscriber, $newsletter1);
    $open->setCreatedAt(CarbonImmutable::now()->subMinutes(5));
    $this->createStatsNewsletter($subscriber, $newsletter2);
    $open = $this->createStatisticsOpens($subscriber, $newsletter2);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(1));
  }

  public function testGetOpened() {
    $segmentFilter = $this->getSegmentFilter(2, 'more', 3);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened-3-newsletters@example.com');
  }

  public function testGetOpenedOld() {
    $segmentFilter = $this->getSegmentFilter(2, 'more', 7);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened-3-newsletters@example.com');
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber2->getEmail())->equals('opened-old-opens@example.com');
  }

  public function testGetOpenedLess() {
    $segmentFilter = $this->getSegmentFilter(3, 'less', 3);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened-less-opens@example.com');
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber2->getEmail())->equals('opened-old-opens@example.com');
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(int $opens, string $operator, int $days): DynamicSegmentFilterEntity {
    $segmentFilterData = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'operator' => $operator,
      'opens' => $opens,
      'days' => $days,
    ]);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $segmentFilterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createSubscriber(string $email) {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLastName('Last');
    $subscriber->setFirstName('First');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createNewsletter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('newsletter 1');
    $newsletter->setStatus('sent');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($newsletter);
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);
    return $newsletter;
  }

  private function createStatsNewsletter(SubscriberEntity $subscriber, NewsletterEntity $newsletter) {
    $queue = $newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    $stats = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($stats);
    $this->entityManager->flush();
    return $stats;
  }

  private function createStatisticsOpens(SubscriberEntity $subscriber, NewsletterEntity $newsletter) {
    $queue = $newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(StatisticsNewsletterEntity::class);
  }
}
