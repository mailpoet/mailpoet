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
use PHPUnit_Framework_MockObject_MockObject;
use WC_Order;
use function MailPoet\Util\array_column;

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

  function _before() {
    parent::_before();
    $this->cleanup();

    $this->subscriber = $this->createSubscriber('test@example.com');
    $this->newsletter = $this->createNewsletter();
    $this->queue = $this->createQueue($this->newsletter, $this->subscriber);
    $this->link = $this->createLink($this->newsletter, $this->queue);
    $this->cookies = new Cookies();
  }

  function testItTracksPayment() {
    $click = $this->createClick($this->link, $this->subscriber);
    $order_mock = $this->createOrderMock($this->subscriber->email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    $purchase_stats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchase_stats))->equals(1);
    expect($purchase_stats[0]->newsletter_id)->equals($this->newsletter->id);
    expect($purchase_stats[0]->subscriber_id)->equals($this->subscriber->id);
    expect($purchase_stats[0]->queue_id)->equals($this->queue->id);
    expect($purchase_stats[0]->click_id)->equals($click->id);
    expect($purchase_stats[0]->order_id)->equals($order_mock->get_id());
    expect($purchase_stats[0]->order_currency)->equals($order_mock->get_currency());
    expect($purchase_stats[0]->order_price_total)->equals($order_mock->get_total());
  }

  function testItTracksPaymentForMultipleNewsletters() {
    $click_1 = $this->createClick($this->link, $this->subscriber);

    // create click in other newsletter
    $newsletter = $this->createNewsletter();
    $queue = $this->createQueue($newsletter, $this->subscriber);
    $link = $this->createLink($newsletter, $queue);
    $click_2 = $this->createClick($link, $this->subscriber, 1);

    $order_mock = $this->createOrderMock($this->subscriber->email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    $purchase_stats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchase_stats))->equals(2);

    $stats_1 = StatisticsWooCommercePurchases::where('newsletter_id', $this->newsletter->id)->findOne();
    expect($stats_1->click_id)->equals($click_1->id);
    expect($stats_1->subscriber_id)->equals($this->subscriber->id);
    expect($stats_1->queue_id)->equals($this->queue->id);

    $stats_2 = StatisticsWooCommercePurchases::where('newsletter_id', $newsletter->id)->findOne();
    expect($stats_2->click_id)->equals($click_2->id);
    expect($stats_2->subscriber_id)->equals($this->subscriber->id);
    expect($stats_2->queue_id)->equals($queue->id);
  }

  function testItTracksPaymentForMultipleOrders() {
    $this->createClick($this->link, $this->subscriber);

    // first order
    $order_mock = $this->createOrderMock($this->subscriber->email, 10.0, 123);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());

    // second order
    $order_mock = $this->createOrderMock($this->subscriber->email, 20.0, 456);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());

    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(2);
  }

  function testItTracksPaymentOnlyForLatestClick() {
    $latest_click = $this->createClick($this->link, $this->subscriber, 1);
    $this->createClick($this->link, $this->subscriber, 3);
    $this->createClick($this->link, $this->subscriber, 5);
    $order_mock = $this->createOrderMock($this->subscriber->email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());

    $purchase_stats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchase_stats))->equals(1);
    expect($purchase_stats[0]->click_id)->equals($latest_click->id);
  }

  function testItTracksPaymentOnlyOnce() {
    $this->createClick($this->link, $this->subscriber);
    $order_mock = $this->createOrderMock($this->subscriber->email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(1);
  }

  function testItDoesNotTrackPaymentWhenClickTooOld() {
    $this->createClick($this->link, $this->subscriber, 20);
    $order_mock = $this->createOrderMock($this->subscriber->email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(0);
  }

  function testItDoesNotTrackPaymentForDifferentEmail() {
    $this->createClick($this->link, $this->subscriber);
    $order_mock = $this->createOrderMock('different.email@example.com');
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(0);
  }

  function testItDoesNotTrackPaymentWhenClickNewerThanOrder() {
    $this->createClick($this->link, $this->subscriber, 0);
    $order_mock = $this->createOrderMock($this->subscriber->email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    expect(count(StatisticsWooCommercePurchases::findMany()))->equals(0);
  }

  function testItTracksByCookie() {
    $order_email = 'order.email@example.com';
    $cookie_email = 'cookie.email@example.com';
    $this->createSubscriber($order_email);
    $cookie_email_subscriber = $this->createSubscriber($cookie_email);

    $click = $this->createClick($this->link, $cookie_email_subscriber);
    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $click->id,
      'created_at' => time(),
    ]);

    $order_mock = $this->createOrderMock($order_email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    $purchase_stats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchase_stats))->equals(1);
    expect($purchase_stats[0]->click_id)->equals($click->id);
  }

  function testItDoesNotTrackByCookieWhenTrackedByOrder() {
    $order_email = 'order.email@example.com';
    $cookie_email = 'cookie.email@example.com';
    $order_email_subscriber = $this->createSubscriber($order_email);
    $cookie_email_subscriber = $this->createSubscriber($cookie_email);

    // both clicks are in the same newsletter
    $order_email_click = $this->createClick($this->link, $order_email_subscriber);
    $cookie_email_click = $this->createClick($this->link, $cookie_email_subscriber);

    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $cookie_email_click->id,
      'created_at' => time(),
    ]);

    $order_mock = $this->createOrderMock($order_email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    $purchase_stats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchase_stats))->equals(1);
    expect($purchase_stats[0]->click_id)->equals($order_email_click->id);
  }

  function testItTracksByBothOrderAndCookieForDifferentNewsletters() {
    $order_email = 'order.email@example.com';
    $cookie_email = 'cookie.email@example.com';
    $order_email_subscriber = $this->createSubscriber($order_email);
    $cookie_email_subscriber = $this->createSubscriber($cookie_email);

    // click by order email subscriber
    $order_email_click = $this->createClick($this->link, $order_email_subscriber);

    // click by cookie email subscriber in a different newsletter
    $newsletter = $this->createNewsletter();
    $queue = $this->createQueue($newsletter, $this->subscriber);
    $link = $this->createLink($newsletter, $queue);
    $cookie_email_click = $this->createClick($link, $cookie_email_subscriber);

    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $cookie_email_click->id,
      'created_at' => time(),
    ]);

    $order_mock = $this->createOrderMock($order_email);
    $woocommerce_purchases = new WooCommercePurchases($this->createWooCommerceHelperMock($order_mock), $this->cookies);
    $woocommerce_purchases->trackPurchase($order_mock->get_id());
    $purchase_stats = StatisticsWooCommercePurchases::findMany();
    expect(count($purchase_stats))->equals(2);
    foreach ($purchase_stats as $stats) {
      if ($stats->click_id === $order_email_click->id) {
        expect($stats->newsletter_id)->equals($this->newsletter->id);
      } else {
        expect($stats->click_id)->equals($cookie_email_click->id);
        expect($stats->newsletter_id)->equals($newsletter->id);
      }
    }
  }

  function _after() {
    $this->cleanup();
  }

  private function cleanup() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsWooCommercePurchases::$_table);
  }

  private function createNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    return $newsletter->save();
  }

  private function createQueue(Newsletter $newsletter, Subscriber $subscriber) {
    $queue = Sending::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->setSubscribers([$subscriber->id]);
    $queue->updateProcessedSubscribers([$subscriber->id]);
    return $queue->save();
  }

  private function createSubscriber($email) {
    $subscriber = Subscriber::create();
    $subscriber->email = $email;
    $subscriber->first_name = 'First';
    $subscriber->last_name = 'Last';
    return $subscriber->save();
  }

  private function createLink(Newsletter $newsletter, Sending $queue) {
    $link = NewsletterLink::create();
    $link->hash = 'hash';
    $link->url = 'url';
    $link->newsletter_id = $newsletter->id;
    $link->queue_id = $queue->id;
    return $link->save();
  }

  private function createClick(NewsletterLink $link, Subscriber $subscriber, $created_days_ago = 5) {
    $click = StatisticsClicks::create();
    $click->newsletter_id = $link->newsletter_id;
    $click->subscriber_id = $subscriber->id;
    $click->queue_id = $link->queue_id;
    $click->link_id = $link->id;
    $click->count = 1;

    $timestamp = new DateTime("-$created_days_ago days");
    $click->created_at = $timestamp->format('Y-m-d H:i:s');
    $click->updated_at = $timestamp->format('Y-m-d H:i:s');
    return $click->save();
  }

  private function createWooCommerceHelperMock(PHPUnit_Framework_MockObject_MockObject $order_mock) {
    $mock = $this->createMock(WooCommerceHelper::class);
    $mock->method('wcGetOrder')->willReturn($order_mock);
    return $mock;
  }

  private function createOrderMock($email, $total_price = 15.0, $id = 123) {
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
    $mock->method('get_total')->willReturn((string)$total_price);
    $mock->method('get_currency')->willReturn('EUR');
    return $mock;
  }
}
