<?php declare(strict_types = 1);

 namespace MailPoet\Test\Acceptance;

 use Codeception\Util\Locator;
 use MailPoet\Subscription\Captcha\CaptchaConstants;
 use MailPoet\Test\DataFactories\Form;
 use MailPoet\Test\DataFactories\Settings;

/**
 * @group frontend
 */
class ConfirmConfirmationPageCest {
  
    const CONFIRMATION_MESSAGE_TIMEOUT = 20;
    const FORM_NAME = 'Subscription Acceptance Test Form';

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

    $formFactory = new Form();
    $this->formId = $formFactory->withName(self::FORM_NAME)->create()->getId();

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

  public function confirmDefaultConfirmationPage(\AcceptanceTester $i) {
    $i->wantTo('Confirm link to default confirmation page works correctly');

    $siteTitle = get_bloginfo('name', 'raw');
    $pageTitle = 'MailPoetConfirmationPage';
    $postContent = 'BobsYourUncle';

    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->waitForText('MailPoet Page');
    $i->click('[data-automation-id="preview_page_link"]');
    $i->switchToNextTab();
    $i->see("You have subscribed to $siteTitle");

    $i->wantTo('See the new confirmation page shows up');
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle", "--post_content=$postContent"]);

    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->waitForText('MailPoet Page');
    $i->selectOption('[data-automation-id="page_selection"]', $pageTitle);
    $i->click('Save settings');
    $i->waitForText('Settings saved');

    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->scrollTo('.mailpoet_submit');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->checkEmailWasReceived('Confirm your subscription');
    $i->click(Locator::contains('span.subject', 'Confirm your subscription'));
    $i->switchToIframe('#preview-html');
    $i->click('Click here to confirm your subscription');

    $i->switchToNextTab();
    $i->waitForText($postContent);
    $i->seeNoJSErrors();
  }
}
