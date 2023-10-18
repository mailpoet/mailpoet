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
}
