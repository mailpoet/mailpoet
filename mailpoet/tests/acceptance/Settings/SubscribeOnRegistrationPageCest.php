<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Segment;

class SubscribeOnRegistrationPageCest {
  public function allowSubscribeOnRegistrationPage(\AcceptanceTester $i) {
    $i->wantTo('Allow users to subscribe to lists on site registration page');
    //create a list for this test
    $segmentFactory = new Segment();
    $regseg = 'RegistrationPageSignup';
    $segment1 = $segmentFactory->withName($regseg)->create();
    $regpageuseremail = 'registerpagesignup@fake.fake';
    $i->login();
    //Go to settings
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="subscribe-on_register-checkbox"]');
    $i->selectOptionInReactSelect($regseg, '[data-automation-id="subscribe-on_register-segments-selection"]');
    //save settings
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->logOut();
    $i->amOnPage('/wp-login.php?action=register');
    $i->waitForElement(['css' => '.registration-form-mailpoet']);
    if (!getenv('MULTISITE')) {
      $i->fillField(['name' => 'user_login'], 'registerpagesignup');
      $i->fillField(['name' => 'user_email'], $regpageuseremail);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('#wp-submit');
      $i->waitForText('Registration complete. Please check your email');
    } else {
      $i->fillField(['name' => 'user_name'], 'muregisterpagesignup');
      $i->fillField(['name' => 'user_email'], $regpageuseremail);
      $i->scrollTo(['css' => '#mailpoet_subscribe_on_register']);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('Next');
      $i->waitForText('muregisterpagesignup is your new username');
    }
    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForText('registerpagesignup@fake.fake');
    $i->clickItemRowActionByItemName($regpageuseremail, 'Edit');
    $i->waitForText($regseg);
  }

  public function sendConfirmationEmailOnRegistration(\AcceptanceTester $i) {
    $i->wantTo('send confirmation email on user registration when no additional lists');
    $userEmail = 'registerpagesignupconfirmation@fake.fake';
    $i->login();
    //Go to settings
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="subscribe-on_register-checkbox"]');
    //save settings
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->logOut();
    $i->amOnPage('/wp-login.php?action=register');
    $i->waitForElement(['css' => '.registration-form-mailpoet']);
    if (!getenv('MULTISITE')) {
      $i->fillField(['name' => 'user_login'], 'registerpagesignup');
      $i->fillField(['name' => 'user_email'], $userEmail);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('#wp-submit');
      $i->waitForText('Registration complete. Please check your email');
    } else {
      $i->fillField(['name' => 'user_name'], 'muregisterpagesignupconfirmation');
      $i->fillField(['name' => 'user_email'], $userEmail);
      $i->scrollTo(['css' => '#mailpoet_subscribe_on_register']);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('Next');
      $i->waitForText('muregisterpagesignupconfirmation is your new username');
    }
    $i->checkEmailWasReceived('Confirm your subscription');
    $i->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $i->switchToIframe('#preview-html');
    $i->click('Click here to confirm your subscription.');
    $i->switchToNextTab();
    $i->see('You have subscribed');
    $i->seeNoJSErrors();
  }
}
