<?php
namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\v1\Mailer;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Services\AuthorizedEmailsController;
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
    $authorized_emails_controller = $this->makeEmpty(AuthorizedEmailsController::class, ['checkAuthorizedEmailAddresses' => Expected::never()]);
    // resumeSending() method should clear the mailer log's status
    $bridge = new Bridge($settings);
    $mailer_endpoint = new Mailer($authorized_emails_controller, $settings, $bridge, new MetaInfo);
    $response = $mailer_endpoint->resumeSending();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->null();
  }

  function testItRunsAuhtorizedEmailsCheckIfErrorIsPresent() {
    $settings = new SettingsController();
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, ['invalid_sender_address' => 'a@b.c']);
    $authorized_emails_controller = $this->makeEmpty(AuthorizedEmailsController::class, ['checkAuthorizedEmailAddresses' => Expected::once()]);
    $bridge = new Bridge($settings);
    $mailer_endpoint = new Mailer($authorized_emails_controller, $settings, $bridge, new MetaInfo);
    $mailer_endpoint->resumeSending();
  }
}
