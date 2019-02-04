<?php

namespace MailPoet\Test\Acceptance;

class SettingsUnsubscribePageCest {
  function previvewDefaultUnsubscribePage(\AcceptanceTester $I) {
    $I->wantTo('Preview default MailPoet Unsubscribe page from MP Settings page');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="unsubscribe_page_preview_link"]');
    $I->switchToNextTab();
    $I->waitForElement(['css'=>'.entry-title']);
    $I->seeInCurrentUrl('&action=unsubscribe');
  }

}
