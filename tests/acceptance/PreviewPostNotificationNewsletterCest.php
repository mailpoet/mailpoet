<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class PreviewPostNotificationNewsletterCest {
  public function previewStandardNewsletter(\AcceptanceTester $i) {
    $newsletterName = 'Preview in Browser Post Notification';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withPostNotificationsType()->create();
    $i->wantTo('Preview a post notification');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Preview');
    $i->switchToNextTab();
    $i->waitForElement('.mailpoet_template');
  }
}
