<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use DateTimeImmutable;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;
use WC_Order;

/**
 * @group woo
 */
class OrderAttributionRevenueReaderTest extends \MailPoetTest {
  /** @var OrderAttributionRevenueReader */
  private $reader;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var StatisticsClickEntity */
  private $click;

  /** @var int[] */
  private $orderIds = [];

  public function _before() {
    parent::_before();
    remove_filter('mailpoet_woo_backed_revenue_reporting', '__return_false');
    update_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION, '2022-01-01 00:00:00');
    $this->reader = $this->diContainer->get(OrderAttributionRevenueReader::class);

    $this->subscriber = (new Subscriber())->withFirstName('Mona')->withLastName('Miller')->create();
    $this->newsletter = (new Newsletter())->withSubject('Paged')->withSendingQueue()->withSubscriber($this->subscriber)->create();
    $link = (new NewsletterLink($this->newsletter))->create();
    $this->click = (new StatisticsClicks($link, $this->subscriber))->create();
  }

  public function testItReturnsNullWhenTheReadModelIsUnavailable(): void {
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    verify($this->reader->getNewsletterOrderRowsPage($this->newsletterIds(), $this->from(), $this->to()))->null();
    verify($this->reader->countNewsletterOrderRows($this->newsletterIds(), $this->from(), $this->to()))->null();
  }

  public function testItPagesAndCountsInSql(): void {
    $first = $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 10:00:00'), 10);
    $second = $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 11:00:00'), 30);
    $third = $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 12:00:00'), 20);

    $page = $this->reader->getNewsletterOrderRowsPage($this->newsletterIds(), $this->from(), $this->to(), [
      'order_by' => 'created_at',
      'order' => 'asc',
      'limit' => 2,
      'offset' => 1,
    ]);
    verify(array_column($page ?? [], 'order_id'))->equals([$second->get_id(), $third->get_id()]);
    verify($this->reader->countNewsletterOrderRows($this->newsletterIds(), $this->from(), $this->to()))->equals(3);

    $byRevenue = $this->reader->getNewsletterOrderRowsPage($this->newsletterIds(), $this->from(), $this->to(), [
      'order_by' => 'revenue',
      'order' => 'desc',
    ]);
    verify(array_column($byRevenue ?? [], 'order_id'))->equals([$second->get_id(), $third->get_id(), $first->get_id()]);
  }

  public function testItMatchesTheFullRowsRead(): void {
    $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 10:00:00'), 10);
    $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 11:00:00'), 30);

    $full = $this->reader->getNewsletterOrderRows($this->newsletterIds(), $this->from(), $this->to()) ?? [];
    $page = $this->reader->getNewsletterOrderRowsPage($this->newsletterIds(), $this->from(), $this->to(), ['order_by' => 'created_at', 'order' => 'asc']) ?? [];

    usort($full, function (array $a, array $b): int {
      return $a['order_id'] <=> $b['order_id'];
    });
    usort($page, function (array $a, array $b): int {
      return $a['order_id'] <=> $b['order_id'];
    });
    verify($page)->equals($full);
  }

  public function testItSubtractsRefundsFromTheTotal(): void {
    $order = $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 10:00:00'), 40);
    $refund = wc_create_refund(['order_id' => $order->get_id(), 'amount' => 7]);
    $this->assertNotInstanceOf(\WP_Error::class, $refund);

    $page = $this->reader->getNewsletterOrderRowsPage($this->newsletterIds(), $this->from(), $this->to()) ?? [];
    verify($page)->arrayCount(1);
    verify($page[0]['total'])->equals(33.0);
  }

  public function testItFiltersBySearchAndStatus(): void {
    $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 10:00:00'), 10);
    $cancelled = $this->createAttributedOrder(new DateTimeImmutable('2022-01-02 11:00:00'), 30, 'cancelled');
    $ids = $this->newsletterIds();

    verify($this->reader->countNewsletterOrderRows($ids, $this->from(), $this->to(), ['search' => 'Miller']))->equals(2);
    verify($this->reader->countNewsletterOrderRows($ids, $this->from(), $this->to(), ['search' => 'nobody']))->equals(0);

    $page = $this->reader->getNewsletterOrderRowsPage($ids, $this->from(), $this->to(), ['status' => ['wc-cancelled']]) ?? [];
    verify(array_column($page, 'order_id'))->equals([$cancelled->get_id()]);
    verify($this->reader->countNewsletterOrderRows($ids, $this->from(), $this->to(), ['status' => ['wc-cancelled'], 'search' => 'Mona']))->equals(1);

    $otherNewsletter = (new Newsletter())->withSubject('No orders')->withSendingQueue()->create();
    $bothNewsletters = array_merge($ids, [(int)$otherNewsletter->getId()]);
    verify($this->reader->countNewsletterOrderRows($bothNewsletters, $this->from(), $this->to(), ['emails' => $ids]))->equals(2);
    verify($this->reader->countNewsletterOrderRows($bothNewsletters, $this->from(), $this->to(), ['emails' => [(int)$otherNewsletter->getId()]]))->equals(0);
  }

  public function testItIncludesLegacyRowsWithoutAResolvedSource(): void {
    $order = new WC_Order();
    $order->set_date_created((new DateTimeImmutable('2022-01-02 10:00:00'))->getTimestamp());
    $order->set_total('15');
    $order->set_status('completed');
    $order->save();
    $this->orderIds[] = $order->get_id();
    (new StatisticsWooCommercePurchases($this->click, ['id' => $order->get_id(), 'currency' => 'USD', 'total' => 15]))
      ->withCreatedAt(new DateTimeImmutable('2022-01-02 10:00:00'))
      ->create();

    $page = $this->reader->getNewsletterOrderRowsPage($this->newsletterIds(), $this->from(), $this->to()) ?? [];
    verify(array_column($page, 'order_id'))->equals([$order->get_id()]);
    verify($page[0]['total'])->equals(15.0);
    verify($page[0]['status'])->equals('wc-completed');
  }

  public function _after() {
    foreach ($this->orderIds as $orderId) {
      $order = wc_get_order($orderId);
      if ($order instanceof WC_Order) {
        $order->delete(true);
      }
    }
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    parent::_after();
  }

  private function createAttributedOrder(DateTimeImmutable $createdAt, float $total, string $status = 'completed'): WC_Order {
    $order = new WC_Order();
    $order->set_date_created($createdAt->getTimestamp());
    $order->set_total((string)$total);
    $order->set_status($status);
    $order->save();
    $this->orderIds[] = $order->get_id();
    (new StatisticsWooCommercePurchases($this->click, ['id' => $order->get_id(), 'currency' => 'USD', 'total' => $total]))
      ->withCreatedAt($createdAt)
      ->create();
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), 'utm');
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'mailpoet');
    $order->save_meta_data();
    return $order;
  }

  /**
   * @return int[]
   */
  private function newsletterIds(): array {
    return [(int)$this->newsletter->getId()];
  }

  private function from(): DateTimeImmutable {
    return new DateTimeImmutable('2022-01-01');
  }

  private function to(): DateTimeImmutable {
    return new DateTimeImmutable('2022-01-04');
  }
}
