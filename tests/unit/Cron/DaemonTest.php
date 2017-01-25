<?php

use Codeception\Util\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Daemon;
use MailPoet\Models\Setting;

class DaemonTest extends MailPoetTest {
  function testItDefinesConstants() {
    expect(Daemon::REQUEST_TIMEOUT)->equals(5);
  }

  function testItConstructs() {
    Setting::setValue(
      CronHelper::DAEMON_SETTING,
      'daemon object'
    );
    $daemon = new Daemon($request_data = 'request data');
    expect($daemon->daemon)->equals('daemon object');
    expect($daemon->request_data)->equals('request data');
    expect(strlen($daemon->timer))->greaterOrEquals(5);
    expect(strlen($daemon->token))->greaterOrEquals(5);
  }

  function testItDoesNotRunWithoutRequestData() {
    $daemon = Stub::construct(
      new Daemon(),
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
      new Daemon(),
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
      new Daemon(),
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
    $daemon = Stub::make(new Daemon(true), array(
      'executeScheduleWorker' => Stub::exactly(1, function() { }),
      'executeQueueWorker' => Stub::exactly(1, function() { }),
      'pauseExecution' => function($a) { },
      'callSelf' => function() { }
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
  }

  function testItContinuesExecutionWhenWorkersThrowException() {
    $daemon = Stub::make(new Daemon(true), array(
      'executeScheduleWorker' => function() {
        throw new \Exception();
      },
      'executeQueueWorker' => function() {
        throw new \Exception();
      },
      'pauseExecution' => function($a) { },
      'callSelf' => function() { }
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
  }

  function testItCanPauseExecution() {
    $daemon = Stub::make(new Daemon(true), array(
      'executeScheduleWorker' => function() { },
      'executeQueueWorker' => function() { },
      'pauseExecution' => Stub::exactly(1, function($pause_delay) {
        expect($pause_delay)->lessThan(CronHelper::DAEMON_EXECUTION_LIMIT);
        expect($pause_delay)->greaterThan(CronHelper::DAEMON_EXECUTION_LIMIT - 1);
      }),
      'callSelf' => function() { }
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
  }


  function testItTerminatesExecutionWhenDaemonIsDeleted() {
    $daemon = Stub::make(new Daemon(true), array(
      'executeScheduleWorker' => function() {
        Setting::deleteValue(CronHelper::DAEMON_SETTING);
      },
      'executeQueueWorker' => function() { },
      'pauseExecution' => function() { },
      'terminateRequest' => Stub::exactly(1, function() { })
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
  }

  function testItTerminatesExecutionWhenDaemonTokenChanges() {
    $daemon = Stub::make(new Daemon(true), array(
      'executeScheduleWorker' => function() {
        Setting::setValue(
          CronHelper::DAEMON_SETTING,
          array('token' => 567)
        );
      },
      'executeQueueWorker' => function() { },
      'pauseExecution' => function() { },
      'terminateRequest' => Stub::exactly(1, function() { })
    ), $this);
    $data = array(
      'token' => 123
    );
    Setting::setValue(CronHelper::DAEMON_SETTING, $data);
    $daemon->__construct($data);
    $daemon->run();
  }

  function testItUpdatesDaemonTokenDuringExecution() {
    $daemon = Stub::make(new Daemon(true), array(
      'executeScheduleWorker' => function() { },
      'executeQueueWorker' => function() { },
      'pauseExecution' => function() { },
      'callSelf' => function() { }
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

  function testItCanRun() {
    ignore_user_abort(0);
    expect(ignore_user_abort())->equals(0);
    $daemon = Stub::make(new Daemon(true), array(
      'pauseExecution' => function() { },
      // workers should be executed
      'executeScheduleWorker' => Stub::exactly(1, function() { }),
      'executeQueueWorker' => Stub::exactly(1, function() { }),
      // daemon should call itself
      'callSelf' => Stub::exactly(1, function() { }),
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
    $daemon = Stub::make(new Daemon(true), array(
      'terminateRequest' => Stub::exactly(1, function($message) {
        expect($message)->equals('pong');
      })
    ), $this);
    $daemon->ping();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}