<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Settings;

class CreateNewWordPressUserCest {

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
  }

  public function sendConfirmationEmail(\AcceptanceTester $i) {
    $i->wantTo('Create a new WP user and check if the confirmation email is sent and if user is subscribed properly');
    $this->settings->withConfirmationEmailEnabled();
    $this->settings->withSubscribeOnRegisterEnabled();
    $secondListName = 'Newsletter mailing list';
    $emailTitle = 'Confirm your subscription';

    // add additional list in settings
    $i->login();
    $i->amOnMailpoetPage('Settings');
    $i->waitForText('Settings');
    $i->selectOptionInReactSelect($secondListName, '[data-automation-id="subscribe-on_register-segments-selection"]');
    $i->click('[data-automation-id="settings-submit-button"]'); //save settings
    $i->waitForElementNotVisible('#mailpoet_loading');

    // create a wp user via registration
    // Note: Xpath used to avoid flakyness and to pass multisite testing where we have different registration page designs
    $i->logOut();
    $i->amOnUrl(\AcceptanceTester::WP_URL . '/wp-login.php?action=register');
    $i->fillField(['xpath' => "//input[@type='text'][contains(@name, 'user')]"], 'newuser');
    $i->fillField(['xpath' => "//input[@type='email'][contains(@name, 'email')]"], 'newuser@test.com');
    $i->click('#mailpoet_subscribe_on_register');
    $i->click(['xpath' => "//input[@type='submit']"]);

    // check email was received and confirm subscribing to both lists
    $i->checkEmailWasReceived($emailTitle);
    $i->click(Locator::contains('span.subject', $emailTitle));
    $i->switchToIframe('#preview-html');
    $i->click('Click here to confirm your subscription.');
    $i->switchToNextTab();
    $i->see('You have subscribed to');
    $i->seeNoJSErrors();

    // check if user is assigned to second list
    $i->amOnUrl(\AcceptanceTester::WP_URL . '/wp-admin');
    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText('Subscribers');
    $i->clickItemRowActionByItemName('newuser@test.com', 'Edit');
    $i->waitForText('Subscribed');
    $i->seeSelectedInSelect2($secondListName);
  }
}
