<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Test\DataFactories\Log;
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
}
