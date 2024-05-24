<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * @group frontend
 */
class BuiltInCaptchaSubscriptionCest {

  /** @var Settings */
  private $settingsFactory;

  /** @var string */
  private $subscriberEmail;

  /** @var int|null */
  private $formId;

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
    $this->formId = $formFactory->withName($formName)->create()->getId();

    $subscriberFactory = new Subscriber();
    $subscriberFactory->withEmail($this->subscriberEmail)->withCountConfirmations(1)->create();

    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'form-test',
      'post_title' => 'Form Test',
      'post_content' => '
        Regular form:
          [mailpoet_form id="' . $this->formId . '"]
        Iframe form:
          <iframe class="mailpoet_form_iframe" id="mailpoet_form_iframe" tabindex="0" src="http://test.local?mailpoet_form_iframe=1" width="100%" height="100%" frameborder="0" marginwidth="0" marginheight="0" scrolling="no"></iframe>
      ',
      'post_status' => 'publish',
    ]);
  }

  public function checkCaptchaPageExistsAfterSubscription(\AcceptanceTester $i) {
    $i->wantTo('See the built-in captcha after subscribing using form widget');
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText('Confirm you’re not a robot');
    $i->seeNoJSErrors();
  }

  public function checkCaptchaPageIsNotShownToLoggedInUsers(\AcceptanceTester $i) {
    $i->wantTo('check that captcha page is not shown to logged in users');
    $i->login();
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
    $i->seeNoJSErrors();
  }
}
