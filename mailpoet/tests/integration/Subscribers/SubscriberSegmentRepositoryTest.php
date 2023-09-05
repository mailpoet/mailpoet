<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriberSegmentRepositoryTest extends \MailPoetTest {

  /** @var SubscriberSegmentRepository */
  private $testee;

  public function _before() {
    $this->testee = $this->diContainer->get(SubscriberSegmentRepository::class);
  }

  public function testResetIsWorking() {
    $subscriber = (new Subscriber())->create();
    $segment1 = (new Segment())->create();
    $segment2 = (new Segment())->create();
    $segment3 = (new Segment())->create();
    $segment4 = (new Segment())->create();

    $this->testee->resetSubscriptions($subscriber, [$segment1, $segment2, $segment3, $segment4]);
    $subscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_SUBSCRIBED);
    $unsubscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $subscribedSegmentIds = $this->getSubscribedSegmentIds($subscribedSegments->toArray());

    $this->assertEquals(0, $unsubscribedSegments->count());
    $this->assertCount(4, $subscribedSegmentIds);
    $this->assertContains($segment1->getId(), $subscribedSegmentIds);
    $this->assertContains($segment2->getId(), $subscribedSegmentIds);
    $this->assertContains($segment3->getId(), $subscribedSegmentIds);
    $this->assertContains($segment4->getId(), $subscribedSegmentIds);

    $this->testee->resetSubscriptions($subscriber, [$segment2, $segment3, $segment4]);
    $subscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_SUBSCRIBED);
    $unsubscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $subscribedSegmentIds = $this->getSubscribedSegmentIds($subscribedSegments->toArray());

    $this->assertEquals(1, $unsubscribedSegments->count());
    $this->assertCount(3, $subscribedSegmentIds);
    $this->assertNotContains($segment1->getId(), $subscribedSegmentIds);
    $this->assertContains($segment2->getId(), $subscribedSegmentIds);
    $this->assertContains($segment3->getId(), $subscribedSegmentIds);
    $this->assertContains($segment4->getId(), $subscribedSegmentIds);

    $this->testee->resetSubscriptions($subscriber, [$segment1, $segment2, $segment3, $segment4]);
    $subscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_SUBSCRIBED);
    $unsubscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $subscribedSegmentIds = $this->getSubscribedSegmentIds($subscribedSegments->toArray());

    $this->assertEquals(0, $unsubscribedSegments->count());
    $this->assertCount(4, $subscribedSegmentIds);
    $this->assertContains($segment1->getId(), $subscribedSegmentIds);
    $this->assertContains($segment2->getId(), $subscribedSegmentIds);
    $this->assertContains($segment3->getId(), $subscribedSegmentIds);
    $this->assertContains($segment4->getId(), $subscribedSegmentIds);

    $this->testee->resetSubscriptions($subscriber, []);
    $subscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_SUBSCRIBED);
    $unsubscribedSegments = $subscriber->getSubscriberSegments(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->assertEquals(4, $unsubscribedSegments->count());
    $this->assertEquals(0, $subscribedSegments->count());
  }

  /**
   * @param SubscriberSegmentEntity[] $subscribedSegments
   * @return int[]
   */
  private function getSubscribedSegmentIds(array $subscribedSegments): array {
    return array_values(array_filter(array_map(
      function(SubscriberSegmentEntity $entity): ?int {
        return $entity->getSegment() ? $entity->getSegment()->getId() : null;
      },
      $subscribedSegments
    )));
  }
}
