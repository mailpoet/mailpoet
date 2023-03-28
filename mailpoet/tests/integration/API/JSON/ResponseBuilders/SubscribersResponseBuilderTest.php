<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;

class SubscribersResponseBuilderTest extends \MailPoetTest {

  /** @var SubscribersResponseBuilder */
  private $responseBuilder;

  /** @var SegmentEntity */
  private $segment;

  /** @var \MailPoet\Entities\TagEntity */
  private $tag;

  /** @var SubscriberEntity */
  private $subscriber1;

  /** @var SubscriberEntity */
  private $subscriber2;

  public function _before() {
    parent::_before();
    $this->responseBuilder = $this->diContainer->get(SubscribersResponseBuilder::class);

    $this->segment = (new SegmentFactory())
      ->withName('My Segment')
      ->withType(SegmentEntity::TYPE_DEFAULT)
      ->create();

    $this->tag = (new TagFactory())
      ->withName('My Tag')
      ->create();

    $this->subscriber1 = (new SubscriberFactory())
      ->withFirstName('First')
      ->withLastName('Last')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withIsWooCommerceUser()
      ->withSource(Source::FORM)
      ->withWpUserId(1)
      ->withSegments([$this->segment])
      ->withTags([$this->tag])
      ->create();

    $this->subscriber2 = (new SubscriberFactory())
      ->withFirstName('Second')
      ->withLastName('Last')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withIsWooCommerceUser()
      ->withWpUserId(2)
      ->withSegments([$this->segment])
      ->withTags([$this->tag])
      ->create();
  }

  public function testItBuildsResponse(): void {
    $subscriber = $this->subscriber1;
    $response = $this->responseBuilder->build($subscriber);

    $this->assertEquals($subscriber->getId(), $response['id']);
    $this->assertEquals($subscriber->getFirstName(), $response['first_name']);
    $this->assertEquals($subscriber->getLastName(), $response['last_name']);
    $this->assertEquals($subscriber->getEmail(), $response['email']);
    $this->assertEquals($subscriber->getWpUserId(), $response['wp_user_id']);
    $this->assertEquals($subscriber->getIsWoocommerceUser(), $response['is_woocommerce_user']);
    $this->assertEquals($subscriber->getStatus(), $response['status']);
    $this->assertEquals($subscriber->getSource(), $response['source']);
    $this->assertArrayHasKey('created_at', $response);
    $this->assertArrayHasKey('updated_at', $response);
    $this->assertArrayHasKey('deleted_at', $response);
    $this->assertArrayHasKey('subscribed_ip', $response);
    $this->assertArrayHasKey('confirmed_ip', $response);
    $this->assertArrayHasKey('confirmed_at', $response);
    $this->assertArrayHasKey('last_subscribed_at', $response);
    $this->assertArrayHasKey('unconfirmed_data', $response);
    $this->assertArrayHasKey('count_confirmations', $response);
    $this->assertArrayHasKey('unsubscribe_token', $response);
    $this->assertArrayHasKey('link_token', $response);
    // check subscriptions
    $this->assertCount(0, $response['unsubscribes']);
    $this->checkSubscription($response, $subscriber);
    // check tags
    $this->checkTag($response, $subscriber);
  }

  public function testItBuildsListingResponse(): void {
    $subscribers = [
      $this->subscriber1,
      $this->subscriber2,
    ];
    $response = $this->responseBuilder->buildForListing($subscribers);

    $this->assertCount(2, $response);
    foreach ($subscribers as $key => $subscriber) {
      $item = $response[$key];
      $this->assertEquals($subscriber->getId(), $item['id']);
      $this->assertEquals($subscriber->getFirstName(), $item['first_name']);
      $this->assertEquals($subscriber->getLastName(), $item['last_name']);
      $this->assertEquals($subscriber->getEmail(), $item['email']);
      $this->assertEquals($subscriber->getWpUserId(), $item['wp_user_id']);
      $this->assertEquals($subscriber->getIsWoocommerceUser(), $item['is_woocommerce_user']);
      $this->assertEquals($subscriber->getStatus(), $item['status']);
      $this->assertArrayHasKey('created_at', $item);
      $this->assertArrayHasKey('count_confirmations', $item);
      $this->assertArrayHasKey('engagement_score', $item);
      // check subscriptions
      $this->checkSubscription($item, $subscriber);
      // check tags
      $this->checkTag($item, $subscriber);
    }
  }

  private function checkSubscription(array $responseItem, SubscriberEntity $subscriber): void {
    $this->assertCount(1, $responseItem['subscriptions']);
    $subscription = reset($responseItem['subscriptions']);
    $this->assertEquals($subscriber->getId(), $subscription['subscriber_id']);
    $this->assertEquals($this->segment->getId(), $subscription['segment_id']);
    $this->assertEquals(SubscriberEntity::STATUS_SUBSCRIBED, $subscription['status']);
    $this->assertArrayHasKey('created_at', $subscription);
    $this->assertArrayHasKey('updated_at', $subscription);
  }

  private function checkTag(array $responseItem, SubscriberEntity $subscriber): void {
    $this->assertCount(1, $responseItem['tags']);
    $tag = reset($responseItem['tags']);
    $this->assertEquals($subscriber->getId(), $tag['subscriber_id']);
    $this->assertEquals($this->tag->getId(), $tag['tag_id']);
    $this->assertEquals($this->tag->getName(), $tag['name']);
    $this->assertArrayHasKey('created_at', $tag);
    $this->assertArrayHasKey('updated_at', $tag);
  }
}
