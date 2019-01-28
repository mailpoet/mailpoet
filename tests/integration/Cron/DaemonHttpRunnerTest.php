<?php
namespace MailPoet\Test\Cron;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Models\Setting;

class DaemonHttpRunnerTest extends \MailPoetTest {
  function testItConstructs() {
    Setting::setValue(
      CronHelper::DAEMON_SETTING,
      []
    );
    $daemon = new DaemonHttpRunner();
    expect(strlen($daemon->timer))->greaterOrEquals(5);
    expect(strlen($daemon->token))->greaterOrEquals(5);
  }

  function testItDoesNotRunWithoutRequestData() {
    $daemon = Stub::make(
      DaemonHttpRunner::class,
      [
        'abortWithError' => function($message) {
          return $message;
        }
      ]
    );
    expect($daemon->run(false))->equals('Invalid or missing request data.');
  }

  function testItDoesNotRunWhenThereIsInvalidOrMissingToken() {
    $daemon = Stub::make(
      DaemonHttpRunner::class,
      [
        'abortWithError' => function($message) {
          return $message;
        }
      ]
    );
    $daemon->settings_daemon_data = array(
      'token' => 123
    );
    expect($daemon->run(['token' => 456]))->equals('Invalid or missing token.');
  }

  function testItStoresErrorMessageAndContinuesExecutionWhenWorkersThrowException() {
    $data = array(
      'token' => 123
    );
    $daemon = Stub::make(
      Daemon::class,
      [
        'executeScheduleWorker' => function() {
          throw new \Exception('Message');
        },
        'executeQueueWorker' => function() {
          throw new \Exception();
        },
        'executeMigrationWorker' => null,
        'executeStatsNotificationsWorker' => null,
      ]
    );
    $daemon_http_runner = Stub::make(new DaemonHttpRunner($daemon), array(
      'pauseExecution' => null,
      'callSelf' => null
    ), $this);
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon_http_runner->__construct($daemon);
    $daemon_http_runner->run($data);
    $updated_daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($updated_daemon['last_error'])->greaterOrEquals('Message');
  }

  function testItCanPauseExecution() {
    $daemon = Stub::makeEmpty(Daemon::class);
    $daemon_http_runner = Stub::make(DaemonHttpRunner::class, array(
      'pauseExecution' => Expected::exactly(1, function($pause_delay) {
        expect($pause_delay)->lessThan(CronHelper::DAEMON_EXECUTION_LIMIT);
        expect($pause_delay)->greaterThan(CronHelper::DAEMON_EXECUTION_LIMIT - 1);
      }),
      'callSelf' => null,
      'terminateRequest' => null,
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon_http_runner->__construct($daemon);
    $daemon_http_runner->run($data);
  }


  function testItTerminatesExecutionWhenDaemonIsDeleted() {
    $daemon = Stub::make(DaemonHttpRunner::class, array(
      'executeScheduleWorker' => function() {
        Setting::deleteValue(CronHelper::DAEMON_SETTING);
      },
      'executeQueueWorker' => null,
      'pauseExecution' => null,
      'terminateRequest' => Expected::exactly(1)
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct(Stub::makeEmpty(Daemon::class));
    $daemon->run($data);
  }

  function testItTerminatesExecutionWhenDaemonTokenChangesAndKeepsChangedToken() {
    $daemon = Stub::make(DaemonHttpRunner::class, array(
      'executeScheduleWorker' => function() {
        Setting::setValue(
          CronHelper::DAEMON_SETTING,
          array('token' => 567)
        );
      },
      'executeQueueWorker' => null,
      'pauseExecution' => null,
      'terminateRequest' => Expected::exactly(1)
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct(Stub::makeEmpty(Daemon::class));
    $daemon->run($data);
    $data_after_run = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($data_after_run['token'], 567);
  }

  function testItTerminatesExecutionWhenDaemonIsDeactivated() {
    $daemon = Stub::make(DaemonHttpRunner::class, [
      'executeScheduleWorker' => null,
      'executeQueueWorker' => null,
      'pauseExecution' => null,
      'terminateRequest' => Expected::exactly(1)
    ], $this);
    $data = [
      'token' => 123,
      'status' => CronHelper::DAEMON_STATUS_INACTIVE,
    ];
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct(Stub::makeEmpty(Daemon::class));
    $daemon->run($data);
  }

  function testItUpdatesDaemonTokenDuringExecution() {
    $daemon_http_runner = Stub::make(DaemonHttpRunner::class, array(
      'executeScheduleWorker' => null,
      'executeQueueWorker' => null,
      'pauseExecution' => null,
      'callSelf' => null,
      'terminateRequest' => null,
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon_http_runner->__construct(Stub::makeEmptyExcept(Daemon::class, 'run'));
    $daemon_http_runner->run($data);
    $updated_daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($updated_daemon['token'])->equals($daemon_http_runner->token);
  }

  function testItUpdatesTimestampsDuringExecution() {
    $daemon = Stub::make(Daemon::class, [
        'executeScheduleWorker' => function() {
          sleep(2);
        },
        'executeQueueWorker' => function() {
          throw new \Exception();
        },
        'executeMigrationWorker' => null,
        'executeStatsNotificationsWorker' => null,
      ]
    );
    $daemon_http_runner = Stub::make(new DaemonHttpRunner($daemon), array(
      'pauseExecution' => null,
      'callSelf' => null
    ), $this);
    $data = array(
      'token' => 123,
    );
    $now = time();
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon_http_runner->__construct($daemon);
    $daemon_http_runner->run($data);
    $updated_daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($updated_daemon['run_started_at'])->greaterOrEquals($now);
    expect($updated_daemon['run_started_at'])->lessThan($now + 2);
    expect($updated_daemon['run_completed_at'])->greaterOrEquals($now + 2);
    expect($updated_daemon['run_completed_at'])->lessThan($now + 4);
  }

  function testItCanRun() {
    ignore_user_abort(0);
    expect(ignore_user_abort())->equals(0);
    $daemon = Stub::make(DaemonHttpRunner::class, array(
      'pauseExecution' => null,
      // workers should be executed
      'executeScheduleWorker' => Expected::exactly(1),
      'executeQueueWorker' => Expected::exactly(1),
      // daemon should call itself
      'callSelf' => Expected::exactly(1),
      'terminateRequest' => null,
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct(Stub::makeEmptyExcept(Daemon::class, 'run'));
    $daemon->run($data);
    expect(ignore_user_abort())->equals(1);
  }

  function testItRespondsToPingRequest() {
    $daemon = Stub::make(DaemonHttpRunner::class, array(
      'terminateRequest' => Expected::exactly(1, function($message) {
        expect($message)->equals('pong');
      })
    ), $this);
    $daemon->ping();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
