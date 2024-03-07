<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\WP\Functions as WPFunctions;
use WC_Customer;
use WC_Order;

/**
 * @group woo
 */
class CustomerFieldsFactoryTest extends \MailPoetTest {
  public function testBillingInfo(): void {
    // set specific countries
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('woocommerce_allowed_countries', 'specific');
    $wp->updateOption('woocommerce_specific_allowed_countries', ['CZ', 'DE']);

    $fields = $this->getFieldsMap();

    // check definitions
    $companyField = $fields['woocommerce:customer:billing-company'];
    $this->assertSame('Billing company', $companyField->getName());
    $this->assertSame('string', $companyField->getType());
    $this->assertSame([], $companyField->getArgs());

    $phoneField = $fields['woocommerce:customer:billing-phone'];
    $this->assertSame('Billing phone', $phoneField->getName());
    $this->assertSame('string', $phoneField->getType());
    $this->assertSame([], $phoneField->getArgs());

    $cityField = $fields['woocommerce:customer:billing-city'];
    $this->assertSame('Billing city', $cityField->getName());
    $this->assertSame('string', $cityField->getType());
    $this->assertSame([], $cityField->getArgs());

    $postcodeField = $fields['woocommerce:customer:billing-postcode'];
    $this->assertSame('Billing postcode', $postcodeField->getName());
    $this->assertSame('string', $postcodeField->getType());
    $this->assertSame([], $postcodeField->getArgs());

    $stateField = $fields['woocommerce:customer:billing-state'];
    $this->assertSame('Billing state/county', $stateField->getName());
    $this->assertSame('string', $stateField->getType());
    $this->assertSame([], $stateField->getArgs());

    $countryField = $fields['woocommerce:customer:billing-country'];
    $this->assertSame('Billing country', $countryField->getName());
    $this->assertSame('enum', $countryField->getType());
    $this->assertSame([
      'options' => [
        ['id' => 'CZ', 'name' => 'Czech Republic'],
        ['id' => 'DE', 'name' => 'Germany'],
      ],
    ], $countryField->getArgs());

    // check values (guest)
    $payload = new CustomerPayload();
    $this->assertNull($companyField->getValue($payload));
    $this->assertNull($phoneField->getValue($payload));
    $this->assertNull($cityField->getValue($payload));
    $this->assertNull($postcodeField->getValue($payload));
    $this->assertNull($stateField->getValue($payload));
    $this->assertNull($countryField->getValue($payload));

    // check values (registered)
    $customer = new WC_Customer();
    $customer->set_billing_company('Test billing company');
    $customer->set_billing_phone('123456789');
    $customer->set_billing_city('Test billing city');
    $customer->set_billing_postcode('12345');
    $customer->set_billing_state('Test billing state');
    $customer->set_billing_country('DE');

    $payload = new CustomerPayload($customer);
    $this->assertSame('Test billing company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test billing city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test billing state', $stateField->getValue($payload));
    $this->assertSame('DE', $countryField->getValue($payload));
  }

  public function testShippingInfo(): void {
    // set specific countries
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('woocommerce_ship_to_countries', 'specific');
    $wp->updateOption('woocommerce_specific_ship_to_countries', ['GR', 'SK']);

    $fields = $this->getFieldsMap();

    // check definitions
    $companyField = $fields['woocommerce:customer:shipping-company'];
    $this->assertSame('Shipping company', $companyField->getName());
    $this->assertSame('string', $companyField->getType());
    $this->assertSame([], $companyField->getArgs());

    $phoneField = $fields['woocommerce:customer:shipping-phone'];
    $this->assertSame('Shipping phone', $phoneField->getName());
    $this->assertSame('string', $phoneField->getType());
    $this->assertSame([], $phoneField->getArgs());

    $cityField = $fields['woocommerce:customer:shipping-city'];
    $this->assertSame('Shipping city', $cityField->getName());
    $this->assertSame('string', $cityField->getType());
    $this->assertSame([], $cityField->getArgs());

    $postcodeField = $fields['woocommerce:customer:shipping-postcode'];
    $this->assertSame('Shipping postcode', $postcodeField->getName());
    $this->assertSame('string', $postcodeField->getType());
    $this->assertSame([], $postcodeField->getArgs());

    $stateField = $fields['woocommerce:customer:shipping-state'];
    $this->assertSame('Shipping state/county', $stateField->getName());
    $this->assertSame('string', $stateField->getType());
    $this->assertSame([], $stateField->getArgs());

    $countryField = $fields['woocommerce:customer:shipping-country'];
    $this->assertSame('Shipping country', $countryField->getName());
    $this->assertSame('enum', $countryField->getType());
    $this->assertSame([
      'options' => [
        ['id' => 'GR', 'name' => 'Greece'],
        ['id' => 'SK', 'name' => 'Slovakia'],
      ],
    ], $countryField->getArgs());

    // check values (guest)
    $payload = new CustomerPayload();
    $this->assertNull($companyField->getValue($payload));
    $this->assertNull($phoneField->getValue($payload));
    $this->assertNull($cityField->getValue($payload));
    $this->assertNull($postcodeField->getValue($payload));
    $this->assertNull($stateField->getValue($payload));
    $this->assertNull($countryField->getValue($payload));

    // check values (registered)
    $customer = new WC_Customer();
    $customer->set_shipping_company('Test shipping company');
    $customer->set_shipping_phone('123456789');
    $customer->set_shipping_city('Test shipping city');
    $customer->set_shipping_postcode('12345');
    $customer->set_shipping_state('Test shipping state');
    $customer->set_shipping_country('SK');

    $payload = new CustomerPayload($customer);
    $this->assertSame('Test shipping company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test shipping city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test shipping state', $stateField->getValue($payload));
    $this->assertSame('SK', $countryField->getValue($payload));
  }

  public function testBillingInfoBackfillsFromOrderForGuests(): void {
    // set specific countries
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('woocommerce_ship_to_countries', 'specific');
    $wp->updateOption('woocommerce_specific_ship_to_countries', ['GR', 'SK']);

    // fields
    $fields = $this->getFieldsMap();
    $companyField = $fields['woocommerce:customer:shipping-company'];
    $phoneField = $fields['woocommerce:customer:shipping-phone'];
    $cityField = $fields['woocommerce:customer:shipping-city'];
    $postcodeField = $fields['woocommerce:customer:shipping-postcode'];
    $stateField = $fields['woocommerce:customer:shipping-state'];
    $countryField = $fields['woocommerce:customer:shipping-country'];

    // check values (guest - fields are backfilled from order)
    $order = new WC_Order();
    $order->set_shipping_company('Test shipping company');
    $order->set_shipping_phone('123456789');
    $order->set_shipping_city('Test shipping city');
    $order->set_shipping_postcode('12345');
    $order->set_shipping_state('Test shipping state');
    $order->set_shipping_country('SK');

    $payload = new CustomerPayload(null, $order);
    $this->assertSame('Test shipping company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test shipping city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test shipping state', $stateField->getValue($payload));
    $this->assertSame('SK', $countryField->getValue($payload));

    // check values (registered - fields are not backfilled)
    $customer = new WC_Customer();
    $payload = new CustomerPayload($customer, $order);
    $this->assertSame('', $companyField->getValue($payload));
    $this->assertSame('', $phoneField->getValue($payload));
    $this->assertSame('', $cityField->getValue($payload));
    $this->assertSame('', $postcodeField->getValue($payload));
    $this->assertSame('', $stateField->getValue($payload));
    $this->assertSame('', $countryField->getValue($payload));
  }

  public function testShippingInfoBackfillsFromOrderForGuests(): void {
    // set specific countries
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('woocommerce_ship_to_countries', 'specific');
    $wp->updateOption('woocommerce_specific_ship_to_countries', ['GR', 'SK']);

    // fields
    $fields = $this->getFieldsMap();
    $companyField = $fields['woocommerce:customer:shipping-company'];
    $phoneField = $fields['woocommerce:customer:shipping-phone'];
    $cityField = $fields['woocommerce:customer:shipping-city'];
    $postcodeField = $fields['woocommerce:customer:shipping-postcode'];
    $stateField = $fields['woocommerce:customer:shipping-state'];
    $countryField = $fields['woocommerce:customer:shipping-country'];

    // check values (guest - fields are backfilled from order)
    $order = new WC_Order();
    $order->set_shipping_company('Test shipping company');
    $order->set_shipping_phone('123456789');
    $order->set_shipping_city('Test shipping city');
    $order->set_shipping_postcode('12345');
    $order->set_shipping_state('Test shipping state');
    $order->set_shipping_country('SK');

    $payload = new CustomerPayload(null, $order);
    $this->assertSame('Test shipping company', $companyField->getValue($payload));
    $this->assertSame('123456789', $phoneField->getValue($payload));
    $this->assertSame('Test shipping city', $cityField->getValue($payload));
    $this->assertSame('12345', $postcodeField->getValue($payload));
    $this->assertSame('Test shipping state', $stateField->getValue($payload));
    $this->assertSame('SK', $countryField->getValue($payload));

    // check values (registered - fields are not backfilled)
    $customer = new WC_Customer();
    $payload = new CustomerPayload($customer, $order);
    $this->assertSame('', $companyField->getValue($payload));
    $this->assertSame('', $phoneField->getValue($payload));
    $this->assertSame('', $cityField->getValue($payload));
    $this->assertSame('', $postcodeField->getValue($payload));
    $this->assertSame('', $stateField->getValue($payload));
    $this->assertSame('', $countryField->getValue($payload));
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
