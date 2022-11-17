<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

/**
 * @group woo
 */
class EditAutomaticWooCommerceEmailCest {
  public function dontSeeWooCommerceTabWhenWooCommerceIsNotActive(\AcceptanceTester $i) {
    $i->wantTo('Not see WooCommerce tab');
    $i->deactivateWooCommerce();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->dontSee('[data-automation-id="tab-WooCommerce"]');
  }

  public function editAutomaticWooCommerceEmail(\AcceptanceTester $i) {
    $newsletterName = 'Edit Automatic WooCommerce Email Test';
    $newsletterEditedName = 'Edit Automatic WooCommerce Email Test Edited';

    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($newsletterName)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->create();

    // open editation
    $i->wantTo('Edit automatic WooCommerce email');
    $i->activateWooCommerce();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-WooCommerce"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Edit');

    // edit subject
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->seeInCurrentUrl('mailpoet-newsletter-editor');
    $i->fillField($titleElement, $newsletterEditedName);

    // edit sending
    $i->click('Next');
    $i->waitForElementVisible('#field_sender_name');
    $i->fillField('#field_sender_name', 'Test sender');
    $i->click('Save as draft and close');

    // check update success
    $i->waitForText('Email was updated successfully!');
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-WooCommerce"]');
    $i->waitForText($newsletterEditedName);
  }
}
