<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\OrderPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\WP\Functions as WPFunctions;
use WC_Coupon;
use WC_Order;

/**
 * @group woo
 */
class OrderFieldsFactoryTest extends \MailPoetTest {
  public function testBillingInfo(): void {
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
    $this->assertSame('string', $countryField->getType());
    $this->assertSame([], $countryField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_billing_company('Test billing company');
    $order->set_billing_phone('123456789');
    $order->set_billing_city('Test billing city');
    $order->set_billing_postcode('12345');
    $order->set_billing_state('Test billing state');
    $order->set_billing_country('Test billing country');

    $payload = new OrderPayload($order);
    $this->assertSame('Test billing company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test billing city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test billing state', $stateField->getValue($payload));
    $this->assertSame('Test billing country', $countryField->getValue($payload));
  }

  public function testShippingInfo(): void {
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
    $this->assertSame('string', $countryField->getType());
    $this->assertSame([], $countryField->getArgs());

    // check values
    $order = new WC_Order();
    $order->set_shipping_company('Test shipping company');
    $order->set_shipping_phone('123456789');
    $order->set_shipping_city('Test shipping city');
    $order->set_shipping_postcode('12345');
    $order->set_shipping_state('Test shipping state');
    $order->set_shipping_country('Test shipping country');

    $payload = new OrderPayload($order);
    $this->assertSame('Test shipping company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test shipping city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test shipping state', $stateField->getValue($payload));
    $this->assertSame('Test shipping country', $countryField->getValue($payload));
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
        ['id' => 'wc-pending', 'name' => 'Pending payment'],
        ['id' => 'wc-processing', 'name' => 'Processing'],
        ['id' => 'wc-on-hold', 'name' => 'On hold'],
        ['id' => 'wc-completed', 'name' => 'Completed'],
        ['id' => 'wc-cancelled', 'name' => 'Cancelled'],
        ['id' => 'wc-refunded', 'name' => 'Refunded'],
        ['id' => 'wc-failed', 'name' => 'Failed'],
        ['id' => 'wc-checkout-draft', 'name' => 'Draft'],
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
    $coupon1 = new WC_Coupon();
    $coupon1->set_code('coupon-1');
    $coupon1->save();

    $coupon2 = new WC_Coupon();
    $coupon2->set_code('coupon-2');
    $coupon2->save();

    $coupon3 = new WC_Coupon();
    $coupon3->set_code('coupon-3');
    $coupon3->save();

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
    $order = new WC_Order();
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
