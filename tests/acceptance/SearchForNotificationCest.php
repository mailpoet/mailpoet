<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class SearchForNotificationCest {
  function searchForStandardNotification(\AcceptanceTester $I) {
    $I->wantTo('Successfully search for an existing notification');
    $newsletter_title = 'Search Test Notification';
    $failure_condition_newsletter = 'Not Actually Real';
    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withType('notification')
      ->withPostNoticationOptions()
      ->create();
    $newsletter_title = 'Search Test Notification';
    $failure_condition_newsletter = 'Fake Newsletter Notification';
    // step 2 - Search
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Post notification', 5, '[data-automation-id="listing_item_3"]');
    $I->fillField('#search_input', $failure_condition_newsletter);
    $I->click('Search');
    $I->wait(5);
    $I->dontSee($newsletter_title); 
    $I->fillField('#search_input', $newsletter_title);
    $I->click('Search');
    $I->waitForText($newsletter_title, 20);
  }
    
}
