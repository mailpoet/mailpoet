<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\CarbonImmutable;

class SubscriberSubscribedDateTest extends \MailPoetTest {

  /** @var SubscriberSubscribedDate */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberSubscribedDate::class);
    $this->cleanUp();

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber->setEmail('e1@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(1));
    $subscriber->setEmail('e12@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(2));
    $subscriber->setEmail('e123@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(3));
    $subscriber->setEmail('e1234@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(4));
    $subscriber->setEmail('e12345@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
  }

  public function testGetBefore(): void {
    $segmentFilterData = $this->getSegmentFilterData('before', CarbonImmutable::now()->subDays(3)->format('Y-m-d'));
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e12345@example.com'], $emails);
  }

  public function testGetAfter(): void {
    $segmentFilterData = $this->getSegmentFilterData('after', CarbonImmutable::now()->subDays(2)->format('Y-m-d'));
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com'], $emails);
  }

  public function testGetOn(): void {
    $segmentFilterData = $this->getSegmentFilterData('on', CarbonImmutable::now()->subDays(2)->format('Y-m-d'));
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e123@example.com'], $emails);
  }

  public function testGetNotOn(): void {
    $segmentFilterData = $this->getSegmentFilterData('notOn', CarbonImmutable::now()->subDays(2)->format('Y-m-d'));
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com', 'e1234@example.com', 'e12345@example.com'], $emails);
  }

  public function testGetInTheLast(): void {
    $segmentFilterData = $this->getSegmentFilterData('inTheLast', '2');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1@example.com', 'e12@example.com'], $emails);
  }

  public function testGetNotInTheLast(): void {
    $segmentFilterData = $this->getSegmentFilterData('notInTheLast', '3');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['e1234@example.com', 'e12345@example.com'], $emails);
  }

  private function getSegmentFilterData(string $operator, string $value): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSubscribedDate::TYPE, [
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
