<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class MailerTest extends \MailPoetTest {
  public function testItResumesSending() {
    // create mailer log with a "paused" status
    $mailerLog = MailerLog::getMailerLog();
    $mailerLog['status'] = MailerLog::STATUS_PAUSED;
    MailerLog::updateMailerLog($mailerLog);
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
    $settings = SettingsController::getInstance();
    $authorizedEmailsController = $this->makeEmpty(AuthorizedEmailsController::class, ['checkAuthorizedEmailAddresses' => Expected::never()]);
    $senderDomainController = $this->diContainer->get(AuthorizedSenderDomainController::class);
    // resumeSending() method should clear the mailer log's status
    $bridge = new Bridge($settings);
    $mailerEndpoint = new Mailer($authorizedEmailsController, $settings, $bridge, $this->diContainer->get(MailerFactory::class), new MetaInfo, $senderDomainController);
    $response = $mailerEndpoint->resumeSending();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->null();
  }

  public function testItRunsAuhtorizedEmailsCheckIfErrorIsPresent() {
    $settings = SettingsController::getInstance();
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, ['invalid_sender_address' => 'a@b.c']);
    $authorizedEmailsController = $this->makeEmpty(AuthorizedEmailsController::class, ['checkAuthorizedEmailAddresses' => Expected::once()]);
    $senderDomainController = $this->diContainer->get(AuthorizedSenderDomainController::class);
    $bridge = new Bridge($settings);
    $mailerEndpoint = new Mailer($authorizedEmailsController, $settings, $bridge, $this->diContainer->get(MailerFactory::class), new MetaInfo, $senderDomainController);
    $mailerEndpoint->resumeSending();
  }
}
