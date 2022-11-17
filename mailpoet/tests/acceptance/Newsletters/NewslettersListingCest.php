<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class NewslettersListingCest {
  public function newslettersListing(\AcceptanceTester $i) {
    $standardNewsletterSubject = 'Standard newsletter';
    $welcomeEmailSubject = 'Welcome email';
    $postNotificationEmailSubject = 'Post notification';
    $standardNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject($standardNewsletterSubject)
      ->create();
    $welcomeEmail = (new Newsletter())
      ->withSentStatus()
      ->withSubject($welcomeEmailSubject)
      ->withWelcomeTypeForSegment()
      ->create();
    $postNotificationEmail = (new Newsletter())
      ->withSentStatus()
      ->withPostNotificationsType()
      ->withSubject($postNotificationEmailSubject)
      ->create();

    $i->wantTo('Open newsletters listings page');

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // Standard newsletters is the default tab
    $i->waitForText('Standard newsletter', 5, '[data-automation-id="listing_item_' . $standardNewsletter->getId() . '"]');

    $i->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('Welcome email', 5, '[data-automation-id="listing_item_' . $welcomeEmail->getId() . '"]');

    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('Post notification', 5, '[data-automation-id="listing_item_' . $postNotificationEmail->getId() . '"]');
    $i->seeNoJSErrors();
  }

  public function statisticsColumn(\AcceptanceTester $i) {
    (new Newsletter())->create();

    $i->wantTo('Check if statistics column is visible depending on tracking option');

    $i->login();

    // column is hidden when tracking is not enabled
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->click('[data-automation-id="tracking-basic-radio"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Subject');
    $i->dontSee('Clicked, Opened');

    // column is visible when tracking is enabled
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->click('[data-automation-id="tracking-partial-radio"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Subject');
    $i->see('Clicked, Opened');
    $i->seeNoJSErrors();
  }
}
