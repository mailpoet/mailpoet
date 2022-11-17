<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SearchForStandardNewsletterCest {
  public function searchForStandardNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Successfully search for an existing newsletter');

    $newsletterTitle = 'Search Test Newsletter';
    $failureConditionNewsletter = 'Not Actually Real';


    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletterTitle)->create();

    // step 2 - Search
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->searchFor($failureConditionNewsletter);
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');
  }
}
