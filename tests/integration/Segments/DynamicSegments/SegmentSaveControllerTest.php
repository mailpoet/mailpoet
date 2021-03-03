<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;

class SegmentSaveControllerTest extends \MailPoetTest {
  /** @var SegmentSaveController */
  private $saveController;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->saveController = $this->diContainer->get(SegmentSaveController::class);
  }

  public function testItCanSaveASegment() {
    $segmentData = [
      'name' => 'Test Segment',
      'description' => 'Description',
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
    ];

    $segment = $this->saveController->save($segmentData);
    expect($segment->getName())->equals('Test Segment');
    expect($segment->getDescription())->equals('Description');
    expect($segment->getDynamicFilters()->count())->equals(1);
    expect($segment->getType())->equals(SegmentEntity::TYPE_DYNAMIC);
    $filter = $segment->getDynamicFilters()->first();
    assert($filter instanceof DynamicSegmentFilterEntity);
    expect($filter->getFilterData()->getData())->equals([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
    ]);
  }

  public function testItCheckDuplicateSegment() {
    $name = 'Test name';
    $this->createSegment($name);
    $segmentData = [
      'name' => $name,
      'description' => 'Description',
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
    ];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Segment with name: 'Test name' already exists.");
    $this->saveController->save($segmentData);
  }

  public function testItValidatesSegmentFilterData() {
    $name = 'Test name';
    $this->createSegment($name);
    $segmentData = [
      'name' => $name,
      'description' => 'Description',
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => null,
    ];
    $this->expectException(InvalidFilterException::class);
    $this->saveController->save($segmentData);
  }

  private function createSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function cleanup() {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }
}
