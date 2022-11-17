<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use MailPoet\Test\DataFactories\Settings;

class EnableAndDisableSignupConfirmationCest {
  public function disableSignupConfirmation(AcceptanceTester $i) {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailEnabled()
      ->withConfirmationEmailSubject('Disable signup confirmation subject');
    $i->wantTo('Disable signup confirmation');
    $i->login();
    $this->setSignupConfirmationSetting($i, $enabled = false);
    $i->createFormAndSubscribe();
    $i->amOnUrl(\AcceptanceTester::MAIL_URL);
    $i->dontSee('Disable signup confirmation subject');
  }

  public function enableSignupConfirmation(AcceptanceTester $i) {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailDisabled()
      ->withConfirmationEmailSubject('Enable signup confirmation subject');
    $i->wantTo('Enable signup confirmation');
    $i->login();
    $this->setSignupConfirmationSetting($i, $enabled = true);
    $i->createFormAndSubscribe();
    $i->amOnUrl(\AcceptanceTester::MAIL_URL);
    $i->waitForText('Enable signup confirmation subject');
    $i->see('Enable signup confirmation subject');
  }

  private function setSignupConfirmationSetting(AcceptanceTester $i, $enabled) {
    $choiceSelector = $enabled ?
      '[data-automation-id="enable_signup_confirmation"]' :
      '[data-automation-id="disable_signup_confirmation"]';
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->click($choiceSelector);
    $i->acceptPopup();
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
  }
}
