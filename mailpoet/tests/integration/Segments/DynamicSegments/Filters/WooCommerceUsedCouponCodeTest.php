<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceUsedCouponCode;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceUsedCouponCodeTest extends \MailPoetTest {

  /** @var WooCommerceUsedCouponCode */
  private $filter;

  /** @var \WC_Product */
  private $product;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommerceUsedCouponCode::class);
    $this->product = $this->tester->createWooCommerceProduct(['price' => 20]);
  }

  public function testItWorksWithAnyOperator(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');
    $customer4 = $this->tester->createCustomer('customer4@example.com');
    $customer5 = $this->tester->createCustomer('customer5@example.com');

    $coupon1Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 1']);
    $coupon2Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 2']);
    $coupon3Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 3']);

    $this->createOrder($customer1, ['Coupon 1']);
    $this->assertFilterReturnsEmails('any', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com']);

    $this->createOrder($customer2, ['Coupon 2']);
    $this->assertFilterReturnsEmails('any', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer2@example.com']);

    $this->createOrder($customer3, ['Coupon 1', 'Coupon 2']);
    $this->assertFilterReturnsEmails('any', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer2@example.com', 'customer3@example.com']);

    $this->createOrder($customer4, ['Coupon 3']);
    $this->assertFilterReturnsEmails('any', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer2@example.com', 'customer3@example.com']);

    $this->createOrder($customer5, []);
    $this->assertFilterReturnsEmails('any', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer2@example.com', 'customer3@example.com']);
  }

  public function testItWorksWithAnyOperatorAndDateRanges(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $coupon1Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 1']);
    $coupon2Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 2']);

    $this->createOrder($customer1, ['Coupon 1'], Carbon::now()->subDays(10));
    $this->createOrder($customer2, ['Coupon 2'], Carbon::now()->subDays(15));
    $this->createOrder($customer3, ['Coupon 1'], Carbon::now()->subDays(25));

    $this->assertFilterReturnsEmails('any', [$coupon1Id, $coupon2Id], 20, 'inTheLast', ['customer1@example.com', 'customer2@example.com']);
    $this->assertFilterReturnsEmails('any', [$coupon1Id, $coupon2Id], 14, 'inTheLast', ['customer1@example.com']);
  }

  public function testItWorksWithAllOperator(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');
    $customer4 = $this->tester->createCustomer('customer4@example.com');
    $customer5 = $this->tester->createCustomer('customer5@example.com');
    $customer6 = $this->tester->createCustomer('customer6@example.com');

    $coupon1Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 1']);
    $coupon2Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 2']);
    $coupon3Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 3']);

    $this->createOrder($customer1, ['Coupon 1', 'Coupon 2']);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com']);

    // Case where customer has used one of the coupons but not all
    $this->createOrder($customer2, ['Coupon 1']);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com']);

    // Case where customer has used all the coupons plus some additional ones
    $this->createOrder($customer3, ['Coupon 1', 'Coupon 2', 'Coupon 3']);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer3@example.com']);

    // Case where customer hasn't used any of the coupons
    $this->createOrder($customer4, ['Coupon 3']);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer3@example.com']);

    // Case where customer has placed an order without any coupon
    $this->createOrder($customer5, []);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer3@example.com']);

    // Multiple orders
    $this->createOrder($customer6, ['Coupon 1']);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer3@example.com']);
    $this->createOrder($customer6, ['Coupon 2']);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer3@example.com', 'customer6@example.com']);
  }

  public function testItWorksWithAllOperatorAndDateRanges(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $coupon1Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 1']);
    $coupon2Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 2']);

    $this->createOrder($customer1, ['Coupon 1', 'Coupon 2'], Carbon::now()->subDays(10));
    $this->createOrder($customer2, ['Coupon 1'], Carbon::now()->subDays(15));
    $this->createOrder($customer3, ['Coupon 1', 'Coupon 2'], Carbon::now()->subDays(25));

    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 20, 'inTheLast', ['customer1@example.com']);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 30, 'inTheLast', ['customer1@example.com', 'customer3@example.com']);
  }

  public function testItWorksWithNoneOperator(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');
    $customer4 = $this->tester->createCustomer('customer4@example.com');
    $customer5 = $this->tester->createCustomer('customer5@example.com');

    $coupon1Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 1']);
    $coupon2Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 2']);
    $coupon3Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 3']);

    // Case where customer hasn't used any of the coupons
    $this->createOrder($customer1, ['Coupon 3']);
    $this->assertFilterReturnsEmails('none', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer2@example.com', 'customer3@example.com', 'customer4@example.com', 'customer5@example.com']);

    // Case where customer has used one of the coupons
    $this->createOrder($customer2, ['Coupon 1']);
    $this->assertFilterReturnsEmails('none', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer3@example.com', 'customer4@example.com', 'customer5@example.com']);

    // Case where customer has used all the coupons
    $this->createOrder($customer3, ['Coupon 1', 'Coupon 2', 'Coupon 3']);
    $this->assertFilterReturnsEmails('none', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer4@example.com', 'customer5@example.com']);

    // Case where customer has used some coupons, but not the ones of interest
    $this->createOrder($customer4, ['Coupon 3']);
    $this->assertFilterReturnsEmails('none', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer4@example.com', 'customer5@example.com']);

    // Case where customer has placed an order without any coupon
    $this->createOrder($customer5, []);
    $this->assertFilterReturnsEmails('none', [$coupon1Id, $coupon2Id], 10, 'inTheLast', ['customer1@example.com', 'customer4@example.com', 'customer5@example.com']);
  }

  public function testItWorksWithNoneOperatorAndDateRanges(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');
    $customer4 = $this->tester->createCustomer('customer4@example.com');

    $coupon1Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 1']);
    $coupon2Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 2']);

    $this->createOrder($customer1, ['Coupon 1'], Carbon::now()->subDays(10));
    $this->createOrder($customer2, ['Coupon 2'], Carbon::now()->subDays(15));
    $this->createOrder($customer3, ['Coupon 1', 'Coupon 2'], Carbon::now()->subDays(25));

    $this->assertFilterReturnsEmails('none', [$coupon1Id, $coupon2Id], 20, 'inTheLast', ['customer3@example.com', 'customer4@example.com']);
    $this->assertFilterReturnsEmails('none', [$coupon1Id, $coupon2Id], 30, 'inTheLast', ['customer4@example.com']);
  }

  public function testItWorksWithAllTimeOption(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');

    $coupon1Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 1']);
    $coupon2Id = $this->tester->createWooCommerceCoupon(['code' => 'Coupon 2']);

    $this->createOrder($customer1, ['Coupon 1'], Carbon::now()->subDays(1000));

    $this->assertFilterReturnsEmails('any', [$coupon1Id], 50, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', [$coupon1Id], 0, 'allTime', ['customer1@example.com']);


    $this->createOrder($customer1, ['Coupon 2'], Carbon::now()->subDays(1000));
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 50, 'inTheLast', []);
    $this->assertFilterReturnsEmails('all', [$coupon1Id, $coupon2Id], 0, 'allTime', ['customer1@example.com']);

    $this->assertFilterReturnsEmails('none', [$coupon1Id], 100, 'inTheLast', ['customer1@example.com', 'customer2@example.com']);
    $this->assertFilterReturnsEmails('none', [$coupon1Id], 0, 'allTime', ['customer2@example.com']);
  }

  /**
   * @dataProvider filterDataProvider
   */
  public function testItValidatesFilterData(array $data, ?string $expectedExceptionClass): void {

    if (!is_null($expectedExceptionClass)) {
      $this->expectException(InvalidFilterException::class);
    }

    $this->filter->validateFilterData($data);
  }

  public function filterDataProvider(): array {
    return [
      'Empty coupon_code_ids' => [
        [
          'coupon_code_ids' => [],
          'operator' => 'any',
          'timeframe' => 'inTheLast',
          'days' => 10,
        ],
        InvalidFilterException::class,
      ],
      'Coupon_code_ids is missing' => [
        [
          'operator' => 'any',
          'timeframe' => 'inTheLast',
          'days' => 10,
        ],
        InvalidFilterException::class,
      ],
      'Operator is missing' => [
        [
          'coupon_code_ids' => ['1'],
          'timeframe' => 'inTheLast',
          'days' => 10,
        ],
        InvalidFilterException::class,
      ],
      'Timeframe is missing' => [
        [
          'coupon_code_ids' => ['1'],
          'operator' => 'any',
          'days' => 10,
        ],
        InvalidFilterException::class,
      ],
      'Invalid timeframe' => [
        [
          'coupon_code_ids' => ['1'],
          'operator' => 'any',
          'timeframe' => 'invalidTimeframe',
          'days' => 10,
        ],
        InvalidFilterException::class,
      ],
      'Invalid operator' => [
        [
          'coupon_code_ids' => ['1'],
          'operator' => 'invalidOperator',
          'timeframe' => 'inTheLast',
          'days' => 10,
        ],
        InvalidFilterException::class,
      ],
      'Valid coupon_code_ids, any operator, allTime timeframe' => [
        [
          'coupon_code_ids' => ['1'],
          'operator' => 'any',
          'timeframe' => 'allTime',
        ],
        null,
      ],
      'Valid coupon_code_ids, all operator, inTheLast timeframe' => [
        [
          'coupon_code_ids' => ['1'],
          'operator' => 'all',
          'timeframe' => 'inTheLast',
          'days' => 10,
        ],
        null,
      ],
      'Valid coupon_code_ids, none operator, inTheLast timeframe' => [
        [
          'coupon_code_ids' => ['1'],
          'operator' => 'none',
          'timeframe' => 'inTheLast',
          'days' => 10,
        ],
        null,
      ],
    ];
  }

  public function testItRetrievesLookupData(): void {
    $couponId = $this->tester->createWooCommerceCoupon(['code' => 'coupon1']);
    $couponId2 = $this->tester->createWooCommerceCoupon(['code' => 'coupon two']);

    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedCouponCode::ACTION, [
      'operator' => 'none',
      'coupon_code_ids' => [$couponId, (string)$couponId2],
      'days' => 10,
      'timeframe' => 'allTime',
    ]);

    $lookupData = $this->filter->getLookupData($filterData);
    $this->assertEqualsCanonicalizing(['coupons' => [
      $couponId => 'coupon1',
      $couponId2 => 'coupon two',
    ]], $lookupData);
  }

  private function assertFilterReturnsEmails(string $operator, array $couponCodeIds, int $days, string $timeframe, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedCouponCode::ACTION, [
      'operator' => $operator,
      'coupon_code_ids' => $couponCodeIds,
      'days' => $days,
      'timeframe' => $timeframe,
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function createOrder(int $customerId, array $couponCodes = [], Carbon $createdAt = null): int {
    if (is_null($createdAt)) {
      $createdAt = Carbon::now()->subDay();
    }
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status('wc-completed');
    $order->add_product($this->product);
    foreach ($couponCodes as $couponCode) {
      $order->apply_coupon($couponCode);
    }
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }

  public function _after() {
    parent::_after();
    global $wpdb;
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_coupon_lookup");
  }
}
