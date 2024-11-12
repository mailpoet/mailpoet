<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Test\DataFactories\CustomField;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group frontend
 */
class SubscriptionFormCest {

  const CONFIRMATION_MESSAGE_TIMEOUT = 20;
  const FORM_NAME = 'Subscription Acceptance Test Form';
  const FIRST_FORM_CLASS = 'first-form';

  /** @var string */
  private $subscriberEmail;

  /** @var int|null */
  private $formId;

  /** @var int|null */
  private $formIdWithCustomField;

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

    $formFactory = new Form();
    $this->formId = $formFactory->withName(self::FORM_NAME)->create()->getId();

    $customFieldFactory = new CustomField();
    $customField = $customFieldFactory
      ->withType(CustomFieldEntity::TYPE_CHECKBOX)
      ->withParams([
        'required' => '1',
        'values' => [['value' => 'Option 1', 'is_checked' => '']],
      ])
      ->create();
    $this->formIdWithCustomField = $formFactory
      ->withName(self::FORM_NAME)
      ->withCustomField($customField)
      ->create()
      ->getId();

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
    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'form-test-double',
      'post_title' => 'Form Test Double',
      'post_content' => '
        <div class="' . self::FIRST_FORM_CLASS . '">
          [mailpoet_form id="' . $this->formIdWithCustomField . '"]
        </div>
        <div class="second-form">
          [mailpoet_form id="' . $this->formIdWithCustomField . '"]
        </div>
      ',
      'post_status' => 'publish',
    ]);
  }

  public function subscriptionFormWidget(\AcceptanceTester $i) {
    $currentTheme = WPFunctions::get()->wpGetTheme();
    if ($currentTheme->get('Name') == 'Twenty Twenty-One') {
      $i->wantTo('Subscribe using form widget');

      $i->cli(['widget', 'add', 'mailpoet_form', 'sidebar-1', '2', "--form=$this->formId", '--title="Subscribe to Our Newsletter"']);
      //login to avoid time limit for subscribing
      $i->login();
      $i->amOnPage('/');
      $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
      $i->click('.mailpoet_submit');
      $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
      $i->seeNoJSErrors();
    } else {
      $i->comment('Skipping test as it depends on a non-block theme.');
    }
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

  public function subscriptionTheSameFormRenderedMultipleTimes(\AcceptanceTester $i) {
    $i->wantTo('See error message in the correct form');

    $i->amOnPage('/form-test-double');
    $firstFormButton = '.' . self::FIRST_FORM_CLASS . ' .mailpoet_submit';
    $i->scrollTo($firstFormButton);
    $i->click($firstFormButton);
    // Look for validation error for checkbox field
    $i->waitForText('This field is required.', 10, '.' . self::FIRST_FORM_CLASS . ' fieldset + span[class*="mailpoet_error_"]');
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

  public function subscriptionNewPageConfirmation(\AcceptanceTester $i) {
    $i->wantTo('Subscribe to a form and to see new page confirmation');
    $i->login();
    $i->amOnMailpoetPage('Forms');
    $i->clickItemRowActionByItemName(self::FORM_NAME, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->click('(//div[@class="components-radio-control__option"])[2]'); // Click Go to Page option
    $i->selectOption('.components-select-control__input', 'Sample Page');
    $i->saveFormInEditor();
    $i->amOnPage('/form-test');
    $i->executeJS('window.scrollTo(0, document.body.scrollHeight);');
    $i->switchToIframe('#mailpoet_form_iframe');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->scrollTo('.mailpoet_submit');
    $i->click('.mailpoet_submit');
    $i->waitForText('Sample Page');
  }
}
