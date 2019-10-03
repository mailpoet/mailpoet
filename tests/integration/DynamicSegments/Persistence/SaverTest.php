<?php

namespace MailPoet\DynamicSegments\Persistence;

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoet\Models\Model;

class SaverTest extends \MailPoetTest {

  /** @var  Saver */
  private $saver;

  function _before() {
    $this->saver = new Saver();
    \ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . DynamicSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . DynamicSegmentFilter::$_table);
  }

  function testItSavesSegment() {
    $dynamic_segment = DynamicSegment::create();
    $dynamic_segment->hydrate([
      'name' => 'segment 1',
      'description' => 'desc',
    ]);
    $id = $this->saver->save($dynamic_segment);
    $loaded = DynamicSegment::findOne($id);
    expect($loaded->name)->equals('segment 1');
    expect($loaded->description)->equals('desc');
  }

  function testItThrowsOnDuplicateSegment() {
    $dynamic_segment1 = DynamicSegment::createOrUpdate([
      'name' => 'segment 1',
      'description' => 'description',
    ]);
    $dynamic_segment2 = DynamicSegment::create();
    $dynamic_segment2->hydrate([
      'name' => 'segment 2',
      'description' => 'desc2',
      'id' => $dynamic_segment1->id,
    ]);
    $this->setExpectedException('\MailPoet\DynamicSegments\Exceptions\ErrorSavingException', 'Another record already exists. Please specify a different "PRIMARY".', Model::DUPLICATE_RECORD);
    $this->saver->save($dynamic_segment2);
  }

  function testItSavesFilters() {
    $dynamic_segment = DynamicSegment::create();
    $dynamic_segment->hydrate([
      'name' => 'segment 1',
      'description' => 'desc',
    ]);
    $dynamic_segment->setFilters([new UserRole('editor', 'and')]);
    $id = $this->saver->save($dynamic_segment);
    $loaded = DynamicSegmentFilter::select('*')->where('segment_id', $id)->findOne();
    expect($loaded)->isInstanceOf('\MailPoet\Models\DynamicSegmentFilter');
  }

}
