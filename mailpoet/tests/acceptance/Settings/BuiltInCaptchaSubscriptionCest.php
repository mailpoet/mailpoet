<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class BuiltInCaptchaSubscriptionCest {

  /** @var Settings */
  private $settingsFactory;

  /** @var string */
  private $subscriberEmail;

  public function _before(\AcceptanceTester $i) {
    $this->subscriberEmail = 'test-form@example.com';
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withCaptchaType(CaptchaConstants::TYPE_BUILTIN);
    $this->settingsFactory
      ->withConfirmationEmailSubject()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailEnabled();

    $formName = 'Subscription Acceptance Test Form';
    $formFactory = new Form();
    $form = $formFactory->withName($formName)->create();

    $subscriberFactory = new Subscriber();
    $subscriberFactory->withEmail($this->subscriberEmail)->withCountConfirmations(1)->create();

    $i->cli(['widget', 'add', 'mailpoet_form', 'sidebar-1', '2', "--form={$form->getId()}", '--title="Subscribe to Our Newsletter"']);
  }

  public function checkCaptchaPageExistsAfterSubscription(\AcceptanceTester $i) {
    $i->wantTo('See the built-in captcha after subscribing using form widget');
    $i->amOnPage('/');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText('Confirm you’re not a robot');
    $i->seeNoJSErrors();
  }

  public function checkCaptchaPageIsNotShownToLoggedInUsers(\AcceptanceTester $i) {
    $i->wantTo('check that captcha page is not shown to logged in users');
    $i->login();
    $i->amOnPage('/');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
    $i->seeNoJSErrors();
  }
}
