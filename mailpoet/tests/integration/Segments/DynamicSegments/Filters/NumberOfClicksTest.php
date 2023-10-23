<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Filters\NumberOfClicks;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\Subscriber;

class NumberOfClicksTest extends \MailPoetTest {
  /** @var NumberOfClicks */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(NumberOfClicks::class);
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
    $subscriber1 = (new Subscriber())->withEmail('1@e.com')->create();
    $this->createClicks($subscriber1, 2, CarbonImmutable::now()->subDays(200));

    $subscriber2 = (new Subscriber())->withEmail('2@e.com')->create();
    $this->createClicks($subscriber2, 2, CarbonImmutable::now()->subDays(2));

    $segmentFilterData = $this->getSegmentFilterData(2, 'equals', 2, 'allTime');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['1@e.com', '2@e.com'], $emails);
  }

  public function testInTheLastDays(): void {
    $subscriber1 = (new Subscriber())->withEmail('1@e.com')->create();
    $this->createClicks($subscriber1, 2, CarbonImmutable::now()->subDays(200));

    $subscriber2 = (new Subscriber())->withEmail('2@e.com')->create();
    $this->createClicks($subscriber2, 2, CarbonImmutable::now()->subDays(2));

    $segmentFilterData = $this->getSegmentFilterData(2, 'equals', 2, 'inTheLast');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['2@e.com'], $emails);
  }

  private function getSegmentFilterData(int $clicks, string $operator, int $days, string $timeframe = DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST, string $action = NumberOfClicks::ACTION): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, [
      'operator' => $operator,
      'clicks' => $clicks,
      'days' => $days,
      'timeframe' => $timeframe,
    ]);
  }

  private function createClicks(SubscriberEntity $subscriber, int $count, DateTimeInterface $date = null): void {
    if ($date === null) {
      $date = CarbonImmutable::now();
    }
    $newsletter = (new Newsletter())->withSendingQueue()->withSentStatus()->withCreatedAt($date)->create();
    for ($i = 0; $i < $count; $i++) {
      $link = (new NewsletterLink($newsletter))
        ->withUrl(sprintf('https://www.example.com/%s/%s', $newsletter->getId(), $i))
        ->create();
      (new StatisticsClicks($link, $subscriber))->withCreatedAt($date)->create();
    }
  }

  private function createSubscriber(string $email, int $clickCount): void {
    $subscriber = (new Subscriber())->withEmail($email)->create();
    $this->createClicks($subscriber, $clickCount);
  }
}
