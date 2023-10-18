<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailActionClickAny;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberDateField;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberScore;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSegment;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedViaForm;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTag;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTextField;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSingleOrderValue;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription;

class FilterDataMapperTest extends \MailPoetTest {
  /** @var FilterDataMapper */
  private $mapper;

  public function _before(): void {
    parent::_before();
    $this->mapper = ContainerWrapper::getInstance()->get(FilterDataMapper::class);
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    verify($filter->getAction())->equals(EmailAction::ACTION_OPENED);
    verify($filter->getData())->equals([
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    verify($filter->getAction())->equals(EmailAction::ACTION_CLICKED);
    verify($filter->getData())->equals([
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
      'link_ids' => [2, 3],
    ]],
    ];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    verify($filter->getAction())->equals(EmailAction::ACTION_CLICKED);
    verify($filter->getData())->equals([
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
      'link_ids' => [2, 3],
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals('userRole');
    verify($filter->getData())->equals([
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    verify($filter->getAction())->equals(WooCommerceCategory::ACTION_CATEGORY);
    verify($filter->getData())->equals([
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    verify($filter->getAction())->equals(WooCommerceProduct::ACTION_PRODUCT);
    verify($filter->getData())->equals([
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    verify($filter->getAction())->equals(EmailOpensAbsoluteCountAction::TYPE);
    verify($filter->getData())->equals([
      'opens' => 5,
      'days' => 3,
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    verify($filter->getAction())->equals(EmailActionClickAny::TYPE);
    verify($filter->getData())->equals([
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    verify($filter->getAction())->equals(EmailOpensAbsoluteCountAction::TYPE);
    verify($filter->getData())->equals([
      'opens' => 5,
      'days' => 3,
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
      'operator' => 'less',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItCreatesEmailOpensWithMissingOpens(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'days' => 3,
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
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
      'days' => 1,
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
      'some_mess' => 'mess',
    ]]];

    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);

    $expectedResult = reset($data['filters']);
    unset($expectedResult['some_mess'], $expectedResult['segmentType'], $expectedResult['action']);
    $expectedResult['connect'] = DynamicSegmentFilterData::CONNECT_TYPE_AND;

    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    verify($filter->getAction())->equals(WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS);
    verify($filter->getData())->equals($expectedResult);
  }

  public function testItRaisesExceptionWhenMappingWooCommerceNumberOfOrders(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing required fields');
    $this->expectExceptionCode(InvalidFilterException::MISSING_NUMBER_OF_ORDERS_FIELDS);

    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS,
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
      'days' => 2,
    ]]]);
  }

  public function testItMapsWooCommerceSingleOrderValue(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceSingleOrderValue::ACTION_SINGLE_ORDER_VALUE,
      'single_order_value_type' => '=',
      'single_order_value_amount' => 20,
      'days' => 7,
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
      'some_mess' => 'mess',
    ]]];

    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);

    $expectedResult = reset($data['filters']);
    unset($expectedResult['some_mess'], $expectedResult['segmentType'], $expectedResult['action']);
    $expectedResult['connect'] = DynamicSegmentFilterData::CONNECT_TYPE_AND;

    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    verify($filter->getAction())->equals(WooCommerceSingleOrderValue::ACTION_SINGLE_ORDER_VALUE);
    verify($filter->getData())->equals($expectedResult);
  }

  public function testItRaisesExceptionWhenMappingWooCommerceSingleOrderValue(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing required fields');
    $this->expectExceptionCode(InvalidFilterException::MISSING_SINGLE_ORDER_VALUE_FIELDS);

    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceSingleOrderValue::ACTION_SINGLE_ORDER_VALUE,
      'days' => 2,
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION);
    verify($filter->getAction())->equals(WooCommerceSubscription::ACTION_HAS_ACTIVE);
    verify($filter->getData())->equals([
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
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    verify($filter->getAction())->equals(WooCommerceCountry::ACTION_CUSTOMER_COUNTRY);
    verify($filter->getData())->equals([
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

  public function testItChecksSubscriberScoreValue(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing engagement score value');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberScore::TYPE,
    ]]]);
  }

  public function testItMapsSubscribedDate(): void {
    $filters = $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberScore::TYPE,
      'value' => 2,
      'operator' => SubscriberScore::HIGHER_THAN,
      'some' => 'mess',
    ]]]);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals(SubscriberScore::TYPE);
    verify($filter->getData())->equals([
      'value' => 2,
      'operator' => SubscriberScore::HIGHER_THAN,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksSubscriberSegmentSegments(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing segments');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSegment::TYPE,
    ]]]);
  }

  public function testItMapsSubscriberSegment(): void {
    $filters = $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSegment::TYPE,
      'segments' => [1, 5],
      'operator' => DynamicSegmentFilterData::OPERATOR_NONE,
      'some' => 'mess',
    ]]]);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals(SubscriberSegment::TYPE);
    verify($filter->getData())->equals([
      'segments' => [1, 5],
      'operator' => DynamicSegmentFilterData::OPERATOR_NONE,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksCustomFieldCustomFieldId(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing custom field id');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => MailPoetCustomFields::TYPE,
    ]]]);
  }

  public function testItChecksCustomFieldCustomFieldType(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing custom field type');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => MailPoetCustomFields::TYPE,
      'custom_field_id' => 123,
    ]]]);
  }

  public function testItChecksCustomFieldValue(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing value');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => MailPoetCustomFields::TYPE,
      'custom_field_id' => 123,
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
    ]]]);
  }

  public function testItMapsCustomFieldFilter(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => MailPoetCustomFields::TYPE,
      'custom_field_id' => 123,
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'value' => 4,
      'operator' => 'equals',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals(MailPoetCustomFields::TYPE);
    verify($filter->getData())->equals([
      'custom_field_id' => 123,
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'value' => 4,
      'operator' => 'equals',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItCheckSubscriberTagTags(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing tags');
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberTag::TYPE,
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]]];
    $this->mapper->map($data);
  }

  public function testItMapsSubscriberTagFilter(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberTag::TYPE,
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'tags' => ['12'],
      'some_mess' => 'mess',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals(SubscriberTag::TYPE);
    verify($filter->getData())->equals([
      'tags' => ['12'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItMapsSubscriberTextField() {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => 'subscriberFirstName',
      'operator' => 'contains',
      'field' => 'email',
      'value' => 'some value',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals(SubscriberTextField::FIRST_NAME);
    verify($filter->getData())->equals([
      'value' => 'some value',
      'operator' => 'contains',
      'action' => 'subscriberFirstName',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testSubscriberTextFieldThrowsErrorWithInvalidOperator(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Invalid operator');
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => 'subscriberEmail',
      'operator' => 'invalid_operator',
      'value' => 'some value',
    ]]];
    $this->mapper->map($data);
  }

  public function testItMapsSubscriberSubscribedViaForm(): void {
    $filters = $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSubscribedViaForm::TYPE,
      'form_ids' => ['1', '2'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]]]);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals(SubscriberSubscribedViaForm::TYPE);
    verify($filter->getData())->equals([
      'form_ids' => [1, 2],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItChecksSubscribedViaFormForFormIds(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing at least one form ID');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSubscribedViaForm::TYPE,
      'form_ids' => [],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]]]);
  }

  public function testItChecksSubscribedViaFormForOperator(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing valid operator');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => SubscriberSubscribedViaForm::TYPE,
      'form_ids' => ['1'],
      'operator' => 'not a valid operator',
    ]]]);
  }

  public function testItMapsEnteredAutomation() {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_AUTOMATIONS,
      'action' => 'enteredAutomation',
      'operator' => 'any',
      'automation_ids' => ['1', '2'],
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_AUTOMATIONS);
    verify($filter->getAction())->equals('enteredAutomation');
    verify($filter->getData())->equals([
      'action' => 'enteredAutomation',
      'operator' => 'any',
      'automation_ids' => ['1', '2'],
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItMapsExitedAutomation() {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_AUTOMATIONS,
      'action' => 'exitedAutomation',
      'operator' => 'all',
      'automation_ids' => ['1', '2'],
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_AUTOMATIONS);
    verify($filter->getAction())->equals('exitedAutomation');
    verify($filter->getData())->equals([
      'action' => 'exitedAutomation',
      'operator' => 'all',
      'automation_ids' => ['1', '2'],
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItThrowsExceptionIfAutomationIdsMissing() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing automation IDs');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_AUTOMATIONS,
      'action' => 'enteredAutomation',
      'operator' => 'none',
    ]]]);
  }

  public function testItThrowsExceptionIfAutomationsOperatorIsInvalid(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_OPERATOR);
    $this->expectExceptionMessage('Missing operator');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_AUTOMATIONS,
      'action' => 'enteredAutomation',
      'operator' => 'not a valid operator',
    ]]]);
  }

  public function testItThrowsExceptionIfInvalidAutomationsAction(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->expectExceptionMessage('Unknown automations action');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_AUTOMATIONS,
      'action' => 'didSomethingUnsupportedWithAutomation',
      'operator' => 'any',
      'automation_ids' => ['1', '2'],
    ]]]);
  }

  public function testItThrowsExceptionIfAutomationActionIsMissing(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->expectExceptionMessage('Missing automations filter action');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_AUTOMATIONS,
      'operator' => 'any',
      'automation_ids' => ['1', '2'],
    ]]]);
  }

  public function testItMapsWooNumberOfReviews(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => 'numberOfReviews',
      'operator' => 'all',
      'count_type' => '!=',
      'rating' => '3',
      'days' => '10',
      'count' => '3',
      'timeframe' => 'inTheLast',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    verify($filter->getAction())->equals('numberOfReviews');
    verify($filter->getData())->equals([
      'connect' => 'and',
      'days' => '10',
      'count_type' => '!=',
      'count' => '3',
      'rating' => '3',
      'timeframe' => 'inTheLast',
    ]);
  }

  public function testItMapsWooUsedCouponCodes(): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => 'usedCouponCode',
      'operator' => 'all',
      'days' => '10',
      'timeframe' => 'inTheLast',
      'coupon_code_ids' => ['1', '5'],
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    verify($filter->getAction())->equals('usedCouponCode');
    verify($filter->getData())->equals([
      'connect' => 'and',
      'operator' => 'all',
      'days' => '10',
      'timeframe' => 'inTheLast',
      'coupon_code_ids' => ['1', '5'],
    ]);
  }

  /**
   * @dataProvider dateFieldActions
   */
  public function testItMapsSubscriberDateField(string $action): void {
    $data = ['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => $action,
      'operator' => 'on',
      'value' => '2023-07-01',
    ]]];
    $filters = $this->mapper->map($data);
    expect($filters)->array();
    expect($filters)->count(1);
    $filter = reset($filters);
    $this->assertInstanceOf(DynamicSegmentFilterData::class, $filter);
    verify($filter)->instanceOf(DynamicSegmentFilterData::class);
    verify($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getAction())->equals($action);
    verify($filter->getData())->equals([
      'value' => '2023-07-01',
      'operator' => 'on',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function dateFieldActions(): array {
    return [
      [SubscriberDateField::LAST_CLICK_DATE],
      [SubscriberDateField::LAST_ENGAGEMENT_DATE],
      [SubscriberDateField::LAST_PURCHASE_DATE],
      [SubscriberDateField::LAST_OPEN_DATE],
      [SubscriberDateField::LAST_PAGE_VIEW_DATE],
      [SubscriberDateField::SUBSCRIBED_DATE],
    ];
  }

  public function testItThrowsExceptionForSubscriberDateFieldInvalidOperator(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_OPERATOR);
    $this->expectExceptionMessage('Invalid operator');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => 'lastClickDate',
      'operator' => 'notAValidOperator',
      'value' => '2023-07-01',
    ]]]);
  }

  public function testItThrowsExceptionForSubscriberDateFieldMissingValue(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->expectExceptionMessage('Missing date value');
    $this->mapper->map(['filters' => [[
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => 'lastClickDate',
      'operator' => 'on',
    ]]]);
  }
}
