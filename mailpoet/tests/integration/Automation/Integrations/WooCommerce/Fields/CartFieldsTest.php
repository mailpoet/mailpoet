<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\AbandonedCartPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\AbandonedCartSubject;
use WC_Customer;

/**
 * @group woo
 */
class CartFieldsTest extends \MailPoetTest {
  public function testCartFields(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $cartTotalField = $fields['woocommerce:cart:cart-total'];
    $this->assertSame('Cart total', $cartTotalField->getName());
    $this->assertSame('number', $cartTotalField->getType());
    $this->assertSame([], $cartTotalField->getArgs());

    // check values (empty cart)
    $customer = new WC_Customer();
    $payload = new AbandonedCartPayload($customer, new DateTimeImmutable(), []);
    $this->assertSame(0.0, $cartTotalField->getValue($payload));

    // check values (with products)
    $product1 = $this->tester->createWooCommerceProduct(['name' => 'Product 1', 'price' => '123.45']);
    $product2 = $this->tester->createWooCommerceProduct(['name' => 'Product 2', 'price' => '100.00']);
    $product3 = $this->tester->createWooCommerceProduct(['name' => 'Product 3']);

    $payload = new AbandonedCartPayload($customer, new DateTimeImmutable(), [$product1->get_id(), $product2->get_id(), $product3->get_id()]);
    $this->assertSame(223.45, $cartTotalField->getValue($payload));
  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(AbandonedCartSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
