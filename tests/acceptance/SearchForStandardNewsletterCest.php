<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class SearchForStandardNewsletterCest {
  function saveStandardNewsletterAsTemplate(\AcceptanceTester $I) {
    $I->wantTo('Successfully search for an existing newsletter');

    $newsletter_title = 'Search Test Newsletter';


    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withType('standard')
      ->create();
    
    // step 2 - Search
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->fillField('#search_input', $newsletter_title);
    $I->click('Search');
    $I->waitForText($newsletter_title, 20);
    }
    
}