<?php declare(strict_types=1);

namespace MailPoet\Entities;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use \MailPoet\Test\DataFactories\Segment as SegmentFactory;
use \MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class SubscriberEntityTest extends \MailPoetTest {

  protected function _before() {
    parent::_before();

    // Calling cleanup() here as some tests from other classes don't remove all SubscriberSegmentEntities causing issues here.
    $this->cleanup();
  }

  public function testGetSubscribedSegments() {
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();

    $subscriber = (new SubscriberFactory())->withSegments([$segment1, $segment2])->create();

    $subscriberSegment1 = $subscriber->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment1);
    $subscriberSegment1->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->persist($subscriberSegment1);
    $this->entityManager->flush();

    $subscriberSegment2 = $subscriber->getSubscriberSegments()->last();

    $this->assertSame([1 => $subscriberSegment2], $subscriber->getSubscribedSegments()->toArray());
  }

  protected function _after() {
    parent::_after();
    $this->cleanup();
  }

  protected function cleanup() {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
  }
}
