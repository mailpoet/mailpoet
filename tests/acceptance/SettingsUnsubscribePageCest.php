<?php

namespace MailPoet\Test\Acceptance;

class SettingsUnsubscribePageCest {
  public function previvewDefaultUnsubscribePage(\AcceptanceTester $i) {
    $i->wantTo('Preview default MailPoet Unsubscribe page from MP Settings page');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="unsubscribe_page_preview_link"]');
    $i->switchToNextTab();
    $i->waitForElement(['css' => '.entry-title']);
  }
}
