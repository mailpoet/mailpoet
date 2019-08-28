<?php

namespace MailPoet\Logging;

use AspectMock\Test as Mock;
use Carbon\Carbon;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Models\Log;

class LogHandlerTest extends \MailPoetTest {

  public function testItCreatesLog() {
    $log_model = Stub::makeEmpty(Log::class, [
      'save' => Expected::once(),
      'hydrate' => Expected::once(),
    ], $this);

    $log_handler = new LogHandler();

    $log_handler = Mock::double($log_handler, [
      'createNewLogModel' => function () use ($log_model) {
        return $log_model;
      },
    ]);

    $log_handler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
    ]);

  }

  public function testItPurgesOldLogs() {
    $model = Log::create();
    $model->hydrate([
      'name' => 'old name',
      'level' => '5',
      'message' => 'xyz',
      'created_at' => Carbon::create()->subDays(100)->toDateTimeString(),
    ]);
    $model->save();
    $random = function() {
      return 0;
    };

    $log_handler = new LogHandler(\MailPoetVendor\Monolog\Logger::DEBUG, true, $random);
    $log_handler->handle([
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
    $model->hydrate([
      'name' => 'old name keep',
      'level' => '5',
      'message' => 'xyz',
      'created_at' => Carbon::create()->subDays(100)->toDateTimeString(),
    ]);
    $model->save();
    $random = function() {
      return 100;
    };

    $log_handler = new LogHandler(\MailPoetVendor\Monolog\Logger::DEBUG, true, $random);
    $log_handler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
    ]);

    $log = Log::whereEqual('name', 'old name keep')->findMany();
    expect($log)->notEmpty();
  }

  function _after() {
    Mock::clean();
    \ORM::raw_execute('TRUNCATE ' . Log::$_table);
  }

}
