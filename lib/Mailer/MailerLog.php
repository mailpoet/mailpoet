<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class MailerLog {
  const SETTING_VALUE = 'mta_log';

  static function getMailerLog() {
    $mailer_log = Setting::getValue(self::SETTING_VALUE);
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
    Setting::setValue(self::SETTING_VALUE, $mailer_log);
    return $mailer_log;
  }

  static function updateMailerLog($mailer_log) {
    Setting::setValue(self::SETTING_VALUE, $mailer_log);
    return $mailer_log;
  }

  static function isSendingLimitReached() {
    $mailer_config = Mailer::getMailerConfig();
    $mailer_log = self::getMailerLog();
    $elapsed_time = time() - (int)$mailer_log['started'];
    if($mailer_log['sent'] === $mailer_config['frequency_limit'] &&
      $elapsed_time <= $mailer_config['frequency_interval']
    ) {
      return true;
    }
    if($elapsed_time > $mailer_config['frequency_interval']) {
      self::createOrResetMailerLog();
    }
    return false;
  }
}