<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;

class SearchForStandardNewsletterCest {
  public function searchForStandardNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Successfully search for an existing and sent newsletter');

    $newsletterTitle = 'Sent Newsletter';
    $failureConditionNewsletter = 'Not Actually Real';
    $segmentName = 'Fancy List';

    // step 1 - Prepare newsletter data
    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName($segmentName)->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletterTitle)
      ->withSentStatus()
      ->withSegments([$segment])
      ->create();

    // step 2 - Search
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->searchFor($failureConditionNewsletter);
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 3 - Filter by Sent and search for the sent newsletter
    $i->click('[data-automation-id="filters_sent"]');
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 4 - Filter by assigned list and make sure the newsletter is present
    $i->selectOption('[data-automation-id="listing_filter_segment"]', $segmentName);
    $i->waitForElement('[data-automation-id="listing_filter_segment"]');
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');
  }
}
