<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Test\DataFactories\Newsletter;

/**
 * @group woo
 */
class EditorAbandonedCartBlockCest {
  public function addAbandonedCart(\AcceptanceTester $i) {
    $i->wantTo('add abandoned cart block to newsletter');

    $abandonedCartOverlayMessage = '.mailpoet_overlay_message';
    $abandonedCartOverlay = '[data-automation-id="acc_overlay"]';
    $overlayMessage = 'This is only a preview! Your customers will see the product(s) they left in their shopping cart.';
    $settingsPanelElement = '.mailpoet_panel_wrapper';
    $settingsTitle = 'Display options';
    $footer = '[data-automation-id="footer"]';

    // Prepare newsletter without AC block
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->withAutomationTransactionalTypeWooCommerceAbandonedCart()
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();

    $i->login();
    $i->activateWooCommerce();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $newsletter->getId());
    $i->waitForElement('#mailpoet_editor_content');
    $i->waitForElementNotVisible('.velocity-animating');

    // Move the Abandoned Cart block to the editor
    $i->dragAndDrop('#automation_editor_block_abandoned_cart_content', '#mce_1');

    // Open settings by clicking on the block
    $i->moveMouseOver($footer, 3, 2);
    $i->moveMouseOver($abandonedCartOverlay, 3, 2);
    $i->waitForElementVisible($abandonedCartOverlayMessage);
    $i->see($overlayMessage, $abandonedCartOverlayMessage);
    $i->click($abandonedCartOverlayMessage);
    $i->waitForElementVisible($settingsPanelElement);
    $i->see($settingsTitle);
  }
}
