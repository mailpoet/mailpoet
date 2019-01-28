<?php
namespace MailPoet\Test\Cron;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Models\Setting;

class DaemonTest extends \MailPoetTest {

  function testItCanExecuteWorkers() {
    $daemon = Stub::make(Daemon::class, array(
      'executeScheduleWorker' => Expected::exactly(1),
      'executeQueueWorker' => Expected::exactly(1),
      'executeMigrationWorker' => null,
      'executeStatsNotificationsWorker' => null,
      'executeSendingServiceKeyCheckWorker' => null,
      'executePremiumKeyCheckWorker' => null,
      'executeBounceWorker' => null,
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->run([]);
  }

  function testItCanRun() {
    $daemon = Stub::make(Daemon::class, array(
      // workers should be executed
      'executeScheduleWorker' => Expected::exactly(1),
      'executeQueueWorker' => Expected::exactly(1),
      'executeMigrationWorker' => Expected::exactly(1),
      'executeStatsNotificationsWorker' => Expected::exactly(1),
      'executeSendingServiceKeyCheckWorker' => Expected::exactly(1),
      'executePremiumKeyCheckWorker' => Expected::exactly(1),
      'executeBounceWorker' => Expected::exactly(1)
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->run($data);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
