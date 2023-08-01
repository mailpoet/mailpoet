<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceUsedPaymentMethod;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceUsedPaymentMethodTest extends \MailPoetTest {

  /** @var WooCommerceUsedPaymentMethod */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommerceUsedPaymentMethod::class);
  }

  public function testItWorksWithAnyOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $customerId3 = $this->tester->createCustomer('c3@e.com');

    $this->createOrder($customerId1, Carbon::now(), 'paypal');
    $this->createOrder($customerId2, Carbon::now(), 'cheque');
    $this->createOrder($customerId3, Carbon::now(), 'cheque');
    $this->createOrder($customerId3, Carbon::now(), 'paypal');

    $this->assertFilterReturnsEmails('any', ['paypal'], 1, 'inTheLast', ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('any', ['cheque'], 1, 'inTheLast', ['c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('any', ['doge'], 1000, 'inTheLast', []);
  }

  public function testItWorksWithAllOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now(), 'paypal');
    $this->createOrder($customerId1, Carbon::now(), 'paypal');

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now(), 'cheque');

    $customerId3 = $this->tester->createCustomer('c3@e.com');
    $this->createOrder($customerId3, Carbon::now(), 'cheque');
    $this->createOrder($customerId3, Carbon::now(), 'paypal');

    $this->assertfilterreturnsemails('all', ['paypal'], 1, 'inTheLast', ['c1@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['cheque'], 1, 'inTheLast', ['c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['cheque', 'paypal'], 1, 'inTheLast', ['c3@e.com']);
    $this->assertFilterReturnsEmails('all', ['doge'], 1000, 'inTheLast', []);
  }

  public function testItWorksWithNoneOperator(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now(), 'paypal');
    $this->createOrder($customerId1, Carbon::now(), 'paypal');

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now(), 'cheque');

    $customerId3 = $this->tester->createCustomer('c3@e.com');
    $this->createOrder($customerId3, Carbon::now(), 'cheque');
    $this->createOrder($customerId3, Carbon::now(), 'paypal');

    (new Subscriber)->withEmail('sub@e.com')->create();

    $this->assertFilterReturnsEmails('none', ['paypal'], 1, 'inTheLast', ['sub@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['cheque'], 1, 'inTheLast', ['sub@e.com', 'c1@e.com']);
    $this->assertFilterReturnsEmails('none', ['doge'], 1000, 'inTheLast', ['sub@e.com', 'c1@e.com', 'c2@e.com', 'c3@e.com']);
    $this->assertFilterReturnsEmails('none', ['paypal', 'cheque'], 1, 'inTheLast', ['sub@e.com']);
  }

  public function testItWorksWithDateRanges(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(2)->addMinute(), 'paypal');
    $this->createOrder($customerId1, Carbon::now()->subDays(5)->addMinute(), 'cheque');

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now()->subDays(100)->addMinute(), 'cash');
    $this->assertFilterReturnsEmails('any', ['paypal'], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', ['paypal'], 2, 'inTheLast', ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', ['cheque'], 4, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', ['cheque'], 5, 'inTheLast', ['c1@e.com']);
    $this->assertFilterReturnsEmails('any', ['cash'], 99, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', ['cash'], 100, 'inTheLast', ['c2@e.com']);
    $this->assertFilterReturnsEmails('any', ['cash', 'paypal'], 100, 'inTheLast', ['c1@e.com', 'c2@e.com']);

    $this->assertFilterReturnsEmails('all', ['paypal'], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('all', ['paypal'], 2, 'inTheLast', ['c1@e.com']);
    $this->assertFilterReturnsEmails('all', ['paypal', 'cheque'], 2, 'inTheLast', []);
    $this->assertFilterReturnsEmails('all', ['paypal', 'cheque'], 5, 'inTheLast', ['c1@e.com']);

    $this->assertFilterReturnsEmails('none', ['paypal'], 1, 'inTheLast', ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['paypal'], 2, 'inTheLast', ['c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['cheque'], 2, 'inTheLast', ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['cheque'], 5, 'inTheLast', ['c2@e.com']);
  }

  public function testItWorksWithAllTime(): void {
    $customerId1 = $this->tester->createCustomer('c1@e.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(2)->addMinute(), 'paypal');
    $this->createOrder($customerId1, Carbon::now()->subDays(5)->addMinute(), 'cheque');

    $customerId2 = $this->tester->createCustomer('c2@e.com');
    $this->createOrder($customerId2, Carbon::now()->subDays(100)->addMinute(), 'cash');

    $this->assertFilterReturnsEmails('any', ['cash', 'paypal'], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', ['cash', 'paypal'], 1, 'allTime', ['c1@e.com', 'c2@e.com']);

    $this->assertFilterReturnsEmails('all', ['cash'], 1, 'inTheLast', []);
    $this->assertFilterReturnsEmails('all', ['cash'], 1, 'allTime', ['c2@e.com']);

    $this->assertFilterReturnsEmails('none', ['cash', 'paypal'], 1, 'inTheLast', ['c1@e.com', 'c2@e.com']);
    $this->assertFilterReturnsEmails('none', ['cash', 'paypal'], 1, 'allTime', []);
  }

  private function assertFilterReturnsEmails(string $operator, array $paymentMethods, int $days, string $timeframe, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceUsedPaymentMethod::ACTION, [
      'operator' => $operator,
      'payment_methods' => $paymentMethods,
      'days' => $days,
      'timeframe' => $timeframe,
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function createOrder(int $customerId, Carbon $createdAt, string $paymentMethod): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status('wc-completed');
    $order->set_payment_method($paymentMethod);
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }
}
