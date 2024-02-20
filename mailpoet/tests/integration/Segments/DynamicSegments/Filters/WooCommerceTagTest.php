<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceTag;

/**
 * @group woo
 */
class WooCommerceTagTest extends \MailPoetTest {
  private WooCommerceTag $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommerceTag::class);

    $this->cleanUp();
  }

  public function testItWorksForAnyOperator(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $tag1 = $this->tester->createWooTag('tag1');
    $tag2 = $this->tester->createWooTag('tag2');

    $product1 = $this->tester->createWooCommerceProduct([
      'tag_ids' => [$tag1],
    ]);

    $product2 = $this->tester->createWooCommerceProduct([
      'tag_ids' => [$tag2],
    ]);

    $this->createOrder($customer1, [$product1]);
    $this->createOrder($customer2, [$product2]);

    $this->assertFilterReturnsEmails('any', [$tag1], ['customer1@example.com']);
    $this->assertFilterReturnsEmails('any', [$tag2], ['customer2@example.com']);
  }

  public function testItWorksForAllOperator(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $tag1 = $this->tester->createWooTag('tag1');
    $tag2 = $this->tester->createWooTag('tag2');

    $product1 = $this->tester->createWooCommerceProduct([
      'tag_ids' => [$tag1],
    ]);

    $product2 = $this->tester->createWooCommerceProduct([
      'tag_ids' => [$tag2],
    ]);

    $this->createOrder($customer1, [$product1]);
    $this->createOrder($customer2, [$product2]);
    $this->createOrder($customer3, [$product1, $product2]);

    $this->assertFilterReturnsEmails('all', [$tag1, $tag2], ['customer3@example.com']);
  }

  public function testItWorksForNoneOperator(): void {
    $customer1 = $this->tester->createCustomer('customer1@example.com');
    $customer2 = $this->tester->createCustomer('customer2@example.com');
    $customer3 = $this->tester->createCustomer('customer3@example.com');

    $tag1 = $this->tester->createWooTag('tag1');
    $tag2 = $this->tester->createWooTag('tag2');

    $product1 = $this->tester->createWooCommerceProduct([
      'tag_ids' => [$tag1],
    ]);

    $product2 = $this->tester->createWooCommerceProduct([
      'tag_ids' => [$tag2],
    ]);

    $this->createOrder($customer1, [$product1]);
    $this->createOrder($customer2, [$product2]);

    $this->assertFilterReturnsEmails('none', [$tag1], ['customer2@example.com', 'customer3@example.com']);
    $this->assertFilterReturnsEmails('none', [$tag2], ['customer1@example.com', 'customer3@example.com']);
    $this->assertFilterReturnsEmails('none', [$tag1, $tag2], ['customer3@example.com']);
  }

  public function testItRetrievesLookupData(): void {
    $tagId1 = $this->tester->createWooTag('tag50');
    $tagId2 = $this->tester->createWooTag('tag51');

    $data = $this->getSegmentFilterData('none', [$tagId1, $tagId2]);
    $lookupData = $this->filter->getLookupData($data);

    $this->assertEqualsCanonicalizing([
      'tags' => [
        (string)$tagId1 => 'tag50',
        (string)$tagId2 => 'tag51',
      ],
    ], $lookupData);
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
      'missing operator' =>
        [
          [
            'tag_ids' => ['1', '2'],
          ],
          false,
        ],
      'invalid operator' =>
        [
          [
            'operator' => 'invalid',
            'tag_ids' => ['1', '2'],
          ],
          false,
        ],
      'missing tag ids' =>
        [
          [
            'operator' => 'any',
          ],
          false,
        ],
      'empty tag ids' =>
        [
          [
            'operator' => 'any',
            'tag_ids' => [],
          ],
          false,
        ],
      'valid filter' =>
        [
          [
            'operator' => 'any',
            'tag_ids' => ['1', '2'],
          ],
          true,
        ],
    ];
  }

  private function createOrder(int $customerId, array $products = []): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_status('wc-completed');
    foreach ($products as $product) {
      $order->add_product($product);
    }
    $order->save();
    $orderId = $order->get_id();
    $this->tester->updateWooOrderStats($orderId);

    return $orderId;
  }

  private function getSegmentFilterData(string $operator, array $tagIds): DynamicSegmentFilterData {
    $filterData = [
      'tag_ids' => array_map(function($tagId) {
        return (string)$tagId;
      }, $tagIds),
      'operator' => $operator,
    ];
    return new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceTag::ACTION,
      $filterData
    );
  }

  private function assertFilterReturnsEmails(string $operator, array $tagIds, array $expectedEmails): void {
    $filterData = $this->getSegmentFilterData($operator, $tagIds);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function _after(): void {
    parent::_after();
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;

    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
  }
}
