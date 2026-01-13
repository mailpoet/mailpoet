<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use PHPUnit\Framework\Assert;

/**
 * @group frontend
 */
class CaptchaSubscriptionCest {

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

  public function checkInlineCaptchaIsShownAfterSubscription(\AcceptanceTester $i) {
    $i->wantTo('See the built-in captcha inline within the form after subscribing');
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForElement('.mailpoet_captcha_container', 10);
    $i->seeElement('.mailpoet_captcha_container img.mailpoet_captcha');
    $i->seeElement('.mailpoet_captcha_container input[name="data[captcha]"]');
    $i->seeElement('.mailpoet_captcha_container .mailpoet_captcha_update');
    $i->seeElement('.mailpoet_captcha_container .mailpoet_captcha_audio');
    $i->see('Please fill in the CAPTCHA', '.mailpoet_validate_error');
    $i->seeNoJSErrors();
  }

  public function checkInlineCaptchaRefreshWorks(\AcceptanceTester $i) {
    $i->wantTo('Verify captcha image can be refreshed');
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForElement('.mailpoet_captcha_container', 10);

    // Get the original image src
    $originalSrc = $i->grabAttributeFrom('.mailpoet_captcha_container img.mailpoet_captcha', 'src');

    // Click refresh button
    $i->click('.mailpoet_captcha_container .mailpoet_captcha_update');
    $i->wait(1);

    // Verify image src has changed (cache busting parameter)
    $newSrc = $i->grabAttributeFrom('.mailpoet_captcha_container img.mailpoet_captcha', 'src');
    Assert::assertNotEquals($originalSrc, $newSrc, 'Captcha image should have changed after refresh');
    $i->seeNoJSErrors();
  }

  public function checkWrongCaptchaShowsError(\AcceptanceTester $i) {
    $i->wantTo('See error when entering wrong captcha');
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForElement('.mailpoet_captcha_container', 10);

    // Enter wrong captcha
    $i->fillField('.mailpoet_captcha_container input[name="data[captcha]"]', 'wrongcode');
    $i->click('.mailpoet_submit');
    $i->waitForText('The characters entered do not match with the previous CAPTCHA.', 10, '.mailpoet_validate_error');
    $i->seeNoJSErrors();
  }

  public function checkCaptchaIsNotShownToLoggedInUsers(\AcceptanceTester $i) {
    $i->wantTo('Check that captcha is not shown to logged in users');
    $i->login();
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
    $i->dontSeeElement('.mailpoet_captcha_container');
    $i->seeNoJSErrors();
  }
}
