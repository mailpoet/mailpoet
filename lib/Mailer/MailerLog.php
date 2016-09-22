<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class MailerLog {
  const SETTING_NAME = 'mta_log';

  static function getMailerLog() {
    $mailer_log = Setting::getValue(self::SETTING_NAME);
    if(!$mailer_log) {
      $mailer_log = self::createMailerLog();
    }
    return $mailer_log;
  }

  static function createMailerLog() {
    $mailer_log = array(
      'sent' => 0,
      'started' => time()
    );
    Setting::setValue(self::SETTING_NAME, $mailer_log);
    return $mailer_log;
  }

  static function resetMailerLog() {
    return self::createMailerLog();
  }

  static function updateMailerLog($mailer_log) {
    Setting::setValue(self::SETTING_NAME, $mailer_log);
    return $mailer_log;
  }

  static function incrementSentCount() {
    $mailer_log = self::getMailerLog();
    (int)$mailer_log['sent']++;
    return self::updateMailerLog($mailer_log);
  }

  static function isSendingLimitReached() {
    $mailer_config = Mailer::getMailerConfig();
    $mailer_log = self::getMailerLog();
    $elapsed_time = time() - (int)$mailer_log['started'];
    if($mailer_log['sent'] === $mailer_config['frequency_limit']) {
      if($elapsed_time <= $mailer_config['frequency_interval']) return true;
      // reset mailer log if enough time has passed since the limit was reached
      self::resetMailerLog();
    }
    return false;
  }

  static function enforceSendingLimit() {
    if(self::isSendingLimitReached()) {
      throw new \Exception(__('Sending frequency limit has been reached.'));
    }
  }
}