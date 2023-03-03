<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\WooCommerce\Helper;

/**
 * @group woo
 */
class EditorCouponCest {


  /** @var Settings */
  private $settings;

  /** @var Helper */
  private $wcHelper;

  public function _before() {
    $this->settings = new Settings();
    $this->wcHelper = ContainerWrapper::getInstance()->get(Helper::class);
  }

  public function addCoupon(\AcceptanceTester $i) {
    $couponInEditor = '[data-automation-id="coupon_block"]';
    $couponSettingsHeading = '[data-automation-id="coupon_settings_heading"]';
    $couponSettingsDone = '[data-automation-id="coupon_done_button"]';
    $footer = '[data-automation-id="footer"]';
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $emailSubject = 'Newsletter with Coupon';
    $this->settings->withCronTriggerMethod('Action Scheduler');

    $i->activateWooCommerce();

    $i->wantTo('Add coupon block to newsletter');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->withSubject($emailSubject)
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_coupon', '#mce_1');
    $i->waitForElementVisible($couponInEditor);
    $i->see(Coupon::CODE_PLACEHOLDER);
    $i->wantTo('Check coupon overlay');
    $i->moveMouseOver($footer, 3, 2);
    $i->moveMouseOver($couponInEditor, 3, 2);
    $i->waitForText('The coupon code will be auto-generated when this campaign is activated.');
    $i->wantTo('Open coupon settings panel');
    $i->click($couponInEditor);
    $i->waitForElement($couponSettingsHeading);
    $i->wantTo('Close coupon settings panel');
    $i->click($couponSettingsDone);
    $i->seeNoJSErrors();

    $i->wantTo('Send the email with coupon');
    $i->click('Next');
    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Choose list and send');
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');
    $i->waitForEmailSendingOrSent();
    $i->triggerMailPoetActionScheduler();

    $i->wantTo('Verify newsletter with generated coupon is received');
    $i->checkEmailWasReceived($emailSubject);
    $i->click('.msglist-message');
    $i->switchToIFrame("#preview-html");
    $i->waitForElementVisible('.mailpoet_coupon');
    $i->dontSee(Locator::contains('.mailpoet_coupon', Coupon::CODE_PLACEHOLDER));
  }

  public function addCouponAndSelectPredefinedCoupon(\AcceptanceTester $i) {
    $couponInEditor = '[data-automation-id="coupon_block"]';
    $couponSettingsHeading = '[data-automation-id="coupon_settings_heading"]';
    $couponSettingsDone = '[data-automation-id="coupon_done_button"]';
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $emailSubject = 'Newsletter with Coupon';
    $couponCode = 'some-coupon-code';
    $this->settings->withCronTriggerMethod('Action Scheduler');

    $i->activateWooCommerce();

    $i->wantTo('Add coupon block to newsletter');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->withSubject($emailSubject)
      ->create();

    $coupon = $this->wcHelper->createWcCoupon('');
    $coupon->set_code($couponCode);
    $coupon->set_amount(100);
    $discountType = current(array_keys($this->wcHelper->wcGetCouponTypes()));
    $coupon->set_discount_type($discountType);
    $coupon->save();

    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_coupon', '#mce_1');
    $i->waitForElementVisible($couponInEditor);
    $i->see(Coupon::CODE_PLACEHOLDER);
    $i->wantTo('Open coupon settings panel');
    $i->click($couponInEditor);
    $i->waitForElement($couponSettingsHeading);
    $i->wantTo('Select predefined coupon');
    $i->click(Locator::contains('button', 'All coupons'));
    $i->click(Locator::contains('label', $couponCode));
    $i->wantTo('Close coupon settings panel');
    $i->click($couponSettingsDone);
    $i->seeNoJSErrors();
    $i->canSee($couponCode);

    $i->wantTo('Send the email with coupon');
    $i->click('Next');
    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Choose list and send');
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');
    $i->waitForEmailSendingOrSent();
    $i->triggerMailPoetActionScheduler();

    $i->wantTo('Verify newsletter with generated coupon is received');
    $i->checkEmailWasReceived($emailSubject);
    $i->click('.msglist-message');
    $i->switchToIFrame("#preview-html");
    $i->waitForElementVisible('.mailpoet_coupon');
    $i->see($couponCode);
  }

  public function seeNoticeWhenCouponCantGenerateAndResumeSending(\AcceptanceTester $i) {
    $couponInEditor = '[data-automation-id="coupon_block"]';
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $emailSubject = 'Newsletter with Coupon';
    $this->settings->withCronTriggerMethod('Action Scheduler');

    $i->activateWooCommerce();

    $i->wantTo('Add coupon block to newsletter');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->withSubject($emailSubject)
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_coupon', '#mce_1');
    $i->waitForElementVisible($couponInEditor);
    $i->see(Coupon::CODE_PLACEHOLDER);

    $i->wantTo('Send the email with coupon');
    $i->click('Next');

    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Choose list and send');
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->deactivateWooCommerce();
    $i->click('Send');
    $i->waitForEmailSendingOrSent();
    $i->triggerMailPoetActionScheduler();
    $i->waitForText('WooCommerce is not active');
    $i->canSee('Resume', 'button');
    $i->reloadPage();
    $i->waitForListingItemsToLoad();
    $i->waitForText($emailSubject, 10, '.mailpoet-listing-title');
    $i->canSee($emailSubject, '.notice.error.notice-error');


    $i->wantTo('Resume sending and verify coupon is sent');
    $i->activateWooCommerce();
    $i->click(Locator::contains('button', 'Resume'));
    $i->waitForText('Confirm to proceed');
    $i->click('#mailpoet_alert_confirm');
    $i->triggerMailPoetActionScheduler();
    $i->wantTo('Verify newsletter with generated coupon is received');
    $i->checkEmailWasReceived($emailSubject);
    $i->click(Locator::contains('.msglist-message', $emailSubject));
    $i->switchToIFrame("#preview-html");
    $i->waitForElementVisible('.mailpoet_coupon');
    $i->dontSee(Locator::contains('.mailpoet_coupon', Coupon::CODE_PLACEHOLDER));
  }
}
