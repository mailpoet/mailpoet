<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class NewslettersListingCest {

  public function newslettersListing(\AcceptanceTester $I) {
    $standard_newsletter_subject = 'Standard newsletter';
    $welcome_email_subject = 'Welcome email';
    $post_notification_email_subject = 'Post notification';
    $standard_newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject($standard_newsletter_subject)
      ->create();
    $welcome_email = (new Newsletter())
      ->withSentStatus()
      ->withSubject($welcome_email_subject)
      ->withWelcomeTypeForSegment()
      ->create();
    $post_notification_email = (new Newsletter())
      ->withSentStatus()
      ->withPostNotificationsType()
      ->withSubject($post_notification_email_subject)
      ->create();

    $I->wantTo('Open newsletters listings page');

    $I->login();
    $I->amOnMailpoetPage('Emails');

    // Standard newsletters is the default tab
    $I->waitForText('Standard newsletter', 5, '[data-automation-id="listing_item_' . $standard_newsletter->id . '"]');

    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Welcome email', 5, '[data-automation-id="listing_item_' . $welcome_email->id . '"]');

    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Post notification', 5, '[data-automation-id="listing_item_' . $post_notification_email->id . '"]');
    $I->seeNoJSErrors();
  }

  public function statisticsColumn(\AcceptanceTester $I) {
    (new Newsletter())->create();

    $I->wantTo('Check if statistics column is visible depending on tracking option');

    $I->login();

    // column is hidden when tracking is not enabled
    $I->amOnMailpoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->click('[data-automation-id="tracking-disabled-radio"]');
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');

    $I->amOnMailpoetPage('Emails');
    $I->waitForText('Subject');
    $I->dontSee('Opened, Clicked');

    // column is visible when tracking is enabled
    $I->amOnMailpoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->click('[data-automation-id="tracking-enabled-radio"]');
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');

    $I->amOnMailpoetPage('Emails');
    $I->waitForText('Subject');
    $I->see('Opened, Clicked');
    $I->seeNoJSErrors();
  }

}
