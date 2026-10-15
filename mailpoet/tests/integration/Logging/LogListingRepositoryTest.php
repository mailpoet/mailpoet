<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Entities\LogEntity;
use MailPoet\Listing\Handler;
use MailPoet\Test\DataFactories\Log;

class LogListingRepositoryTest extends \MailPoetTest {
  /** @var Handler */
  private $listingHandler;

  /** @var LogListingRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->listingHandler = new Handler();
    $this->repository = $this->diContainer->get(LogListingRepository::class);
  }

  /** @dataProvider siteDayCases */
  public function testItFiltersCreatedDateRangeBySiteLocalDay(string $timezone, $gmtOffset, string $day, array $inside, array $outside): void {
    $this->withSiteTimezone($timezone, $gmtOffset, function () use ($day, $inside, $outside) {
      foreach (array_merge($inside, $outside) as $createdAt) {
        (new Log())
          ->withMessage($createdAt)
          ->withCreatedAt(new \DateTimeImmutable($createdAt, new \DateTimeZone('UTC')))
          ->create();
      }

      $logs = $this->repository->getData(
        $this->listingHandler->getListingDefinition(['filter' => ['from' => $day, 'to' => $day]])
      );
      $messages = array_map(function (LogEntity $log): ?string {
        return $log->getMessage();
      }, $logs);
      sort($messages);
      verify($messages)->same($inside);
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
}
