<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditAutomaticWooCommerceEmailCest {
  function dontSeeWooCommerceTabWhenWooCommerceIsNotActive(\AcceptanceTester $I) {
    $I->wantTo('Not see WooCommerce tab');
    $I->deactivateWooCommerce();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->dontSee('[data-automation-id="tab-WooCommerce"]');
  }

  function editAutomaticWooCommerceEmail(\AcceptanceTester $I) {
    $newsletter_name = 'Edit Automatic WooCommerce Email Test';
    $newsletter_edited_name = 'Edit Automatic WooCommerce Email Test Edited';

    $newsletter_factory = new Newsletter();
    $newsletter_factory
      ->withSubject($newsletter_name)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->create();

    // open editation
    $I->wantTo('Edit automatic WooCommerce email');
    $I->activateWooCommerce();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-WooCommerce"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Edit');

    // edit subject
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_edited_name);

    // edit sending
    $I->click('Next');
    $I->waitForElementVisible('#field_sender_name');
    $I->fillField('#field_sender_name', 'Test sender');
    $I->click('Save as draft and close');

    // check update success
    $I->waitForText('Email was updated successfully!');
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-WooCommerce"]');
    $I->waitForText($newsletter_edited_name);
  }
}
