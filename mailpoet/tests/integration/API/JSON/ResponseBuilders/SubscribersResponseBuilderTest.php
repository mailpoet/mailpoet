<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
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
    $this->assertEquals($subscriber->getTimeZone(), $response['timezone']);
    // check subscriptions
    $this->assertCount(0, $response['unsubscribes']);
    $this->checkSubscription($response, $subscriber);
    // check tags
    $this->checkTag($response, $subscriber);
  }

  public function testItMapsUnspecifiedUnsubscribeReasonToHumanReadableLabel(): void {
    $unsubscribe = new StatisticsUnsubscribeEntity(null, null, $this->subscriber1);
    $unsubscribe->setReason(StatisticsUnsubscribeEntity::REASON_UNSPECIFIED);
    $this->entityManager->persist($unsubscribe);
    $this->entityManager->flush();

    $response = $this->responseBuilder->build($this->subscriber1);

    $this->assertCount(1, $response['unsubscribes']);
    $row = $response['unsubscribes'][0];
    $this->assertSame(StatisticsUnsubscribeEntity::REASON_UNSPECIFIED, $row['reason']);
    $this->assertSame(__('No reason provided', 'mailpoet'), $row['reasonLabel']);
  }

  public function testItExposesTrackingConsentFields(): void {
    $this->subscriber1->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK,
      'By clicking here you opt out of email open and click tracking.'
    );
    $this->entityManager->flush();

    $response = $this->responseBuilder->build($this->subscriber1);

    $this->assertSame(SubscriberEntity::TRACKING_CONSENT_DENIED, $response['tracking_consent']);
    $this->assertNotNull($response['tracking_consent_updated_at']);
    $this->assertSame(SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK, $response['tracking_consent_method']);
    $this->assertSame('By clicking here you opt out of email open and click tracking.', $response['tracking_consent_copy']);
  }

  public function testItExposesUnsetTrackingConsentAsTheDefaultWithNoEvidence(): void {
    $response = $this->responseBuilder->build($this->subscriber2);

    $this->assertSame(SubscriberEntity::TRACKING_CONSENT_UNKNOWN, $response['tracking_consent']);
    $this->assertNull($response['tracking_consent_updated_at']);
    $this->assertNull($response['tracking_consent_method']);
    $this->assertNull($response['tracking_consent_copy']);
  }

  public function testItBuildsListingResponse(): void {
    $subscribers = [
      $this->subscriber1,
      $this->subscriber2,
    ];
    $response = $this->responseBuilder->buildForListing($subscribers);

    $this->assertCount(2, $response);
    foreach ($subscribers as $key => $subscriber) {
      /** @var array $item - for PHPStan */
      $item = $response[$key];
      $this->assertEquals($subscriber->getId(), $item['id']);
      $this->assertEquals($subscriber->getFirstName(), $item['first_name']);
      $this->assertEquals($subscriber->getLastName(), $item['last_name']);
      $this->assertEquals($subscriber->getEmail(), $item['email']);
      $this->assertEquals($subscriber->getWpUserId(), $item['wp_user_id']);
      $this->assertEquals($subscriber->getIsWoocommerceUser(), $item['is_woocommerce_user']);
      $this->assertEquals($subscriber->getStatus(), $item['status']);
      $this->assertEquals($subscriber->getSource(), $item['source']);
      $this->assertEquals($subscriber->getTrackingConsent(), $item['tracking_consent']);
      $this->assertArrayHasKey('created_at', $item);
      $this->assertArrayHasKey('deleted_at', $item);
      $this->assertArrayHasKey('last_subscribed_at', $item);
      $this->assertArrayHasKey('count_confirmations', $item);
      $this->assertArrayHasKey('engagement_score', $item);
      $this->assertSame(SubscriberStatisticsRepository::ENGAGEMENT_SCORE_UNKNOWN, $item['engagement_score_type']);
      // check subscriptions
      $this->checkSubscription($item, $subscriber);
      // check tags
      $this->checkTag($item, $subscriber);
    }
  }

  public function testItBuildsUnsubscribedDateForListing(): void {
    // Unsubscribed, subscribed again, unsubscribed, and recorded once more while unsubscribed.
    $unsubscribed = (new SubscriberFactory())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withLastSubscribedAt(new \DateTimeImmutable('2026-01-01 10:00:00'))
      ->create();
    $this->createUnsubscribe($unsubscribed, '2025-12-01 10:00:00');
    $this->createUnsubscribe($unsubscribed, '2026-03-01 11:30:00');
    $this->createUnsubscribe($unsubscribed, '2026-02-01 10:00:00');

    $neverSubscribed = (new SubscriberFactory())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $this->createUnsubscribe($neverSubscribed, '2026-05-05 09:00:00');

    // Signed up again after unsubscribing and has not confirmed yet.
    $signedUpAgain = (new SubscriberFactory())
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withLastSubscribedAt(new \DateTimeImmutable('2026-01-01 10:00:00'))
      ->create();
    $this->createUnsubscribe($signedUpAgain, '2026-03-01 10:00:00');

    // For example imported as unsubscribed.
    $withoutRecord = (new SubscriberFactory())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    // Subscribed again and then unsubscribed in a way that leaves no record.
    $withOldRecord = (new SubscriberFactory())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withLastSubscribedAt(new \DateTimeImmutable('2026-04-01 10:00:00'))
      ->create();
    $this->createUnsubscribe($withOldRecord, '2026-03-01 10:00:00');

    $response = $this->responseBuilder->buildForListing([$unsubscribed, $neverSubscribed, $signedUpAgain, $withoutRecord, $withOldRecord]);

    $this->assertSame('2026-02-01 10:00:00', $response[0]['unsubscribed_at'], 'first unsubscribe since the last subscription');
    $this->assertSame('2026-05-05 09:00:00', $response[1]['unsubscribed_at'], 'second subscriber with a date');
    $this->assertNull($response[2]['unsubscribed_at'], 'no longer unsubscribed');
    $this->assertNull($response[3]['unsubscribed_at'], 'no unsubscribe record');
    $this->assertNull($response[4]['unsubscribed_at'], 'record older than the last subscription');
  }

  private function createUnsubscribe(SubscriberEntity $subscriber, string $createdAt): void {
    $unsubscribe = new StatisticsUnsubscribeEntity(null, null, $subscriber);
    $unsubscribe->setCreatedAt(new \DateTimeImmutable($createdAt));
    $this->entityManager->persist($unsubscribe);
    $this->entityManager->flush();
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
