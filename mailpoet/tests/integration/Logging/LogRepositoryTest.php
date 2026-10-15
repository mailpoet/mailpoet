<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Test\DataFactories\Log;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoetVendor\Carbon\Carbon;

class LogRepositoryTest extends \MailPoetTest {
  /** @var LogRepository */
  private $repository;

  public function _before() {
    $this->repository = $this->diContainer->get(LogRepository::class);
  }

  public function testDeletesOldLogs() {
    $logFactory = new Log();
    $logFactory->withCreatedAt(Carbon::now()->subDays(50))->create(); // Oldest one to delete
    $log2 = $logFactory->withCreatedAt(Carbon::now()->subDays(40))->create(); // Old enough to delete but not the oldest one
    $log3 = $logFactory->withCreatedAt(Carbon::now()->subDays(20))->create(); // Not old enough
    $log4 = $logFactory->withCreatedAt(Carbon::now())->create(); // New

    // Delete 1 log older than 30 days
    $this->repository->purgeOldLogs(30, 1);

    $allLogs = $this->repository->getLogs();
    $logsInDB = [];
    foreach ($allLogs as $log) {
      $logsInDB[] = $log->getId();
    }
    sort($logsInDB);
    verify([$log2->getId(), $log3->getId(), $log4->getId()])->equals($logsInDB);
  }

  public function testDeleteLogsRemovesEverythingInBatches() {
    $logFactory = new Log();
    for ($i = 0; $i < 5; $i++) {
      $logFactory->create();
    }

    // Batch size below the row count forces the loop to run multiple times.
    $deleted = $this->repository->deleteLogs([], null, 2);

    verify($deleted)->equals(5);
    verify($this->repository->getLogs())->arrayCount(0);
  }

  public function testDeleteLogsAppliesFilterAndDateBoundaries() {
    $logFactory = new Log();
    $match = $logFactory
      ->withName('api')
      ->withLevel(400)
      ->withCreatedAt(new Carbon('2025-04-10 23:30:00'))
      ->create();
    $keepLevel = $logFactory
      ->withName('api')
      ->withLevel(100)
      ->withCreatedAt(new Carbon('2025-04-10 12:00:00'))
      ->create();
    $keepDate = $logFactory
      ->withName('api')
      ->withLevel(400)
      ->withCreatedAt(new Carbon('2025-04-11 00:00:01'))
      ->create();

    $deleted = $this->repository->deleteLogs([
      'from' => '2025-04-10',
      'to' => '2025-04-10',
      'name' => ['api'],
      'level' => [400],
    ]);

    verify($deleted)->equals(1);
    $remaining = array_map(function ($log) {
      return $log->getId();
    }, $this->repository->getLogs());
    sort($remaining);
    verify([$keepLevel->getId(), $keepDate->getId()])->equals($remaining);
    verify(in_array($match->getId(), $remaining, true))->false();
  }

  public function testDeleteLogsTreatsSearchWildcardsLiterally() {
    $logFactory = new Log();
    $literal = $logFactory->withMessage('value a%b literal')->create();
    $other = $logFactory->withMessage('value axxb other')->create();

    $deleted = $this->repository->deleteLogs([], '%');

    verify($deleted)->equals(1);
    $remaining = array_map(function ($log) {
      return $log->getId();
    }, $this->repository->getLogs());
    verify($remaining)->equals([$other->getId()]);
    verify(in_array($literal->getId(), $remaining, true))->false();
  }

  public function testGetLogsForExportStreamsAllRowsAcrossBatches() {
    $logFactory = new Log();
    $sameTime = Carbon::now()->subMinutes(5);
    $logFactory->withName('a')->withMessage('m1')->withCreatedAt(Carbon::now()->subMinutes(1))->create();
    $logFactory->withName('b')->withMessage('m2')->withCreatedAt(Carbon::now()->subMinutes(2))->create();
    // Two rows share a created_at so the (created_at, id) keyset tiebreak is exercised.
    $logFactory->withName('c')->withMessage('m3')->withCreatedAt($sameTime)->create();
    $logFactory->withName('d')->withMessage('m4')->withCreatedAt($sameTime)->create();
    $logFactory->withName('e')->withMessage('m5')->withCreatedAt(Carbon::now()->subMinutes(9))->create();

    // A batch size of 2 forces multiple pages and the keyset cursor.
    $rows = [];
    foreach ($this->repository->getLogsForExport([], null, 50000, 2) as $row) {
      $rows[] = $row;
    }

    // Every row is returned exactly once (no gaps or duplicates across batches).
    verify(count($rows))->equals(5);
    $messages = array_map(function ($row) {
      return $row['message'];
    }, $rows);
    sort($messages);
    verify($messages)->equals(['m1', 'm2', 'm3', 'm4', 'm5']);

    // Rows stay in newest-first order across batch boundaries.
    $timestamps = array_map(function ($row) {
      return $row['created_at'];
    }, $rows);
    $sorted = $timestamps;
    rsort($sorted);
    verify($timestamps)->equals($sorted);
  }

  public function testGetLogsForExportRespectsLimit() {
    $logFactory = new Log();
    for ($i = 0; $i < 5; $i++) {
      $logFactory->withMessage('m' . $i)->withCreatedAt(Carbon::now()->subMinutes($i))->create();
    }

    $rows = [];
    foreach ($this->repository->getLogsForExport([], null, 3, 2) as $row) {
      $rows[] = $row;
    }

    verify(count($rows))->equals(3);
  }

  /** @dataProvider siteDayCases */
  public function testExportAndDeleteUseSiteLocalDays(string $timezone, $gmtOffset, string $day, array $inside, array $outside): void {
    $this->withSiteTimezone($timezone, $gmtOffset, function () use ($day, $inside, $outside) {
      $createLogs = function () use ($inside, $outside) {
        foreach (array_merge($inside, $outside) as $createdAt) {
          (new Log())
            ->withMessage($createdAt)
            ->withCreatedAt(new \DateTimeImmutable($createdAt, new \DateTimeZone('UTC')))
            ->create();
        }
      };
      $remainingMessages = function (): array {
        $messages = array_map(function ($log) {
          return $log->getMessage();
        }, $this->repository->getLogs());
        sort($messages);
        return $messages;
      };
      $filter = ['from' => $day, 'to' => $day];

      $createLogs();
      $exported = [];
      foreach ($this->repository->getLogsForExport($filter) as $row) {
        $exported[] = $row['message'];
      }
      sort($exported);
      verify($exported)->same($inside);

      verify($this->repository->deleteLogs($filter))->same(count($inside));
      $expectedRemaining = $outside;
      sort($expectedRemaining);
      verify($remainingMessages())->same($expectedRemaining);
    });
  }

  /** @dataProvider invalidDayFilters */
  public function testInvalidDayBoundMatchesNothingForExportAndDelete(array $filter): void {
    (new Log())->withMessage('keep_me')->withCreatedAt(new \DateTimeImmutable('2024-02-01 10:00:00', new \DateTimeZone('UTC')))->create();

    $exported = [];
    foreach ($this->repository->getLogsForExport($filter) as $row) {
      $exported[] = $row['message'];
    }
    verify($exported)->same([]);
    verify($this->repository->deleteLogs($filter))->same(0);
    verify(count($this->repository->getLogs()))->same(1);
  }

  /** @return array<string, array{array<string, string>}> */
  public function invalidDayFilters(): array {
    return [
      'invalid to' => [['to' => '2024-13-01']],
      'invalid from' => [['from' => 'yesterday']],
      'zero from' => [['from' => '0']],
    ];
  }

  /**
   * Site timezone, options to set, filter day, and UTC created-at values grouped by whether the
   * row belongs to that site-local day. Rows sit one second either side of the UTC day boundaries.
   *
   * @return array<string, array{string, string|float|int, string, string[], string[]}>
   */
  public function siteDayCases(): array {
    return [
      'utc site' => ['', 0, '2020-03-10', ['2020-03-10 00:00:00', '2020-03-10 23:59:59'], ['2020-03-09 23:59:59', '2020-03-11 00:00:00']],
      'dst zone, standard time' => ['Europe/Prague', 0, '2020-03-10', ['2020-03-09 23:00:00', '2020-03-10 22:59:59'], ['2020-03-09 22:59:59', '2020-03-10 23:00:00']],
      'fixed gmt offset' => ['', 5.5, '2020-03-10', ['2020-03-09 18:30:00', '2020-03-10 18:29:59'], ['2020-03-09 18:29:59', '2020-03-10 18:30:00']],
    ];
  }

  /** @param string|float|int $gmtOffset */
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

  public function testGetRawMessagesForNewslettersGroupsDistinctMessagesByNewsletter(): void {
    $first = (new Newsletter())->withSubject('First')->create();
    $second = (new Newsletter())->withSubject('Second')->create();
    $third = (new Newsletter())->withSubject('Third')->create();
    $couponLog = (new Log())->withName(LoggerFactory::TOPIC_COUPONS);

    $couponLog->withRawMessage('later')->withContext(['newsletter_id' => $first->getId()])->withCreatedAt(Carbon::now())->create();
    $couponLog->withRawMessage('earlier')->withContext(['newsletter_id' => $first->getId()])->withCreatedAt(Carbon::now()->subMinute())->create();
    $couponLog->withRawMessage('earlier')->withContext(['newsletter_id' => $first->getId()])->withCreatedAt(Carbon::now()->subMinutes(2))->create();
    $couponLog->withRawMessage('second only')->withContext(['newsletter_id' => $second->getId()])->create();
    $couponLog->withRawMessage('unrelated topic')->withName('cron')->withContext(['newsletter_id' => $first->getId()])->create();
    $couponLog->withRawMessage('unrelated context')->withContext(['newsletter_id' => $first->getId(), 'task_id' => 1])->create();

    $messages = $this->repository->getRawMessagesForNewsletters([$first, $second, $third], LoggerFactory::TOPIC_COUPONS);

    verify($messages)->equals([
      $first->getId() => ['earlier', 'later'],
      $second->getId() => ['second only'],
    ]);
  }

  public function testGetRawMessagesForNewslettersReturnsEmptyArrayForNoNewsletters(): void {
    verify($this->repository->getRawMessagesForNewsletters([], LoggerFactory::TOPIC_COUPONS))->equals([]);
  }
}
