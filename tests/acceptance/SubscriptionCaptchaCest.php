<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Subscription\Captcha;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriptionCaptchaCest {

  /** @var Settings */
  private $settings_factory;

  /** @var string */
  private $subscriber_email;

  function _before(\AcceptanceTester $I) {
    $this->subscriber_email = 'test-form@example.com';
    $this->settings_factory = new Settings();
    $this->settings_factory->withCaptchaType(Captcha::TYPE_BUILTIN);
    $this->settings_factory
      ->withConfirmationEmailSubject()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailEnabled();

    $form_name = 'Subscription Acceptance Test Form';
    $form_factory = new Form();
    $form = $form_factory->withName($form_name)->create();

    $subscriber_factory = new Subscriber();
    $subscriber_factory->withEmail($this->subscriber_email)->withCountConfirmations(1)->create();

    $I->cli(['widget', 'add', 'mailpoet_form', 'sidebar-1', '2', "--form=$form->id", '--title=Subscribe to Our Newsletter', '--allow-root']);
  }

  function checkCaptchaPageExistsAfterSubscription(\AcceptanceTester $I) {
    $I->wantTo('See the built-in captcha after subscribing using form widget');
    $I->amOnPage('/');
    $I->fillField('[data-automation-id="form_email"]', $this->subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText('Confirm you’re not a robot');
    $I->seeNoJSErrors();
  }

  function checkCaptchaPageIsNotShownToLoggedInUsers(\AcceptanceTester $I) {
    $I->wantTo('check that captcha page is not shown to logged in users');
    $I->login();
    $I->amOnPage('/');
    $I->fillField('[data-automation-id="form_email"]', $this->subscriber_email);
    $I->click('.mailpoet_submit');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.');
    $I->seeNoJSErrors();
  }
}
