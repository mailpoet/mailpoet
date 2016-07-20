<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class MailerLog {
  const MAILER_LOG_CONFIG = 'mta_log';
  const SENDING_LIMIT_INTERVAL_MULTIPLIER = 60;

  static function getMailerLog() {
    $mailer_log = Setting::getValue(self::MAILER_LOG_CONFIG);
    if(!$mailer_log) {
      $mailer_log = self::createOrResetMailerLog();
    }
    return $mailer_log;
  }

  static function createOrResetMailerLog() {
    $mailer_log = array(
      'sent' => 0,
      'started' => time()
    );
    Setting::setValue(self::MAILER_LOG_CONFIG, $mailer_log);
    return $mailer_log;
  }

  static function updateMailerLog($mailer_log) {
    Setting::setValue(self::MAILER_LOG_CONFIG, $mailer_log);
    return $mailer_log;
  }

  static function isSendingLimitReached() {
    $mailer = Mailer::getMailer();
    $mailer_log = self::getMailerLog();
    $elapsed_time = time() - (int)$mailer_log['started'];
    if($mailer_log['sent'] === $mailer['frequency_limit'] &&
      $elapsed_time <= $mailer['frequency_interval']
    ) {
      return true;
    }
    if($elapsed_time > $mailer['frequency_interval']) {
      self::createOrResetMailerLog();
    }
    return false;
  }
}