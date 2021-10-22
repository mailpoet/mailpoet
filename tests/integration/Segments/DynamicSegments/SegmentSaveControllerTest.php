<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;

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
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => 'editor',
        'action' => UserRole::TYPE,
      ]],
    ];

    $segment = $this->saveController->save($segmentData);
    expect($segment->getName())->equals('Test Segment');
    expect($segment->getDescription())->equals('Description');
    expect($segment->getDynamicFilters()->count())->equals(1);
    expect($segment->getType())->equals(SegmentEntity::TYPE_DYNAMIC);
    $filter = $segment->getDynamicFilters()->first();
    assert($filter instanceof DynamicSegmentFilterEntity);
    expect($filter->getFilterData()->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    expect($filter->getFilterData()->getAction())->equals(UserRole::TYPE);
    expect($filter->getFilterData()->getData())->equals([
      'wordpressRole' => 'editor',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItCanSaveASegmentWithTwoFilters() {
    $segmentData = [
      'name' => 'Test Segment',
      'description' => 'Description',
      'filters_connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
      'filters' => [
        [
          'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
          'wordpressRole' => 'administrator',
          'action' => UserRole::TYPE,
        ],
        [
          'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
          'wordpressRole' => 'editor',
          'action' => UserRole::TYPE,
        ],
      ],
    ];

    $segment = $this->saveController->save($segmentData);
    expect($segment->getName())->equals('Test Segment');
    expect($segment->getDescription())->equals('Description');
    expect($segment->getDynamicFilters()->count())->equals(2);
    expect($segment->getType())->equals(SegmentEntity::TYPE_DYNAMIC);
    $filter = $segment->getDynamicFilters()->first();
    assert($filter instanceof DynamicSegmentFilterEntity);
    expect($filter->getFilterData()->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    expect($filter->getFilterData()->getAction())->equals(UserRole::TYPE);
    expect($filter->getFilterData()->getData())->equals([
      'wordpressRole' => 'administrator',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
    ]);
    $filter = $segment->getDynamicFilters()->next();
    assert($filter instanceof DynamicSegmentFilterEntity);
    expect($filter->getFilterData()->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    expect($filter->getFilterData()->getAction())->equals(UserRole::TYPE);
    expect($filter->getFilterData()->getData())->equals([
      'wordpressRole' => 'editor',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
    ]);
  }

  public function testItCanRemoveRedundantFilter() {
    $segment = $this->createSegment('Test Segment');
    $this->addDynamicFilter($segment, 'editor');
    $this->addDynamicFilter($segment, 'administrator');
    $segmentData = [
      'id' => $segment->getId(),
      'name' => 'Test Segment Edited',
      'description' => 'Description Edited',
      'filters_connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => 'subscriber',
        'action' => UserRole::TYPE,
        'connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
      ]],
    ];

    $segment = $this->saveController->save($segmentData);
    expect($segment->getName())->equals('Test Segment Edited');
    expect($segment->getDescription())->equals('Description Edited');
    expect($segment->getDynamicFilters()->count())->equals(1);
    expect($segment->getType())->equals(SegmentEntity::TYPE_DYNAMIC);
    $filter = $segment->getDynamicFilters()->first();
    assert($filter instanceof DynamicSegmentFilterEntity);
    expect($filter->getFilterData()->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    expect($filter->getFilterData()->getAction())->equals(UserRole::TYPE);
    expect($filter->getFilterData()->getData())->equals([
      'wordpressRole' => 'subscriber',
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
    ]);
  }

  public function testItCheckDuplicateSegment() {
    $name = 'Test name';
    $this->createSegment($name);
    $segmentData = [
      'name' => $name,
      'description' => 'Description',
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => 'editor',
        'action' => UserRole::TYPE,
      ]],
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
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => null,
        'action' => UserRole::TYPE,
      ]],
    ];
    $this->expectException(InvalidFilterException::class);
    $this->saveController->save($segmentData);
  }

  private function createSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function addDynamicFilter(SegmentEntity $segment, string $wordpressRole): DynamicSegmentFilterEntity {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, UserRole::TYPE, [
      'wordpressRole' => $wordpressRole,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($dynamicFilter);
    $this->entityManager->flush();
    return $dynamicFilter;
  }

  private function cleanup() {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }
}
