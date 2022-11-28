<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

/**
 * @group woo
 */
class DeleteAutomaticWooCommerceEmailCest {
  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
  }

  public function trashAutomaticWooCommerceEmail(\AcceptanceTester $i) {
    $newsletterName = 'Trash Automatic WooCommerce Email Test';

    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($newsletterName)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->create();

    $i->wantTo('Trash automatic WooCommerce email');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-WooCommerce"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Move to trash');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
  }

  public function restoreTrashedAutomaticWooCommerceEmail(\AcceptanceTester $i) {
    $newsletterName = 'Restore Trashed Automatic WooCommerce Email Test';

    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($newsletterName)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withDeleted()
      ->create();

    $i->wantTo('Restore trashed automatic WooCommerce email');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-WooCommerce"]');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Restore');
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-WooCommerce"]');
    $i->waitForText($newsletterName);
  }

  public function deleteTrashedAutomaticWooCommerceEmail(\AcceptanceTester $i) {
    $newsletterName = 'Delete Trashed Automatic WooCommerce Email Test';

    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($newsletterName)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withDeleted()
      ->create();

    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($newsletterName . '2')
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withDeleted()
      ->create();

    $i->wantTo('Delete trashed automatic WooCommerce email');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-WooCommerce"]');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Delete Permanently');
    $i->waitForText('permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
    $i->waitForText($newsletterName . '2');
  }
}
