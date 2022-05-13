<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailActionClickAny;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedDate;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription;
use MailPoet\WP\Functions as WPFunctions;

class FilterDataMapperTest extends \MailPoetUnitTest {
  /** @var FilterDataMapper */
  private $mapper;

  public function _before(): void {
    parent::_before();
    $wp = $this->makeEmpty(WPFunctions::class, [
      'hasFilter' => false,
    ]);
    $this->mapper = new FilterDataMapper($wp);
  }

  public function testItChecksFiltersArePresent(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Filters are missing');
    $this->expectExceptionCode(InvalidFilterException::MISSING_FILTER);
    $this->mapper->map([]);
  }

  public function testItChecksFilterTypeIsPresent(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Segment type is not set');
    $this->expectExceptionCode(InvalidFilterException::MISSING_TYPE);
    $this->mapper->map(['filters' => [['someFilter']]]);
  }

  public function testItChecksFilterTypeIsValid(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Invalid type');
    $this->expectExceptionCode(InvalidFilterException::INVALID_TYPE);
    $this->mapper->map(['filters' => [['segmentType' => 'noexistent']]]);
  }

  public function testItMapsEmailFilter(): void {
    $data = ['filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
        'action' => EmailAction::ACTION_OPENED,
        'newsletters' => [1],
      ]],
      'some_mess' => 'mess',
    ];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getAction())->equals(EmailAction::ACTION_OPENED);
    expect($filter->getData())->equals([
      'newsletters' => [1],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItMapsEmailFilterForClicksWithoutLink(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_CLICKED,
      'newsletter_id' => 1,
    ]],
    ];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getAction())->equals(EmailAction::ACTION_CLICKED);
    expect($filter->getData())->equals([
      'newsletter_id' => 1,
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItMapsEmailFilterForClicksWithLinks(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_CLICKED,
      'newsletter_id' => 1,
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'link_ids' => [2,3],
    ]],
    ];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getAction())->equals(EmailAction::ACTION_CLICKED);
    expect($filter->getData())->equals([
      'newsletter_id' => 1,
      'link_ids' => [2, 3],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksOperatorForEmailFilterForClicksWithLinks(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_CLICKED,
      'newsletter_id' => 1,
      'link_ids' => [2,3],
    ]],
    ];
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing operator');
    $this->expectExceptionCode(InvalidFilterException::MISSING_OPERATOR);
    $this->mapper->map($data);
  }

  public function testItChecksFilterEmailAction(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'newsletters' => [1],
    ]]]);
  }

  public function testItChecksFilterEmailNewsletter(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing newsletter');
    $this->expectExceptionCode(InvalidFilterException::MISSING_NEWSLETTER_ID);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_OPENED,
    ]]]);
  }

  public function testItChecksFilterEmailActionIsSupported(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Invalid email action');
    $this->expectExceptionCode(InvalidFilterException::INVALID_EMAIL_ACTION);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'newsletter_id' => 1,
      'action' => 'unknown',
    ]]]);
  }

  public function testItMapsUserRoleFilter(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => ['editor'],
      'some_mess' => 'mess',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    expect($filter->getAction())->equals('userRole');
    expect($filter->getData())->equals([
      'wordpressRole' => ['editor'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksUserRoleFilterRole(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing role');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ROLE);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
    ]]]);
  }

  public function testItChecksSubscribedDateValue(): void {
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSubscribedDate::TYPE,
    ]]]);
  }

  public function testItCreatesSubscribedDate(): void {
    $filters = $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSubscribedDate::TYPE,
      'value' => 2,
      'operator' => SubscriberSubscribedDate::AFTER,
    ]]]);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    expect($filter->getAction())->equals(SubscriberSubscribedDate::TYPE);
    expect($filter->getData())->equals([
      'value' => 2,
      'operator' => SubscriberSubscribedDate::AFTER,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItMapsWooCommerceCategory(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'category_ids' => ['1', '3'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'some_mess' => 'mess',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($filter->getAction())->equals(WooCommerceCategory::ACTION_CATEGORY);
    expect($filter->getData())->equals([
      'category_ids' => ['1', '3'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksWooCommerceAction(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'category_ids' => ['10'],
    ]]]);
  }

  public function testItChecksWooCommerceCategoryId(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing category');
    $this->expectExceptionCode(InvalidFilterException::MISSING_CATEGORY_ID);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]]]);
  }

  public function testItChecksWooCommerceCategoryOperator(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing operator');
    $this->expectExceptionCode(InvalidFilterException::MISSING_OPERATOR);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
      'category_ids' => ['10'],
    ]]]);
  }

  public function testItMapsWooCommerceProduct(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceProduct::ACTION_PRODUCT,
      'product_ids' => ['10', '11'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'some_mess' => 'mess',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($filter->getAction())->equals(WooCommerceProduct::ACTION_PRODUCT);
    expect($filter->getData())->equals([
      'product_ids' => ['10', '11'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksWooCommerceProductId(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing product');
    $this->expectExceptionCode(InvalidFilterException::MISSING_PRODUCT_ID);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceProduct::ACTION_PRODUCT,
    ]]]);
  }

  public function testItCreatesEmailOpens(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
      'days' => 3,
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getAction())->equals(EmailOpensAbsoluteCountAction::TYPE);
    expect($filter->getData())->equals([
      'opens' => 5,
      'days' => 3,
      'operator' => 'more',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItMapsLinkClicksAny(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailActionClickAny::TYPE,
      'uselessParam' => 1,
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getAction())->equals(EmailActionClickAny::TYPE);
    expect($filter->getData())->equals([
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItCreatesEmailOpensWithOperator(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
      'days' => 3,
      'operator' => 'less',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getAction())->equals(EmailOpensAbsoluteCountAction::TYPE);
    expect($filter->getData())->equals([
      'opens' => 5,
      'days' => 3,
      'operator' => 'less',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItCreatesEmailOpensWithMissingOpens(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'days' => 3,
    ]]];
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map($data);
  }

  public function testItCreatesEmailOpensWithMissingDays(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
    ]]];
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map($data);
  }

  public function testItMapsWooCommerceNumberOfOrders(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS,
      'number_of_orders_type' => '=',
      'number_of_orders_count' => 2,
      'number_of_orders_days' => 1,
      'some_mess' => 'mess',
    ]]];

    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);

    $expectedResult = reset($data['filters']);
    unset($expectedResult['some_mess'], $expectedResult['segmentType'], $expectedResult['action']);
    $expectedResult['connect'] = DynamicSegmentFilterData::CONNECT_TYPE_AND;

    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($filter->getAction())->equals(WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS);
    expect($filter->getData())->equals($expectedResult);
  }

  public function testItRaisesExceptionWhenMappingWooCommerceNumberOfOrders(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing required fields');
    $this->expectExceptionCode(InvalidFilterException::MISSING_NUMBER_OF_ORDERS_FIELDS);

    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS,
    ]]]);
  }

  public function testItMapsWooCommerceSubscription(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'action' => WooCommerceSubscription::ACTION_HAS_ACTIVE,
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'product_ids' => ['10'],
      'some_mess' => 'mess',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION);
    expect($filter->getAction())->equals(WooCommerceSubscription::ACTION_HAS_ACTIVE);
    expect($filter->getData())->equals([
      'product_ids' => ['10'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksWooCommerceSubscriptionAction(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'product_ids' => ['10'],
    ]]];
    $this->mapper->map($data);
  }

  public function testItChecksWooCommerceSubscriptionProductIds(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing product');
    $this->expectExceptionCode(InvalidFilterException::MISSING_PRODUCT_ID);
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'action' => WooCommerceSubscription::ACTION_HAS_ACTIVE,
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]]];
    $this->mapper->map($data);
  }

  public function testItChecksWooCommerceSubscriptionMissingOperator(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing product');
    $this->expectExceptionCode(InvalidFilterException::MISSING_PRODUCT_ID);
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'action' => WooCommerceSubscription::ACTION_HAS_ACTIVE,
    ]]];
    $this->mapper->map($data);
  }

  public function testItCreatesWooCommerceCustomerCountry(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCountry::ACTION_CUSTOMER_COUNTRY,
      'country_code' => ['UK'],
      'nonsense' => 1,
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    assert($filter instanceof DynamicSegmentFilterData);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($filter->getAction())->equals(WooCommerceCountry::ACTION_CUSTOMER_COUNTRY);
    expect($filter->getData())->equals([
      'country_code' => ['UK'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItRaisesExceptionCountryIsMissingForWooCommerceCustomerCountry(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing country');
    $this->expectExceptionCode(InvalidFilterException::MISSING_COUNTRY);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCountry::ACTION_CUSTOMER_COUNTRY,
    ]]]);
  }
}
