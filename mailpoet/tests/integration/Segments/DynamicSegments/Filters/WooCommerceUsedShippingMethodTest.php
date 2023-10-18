<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceUsedShippingMethod;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Carbon\Carbon;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * @group woo
 */
class WooCommerceUsedShippingMethodTest extends \MailPoetTest {

  /** @var WooCommerceUsedShippingMethod */
  private $filter;

  /** @var Helper */
  private $wooHelper;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommerceUsedShippingMethod::class);
    $this->wooHelper = $this->diContainer->get(Helper::class);
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

    $this->assertFilterReturnsEmails('any', [1], 1, 'inTheLast', ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('any', [2], 1, 'inTheLast', ['c2@e.com']);
    $this->assertFilterReturnsEmails('any', [2, 1], 1, 'inTheLast', ['c1@e.com', 'c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('any', [8], 1000, 'inTheLast', []); // non-existing shipping method
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

    $this->assertFilterReturnsEmails('all', [1], 1, 'inTheLast', ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', [2], 1, 'inTheLast', ['c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', [2, 1], 1, 'inTheLast', ['c3@e.com']);
    $this->assertFilterReturnsEmails('all', [8, 1], 1000, 'inTheLast', []); // includes non-existing shipping method
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

    $this->assertFilterReturnsEmails('none', [1], 1, 'inTheLast', ['sub@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', [2], 1, 'inTheLast', ['sub@e.com', 'c1@e.com']);
    $this->assertFilterReturnsEmails('none', [8], 1000, 'inTheLast', ['sub@e.com', 'c1@e.com', 'c2@e.com', 'c3@e.com']); // non-existing shipping method
    $this->assertFilterReturnsEmails('none', [1, 2], 1, 'inTheLast', ['sub@e.com']);
  }

  public function testItWorksWithDateRanges(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(2)->addMinute(), 'flat_rate', 1);
    $this->createOrder($customerId1, Carbon::now()->subDays(5)->addMinute(), 'free_shipping', 2);

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now()->subDays(100)->addMinute(), 'local_pickup', 3);
    $this->assertFilterReturnsEmails('any', [1], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', [1], 2, 'inTheLast', ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', [2], 4, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', [2], 5, 'inTheLast', ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', [3], 99, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', [3], 100, 'inTheLast', ['c2@e.com']);
    $this->assertFilterReturnsEmails('any', [3, 1], 100, 'inTheLast', ['c1@e.com', 'c2@e.com']);

    $this->assertFilterReturnsEmails('all', [1], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('all', [1], 2, 'inTheLast', ['c1@e.com']);
    $this->assertFilterReturnsEmails('all', [1, 2], 2, 'inTheLast', []);
    $this->assertFilterReturnsEmails('all', [1, 2], 5, 'inTheLast', ['c1@e.com']);

    $this->assertFilterReturnsEmails('none', [1], 1, 'inTheLast', ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', [1], 2, 'inTheLast', ['c2@e.com']);
    $this->assertFilterReturnsEmails('none', [2], 2, 'inTheLast', ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', [2], 5, 'inTheLast', ['c2@e.com']);
  }

  public function testItWorksWithAllTimeTimeframe(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(2)->addMinute(), 'flat_rate', 1);
    $this->createOrder($customerId1, Carbon::now()->subDays(5)->addMinute(), 'free_shipping', 2);

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now()->subDays(100)->addMinute(), 'local_pickup', 3);

    $this->assertFilterReturnsEmails('any', [3, 1], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', [3, 1], 1, 'allTime', ['c1@e.com', 'c2@e.com']);

    $this->assertFilterReturnsEmails('none', [2], 1, 'inTheLast', ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', [2], 1, 'allTime', ['c2@e.com']);

    $this->assertFilterReturnsEmails('all', [2], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('all', [2], 1, 'allTime', ['c1@e.com']);
  }

  public function testItRetrievesLookupData(): void {
    $defaultZone = WC_Shipping_Zones::get_zone(0);
    $this->assertInstanceOf(WC_Shipping_Zone::class, $defaultZone);
    $instanceId1 = $defaultZone->add_shipping_method('flat_rate');
    $instanceId2 = $defaultZone->add_shipping_method('local_pickup');

    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedShippingMethod::ACTION, [
      'operator' => 'any',
      'shipping_methods' => [$instanceId1, 12345],
      'days' => 10,
      'timeframe' => 'inTheLast',
    ]);

    $formattedMethods = $this->wooHelper->getShippingMethodInstancesData();

    $lookupData = $this->filter->getLookupData($filterData);
    $this->assertEqualsCanonicalizing([
      'shippingMethods' => [
        $instanceId1 => $formattedMethods[$instanceId1]['name'],
      ],
    ], $lookupData);

    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedShippingMethod::ACTION, [
      'operator' => 'any',
      'shipping_methods' => [$instanceId2, $instanceId1, 12345],
      'days' => 10,
      'timeframe' => 'inTheLast',
    ]);

    $lookupData = $this->filter->getLookupData($filterData);
    $this->assertEqualsCanonicalizing([
      'shippingMethods' => [
        $instanceId1 => $formattedMethods[$instanceId1]['name'],
        $instanceId2 => $formattedMethods[$instanceId2]['name'],
      ],
    ], $lookupData);

    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedShippingMethod::ACTION, [
      'operator' => 'any',
      'shipping_methods' => [],
      'days' => 10,
      'timeframe' => 'inTheLast',
    ]);
    $lookupData = $this->filter->getLookupData($filterData);
    verify($lookupData)->equals(['shippingMethods' => []]);
  }

  private function assertFilterReturnsEmails(string $operator, array $shippingMethodStrings, int $days, string $timeframe, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedShippingMethod::ACTION, [
      'operator' => $operator,
      'shipping_methods' => $shippingMethodStrings,
      'days' => $days,
      'timeframe' => $timeframe,
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

  public function _after() {
    parent::_after();
    global $wpdb;
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_methods");
  }
}
