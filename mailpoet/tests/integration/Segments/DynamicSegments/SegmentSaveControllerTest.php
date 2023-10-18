<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\ConflictException;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;

class SegmentSaveControllerTest extends \MailPoetTest {
  /** @var SegmentSaveController */
  private $saveController;

  public function _before(): void {
    parent::_before();
    $this->saveController = $this->diContainer->get(SegmentSaveController::class);
  }

  public function testItCanSaveASegment(): void {
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
    verify($segment->getName())->equals('Test Segment');
    verify($segment->getDescription())->equals('Description');
    verify($segment->getDynamicFilters()->count())->equals(1);
    verify($segment->getType())->equals(SegmentEntity::TYPE_DYNAMIC);
    $filter = $segment->getDynamicFilters()->first();
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    verify($filter->getFilterData()->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getFilterData()->getAction())->equals(UserRole::TYPE);
    verify($filter->getFilterData()->getData())->equals([
      'wordpressRole' => 'editor',
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]);
  }

  public function testItCanRemoveRedundantFilter(): void {
    $segment = $this->createSegment('Test Segment');
    $this->addDynamicFilter($segment, ['editor']);
    $this->addDynamicFilter($segment, ['administrator']);
    $segmentData = [
      'id' => $segment->getId(),
      'name' => 'Test Segment Edited',
      'description' => 'Description Edited',
      'filters_connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => ['subscriber'],
        'action' => UserRole::TYPE,
        'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
        'connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
      ]],
    ];

    $segment = $this->saveController->save($segmentData);
    verify($segment->getName())->equals('Test Segment Edited');
    verify($segment->getDescription())->equals('Description Edited');
    verify($segment->getDynamicFilters()->count())->equals(1);
    verify($segment->getType())->equals(SegmentEntity::TYPE_DYNAMIC);
    $filter = $segment->getDynamicFilters()->first();
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    verify($filter->getFilterData()->getFilterType())->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($filter->getFilterData()->getAction())->equals(UserRole::TYPE);
    verify($filter->getFilterData()->getData())->equals([
      'wordpressRole' => ['subscriber'],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_OR,
    ]);
  }

  public function testItCheckDuplicateSegment(): void {
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
    $this->expectException(ConflictException::class);
    $this->expectExceptionMessage("Could not create new segment with name [Test name] because a segment with that name already exists.");
    $this->saveController->save($segmentData);
  }

  public function testItValidatesSegmentFilterData(): void {
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

  public function testItCanDuplicateExistingSegment(): void {
    $segment = $this->createSegment('original');
    $this->addDynamicFilter($segment, ['administrator']);
    $this->addDynamicFilter($segment, ['editor']);

    $duplicate = $this->saveController->duplicate($segment);
    verify($duplicate->getId())->notEquals($segment->getId());
    $filters = $duplicate->getDynamicFilters();
    verify($filters)->arrayCount(2);

    $originalFilter1 = $segment->getDynamicFilters()->get(0);
    $duplicateFilter1 = $duplicate->getDynamicFilters()->get(0);

    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $originalFilter1);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $duplicateFilter1);

    verify($originalFilter1->getId())->notEquals($duplicateFilter1->getId());
    verify($duplicateFilter1->getFilterData()->getAction())->equals(UserRole::TYPE);
    verify($duplicateFilter1->getFilterData()->getParam('wordpressRole'))->equals(['administrator']);
    verify($duplicateFilter1->getFilterData()->getParam('connect'))->equals(DynamicSegmentFilterData::CONNECT_TYPE_AND);

    $originalFilter2 = $segment->getDynamicFilters()->get(1);
    $duplicateFilter2 = $duplicate->getDynamicFilters()->get(1);

    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $originalFilter2);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $duplicateFilter2);

    verify($originalFilter2->getId())->notEquals($duplicateFilter2->getId());
    verify($duplicateFilter2->getFilterData()->getAction())->equals(UserRole::TYPE);
    verify($duplicateFilter2->getFilterData()->getParam('wordpressRole'))->equals(['editor']);
    verify($duplicateFilter2->getFilterData()->getParam('connect'))->equals(DynamicSegmentFilterData::CONNECT_TYPE_AND);
  }

  private function createSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function addDynamicFilter(SegmentEntity $segment, array $wordpressRole): DynamicSegmentFilterEntity {
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
}
