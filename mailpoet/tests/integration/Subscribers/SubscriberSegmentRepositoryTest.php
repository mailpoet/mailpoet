<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

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

  public function testItTriggersSegmentUnsubscribedHookOnStatusChange() {
    $subscriber = (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $segment = (new Segment())->create();
    $subscriberSegment = $this->testee->createOrUpdate($subscriber, $segment, SubscriberEntity::STATUS_SUBSCRIBED);

    $hookCalls = 0;
    $receivedSubscriberSegmentId = null;
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->removeAllActions('mailpoet_segment_unsubscribed');
    $wp->addAction('mailpoet_segment_unsubscribed', function (SubscriberSegmentEntity $receivedSubscriberSegment) use (&$hookCalls, &$receivedSubscriberSegmentId) {
      $hookCalls++;
      $receivedSubscriberSegmentId = $receivedSubscriberSegment->getId();
    }, 10, 1);

    try {
      $this->testee->createOrUpdate($subscriber, $segment, SubscriberEntity::STATUS_UNSUBSCRIBED);
      $this->testee->createOrUpdate($subscriber, $segment, SubscriberEntity::STATUS_UNSUBSCRIBED);
    } finally {
      $wp->removeAllActions('mailpoet_segment_unsubscribed');
    }

    $this->assertSame(1, $hookCalls);
    $this->assertSame($subscriberSegment->getId(), $receivedSubscriberSegmentId);
  }

  public function testItTriggersSegmentUnsubscribedHookWhenUnsubscribingFromAllSegments() {
    $segment1 = (new Segment())->create();
    $segment2 = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment1, $segment2])
      ->create();

    $receivedSegmentIds = [];
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->removeAllActions('mailpoet_segment_unsubscribed');
    $wp->addAction('mailpoet_segment_unsubscribed', function (SubscriberSegmentEntity $subscriberSegment) use (&$receivedSegmentIds) {
      $segment = $subscriberSegment->getSegment();
      $receivedSegmentIds[] = $segment ? $segment->getId() : null;
    }, 10, 1);

    try {
      $this->testee->unsubscribeFromSegments($subscriber);
      $this->testee->unsubscribeFromSegments($subscriber);
    } finally {
      $wp->removeAllActions('mailpoet_segment_unsubscribed');
    }

    sort($receivedSegmentIds);
    $this->assertSame([$segment1->getId(), $segment2->getId()], $receivedSegmentIds);
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
