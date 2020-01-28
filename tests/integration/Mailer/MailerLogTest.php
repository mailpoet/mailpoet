<?php

namespace MailPoet\Test\Mailer;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class MailerLogTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
  }

  public function testItGetsMailerLogWhenOneExists() {
    $mailerLog = [
      'sent' => 0,
      'started' => time(),
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    expect(MailerLog::getMailerLog())->equals($mailerLog);
  }

  public function testItGetsMailerLogWhenOneDoesNotExist() {
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['sent'])->equals(0);
    expect(strlen($mailerLog['started']))->greaterThan(5);
  }

  public function testItCreatesMailer() {
    $mailerLog = MailerLog::createMailerLog();
    expect($mailerLog['sent'])->equals(0);
    expect(strlen($mailerLog['started']))->greaterThan(5);
  }

  public function testItResetsMailerLog() {
    $mailerLog = [
      'sent' => 1,
      'started' => time() - 10,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    MailerLog::resetMailerLog();
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect($updatedMailerLog['sent'])->equals(0);
    expect($updatedMailerLog['started'])->greaterThan($mailerLog['started']);
  }

  public function testItUpdatesMailerLog() {
    $mailerLog = [
      'sent' => 1,
      'started' => time() - 10,
    ];
    MailerLog::updateMailerLog($mailerLog);
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect($updatedMailerLog)->equals($mailerLog);
  }

  public function testItIncrementsSentCount() {
    $mailerLog = [
      'sent' => 1,
      'started' => time(),
      'error' => null,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    MailerLog::incrementSentCount();
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect($updatedMailerLog['sent'])->equals(2);
  }

  public function testItChecksWhenSendingLimitIsReached() {
    $mailerConfig = [
      'frequency' => [
        'emails' => 2,
        'interval' => 1,
      ],
    ];
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);

    // limit is not reached
    $mailerLog = [
      'sent' => 1,
      'started' => time(),
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    expect(MailerLog::isSendingLimitReached())->false();

    // limit is reached
    $mailerLog = [
      'sent' => 2,
      'started' => time(),
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    expect(MailerLog::isSendingLimitReached())->true();
  }

  public function testItChecksWhenSendingIsPaused() {
    $mailerLog = ['status' => MailerLog::STATUS_PAUSED];
    expect(MailerLog::isSendingPaused($mailerLog))->true();
    $mailerLog = ['status' => false];
    expect(MailerLog::isSendingPaused($mailerLog))->false();
  }

  public function testItResetsMailerAfterSendingLimitWaitPeriodIsOver() {
    $mailerConfig = [
      'frequency' => [
        'emails' => 2,
        'interval' => 1,
      ],
    ];
    $mailerLog = [
      'sent' => 2,
      // (mailer config's interval * 60 seconds) + 1 second
      'started' => time() - 61,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);

    // limit is not reached
    expect(MailerLog::isSendingLimitReached())->false();
    // mailer log is reset
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect($updatedMailerLog['sent'])->equals(0);
  }

  public function testItResumesSending() {
    // set status to "paused"
    $mailerLog = ['status' => MailerLog::STATUS_PAUSED];
    MailerLog::updateMailerLog($mailerLog);
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
    // status is reset when sending is resumed
    MailerLog::resumeSending();
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->null();
  }

  public function testItPausesSending() {
    $mailerLog = [
      'status' => null,
      'retry_attempt' => MailerLog::RETRY_ATTEMPTS_LIMIT,
      'retry_at' => time() + 20,
    ];
    // status is set to PAUSED, retry attempt and retry at time are cleared
    MailerLog::pauseSending($mailerLog);
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
    expect($mailerLog['retry_attempt'])->null();
    expect($mailerLog['retry_at'])->null();
  }

  public function testItProcessesSendingError() {
    // retry-related mailer values should be null
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['retry_attempt'])->null();
    expect($mailerLog['retry_at'])->null();
    expect($mailerLog['error'])->null();
    // retry attempt should be incremented, error logged, retry attempt scheduled
    $this->expectException('\Exception');
    MailerLog::processError($operation = 'send', $error = 'email rejected');
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['retry_attempt'])->equals(1);
    expect($mailerLog['retry_at'])->greaterThan(time());
    expect($mailerLog['error'])->equals(
      [
        'operation' => 'send',
        'error_message' => $error,
      ]
    );
  }

  public function testItProcessesNonBlockingSendingError() {
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['retry_attempt'])->null();
    expect($mailerLog['retry_at'])->null();
    expect($mailerLog['error'])->null();
    $this->expectException('\Exception');
    MailerLog::processNonBlockingError($operation = 'send', $error = 'email rejected');
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['retry_attempt'])->equals(1);
    expect($mailerLog['retry_at'])->greaterThan(time());
    expect($mailerLog['error'])->equals(
      [
        'operation' => 'send',
        'error_message' => $error,
      ]
    );
  }

  public function testItPausesSendingAfterProcessingSendingError() {
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['error'])->null();
    $error = null;
    try {
      MailerLog::processError($operation = 'send', $error = 'email rejected - sending paused', $errorCode = null, $pauseSending = true);
      $this->fail('Paused sending exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sending has been paused.');
    }
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['retry_attempt'])->null();
    expect($mailerLog['retry_at'])->null();
    expect($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
    expect($mailerLog['error'])->equals(
      [
        'operation' => 'send',
        'error_message' => $error,
      ]
    );
  }

  public function testItEnforcesSendingLimit() {
    $mailerConfig = [
      'frequency' => [
        'emails' => 2,
        'interval' => 1,
      ],
    ];
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['sent'] = 2;
    $mailerLog['started'] = time();
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);

    // exception is thrown when sending limit is reached
    try {
      MailerLog::enforceExecutionRequirements();
      self::fail('Sending frequency exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sending frequency limit has been reached.');
    }
  }

  public function testItEnsuresSendingLimitIsEnforcedAfterFrequencyIsLowered() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['sent'] = 10;
    $mailerLog['started'] = time();
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    $mailerConfig = [
      'frequency' => [
        'emails' => 2, // frequency is less than the sent count
        'interval' => 1,
      ],
    ];
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);

    // exception is thrown when sending limit is reached
    try {
      MailerLog::enforceExecutionRequirements();
      self::fail('Sending frequency exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sending frequency limit has been reached.');
    }
  }

  public function testItEnsuresSendingLimitIsNotEnforcedAfterFrequencyIsIncreased() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['sent'] = 10;
    $mailerLog['started'] = time();
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    $mailerConfig = [
      'frequency' => [
        'emails' => 20, // frequency is greater than the sent count
        'interval' => 1,
      ],
    ];
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);

    // sending limit exception should not be thrown
    try {
      MailerLog::enforceExecutionRequirements();
    } catch (\Exception $e) {
      self::fail('Sending frequency exception was thrown.');
    }
  }

  public function testItEnforcesRetryAtTime() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['retry_at'] = time() + 10;
    // exception is thrown when current time is sooner than 120 seconds
    try {
      MailerLog::enforceExecutionRequirements($mailerLog);
      self::fail('Sending waiting to be retried exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sending is waiting to be retried.');
    }
  }

  public function testItEnforcesRetryAttempts() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['retry_attempt'] = 2;
    // allow less than 3 attempts
    expect(MailerLog::enforceExecutionRequirements($mailerLog))->null();
    // pase sending and throw exception when more than 3 attempts
    $mailerLog['retry_attempt'] = MailerLog::RETRY_ATTEMPTS_LIMIT;
    try {
      MailerLog::enforceExecutionRequirements($mailerLog);
      self::fail('Sending paused exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sending has been paused.');
    }
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
  }

  public function testItClearsSendingErrorLog() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['retry_attempt'] = 1;
    $mailerLog['retry_at'] = 1;
    $mailerLog['error'] = 1;
    $mailerLog['status'] = 'status';
    $mailerLog = MailerLog::clearSendingErrorLog($mailerLog);
    expect($mailerLog['retry_attempt'])->null();
    expect($mailerLog['retry_at'])->null();
    expect($mailerLog['error'])->null();
    expect($mailerLog['status'])->equals('status');
  }

  public function testItEnforcesPausedStatus() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['status'] = MailerLog::STATUS_PAUSED;
    try {
      MailerLog::enforceExecutionRequirements($mailerLog);
      self::fail('Sending paused exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sending has been paused.');
    }
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
