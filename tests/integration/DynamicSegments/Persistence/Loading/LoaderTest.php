<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoetVendor\Idiorm\ORM;

class LoaderTest extends \MailPoetTest {

  private $segments;

  /** @var Loader */
  private $loader;

  public function _before() {
    $this->loader = new Loader(new DBMapper());
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
    $this->segments[] = DynamicSegment::createOrUpdate([
      'name' => 'segment 1',
      'description' => 'description',
    ]);
    $this->segments[] = DynamicSegment::createOrUpdate([
      'name' => 'segment 2',
      'description' => 'description',
    ]);
    $filter = new UserRole('Administrator', 'and');
    $filter_data = DynamicSegmentFilter::create();
    $filter_data->hydrate([
      'segment_id' => $this->segments[1]->id,
      'filter_data' => $filter->toArray(),
    ]);
    $filter_data->save();
    $filter = new UserRole('Editor', 'or');
    $filter_data = DynamicSegmentFilter::create();
    $filter_data->hydrate([
      'segment_id' => $this->segments[0]->id,
      'filter_data' => $filter->toArray(),
    ]);
    $filter_data->save();
  }

  public function testItLoadsSegments() {
    $data = $this->loader->load();
    expect($data)->count(2);
    expect($data[0])->isInstanceOf('\MailPoet\Models\DynamicSegment');
    expect($data[1])->isInstanceOf('\MailPoet\Models\DynamicSegment');
  }

  public function testItDoesNotLoadTrashedSegments() {
    $this->segments[0]->trash();
    $data = $this->loader->load();
    expect($data)->count(1);
    expect($data[0])->isInstanceOf('\MailPoet\Models\DynamicSegment');
    expect($data[0]->name)->equals('segment 2');
  }

  public function testItPopulatesCommonData() {
    $data = $this->loader->load();
    expect($data[0]->name)->equals('segment 1');
    expect($data[1]->name)->equals('segment 2');
    expect($data[0]->description)->equals('description');
    expect($data[1]->description)->equals('description');
  }

  public function testItPopulatesFilters() {
    $data = $this->loader->load();
    $filters0 = $data[0]->getFilters();
    $filters1 = $data[1]->getFilters();
    expect($filters0)->count(1);
    expect($filters1)->count(1);
    /** @var UserRole $filter0 */
    $filter0 = $filters0[0];
    /** @var UserRole $filter1 */
    $filter1 = $filters1[0];
    expect($filter0)->isInstanceOf('\MailPoet\DynamicSegments\Filters\UserRole');
    expect($filter1)->isInstanceOf('\MailPoet\DynamicSegments\Filters\UserRole');
    expect($filter0->getRole())->equals('Editor');
    expect($filter1->getRole())->equals('Administrator');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
  }
}
