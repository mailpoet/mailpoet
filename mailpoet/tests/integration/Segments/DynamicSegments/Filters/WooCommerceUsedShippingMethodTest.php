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
    $customerId4 = $this->tester->createCustomer('c4@e.com');

    $this->createOrder($customerId1, Carbon::now(), 'flat_rate', 1);
    $this->createOrder($customerId2, Carbon::now(), 'local_pickup', 2);
    $this->createOrder($customerId2, Carbon::now(), 'flat_rate', 4);
    $this->createOrder($customerId3, Carbon::now(), 'flat_rate', 1);
    $this->createOrder($customerId3, Carbon::now(), 'free_shipping', 3);
    $this->createOrder($customerId4, Carbon::now(), 'flat_rate', 4);

    $this->assertFilterReturnsEmails('any', ['flat_rate:1'], 1, ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('any', ['local_pickup:2'], 1, ['c2@e.com']);
    $this->assertFilterReturnsEmails('any', ['local_pickup:2', 'flat_rate:1'], 1, ['c1@e.com', 'c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('any', ['nonexistent_method:1'], 1000, []);
  }

  public function testItWorksWithAllOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now(), 'flat_rate', 1);
    $this->createOrder($customerId1, Carbon::now(), 'flat_rate', 1);

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now(), 'free_shipping', 2);

    $customerId3 = $this->tester->createCustomer('c3@e.com');
    $this->createOrder($customerId3, Carbon::now(), 'free_shipping', 2);
    $this->createOrder($customerId3, Carbon::now(), 'flat_rate', 1);

    $this->assertfilterreturnsemails('all', ['flat_rate:1'], 1, ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['free_shipping:2'], 1, ['c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['free_shipping:2', 'flat_rate:1'], 1, ['c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['nonexistent_method:1', 'flat_rate:1'], 1000, []);
  }

  public function testItWorksWithNoneOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now(), 'flat_rate', 1);
    $this->createOrder($customerId1, Carbon::now(), 'flat_rate', 1);

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now(), 'free_shipping', 2);

    $customerId3 = $this->tester->createCustomer('c3@e.com');
    $this->createOrder($customerId3, Carbon::now(), 'free_shipping', 2);
    $this->createOrder($customerId3, Carbon::now(), 'flat_rate', 1);

    (new Subscriber)->withEmail('sub@e.com')->create();

    $this->assertFilterReturnsEmails('none', ['flat_rate:1'], 1, ['sub@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['free_shipping:2'], 1, ['sub@e.com', 'c1@e.com']);
    $this->assertFilterReturnsEmails('none', ['nonexistent_method:1'], 1000, ['sub@e.com', 'c1@e.com', 'c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('none', ['flat_rate:1', 'free_shipping:2'], 1, ['sub@e.com']);
  }

  public function testItWorksWithDateRanges(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(2)->addMinute(), 'flat_rate', 1);
    $this->createOrder($customerId1, Carbon::now()->subDays(5)->addMinute(), 'free_shipping', 2);

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now()->subDays(100)->addMinute(), 'local_pickup', 3);
    $this->assertFilterReturnsEmails('any', ['flat_rate:1'], 1, []);
    $this->assertFilterReturnsEmails('any', ['flat_rate:1'], 2, ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', ['free_shipping:2'], 4, []);
    $this->assertFilterReturnsEmails('any', ['free_shipping:2'], 5, ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', ['local_pickup:3'], 99, []);
    $this->assertFilterReturnsEmails('any', ['local_pickup:3'], 100, ['c2@e.com']);
    $this->assertFilterReturnsEmails('any', ['local_pickup:3', 'flat_rate:1'], 100, ['c1@e.com', 'c2@e.com']);

    $this->assertFilterReturnsEmails('all', ['flat_rate:1'], 1, []);
    $this->assertFilterReturnsEmails('all', ['flat_rate:1'], 2, ['c1@e.com']);
    $this->assertFilterReturnsEmails('all', ['flat_rate:1', 'free_shipping:2'], 2, []);
    $this->assertFilterReturnsEmails('all', ['flat_rate:1', 'free_shipping:2'], 5, ['c1@e.com']);

    $this->assertFilterReturnsEmails('none', ['flat_rate:1'], 1, ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['flat_rate:1'], 2, ['c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['free_shipping:2'], 2, ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['free_shipping:2'], 5, ['c2@e.com']);
  }

  private function assertFilterReturnsEmails(string $operator, array $shippingMethodStrings, int $days, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedShippingMethod::ACTION, [
      'operator' => $operator,
      'shipping_methods' => $shippingMethodStrings,
      'used_shipping_method_days' => $days,
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function createOrder(int $customerId, Carbon $createdAt, string $shippingMethodId, $shippingInstanceId): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status('wc-completed');

    $shippingItem = new \WC_Order_Item_Shipping();
    $shippingItem->set_method_id($shippingMethodId);
    $shippingItem->set_instance_id($shippingInstanceId);
    $order->add_item($shippingItem);

    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }
}
