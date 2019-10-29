<?php

namespace MailPoet\Test\Services;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class AuthorizedEmailsControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
  }

  function testItResetsAuthorisedEmailsErrorIfMssIsNotActive() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, 'Error');
    $controller = $this->getController($authorized_emails_from_api = null);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  function testItSetsProperErrorForOldUsers() {
    $this->settings->set('installed_at', '2018-03-04');
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com']);
  }

  function testItSetsProperErrorForInvalidDefaultSender() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com']);
  }

  function testItSetEmptyErrorWhenDefaultSenderAddressIsCorrect() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  function testItSetErrorForScheduledNewsletterWithUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_SCHEDULED);
  }

  function testItSetErrorForActiveWelcomeEmailUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(Newsletter::TYPE_WELCOME, Newsletter::STATUS_ACTIVE);
  }

  function testItSetErrorForPostNotificationUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(Newsletter::TYPE_NOTIFICATION, Newsletter::STATUS_ACTIVE);
  }

  function testItSetErrorForAutomaticEmailUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_ACTIVE);
  }

  function testItResetErrorWhenAllSendersAreCorrect() {
    $newsletter = Newsletter::createOrUpdate([
      'subject' => 'Subject',
      'status' => Newsletter::STATUS_ACTIVE,
      'type' => Newsletter::TYPE_AUTOMATIC,
    ]);
    $newsletter->sender_address = 'auth@email.com';
    $newsletter->save();
    $newsletter2 = Newsletter::createOrUpdate([
      'subject' => 'Subject2',
      'status' => Newsletter::STATUS_SCHEDULED,
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $newsletter2->sender_address = 'auth@email.com';
    $newsletter2->save();
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    expect($error)->null();
  }

  function testItResetsUnauthorizedErrorInMailerLog() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect($error)->null();
  }

  function testItDoesNotResetOtherErrorInMailerLog() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_SEND, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect($error['operation'])->equals(MailerError::OPERATION_SEND);
  }

  function testItDoesNotResetMailerLogItErrorPersists() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect($error['operation'])->equals(MailerError::OPERATION_AUTHORIZATION);
  }

  private function checkUnauthorizedInNewsletter($type, $status) {
    $newsletter = Newsletter::createOrUpdate([
      'subject' => 'Subject',
      'status' => $status,
      'type' => $type,
    ]);
    $newsletter->sender_address = 'invalid@email.com';
    $newsletter->save();
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    expect(count($error['invalid_senders_in_newsletters']))->equals(1);
    expect($error['invalid_senders_in_newsletters'][0]['newsletter_id'])->equals($newsletter->id);
    expect($error['invalid_senders_in_newsletters'][0]['sender_address'])->equals('invalid@email.com');
    expect($error['invalid_senders_in_newsletters'][0]['subject'])->equals('Subject');
  }

  private function setMailPoetSendingMethod() {
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
    );
  }

  private function getController($authorized_emails) {
    if ($authorized_emails === null) {
      $get_emails_expectaton = Expected::never();
    } else {
      $get_emails_expectaton = Expected::once($authorized_emails);
    }
    $bridge_mock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => $get_emails_expectaton]);
    return new AuthorizedEmailsController($this->settings, $bridge_mock);
  }

  function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
  }
}
