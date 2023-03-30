<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoetVendor\Idiorm\ORM;

class SubscriberSegmentTest extends \MailPoetTest {
  public $wcSegment;
  public $wpSegment;
  public $segment2;
  public $segment1;
  public $subscriber;

  public function _before() {
    parent::_before();
    ORM::raw_execute('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');
    $this->subscriber = Subscriber::createOrUpdate([
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $this->segment1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $this->segment2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    $this->wpSegment = Segment::getWPSegment();
    $this->wcSegment = Segment::getWooCommerceSegment();
  }

  public function testItCanSubscribeToSegments() {
    $result = SubscriberSegment::subscribeToSegments($this->subscriber, [
        $this->segment1->id,
        $this->segment2->id,
    ]);
    expect($result)->true();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(2);
  }

  public function testItCanResetSubscriptions() {
    // subscribe to the first segment
    $result = SubscriberSegment::subscribeToSegments($this->subscriber, [
        $this->segment1->id,
    ]);
    expect($result)->true();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(1);
    expect($subscribedSegments[0]['name'])->equals($this->segment1->name);

    // reset subscriptions to second segment
    SubscriberSegment::resetSubscriptions($this->subscriber, [
        $this->segment2->id,
    ]);

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(1);
    expect($subscribedSegments[0]['name'])->equals($this->segment2->name);
  }

  public function testItCanUnsubscribeFromSegments() {
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment1->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment2->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

    // unsubscribe subscriber from first segment
    $result = SubscriberSegment::unsubscribeFromSegments($this->subscriber,
      [
        $this->segment1->id,
      ]
    );
    expect($result)->true();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(1);
    expect($subscribedSegments[0]['name'])->equals($this->segment2->name);
  }

  public function testItDoesNotUnsubscribeFromWPSegment() {
    $subscriber = $this->subscriber;
    $segment1 = $this->segment1;
    $segment1->type = Segment::TYPE_WP_USERS;
    $segment1->save();
    $segment2 = $this->segment2;
    $segment3 = $this->wcSegment;
    $subscriberSegment = SubscriberSegment::createOrUpdate(
      [
        'subscriber_id' => $subscriber->id,
        'segment_id' => $segment1->id,
        'status' => Subscriber::STATUS_SUBSCRIBED,
      ]
    );
    $subscriberSegment = SubscriberSegment::createOrUpdate(
      [
        'subscriber_id' => $subscriber->id,
        'segment_id' => $this->segment2->id,
        'status' => Subscriber::STATUS_SUBSCRIBED,
      ]
    );
    $subscriberSegment = SubscriberSegment::createOrUpdate(
      [
        'subscriber_id' => $subscriber->id,
        'segment_id' => $this->wcSegment->id,
        'status' => Subscriber::STATUS_SUBSCRIBED,
      ]
    );

    // verify that subscriber is subscribed to 3 segments
    $subscriber = Subscriber::findOne($subscriber->id)->withSubscriptions();
    expect($subscriber->subscriptions[0]['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->subscriptions[0]['segment_id'])->equals($segment1->id);
    expect($subscriber->subscriptions[1]['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->subscriptions[1]['segment_id'])->equals($segment2->id);
    expect($subscriber->subscriptions[2]['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->subscriptions[2]['segment_id'])->equals($segment3->id);

    // verify that subscriber is not subscribed only to the non-WP segment (#2)
    $subscriber = $this->subscriber;
    SubscriberSegment::unsubscribeFromSegments($subscriber, [$segment1->id, $segment2->id, $segment3->id]);
    $subscriber = Subscriber::findOne($subscriber->id)->withSubscriptions();

    expect($subscriber->subscriptions[0]['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->subscriptions[0]['segment_id'])->equals($segment1->id);
    expect($subscriber->subscriptions[1]['status'])->equals(Subscriber::STATUS_UNSUBSCRIBED);
    expect($subscriber->subscriptions[1]['segment_id'])->equals($segment2->id);
    expect($subscriber->subscriptions[2]['status'])->equals(Subscriber::STATUS_UNSUBSCRIBED);
    expect($subscriber->subscriptions[2]['segment_id'])->equals($segment3->id);
  }

  public function testItCanUnsubscribeFromAllSegments() {
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment1->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment2->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

    // unsubscribe subscriber from all segments
    $result = SubscriberSegment::unsubscribeFromSegments($this->subscriber);
    expect($result)->true();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->isEmpty();

    // the relations still exist but now have a status of "unsubscribed"
    $subscriptionsCount = SubscriberSegment::where(
        'subscriber_id', $this->subscriber->id
      )
      ->where('status', Subscriber::STATUS_UNSUBSCRIBED)
      ->count();
    expect($subscriptionsCount)->equals(2);
  }

  public function testItCanResubscribeToAllSegments() {
    $result = SubscriberSegment::subscribeToSegments($this->subscriber, [
        $this->segment1->id,
        $this->segment2->id,
    ]);
    expect($result)->true();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(2);

    $result = SubscriberSegment::unsubscribeFromSegments($this->subscriber);
    expect($result)->true();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(0);

    SubscriberSegment::resubscribeToAllSegments($this->subscriber);

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(2);
  }

  public function testItCanDeleteSubscriptions() {
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment1->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment2->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(2);

    // completely remove all subscriptions
    SubscriberSegment::deleteSubscriptions($this->subscriber);

    $subscriptionsCount = SubscriberSegment::where(
        'subscriber_id', $this->subscriber->id
      )->count();
    expect($subscriptionsCount)->equals(0);
  }

  public function testItCanDeleteManySubscriptions() {
    // subscribe first subscriber to segments
    SubscriberSegment::subscribeToSegments($this->subscriber, [
      $this->segment1->id, $this->segment2->id,
    ]);
    // create a second subscriber
    $subscriber2 = Subscriber::createOrUpdate([
      'email' => 'jane.doe@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    // subscribe her to segments
    SubscriberSegment::subscribeToSegments($subscriber2, [
      $this->segment1->id, $this->segment2->id,
    ]);

    expect(SubscriberSegment::count())->equals(4);

    $result = SubscriberSegment::deleteManySubscriptions([
      $this->subscriber->id, $subscriber2->id,
    ]);
    expect($result)->true();

    expect(SubscriberSegment::count())->equals(0);
  }

  public function testItCanCreateOrUpdate() {
    // create relationship between subscriber and a segment
    $result = SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment1->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    expect($result->id > 0)->true();
    expect($result->getErrors())->false();

    // check that we have the proper status
    $created = SubscriberSegment::findOne($result->id);
    $this->assertInstanceOf(SubscriberSegment::class, $created);
    expect($created->status)->equals(Subscriber::STATUS_SUBSCRIBED);

    // update same combination of subscriber/segment with a different status
    $result = SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment1->id,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    expect($result->id > 0)->true();
    expect($result->getErrors())->false();

    // check updated status
    $updated = SubscriberSegment::findOne($created->id);
    $this->assertInstanceOf(SubscriberSegment::class, $updated);
    expect($updated->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);

    // we should have only one relationship for that user
    $subscriptionsCount = SubscriberSegment::where(
      'subscriber_id', $this->subscriber->id
      )
      ->where('segment_id', $this->segment1->id)
      ->count();
    expect($subscriptionsCount)->equals(1);
  }

  public function testItCanFilterBySubscribedStatus() {
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment1->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $this->subscriber->id,
      'segment_id' => $this->segment2->id,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);

    $subscriptionsCount = SubscriberSegment::count();
    expect($subscriptionsCount)->equals(2);

    $subscriptionsCount = SubscriberSegment::filter('subscribed')->count();
    expect($subscriptionsCount)->equals(1);
  }

  public function testItCannotUnsubscribeFromWPAndWooCommerceSegments() {
    // subscribe to a segment, the WP segment, the WooCommerce segment
    $result = SubscriberSegment::subscribeToSegments($this->subscriber, [
        $this->segment1->id,
        $this->wpSegment->id,
        $this->wcSegment->id,
    ]);
    expect($result)->true();

    // unsubscribe from all segments
    $result = SubscriberSegment::unsubscribeFromSegments($this->subscriber);
    expect($result)->true();

    // the subscriber should still be subscribed to the WP segment
    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(1);
    expect($subscribedSegments[0]['name'])->equals($this->wpSegment->name);
  }

  public function testItCannotDeleteSubscriptionToWPAndWooCommerceSegments() {
    // subscribe to a segment, the WP segment, the WooCommerce segment
    $result = SubscriberSegment::subscribeToSegments($this->subscriber, [
        $this->segment1->id,
        $this->wpSegment->id,
        $this->wcSegment->id,
    ]);
    expect($result)->true();

    // delete all subscriber's subscriptions
    $result = SubscriberSegment::deleteSubscriptions($this->subscriber);
    expect($result)->true();

    // the subscriber should still be subscribed to the WP segment
    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(2);
    expect($subscribedSegments[0]['name'])->equals($this->wpSegment->name);
    expect($subscribedSegments[1]['name'])->equals($this->wcSegment->name);
  }

  public function _after() {
    parent::_after();
    Segment::deleteMany();
    Subscriber::deleteMany();
    SubscriberSegment::deleteMany();
  }
}
