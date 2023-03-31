<?php declare(strict_types = 1);

namespace integration\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentsFinder;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetTest;

class SegmentsFinderTest extends MailPoetTest {
  public function testItFindsStaticSegments(): void {
    $static = [
      $this->createSegment(SegmentEntity::TYPE_DEFAULT),
      $this->createSegment(SegmentEntity::TYPE_WP_USERS),
      $this->createSegment(SegmentEntity::TYPE_WC_USERS),
      $this->createSegment(SegmentEntity::TYPE_WC_MEMBERSHIPS),
    ];
    $this->createSegment(SegmentEntity::TYPE_DYNAMIC);
    $this->createSegment(SegmentEntity::TYPE_WITHOUT_LIST);

    $subscribed = $this->createSubscriber(SubscriberEntity::STATUS_SUBSCRIBED, $static);
    $unsubscribed = $this->createSubscriber(SubscriberEntity::STATUS_UNSUBSCRIBED, $static);
    $unconfirmed = $this->createSubscriber(SubscriberEntity::STATUS_UNCONFIRMED, $static);
    $inactive = $this->createSubscriber(SubscriberEntity::STATUS_INACTIVE, $static);
    $bounced = $this->createSubscriber(SubscriberEntity::STATUS_BOUNCED, $static);

    $segmentsFinder = $this->diContainer->get(SegmentsFinder::class);
    foreach ([$subscribed, $unsubscribed, $unconfirmed, $inactive, $bounced] as $subscriber) {
      $segments = $segmentsFinder->findStaticSegments($subscriber);
      $this->assertCount(4, $segments);
      $this->assertSame('Segment default', $segments[0]->getName());
      $this->assertSame('Segment wp_users', $segments[1]->getName());
      $this->assertSame('Segment woocommerce_users', $segments[2]->getName());
      $this->assertSame('Segment woocommerce_memberships', $segments[3]->getName());
    }
  }

  public function testItFindsDynamicSegments(): void {
    $this->createSegment(SegmentEntity::TYPE_DEFAULT);
    $this->createSegment(SegmentEntity::TYPE_DYNAMIC);
    $this->createSegment(SegmentEntity::TYPE_WP_USERS);
    $this->createSegment(SegmentEntity::TYPE_WC_USERS);
    $this->createSegment(SegmentEntity::TYPE_WC_MEMBERSHIPS);
    $this->createSegment(SegmentEntity::TYPE_WITHOUT_LIST);

    $subscribed = $this->createSubscriber(SubscriberEntity::STATUS_SUBSCRIBED);
    $unsubscribed = $this->createSubscriber(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $unconfirmed = $this->createSubscriber(SubscriberEntity::STATUS_UNCONFIRMED);
    $inactive = $this->createSubscriber(SubscriberEntity::STATUS_INACTIVE);
    $bounced = $this->createSubscriber(SubscriberEntity::STATUS_BOUNCED);

    $segmentsFinder = $this->diContainer->get(SegmentsFinder::class);
    foreach ([$subscribed, $unsubscribed, $unconfirmed, $inactive, $bounced] as $subscriber) {
      $segments = $segmentsFinder->findDynamicSegments($subscriber);
      $this->assertCount(1, $segments);
      $this->assertSame('Segment dynamic', $segments[0]->getName());
    }
  }

  public function testItFindsAllSegments(): void {
    $default = $this->createSegment(SegmentEntity::TYPE_DEFAULT);
    $this->createSegment(SegmentEntity::TYPE_DYNAMIC);
    $unsubscribed = $this->createSubscriber(SubscriberEntity::STATUS_UNCONFIRMED, [$default]);

    $segmentsFinder = $this->diContainer->get(SegmentsFinder::class);
    $segments = $segmentsFinder->findSegments($unsubscribed);
    $this->assertCount(2, $segments);
    $this->assertSame('Segment default', $segments[0]->getName());
    $this->assertSame('Segment dynamic', $segments[1]->getName());
  }

  private function createSubscriber(string $status, array $lists = []): SubscriberEntity {
    $subscriberFactory = new SubscriberFactory();
    return $subscriberFactory
      ->withEmail("{$status}@example.com")
      ->withStatus($status)
      ->withSegments($lists)
      ->create();
  }

  private function createSegment(string $type): SegmentEntity {
    $segmentFactory = new SegmentFactory();
    return $segmentFactory
      ->withName("Segment $type")
      ->withType($type)
      ->create();
  }
}
