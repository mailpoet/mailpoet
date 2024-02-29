<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use WC_Customer;

/**
 * @group woo
 */
class CustomerReviewFieldsFactoryTest extends \MailPoetTest {
  public function testReviewCountField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $reviewCountField = $fields['woocommerce:customer:review-count'];
    $this->assertSame('Review count', $reviewCountField->getName());
    $this->assertSame('integer', $reviewCountField->getType());
    $this->assertSame([
      'params' => ['in_the_last'],
    ], $reviewCountField->getArgs());

    // create products
    $product1Id = $this->tester->createWooCommerceProduct(['name' => 'Product 1'])->get_id();
    $product2Id = $this->tester->createWooCommerceProduct(['name' => 'Product 2'])->get_id();
    $product3Id = $this->tester->createWooCommerceProduct(['name' => 'Product 3'])->get_id();

    // check values (guest)
    $this->createProductReview(0, '', $product1Id);
    $this->createProductReview(0, 'guest@example.com', $product1Id);
    $this->assertSame(0, $reviewCountField->getValue(new CustomerPayload()));

    // check values (registered)
    $id = $this->tester->createCustomer('customer@example.com');
    $this->createProductReview($id, 'customer@example.com', $product1Id); // product 1 (by ID and email)
    $this->createProductReview(0, 'customer@example.com', $product1Id); // product 1 (by email; duplicate - shouldn't be counted)
    $this->createProductReview($id, '', $product1Id); // product 1 (by ID; duplicate - shouldn't be counted)
    $this->createProductReview($id, '', $product2Id); // product 2 (by ID)
    $this->createProductReview(0, 'customer@example.com', $product3Id); // product 3 (by email)

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertSame(3, $reviewCountField->getValue($customerPayload));
  }

  public function testReviewCountFieldWithInTheLastParameter(): void {
    $reviewCountField = $this->getFieldsMap()['woocommerce:customer:review-count'];

    // create products
    $product1Id = $this->tester->createWooCommerceProduct(['name' => 'Product 1'])->get_id();
    $product2Id = $this->tester->createWooCommerceProduct(['name' => 'Product 2'])->get_id();
    $product3Id = $this->tester->createWooCommerceProduct(['name' => 'Product 3'])->get_id();

    $getDate = function (string $date): string {
      return (new DateTimeImmutable($date))->format('Y-m-d H:i:s');
    };

    $id = $this->tester->createCustomer('customer@example.com');
    $this->createProductReview($id, 'customer@example.com', $product1Id, $getDate('-1 month')); // product 1 (by ID and email)
    $this->createProductReview(0, 'customer@example.com', $product1Id, $getDate('-1 week')); // product 1 (by email; duplicate, but different date)
    $this->createProductReview($id, '', $product1Id, $getDate('-1 month')); // product 1 (by ID; duplicate, same date)
    $this->createProductReview($id, 'customer@example.com', $product2Id, $getDate('-1 month')); // product 2 (by ID and email)
    $this->createProductReview(0, 'customer@example.com', $product2Id, $getDate('-1 month')); // product 2 (by email; duplicate, same date)
    $this->createProductReview($id, '', $product2Id, $getDate('-1 month')); // product 2 (by ID; duplicate, same date)
    $this->createProductReview(0, 'customer@example.com', $product3Id, $getDate('-1 year')); // product 3 (by email; long time ago)

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertSame(3, $reviewCountField->getValue($customerPayload));
    $this->assertSame(2, $reviewCountField->getValue($customerPayload, ['in_the_last' => 3 * MONTH_IN_SECONDS]));
    $this->assertSame(1, $reviewCountField->getValue($customerPayload, ['in_the_last' => 3 * WEEK_IN_SECONDS]));
    $this->assertSame(0, $reviewCountField->getValue($customerPayload, ['in_the_last' => 3 * DAY_IN_SECONDS]));
  }

  public function testLastReviewDateField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $lastReviewDateField = $fields['woocommerce:customer:last-review-date'];
    $this->assertSame('Last review date', $lastReviewDateField->getName());
    $this->assertSame('datetime', $lastReviewDateField->getType());
    $this->assertSame([], $lastReviewDateField->getArgs());

    $productId = $this->tester->createWooCommerceProduct(['name' => 'Product 1'])->get_id();

    // check values (guest)
    $this->createProductReview(0, '', $productId, '2023-05-04 12:08:29');
    $this->createProductReview(0, 'guest@example.com', $productId, '2023-05-04 12:08:29');
    $this->assertNull($lastReviewDateField->getValue(new CustomerPayload()));

    // check values (registered) - by ID
    $id = $this->tester->createCustomer('customer1@example.com');
    $this->createProductReview($id, 'customer1@example.com', $productId, '2023-05-04 12:08:29');
    $this->createProductReview($id, 'customer1@example.com', $productId, '2023-05-14 19:16:38');
    $this->createProductReview($id, '', $productId, '2023-05-19 23:14:27');

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertEquals(new DateTimeImmutable('2023-05-19 23:14:27'), $lastReviewDateField->getValue($customerPayload));

    // check values (registered) - by email
    $id = $this->tester->createCustomer('customer2@example.com');
    $this->createProductReview($id, 'customer2@example.com', $productId, '2023-05-04 12:08:29');
    $this->createProductReview($id, 'customer2@example.com', $productId, '2023-05-14 19:16:38');
    $this->createProductReview(0, 'customer2@example.com', $productId, '2023-05-19 23:14:27');

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertEquals(new DateTimeImmutable('2023-05-19 23:14:27'), $lastReviewDateField->getValue($customerPayload));
  }

  public function _after(): void {
    parent::_after();
    global $wpdb;
    $wpdb->query("TRUNCATE $wpdb->comments");
  }

  private function createProductReview(int $customerId, string $customerEmail, int $productId, string $date = '2023-06-01 14:03:27'): void {
    wp_insert_comment([
      'comment_type' => 'review',
      'user_id' => $customerId,
      'comment_author_email' => $customerEmail,
      'comment_post_ID' => $productId,
      'comment_parent' => 0,
      'comment_date' => $date,
      'comment_approved' => 1,
    ]);
  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(CustomerSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
