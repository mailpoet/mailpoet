<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\OrderPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\WP\Functions as WPFunctions;
use WC_Order;
use WP_Term;

/**
 * @group woo
 */
class OrderFieldsFactoryTest extends \MailPoetTest {
  public function testBillingInfo(): void {
    // set specific countries
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('woocommerce_allowed_countries', 'specific');
    $wp->updateOption('woocommerce_specific_allowed_countries', ['CZ', 'DE']);

    $fields = $this->getFieldsMap();

    // check definitions
    $companyField = $fields['woocommerce:order:billing-company'];
    $this->assertSame('Billing company', $companyField->getName());
    $this->assertSame('string', $companyField->getType());
    $this->assertSame([], $companyField->getArgs());

    $phoneField = $fields['woocommerce:order:billing-phone'];
    $this->assertSame('Billing phone', $phoneField->getName());
    $this->assertSame('string', $phoneField->getType());
    $this->assertSame([], $phoneField->getArgs());

    $cityField = $fields['woocommerce:order:billing-city'];
    $this->assertSame('Billing city', $cityField->getName());
    $this->assertSame('string', $cityField->getType());
    $this->assertSame([], $cityField->getArgs());

    $postcodeField = $fields['woocommerce:order:billing-postcode'];
    $this->assertSame('Billing postcode', $postcodeField->getName());
    $this->assertSame('string', $postcodeField->getType());
    $this->assertSame([], $postcodeField->getArgs());

    $stateField = $fields['woocommerce:order:billing-state'];
    $this->assertSame('Billing state/county', $stateField->getName());
    $this->assertSame('string', $stateField->getType());
    $this->assertSame([], $stateField->getArgs());

    $countryField = $fields['woocommerce:order:billing-country'];
    $this->assertSame('Billing country', $countryField->getName());
    $this->assertSame('enum', $countryField->getType());
    $this->assertSame([
      'options' => [
        ['id' => 'CZ', 'name' => 'Czech Republic'],
        ['id' => 'DE', 'name' => 'Germany'],
      ],
    ], $countryField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_billing_company('Test billing company');
    $order->set_billing_phone('123456789');
    $order->set_billing_city('Test billing city');
    $order->set_billing_postcode('12345');
    $order->set_billing_state('Test billing state');
    $order->set_billing_country('CZ');

    $payload = new OrderPayload($order);
    $this->assertSame('Test billing company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test billing city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test billing state', $stateField->getValue($payload));
    $this->assertSame('CZ', $countryField->getValue($payload));
  }

  public function testShippingInfo(): void {
    // set specific countries
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('woocommerce_ship_to_countries', 'specific');
    $wp->updateOption('woocommerce_specific_ship_to_countries', ['GR', 'SK']);

    $fields = $this->getFieldsMap();

    // check definitions
    $companyField = $fields['woocommerce:order:shipping-company'];
    $this->assertSame('Shipping company', $companyField->getName());
    $this->assertSame('string', $companyField->getType());
    $this->assertSame([], $companyField->getArgs());

    $phoneField = $fields['woocommerce:order:shipping-phone'];
    $this->assertSame('Shipping phone', $phoneField->getName());
    $this->assertSame('string', $phoneField->getType());
    $this->assertSame([], $phoneField->getArgs());

    $cityField = $fields['woocommerce:order:shipping-city'];
    $this->assertSame('Shipping city', $cityField->getName());
    $this->assertSame('string', $cityField->getType());
    $this->assertSame([], $cityField->getArgs());

    $postcodeField = $fields['woocommerce:order:shipping-postcode'];
    $this->assertSame('Shipping postcode', $postcodeField->getName());
    $this->assertSame('string', $postcodeField->getType());
    $this->assertSame([], $postcodeField->getArgs());

    $stateField = $fields['woocommerce:order:shipping-state'];
    $this->assertSame('Shipping state/county', $stateField->getName());
    $this->assertSame('string', $stateField->getType());
    $this->assertSame([], $stateField->getArgs());

    $countryField = $fields['woocommerce:order:shipping-country'];
    $this->assertSame('Shipping country', $countryField->getName());
    $this->assertSame('enum', $countryField->getType());
    $this->assertSame([
      'options' => [
        ['id' => 'GR', 'name' => 'Greece'],
        ['id' => 'SK', 'name' => 'Slovakia'],
      ],
    ], $countryField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_shipping_company('Test shipping company');
    $order->set_shipping_phone('123456789');
    $order->set_shipping_city('Test shipping city');
    $order->set_shipping_postcode('12345');
    $order->set_shipping_state('Test shipping state');
    $order->set_shipping_country('GR');

    $payload = new OrderPayload($order);
    $this->assertSame('Test shipping company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test shipping city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test shipping state', $stateField->getValue($payload));
    $this->assertSame('GR', $countryField->getValue($payload));
  }

  public function testCreatedDateField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $createdDateField = $fields['woocommerce:order:created-date'];
    $this->assertSame('Created date', $createdDateField->getName());
    $this->assertSame('datetime', $createdDateField->getType());
    $this->assertSame([], $createdDateField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_date_created('2020-01-01 00:00:00');

    $payload = new OrderPayload($order);
    $this->assertEquals(new DateTimeImmutable('2020-01-01 00:00:00'), $createdDateField->getValue($payload));
  }

  public function testPaidDateField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $paidDateField = $fields['woocommerce:order:paid-date'];
    $this->assertSame('Paid date', $paidDateField->getName());
    $this->assertSame('datetime', $paidDateField->getType());
    $this->assertSame([], $paidDateField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_date_paid('2020-01-01 00:00:00');

    $payload = new OrderPayload($order);
    $this->assertEquals(new DateTimeImmutable('2020-01-01 00:00:00'), $paidDateField->getValue($payload));
  }

  public function testCustomerNoteField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $customerNoteField = $fields['woocommerce:order:customer-note'];
    $this->assertSame('Customer provided note', $customerNoteField->getName());
    $this->assertSame('string', $customerNoteField->getType());
    $this->assertSame([], $customerNoteField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_customer_note('Test customer note');

    $payload = new OrderPayload($order);
    $this->assertSame('Test customer note', $customerNoteField->getValue($payload));
  }

  public function testPaymentMethodField(): void {
    WPFunctions::get()->updateOption('woocommerce_cod_settings', ['enabled' => 'yes']);
    WPFunctions::get()->updateOption('woocommerce_paypal_settings', ['enabled' => 'yes']);
    WC()->payment_gateways()->init();

    $fields = $this->getFieldsMap();

    // check definitions
    $paymentMethodField = $fields['woocommerce:order:payment-method'];
    $this->assertSame('Payment method', $paymentMethodField->getName());
    $this->assertSame('enum', $paymentMethodField->getType());
    $this->assertSame([
      'options' => [
        ['id' => 'cod', 'name' => 'Cash on delivery'],
        ['id' => 'paypal', 'name' => 'PayPal'],
      ],
    ], $paymentMethodField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_payment_method('Test payment method');

    $payload = new OrderPayload($order);
    $this->assertSame('Test payment method', $paymentMethodField->getValue($payload));
  }

  public function testStatusField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $statusField = $fields['woocommerce:order:status'];
    $this->assertSame('Status', $statusField->getName());
    $this->assertSame('enum', $statusField->getType());
    $this->assertSame([
      'options' => [
        ['id' => 'pending', 'name' => 'Pending payment'],
        ['id' => 'processing', 'name' => 'Processing'],
        ['id' => 'on-hold', 'name' => 'On hold'],
        ['id' => 'completed', 'name' => 'Completed'],
        ['id' => 'cancelled', 'name' => 'Cancelled'],
        ['id' => 'refunded', 'name' => 'Refunded'],
        ['id' => 'failed', 'name' => 'Failed'],
        ['id' => 'checkout-draft', 'name' => 'Draft'],
      ],
    ], $statusField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_status('wc-processing');

    $payload = new OrderPayload($order);
    $this->assertSame('processing', $statusField->getValue($payload));
  }

  public function testTotalField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $totalField = $fields['woocommerce:order:total'];
    $this->assertSame('Total', $totalField->getName());
    $this->assertSame('number', $totalField->getType());
    $this->assertSame([], $totalField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_total('123.45');

    $payload = new OrderPayload($order);
    $this->assertSame(123.45, $totalField->getValue($payload));
  }

  public function testCouponsField(): void {
    $this->tester->createWooCommerceCoupon(['code' => 'coupon-1']);
    $this->tester->createWooCommerceCoupon(['code' => 'coupon-2']);
    $this->tester->createWooCommerceCoupon(['code' => 'coupon-3']);

    $fields = $this->getFieldsMap();

    // check definitions
    $couponsField = $fields['woocommerce:order:coupons'];
    $this->assertSame('Used coupons', $couponsField->getName());
    $this->assertSame('enum_array', $couponsField->getType());
    $this->assertSame([
      'options' => [
        ['id' => 'coupon-1', 'name' => 'coupon-1'],
        ['id' => 'coupon-2', 'name' => 'coupon-2'],
        ['id' => 'coupon-3', 'name' => 'coupon-3'],
      ],
    ], $couponsField->getArgs());

    // check values
    $order = $this->tester->createWooCommerceOrder();
    $order->apply_coupon('coupon-1');
    $order->apply_coupon('coupon-2');

    $payload = new OrderPayload($order);
    $this->assertSame(['coupon-1', 'coupon-2'], $couponsField->getValue($payload));
  }

  public function testIsFirstOrderField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $isFirstOrderField = $fields['woocommerce:order:is-first-order'];
    $this->assertSame('Is first order', $isFirstOrderField->getName());
    $this->assertSame('boolean', $isFirstOrderField->getType());
    $this->assertSame([], $isFirstOrderField->getArgs());

    // check values
    $order1 = $this->tester->createWooCommerceOrder(['customer_id' => 123]);
    $payload = new OrderPayload($order1);
    $this->assertTrue($isFirstOrderField->getValue($payload));

    $order2 = $this->tester->createWooCommerceOrder(['customer_id' => 123]);
    $payload = new OrderPayload($order2);
    $this->assertFalse($isFirstOrderField->getValue($payload));

    $order3 = $this->tester->createWooCommerceOrder(['customer_id' => 999]);
    $payload = new OrderPayload($order3);
    $this->assertTrue($isFirstOrderField->getValue($payload));

    // check values for guest
    $order1 = $this->tester->createWooCommerceOrder(['customer_id' => 0, 'billing_email' => 'guest@example.com']);
    $payload = new OrderPayload($order1);
    $this->assertTrue($isFirstOrderField->getValue($payload));

    $order2 = $this->tester->createWooCommerceOrder(['customer_id' => 0, 'billing_email' => 'guest@example.com']);
    $payload = new OrderPayload($order2);
    $this->assertFalse($isFirstOrderField->getValue($payload));

    $order3 = $this->tester->createWooCommerceOrder(['customer_id' => 0, 'billing_email' => 'another-guest@example.com']);
    $payload = new OrderPayload($order3);
    $this->assertTrue($isFirstOrderField->getValue($payload));
  }

  public function testCategoriesField(): void {
    // categories
    $uncategorized = get_term_by('slug', 'uncategorized', 'product_cat');
    $uncategorizedId = $uncategorized instanceof WP_Term ? $uncategorized->term_id : null; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $cat1Id = $this->tester->createWordPressTerm('Cat 1', 'product_cat', ['slug' => 'cat-1']);
    $cat2Id = $this->tester->createWordPressTerm('Cat 2', 'product_cat', ['slug' => 'cat-2']);
    $cat3Id = $this->tester->createWordPressTerm('Cat 3', 'product_cat', ['slug' => 'cat-3']);
    $subCat1Id = $this->tester->createWordPressTerm('Subcat 1', 'product_cat', ['slug' => 'subcat-1', 'parent' => $cat1Id]);
    $subCat2Id = $this->tester->createWordPressTerm('Subcat 2', 'product_cat', ['slug' => 'subcat-2', 'parent' => $cat1Id]);
    $subSubCat1Id = $this->tester->createWordPressTerm('Subsubcat 1', 'product_cat', ['slug' => 'subsubcat-1', 'parent' => $subCat1Id]);

    // check definitions
    $fields = $this->getFieldsMap();
    $purchasedCategories = $fields['woocommerce:order:categories'];
    $this->assertSame('Categories', $purchasedCategories->getName());
    $this->assertSame('enum_array', $purchasedCategories->getType());
    $this->assertSame([
      'options' => [
        ['id' => $cat1Id, 'name' => 'Cat 1'],
        ['id' => $subCat1Id, 'name' => 'Cat 1 | Subcat 1'],
        ['id' => $subSubCat1Id, 'name' => 'Cat 1 | Subcat 1 | Subsubcat 1'],
        ['id' => $subCat2Id, 'name' => 'Cat 1 | Subcat 2'],
        ['id' => $cat2Id, 'name' => 'Cat 2'],
        ['id' => $cat3Id, 'name' => 'Cat 3'],
        ['id' => $uncategorizedId, 'name' => 'Uncategorized'],
      ],
    ], $purchasedCategories->getArgs());

    // check values
    $product = $this->tester->createWooCommerceProduct(['name' => 'Test product', 'category_ids' => [$cat2Id, $subSubCat1Id]]);
    $order = $this->tester->createWooCommerceOrder();
    $order->add_product($product);

    $orderPayload = new OrderPayload($order);
    $value = $purchasedCategories->getValue($orderPayload);

    $this->assertIsArray($value);
    $this->assertCount(4, $value);
    $this->assertContains($cat1Id, $value); // auto-included parent
    $this->assertContains($cat2Id, $value);
    $this->assertContains($subCat1Id, $value); // auto-included parent
    $this->assertContains($subSubCat1Id, $value);
    $this->assertNotContains($uncategorizedId, $value);
    $this->assertNotContains($cat3Id, $value);
    $this->assertNotContains($subCat2Id, $value);
  }

  public function testTagsField(): void {
    // tags
    $tag1Id = $this->tester->createWordPressTerm('Tag 1', 'product_tag', ['slug' => 'tag-1']);
    $tag2Id = $this->tester->createWordPressTerm('Tag 2', 'product_tag', ['slug' => 'tag-2']);
    $tag3Id = $this->tester->createWordPressTerm('Tag 3', 'product_tag', ['slug' => 'tag-3']);

    // check definitions
    $fields = $this->getFieldsMap();
    $purchasedTags = $fields['woocommerce:order:tags'];
    $this->assertSame('Tags', $purchasedTags->getName());
    $this->assertSame('enum_array', $purchasedTags->getType());
    $this->assertSame([
      'options' => [
        ['id' => $tag1Id, 'name' => 'Tag 1'],
        ['id' => $tag2Id, 'name' => 'Tag 2'],
        ['id' => $tag3Id, 'name' => 'Tag 3'],
      ],
    ], $purchasedTags->getArgs());

    // check values
    $product = $this->tester->createWooCommerceProduct(['name' => 'Test product', 'tag_ids' => [$tag1Id, $tag2Id]]);
    $order = $this->tester->createWooCommerceOrder();
    $order->add_product($product);

    $orderPayload = new OrderPayload($order);
    $value = $purchasedTags->getValue($orderPayload);

    $this->assertIsArray($value);
    $this->assertCount(2, $value);
    $this->assertContains($tag1Id, $value);
    $this->assertContains($tag2Id, $value);
    $this->assertNotContains($tag3Id, $value);
  }

  public function testProductsField(): void {
    // products
    $product1 = $this->tester->createWooCommerceProduct(['name' => 'Product 1']);
    $product2 = $this->tester->createWooCommerceProduct(['name' => 'Product 2']);
    $product3 = $this->tester->createWooCommerceProduct(['name' => 'Product 3']);

    // check definitions
    $fields = $this->getFieldsMap();
    $purchasedProducts = $fields['woocommerce:order:products'];
    $this->assertSame('Products', $purchasedProducts->getName());
    $this->assertSame('enum_array', $purchasedProducts->getType());
    $this->assertSame([
      'options' => [
        ['id' => $product1->get_id(), 'name' => "Product 1 (#{$product1->get_id()})"],
        ['id' => $product2->get_id(), 'name' => "Product 2 (#{$product2->get_id()})"],
        ['id' => $product3->get_id(), 'name' => "Product 3 (#{$product3->get_id()})"],
      ],
    ], $purchasedProducts->getArgs());

    // check values
    $order = $this->tester->createWooCommerceOrder();
    $order->add_product($product1);
    $order->add_product($product2);

    $orderPayload = new OrderPayload($order);
    $value = $purchasedProducts->getValue($orderPayload);

    $this->assertIsArray($value);
    $this->assertCount(2, $value);
    $this->assertContains($product1->get_id(), $value);
    $this->assertContains($product2->get_id(), $value);
    $this->assertNotContains($product3->get_id(), $value);
  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(OrderSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
