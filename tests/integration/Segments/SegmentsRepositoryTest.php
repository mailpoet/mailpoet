<?php

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;

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

  public function testItSkipTrashingForActivelyUsedDefaultSegments() {
    $segment1 = $this->createDefaultSegment('Segment 1');
    $segment2 = $this->createDefaultSegment('Segment 2');
    $this->addActiveNewsletterToSegment($segment1);
    $this->entityManager->flush();
    $result = $this->segmentsRepository->bulkTrash([$segment1->getId(), $segment2->getId()]);
    $this->entityManager->refresh($segment1);
    $this->entityManager->refresh($segment2);
    expect($result)->equals(1);
    expect($segment1->getDeletedAt())->null();
    expect($segment2->getDeletedAt())->isInstanceOf(\DateTimeInterface::class);
  }

  public function testItSkipTrashingForActivelyUsedDynamicSegments() {
    $segment1 = $this->createDynamicSegmentEntityForEditorUsers();
    $segment2 = $this->createDynamicSegmentEntityForEditorUsers();
    $this->addActiveNewsletterToSegment($segment2);
    $this->entityManager->flush();
    $result = $this->segmentsRepository->bulkTrash([$segment1->getId(), $segment2->getId()], SegmentEntity::TYPE_DYNAMIC);
    $this->entityManager->refresh($segment1);
    $this->entityManager->refresh($segment2);
    expect($result)->equals(1);
    expect($segment1->getDeletedAt())->isInstanceOf(\DateTimeInterface::class);
    expect($segment2->getDeletedAt())->null();
  }

  public function testItReturnsCountsOfSegmentsWithMultipleFilters() {
    // No Segments
    $count = $this->segmentsRepository->getSegmentCountWithMultipleFilters();
    expect($count)->equals(0);

    // Two segments with one filter each
    $segment1 = $this->createDynamicSegmentEntityForEditorUsers();
    $segment2 = $this->createDynamicSegmentEntityForEditorUsers();
    $count = $this->segmentsRepository->getSegmentCountWithMultipleFilters();
    expect($count)->equals(0);

    // One segment with multiple filters
    $filterData = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $dynamicFilter = new DynamicSegmentFilterEntity($segment1, $filterData);
    $this->entityManager->persist($dynamicFilter);
    $segment1->addDynamicFilter($dynamicFilter);
    $this->segmentsRepository->flush();
    $count = $this->segmentsRepository->getSegmentCountWithMultipleFilters();
    expect($count)->equals(1);

    // Both segments with multiple filters
    $filterData = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $dynamicFilter = new DynamicSegmentFilterEntity($segment2, $filterData);
    $this->entityManager->persist($dynamicFilter);
    $segment1->addDynamicFilter($dynamicFilter);
    $this->segmentsRepository->flush();
    $count = $this->segmentsRepository->getSegmentCountWithMultipleFilters();
    expect($count)->equals(2);
  }

  private function createDefaultSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'description');
    $this->entityManager->persist($segment);
    return $segment;
  }

  private function createDynamicSegmentEntityForEditorUsers(): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $filterData = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }

  private function addActiveNewsletterToSegment(SegmentEntity $segmentEntity) {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segmentEntity);
    $this->entityManager->persist($newsletter);
    $this->entityManager->persist($newsletterSegment);
  }

  private function cleanup() {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterSegmentEntity::class);
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }
}
