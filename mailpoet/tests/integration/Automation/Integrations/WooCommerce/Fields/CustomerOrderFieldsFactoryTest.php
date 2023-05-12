<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use WC_Customer;
use WC_Order;

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
