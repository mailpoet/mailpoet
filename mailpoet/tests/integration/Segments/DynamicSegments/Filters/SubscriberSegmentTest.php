<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Carbon\CarbonImmutable;

class SubscriberSegmentTest extends \MailPoetTest {
  /** @var SubscriberSegment */
  private $filter;

  /** @var SegmentEntity */
  private $segment1;
  /** @var SegmentEntity */
  private $segment2;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberSegment::class);

    $this->cleanUp();

    $subscriber1 = new SubscriberEntity();
    $subscriber1->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber1->setEmail('a1@example.com');
    $this->entityManager->persist($subscriber1);

    $subscriber2 = new SubscriberEntity();
    $subscriber2->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber2->setEmail('a2@example.com');
    $this->entityManager->persist($subscriber2);

    $subscriber3 = new SubscriberEntity();
    $subscriber3->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber3->setEmail('a3@example.com');
    $this->entityManager->persist($subscriber3);

    $this->segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Segment 1');
    $this->segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Segment 2');
    $this->entityManager->persist($this->segment1);
    $this->entityManager->persist($this->segment2);

    $this->entityManager->persist(new SubscriberSegmentEntity($this->segment1, $subscriber1, SubscriberEntity::STATUS_SUBSCRIBED));

    $this->entityManager->persist(new SubscriberSegmentEntity($this->segment2, $subscriber1, SubscriberEntity::STATUS_SUBSCRIBED));
    $this->entityManager->persist(new SubscriberSegmentEntity($this->segment2, $subscriber2, SubscriberEntity::STATUS_SUBSCRIBED));
    $this->entityManager->flush();
  }

  public function testSubscribedAnyOf(): void {
    $segmentFilterData = $this->getSegmentFilterData(DynamicSegmentFilterData::OPERATOR_ANY, [$this->segment1->getId(), $this->segment2->getId()]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['a1@example.com', 'a2@example.com'], $emails);

  }

  public function testSubscribedAllOf(): void {
    $segmentFilterData = $this->getSegmentFilterData(DynamicSegmentFilterData::OPERATOR_ALL, [$this->segment1->getId(), $this->segment2->getId()]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['a1@example.com'], $emails);
  }

  public function testSubscribedNoneOf(): void {
    $segmentFilterData = $this->getSegmentFilterData(DynamicSegmentFilterData::OPERATOR_NONE, [$this->segment1->getId()]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing(['a2@example.com', 'a3@example.com'], $emails);
  }

  private function getSegmentFilterData(string $operator, array $segments): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSegment::TYPE, [
      'operator' => $operator,
      'segments' => $segments,
    ]);
  }

  private function cleanUp(): void {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
  }
}
