<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;

require_once __DIR__ . '/../DataFactories/Settings.php';
require_once __DIR__ . '/../DataFactories/Form.php';

class SubscriptionFormCest {

  const CONFIRMATION_MESSAGE_TIMEOUT = 20;

  /** @var string */
  private $subscriber_email;

  function __construct() {
    $this->subscriber_email = 'test-form@example.com';
  }

  function _before() {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailSubject()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailEnabled();
  }

  function subscriptionFormWidget(\AcceptanceTester $I) {
    $form_name = 'Subscription Acceptance Test Form';
    $form_factory = new Form();
    $form = $form_factory->withName($form_name)->create();
    $I->wantTo('Subscribe using form widget');

    $I->cli('widget add mailpoet_form sidebar-1 2 --form=' . $form->id . ' --title="Subscribe to Our Newsletter" --allow-root');
    //login to avoid time limit for subscribing
    $I->login();
    $I->amOnPage('/');
    $I->fillField('[data-automation-id="form_email"]', $this->subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $I->seeNoJSErrors();

    $I->cli('widget reset sidebar-1 --allow-root');
  }

  function subscriptionFormShortcode(\AcceptanceTester $I) {
    $I->wantTo('Subscribe using form shortcode');

    $I->amOnPage('/form-test');
    $I->fillField('[data-automation-id="form_email"]', $this->subscriber_email);
    $I->scrollTo('.mailpoet_submit');
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
    $I->seeCurrentUrlEquals('/form-test/');
  }

  function subscriptionFormIframe(\AcceptanceTester $I) {
    $I->wantTo('Subscribe using iframe form');

    $I->amOnPage('/form-test');
    $I->switchToIframe('mailpoet_form_iframe');
    $I->fillField('[data-automation-id="form_email"]', $this->subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
  }

  /**
   * @depends subscriptionFormWidget
   */
  function subscriptionConfirmation(\AcceptanceTester $I) {
    $I->amOnMailboxAppPage();
    $I->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $I->switchToIframe('preview-html');
    $I->click('Click here to confirm your subscription');
    $I->switchToNextTab();
    $I->see('You have subscribed');
    $I->seeNoJSErrors();

    $I->amOnUrl(\AcceptanceTester::WP_URL);
    $I->login();
    $I->amOnMailpoetPage('Subscribers');
    $I->waitForText($this->subscriber_email);
    $I->see('Subscribed', Locator::contains('tr', $this->subscriber_email));
  }

  function subscriptionAfterDisablingConfirmation(\AcceptanceTester $I) {
    $I->wantTo('Disable sign-up confirmation then subscribe and see a different message');
    $I->login();

    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');
    $I->click('[data-automation-id="disable_signup_confirmation"]');
    $I->acceptPopup();
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');

    $I->amOnPage('/form-test');
    $I->switchToIframe('mailpoet_form_iframe');
    $I->fillField('[data-automation-id="form_email"]', $this->subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText("You’ve been successfully subscribed to our newsletter!", self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
  }

  function _after(\AcceptanceTester $I) {
    $I->cli('db query "TRUNCATE TABLE mp_mailpoet_subscriber_ips" --allow-root');
  }
}
