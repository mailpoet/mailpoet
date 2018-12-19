<?php

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use Codeception\Util\Locator;

class EnableAndDisableSignupConfirmationCest {

  function removeAllEmails(AcceptanceTester $I) {
    // Remove all mails, because when there is more mails than paging allows it causes
    // problems with counting ones, which would be moved to other page after adding more mails
    $I->amOnMailboxAppPage();
    $I->waitForElement(Locator::contains('a', 'Delete all messages'), 10);
    $I->click(Locator::contains('a', 'Delete all messages'));
    $I->waitForElement('.modal-footer');
    $I->wait(2); // Wait for modal fade-in animation to finish
    $I->click(Locator::contains('.btn', 'Delete all messages'));
    $I->waitForElementNotVisible('.modal');
  }

  function disableSignupConfirmation(AcceptanceTester $I) {
    $I->wantTo('Disable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = false);
    $confirmation_emails_count = $this->countConfirmationEmails($I);
    $I->createFormAndSubscribe();
    $this->seeConfirmationEmailsCountIs($I, $confirmation_emails_count);
    $I->cli('widget reset sidebar-1 --allow-root');
  }

  function enableSignupConfirmation(AcceptanceTester $I) {
    $I->wantTo('Enable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = true);
    $confirmation_emails_count = $this->countConfirmationEmails($I);
    $I->createFormAndSubscribe();
    $this->seeConfirmationEmailsCountIs($I, $confirmation_emails_count + 1);
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
    $I->amOnMailboxAppPage();
    $confirmation_emails = $I->grabMultiple(Locator::contains('span.subject', 'Confirm your subscription'));
    return count($confirmation_emails);
  }

  private function seeConfirmationEmailsCountIs(AcceptanceTester $I, $n) {
    $I->amOnMailboxAppPage();
    $I->seeNumberOfElements(Locator::contains('span.subject', 'Confirm your subscription'), $n);
  }
}
