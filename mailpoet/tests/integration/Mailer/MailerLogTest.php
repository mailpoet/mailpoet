<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer;

use MailPoet\Entities\LogEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Settings\SettingsController;

class MailerLogTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
  }

  public function testItGetsMailerLogWhenOneExists() {
    $mailerLog = [
      'sent' => [],
      'started' => time(),
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    expect(MailerLog::getMailerLog())->equals($mailerLog);
  }

  public function testItGetsMailerLogWhenOneDoesNotExist() {
    $resultExpectedGreaterThan = time() - 1;
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['sent'])->equals([]);
    expect($mailerLog['started'])->greaterThan($resultExpectedGreaterThan);
  }

  public function testItDoesNotIncrementWhenSendingMethodIsMailpoet() {

    $expectedCount = MailerLog::sentSince();
    $settings = SettingsController::getInstance();
    $mailerConfig = $settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    $mailerConfig['method'] = Mailer::METHOD_MAILPOET;
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);
    expect(MailerLog::incrementSentCount())->null();
    expect(MailerLog::sentSince())->equals($expectedCount);
  }

  public function testItResetsErrorOnIncrementCountEvenForMSS() {
    $settings = SettingsController::getInstance();
    $mailerConfig = $settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    $mailerConfig['method'] = Mailer::METHOD_MAILPOET;
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);
    $mailerLog = MailerLog::getMailerLog();
    $mailerLog['error'] = ['operation' => 'send', 'error_message' => ''];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    expect(MailerLog::incrementSentCount())->null();
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['error'])->null();
  }

  public function testItCreatesMailer() {
    $resultExpectedGreaterThan = time() - 1;
    $mailerLog = MailerLog::createMailerLog();
    expect($mailerLog['sent'])->equals([]);
    expect($mailerLog['started'])->greaterThan($resultExpectedGreaterThan);
  }

  public function testItResetsMailerLog() {
    $started = time() - 10;
    $mailerLog = [
      'sent' => [date('Y-m-d H:i:s', $started) => 1],
      'started' => $started,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    MailerLog::resetMailerLog();
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect($updatedMailerLog['sent'])->equals([]);
    expect($updatedMailerLog['started'])->greaterThan($mailerLog['started']);
  }

  public function testItUpdatesMailerLog() {
    $started = time() - 10;
    $mailerLog = MailerLog::getMailerLog();
    $mailerLog['sent'] = [date('Y-m-d H:i:s', $started) => 1];
    $mailerLog['started'] = $started;
    MailerLog::updateMailerLog($mailerLog);
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect($updatedMailerLog)->equals($mailerLog);
  }

  public function testItIncrementsSentCount() {
    $started = time() - 10;
    $mailerLog = [
      'sent' => [date('Y-m-d H:i:s', $started) => 1],
      'started' => $started,
      'error' => null,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    MailerLog::incrementSentCount();
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect(array_sum($updatedMailerLog['sent']))->equals(2);
  }

  public function testItTruncatesOutdatedEntriesWhenIncrementingSentCount() {
    $mailerConfig = [
      'frequency' => [
        'emails' => 12,
        'interval' => 1,
      ],
    ];
    $currentTimeframeBorder = time() - 60;
    $outdated = $currentTimeframeBorder - 1;
    $notOutdated = $currentTimeframeBorder + 1;
    $mailerLog = [
      'sent' => [
        date('Y-m-d H:i:s', $outdated) => 2,
        date('Y-m-d H:i:s', $notOutdated) => 10,
      ],
      'started' => $outdated,
      'error' => null,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);
    MailerLog::incrementSentCount();
    $updatedMailerLog = $this->settings->get(MailerLog::SETTING_NAME);
    expect(array_sum($updatedMailerLog['sent']))->equals(11);
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
    $started = time() - 10;
    $mailerLog = [
      'sent' => [date('Y-m-d H:i:s', $started) => 1],
      'started' => $started,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    expect(MailerLog::isSendingLimitReached())->false();

    // limit is reached
    $started = time() - 10;
    $mailerLog = [
      'sent' => [date('Y-m-d H:i:s', $started) => 2],
      'started' => $started,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    expect(MailerLog::isSendingLimitReached())->true();
  }

  public function testItChecksWhenSendingIsPaused() {
    $mailerLog = MailerLog::getMailerLog();
    $mailerLog['status'] = MailerLog::STATUS_PAUSED;
    expect(MailerLog::isSendingPaused($mailerLog))->true();
    $mailerLog['status'] = null;
    expect(MailerLog::isSendingPaused($mailerLog))->false();
  }

  public function testItLimitReachedCalculationDoesNotIncludeOutdatedData() {
    $mailerConfig = [
      'frequency' => [
        'emails' => 2,
        'interval' => 1,
      ],
    ];
    $started = time() - 61;
    $mailerLog = [
      'sent' => [date('Y-m-d H:i:s', $started) => 2],
      'started' => $started,
    ];
    $this->settings->set(MailerLog::SETTING_NAME, $mailerLog);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $mailerConfig);

    // limit is not reached
    expect(MailerLog::isSendingLimitReached())->false();
  }

  public function testItResumesSending() {
    // set status to "paused"
    $mailerLog = MailerLog::getMailerLog();
    $mailerLog['status'] = MailerLog::STATUS_PAUSED;
    MailerLog::updateMailerLog($mailerLog);
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
    // status is reset when sending is resumed
    MailerLog::resumeSending();
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->null();
  }

  public function testItPausesSending() {
    $mailerLog = MailerLog::getMailerLog();
    $mailerLog['status'] = null;
    $mailerLog['retry_attempt'] = MailerLog::RETRY_ATTEMPTS_LIMIT;
    $mailerLog['retry_at'] = time() + 20;

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
    $logs = $this->entityManager->getRepository(LogEntity::class)->findAll();
    $this->assertInstanceOf(LogEntity::class, $logs[0]);
    expect($logs[0]->getMessage())->stringContainsString('Email sending was paused due an error');
  }

  public function testItProcessesTransactionalEmailSendingError() {
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['transactional_email_last_error_at'])->null();
    expect($mailerLog['transactional_email_error_count'])->null();
    MailerLog::processTransactionalEmailError(MailerError::OPERATION_SEND, 'email rejected');
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['transactional_email_last_error_at'])->equals(time(), 1);
    expect($mailerLog['transactional_email_error_count'])->equals(1);
    expect($mailerLog['error'])->equals(
      [
        'operation' => MailerError::OPERATION_SEND,
        'error_message' => 'email rejected',
      ]
    );
  }

  public function testItSkipsTransactionalEmailSendingErrorWhenLastLoggedIsWithinIgnoreThreshold() {
    $mailerLog = MailerLog::createMailerLog();
    $almostTwoMinutesAgo = time() - 110;
    $mailerLog['transactional_email_last_error_at'] = $almostTwoMinutesAgo;
    $mailerLog['transactional_email_error_count'] = 1;
    MailerLog::updateMailerLog($mailerLog);
    MailerLog::processTransactionalEmailError(MailerError::OPERATION_SEND, 'email rejected');
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['transactional_email_last_error_at'])->equals($almostTwoMinutesAgo);
    expect($mailerLog['transactional_email_error_count'])->equals(1);
  }

  public function testItIncreaseCounterOfTransactionalEmailSendingErrorWhenLastLoggedOlderThanIgnoreThreshold() {
    $mailerLog = MailerLog::createMailerLog();
    $moreThanTwoMinutesAgo = time() - 130;
    $mailerLog['transactional_email_last_error_at'] = $moreThanTwoMinutesAgo;
    $mailerLog['transactional_email_error_count'] = 1;
    MailerLog::updateMailerLog($mailerLog);
    MailerLog::processTransactionalEmailError(MailerError::OPERATION_SEND, 'email rejected');
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['transactional_email_last_error_at'])->equals(time(), 1);
    expect($mailerLog['transactional_email_error_count'])->equals(2);
  }

  public function testItPausesSendingWhenTransactionalEmailSendingErrorCountReachesLimit() {
    $mailerLog = MailerLog::createMailerLog();
    $moreThanTwoMinutesAgo = time() - 130;
    $mailerLog['transactional_email_last_error_at'] = $moreThanTwoMinutesAgo;
    $mailerLog['transactional_email_error_count'] = MailerLog::RETRY_ATTEMPTS_LIMIT - 1;
    MailerLog::updateMailerLog($mailerLog);
    MailerLog::processTransactionalEmailError(MailerError::OPERATION_SEND, 'email rejected');
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['transactional_email_last_error_at'])->null();
    expect($mailerLog['transactional_email_error_count'])->null();
    expect(MailerLog::isSendingPaused())->true();
    $logs = $this->entityManager->getRepository(LogEntity::class)->findAll();
    $this->assertInstanceOf(LogEntity::class, $logs[0]);
    expect($logs[0]->getMessage())->stringContainsString('Email sending was paused due a transactional email error');
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
    $mailerLog['error'] = [
      'operation' => 'operation',
      'error_code' => 'error_code',
      'error_message' => 'error_message',
      'transactional_email_last_error_at' => time(),
      'transactional_email_error_count' => 1,
    ];
    $mailerLog['status'] = 'status';
    $mailerLog = MailerLog::clearSendingErrorLog($mailerLog);
    expect($mailerLog['retry_attempt'])->null();
    expect($mailerLog['retry_at'])->null();
    expect($mailerLog['error'])->null();
    expect($mailerLog['transactional_email_last_error_at'])->null();
    expect($mailerLog['transactional_email_error_count'])->null();
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
}
