<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Segment;

class SubscribeOnRegistrationPageCest {
  public function allowSubscribeOnRegistrationPage(\AcceptanceTester $I) {
    $I->wantTo('Allow users to subscribe to lists on site registration page');
    //create a list for this test
    $segment_factory = new Segment();
    $regseg = 'RegistrationPageSignup';
    $segment1 = $segment_factory->withName($regseg)->create();
    $regpageuseremail = 'registerpagesignup@fake.fake';
    $I->login();
    //Go to settings
    $I->amOnMailPoetPage('Settings');
    $I->checkOption('#settings[subscribe_on_register]');
    $I->selectOptionInSelect2($regseg, '#mailpoet_subscribe_in_form input.select2-search__field');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->logOut();
    $I->amOnPage('/wp-login.php?action=register');
    $I->waitForElement(['css' => '.registration-form-mailpoet']);
    if (!getenv('MULTISITE')) {
      $I->fillField(['name' => 'user_login'], 'registerpagesignup');
      $I->fillField(['name' => 'user_email'], $regpageuseremail);
      $I->checkOption('#mailpoet_subscribe_on_register');
      $I->click('#wp-submit');
      $I->waitForText('Registration complete. Please check your email.');
    } else {
      $I->fillField(['name' => 'user_name'], 'muregisterpagesignup');
      $I->fillField(['name' => 'user_email'], $regpageuseremail);
      $I->scrollTo(['css' => '#mailpoet_subscribe_on_register']);
      $I->checkOption('#mailpoet_subscribe_on_register');
      $I->click('Next');
      $I->waitForText('muregisterpagesignup is your new username');
    }
    $I->login();
    $I->amOnMailPoetPage('Subscribers');
    $I->waitForText('registerpagesignup@fake.fake');
    $I->clickItemRowActionByItemName($regpageuseremail, 'Edit');
    $I->waitForText($regseg);
  }

  public function sendConfirmationEmailOnRegistration(\AcceptanceTester $I) {
    $I->wantTo('send confirmation email on user registration when no additional lists');
    $user_email = 'registerpagesignupconfirmation@fake.fake';
    $I->login();
    //Go to settings
    $I->amOnMailPoetPage('Settings');
    $I->checkOption('#settings[subscribe_on_register]');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->logOut();
    $I->amOnPage('/wp-login.php?action=register');
    $I->waitForElement(['css' => '.registration-form-mailpoet']);
    if (!getenv('MULTISITE')) {
      $I->fillField(['name' => 'user_login'], 'registerpagesignup');
      $I->fillField(['name' => 'user_email'], $user_email);
      $I->checkOption('#mailpoet_subscribe_on_register');
      $I->click('#wp-submit');
      $I->waitForText('Registration complete. Please check your email.');
    } else {
      $I->fillField(['name' => 'user_name'], 'muregisterpagesignupconfirmation');
      $I->fillField(['name' => 'user_email'], $user_email);
      $I->scrollTo(['css' => '#mailpoet_subscribe_on_register']);
      $I->checkOption('#mailpoet_subscribe_on_register');
      $I->click('Next');
      $I->waitForText('muregisterpagesignupconfirmation is your new username');
    }
    $I->amOnMailboxAppPage();
    $I->waitForElement(Locator::contains('span.subject', 'Confirm your subscription'));
    $I->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $I->switchToIframe('preview-html');
    $I->click('I confirm my subscription!');
    $I->switchToNextTab();
    if (!getenv('MULTISITE')) {
      $I->see('You have subscribed');
    } else {
      $I->see('You are now subscribed');
    }
    $I->seeNoJSErrors();
  }
}

