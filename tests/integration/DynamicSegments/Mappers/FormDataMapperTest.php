<?php

namespace MailPoet\DynamicSegments\Mappers;

use MailPoet\Models\DynamicSegment;

class FormDataMapperTest extends \MailPoetTest {

  /** @var FormDataMapper */
  private $mapper;

  public function _before() {
    $this->mapper = new FormDataMapper();
  }

  public function testItThrowsForEmptyData() {
    $data = [
      'name' => '',
      'description' => '',
      'segmentType' => '',
    ];
    $this->expectException('\MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException');
    $this->mapper->mapDataToDB($data);
  }

  public function testItThrowsForInvalidType() {
    $data = [
      'name' => '',
      'description' => '',
      'segmentType' => 'invalid',
    ];
    $this->expectException('\MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException');
    $this->mapper->mapDataToDB($data);
  }

  public function testItCreatesUserRoleFilter() {
    $data = [
      'name' => 'Name',
      'description' => 'Description',
      'segmentType' => 'userRole',
      'wordpressRole' => 'administrator',
    ];
    $segment = $this->mapper->mapDataToDB($data);
    $this->assertInstanceOf('\MailPoet\Models\DynamicSegment', $segment);
    $this->assertEquals('Name', $segment->name);
    $this->assertEquals('Description', $segment->description);
    $this->assertTrue($segment->id === null);
    $this->assertCount(1, $segment->getFilters());
  }

  public function testItFailsIfWooCommerceFilterDataIsMissing() {
    $data = [
      'name' => 'Name',
      'description' => 'Description',
      'segmentType' => 'woocommerce',
    ];
    $this->expectException('\MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException');
    $this->mapper->mapDataToDB($data);
  }

  public function testItCreatesWooCommerceCategoryFilter() {
    $data = [
      'name' => 'Name',
      'description' => 'Description',
      'segmentType' => 'woocommerce',
      'category_id' => '45',
      'action' => 'purchasedCategory',
    ];
    $segment = $this->mapper->mapDataToDB($data);
    $this->assertInstanceOf('\MailPoet\Models\DynamicSegment', $segment);
    $this->assertEquals('Name', $segment->name);
    $this->assertEquals('Description', $segment->description);
    $this->assertTrue($segment->id === null);
    $filters = $segment->getFilters();
    $this->assertCount(1, $filters);
    $this->assertInstanceOf('\MailPoet\DynamicSegments\Filters\WooCommerceCategory', $filters[0]);
  }

  public function testItCreatesWooCommerceProductFilter() {
    $data = [
      'name' => 'Name',
      'description' => 'Description',
      'segmentType' => 'woocommerce',
      'product_id' => '45',
      'action' => 'purchasedProduct',
    ];
    $segment = $this->mapper->mapDataToDB($data);
    $this->assertInstanceOf('\MailPoet\Models\DynamicSegment', $segment);
    $this->assertEquals('Name', $segment->name);
    $this->assertEquals('Description', $segment->description);
    $this->assertTrue($segment->id === null);
    $filters = $segment->getFilters();
    $this->assertCount(1, $filters);
    $this->assertInstanceOf('\MailPoet\DynamicSegments\Filters\WooCommerceProduct', $filters[0]);
  }

  public function testItSetsIdOnEdit() {
    $dynamicSegment = DynamicSegment::createOrUpdate([
        'name' => 'segment',
        'description' => 'description',
    ]);
    $data = [
      'id' => (string)$dynamicSegment->id(),
      'name' => 'Name',
      'description' => 'Description',
      'segmentType' => 'userRole',
      'wordpressRole' => 'administrator',
    ];
    $segment = $this->mapper->mapDataToDB($data);
    $this->assertSame($dynamicSegment->id(), $segment->id);

  }
}
