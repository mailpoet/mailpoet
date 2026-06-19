<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\Handler;

class SegmentListingRepositoryTest extends \MailPoetTest {
  /** @var Handler */
  private $listingHandler;

  /** @var SegmentListingRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->listingHandler = new Handler();
    $this->repository = $this->diContainer->get(SegmentListingRepository::class);
  }

  public function testItFiltersByCreatedDateRange(): void {
    $older = $this->createSegment('Older', SegmentEntity::TYPE_DEFAULT);
    $newer = $this->createSegment('Newer', SegmentEntity::TYPE_DEFAULT);
    $older->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
    $newer->setCreatedAt(new \DateTimeImmutable('2020-06-01 10:00:00'));
    $this->entityManager->flush();

    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['created_from' => '2020-03-01']])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Newer');

    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['created_to' => '2020-03-01']])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Older');

    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['created_from' => '2020-01-01', 'created_to' => '2020-06-01']])
    );
    verify($segments)->arrayCount(2);
  }

  public function testItFiltersByEngagementScore(): void {
    $low = $this->createSegment('Low', SegmentEntity::TYPE_DEFAULT);
    $high = $this->createSegment('High', SegmentEntity::TYPE_DEFAULT);
    $low->setAverageEngagementScore(10.0);
    $high->setAverageEngagementScore(80.0);
    $this->entityManager->flush();

    // score_min keeps only the high one
    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['score_min' => 50]])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('High');

    // score_max keeps only the low one
    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['score_max' => 50]])
    );
    verify($segments)->arrayCount(1);
    verify($segments[0]->getName())->same('Low');

    // bounded range keeps both
    $segments = $this->repository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['score_min' => 5, 'score_max' => 90]])
    );
    verify($segments)->arrayCount(2);
  }

  public function testItSortsByCreatedAt(): void {
    $first = $this->createSegment('First', SegmentEntity::TYPE_DEFAULT);
    $second = $this->createSegment('Second', SegmentEntity::TYPE_DEFAULT);
    $first->setCreatedAt(new \DateTimeImmutable('2020-06-01 10:00:00'));
    $second->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
    $this->entityManager->flush();

    $segments = $this->repository->getData($this->listingHandler->getListingDefinition([
      'sort_by' => 'created_at',
      'sort_order' => 'asc',
    ]));
    verify($segments[0]->getName())->same('Second');
    verify($segments[1]->getName())->same('First');
  }

  private function createSegment(string $name, string $type): SegmentEntity {
    $segment = new SegmentEntity($name, $type, '');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }
}
