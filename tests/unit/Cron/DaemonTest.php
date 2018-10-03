<?php
namespace MailPoet\Test\Cron;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Models\Setting;

class DaemonHttpRunnerTest extends \MailPoetTest {
  function testItConstructs() {
    Setting::setValue(
      CronHelper::DAEMON_SETTING,
      'daemon object'
    );
    $daemon = new DaemonHttpRunner($request_data = 'request data');
    expect($daemon->daemon)->equals('daemon object');
    expect($daemon->request_data)->equals('request data');
    expect(strlen($daemon->timer))->greaterOrEquals(5);
    expect(strlen($daemon->token))->greaterOrEquals(5);
  }

  function testItDoesNotRunWithoutRequestData() {
    $daemon = Stub::construct(
      new DaemonHttpRunner(),
      array(),
      array(
        'abortWithError' => function($message) {
          return $message;
        }
      )
    );
    $daemon->request_data = false;
    expect($daemon->run())->equals('Invalid or missing request data.');
  }

  function testItDoesNotRunWhenDaemonIsNotFound() {
    $daemon = Stub::construct(
      new DaemonHttpRunner(),
      array(),
      array(
        'abortWithError' => function($message) {
          return $message;
        }
      )
    );
    $daemon->request_data = true;
    expect($daemon->run())->equals('Daemon does not exist.');
  }

  function testItDoesNotRunWhenThereIsInvalidOrMissingToken() {
    $daemon = Stub::construct(
      new DaemonHttpRunner(),
      array(),
      array(
        'abortWithError' => function($message) {
          return $message;
        }
      )
    );
    $daemon->daemon = array(
      'token' => 123
    );
    $daemon->request_data = array('token' => 456);
    expect($daemon->run())->equals('Invalid or missing token.');
  }

  function testItCanExecuteWorkers() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
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
    $daemon->run();
  }

  function testItStoresErrorMessageAndContinuesExecutionWhenWorkersThrowException() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
      'executeScheduleWorker' => function() {
        throw new \Exception('Message');
      },
      'executeQueueWorker' => function() {
        throw new \Exception();
      },
      'pauseExecution' => null,
      'callSelf' => null
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
    $updated_daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($updated_daemon['last_error'])->greaterOrEquals('Message');
  }

  function testItCanPauseExecution() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
      'executeScheduleWorker' => null,
      'executeQueueWorker' => null,
      'pauseExecution' => Expected::exactly(1, function($pause_delay) {
        expect($pause_delay)->lessThan(CronHelper::DAEMON_EXECUTION_LIMIT);
        expect($pause_delay)->greaterThan(CronHelper::DAEMON_EXECUTION_LIMIT - 1);
      }),
      'callSelf' => null
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
  }


  function testItTerminatesExecutionWhenDaemonIsDeleted() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
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
    $daemon->__construct($data);
    $daemon->run();
  }

  function testItTerminatesExecutionWhenDaemonTokenChangesAndKeepsChangedToken() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
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
    $daemon->__construct($data);
    $daemon->run();
    $data_after_run = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($data_after_run['token'], 567);
  }

  function testItTerminatesExecutionWhenDaemonIsDeactivated() {
    $daemon = Stub::make(new DaemonHttpRunner(true), [
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
    $daemon->__construct($data);
    $daemon->run();
  }

  function testItUpdatesDaemonTokenDuringExecution() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
      'executeScheduleWorker' => null,
      'executeQueueWorker' => null,
      'pauseExecution' => null,
      'callSelf' => null
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
    $updated_daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($updated_daemon['token'])->equals($daemon->token);
  }

  function testItUpdatesTimestampsDuringExecution() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
      'executeScheduleWorker' => function() {
        sleep(2);
      },
      'executeQueueWorker' => null,
      'pauseExecution' => null,
      'callSelf' => null
    ), $this);
    $data = array(
      'token' => 123,
    );
    $now = time();
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
    $updated_daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($updated_daemon['run_started_at'])->greaterOrEquals($now);
    expect($updated_daemon['run_started_at'])->lessThan($now + 2);
    expect($updated_daemon['run_completed_at'])->greaterOrEquals($now + 2);
    expect($updated_daemon['run_completed_at'])->lessThan($now + 4);
  }

  function testItCanRun() {
    ignore_user_abort(0);
    expect(ignore_user_abort())->equals(0);
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
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
    $daemon->__construct($data);
    $daemon->run();
    expect(ignore_user_abort())->equals(1);
  }

  function testItRespondsToPingRequest() {
    $daemon = Stub::make(new DaemonHttpRunner(true), array(
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
