<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use WC_Customer;
use WC_Order;
use WC_Product_Variation;
use WP_Term;

/**
 * @group woo
 */
class CustomerOrderFieldsFactoryTest extends \MailPoetTest {
  public function testOrderStatsFields(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $spentTotalField = $fields['woocommerce:customer:spent-total'];
    $this->assertSame('Total spent', $spentTotalField->getName());
    $this->assertSame('number', $spentTotalField->getType());
    $this->assertSame([], $spentTotalField->getArgs());

    $spentAverageField = $fields['woocommerce:customer:spent-average'];
    $this->assertSame('Average spent', $spentAverageField->getName());
    $this->assertSame('number', $spentAverageField->getType());
    $this->assertSame([], $spentAverageField->getArgs());

    $orderCountField = $fields['woocommerce:customer:order-count'];
    $this->assertSame('Order count', $orderCountField->getName());
    $this->assertSame('integer', $orderCountField->getType());
    $this->assertSame([], $orderCountField->getArgs());

    // check values (guest)
    $this->createOrder(0, 12.3);
    $this->createOrder(0, 0);
    $this->createOrder(0, 150.0);

    $this->assertSame(0.0, $spentTotalField->getValue(new CustomerPayload()));
    $this->assertSame(0.0, $spentAverageField->getValue(new CustomerPayload()));

    // check values (registered)
    $id = $this->tester->createCustomer('customer@example.com');
    $this->createOrder($id, 12.3);
    $this->createOrder($id, 0);
    $this->createOrder($id, 150.0);
    $this->createOrder($id + 1, 12345.0); // other user

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertSame(162.3, $spentTotalField->getValue($customerPayload));
    $this->assertSame(54.1, $spentAverageField->getValue($customerPayload));
    $this->assertSame(3, $orderCountField->getValue($customerPayload));
  }

  public function testOrderDateFields(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $firstPaidOrderDateField = $fields['woocommerce:customer:first-paid-order-date'];
    $this->assertSame('First paid order date', $firstPaidOrderDateField->getName());
    $this->assertSame('datetime', $firstPaidOrderDateField->getType());
    $this->assertSame([], $firstPaidOrderDateField->getArgs());

    $lastPaidOrderDateField = $fields['woocommerce:customer:last-paid-order-date'];
    $this->assertSame('Last paid order date', $lastPaidOrderDateField->getName());
    $this->assertSame('datetime', $lastPaidOrderDateField->getType());
    $this->assertSame([], $lastPaidOrderDateField->getArgs());

    // check values (guest)
    $this->createOrder(0, 0, '2023-05-03 08:22:38');
    $this->createOrder(0, 12.3, '2023-05-12 17:42:11');
    $this->assertNull($firstPaidOrderDateField->getValue(new CustomerPayload()));
    $this->assertNull($lastPaidOrderDateField->getValue(new CustomerPayload()));

    // check values (registered)
    $id = $this->tester->createCustomer('customer@example.com');
    $this->createOrder($id, 0, '2023-05-03 08:22:38');
    $this->createOrder($id, 12.3, '2023-05-12 17:42:11');
    $this->createOrder($id, 0, '2023-05-19 21:35:03');
    $this->createOrder($id, 150.0, '2023-05-26 11:13:53');
    $this->createOrder($id, 0, '2023-06-01 14:05:01');
    $this->createOrder($id + 1, 0, '2023-06-05 15:42:56'); // other user

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertEquals(new DateTimeImmutable('2023-05-12 17:42:11'), $firstPaidOrderDateField->getValue($customerPayload));
    $this->assertEquals(new DateTimeImmutable('2023-05-26 11:13:53'), $lastPaidOrderDateField->getValue($customerPayload));
  }

  public function testPurchasedCategories(): void {
    // categories
    $uncategorized = get_term_by('slug', 'uncategorized', 'product_cat');
    $uncategorizedId = $uncategorized instanceof WP_Term ? $uncategorized->term_id : null; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $cat1Id = $this->tester->createWordPressTerm('Cat 1', 'product_cat', ['slug' => 'cat-1']);
    $cat2Id = $this->tester->createWordPressTerm('Cat 2', 'product_cat', ['slug' => 'cat-2']);
    $cat3Id = $this->tester->createWordPressTerm('Cat 3', 'product_cat', ['slug' => 'cat-3']);
    $subCat1 = $this->tester->createWordPressTerm('Subcat 1', 'product_cat', ['slug' => 'subcat-1', 'parent' => $cat1Id]);
    $subCat2 = $this->tester->createWordPressTerm('Subcat 2', 'product_cat', ['slug' => 'subcat-2', 'parent' => $cat1Id]);

    // check definitions
    $fields = $this->getFieldsMap();
    $purchasedCategories = $fields['woocommerce:customer:purchased-categories'];
    $this->assertSame('Purchased categories', $purchasedCategories->getName());
    $this->assertSame('enum_array', $purchasedCategories->getType());
    $this->assertSame([
      'options' => [
        ['id' => $cat1Id, 'name' => 'Cat 1'],
        ['id' => $subCat1, 'name' => 'Cat 1 | Subcat 1'],
        ['id' => $subCat2, 'name' => 'Cat 1 | Subcat 2'],
        ['id' => $cat2Id, 'name' => 'Cat 2'],
        ['id' => $cat3Id, 'name' => 'Cat 3'],
        ['id' => $uncategorizedId, 'name' => 'Uncategorized'],
      ],
    ], $purchasedCategories->getArgs());

    // create products
    $p1 = $this->tester->createWooCommerceProduct(['name' => 'Product 1']); // uncategorized
    $p2 = $this->tester->createWooCommerceProduct(['name' => 'Product 2', 'category_ids' => [$cat1Id]]);
    $p3 = $this->tester->createWooCommerceProduct(['name' => 'Product 3', 'category_ids' => [$cat1Id, $cat2Id]]);
    $p4 = $this->tester->createWooCommerceProduct(['name' => 'Product 4', 'category_ids' => [$subCat1]]);
    $p5 = $this->tester->createWooCommerceProduct(['name' => 'Product 5', 'category_ids' => [$cat3Id, $subCat2]]);

    // check values (guest)
    $o1 = $this->createOrder(0, 123);
    $o1->add_product($p1);
    $o1->add_product($p2);

    $o2 = $this->createOrder(0, 999);
    $o2->add_product($p3);
    $o2->add_product($p4);

    $this->assertSame([], $purchasedCategories->getValue(new CustomerPayload()));

    // check values (registered)
    $id = $this->tester->createCustomer('customer@example.com');

    $o1 = $this->createOrder($id, 123);
    $o1->add_product($p1);
    $o1->add_product($p2);

    $o2 = $this->createOrder($id, 999);
    $o2->add_product($p3);
    $o2->add_product($p4);

    $o3 = $this->createOrder($id + 1, 12345); // other user
    $o3->add_product($p5);

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $value = $purchasedCategories->getValue($customerPayload);

    $this->assertIsArray($value);
    $this->assertCount(4, $value);
    $this->assertContains($uncategorizedId, $value);
    $this->assertContains($cat1Id, $value);
    $this->assertContains($cat2Id, $value);
    $this->assertContains($subCat1, $value);
    $this->assertNotContains($cat3Id, $value);
    $this->assertNotContains($subCat2, $value);
  }

  public function testPurchasedTags(): void {
    // tags
    $tag1Id = $this->tester->createWordPressTerm('Tag 1', 'product_tag', ['slug' => 'tag-1']);
    $tag2Id = $this->tester->createWordPressTerm('Tag 2', 'product_tag', ['slug' => 'tag-2']);
    $tag3Id = $this->tester->createWordPressTerm('Tag 3', 'product_tag', ['slug' => 'tag-3']);

    // check definitions
    $fields = $this->getFieldsMap();
    $purchasedTags = $fields['woocommerce:customer:purchased-tags'];
    $this->assertSame('Purchased tags', $purchasedTags->getName());
    $this->assertSame('enum_array', $purchasedTags->getType());
    $this->assertSame([
      'options' => [
        ['id' => $tag1Id, 'name' => 'Tag 1'],
        ['id' => $tag2Id, 'name' => 'Tag 2'],
        ['id' => $tag3Id, 'name' => 'Tag 3'],
      ],
    ], $purchasedTags->getArgs());

    // create products
    $p1 = $this->tester->createWooCommerceProduct(['name' => 'Product 1']); // no tags
    $p2 = $this->tester->createWooCommerceProduct(['name' => 'Product 2', 'tag_ids' => [$tag1Id, $tag2Id]]);
    $p3 = $this->tester->createWooCommerceProduct(['name' => 'Product 3', 'tag_ids' => [$tag2Id]]);
    $p4 = $this->tester->createWooCommerceProduct(['name' => 'Product 4', 'tag_ids' => [$tag3Id]]);

    // check values (guest)
    $o1 = $this->createOrder(0, 123);
    $o1->add_product($p1);
    $o1->add_product($p2);

    $o2 = $this->createOrder(0, 999);
    $o2->add_product($p3);

    $this->assertSame([], $purchasedTags->getValue(new CustomerPayload()));

    // check values (registered)
    $id = $this->tester->createCustomer('customer@example.com');

    $o1 = $this->createOrder($id, 123);
    $o1->add_product($p1);
    $o1->add_product($p2);

    $o2 = $this->createOrder($id, 999);
    $o2->add_product($p3);

    $o3 = $this->createOrder($id + 1, 12345); // other user
    $o3->add_product($p4);

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $value = $purchasedTags->getValue($customerPayload);

    $this->assertIsArray($value);
    $this->assertCount(2, $value);
    $this->assertContains($tag1Id, $value);
    $this->assertContains($tag2Id, $value);
    $this->assertNotContains($tag3Id, $value);
  }

  public function testPurchasedCategoriesAndTagsForProductVariations(): void {
    // tags & categories
    $tagId = $this->tester->createWordPressTerm('Test tag', 'product_tag', ['slug' => 'tag']);
    $categoryId = $this->tester->createWordPressTerm('Test category', 'product_cat', ['slug' => 'category']);
    $subCategoryId = $this->tester->createWordPressTerm('Test subcategory', 'product_cat', ['slug' => 'subcategory', 'parent' => $categoryId]);

    // product & variation
    $product = $this->tester->createWooCommerceProduct(['name' => 'Test product', 'category_ids' => [$subCategoryId], 'tag_ids' => [$tagId]]);

    $variation = new WC_Product_Variation();
    $variation->set_name('Variation 1');
    $variation->set_parent_id($product->get_id());
    $variation->save();

    // check values
    $id = $this->tester->createCustomer('customer@example.com');
    $order = $this->createOrder($id, 123);
    $order->add_product($variation);

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $fields = $this->getFieldsMap();

    // tags
    $purchasedTags = $fields['woocommerce:customer:purchased-tags'];
    $value = $purchasedTags->getValue($customerPayload);
    $this->assertIsArray($value);
    $this->assertCount(1, $value);
    $this->assertContains($tagId, $value);

    // categories
    $purchasedCategories = $fields['woocommerce:customer:purchased-categories'];
    $value = $purchasedCategories->getValue($customerPayload);
    $this->assertIsArray($value);
    $this->assertCount(1, $value);
    $this->assertContains($subCategoryId, $value);
  }

  private function createOrder(int $customerId, float $total, string $date = '2023-06-01 14:03:27'): WC_Order {
    $order = $this->tester->createWooCommerceOrder([
      'customer_id' => $customerId,
      'total' => (string)$total,
      'date_created' => $date,
    ]);
    $order->set_status('wc-completed');
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());
    return $order;
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
