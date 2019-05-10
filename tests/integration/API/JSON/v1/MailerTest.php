<?php
namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\v1\Mailer;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class MailerTest extends \MailPoetTest {
  function testItResumesSending() {
    // create mailer log with a "paused" status
    $mailer_log = ['status' => MailerLog::STATUS_PAUSED];
    MailerLog::updateMailerLog($mailer_log);
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->equals(MailerLog::STATUS_PAUSED);
    $settings = new SettingsController();
    $bridge = $this->makeEmpty(Bridge::class, ['checkAuthorizedEmailAddresses' => Expected::never()]);
    // resumeSending() method should clear the mailer log's status
    $mailer_endpoint = new Mailer($bridge, $settings);
    $response = $mailer_endpoint->resumeSending();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->null();
  }

  function testItRunsAuhtorizedEmailsCheckIfErrorIsPresent() {
    $settings = new SettingsController();
    $settings->set(Bridge::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING_NAME, ['invalid_sender_address' => 'a@b.c']);
    $bridge = $this->makeEmpty(Bridge::class, ['checkAuthorizedEmailAddresses' => Expected::once()]);
    $mailer_endpoint = new Mailer($bridge, $settings);
    $mailer_endpoint->resumeSending();
  }
}
