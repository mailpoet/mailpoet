<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;

class FilterDataMapperTest extends \MailPoetUnitTest {
  /** @var FilterDataMapper */
  private $mapper;

  public function _before() {
    parent::_before();
    $this->mapper = new FilterDataMapper();
  }

  public function testItChecksFilterTypeIsPresent() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Segment type is not set');
    $this->expectExceptionCode(InvalidFilterException::MISSING_TYPE);
    $this->mapper->map([]);
  }

  public function testItChecksFilterTypeIsValid() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Invalid type');
    $this->expectExceptionCode(InvalidFilterException::INVALID_TYPE);
    $this->mapper->map(['segmentType' => 'noexistent']);
  }

  public function testItMapsEmailFilter() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_OPENED,
      'newsletter_id' => 1,
      'some_mess' => 'mess',
    ];
    $filter = $this->mapper->map($data);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getData())->equals([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_OPENED,
      'newsletter_id' => 1,
    ]);
  }

  public function testItChecksFilterEmailAction() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'newsletter_id' => 1,
    ]);
  }

  public function testItChecksFilterEmailNewsletter() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing newsletter id');
    $this->expectExceptionCode(InvalidFilterException::MISSING_NEWSLETTER_ID);
    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_OPENED,
    ]);
  }

  public function testItChecksFilterEmailActionIsSupported() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Invalid email action');
    $this->expectExceptionCode(InvalidFilterException::INVALID_EMAIL_ACTION);
    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'newsletter_id' => 1,
      'action' => 'unknown',
    ]);
  }

  public function testItMapsUserRoleFilter() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
      'some_mess' => 'mess',
    ];
    $filter = $this->mapper->map($data);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    expect($filter->getData())->equals([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
    ]);
  }

  public function testItChecksUserRoleFilterRole() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing role');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ROLE);
    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
    ]);
  }

  public function testItMapsWooCommerceCategory() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'category_id' => '1',
      'some_mess' => 'mess',
    ];
    $filter = $this->mapper->map($data);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($filter->getData())->equals([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'category_id' => '1',
    ]);
  }

  public function testItChecksWooCommerceAction() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing action');
    $this->expectExceptionCode(InvalidFilterException::MISSING_ACTION);
    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'category_id' => '10',
    ]);
  }

  public function testItChecksWooCommerceCategoryId() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing category');
    $this->expectExceptionCode(InvalidFilterException::MISSING_CATEGORY_ID);
    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceCategory::ACTION_CATEGORY,
    ]);
  }

  public function testItMapsWooCommerceProduct() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceProduct::ACTION_PRODUCT,
      'product_id' => '10',
      'some_mess' => 'mess',
    ];
    $filter = $this->mapper->map($data);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($filter->getData())->equals([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceProduct::ACTION_PRODUCT,
      'product_id' => '10',
    ]);
  }

  public function testItChecksWooCommerceProductId() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing product');
    $this->expectExceptionCode(InvalidFilterException::MISSING_PRODUCT_ID);
    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceProduct::ACTION_PRODUCT,
    ]);
  }

  public function testItCreatesEmailOpens() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
      'days' => 3,
    ];
    $filter = $this->mapper->map($data);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getData())->equals([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
      'days' => 3,
      'operator' => 'more',
    ]);
  }

  public function testItCreatesEmailOpensWithOperator() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
      'days' => 3,
      'operator' => 'less',
    ];
    $filter = $this->mapper->map($data);
    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_EMAIL);
    expect($filter->getData())->equals([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
      'days' => 3,
      'operator' => 'less',
    ]);
  }

  public function testItCreatesEmailOpensWithMissingOpens() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'days' => 3,
    ];
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map($data);
  }

  public function testItCreatesEmailOpensWithMissingDays() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailOpensAbsoluteCountAction::TYPE,
      'opens' => 5,
    ];
    $this->expectException(InvalidFilterException::class);
    $this->mapper->map($data);
  }

  public function testItMapsWooCommerceNumberOfOrders() {
    $data = [
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS,
      'number_of_orders_type' => '=',
      'number_of_orders_count' => 2,
      'number_of_orders_days' => 1,
      'some_mess' => 'mess',
    ];

    $filter = $this->mapper->map($data);

    unset($data['some_mess']);
    $expectedResult = $data;

    expect($filter)->isInstanceOf(DynamicSegmentFilterData::class);
    expect($filter->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($filter->getData())->equals($expectedResult);
  }

  public function testItRaisesExceptionWhenMappingWooCommerceNumberOfOrders() {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionMessage('Missing required fields');
    $this->expectExceptionCode(InvalidFilterException::MISSING_NUMBER_OF_ORDERS_FIELDS);

    $this->mapper->map([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS,
    ]);
  }
}
