<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceProductTest extends \MailPoetTest {
  /** @var WooCommerceProduct */
  private $wooCommerceProductFilter;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var int[] */
  private $productIds;

  /** @var int[] */
  private $orderIds;

  public function _before(): void {
    $this->wooCommerceProductFilter = $this->diContainer->get(WooCommerceProduct::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->cleanUp();

    $customerId1 = $this->tester->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->tester->createCustomer('customer2@example.com', 'customer');
    $customerIdOnHold = $this->tester->createCustomer('customer-on-hold@example.com', 'customer');
    $customerIdPendingPayment = $this->tester->createCustomer('customer-pending-payment@example.com', 'customer');

    $this->createSubscriber('a1@example.com');
    $this->createSubscriber('a2@example.com');

    $this->productIds[] = $this->createProduct('testProduct1');
    $this->productIds[] = $this->createProduct('testProduct2');

    $this->orderIds[] = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $this->orderIds[0], $this->productIds[0], $customerId1);
    $this->orderIds[] = $this->createOrder($customerId2, Carbon::now());
    $this->addToOrder(2, $this->orderIds[1], $this->productIds[1], $customerId2);
    $this->orderIds[] = $this->createOrder($customerIdOnHold, Carbon::now(), 'wc-on-hold');
    $this->addToOrder(3, $this->orderIds[2], $this->productIds[0], $customerIdOnHold);
    $this->orderIds[] = $this->createOrder($customerIdPendingPayment, Carbon::now(), 'wc-pending');
    $this->addToOrder(4, $this->orderIds[3], $this->productIds[0], $customerIdPendingPayment);
  }

  public function testItGetsSubscribersThatPurchasedAnyProducts(): void {
    $expectedEmails = ['customer1@example.com', 'customer2@example.com'];
    $segmentFilterData = $this->getSegmentFilterData($this->productIds, DynamicSegmentFilterData::OPERATOR_ANY);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatDidNotPurchasedProducts(): void {
    $expectedEmails = [
      'a1@example.com',
      'a2@example.com',
      'customer-on-hold@example.com',
      'customer-pending-payment@example.com',
      'customer2@example.com',
    ];
    $segmentFilterData = $this->getSegmentFilterData([$this->productIds[0]], DynamicSegmentFilterData::OPERATOR_NONE);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatPurchasedAllProducts(): void {
    $segmentFilterData = $this->getSegmentFilterData($this->productIds, DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductFilter);
    verify($emails)->arrayCount(0);

    $expectedEmails = ['customer1@example.com'];
    $segmentFilterData = $this->getSegmentFilterData([$this->productIds[0]], DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceProductFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItRetrievesLookupData(): void {
    $productId1 = $this->createProduct('product one');
    $productId2 = $this->createProduct('product two');

    $this->productIds[] = $productId1;
    $this->productIds[] = $productId2;

    $data = $this->getSegmentFilterData([$productId1, $productId2, 12345], 'none');
    $lookupData = $this->wooCommerceProductFilter->getLookupData($data);
    $this->assertEqualsCanonicalizing([
      'products' => [
        $productId1 => 'product one',
        $productId2 => 'product two',
      ],
    ], $lookupData);
  }

  private function getSegmentFilterData(array $productIds, string $operator): DynamicSegmentFilterData {
    $filterData = [
      'product_ids' => $productIds,
      'operator' => $operator,
    ];

    return new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceProduct::ACTION_PRODUCT,
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

  private function addToOrder(int $orderItemId, int $orderId, int $productId, int $customerId): void {
    global $wpdb;
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_order_product_lookup (order_item_id, order_id, product_id, customer_id, variation_id, product_qty, date_created)
      VALUES ({$orderItemId}, {$orderId}, {$productId}, {$customerId}, 0, 1, now())
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

    if (!empty($this->productIds)) {
      foreach ($this->productIds as $productId) {
        wp_delete_post($productId);
      }
    }

    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
  }
}
