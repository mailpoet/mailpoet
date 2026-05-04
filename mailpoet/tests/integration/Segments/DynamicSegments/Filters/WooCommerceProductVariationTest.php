<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceProductVariationTest extends \MailPoetTest {
  /** @var WooCommerceProductVariation */
  private $wooCommerceProductVariationFilter;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var int[] */
  private $productIds = [];

  /** @var int[] */
  private $variationIds = [];

  /** @var int[] */
  private $orderIds = [];

  public function _before(): void {
    $this->wooCommerceProductVariationFilter = $this->diContainer->get(WooCommerceProductVariation::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->cleanUp();

    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $customerId2 = $this->tester->createCustomer('customer2@example.com');
    $customerIdOnHold = $this->tester->createCustomer('customer-on-hold@example.com');
    $customerIdMulti = $this->tester->createCustomer('customer-multi-orders@example.com');

    $this->createSubscriber('a1@example.com');
    $this->createSubscriber('a2@example.com');

    $this->productIds[] = $this->createProduct('variableProduct1');
    $this->productIds[] = $this->createProduct('variableProduct2');

    $this->variationIds[] = $this->createVariation($this->productIds[0], 'variableProduct1 - red');
    $this->variationIds[] = $this->createVariation($this->productIds[0], 'variableProduct1 - blue');
    $this->variationIds[] = $this->createVariation($this->productIds[1], 'variableProduct2 - small');

    $this->orderIds[] = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $this->orderIds[0], $this->productIds[0], $this->variationIds[0], $customerId1);
    $this->orderIds[] = $this->createOrder($customerId2, Carbon::now());
    $this->addToOrder(2, $this->orderIds[1], $this->productIds[0], $this->variationIds[1], $customerId2);
    $this->orderIds[] = $this->createOrder($customerIdOnHold, Carbon::now(), 'wc-on-hold');
    $this->addToOrder(3, $this->orderIds[2], $this->productIds[0], $this->variationIds[0], $customerIdOnHold);

    // customer-multi-orders bought variation[0] and variation[1] in two separate completed orders.
    // This exercises the "all of" operator across orders.
    $this->orderIds[] = $this->createOrder($customerIdMulti, Carbon::now());
    $this->addToOrder(4, $this->orderIds[3], $this->productIds[0], $this->variationIds[0], $customerIdMulti);
    $this->orderIds[] = $this->createOrder($customerIdMulti, Carbon::now());
    $this->addToOrder(5, $this->orderIds[4], $this->productIds[0], $this->variationIds[1], $customerIdMulti);
  }

  public function testItGetsSubscribersThatPurchasedAnyVariation(): void {
    $expectedEmails = [
      'customer1@example.com',
      'customer2@example.com',
      'customer-multi-orders@example.com',
    ];
    $segmentFilterData = $this->getSegmentFilterData(
      [$this->variationIds[0], $this->variationIds[1]],
      DynamicSegmentFilterData::OPERATOR_ANY
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductVariationFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatPurchasedNoneOfVariations(): void {
    $expectedEmails = [
      'a1@example.com',
      'a2@example.com',
      'customer-on-hold@example.com',
      'customer2@example.com',
    ];
    $segmentFilterData = $this->getSegmentFilterData(
      [$this->variationIds[0]],
      DynamicSegmentFilterData::OPERATOR_NONE
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductVariationFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatPurchasedAllVariations(): void {
    // customer-multi-orders bought variation[0] and variation[1] in two separate orders.
    // The "all of" operator must match across orders, not require both in the same order.
    $expectedEmails = ['customer-multi-orders@example.com'];
    $segmentFilterData = $this->getSegmentFilterData(
      [$this->variationIds[0], $this->variationIds[1]],
      DynamicSegmentFilterData::OPERATOR_ALL
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductVariationFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);

    $expectedEmails = ['customer1@example.com', 'customer-multi-orders@example.com'];
    $segmentFilterData = $this->getSegmentFilterData(
      [$this->variationIds[0]],
      DynamicSegmentFilterData::OPERATOR_ALL
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductVariationFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItRetrievesLookupData(): void {
    $segmentFilterData = $this->getSegmentFilterData(
      [$this->variationIds[0], 999999],
      DynamicSegmentFilterData::OPERATOR_ANY
    );
    $lookupData = $this->wooCommerceProductVariationFilter->getLookupData($segmentFilterData);
    verify($lookupData)->arrayHasKey('variations');
    verify($lookupData['variations'])->arrayHasKey($this->variationIds[0]);
  }

  private function getSegmentFilterData(array $variationIds, string $operator): DynamicSegmentFilterData {
    $filterData = [
      'variation_ids' => $variationIds,
      'operator' => $operator,
    ];

    return new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceProductVariation::ACTION_PRODUCT_VARIATION,
      $filterData
    );
  }

  private function createOrder(int $customerId, Carbon $createdAt, string $status = 'wc-completed'): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status($status);
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }

  private function createProduct(string $name): int {
    $productData = [
      'post_type' => 'product',
      'post_status' => 'publish',
      'post_title' => $name,
    ];
    $productId = wp_insert_post($productData);
    $this->assertIsInt($productId);
    return $productId;
  }

  private function createVariation(int $parentId, string $name): int {
    $variationData = [
      'post_type' => 'product_variation',
      'post_status' => 'publish',
      'post_title' => $name,
      'post_parent' => $parentId,
    ];
    $variationId = wp_insert_post($variationData);
    $this->assertIsInt($variationId);
    return $variationId;
  }

  private function addToOrder(int $orderItemId, int $orderId, int $productId, int $variationId, int $customerId): void {
    global $wpdb;
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_order_product_lookup (order_item_id, order_id, product_id, customer_id, variation_id, product_qty, date_created)
      VALUES ({$orderItemId}, {$orderId}, {$productId}, {$customerId}, {$variationId}, 1, now())
    ");
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    return $subscriber;
  }

  public function _after(): void {
    parent::_after();
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;

    foreach ($this->variationIds as $variationId) {
      wp_delete_post($variationId, true);
    }
    foreach ($this->productIds as $productId) {
      wp_delete_post($productId, true);
    }
    $this->variationIds = [];
    $this->productIds = [];

    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
  }
}
