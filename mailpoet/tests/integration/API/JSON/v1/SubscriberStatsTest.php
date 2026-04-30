<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SubscriberStats;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Statistics\UnsubscribeReasonTracker;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscriberStatsTest extends \MailPoetTest {
  /** @var SubscriberStats */
  private $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(SubscriberStats::class);
  }

  public function testItReturnsProfileInformation(): void {
    $segment = (new SegmentFactory())->withName('Customers')->create();
    $tag = (new TagFactory())->withName('VIP')->create();
    $lastEngagementAt = Carbon::now()->subDay();
    $subscriber = (new SubscriberFactory())
      ->withEmail('john.geller@example.com')
      ->withFirstName('John')
      ->withLastName('Geller')
      ->withEngagementScore(60)
      ->withLastEngagementAt($lastEngagementAt)
      ->withSource(Source::WOOCOMMERCE_CHECKOUT)
      ->withSegments([$segment])
      ->withTags([$tag])
      ->create();
    $customField = (new CustomFieldFactory())
      ->withName('Favorite color')
      ->withSubscriber((int)$subscriber->getId(), 'Blue')
      ->create();

    $response = $this->endpoint->get(['subscriber_id' => $subscriber->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['last_engagement'])->equals($lastEngagementAt->format('Y-m-d H:i:s'));
    verify($response->data['source_label'])->equals('WooCommerce checkout');
    verify($response->data['avatar_url'])->stringContainsString('gravatar.com');
    verify($response->data['profile']['status'])->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($response->data['profile']['unsubscribe_reason'])->null();
    verify($response->data['profile']['first_name'])->equals('John');
    verify($response->data['profile']['last_name'])->equals('Geller');
    verify($response->data['profile']['email'])->equals('john.geller@example.com');
    verify($response->data['profile']['segments'])->equals([
      [
        'id' => (string)$segment->getId(),
        'name' => 'Customers',
      ],
    ]);
    $tags = $response->data['profile']['tags'];
    verify($tags)->arrayCount(1);
    verify($tags[0]['subscriber_id'])->equals((string)$subscriber->getId());
    verify($tags[0]['tag_id'])->equals((string)$tag->getId());
    verify($tags[0]['name'])->equals('VIP');
    verify((int)$tags[0]['id'] > 0)->true();
    verify($response->data['profile']['custom_fields'])->equals([
      [
        'id' => (string)$customField->getId(),
        'name' => 'Favorite color',
        'value' => 'Blue',
      ],
    ]);
  }

  /**
   * @group woo
   */
  public function testItReturnsShippingAddressFromWcCustomer(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('jane.doe@example.com')
      ->withWpUserId(123)
      ->create();

    $customer = new \WC_Customer();
    $customer->set_shipping_first_name('Jane');
    $customer->set_shipping_last_name('Doe');
    $customer->set_shipping_address_1('742 Evergreen Terrace');
    $customer->set_shipping_city('Springfield');
    $customer->set_shipping_postcode('97403');
    $customer->set_shipping_country('US');

    $wooHelper = $this->createMock(WooCommerceHelper::class);
    $wooHelper->method('isWooCommerceActive')->willReturn(true);
    $wooHelper->method('wcGetCustomer')->with(123)->willReturn($customer);
    $wooHelper->method('WC')->willReturn(\WC());

    $endpoint = $this->buildEndpoint($wooHelper);
    $response = $endpoint->get(['subscriber_id' => $subscriber->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    $shippingAddress = $response->data['profile']['shipping_address'];
    verify($shippingAddress)->notEmpty();
    verify($shippingAddress[0])->equals('Jane Doe');
    verify(implode("\n", $shippingAddress))->stringContainsString('742 Evergreen Terrace');
    verify(implode("\n", $shippingAddress))->stringContainsString('Springfield');
    verify(implode("\n", $shippingAddress))->stringContainsString('97403');
  }

  /**
   * @group woo
   */
  public function testItDoesNotQueryOrdersForNonWooSubscriberWhenWooActive(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('plain.subscriber@example.com')
      ->withWpUserId(456)
      ->withIsWooCommerceUser(false)
      ->create();

    $wooHelper = $this->createMock(WooCommerceHelper::class);
    $wooHelper->method('isWooCommerceActive')->willReturn(true);
    $wooHelper->method('wcGetCustomer')->with(456)->willReturn(null);
    $wooHelper->expects($this->never())->method('wcGetOrders');

    $endpoint = $this->buildEndpoint($wooHelper);
    $response = $endpoint->get(['subscriber_id' => $subscriber->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['profile']['shipping_address'])->equals([]);
  }

  public function testItOmitsShippingAddressWhenWooInactive(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('no-woo@example.com')
      ->create();

    $wooHelper = $this->createMock(WooCommerceHelper::class);
    $wooHelper->method('isWooCommerceActive')->willReturn(false);
    $wooHelper->expects($this->never())->method('wcGetCustomer');
    $wooHelper->expects($this->never())->method('wcGetOrders');

    $endpoint = $this->buildEndpoint($wooHelper);
    $response = $endpoint->get(['subscriber_id' => $subscriber->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['is_woo_active'])->false();
    verify($response->data['profile']['shipping_address'])->equals([]);
  }

  public function testItReturnsLatestUnsubscribeReasonInProfile(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('unsub.reason@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $unsubscribe = new StatisticsUnsubscribeEntity(null, null, $subscriber);
    $unsubscribe->setReasonData(StatisticsUnsubscribeEntity::REASON_SPAM, null);
    $this->entityManager->persist($unsubscribe);
    $this->entityManager->flush();

    $response = $this->endpoint->get(['subscriber_id' => $subscriber->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    $expectedLabel = $this->diContainer->get(UnsubscribeReasonTracker::class)
      ->getReasonLabels()[StatisticsUnsubscribeEntity::REASON_SPAM];
    verify($response->data['profile']['unsubscribe_reason'])->equals($expectedLabel);
  }

  private function buildEndpoint(WooCommerceHelper $wooHelper): SubscriberStats {
    return new SubscriberStats(
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriberStatisticsRepository::class),
      $wooHelper,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(StatisticsUnsubscribesRepository::class),
      $this->diContainer->get(UnsubscribeReasonTracker::class)
    );
  }

  public function testItReturnsEngagementPeriods(): void {
    $subscriber = (new SubscriberFactory())->create();
    $now = Carbon::now();

    $this->createNewsletterStats($subscriber, $now->copy()->subDays(5));
    $this->createNewsletterStats($subscriber, $now->copy()->subMonths(6));
    $this->createNewsletterStats($subscriber, $now->copy()->subYears(2));

    $response = $this->endpoint->get(['subscriber_id' => $subscriber->getId()]);
    $periods = [];
    foreach ($response->data['periodic_stats'] as $period) {
      $periods[$period['key']] = $period;
    }

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify(array_keys($periods))->equals([
      '7_days',
      '30_days',
      '3_months',
      '12_months',
      'lifetime',
    ]);
    verify($periods['7_days']['timeframe'])->equals('7 days');
    verify($periods['30_days']['timeframe'])->equals('30 days');
    verify($periods['3_months']['timeframe'])->equals('3 months');
    verify($periods['12_months']['timeframe'])->equals('12 months');
    verify($periods['lifetime']['timeframe'])->equals('Lifetime');
    verify($periods['7_days']['total_sent'])->equals(1);
    verify($periods['30_days']['total_sent'])->equals(1);
    verify($periods['3_months']['total_sent'])->equals(1);
    verify($periods['12_months']['total_sent'])->equals(2);
    verify($periods['lifetime']['total_sent'])->equals(3);
    verify($periods['lifetime']['open'])->equals(3);
    verify($periods['lifetime']['click'])->equals(3);
  }

  private function createNewsletterStats($subscriber, \DateTimeInterface $date): void {
    $newsletter = (new NewsletterFactory())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    (new StatisticsNewsletters($newsletter, $subscriber))
      ->withSentAt($date)
      ->create();
    (new StatisticsOpens($newsletter, $subscriber))
      ->withCreatedAt($date)
      ->create();
    (new StatisticsClicks($link, $subscriber))
      ->withCreatedAt($date)
      ->create();
  }
}
