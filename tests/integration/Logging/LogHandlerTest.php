<?php

namespace MailPoet\Logging;

use MailPoet\Models\Log;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class LogHandlerTest extends \MailPoetTest {

  public function testItCreatesLog() {
    $log_handler = new LogHandler();
    $time = new \DateTime();
    $log_handler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => $time,
    ]);

    $log = Log::where('name', 'name')->orderByDesc('id')->findOne();
    expect($log->created_at)->equals($time->format('Y-m-d H:i:s'));

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

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Log::$_table);
  }

}
