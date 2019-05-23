<?php

namespace MailPoet\Test\Services;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class AuthorizedEmailsControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController();
  }

  function testItResetsAuthorisedEmailsErrorIfMssIsNotActive() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, 'Error');
    $controller = $this->getController($authorized_emails_from_api = null);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  function testItResetsAuthorisedEmailsErrorIfIntalationDateIsOlderThanAuthEmailsFeature() {
    $this->settings->set('installed_at', '2018-03-04');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = null);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  function testItSetProperErrorForInvalidDefaultSender() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->settings->set('signup_confirmation.from.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com']);
  }

  function testItSetProperErrorForInvalidConfirmationSender() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->settings->set('signup_confirmation.from.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_confirmation_address' => 'invalid@email.com']);
  }

  function testItSetProperErrorForConfirmationAddressAndDefaultSender() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->settings->set('signup_confirmation.from.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com', 'invalid_confirmation_address' => 'invalid@email.com']);
  }

  function testItSetEmptyErrorWhenBothAdressesAreCorrect() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->settings->set('signup_confirmation.from.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorized_emails_from_api = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
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
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
