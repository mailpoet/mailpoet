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

class FilterDataMapperTest extends \MailPoetUnitTest {
  /** @var FilterDataMapper */
  private $mapper;

  public function _before() {
    parent::_before();
    $this->mapper = new FilterDataMapper();
  }

  public function testItChecksFiltersArePresent() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Filters are missing');
    $this->expectExceptionCode(InvalidFilterException::MISSING_FILTER);
    $this->mapper->map([]);
  }

  public function testItChecksFilterTypeIsPresent() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Segment type is not set');
    $this->expectExceptionCode(InvalidFilterException::MISSING_TYPE);
    $this->mapper->map(['filters' => [['someFilter']]]);
  }

  public function testItChecksFilterTypeIsValid() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Invalid type');
    $this->expectExceptionCode(InvalidFilterException::INVALID_TYPE);
    $this->mapper->map(['filters' => [['segmentType' => 'noexistent']]]);
  }

  public function testItMapsEmailFilter() {
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

  public function testItChecksFilterEmailAction() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'newsletters' => [1],
    ]]]);
  }

  public function testItChecksFilterEmailNewsletter() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing newsletter');
    $this->expectExceptionCode(InvalidFilterException::MISSING_NEWSLETTER_ID);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_OPENED,
    ]]]);
  }

  public function testItChecksFilterEmailActionIsSupported() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Invalid email action');
    $this->expectExceptionCode(InvalidFilterException::INVALID_EMAIL_ACTION);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'newsletter_id' => 1,
      'action' => 'unknown',
    ]]]);
  }

  public function testItMapsUserRoleFilter() {
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

  public function testItChecksUserRoleFilterRole() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing role');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ROLE);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
    ]]]);
  }

  public function testItChecksSubscribedDateValue() {
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSubscribedDate::TYPE,
    ]]]);
  }

  public function testItCreatesSubscribedDate() {
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

  public function testItMapsWooCommerceCategory() {
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

  public function testItChecksWooCommerceAction() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'category_ids' => ['10'],
    ]]]);
  }

  public function testItChecksWooCommerceCategoryId() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing category');
    $this->expectExceptionCode(InvalidFilterException::MISSING_CATEGORY_ID);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]]]);
  }

  public function testItChecksWooCommerceCategoryOperator() {
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

  public function testItMapsWooCommerceProduct() {
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

  public function testItChecksWooCommerceProductId() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing product');
    $this->expectExceptionCode(InvalidFilterException::MISSING_PRODUCT_ID);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceProduct::ACTION_PRODUCT,
    ]]]);
  }

  public function testItCreatesEmailOpens() {
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

  public function testItMapsLinkClicksAny() {
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

  public function testItCreatesEmailOpensWithOperator() {
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

  public function testItCreatesEmailOpensWithMissingOpens() {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'days' => 3,
    ]]];
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map($data);
  }

  public function testItCreatesEmailOpensWithMissingDays() {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
    ]]];
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map($data);
  }

  public function testItMapsWooCommerceNumberOfOrders() {
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

  public function testItRaisesExceptionWhenMappingWooCommerceNumberOfOrders() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing required fields');
    $this->expectExceptionCode(InvalidFilterException::MISSING_NUMBER_OF_ORDERS_FIELDS);

    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS,
    ]]]);
  }

  public function testItMapsWooCommerceSubscription() {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'action' => WooCommerceSubscription::ACTION_HAS_ACTIVE,
      'product_id' => '10',
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
      'product_id' => '10',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksWooCommerceSubscriptionAction() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'product_id' => '10',
    ]]];
    $this->mapper->map($data);
  }

  public function testItChecksWooCommerceSubscriptionProductId() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing product');
    $this->expectExceptionCode(InvalidFilterException::MISSING_PRODUCT_ID);
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'action' => WooCommerceSubscription::ACTION_HAS_ACTIVE,
    ]]];
    $this->mapper->map($data);
  }

  public function testItCreatesWooCommerceCustomerCountry() {
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

  public function testItRaisesExceptionCountryIsMissingForWooCommerceCustomerCountry() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing country');
    $this->expectExceptionCode(InvalidFilterException::MISSING_COUNTRY);
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCountry::ACTION_CUSTOMER_COUNTRY,
    ]]]);
  }
}
