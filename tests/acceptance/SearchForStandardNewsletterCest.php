<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class SearchForStandardNewsletterCest {

  function searchForStandardNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Successfully search for an existing newsletter');

    $newsletter_title = 'Search Test Newsletter';
    $failure_condition_newsletter = 'Not Actually Real';


    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletter_title)->create();

    // step 2 - Search
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->searchFor($failure_condition_newsletter, 2);
    $I->wait(5);
    $I->dontSee($newsletter_title);
    $I->searchFor($newsletter_title);
    $I->waitForText($newsletter_title, 20);
  }

}
