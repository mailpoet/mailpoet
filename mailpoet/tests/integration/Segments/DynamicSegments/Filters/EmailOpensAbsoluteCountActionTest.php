<?php declare(strict_types = 1);

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
use MailPoet\Entities\UserAgentEntity;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class EmailOpensAbsoluteCountActionTest extends \MailPoetTest {
  /** @var EmailOpensAbsoluteCountAction */
  private $action;

  public function _before(): void {
    $this->action = $this->diContainer->get(EmailOpensAbsoluteCountAction::class);
    $newsletter1 = $this->createNewsletter();
    $newsletter2 = $this->createNewsletter();
    $newsletter3 = $this->createNewsletter();
    $newsletter4 = $this->createNewsletter();
    $this->entityManager->flush();

    $userAgent = new UserAgentEntity(UserAgentEntity::MACHINE_USER_AGENTS[0]);
    $this->entityManager->persist($userAgent);

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

    $open = $this->createStatisticsOpens($subscriber, $newsletter4);
    $open->setCreatedAt(CarbonImmutable::now()->subMinutes(5));
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $open->setUserAgent($userAgent);
    $open = $this->createStatisticsOpens($subscriber, $newsletter4);
    $open->setCreatedAt(CarbonImmutable::now()->subMinutes(6));
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $open->setUserAgent($userAgent);

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
    $open = $this->createStatisticsOpens($subscriber, $newsletter4);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(5));
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $open->setUserAgent($userAgent);

    $subscriber = $this->createSubscriber('opened-less-opens@example.com');

    $this->createStatsNewsletter($subscriber, $newsletter1);
    $open = $this->createStatisticsOpens($subscriber, $newsletter1);
    $open->setCreatedAt(CarbonImmutable::now()->subMinutes(5));
    $this->createStatsNewsletter($subscriber, $newsletter2);
    $open = $this->createStatisticsOpens($subscriber, $newsletter2);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(1));
    $open = $this->createStatisticsOpens($subscriber, $newsletter4);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(1));
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $open->setUserAgent($userAgent);

    $subscriber = $this->createSubscriber('opened-no-opens@example.com');
    $open = $this->createStatisticsOpens($subscriber, $newsletter4);
    $open->setCreatedAt(CarbonImmutable::now()->subDays(1));
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $open->setUserAgent($userAgent);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
  }

  public function testGetOpened(): void {
    $segmentFilter = $this->getSegmentFilter(2, 'more', 3);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened-3-newsletters@example.com');
  }

  public function testGetMachineOpened(): void {
    $segmentFilter = $this->getSegmentFilter(1, 'more', 5, EmailOpensAbsoluteCountAction::MACHINE_TYPE);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened-3-newsletters@example.com');
  }

  public function testGetOpenedOld(): void {
    $segmentFilter = $this->getSegmentFilter(2, 'more', 7);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened-3-newsletters@example.com');
    $this->assertIsArray($result[1]);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2->getEmail())->equals('opened-old-opens@example.com');
  }

  public function testGetOpenedLess(): void {
    $segmentFilter = $this->getSegmentFilter(3, 'less', 3);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened-less-opens@example.com');
    $this->assertIsArray($result[1]);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2->getEmail())->equals('opened-old-opens@example.com');
  }

  public function testGetOpenedEquals(): void {
    $segmentFilter = $this->getSegmentFilter(1, 'equals', 3);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    $this->assertCount(1, $result);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('opened-old-opens@example.com', $subscriber1->getEmail());
  }

  public function testGetOpenedNotEquals(): void {
    $segmentFilter = $this->getSegmentFilter(2, 'not_equals', 3);
    $statement = $this->action->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    $this->assertCount(2, $result);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('opened-3-newsletters@example.com', $subscriber1->getEmail());
    $this->assertIsArray($result[1]);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertSame('opened-old-opens@example.com', $subscriber2->getEmail());
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(int $opens, string $operator, int $days, string $action = EmailOpensAbsoluteCountAction::TYPE): DynamicSegmentFilterEntity {
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, [
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

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLastName('Last');
    $subscriber->setFirstName('First');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createNewsletter(): NewsletterEntity {
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

  private function createStatsNewsletter(SubscriberEntity $subscriber, NewsletterEntity $newsletter): StatisticsNewsletterEntity {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $stats = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($stats);
    $this->entityManager->flush();
    return $stats;
  }

  private function createStatisticsOpens(SubscriberEntity $subscriber, NewsletterEntity $newsletter): StatisticsOpenEntity {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }
}
