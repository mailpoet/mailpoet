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
      'started' => time()
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

  function testItCanEnforceSendingLimit() {
    $mailer_config = array(
      'frequency' => array(
        'emails' => 2,
        'interval' => 1
      )
    );
    $mailer_log = array(
      'sent' => 2,
      'started' => time()
    );
    Setting::setValue(MailerLog::SETTING_NAME, $mailer_log);
    Setting::setValue(Mailer::MAILER_CONFIG_SETTING_NAME, $mailer_config);

    // exception is thrown when sending limit is reached
    try {
      MailerLog::enforceSendingLimit();
      self::fail('Sending frequency exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Sending frequency limit has been reached.');
    }
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}