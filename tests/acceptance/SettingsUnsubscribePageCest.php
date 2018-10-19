<?php

namespace MailPoet\Test\Acceptance;

class SettingsUnsubscribePageCest {
  function previvewDefaultUnsubscribePage(\AcceptanceTester $I) {
    $I->wantTo('Preview default MailPoet Unsubscribe page from MP Settings page');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/div[2]/table/tbody/tr[6]/td/p/a']);
    $I->switchToNextTab();
    $I->waitForElement(['css'=>'.entry-title'], 20);
    $I->seeInCurrentUrl('&action=unsubscribe');
  }

}