<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use Carbon\CarbonImmutable;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Filters\EmailsReceived;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Subscriber;

class EmailsReceivedTest extends \MailPoetTest {
  /** @var EmailsReceived */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(EmailsReceived::class);
  }

  public function testGetMoreThan(): void {
    $this->createSubscriber('1@e.com', 1);
    $this->createSubscriber('2@e.com', 2);
    $this->createSubscriber('3@e.com', 3);
    $segmentFilterData = $this->getSegmentFilterData(2, 'more', 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['3@e.com'], $emails);
  }

  public function testLessThan(): void {
    $this->createSubscriber('1@e.com', 1);
    $this->createSubscriber('2@e.com', 2);
    $this->createSubscriber('3@e.com', 3);
    $segmentFilterData = $this->getSegmentFilterData(3, 'less', 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['1@e.com', '2@e.com'], $emails);
  }

  public function testEquals(): void {
    $this->createSubscriber('1@e.com', 1);
    $this->createSubscriber('2@e.com', 2);
    $this->createSubscriber('3@e.com', 3);
    $segmentFilterData = $this->getSegmentFilterData(2, 'equals', 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['2@e.com'], $emails);
  }

  public function testNotEquals(): void {
    $this->createSubscriber('1@e.com', 1);
    $this->createSubscriber('2@e.com', 2);
    $this->createSubscriber('3@e.com', 3);
    $segmentFilterData = $this->getSegmentFilterData(2, 'not_equals', 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['1@e.com', '3@e.com'], $emails);
  }

  public function testOverAllTime() {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $newsletter1 = (new Newsletter())->withSendingQueue()->withSentStatus()->create();
    $stats1 = new StatisticsNewsletterEntity($newsletter1, $newsletter1->getLatestQueue(), $sub1);
    $stats1->setSentAt(CarbonImmutable::now()->subDays(1));
    $this->entityManager->persist($stats1);
    $this->entityManager->flush();

    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->withSentStatus()->create();
    $stats2 = new StatisticsNewsletterEntity($newsletter2, $newsletter2->getLatestQueue(), $sub2);
    $stats2->setSentAt(CarbonImmutable::now()->subDays(10));
    $this->entityManager->persist($stats2);
    $this->entityManager->flush();

    $filterData = $this->getSegmentFilterData(1, 'equals', 0, 'allTime');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing(['1@e.com', '2@e.com'], $emails);
  }

  public function testInTheLastDays(): void {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $newsletter1 = (new Newsletter())->withSendingQueue()->withSentStatus()->create();
    $stats1 = new StatisticsNewsletterEntity($newsletter1, $newsletter1->getLatestQueue(), $sub1);
    $stats1->setSentAt(CarbonImmutable::now()->subDays(1));
    $this->entityManager->persist($stats1);
    $this->entityManager->flush();

    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->withSentStatus()->create();
    $stats2 = new StatisticsNewsletterEntity($newsletter2, $newsletter2->getLatestQueue(), $sub2);
    $stats2->setSentAt(CarbonImmutable::now()->subDays(10));
    $this->entityManager->persist($stats2);
    $this->entityManager->flush();

    $filterData = $this->getSegmentFilterData(1, 'equals', 2, 'inTheLast');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing(['1@e.com'], $emails);
  }

  private function getSegmentFilterData(int $emails, string $operator, int $days, string $timeframe = DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST, string $action = EmailsReceived::ACTION): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, [
      'operator' => $operator,
      'emails' => $emails,
      'days' => $days,
      'timeframe' => $timeframe,
    ]);
  }

  private function createSubscriber(string $email, int $receivedCount): void {
    $subscriber = (new Subscriber())->withEmail($email)->create();
    for ($i = 0; $i < $receivedCount; $i++) {
      $newsletter = (new Newsletter())->withSendingQueue()->withSentStatus()->create();
      $this->createStatsNewsletter($subscriber, $newsletter);
    }
  }

  private function createStatsNewsletter(SubscriberEntity $subscriber, NewsletterEntity $newsletter): StatisticsNewsletterEntity {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $stats = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($stats);
    $this->entityManager->flush();
    return $stats;
  }
}
