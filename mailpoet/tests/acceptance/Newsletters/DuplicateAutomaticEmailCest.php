<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

/**
 * @group woo
 */
class DuplicateAutomaticEmailCest {
  public function duplicateAutomaticEmail(\AcceptanceTester $i) {
    $i->wantTo('Duplicate an automatic email');
    $i->activateWooCommerce();
    $emailSubject = 'Duplicate Automatic Email Test';
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($emailSubject)->withAutomaticTypeWooCommerceFirstPurchase()->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-WooCommerce"]');
    $i->waitForText($emailSubject);
    $i->clickItemRowActionByItemName($emailSubject, 'Duplicate');
    $i->waitForText('Email "Copy of ' . $emailSubject . '" has been duplicated.', 20);
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName('Copy of ' . $emailSubject, 'Edit');
    $i->waitForElement('[data-automation-id="newsletter_title"]', 20);
  }
}
