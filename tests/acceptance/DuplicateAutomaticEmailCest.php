<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DuplicateAutomaticEmailCest {
  function duplicateAutomaticEmail(\AcceptanceTester $I) {
    $I->wantTo('Duplicate an automatic email');
    $I->activateWooCommerce();
    $email_subject = 'Duplicate Automatic Email Test';
    $newsletter_factory = new Newsletter();
    $newsletter_factory->withSubject($email_subject)->withAutomaticTypeWooCommerceFirstPurchase()->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-WooCommerce"]');
    $I->waitForText($email_subject);
    $I->clickItemRowActionByItemName($email_subject, 'Duplicate');
    $I->waitForText('Email "Copy of ' . $email_subject . '" has been duplicated.', 20);
    $I->waitForListingItemsToLoad();
    $I->clickItemRowActionByItemName('Copy of ' . $email_subject, 'Edit');
    $I->waitForElement('[data-automation-id="newsletter_title"]', 20);
  }
}
