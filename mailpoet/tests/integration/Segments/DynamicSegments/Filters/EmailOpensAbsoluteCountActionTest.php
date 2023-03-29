<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoetVendor\Carbon\CarbonImmutable;

class EmailOpensAbsoluteCountActionTest extends \MailPoetTest {
  /** @var EmailOpensAbsoluteCountAction */
  private $action;

  public function _before(): void {
    $this->cleanData();
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
    $segmentFilterData = $this->getSegmentFilterData(2, 'more', 3);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->action);
    $this->assertEqualsCanonicalizing(['opened-3-newsletters@example.com'], $emails);
  }

  public function testGetMachineOpened(): void {
    $segmentFilterData = $this->getSegmentFilterData(1, 'more', 5, EmailOpensAbsoluteCountAction::MACHINE_TYPE);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->action);
    $this->assertEqualsCanonicalizing(['opened-3-newsletters@example.com'], $emails);
  }

  public function testGetOpenedOld(): void {
    $segmentFilterData = $this->getSegmentFilterData(2, 'more', 7);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->action);
    $this->assertEqualsCanonicalizing(['opened-3-newsletters@example.com', 'opened-old-opens@example.com'], $emails);
  }

  public function testGetOpenedLess(): void {
    $segmentFilterData = $this->getSegmentFilterData(3, 'less', 3);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->action);
    $this->assertEqualsCanonicalizing(['opened-less-opens@example.com', 'opened-old-opens@example.com'], $emails);
  }

  public function testGetOpenedEquals(): void {
    $segmentFilterData = $this->getSegmentFilterData(1, 'equals', 3);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->action);
    $this->assertEqualsCanonicalizing(['opened-old-opens@example.com'], $emails);
  }

  public function testGetOpenedNotEquals(): void {
    $segmentFilterData = $this->getSegmentFilterData(2, 'not_equals', 3);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->action);
    $this->assertEqualsCanonicalizing(['opened-3-newsletters@example.com', 'opened-old-opens@example.com'], $emails);
  }

  private function getSegmentFilterData(int $opens, string $operator, int $days, string $action = EmailOpensAbsoluteCountAction::TYPE): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, [
      'operator' => $operator,
      'opens' => $opens,
      'days' => $days,
    ]);
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

  public function _after(): void {
    $this->cleanData();
  }

  private function cleanData(): void {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(StatisticsNewsletterEntity::class);
    $this->truncateEntity(UserAgentEntity::class);
  }
}
