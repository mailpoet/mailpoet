<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
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
}
