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

  /** @dataProvider siteDayCases */
  public function testItFiltersCreatedDateRangeBySiteLocalDay(string $timezone, $gmtOffset, string $day, array $inside, array $outside): void {
    $this->withSiteTimezone($timezone, $gmtOffset, function () use ($day, $inside, $outside) {
      foreach (array_merge($inside, $outside) as $createdAt) {
        $segment = $this->createSegment($createdAt, SegmentEntity::TYPE_DEFAULT);
        $segment->setCreatedAt(new \DateTimeImmutable($createdAt, new \DateTimeZone('UTC')));
      }
      $this->entityManager->flush();

      $segments = $this->repository->getData(
        $this->listingHandler->getListingDefinition(['filter' => ['created_from' => $day, 'created_to' => $day]])
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
