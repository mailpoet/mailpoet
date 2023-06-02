<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceUsedShippingMethod;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceUsedShippingMethodTest extends \MailPoetTest {

  /** @var WooCommerceUsedShippingMethod */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommerceUsedShippingMethod::class);
  }

  public function testItWorksWithAnyOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $customerId3 = $this->tester->createCustomer('c3@e.com');

    $this->createOrder($customerId1, Carbon::now(), 'Flat rate');
    $this->createOrder($customerId2, Carbon::now(), 'Local pickup');
    $this->createOrder($customerId3, Carbon::now(), 'Flat rate');
    $this->createOrder($customerId3, Carbon::now(), 'Free shipping');

    $this->assertFilterReturnsEmails('any', ['Flat rate'], 1, ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('any', ['Local pickup'], 1, ['c2@e.com']);
    $this->assertFilterReturnsEmails('any', ['Nonexistent method'], 1000, []);
  }

  public function testItWorksWithAllOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now(), 'Flat rate');
    $this->createOrder($customerId1, Carbon::now(), 'Flat rate');

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now(), 'Free shipping');

    $customerId3 = $this->tester->createCustomer('c3@e.com');
    $this->createOrder($customerId3, Carbon::now(), 'Free shipping');
    $this->createOrder($customerId3, Carbon::now(), 'Flat rate');

    $this->assertfilterreturnsemails('all', ['Flat rate'], 1, ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['Free shipping'], 1, ['c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['Free shipping', 'Flat rate'], 1, ['c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['Nonexistent method', 'Flat rate'], 1000, []);
  }

  public function testItWorksWithNoneOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now(), 'Flat rate');
    $this->createOrder($customerId1, Carbon::now(), 'Flat rate');

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now(), 'Free shipping');

    $customerId3 = $this->tester->createCustomer('c3@e.com');
    $this->createOrder($customerId3, Carbon::now(), 'Free shipping');
    $this->createOrder($customerId3, Carbon::now(), 'Flat rate');

    (new Subscriber)->withEmail('sub@e.com')->create();

    $this->assertFilterReturnsEmails('none', ['Flat rate'], 1, ['sub@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['Free shipping'], 1, ['sub@e.com', 'c1@e.com']);
    $this->assertFilterReturnsEmails('none', ['Nonexistent method'], 1000, ['sub@e.com', 'c1@e.com', 'c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('none', ['Flat rate', 'Free shipping'], 1, ['sub@e.com']);
  }

  public function testItWorksWithDateRanges(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(2)->addMinute(), 'Flat rate');
    $this->createOrder($customerId1, Carbon::now()->subDays(5)->addMinute(), 'Free shipping');

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now()->subDays(100)->addMinute(), 'Local pickup');
    $this->assertFilterReturnsEmails('any', ['Flat rate'], 1, []);
    $this->assertFilterReturnsEmails('any', ['Flat rate'], 2, ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', ['Free shipping'], 4, []);
    $this->assertFilterReturnsEmails('any', ['Free shipping'], 5, ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', ['Local pickup'], 99, []);
    $this->assertFilterReturnsEmails('any', ['Local pickup'], 100, ['c2@e.com']);
    $this->assertFilterReturnsEmails('any', ['Local pickup', 'Flat rate'], 100, ['c1@e.com', 'c2@e.com']);

    $this->assertFilterReturnsEmails('all', ['Flat rate'], 1, []);
    $this->assertFilterReturnsEmails('all', ['Flat rate'], 2, ['c1@e.com']);
    $this->assertFilterReturnsEmails('all', ['Flat rate', 'Free shipping'], 2, []);
    $this->assertFilterReturnsEmails('all', ['Flat rate', 'Free shipping'], 5, ['c1@e.com']);

    $this->assertFilterReturnsEmails('none', ['Flat rate'], 1, ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['Flat rate'], 2, ['c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['Free shipping'], 2, ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['Free shipping'], 5, ['c2@e.com']);
  }

  private function assertFilterReturnsEmails(string $operator, array $shippingMethods, int $days, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedShippingMethod::ACTION, [
      'operator' => $operator,
      'shipping_methods' => $shippingMethods,
      'used_shipping_method_days' => $days,
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function createOrder(int $customerId, Carbon $createdAt, string $shippingMethodTitle): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status('wc-completed');

    $shippingItem = new \WC_Order_Item_Shipping();
    $shippingItem->set_method_title($shippingMethodTitle);
    $order->add_item($shippingItem);

    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }
}
