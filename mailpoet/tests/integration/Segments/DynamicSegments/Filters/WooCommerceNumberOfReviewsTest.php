<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfReviews;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceNumberOfReviewsTest extends \MailPoetTest {
  /** @var WooCommerceNumberOfReviews */
  private $filter;

  /** @var int */
  private $productId;

  public function _before(): void {
    $this->filter = $this->diContainer->get(WooCommerceNumberOfReviews::class);
    $this->productId = $this->tester->createWooCommerceProduct(['name' => 'Some really fantastic product'])->get_id();
  }

  public function testAnyRatingOnlyReturnsSubscribersWithRating(): void {
    $customerId = $this->tester->createCustomer('1@e.com');
    $this->tester->createCustomer('noreview@e.com');
    $this->tester->createWooProductReview($customerId, '1@e.com', $this->productId, 1, Carbon::now()->subDay());
    $this->assertFilterReturnsEmails('any', '>', 0, 200, 'inTheLast', ['1@e.com']);
  }

  public function testItWorksWithOverAllTimeOption(): void {
    $customerId = $this->tester->createCustomer('1@e.com');
    $this->tester->createWooProductReview($customerId, '1@e.com', $this->productId, 1, Carbon::now()->subDays(20));
    $this->assertFilterReturnsEmails('any', '>', 0, 2, 'inTheLast', []);
    $this->assertFilterReturnsEmails('any', '>', 0, 2, 'allTime', ['1@e.com']);
  }

  public function testItHandlesExactNumberOfReviews(): void {
    $customerId = $this->tester->createCustomer('test1@e.com');
    $this->tester->createWooProductReview($customerId, 'test1@e.com', $this->productId, 1, Carbon::now()->subDay());
    $this->tester->createWooProductReview($customerId, 'test1@e.com', $this->productId, 2, Carbon::now()->subDay());
    $this->assertFilterReturnsEmails('any', '=', 2, 200, 'inTheLast', ['test1@e.com']);
  }

  public function testItHandlesGreaterThan(): void {
    $customerId = $this->tester->createCustomer('greaterThanTest@e.com');
    $this->tester->createWooProductReview($customerId, 'greaterthantest@e.com', $this->productId, 1, Carbon::now()->subDay());
    $this->tester->createWooProductReview($customerId, 'greaterthantest@e.com', $this->productId, 2, Carbon::now()->subDay());
    $customerId = $this->tester->createCustomer('oneReviewTest@e.com');
    $this->tester->createWooProductReview($customerId, 'onereviewtest@e.com', $this->productId, 1, Carbon::now()->subDay());
    $this->assertFilterReturnsEmails('any', '>', 1, 200, 'inTheLast', ['greaterThanTest@e.com']);
  }

  public function testItHandlesLessThan(): void {
    $this->tester->createCustomer('lessthantest@e.com');
    $customerId = $this->tester->createCustomer('onereviewtest@e.com');
    $this->tester->createWooProductReview($customerId, 'onereviewtest@e.com', $this->productId, 1, Carbon::now()->subDay());
    $customerId2 = $this->tester->createCustomer('tworeviews@e.com');
    $this->tester->createWooProductReview($customerId, 'tworeviews@e.com', $this->productId, 2, Carbon::now()->subDay());
    $this->tester->createWooProductReview($customerId, 'tworeviews@e.com', $this->productId, 2, Carbon::now()->subDay());
    $this->assertFilterReturnsEmails('any', '<', 1, 200, 'inTheLast', ['lessthantest@e.com']);
    $this->assertFilterReturnsEmails('any', '<', 2, 200, 'inTheLast', ['lessthantest@e.com', 'onereviewtest@e.com']);
  }

  public function testItHandlesNotEquals(): void {
    $this->tester->createCustomer('notequalstest@e.com');
    $customerId = $this->tester->createCustomer('onereviewtest@e.com');
    $this->tester->createWooProductReview($customerId, 'onereviewtest@e.com', $this->productId, 1, Carbon::now()->subDay());
    $customerId = $this->tester->createCustomer('tworeviewstest@e.com');
    $this->tester->createWooProductReview($customerId, 'tworeviewstest@e.com', $this->productId, 1, Carbon::now()->subDay());
    $this->tester->createWooProductReview($customerId, 'tworeviewstest@e.com', $this->productId, 2, Carbon::now()->subDay());

    $this->assertFilterReturnsEmails('any', '!=', 1, 200, 'inTheLast', ['notequalstest@e.com', 'tworeviewstest@e.com']);
  }

  public function testItHandlesCustomerWithNoReviews(): void {
    $this->tester->createCustomer('test2@e.com');
    $this->assertFilterReturnsEmails('any', '>', 0, 200, 'inTheLast', []);
  }

  public function testItIncludesCustomersWithNoReviewsWhenUsingLessThan(): void {
    $this->tester->createCustomer('test1@e.com');
    $customerId = $this->tester->createCustomer('test2@e.com');
    $this->tester->createWooProductReview($customerId, 'test2@e.com', $this->productId, 1, Carbon::now()->subDay());
    $this->assertFilterReturnsEmails('any', '<', 1, 200, 'inTheLast', ['test1@e.com']);
  }

  public function testFiltersByDifferentRatings(): void {
    $customerOneId = $this->tester->createCustomer('customer-one@test.com');
    $this->tester->createWooProductReview($customerOneId, 'customer-one@test.com', $this->productId, 5);
    $this->tester->createWooProductReview($customerOneId, 'customer-one@test.com', $this->productId, 5);
    $this->tester->createWooProductReview($customerOneId, 'customer-one@test.com', $this->productId, 3);

    $customerTwoId = $this->tester->createCustomer('customer-two@test.com');
    $this->tester->createWooProductReview($customerTwoId, 'customer-two@test.com', $this->productId, 4);
    $this->tester->createWooProductReview($customerTwoId, 'customer-two@test.com', $this->productId, 4);
    $this->tester->createWooProductReview($customerTwoId, 'customer-two@test.com', $this->productId, 4);

    $customerThreeId = $this->tester->createCustomer('customer-three@test.com');
    $this->tester->createWooProductReview($customerThreeId, 'customer-three@test.com', $this->productId, 2);
    $this->tester->createWooProductReview($customerThreeId, 'customer-three@test.com', $this->productId, 5);

    $this->assertFilterReturnsEmails('5', '>', 1, 200, 'inTheLast', ['customer-one@test.com']);
    $this->assertFilterReturnsEmails('4', '=', 3, 200, 'inTheLast', ['customer-two@test.com']);
    $this->assertFilterReturnsEmails('2', '=', 1, 200, 'inTheLast', ['customer-three@test.com']);
  }

  public function testFiltersByDifferentDates(): void {
    $customerFourId = $this->tester->createCustomer('1@e.com');
    $this->tester->createWooProductReview($customerFourId, '1@e.com', $this->productId, 5, Carbon::now()->subDays(6));

    $customerFiveId = $this->tester->createCustomer('2@e.com');
    $this->tester->createWooProductReview($customerFiveId, '2@e.com', $this->productId, 5, Carbon::now()->subWeeks(3));

    $customerSixId = $this->tester->createCustomer('3@e.com');
    $this->tester->createWooProductReview($customerSixId, '3@e.com', $this->productId, 4, Carbon::now()->subWeeks(6));

    $this->assertFilterReturnsEmails('5', '=', 1, 7, 'inTheLast', ['1@e.com']);
    $this->assertFilterReturnsEmails('5', '=', 1, 30, 'inTheLast', ['1@e.com', '2@e.com']);
    $this->assertFilterReturnsEmails('4', '=', 1, 50, 'inTheLast', ['3@e.com']);
  }

  public function testItValidatesRatingPresence(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing rating');
    $this->filter->validateFilterData([
      'action' => 'numberOfReviews',
      'count_type' => '!=',
      'days' => '10',
      'count' => '3',
      'timeframe' => 'inTheLast',
    ]);
  }

  /**
   * @dataProvider ratingDataProvider
   */
  public function testItValidatesRatingValue(string $rating, bool $shouldFail): void {
    $data = [
      'action' => 'numberOfReviews',
      'rating' => $rating,
      'days' => '10',
      'count' => '2',
      'count_type' => '!=',
      'timeframe' => 'inTheLast',
    ];

    if ($shouldFail) {
      $this->expectException(InvalidFilterException::class);
      $this->expectExceptionMessage('Invalid rating');
      $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    }

    $this->filter->validateFilterData($data);
  }

  public function ratingDataProvider(): array {
    return [
      'Invalid rating value' => ['6', true],
      'Valid rating value 1' => ['1', false],
      'Valid rating value 2' => ['2', false],
      'Valid rating value 3' => ['3', false],
      'Valid rating value 4' => ['4', false],
      'Valid rating value 5' => ['5', false],
      'Valid rating value any' => ['any', false],
    ];
  }

  public function testItValidatesCountType(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing count type');
    $this->filter->validateFilterData([
      'action' => 'numberOfReviews',
      'rating' => '3',
      'days' => '10',
      'count' => '3',
      'timeframe' => 'inTheLast',
    ]);
  }

  public function testItValidatesCountTypeOptions(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::INVALID_TYPE);
    $this->expectExceptionMessage('Invalid count type');
    $this->filter->validateFilterData([
      'action' => 'numberOfReviews',
      'rating' => '3',
      'days' => '10',
      'count' => '3',
      'count_type' => 'invalid',
      'timeframe' => 'inTheLast',
    ]);
  }

  public function testItValidatesCount(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing review count');
    $this->filter->validateFilterData([
      'action' => 'numberOfReviews',
      'count_type' => '!=',
      'rating' => '3',
      'days' => '10',
      'timeframe' => 'inTheLast',
    ]);
  }

  public function _after(): void {
    parent::_after();
    $this->cleanUp();
  }

  private function assertFilterReturnsEmails(string $rating, string $countType, int $count, int $days, string $timeframe, array $expectedEmails): void {
    $data = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceNumberOfReviews::ACTION,
      [
        'days' => $days,
        'count_type' => $countType,
        'count' => $count,
        'timeframe' => $timeframe,
        'rating' => $rating,
      ]
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($data, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function cleanUp(): void {
    global $wpdb;
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}comments");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}commentmeta");
  }
}
