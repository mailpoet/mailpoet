<?php

namespace MailPoet\Premium\DynamicSegments\Persistence\Loading;

use MailPoet\Premium\DynamicSegments\Filters\UserRole;
use MailPoet\Premium\DynamicSegments\Mappers\DBMapper;
use MailPoet\Premium\Models\DynamicSegment;
use MailPoet\Premium\Models\DynamicSegmentFilter;

class SingleSegmentLoaderTest extends \MailPoetTest {

  private $segment;

  /** @var SingleSegmentLoader */
  private $loader;

  function _before() {
    $this->loader = new SingleSegmentLoader(new DBMapper());
    \ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
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

  function testItLoadsSegments() {
    $data = $this->loader->load($this->segment->id);
    expect($data)->isInstanceOf('\MailPoet\Premium\Models\DynamicSegment');
  }

  function testItThrowsForUnknownSegment() {
    $this->setExpectedException('InvalidArgumentException');
    $this->loader->load($this->segment->id + 11564564);
  }

  function testItPopulatesCommonData() {
    $data = $this->loader->load($this->segment->id);
    expect($data->name)->equals('segment 1');
    expect($data->description)->equals('description');
  }

  function testItPopulatesFilters() {
    $data = $this->loader->load($this->segment->id);
    $filters0 = $data->getFilters();
    expect($filters0)->count(1);
    expect($filters0[0])->isInstanceOf('\MailPoet\Premium\DynamicSegments\Filters\UserRole');
    expect($filters0[0]->getRole())->equals('Administrator');
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
  }

}
