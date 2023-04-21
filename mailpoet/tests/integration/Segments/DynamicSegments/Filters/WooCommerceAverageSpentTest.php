<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceAverageSpent;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceAverageSpentTest extends \MailPoetTest {
  /** @var WooCommerceAverageSpent */
  private $averageSpentFilter;

  public function _before(): void {
    $this->averageSpentFilter = $this->diContainer->get(WooCommerceAverageSpent::class);
  }

  public function testItWorksWithAmountEquals(): void {
    $this->createCustomerWithOrderValues('1@e.com', [10, 20]);
    $this->createCustomerWithOrderValues('2@e.com', [5]);
    $this->createCustomerWithOrderValues('3@e.com', [1, 1, 43]);
    $this->createCustomerWithOrderValues('4@e.com', [15.01]);

    $matchingEmails = $this->getMatchingEmails('=', 15);
    $this->assertEqualsCanonicalizing(['1@e.com', '3@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('=', 5);
    $this->assertEqualsCanonicalizing(['2@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('=', 15.01);
    $this->assertEqualsCanonicalizing(['4@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('=', 13.13);
    $this->assertEqualsCanonicalizing([], $matchingEmails);
  }

  public function testItWorksWithAmountGreaterThan(): void {
    $this->createCustomerWithOrderValues('1@e.com', [10, 20]);
    $this->createCustomerWithOrderValues('2@e.com', [20]);
    $this->createCustomerWithOrderValues('3@e.com', [15, 15.01]);

    $matchingEmails = $this->getMatchingEmails('>', 19.99);
    $this->assertEqualsCanonicalizing(['2@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('>', 15);
    $this->assertEqualsCanonicalizing(['2@e.com', '3@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('>', 14.99);
    $this->assertEqualsCanonicalizing(['1@e.com', '2@e.com', '3@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('>', 20);
    $this->assertEqualsCanonicalizing([], $matchingEmails);
  }

  public function testItWorksWithAmountLessThan(): void {
    $this->createCustomerWithOrderValues('1@e.com', [100, 110, 2000]); // average = ~$736.67
    $this->createCustomerWithOrderValues('2@e.com', [0.01]);
    $this->createCustomerWithOrderValues('3@e.com', [2000, 10, 25]); // average = ~$678.33

    $matchingEmails = $this->getMatchingEmails('<', 678.34);
    $this->assertEqualsCanonicalizing(['2@e.com', '3@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('<', 678.33);
    $this->assertEqualsCanonicalizing(['2@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('<', 0.01);
    $this->assertEqualsCanonicalizing([], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('<', 736.68);
    $this->assertEqualsCanonicalizing(['1@e.com', '2@e.com', '3@e.com'], $matchingEmails);
  }

  public function testItWorksWithAmountNotEqual(): void {
    $this->createCustomerWithOrderValues('1@e.com', [1, 2, 3, 4]); // average = 2.50
    $this->createCustomerWithOrderValues('2@e.com', [22.22, 33.33, 44.44]); // average = 33.33
    $this->createCustomerWithOrderValues('3@e.com', [30000, 30000, 30000]); // big spender!

    $matchingEmails = $this->getMatchingEmails('!=', 2.50);
    $this->assertEqualsCanonicalizing(['2@e.com', '3@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('!=', 33.33);
    $this->assertEqualsCanonicalizing(['1@e.com', '3@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('!=', 30000);
    $this->assertEqualsCanonicalizing(['1@e.com', '2@e.com'], $matchingEmails);
    $matchingEmails = $this->getMatchingEmails('!=', 8);
    $this->assertEqualsCanonicalizing(['1@e.com', '2@e.com', '3@e.com'], $matchingEmails);
  }

  public function testItWorksWithDateRanges(): void {
    $id1 = $this->tester->createCustomer('1@e.com');
    $this->createOrder($id1, 100, 3);
    $this->createOrder($id1, 200, 6); // 150 average
    $this->createOrder($id1, 60, 9); // 120 average
    $this->createOrder($id1, 400, 100); // 190 average

    $emails = $this->getMatchingEmails('=', 100, 3);
    $this->assertEqualsCanonicalizing(['1@e.com'], $emails);
    $emails = $this->getMatchingEmails('=', 100, 6);
    $this->assertEqualsCanonicalizing([], $emails);
    $emails = $this->getMatchingEmails('=', 150, 6);
    $this->assertEqualsCanonicalizing(['1@e.com'], $emails);
    $emails = $this->getMatchingEmails('=', 120, 9);
    $this->assertEqualsCanonicalizing(['1@e.com'], $emails);
    $emails = $this->getMatchingEmails('=', 120, 99);
    $this->assertEqualsCanonicalizing(['1@e.com'], $emails);
    $emails = $this->getMatchingEmails('=', 190, 100);
    $this->assertEqualsCanonicalizing(['1@e.com'], $emails);
  }

  private function getMatchingEmails(string $operator, float $amount, int $days = 365): array {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceAverageSpent::ACTION, [
      'operator' => $operator,
      'amount' => $amount,
      'days' => $days,
    ]);

    return $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->averageSpentFilter);
  }

  private function createCustomerWithOrderValues(string $customerEmail, array $values): void {
    $customerId = $this->tester->createCustomer($customerEmail);
    foreach ($values as $value) {
      $this->createOrder($customerId, $value );
    }
  }

  private function createOrder(int $customerId, float $orderTotal, int $daysAgo = 0) {
    $createdAt = Carbon::now();
    $createdAt->subDays($daysAgo)->addMinute();
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status('wc-completed');
    $order->set_total((string)$orderTotal);
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }
}
