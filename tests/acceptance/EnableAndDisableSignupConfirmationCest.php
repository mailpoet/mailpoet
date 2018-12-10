<?php

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use Codeception\Util\Locator;

class EnableAndDisableSignupConfirmationCest {
  function disableSignupConfirmation(AcceptanceTester $I) {
    $I->wantTo('Disable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = false);
    $confirmation_emails_count = $this->countConfirmationEmails($I);
    $I->createFormAndSubscribe();
    $this->seeConfirmationEmailsCountIs($I, $confirmation_emails_count);
  }

  function enableSignupConfirmation(AcceptanceTester $I) {
    $I->wantTo('Enable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = true);
    $confirmation_emails_count = $this->countConfirmationEmails($I);
    $I->createFormAndSubscribe();
    $this->seeConfirmationEmailsCountIs($I, $confirmation_emails_count + 1);
  }

  function _after(AcceptanceTester $I) {
    $I->cli('widget reset sidebar-1 --allow-root');
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
  }

  private function countConfirmationEmails(AcceptanceTester $I) {
    $I->amOnUrl(AcceptanceTester::MAIL_URL);
    $subjects = $I->grabMultiple('span.subject');
    $confirmation_emails = array_filter($subjects, function($subject) {
      return strpos($subject, 'Confirm your subscription') !== false;
    });
    return count($confirmation_emails);
  }

  private function seeConfirmationEmailsCountIs(AcceptanceTester $I, $n) {
    $I->amOnUrl(AcceptanceTester::MAIL_URL);
    $I->seeNumberOfElements(Locator::contains('span.subject', 'Confirm your subscription'), $n);
  }
}
