<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class PreviewPostNotificationNewsletterCest {
  public function previewStandardNewsletter(\AcceptanceTester $I) {
    $newsletterName = 'Preview in Browser Post Notification';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withPostNotificationsType()->create();
    $I->wantTo('Preview a post notification');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletterName);
    $I->clickItemRowActionByItemName($newsletterName, 'Preview');
    $I->switchToNextTab();
    $I->waitForElement('.mailpoet_template');
  }
}
