<?php

namespace MailPoet\Test\Acceptance;

class SubscriptionFormCest {
  const CONFIRMATION_MESSAGE_TIMEOUT = 10;

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

  function _after(\AcceptanceTester $I) {
    $I->cli('db query "TRUNCATE TABLE wp_mailpoet_subscriber_ips" --allow-root');
  }
}