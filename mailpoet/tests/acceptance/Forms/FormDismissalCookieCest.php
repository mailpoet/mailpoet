<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group frontend
 */
class FormDismissalCookieCest {

  private const SUCCESS_MESSAGE = 'You’ve been successfully subscribed to our newsletter!';
  private const SUCCESS_MESSAGE_TIMEOUT = 20;

  public function _before() {
    (new Settings())
      ->withCaptchaType(CaptchaConstants::TYPE_DISABLED)
      ->withConfirmationEmailDisabled();
  }

  public function noDismissalCookieForShortcodeForm(AcceptanceTester $i) {
    $i->wantTo('Subscribe with a shortcode form and see no dismissal cookie');

    $segment = (new Segment())->withName('Shortcode form list')->create();
    $formId = (new Form())
      ->withName('Shortcode dismissal cookie form')
      ->withSegments([$segment])
      ->create()
      ->getId();
    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'shortcode-cookie-test',
      'post_title' => 'Shortcode Cookie Test',
      'post_content' => '[mailpoet_form id="' . $formId . '"]',
      'post_status' => 'publish',
    ]);

    // logged in to avoid the time limit between subscriptions
    $i->login();
    $i->amOnPage('/shortcode-cookie-test');
    $i->cantSeeCookie($this->getDismissalCookieName($formId));
    $i->fillField('[data-automation-id="form_email"]', 'shortcode-form@example.com');
    $i->scrollTo('.mailpoet_submit');
    $i->click('.mailpoet_submit');
    $i->waitForText(self::SUCCESS_MESSAGE, self::SUCCESS_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->cantSeeCookie($this->getDismissalCookieName($formId));
    $i->seeNoJSErrors();
  }

  public function dismissalCookieForPopupForm(AcceptanceTester $i) {
    $i->wantTo('Subscribe with a popup form and see the dismissal cookie');

    $segment = (new Segment())->withName('Popup form list')->create();
    $formId = (new Form())
      ->withName('Popup dismissal cookie form')
      ->withSegments([$segment])
      ->withDisplayAsPopup()
      ->create()
      ->getId();
    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'popup-cookie-test',
      'post_title' => 'Popup Cookie Test',
      'post_content' => 'Popup form test page',
      'post_status' => 'publish',
    ]);

    // logged in to avoid the time limit between subscriptions
    $i->login();
    $i->amOnPage('/popup-cookie-test');
    $i->waitForElementVisible('div.mailpoet_form_popup.active');
    $i->cantSeeCookie($this->getDismissalCookieName($formId));
    $i->fillField('[data-automation-id="form_email"]', 'popup-form@example.com');
    $i->click('.mailpoet_submit');
    $i->waitForText(self::SUCCESS_MESSAGE, self::SUCCESS_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->canSeeCookie($this->getDismissalCookieName($formId));
    $i->seeNoJSErrors();
  }

  private function getDismissalCookieName(?int $formId): string {
    return "popup_form_dismissed_$formId";
  }
}
