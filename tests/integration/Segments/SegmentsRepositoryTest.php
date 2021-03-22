<?php

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;

class SegmentsRepositoryTest extends \MailPoetTest {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function testItCanBulkTrashDefaultSegments() {
    $segment1 = $this->createDefaultSegment('Segment 1');
    $segment2 = $this->createDefaultSegment('Segment 2');
    $this->entityManager->flush();
    $result = $this->segmentsRepository->bulkTrash([$segment1->getId(), $segment2->getId()]);
    $this->entityManager->refresh($segment1);
    $this->entityManager->refresh($segment2);
    expect($result)->equals(2);
    expect($segment1->getDeletedAt())->isInstanceOf(\DateTimeInterface::class);
    expect($segment2->getDeletedAt())->isInstanceOf(\DateTimeInterface::class);
  }

  public function testItCanBulkTrashDynamicSegments() {
    $segment1 = $this->createDynamicSegmentEntityForEditorUsers();
    $segment2 = $this->createDynamicSegmentEntityForEditorUsers();
    $this->entityManager->flush();
    $result = $this->segmentsRepository->bulkTrash([$segment1->getId(), $segment2->getId()], SegmentEntity::TYPE_DYNAMIC);
    $this->entityManager->refresh($segment1);
    $this->entityManager->refresh($segment2);
    expect($result)->equals(2);
    expect($segment1->getDeletedAt())->isInstanceOf(\DateTimeInterface::class);
    expect($segment2->getDeletedAt())->isInstanceOf(\DateTimeInterface::class);
  }

  private function createDefaultSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'description');
    $this->entityManager->persist($segment);
    return $segment;
  }

  private function createDynamicSegmentEntityForEditorUsers(): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, new DynamicSegmentFilterData([
      'wordpressRole' => 'editor',
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
    ]));
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }

  private function cleanup() {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }
}
