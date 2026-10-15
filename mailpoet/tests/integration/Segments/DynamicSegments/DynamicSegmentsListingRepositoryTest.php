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

  /** @dataProvider siteDayCases */
  public function testItFiltersCreatedDateRangeBySiteLocalDay(string $timezone, $gmtOffset, string $day, array $inside, array $outside): void {
    $this->withSiteTimezone($timezone, $gmtOffset, function () use ($day, $inside, $outside) {
      foreach (array_merge($inside, $outside) as $createdAt) {
        $segment = $this->createSegment($createdAt, SegmentEntity::TYPE_DYNAMIC);
        $segment->setCreatedAt(new \DateTimeImmutable($createdAt, new \DateTimeZone('UTC')));
      }
      $this->entityManager->flush();

      $segments = $this->repository->getData(
        $this->listingHandler->getListingDefinition(['params' => ['segments'], 'filter' => ['created_from' => $day, 'created_to' => $day]])
      );
      $names = array_map(function (SegmentEntity $segment): string {
        return $segment->getName();
      }, $segments);
      sort($names);
      verify($names)->same($inside);
    });
  }

  /**
   * Site timezone, options to set, filter day, and UTC created-at values grouped by whether the
   * row belongs to that site-local day. Rows sit one second either side of the UTC day boundaries.
   *
   * @return array<string, array{string, string|float, string, string[], string[]}>
   */
  public function siteDayCases(): array {
    return [
      'utc site' => ['', 0, '2020-03-10', ['2020-03-10 00:00:00', '2020-03-10 23:59:59'], ['2020-03-09 23:59:59', '2020-03-11 00:00:00']],
      'dst zone, standard time' => ['Europe/Prague', 0, '2020-03-10', ['2020-03-09 23:00:00', '2020-03-10 22:59:59'], ['2020-03-09 22:59:59', '2020-03-10 23:00:00']],
      'dst zone, 23-hour day' => ['America/New_York', 0, '2020-03-08', ['2020-03-08 05:00:00', '2020-03-09 03:59:59'], ['2020-03-08 04:59:59', '2020-03-09 04:00:00']],
      'fixed gmt offset' => ['', 5.5, '2020-03-10', ['2020-03-09 18:30:00', '2020-03-10 18:29:59'], ['2020-03-09 18:29:59', '2020-03-10 18:30:00']],
    ];
  }

  private function withSiteTimezone(string $timezoneString, $gmtOffset, callable $callback): void {
    $previous = [
      'timezone_string' => get_option('timezone_string'),
      'gmt_offset' => get_option('gmt_offset'),
    ];
    update_option('timezone_string', $timezoneString);
    update_option('gmt_offset', $gmtOffset);
    try {
      $callback();
    } finally {
      foreach ($previous as $option => $value) {
        if ($value === false) {
          delete_option($option);
        } else {
          update_option($option, $value);
        }
      }
    }
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
