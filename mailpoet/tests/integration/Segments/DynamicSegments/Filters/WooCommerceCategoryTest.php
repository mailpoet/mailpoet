<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceCategoryTest extends \MailPoetTest {
  /** @var WooCommerceCategory */
  private $wooCommerceCategoryFilter;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var int[] */
  private $productIds;

  /** @var int[] */
  private $orderIds;

  /** @var int[] */
  private $categoryIds;

  public function _before(): void {
    $this->wooCommerceCategoryFilter = $this->diContainer->get(WooCommerceCategory::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->cleanUp();

    $customerId1 = $this->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->createCustomer('customer2@example.com', 'customer');
    $customerId3OnHold = $this->createCustomer('customer-on-hold@example.com', 'customer');
    $customerId4PendingPayment = $this->createCustomer('customer-pending-payment@example.com', 'customer');

    $this->createSubscriber('a1@example.com');
    $this->createSubscriber('a2@example.com');

    $this->categoryIds[] = $this->createCategory('productCat1');
    $this->categoryIds[] = $this->createCategory('productCat2');

    $this->productIds[] = $this->createProduct('testProduct1', [$this->categoryIds[0]]);
    $this->productIds[] = $this->createProduct('testProduct2', [$this->categoryIds[1]]);

    $this->orderIds[] = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $this->orderIds[0], $this->productIds[0], $customerId1);
    $this->orderIds[] = $this->createOrder($customerId2, Carbon::now());
    $this->addToOrder(2, $this->orderIds[1], $this->productIds[1], $customerId2);
    $this->orderIds[] = $this->createOrder($customerId3OnHold, Carbon::now(), 'wc-on-hold');
    $this->addToOrder(3, $this->orderIds[2], $this->productIds[1], $customerId3OnHold);
    $this->orderIds[] = $this->createOrder($customerId4PendingPayment, Carbon::now(), 'wc-pending');
    $this->addToOrder(4, $this->orderIds[3], $this->productIds[1], $customerId4PendingPayment);
  }

  public function testItGetsSubscribersThatPurchasedProductsInAnyCategory(): void {
    $expectedEmails = ['customer1@example.com', 'customer2@example.com'];
    $segmentFilterData = $this->getSegmentFilterData($this->categoryIds, DynamicSegmentFilterData::OPERATOR_ANY);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
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
    $segmentFilterData = $this->getSegmentFilterData([$this->categoryIds[0]], DynamicSegmentFilterData::OPERATOR_NONE);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatPurchasedAllProducts(): void {
    $segmentFilterData = $this->getSegmentFilterData($this->categoryIds, DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    expect($emails)->count(0);
    $expectedEmails = ['customer1@example.com'];
    $segmentFilterData = $this->getSegmentFilterData([$this->categoryIds[0]], DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function getSegmentFilterData(array $categoryIds, string $operator): DynamicSegmentFilterData {
    $filterData = [
      'category_ids' => $categoryIds,
      'operator' => $operator,
    ];

    return new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceCategory::ACTION_CATEGORY,
      $filterData
    );
  }

  private function createCustomer(string $email, string $role): int {
    global $wpdb;
    $userId = $this->tester->createWordPressUser($email, $role);
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_customer_lookup (customer_id, user_id, first_name, last_name, email)
      VALUES ({$userId}, {$userId}, 'First Name', 'Last Name', '{$email}')
    ");
    return $userId;
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

  private function createProduct(string $name, array $terms): int {
    $productData = [
      'post_type' => 'product',
      'post_status' => 'publish',
      'post_title' => $name,
    ];
    $productId = wp_insert_post($productData);
    if (is_int($productId)) {
      wp_set_object_terms($productId, $terms, 'category');
    }
    return $productId;
  }

  private function createCategory(string $name): int {
    $categoryId = wp_create_category($name);
    $this->assertIsInt($categoryId);
    return $categoryId;
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
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $emails = [
      'customer1@example.com',
      'customer2@example.com',
      'customer-on-hold@example.com',
      'customer-pending-payment@example.com',
    ];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }

    if (!empty($this->orders)) {
      foreach ($this->orders as $orderId) {
        wp_delete_post($orderId);
      }
    }

    if (!empty($this->products)) {
      foreach ($this->products as $productId) {
        wp_delete_post($productId);
      }
    }

    if (!empty($this->categoryIds)) {
      foreach ($this->categoryIds as $categoryId) {
        wp_delete_category($categoryId);
      }
    }

    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
  }
}
