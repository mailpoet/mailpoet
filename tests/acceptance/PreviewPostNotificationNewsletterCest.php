<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class PreviewPostNotificationNewsletterCest {
  public function previewPostNotificationNewsletter(\AcceptanceTester $i) {
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

  public function previewPostNotificationNewsletterInEditor(\AcceptanceTester $i) {
    $i->wantTo('Preview and send post notification newsletter inside editor');
    $newsletterName = 'Preview in Browser Post Notification';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withPostNotificationsType()->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Edit');
    $i->click('[data-automation-id="sidebar_preview_region_heading"]');
    $i->waitForText('Send preview');
    $i->click('Send preview');
    $i->waitForText('Your test email has been sent!');
    $i->click('.notice-dismiss');
    $i->clearField('#mailpoet_preview_to_email');
    $i->click('Send preview');
    $i->waitForText('Enter an email address to send the preview newsletter to.');
    $i->fillField('#mailpoet_preview_to_email', 'test2@test.com');
    $i->click('Send preview');
    $i->waitForText('Your test email has been sent!');
    $i->click('View in browser');
    $i->waitForText('Newsletter Preview');
    $i->click('Open in new tab');
    $i->switchToNextTab();
    $i->waitForElement('.mailpoet_template');
  }
}
