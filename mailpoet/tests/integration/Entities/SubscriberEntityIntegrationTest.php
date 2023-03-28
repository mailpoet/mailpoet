<?php declare(strict_types = 1);

namespace MailPoet\Entities;

use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class SubscriberEntityIntegrationTest extends \MailPoetTest {

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SegmentEntity */
  private $segment2;

  /** @var SubscriberEntity */
  private $subscriber;

  protected function _before() {
    parent::_before();

    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);

    $segment1 = (new SegmentFactory())->create();
    $this->segment2 = (new SegmentFactory())->create();

    $this->subscriber = (new SubscriberFactory())->withSegments([$segment1, $this->segment2])->create();

    $subscriberSegment1 = $this->subscriberSegmentRepository->findOneBy(['segment' => $segment1]);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment1);
    $subscriberSegment1->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->persist($subscriberSegment1);
    $this->entityManager->flush();
  }

  public function testGetSubscriberSegmentsShouldReturnAllSubscriberSegments() {
    $allSubscriberSegments = $this->subscriberSegmentRepository->findBy(['subscriber' => $this->subscriber]);
    $this->assertSame($allSubscriberSegments, $this->subscriber->getSubscriberSegments()->toArray());
  }

  public function testGetSubscriberSegmentsReturnsOnlySubscribersOfGivenStatus() {
    $subscriberSegment2 = $this->subscriberSegmentRepository->findOneBy(['segment' => $this->segment2, 'subscriber' => $this->subscriber]);

    $this->assertSame([1 => $subscriberSegment2], $this->subscriber->getSubscriberSegments(SubscriberEntity::STATUS_SUBSCRIBED)->toArray());
  }
}
