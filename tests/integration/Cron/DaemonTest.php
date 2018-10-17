<?php
namespace MailPoet\Test\Cron;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Cron\Daemon;
use MailPoet\Models\Setting;

class DaemonTest extends \MailPoetTest {

  function testItCanExecuteWorkers() {
    $daemon = Stub::make(new Daemon(), array(
      'executeScheduleWorker' => Expected::exactly(1),
      'executeQueueWorker' => Expected::exactly(1),
      'pauseExecution' => null,
      'callSelf' => null
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run([]);
  }

  function testItCanRun() {
    $daemon = Stub::make(new Daemon(), array(
      'pauseExecution' => null,
      // workers should be executed
      'executeScheduleWorker' => Expected::exactly(1),
      'executeQueueWorker' => Expected::exactly(1),
      // daemon should call itself
      'callSelf' => Expected::exactly(1),
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct();
    $daemon->run($data);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
