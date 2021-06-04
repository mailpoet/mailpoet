<?php

namespace MailPoet\Logging;

use MailPoet\Entities\LogEntity;
use MailPoet\Models\Log;
use MailPoetVendor\Carbon\Carbon;

class LogHandlerTest extends \MailPoetTest {
  /** @var LogRepository */
  private $repository;

  public function _before() {
    $this->truncateEntity(LogEntity::class);
    $this->repository = $this->diContainer->get(LogRepository::class);
  }

  public function testItCreatesLog() {
    $logHandler = new LogHandler($this->repository);
    $time = new \DateTime();
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => $time,
    ]);

    $log = Log::where('name', 'name')->orderByDesc('id')->findOne();
    assert($log instanceof Log);
    expect($log->createdAt)->equals($time->format('Y-m-d H:i:s'));

  }

  public function testItPurgesOldLogs() {
    $model = Log::create();
    $model->hydrate([
      'name' => 'old name',
      'level' => '5',
      'message' => 'xyz',
      'created_at' => Carbon::now()->subDays(100)->toDateTimeString(),
    ]);
    $model->save();
    $random = function() {
      return 0;
    };

    $logHandler = new LogHandler($this->repository, \MailPoetVendor\Monolog\Logger::DEBUG, true, $random);
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
    ]);

    $log = Log::whereEqual('name', 'old name')->findMany();
    expect($log)->isEmpty();
  }

  public function testItNotPurgesOldLogs() {
    $model = Log::create();
    $date = Carbon::now();
    $model->hydrate([
      'name' => 'old name keep',
      'level' => '5',
      'message' => 'xyz',
      'created_at' => $date->subDays(100)->toDateTimeString(),
    ]);
    $model->save();
    $random = function() {
      return 100;
    };

    $logHandler = new LogHandler($this->repository, \MailPoetVendor\Monolog\Logger::DEBUG, true, $random);
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
    ]);

    $log = Log::whereEqual('name', 'old name keep')->findMany();
    expect($log)->notEmpty();
  }
}
