<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoetVendor\Idiorm\ORM;

class SingleSegmentLoaderTest extends \MailPoetTest {

  private $segment;

  /** @var SingleSegmentLoader */
  private $loader;

  public function _before() {
    $this->loader = new SingleSegmentLoader(new DBMapper());
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
    $this->segment = DynamicSegment::createOrUpdate([
      'name' => 'segment 1',
      'description' => 'description',
    ]);
    $filter = new UserRole('Administrator', 'and');
    $filter_data = DynamicSegmentFilter::create();
    $filter_data->hydrate([
      'segment_id' => $this->segment->id,
      'filter_data' => $filter->toArray(),
    ]);
    $filter_data->save();
  }

  public function testItLoadsSegments() {
    $data = $this->loader->load($this->segment->id);
    expect($data)->isInstanceOf('\MailPoet\Models\DynamicSegment');
  }

  public function testItThrowsForUnknownSegment() {
    $this->setExpectedException('InvalidArgumentException');
    $this->loader->load($this->segment->id + 11564564);
  }

  public function testItPopulatesCommonData() {
    $data = $this->loader->load($this->segment->id);
    expect($data->name)->equals('segment 1');
    expect($data->description)->equals('description');
  }

  public function testItPopulatesFilters() {
    $data = $this->loader->load($this->segment->id);
    $filters0 = $data->getFilters();
    expect($filters0)->count(1);
    expect($filters0[0])->isInstanceOf('\MailPoet\DynamicSegments\Filters\UserRole');
    expect($filters0[0]->getRole())->equals('Administrator');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
  }

}
