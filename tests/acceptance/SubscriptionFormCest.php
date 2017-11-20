<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;

class SubscriptionFormCest {
  const CONFIRMATION_MESSAGE_TIMEOUT = 20;

  function __construct() {
    $this->subscriber_email = 'test-form@example.com';
  }

  function subscriptionFormWidget(\AcceptanceTester $I) {
    $I->wantTo('Subscribe using form widget');

    $I->cli('widget add mailpoet_form sidebar-1 2 --form=1 --title="Subscribe to Our Newsletter" --allow-root');

    $I->amOnPage('/');
    $I->fillField('[data-automation-id=\'form_email\']', $this->subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $I->seeNoJSErrors();

    $I->cli('widget reset sidebar-1 --allow-root');
  }

  function subscriptionFormIframe(\AcceptanceTester $I) {
    $I->wantTo('Subscribe using iframe form');

    $I->amOnPage('/form-test');
    $I->switchToIframe('mailpoet_form_iframe');
    $I->fillField('[data-automation-id=\'form_email\']', $this->subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
  }

  /**
   * @depends subscriptionFormWidget
   */
  function subscriptionConfirmation(\AcceptanceTester $I) {
    $I->amOnUrl('http://mailhog:8025');
    $I->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $I->switchToIframe('preview-html');
    $I->click('Click here to confirm your subscription');
    $I->switchToNextTab();
    $I->see('You have subscribed');
    $I->seeNoJSErrors();

    $I->amOnUrl('http://wordpress');
    $I->loginAsAdmin();
    $I->amOnMailpoetPage('Subscribers');
    $I->see('Subscribed', Locator::contains('tr', $this->subscriber_email));
  }

  function _after(\AcceptanceTester $I) {
    $I->cli('db query "TRUNCATE TABLE wp_mailpoet_subscriber_ips" --allow-root');
  }
}