<?php

namespace MailPoet\DynamicSegments\Persistence;

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoet\Models\Model;
use MailPoetVendor\Idiorm\ORM;

class SaverTest extends \MailPoetTest {

  /** @var  Saver */
  private $saver;

  public function _before() {
    $this->saver = new Saver();
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
  }

  public function testItSavesSegment() {
    $dynamicSegment = DynamicSegment::create();
    $dynamicSegment->hydrate([
      'name' => 'segment 1',
      'description' => 'desc',
    ]);
    $id = $this->saver->save($dynamicSegment);
    $loaded = DynamicSegment::findOne($id);
    assert($loaded instanceof DynamicSegment);
    expect($loaded->name)->equals('segment 1');
    expect($loaded->description)->equals('desc');
  }

  public function testItThrowsOnDuplicateSegment() {
    $dynamicSegment1 = DynamicSegment::createOrUpdate([
      'name' => 'segment 1',
      'description' => 'description',
    ]);
    $dynamicSegment2 = DynamicSegment::create();
    $dynamicSegment2->hydrate([
      'name' => 'segment 2',
      'description' => 'desc2',
      'id' => $dynamicSegment1->id,
    ]);
    $this->expectException('\MailPoet\DynamicSegments\Exceptions\ErrorSavingException');
    $this->expectExceptionCode(Model::DUPLICATE_RECORD);
    $this->expectExceptionMessage('Another record already exists. Please specify a different "PRIMARY".');
    $this->saver->save($dynamicSegment2);
  }

  public function testItSavesFilters() {
    $dynamicSegment = DynamicSegment::create();
    $dynamicSegment->hydrate([
      'name' => 'segment 1',
      'description' => 'desc',
    ]);
    $dynamicSegment->setFilters([new UserRole('editor', 'and')]);
    $id = $this->saver->save($dynamicSegment);
    $loaded = DynamicSegmentFilter::select('*')->where('segment_id', $id)->findOne();
    expect($loaded)->isInstanceOf('\MailPoet\Models\DynamicSegmentFilter');
  }
}
