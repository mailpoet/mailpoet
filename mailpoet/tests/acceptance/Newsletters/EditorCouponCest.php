<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Newsletter;

/**
 * @group woo
 */
class EditorCouponCest {
  public function _before() {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_COUPON_BLOCK);
  }

  public function addCoupon(\AcceptanceTester $i) {
    $couponInEditor = '[data-automation-id="coupon_block"]';
    $couponSettingsHeading = '[data-automation-id="coupon_settings_heading"]';
    $couponSettingsDone = '[data-automation-id="coupon_done_button"]';
    $footer = '[data-automation-id="footer"]';

    $i->activateWooCommerce();

    $i->wantTo('Add coupon block to newsletter');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
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
    $i->waitForElementNotVisible($couponSettingsHeading);
  }
}
