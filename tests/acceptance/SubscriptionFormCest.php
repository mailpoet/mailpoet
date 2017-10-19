<?php

namespace MailPoet\Test\Acceptance;

class SubscriptionFormCest
{
  function _before(\AcceptanceTester $I) {
    $I->cli('widget add mailpoet_form sidebar-1 2 --form=1 --title="Subscribe to Our Newsletter" --allow-root');
  }

  // tests
  function subscriptionFormWidgetSubscribe(\AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->fillField('input[title="Email"]', 'test-email@example.com');
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', 3, '.mailpoet_validate_success');
  }

  function _after(\AcceptanceTester $I) {
    $I->cli('widget reset sidebar-1 --allow-root');
  }
}
