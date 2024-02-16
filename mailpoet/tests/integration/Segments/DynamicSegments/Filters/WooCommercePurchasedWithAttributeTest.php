<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\WooCommercePurchasedWithAttribute;

/**
 * @group woo
 */
class WooCommercePurchasedWithAttributeTest extends \MailPoetTest {

  private WooCommercePurchasedWithAttribute $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommercePurchasedWithAttribute::class);
  }

  public function testItWorksWithAnyOperatorForTaxonomies(): void {
    $product1 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['blue']),
      ],
    ]);
    $product2 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['red']),
      ],
    ]);

    $blueTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'blue');
    $redTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'red');

    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $this->createOrder($customer1, [$product1]);
    $this->createOrder($customer2, [$product2]);
    $this->assertFilterReturnsEmailsForTaxonomyAttributes('any', 'pa_color', [$blueTermId, $redTermId], ['customer1@example.com', 'customer2@example.com']);
  }

  public function testItWorksWithNoneOperatorForTaxonomies(): void {
    $product1 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['blue']),
      ],
    ]);
    $product2 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['red']),
      ],
    ]);

    $blueTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'blue');
    $redTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'red');

    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $this->createOrder($customer1, [$product1]);
    $this->createOrder($customer2, [$product2]);
    $this->assertFilterReturnsEmailsForTaxonomyAttributes('none', 'pa_color', [$blueTermId, $redTermId], ['customer3@example.com']);
  }

  public function testItWorksWithAllOperatorForTaxonomies(): void {
    $product1 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['blue']),
      ],
    ]);
    $product2 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['red']),
      ],
    ]);

    $blueTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'blue');
    $redTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'red');

    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $this->createOrder($customer1, [$product1, $product2]);
    $this->createOrder($customer2, [$product2]);
    $this->assertFilterReturnsEmailsForTaxonomyAttributes('all', 'pa_color', [$blueTermId, $redTermId], ['customer1@example.com']);
  }

  public function testItWorksWithAnyOperatorForLocalAttributes(): void {
    $product1 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'red',
      ],
    ]);
    $product2 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'blue',
      ],
    ]);

    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $this->createOrder($customer1, [$product1]);
    $this->createOrder($customer2, [$product2]);

    $this->assertFilterReturnsEmailsForLocalAttributes('any', 'color', ['red', 'blue'], ['customer1@example.com', 'customer2@example.com']);
    $this->assertFilterReturnsEmailsForLocalAttributes('any', 'color', ['red'], ['customer1@example.com']);
    $this->assertFilterReturnsEmailsForLocalAttributes('any', 'color', ['blue'], ['customer2@example.com']);
  }

  public function testItWorksWithAllOperatorForLocalAttributes(): void {
    $product1 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'red',
      ],
    ]);
    $product2 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'blue',
      ],
    ]);

    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $this->createOrder($customer1, [$product1, $product2]);
    $this->createOrder($customer2, [$product1]);
    $this->createOrder($customer3, [$product2]);

    $this->assertFilterReturnsEmailsForLocalAttributes('all', 'color', ['red', 'blue'], ['customer1@example.com']);
    $this->assertFilterReturnsEmailsForLocalAttributes('all', 'color', ['red'], ['customer1@example.com', 'customer2@example.com']);
    $this->assertFilterReturnsEmailsForLocalAttributes('all', 'color', ['blue'], ['customer1@example.com', 'customer3@example.com']);
  }

  public function testItWorksWithNoneOperatorForLocalAttributes(): void {
    $redProduct = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'red',
      ],
    ]);
    $blueProduct = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'blue',
      ],
    ]);

    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');
    $customer4 = $this->tester->createCustomer('customer4@example.com');

    $this->createOrder($customer1, [$redProduct, $blueProduct]);
    $this->createOrder($customer2, [$redProduct]);
    $this->createOrder($customer3, [$blueProduct]);

    $this->assertFilterReturnsEmailsForLocalAttributes('none', 'color', ['red', 'blue'], ['customer4@example.com']);
    $this->assertFilterReturnsEmailsForLocalAttributes('none', 'color', ['red'], ['customer3@example.com', 'customer4@example.com']);
    $this->assertFilterReturnsEmailsForLocalAttributes('none', 'color', ['blue'], ['customer2@example.com', 'customer4@example.com']);
  }

  public function testItRetrievesLookupData(): void {
    $product1 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['blue']),
      ],
    ]);
    $product2 = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'attributes' => [
        $this->tester->getWooCommerceProductAttribute('color', ['red']),
      ],
    ]);

    $blueTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'blue');
    $redTermId = $this->tester->getWooCommerceProductAttributeTermId('color', 'red');

    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommercePurchasedWithAttribute::ACTION, [
      'operator' => 'any',
      'attribute_taxonomy_slug' => 'pa_color',
      'attribute_term_ids' => [$blueTermId, $redTermId],
      'attribute_type' => 'taxonomy',
    ]);

    $lookupData = $this->filter->getLookupData($filterData);

    $this->assertEqualsCanonicalizing([
      'attribute' => 'pa_color',
      'terms' => ['blue', 'red'],
    ], $lookupData);
  }

  public function testItDoesNotGenerateLookupDataForLocalAttributes(): void {
    $redProduct = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'red',
      ],
    ]);
    $blueProduct = $this->tester->createWooCommerceProduct([
      'price' => 20,
      'local_attributes' => [
        'color' => 'blue',
      ],
    ]);

    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommercePurchasedWithAttribute::ACTION, [
      'operator' => 'any',
      'attribute_local_name' => 'color',
      'attribute_local_values' => ['red', 'blue'],
      'attribute_type' => 'local',
    ]);

    $lookupData = $this->filter->getLookupData($filterData);
    $this->assertSame([], $lookupData);
  }

  /**
   * @dataProvider filterDataProvider
   */
  public function testItValidatesFilterData(array $data, bool $isValid): void {
    if (!$isValid) {
      $this->expectException(InvalidFilterException::class);
    }
    $this->filter->validateFilterData($data);
  }

  public function filterDataProvider(): array {
    return [
      'missing term ids' =>
        [
          [
            'operator' => 'any',
            'attribute_type' => 'taxonomy',
            'attribute_taxonomy_slug' => 'pa_color',
            'attribute_term_ids' => [],
          ],
          false,
        ],
      'missing taxonomy slug' =>
        [
          [
            'operator' => 'any',
            'attribute_type' => 'taxonomy',
            'attribute_taxonomy_slug' => '',
            'attribute_term_ids' => ['1'],
          ],
          false,
        ],
      'valid taxonomy' => [
        [
          'operator' => 'any',
          'attribute_type' => 'taxonomy',
          'attribute_taxonomy_slug' => 'pa_something',
          'attribute_term_ids' => ['1'],
        ],
        true,
      ],
      'missing operator' =>
        [
          [
            'operator' => '',
            'attribute_type' => 'taxonomy',
            'attribute_taxonomy_slug' => 'pa_color',
            'attribute_term_ids' => ['1'],
          ],
          false,
        ],
      'invalid operator' =>
        [
          [
            'operator' => 'anyyyyy',
            'attribute_type' => 'taxonomy',
            'attribute_taxonomy_slug' => 'pa_color',
            'attribute_term_ids' => ['1'],
          ],
          false,
        ],
      'missing name' =>
        [
          [
            'operator' => 'any',
            'attribute_type' => 'local',
            'attribute_local_name' => '',
            'attribute_local_values' => ['1'],
          ],
          false,
        ],
      'missing values' =>
        [
          [
            'operator' => 'any',
            'attribute_type' => 'local',
            'attribute_local_name' => 'color',
            'attribute_local_values' => [],
          ],
          false,
        ],
      'valid local' =>
        [
          [
            'operator' => 'any',
            'attribute_type' => 'local',
            'attribute_local_name' => 'color',
            'attribute_local_values' => ['red'],
          ],
          true,
        ],
    ];
  }

  private function assertFilterReturnsEmailsForTaxonomyAttributes(string $operator, string $attributeTaxonomySlug, array $termIds, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommercePurchasedWithAttribute::ACTION, [
      'operator' => $operator,
      'attribute_taxonomy_slug' => $attributeTaxonomySlug,
      'attribute_term_ids' => $termIds,
      'attribute_type' => 'taxonomy',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function assertFilterReturnsEmailsForLocalAttributes(string $operator, string $localAttributeName, array $localAttributeValues, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommercePurchasedWithAttribute::ACTION, [
      'operator' => $operator,
      'attribute_local_name' => $localAttributeName,
      'attribute_local_values' => $localAttributeValues,
      'attribute_type' => 'local',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function createOrder(int $customerId, array $products): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_status('wc-completed');
    foreach ($products as $product) {
      $order->add_product($product);
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
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_product_attributes_lookup");
  }
}
