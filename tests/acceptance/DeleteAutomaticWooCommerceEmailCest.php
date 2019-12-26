<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteAutomaticWooCommerceEmailCest {

  public function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
  }

  public function trashAutomaticWooCommerceEmail(\AcceptanceTester $I) {
    $newsletter_name = 'Trash Automatic WooCommerce Email Test';

    $newsletter_factory = new Newsletter();
    $newsletter_factory
      ->withSubject($newsletter_name)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->create();

    $I->wantTo('Trash automatic WooCommerce email');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-WooCommerce"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
  }

  public function restoreTrashedAutomaticWooCommerceEmail(\AcceptanceTester $I) {
    $newsletter_name = 'Restore Trashed Automatic WooCommerce Email Test';

    $newsletter_factory = new Newsletter();
    $newsletter_factory
      ->withSubject($newsletter_name)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withDeleted()
      ->create();

    $I->wantTo('Restore trashed automatic WooCommerce email');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-WooCommerce"]');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Restore');
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-WooCommerce"]');
    $I->waitForText($newsletter_name);
  }

  public function deleteTrashedAutomaticWooCommerceEmail(\AcceptanceTester $I) {
    $newsletter_name = 'Delete Trashed Automatic WooCommerce Email Test';

    $newsletter_factory = new Newsletter();
    $newsletter_factory
      ->withSubject($newsletter_name)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withDeleted()
      ->create();

    $I->wantTo('Delete trashed automatic WooCommerce email');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-WooCommerce"]');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Delete Permanently');
    $I->waitForText('permanently deleted.');
    $I->waitForElementNotVisible($newsletter_name);
  }
}
