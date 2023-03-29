<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;

class SubscriberScoreTest extends \MailPoetTest {

  /** @var SubscriberScore */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberScore::class);
    $this->cleanUp();

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
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com', 'e123@example.com', 'e1234@example.com', 'e12345@example.com'], $emails);
  }

  private function getSegmentFilterData(string $operator, string $value): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberScore::TYPE, [
      'operator' => $operator,
      'value' => $value,
    ]);
  }

  private function cleanUp(): void {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }
}
