<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;

class SubscriptionFormCest {

  const CONFIRMATION_MESSAGE_TIMEOUT = 20;

  /** @var string */
  private $subscriberEmail;

  /** @var int|null */
  private $formId;

  public function __construct() {
    $this->subscriberEmail = 'test-form@example.com';
  }

  public function _before(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailSubject()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailEnabled()
      ->withCaptchaType(CaptchaConstants::TYPE_DISABLED);

    $formName = 'Subscription Acceptance Test Form';
    $formFactory = new Form();
    $this->formId = $formFactory->withName($formName)->create()->getId();

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

  public function subscriptionFormWidget(\AcceptanceTester $i) {
    $i->wantTo('Subscribe using form widget');

    $i->cli(['widget', 'add', 'mailpoet_form', 'sidebar-1', '2', "--form=$this->formId", '--title="Subscribe to Our Newsletter"']);
    //login to avoid time limit for subscribing
    $i->login();
    $i->amOnPage('/');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }

  public function subscriptionFormShortcode(\AcceptanceTester $i) {
    $i->wantTo('Subscribe using form shortcode');

    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->scrollTo('.mailpoet_submit');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
    $i->seeCurrentUrlEquals('/form-test/');
  }

  public function subscriptionFormIframe(\AcceptanceTester $i) {
    $i->wantTo('Subscribe using iframe form');

    $i->amOnPage('/form-test');
    $i->executeJS('window.scrollTo(0, document.body.scrollHeight);');
    $i->switchToIframe('#mailpoet_form_iframe');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->scrollTo('.mailpoet_submit');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }

  /**
   * @depends subscriptionFormWidget
   */
  public function subscriptionConfirmation(\AcceptanceTester $i) {
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->scrollTo('.mailpoet_submit');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');

    $i->checkEmailWasReceived('Confirm your subscription');
    $i->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $i->switchToIframe('#preview-html');
    $i->click('I confirm my subscription!');
    $i->switchToNextTab();
    $i->see('You have subscribed');
    $i->seeNoJSErrors();

    $i->amOnUrl(\AcceptanceTester::WP_URL);
    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($this->subscriberEmail);
    $i->see('Subscribed', Locator::contains('tr', $this->subscriberEmail));
  }

  public function subscriptionAfterDisablingConfirmation(\AcceptanceTester $i) {
    $i->wantTo('Disable sign-up confirmation then subscribe and see a different message');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->click('[data-automation-id="disable_signup_confirmation"]');
    $i->acceptPopup();
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->amOnPage('/form-test');
    $i->scrollTo('.mailpoet_form_iframe');
    $i->switchToIframe('#mailpoet_form_iframe');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText("You’ve been successfully subscribed to our newsletter!", self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }
}
