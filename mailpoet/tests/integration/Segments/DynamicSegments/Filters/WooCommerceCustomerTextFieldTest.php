<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCustomerTextField;

/**
 * @group woo
 */
class WooCommerceCustomerTextFieldTest extends \MailPoetTest {

  /** @var WooCommerceCustomerTextField */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommerceCustomerTextField::class);
  }

  public function testEquals(): void {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'is', 'Minneapolis', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'is', 'Anchorage', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'is', 'eapolis', []);
    $this->assertFilterReturnsEmails('customerInCity', 'is', 'Anchorag', []);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'is', '55111', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'is', '99540', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'is', '9954', []);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'is', '9540', []);
  }

  public function testNotEquals(): void {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'isNot', 'Minneapolis', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'isNot', 'Anchorage', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'isNot', 'eapolis', ['1@e.com', '2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'isNot', 'Anchorag', ['1@e.com', '2@e.com']);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'isNot', '55111', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'isNot', '99540', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'isNot', '9540', ['1@e.com', '2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'isNot', '9954', ['1@e.com', '2@e.com']);
  }

  public function testStartsWith() {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'startsWith', 'Minneapolis', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'startsWith', 'Minn', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'startsWith', 'Anchorage', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'startsWith', 'A', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'startsWith', 'Anchoragee', []);
    $this->assertFilterReturnsEmails('customerInCity', 'startsWith', 'inneapolis', []);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'startsWith', '55111', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'startsWith', '5', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'startsWith', '99540', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'startsWith', '9954', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'startsWith', '6', []);
  }

  public function testDoesNotStartWith(): void {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'notStartsWith', 'Minneapolis', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notStartsWith', 'Minn', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notStartsWith', 'Anchorage', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notStartsWith', 'A', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notStartsWith', 'Anchoragee', ['1@e.com', '2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notStartsWith', 'inneapolis', ['1@e.com', '2@e.com']);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'notStartsWith', '55111', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notStartsWith', '5', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notStartsWith', '99540', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notStartsWith', '9954', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notStartsWith', '6', ['1@e.com', '2@e.com']);
  }

  public function testEndsWith(): void {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'endsWith', 'Minneapolis', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'endsWith', 'lis', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'endsWith', 'Minn', []);
    $this->assertFilterReturnsEmails('customerInCity', 'endsWith', 'Anchorage', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'endsWith', 'age', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'endsWith', 'A', []);
    $this->assertFilterReturnsEmails('customerInCity', 'endsWith', 'liss', []);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'endsWith', '55111', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'endsWith', '1', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'endsWith', '99540', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'endsWith', '40', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'endsWith', '2', []);
  }

  public function testDoesNotEndWith(): void {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'notEndsWith', 'Minneapolis', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notEndsWith', 'lis', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notEndsWith', 'Anchorage', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notEndsWith', 'e', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notEndsWith', 'q', ['1@e.com', '2@e.com']);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'notEndsWith', '55111', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notEndsWith', '1', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notEndsWith', '99540', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notEndsWith', '40', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notEndsWith', '6', ['1@e.com', '2@e.com']);
  }

  public function testContains(): void {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'Minneapolis', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'lis', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'Min', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'eapo', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'Anchorage', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'age', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'Anc', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'hora', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'q', []);
    $this->assertFilterReturnsEmails('customerInCity', 'contains', 'e', ['1@e.com', '2@e.com']);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '55111', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '55', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '111', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '51', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '99540', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '99', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '40', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '954', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '6', []);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'contains', '5', ['1@e.com', '2@e.com']);
  }

  public function testNotContains(): void {
    $this->createCustomer('1@e.com', 'Minneapolis', '55111');
    $this->createCustomer('2@e.com', 'Anchorage', '99540');

    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'Minneapolis', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'lis', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'Min', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'eapo', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'Anchorage', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'age', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'Anc', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'hora', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'q', ['1@e.com', '2@e.com']);
    $this->assertFilterReturnsEmails('customerInCity', 'notContains', 'e', []);

    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '55111', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '55', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '111', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '51', ['2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '99540', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '99', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '40', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '954', ['1@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '6', ['1@e.com', '2@e.com']);
    $this->assertFilterReturnsEmails('customerInPostalCode', 'notContains', '5', []);
  }

  public function testContainsDoesNotBreakIfItIncludesPercentSymbol(): void {
    $this->createCustomer('1@e.com', 'Minn%eapolis', '55111');
    $this->assertFilterReturnsEmails('customerInCity', 'contains', '%', ['1@e.com']);
  }

  private function assertFilterReturnsEmails(string $action, string $operator, string $value, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, $action, [
      'operator' => $operator,
      'value' => $value,
      'action' => $action,
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function createCustomer(string $email, string $city, string $postalCode): void {
    $this->tester->createCustomer($email);
    $order = $this->tester->createWooCommerceOrder([
      'billing_email' => $email,
      'billing_postcode' => $postalCode,
      'billing_city' => $city,
      'status' => 'wc-complete',
    ]);
    $order->save();
  }

  public function _after() {
    parent::_after();
    global $wpdb;
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
