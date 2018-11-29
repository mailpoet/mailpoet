<?php

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use Codeception\Util\Locator;
use Facebook\WebDriver\WebDriverKeys;
use MailPoet\Test\DataFactories\Form;

require_once __DIR__ . '/../DataFactories/Form.php';

class EnableAndDisableSignupConfirmationCest {  
  function disableSignupConfirmation(AcceptanceTester $I) {
    $I->wantTo('Disable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = false);
    $this->addFormWidget($I);
    $confirmation_emails_count = $this->countConfirmationEmails($I);
    $this->subscribeUsingWidgetForm($I);
    $this->seeConfirmationEmailsCountIs($I, $confirmation_emails_count);
  }

  function enableSignupConfirmation(AcceptanceTester $I) {
    $I->wantTo('Enable signup confirmation');
    $I->login();
    $this->setSignupConfirmationSetting($I, $enabled = true);
    $this->addFormWidget($I);
    $confirmation_emails_count = $this->countConfirmationEmails($I);
    $this->subscribeUsingWidgetForm($I);
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

  private function addFormWidget(AcceptanceTester $I) {
    $form_factory = new Form();
    $form = $form_factory->withName('Confirmation Form')->create();
    $I->cli('widget reset sidebar-1 --allow-root');
    $I->cli('widget add mailpoet_form sidebar-1 2 --form=' . $form->id . ' --title="Subscribe to Our Newsletter" --allow-root');
  }

  private function subscribeUsingWidgetForm(AcceptanceTester $I) {
    $I->amOnUrl(AcceptanceTester::WP_URL);
    $I->fillField('[data-automation-id="form_email"]', 'test-confirmation@example.com');
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', 30, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
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
