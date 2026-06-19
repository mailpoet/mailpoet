<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\Handler;

class DynamicSegmentsListingRepositoryTest extends \MailPoetTest {
  /** @var Handler */
  private $listingHandler;

  /** @var DynamicSegmentsListingRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->listingHandler = new Handler();
    $this->repository = $this->diContainer->get(DynamicSegmentsListingRepository::class);
  }

  public function testItReturnsOnlyDynamicSegments(): void {
    $this->createSegment('Dynamic', SegmentEntity::TYPE_DYNAMIC);
    $this->createSegment('List', SegmentEntity::TYPE_DEFAULT);

    $definition = $this->listingHandler->getListingDefinition(['params' => ['segments']]);
    $segments = $this->repository->getData($definition);
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Dynamic');
  }

  public function testItFiltersByCreatedDateRange(): void {
    $older = $this->createSegment('Older', SegmentEntity::TYPE_DYNAMIC);
    $newer = $this->createSegment('Newer', SegmentEntity::TYPE_DYNAMIC);
    $older->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
    $newer->setCreatedAt(new \DateTimeImmutable('2020-06-01 10:00:00'));
    $this->entityManager->flush();

    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['params' => ['segments'], 'filter' => ['created_from' => '2020-03-01']])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Newer');

    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['params' => ['segments'], 'filter' => ['created_to' => '2020-03-01']])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Older');
  }

  public function testItFiltersByModifiedDateRange(): void {
    $older = $this->createSegment('Older', SegmentEntity::TYPE_DYNAMIC);
    $newer = $this->createSegment('Newer', SegmentEntity::TYPE_DYNAMIC);
    // updatedAt is reset on every flush, so pin deterministic values with a DQL UPDATE.
    $this->setUpdatedAt($older, '2020-01-01 10:00:00');
    $this->setUpdatedAt($newer, '2020-06-01 10:00:00');

    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['params' => ['segments'], 'filter' => ['updated_from' => '2020-03-01']])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Newer');

    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['params' => ['segments'], 'filter' => ['updated_to' => '2020-03-01']])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Older');
  }

  public function testItFiltersByCreatedAndModifiedDateIndependently(): void {
    $segmentA = $this->createSegment('A', SegmentEntity::TYPE_DYNAMIC);
    $segmentB = $this->createSegment('B', SegmentEntity::TYPE_DYNAMIC);
    $segmentA->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
    $segmentB->setCreatedAt(new \DateTimeImmutable('2020-06-01 10:00:00'));
    $this->entityManager->flush();
    $this->setUpdatedAt($segmentA, '2020-12-01 10:00:00');
    $this->setUpdatedAt($segmentB, '2020-06-15 10:00:00');

    // Created before March AND modified after October → only A. Exercises both
    // ranges coexisting on one query without parameter collisions.
    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition([
        'params' => ['segments'],
        'filter' => ['created_to' => '2020-03-01', 'updated_from' => '2020-10-01'],
      ])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('A');
  }

  private function createSegment(string $name, string $type): SegmentEntity {
    $segment = new SegmentEntity($name, $type, '');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function setUpdatedAt(SegmentEntity $segment, string $date): void {
    $this->entityManager
      ->createQuery('UPDATE ' . SegmentEntity::class . ' s SET s.updatedAt = :date WHERE s.id = :id')
      ->setParameter('date', new \DateTimeImmutable($date))
      ->setParameter('id', $segment->getId())
      ->execute();
  }
}
