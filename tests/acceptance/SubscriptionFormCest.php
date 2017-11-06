<?php

namespace MailPoet\Test\Acceptance;

class SubscriptionFormCest {
  function _before(\AcceptanceTester $I) {
    // prevent throttling from consecutive subscriptions
    $I->loginAsAdmin();
  }

  function subscriptionFormWidget(\AcceptanceTester $I) {
    $I->cli('widget add mailpoet_form sidebar-1 2 --form=1 --title="Subscribe to Our Newsletter" --allow-root');
    $I->wantTo('Subscribe using form widget');

    $I->amOnPage('/');
    $I->fillField('input[title="Email"]', 'test-email@example.com');
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', 3, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
    $I->cli('widget reset sidebar-1 --allow-root');
  }

  function subscriptionFormIframe(\AcceptanceTester $I) {
    $I->wantTo('Subscribe using iframe form');

    $I->amOnPage('/form-test');
    $I->seeElement('iframe.mailpoet_form_iframe');
    $I->executeJS('jQuery(".mailpoet_form_iframe").attr("id", "iframe_form")');
    $I->switchToIframe('iframe_form');
    $I->fillField('input[title="Email"]', 'test-email@example.com');
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', 3, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
  }
}