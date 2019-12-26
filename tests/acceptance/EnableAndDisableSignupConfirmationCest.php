<?php

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use MailPoet\Test\DataFactories\Settings;

class EnableAndDisableSignupConfirmationCest {

  public function disableSignupConfirmation(AcceptanceTester $I) {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailEnabled()
      ->withConfirmationEmailSubject('Disable signup confirmation subject');
    $I->wantTo('Disable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = false);
    $I->createFormAndSubscribe();
    $I->amOnUrl(\AcceptanceTester::MAIL_URL);
    $I->dontSee('Disable signup confirmation subject');
  }

  public function enableSignupConfirmation(AcceptanceTester $I) {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailDisabled()
      ->withConfirmationEmailSubject('Enable signup confirmation subject');
    $I->wantTo('Enable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = true);
    $I->createFormAndSubscribe();
    $I->amOnUrl(\AcceptanceTester::MAIL_URL);
    $I->waitForText('Enable signup confirmation subject');
    $I->see('Enable signup confirmation subject');
  }

  private function setSignupConfirmationSetting(AcceptanceTester $I, $enabled) {
    $choice_selector = $enabled ?
      '[data-automation-id="enable_signup_confirmation"]' :
      '[data-automation-id="disable_signup_confirmation"]';
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');
    $I->click($choice_selector);
    $I->acceptPopup();
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');
  }
}
