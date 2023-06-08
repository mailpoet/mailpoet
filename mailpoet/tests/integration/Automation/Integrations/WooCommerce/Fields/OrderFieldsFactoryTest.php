<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\OrderPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
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
