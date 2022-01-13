<?php

namespace MailPoet\Mailer;

use MailPoet\Settings\SettingsController;

class MailerLog {
  const SETTING_NAME = 'mta_log';
  const STATUS_PAUSED = 'paused';
  const RETRY_ATTEMPTS_LIMIT = 3;
  const RETRY_INTERVAL = 120; // seconds

  public static function getMailerLog($mailerLog = false) {
    if ($mailerLog) return $mailerLog;
    $settings = SettingsController::getInstance();
    $mailerLog = $settings->get(self::SETTING_NAME);
    if (!$mailerLog) {
      $mailerLog = self::createMailerLog();
    }
    return $mailerLog;
  }

  public static function createMailerLog() {
    $mailerLog = [
      'sent' => null,
      'started' => time(),
      'status' => null,
      'retry_attempt' => null,
      'retry_at' => null,
      'error' => null,
    ];
    $settings = SettingsController::getInstance();
    $settings->set(self::SETTING_NAME, $mailerLog);
    return $mailerLog;
  }

  public static function resetMailerLog() {
    return self::createMailerLog();
  }

  public static function updateMailerLog($mailerLog) {
    $settings = SettingsController::getInstance();
    $settings->set(self::SETTING_NAME, $mailerLog);
    return $mailerLog;
  }

  public static function enforceExecutionRequirements($mailerLog = false) {
    $mailerLog = self::getMailerLog($mailerLog);
    if ($mailerLog['retry_attempt'] === self::RETRY_ATTEMPTS_LIMIT) {
      $mailerLog = self::pauseSending($mailerLog);
    }
    if (self::isSendingPaused($mailerLog)) {
      throw new \Exception(__('Sending has been paused.', 'mailpoet'));
    }
    if (!is_null($mailerLog['retry_at'])) {
      if (time() <= $mailerLog['retry_at']) {
        throw new \Exception(__('Sending is waiting to be retried.', 'mailpoet'));
      } else {
        $mailerLog['retry_at'] = null;
        self::updateMailerLog($mailerLog);
      }
    }
    // ensure that sending frequency has not been reached
    if (self::isSendingLimitReached($mailerLog)) {
      throw new \Exception(__('Sending frequency limit has been reached.', 'mailpoet'));
    }
  }

  public static function pauseSending($mailerLog) {
    $mailerLog['status'] = self::STATUS_PAUSED;
    $mailerLog['retry_attempt'] = null;
    $mailerLog['retry_at'] = null;
    return self::updateMailerLog($mailerLog);
  }

  public static function resumeSending() {
    return self::resetMailerLog();
  }

  /**
   * Process error, doesn't increase retry_attempt so it will not block sending
   *
   * @param string $operation
   * @param string $errorMessage
   * @param int $retryInterval
   *
   * @throws \Exception
   */
  public static function processNonBlockingError($operation, $errorMessage, $retryInterval = self::RETRY_INTERVAL) {
    $mailerLog = self::getMailerLog();
    $mailerLog['retry_at'] = time() + $retryInterval;
    $mailerLog = self::setError($mailerLog, $operation, $errorMessage);
    self::updateMailerLog($mailerLog);
    self::enforceExecutionRequirements();
  }

  /**
   * Process error, increase retry_attempt and block sending if it goes above RETRY_INTERVAL
   *
   * @param string $operation
   * @param string $errorMessage
   * @param string $errorCode
   * @param bool $pauseSending
   *
   * @throws \Exception
   */
  public static function processError($operation, $errorMessage, $errorCode = null, $pauseSending = false, $throttledBatchSize = null) {
    $mailerLog = self::getMailerLog();
    if (!isset($throttledBatchSize) || $throttledBatchSize === 1) {
      $mailerLog['retry_attempt']++;
    }
    $mailerLog['retry_at'] = time() + self::RETRY_INTERVAL;
    $mailerLog = self::setError($mailerLog, $operation, $errorMessage, $errorCode);
    self::updateMailerLog($mailerLog);
    if ($pauseSending) {
      self::pauseSending($mailerLog);
    }
    self::enforceExecutionRequirements();
  }

  public static function setError($mailerLog, $operation, $errorMessage, $errorCode = null) {
    $mailerLog['error'] = [
      'operation' => $operation,
      'error_message' => $errorMessage,
    ];
    if ($errorCode) {
      $mailerLog['error']['error_code'] = $errorCode;
    }
    return $mailerLog;
  }

  public static function getError($mailerLog = false) {
    $mailerLog = self::getMailerLog($mailerLog);
    return isset($mailerLog['error']) ? $mailerLog['error'] : null;
  }

  public static function incrementSentCount() {
    $mailerLog = self::getMailerLog();
    // do not increment count if sending limit is reached
    if (self::isSendingLimitReached($mailerLog)) return;
    // clear previous retry count, errors, etc.
    if ($mailerLog['error']) {
      $mailerLog = self::clearSendingErrorLog($mailerLog);
    }
    (int)$mailerLog['sent']++;
    return self::updateMailerLog($mailerLog);
  }

  public static function clearSendingErrorLog($mailerLog) {
    $mailerLog['retry_attempt'] = null;
    $mailerLog['retry_at'] = null;
    $mailerLog['error'] = null;
    return self::updateMailerLog($mailerLog);
  }

  public static function isSendingLimitReached($mailerLog = false) {
    $settings = SettingsController::getInstance();
    $mailerConfig = $settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    // do not enforce sending limit for MailPoet's sending method
    if ($mailerConfig['method'] === Mailer::METHOD_MAILPOET) return false;
    $mailerLog = self::getMailerLog($mailerLog);
    $elapsedTime = time() - (int)$mailerLog['started'];

    if (empty($mailer['frequency'])) {
      $defaultSettings = $settings->getAllDefaults();
      $mailer['frequency'] = $defaultSettings['mta']['frequency'];
    }
    $frequencyInterval = (int)$mailerConfig['frequency']['interval'] * Mailer::SENDING_LIMIT_INTERVAL_MULTIPLIER;
    $frequencyLimit = (int)$mailerConfig['frequency']['emails'];

    if ($mailerLog['sent'] >= $frequencyLimit) {
      if ($elapsedTime <= $frequencyInterval) return true;
      // reset mailer log as enough time has passed since the limit was reached
      self::resetMailerLog();
    }
    return false;
  }

  public static function isSendingPaused($mailerLog = false) {
    $mailerLog = self::getMailerLog($mailerLog);
    return $mailerLog['status'] === self::STATUS_PAUSED;
  }
}
