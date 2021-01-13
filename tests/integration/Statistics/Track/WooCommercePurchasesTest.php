<?php

namespace MailPoet\Test\Statistics\Track;

use DateTime;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsWooCommercePurchases;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Tasks\Sending;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;

class WooCommercePurchasesTest extends \MailPoetTest {
  /** @var Subscriber */
  private $subscriber;

  /** @var Subscriber */
  private $newsletter;

  /** @var Sending */
  private $queue;

  /** @var NewsletterLink */
  private $link;

  /** @var Cookies */
  private $cookies;

  public function _before() {
    parent::_before();
    $this->cleanup();

    $this->subscriber = $this->createSubscriber('test@example.com');
    $this->newsletter = $this->createNewsletter();
    $this->queue = $this->createQueue($this->newsletter, $this->subscriber);
    $this->link = $this->createLink($this->newsletter, $this->queue);
    $this->cookies = new Cookies();
  }

  public function testItDoesNotTrackPaymentForWrongSubscriber() {
    $click = $this->createClick($this->link, $this->subscriber, 3);

    // create 'wrong_click' for different subscriber that is newer than the correct 'click'
    $wrongSubscriber = $this->createSubscriber('wrong.subscriber@example.com');
    $wrongClick = $this->createClick($this->link, $wrongSubscriber, 1);

    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchaseStats))->equals(1);
    expect($purchaseStats[0]->click_id)->equals($click->id);
  }

  public function testItTracksPayment() {
    $click = $this->createClick($this->link, $this->subscriber);
    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchaseStats))->equals(1);
    expect($purchaseStats[0]->newsletter_id)->equals($this->newsletter->id);
    expect($purchaseStats[0]->subscriber_id)->equals($this->subscriber->id);
    expect($purchaseStats[0]->queue_id)->equals($this->queue->id);
    expect($purchaseStats[0]->click_id)->equals($click->id);
    expect($purchaseStats[0]->order_id)->equals($orderMock->get_id());
    expect($purchaseStats[0]->order_currency)->equals($orderMock->get_currency());
    expect($purchaseStats[0]->order_price_total)->equals($orderMock->get_total());
  }

  public function testItTracksPaymentForMultipleNewsletters() {
    $click1 = $this->createClick($this->link, $this->subscriber);

    // create click in other newsletter
    $newsletter = $this->createNewsletter();
    $queue = $this->createQueue($newsletter, $this->subscriber);
    $link = $this->createLink($newsletter, $queue);
    $click2 = $this->createClick($link, $this->subscriber, 1);

    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchaseStats))->equals(2);

    $stats1 = StatisticsWooCommercePurchases::where('newsletter_id', $this->newsletter->id)->findOne();
    assert($stats1 instanceof StatisticsWooCommercePurchases);
    expect($stats1->clickId)->equals($click1->id);
    expect($stats1->subscriberId)->equals($this->subscriber->id);
    expect($stats1->queueId)->equals($this->queue->id);

    $stats2 = StatisticsWooCommercePurchases::where('newsletter_id', $newsletter->id)->findOne();
    assert($stats2 instanceof StatisticsWooCommercePurchases);
    expect($stats2->clickId)->equals($click2->id);
    expect($stats2->subscriberId)->equals($this->subscriber->id);
    expect($stats2->queueId)->equals($queue->id);
  }

  public function testItTracksPaymentForMultipleOrders() {
    $this->createClick($this->link, $this->subscriber);

    // first order
    $orderMock = $this->createOrderMock($this->subscriber->email, 10.0, 123);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    // second order
    $orderMock = $this->createOrderMock($this->subscriber->email, 20.0, 456);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(2);
  }

  public function testItTracksPaymentOnlyForLatestClick() {
    $this->createClick($this->link, $this->subscriber, 3);
    $this->createClick($this->link, $this->subscriber, 5);
    $latestClick = $this->createClick($this->link, $this->subscriber, 1);
    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    $purchaseStats = StatisticsWooCommercePurchases::orderByDesc('created_at')->findMany();
    expect(count($purchaseStats))->equals(1);
    expect($purchaseStats[0]->click_id)->equals($latestClick->id);
  }

  public function testItTracksPaymentOnlyOnce() {
    $this->createClick($this->link, $this->subscriber);
    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(1);
  }

  public function testItTracksPaymentOnlyOnceWhenNewClickAppears() {
    $this->createClick($this->link, $this->subscriber, 5);
    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    $this->createClick($this->link, $this->subscriber, 1);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(1);
  }

  public function testItDoesNotTrackPaymentWhenClickTooOld() {
    $this->createClick($this->link, $this->subscriber, 20);
    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(0);
  }

  public function testItDoesNotTrackPaymentForDifferentEmail() {
    $this->createClick($this->link, $this->subscriber);
    $orderMock = $this->createOrderMock('different.email@example.com');
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(0);
  }

  public function testItDoesNotTrackPaymentWhenClickNewerThanOrder() {
    $this->createClick($this->link, $this->subscriber, 0);
    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(0);
  }

  public function testItTracksPaymentForCorrectClickWhenClickNewerThanOrderExists() {
    $click = $this->createClick($this->link, $this->subscriber, 5);
    $this->createClick($this->link, $this->subscriber, 0); // wrong click, should not be tracked

    $orderMock = $this->createOrderMock($this->subscriber->email);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect($purchaseStats)->count(1);
    expect($purchaseStats[0]->click_id)->equals($click->id);
  }

  public function testItTracksByCookie() {
    $orderEmail = 'order.email@example.com';
    $cookieEmail = 'cookie.email@example.com';
    $this->createSubscriber($orderEmail);
    $cookieEmailSubscriber = $this->createSubscriber($cookieEmail);

    $click = $this->createClick($this->link, $cookieEmailSubscriber);
    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $click->id,
      'created_at' => time(),
    ]);

    $orderMock = $this->createOrderMock($orderEmail);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchaseStats))->equals(1);
    expect($purchaseStats[0]->click_id)->equals($click->id);
  }

  public function testItDoesNotTrackByCookieWhenTrackedByOrder() {
    $orderEmail = 'order.email@example.com';
    $cookieEmail = 'cookie.email@example.com';
    $orderEmailSubscriber = $this->createSubscriber($orderEmail);
    $cookieEmailSubscriber = $this->createSubscriber($cookieEmail);

    // both clicks are in the same newsletter
    $orderEmailClick = $this->createClick($this->link, $orderEmailSubscriber);
    $cookieEmailClick = $this->createClick($this->link, $cookieEmailSubscriber);

    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $cookieEmailClick->id,
      'created_at' => time(),
    ]);

    $orderMock = $this->createOrderMock($orderEmail);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchaseStats))->equals(1);
    expect($purchaseStats[0]->click_id)->equals($orderEmailClick->id);
  }

  public function testItTracksByBothOrderAndCookieForDifferentNewsletters() {
    $orderEmail = 'order.email@example.com';
    $cookieEmail = 'cookie.email@example.com';
    $orderEmailSubscriber = $this->createSubscriber($orderEmail);
    $cookieEmailSubscriber = $this->createSubscriber($cookieEmail);

    // click by order email subscriber
    $orderEmailClick = $this->createClick($this->link, $orderEmailSubscriber);

    // click by cookie email subscriber in a different newsletter
    $newsletter = $this->createNewsletter();
    $queue = $this->createQueue($newsletter, $this->subscriber);
    $link = $this->createLink($newsletter, $queue);
    $cookieEmailClick = $this->createClick($link, $cookieEmailSubscriber);

    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $cookieEmailClick->id,
      'created_at' => time(),
    ]);

    $orderMock = $this->createOrderMock($orderEmail);
    $woocommercePurchases = new WooCommercePurchases($this->createWooCommerceHelperMock($orderMock), $this->cookies);
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchaseStats))->equals(2);
    foreach ($purchaseStats as $stats) {
      if ($stats->clickId === $orderEmailClick->id) {
        expect($stats->newsletterId)->equals($this->newsletter->id);
      } else {
        expect($stats->clickId)->equals($cookieEmailClick->id);
        expect($stats->newsletterId)->equals($newsletter->id);
      }
    }
  }

  public function _after() {
    $this->cleanup();
  }

  private function cleanup() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsWooCommercePurchases::$_table);
  }

  private function createNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    return $newsletter->save();
  }

  private function createQueue(Newsletter $newsletter, Subscriber $subscriber) {
    $queue = Sending::create();
    $queue->newsletterId = $newsletter->id;
    $queue->setSubscribers([$subscriber->id]);
    $queue->updateProcessedSubscribers([$subscriber->id]);
    return $queue->save();
  }

  private function createSubscriber($email) {
    $subscriber = Subscriber::create();
    $subscriber->email = $email;
    $subscriber->firstName = 'First';
    $subscriber->lastName = 'Last';
    return $subscriber->save();
  }

  private function createLink(Newsletter $newsletter, Sending $queue) {
    $link = NewsletterLink::create();
    $link->hash = 'hash';
    $link->url = 'url';
    $link->newsletterId = $newsletter->id;
    $link->queueId = $queue->id;
    return $link->save();
  }

  private function createClick(NewsletterLink $link, Subscriber $subscriber, $createdDaysAgo = 5) {
    $click = StatisticsClicks::create();
    $click->newsletterId = $link->newsletterId;
    $click->subscriberId = $subscriber->id;
    $click->queueId = $link->queueId;
    $click->linkId = (int)$link->id;
    $click->count = 1;

    $timestamp = new DateTime("-$createdDaysAgo days");
    $click->createdAt = $timestamp->format('Y-m-d H:i:s');
    $click->updatedAt = $timestamp->format('Y-m-d H:i:s');
    return $click->save();
  }

  private function createWooCommerceHelperMock(MockObject $orderMock) {
    $mock = $this->createMock(WooCommerceHelper::class);
    $mock->method('wcGetOrder')->willReturn($orderMock);
    return $mock;
  }

  private function createOrderMock($email, $totalPrice = 15.0, $id = 123) {
    // WC_Order class needs to be mocked without default 'disallowMockingUnknownTypes'
    // since WooCommerce may not be active (would result in error mocking undefined class)
    $mock = $this->getMockBuilder(WC_Order::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_id', 'get_date_created', 'get_billing_email', 'get_total', 'get_currency'])
      ->getMock();

    $mock->method('get_id')->willReturn($id);
    $mock->method('get_date_created')->willReturn(new DateTime());
    $mock->method('get_billing_email')->willReturn($email);
    $mock->method('get_total')->willReturn((string)$totalPrice);
    $mock->method('get_currency')->willReturn('EUR');
    return $mock;
  }
}
