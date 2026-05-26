<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoetVendor\Carbon\Carbon;

class SubscriberScoreTest extends \MailPoetTest {

  /** @var SubscriberScore */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberScore::class);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(0);
    $subscriber->setEmail('e1@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(25);
    $subscriber->setEmail('e12@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(50);
    $subscriber->setEmail('e123@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(75);
    $subscriber->setEmail('e1234@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(100);
    $subscriber->setEmail('e12345@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    $subscriber = new SubscriberEntity();
    // Engagement score not set, should be NULL
    $subscriber->setEmail('e123456@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('dormant@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    $this->createSentEmails($subscriber, 3, Carbon::now()->subMonths(13));
  }

  public function testGetHigherThan(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::HIGHER_THAN, '80');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e12345@example.com'], $emails);
  }

  public function testGetLowerThan(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::LOWER_THAN, '30');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com'], $emails);
  }

  public function testGetEquals(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::EQUALS, '50');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e123@example.com'], $emails);
  }

  public function testGetNotEquals(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::NOT_EQUALS, '50');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com', 'e1234@example.com', 'e12345@example.com'], $emails);
  }

  public function testGetUnknown(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::UNKNOWN, '');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e123456@example.com'], $emails);
  }

  public function testGetNotUnknown(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::NOT_UNKNOWN, '');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com', 'e123@example.com', 'e1234@example.com', 'e12345@example.com', 'dormant@example.com'], $emails);
  }

  public function testGetDormant(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::DORMANT, '');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['dormant@example.com'], $emails);
  }

  public function testGetNotDormant(): void {
    $segmentFilterData = $this->getSegmentFilterData(SubscriberScore::NOT_DORMANT, '');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com', 'e123@example.com', 'e1234@example.com', 'e12345@example.com', 'e123456@example.com'], $emails);
  }

  private function getSegmentFilterData(string $operator, string $value): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberScore::TYPE, [
      'operator' => $operator,
      'value' => $value,
    ]);
  }

  private function createSentEmails(SubscriberEntity $subscriber, int $count, Carbon $sentAt): void {
    for ($i = 0; $i < $count; $i++) {
      $newsletter = (new Newsletter())->withSendingQueue()->create();
      (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($sentAt)->create();
    }
  }
}
