<?php

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Setting;

class MailerLogTest extends MailPoetTest {
  function testItGetsMailerLogWhenOneExists() {
    $mailer_log = array(
      'sent' => 0,
      'started' => time()
    );
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    expect(MailerLog::getMailerLog())->equals($mailer_log);
  }

  function testItGetsMailerLogWhenOneDoesNotExist() {
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['sent'])->equals(0);
    expect(strlen($mailer_log['started']))->greaterThan(5);
  }

  function testItCreatesMailer() {
    $mailer_log = MailerLog::createMailerLog();
    expect($mailer_log['sent'])->equals(0);
    expect(strlen($mailer_log['started']))->greaterThan(5);
  }

  function testItResetsMailerLog() {
    $mailer_log = array(
      'sent' => 1,
      'started' => time() - 10
    );
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    MailerLog::resetMailerLog();
    $updated_mailer_log = Setting::getValue(MailerLog::SETTING_NAME);
    expect($updated_mailer_log['sent'])->equals(0);
    expect($updated_mailer_log['started'])->greaterThan($mailer_log['started']);
  }

  function testItUpdatesMailerLog() {
    $mailer_log = array(
      'sent' => 1,
      'started' => time() - 10
    );
    MailerLog::updateMailerLog($mailer_log);
    $updated_mailer_log = Setting::getValue(MailerLog::SETTING_NAME);
    expect($updated_mailer_log)->equals($mailer_log);
  }

  function testItIncrementsSentCount() {
    $mailer_log = array(
      'sent' => 1,
      'started' => time(),
      'error' => null
    );
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    MailerLog::incrementSentCount();
    $updated_mailer_log = Setting::getValue(MailerLog::SETTING_NAME);
    expect($updated_mailer_log['sent'])->equals(2);
  }

  function testItChecksWhenSendingLimitIsReached() {
    $mailer_config = array(
      'frequency' => array(
        'emails' => 2,
        'interval' => 1
      )
    );
    Setting::setValue(Mailer::MAILER_CONFIG_SETTING_NAME, $mailer_config);

    // limit is not reached
    $mailer_log = array(
      'sent' => 1,
      'started' => time()
    );
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    expect(MailerLog::isSendingLimitReached())->false();

    // limit is reached
    $mailer_log = array(
      'sent' => 2,
      'started' => time()
    );
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    expect(MailerLog::isSendingLimitReached())->true();
  }

  function testitChecksWhenSendingIsPaused() {
    $mailer_log = array('status' => MailerLog::STATUS_PAUSED);
    expect(MailerLog::isSendingPaused($mailer_log))->true();
    $mailer_log = array('status' => false);
    expect(MailerLog::isSendingPaused($mailer_log))->false();
  }

  function testItResetsMailerAfterSendingLimitWaitPeriodIsOver() {
    $mailer_config = array(
      'frequency' => array(
        'emails' => 2,
        'interval' => 1
      )
    );
    $mailer_log = array(
      'sent' => 2,
      // (mailer config's interval * 60 seconds) + 1 second
      'started' => time() - 61
    );
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    Setting::setValue(Mailer::MAILER_CONFIG_SETTING_NAME, $mailer_config);

    // limit is not reached
    expect(MailerLog::isSendingLimitReached())->false();
    // mailer log is reset
    $updated_mailer_log = Setting::getValue(MailerLog::SETTING_NAME);
    expect($updated_mailer_log['sent'])->equals(0);
  }

  function testItResumesSending() {
    // set status to "paused"
    $mailer_log = array('status' => MailerLog::STATUS_PAUSED);
    MailerLog::updateMailerLog($mailer_log);
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->equals(MailerLog::STATUS_PAUSED);
    // status is reset when sending is resumed
    MailerLog::resumeSending();
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->null();
  }

  function testItPausesSending() {
    $mailer_log = array(
      'status' => null,
      'retry_attempt' => MailerLog::RETRY_ATTEMPTS_LIMIT,
      'retry_at' => time() + 20
    );
    // status is set to PAUSED, retry attempt and retry at time are cleared
    MailerLog::pauseSending($mailer_log);
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->equals(MailerLog::STATUS_PAUSED);
    expect($mailer_log['retry_attempt'])->null();
    expect($mailer_log['retry_at'])->null();
  }

  function itProcessesSendingError() {
    // retry-related mailer values should be null
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['retry_attempt'])->null();
    expect($mailer_log['retry_at'])->null();
    expect($mailer_log['error'])->null();
    // retry attempt should be incremented, error logged, retry attempt scheduled
    MailerLog::processSendingError($operation = 'send', $error = 'email rejected');
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['retry_attempt'])->equals(1);
    expect($mailer_log['retry_at'])->greaterThan(time());
    expect($mailer_log['error'])->equals(
      array(
        'operation' => 'send',
        'error_message' => 'email rejected'
      )
    );
  }

  function testItEnforcesSendingLimit() {
    $mailer_config = array(
      'frequency' => array(
        'emails' => 2,
        'interval' => 1
      )
    );
    $mailer_log = MailerLog::createMailerLog();
    $mailer_log['sent'] = 2;
    $mailer_log['started'] = time();
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    Setting::setValue(Mailer::MAILER_CONFIG_SETTING_NAME, $mailer_config);

    // exception is thrown when sending limit is reached
    try {
      MailerLog::enforceExecutionRequirements();
      self::fail('Sending frequency exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Sending frequency limit has been reached.');
    }
  }

  function testItEnsuresSendingLimitIsEnforcedAfterFrequencyIsLowered() {
    $mailer_log = MailerLog::createMailerLog();
    $mailer_log['sent'] = 10;
    $mailer_log['started'] = time();
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    $mailer_config = array(
      'frequency' => array(
        'emails' => 2, // frequency is less than the sent count
        'interval' => 1
      )
    );
    Setting::setValue(Mailer::MAILER_CONFIG_SETTING_NAME, $mailer_config);

    // exception is thrown when sending limit is reached
    try {
      MailerLog::enforceExecutionRequirements();
      self::fail('Sending frequency exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Sending frequency limit has been reached.');
    }
  }

  function testItEnsuresSendingLimitIsNotEnforcedAfterFrequencyIsIncreased() {
    $mailer_log = MailerLog::createMailerLog();
    $mailer_log['sent'] = 10;
    $mailer_log['started'] = time();
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    $mailer_config = array(
      'frequency' => array(
        'emails' => 20, // frequency is greater than the sent count
        'interval' => 1
      )
    );
    Setting::setValue(Mailer::MAILER_CONFIG_SETTING_NAME, $mailer_config);

    // sending limit exception should not be thrown
    try {
      MailerLog::enforceExecutionRequirements();
    } catch(\Exception $e) {
      self::fail('Sending frequency exception was thrown.');
    }
  }

  function testItEnforcesRetryAtTime() {
    $mailer_log = MailerLog::createMailerLog();
    $mailer_log['retry_at'] = time() + 10;
    // exception is thrown when current time is sooner than 120 seconds
    try {
      MailerLog::enforceExecutionRequirements($mailer_log);
      self::fail('Sending waiting to be retried exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Sending is waiting to be retried.');
    }
  }

  function testItEnforcesRetryAttempts() {
    $mailer_log = MailerLog::createMailerLog();
    $mailer_log['retry_attempt'] = 2;
    // allow less than 3 attempts
    expect(MailerLog::enforceExecutionRequirements($mailer_log))->null();
    // pase sending and throw exception when more than 3 attempts
    $mailer_log['retry_attempt'] = MailerLog::RETRY_ATTEMPTS_LIMIT;
    try {
      MailerLog::enforceExecutionRequirements($mailer_log);
      self::fail('Sending paused exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Sending has been paused.');
    }
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->equals(MailerLog::STATUS_PAUSED);
  }

  function testItClearsSendingErrorLog() {
    $mailer_log = MailerLog::createMailerLog();
    $mailer_log['retry_attempt'] = 1;
    $mailer_log['retry_at'] = 1;
    $mailer_log['error'] = 1;
    $mailer_log['status'] = 'status';
    $mailer_log = MailerLog::clearSendingErrorLog($mailer_log);
    expect($mailer_log['retry_attempt'])->null();
    expect($mailer_log['retry_at'])->null();
    expect($mailer_log['error'])->null();
    expect($mailer_log['status'])->equals('status');
  }

  function testItEnforcesPausedStatus() {
    $mailer_log = MailerLog::createMailerLog();
    $mailer_log['status'] = MailerLog::STATUS_PAUSED;
    try {
      MailerLog::enforceExecutionRequirements($mailer_log);
      self::fail('Sending paused exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Sending has been paused.');
    }
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}