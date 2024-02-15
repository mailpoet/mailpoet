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

  public function testItWorksWithAnyOperator(): void {
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
    $this->assertFilterReturnsEmails('any', 'pa_color', [$blueTermId, $redTermId], ['customer1@example.com', 'customer2@example.com']);
  }

  public function testItWorksWithNoneOperator(): void {
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
    $this->assertFilterReturnsEmails('none', 'pa_color', [$blueTermId, $redTermId], ['customer3@example.com']);
  }

  public function testItWorksWithAllOperator(): void {
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
    $this->assertFilterReturnsEmails('all', 'pa_color', [$blueTermId, $redTermId], ['customer1@example.com']);
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
    ]);

    $lookupData = $this->filter->getLookupData($filterData);

    $this->assertEqualsCanonicalizing([
      'attribute' => 'pa_color',
      'terms' => ['blue', 'red'],
    ], $lookupData);
  }

  public function testItValidatesOperator(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing operator');
    $this->expectExceptionCode(InvalidFilterException::MISSING_OPERATOR);
    $this->filter->validateFilterData(['operator' => '', 'attribute_taxonomy_slug' => 'pa_color', 'attribute_term_ids' => ['1']]);
  }

  public function testItValidatesAttribute(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing attribute');
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->filter->validateFilterData(['operator' => 'any', 'attribute_taxonomy_slug' => '', 'attribute_term_ids' => ['1']]);
  }

  public function testItValidatesTerms(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing attribute terms');
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->filter->validateFilterData(['operator' => 'any', 'attribute_taxonomy_slug' => 'pa_color', 'attribute_term_ids' => []]);
  }

  private function assertFilterReturnsEmails(string $operator, string $attributeTaxonomySlug, array $termIds, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommercePurchasedWithAttribute::ACTION, [
      'operator' => $operator,
      'attribute_taxonomy_slug' => $attributeTaxonomySlug,
      'attribute_term_ids' => $termIds,
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
