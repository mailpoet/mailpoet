<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class ScheduleNewsletterCest {
  public function scheduleStandardNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Schedule a newsletter');
    $newsletterTitle = 'Schedule Test Newsletter';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)
      ->create();
    $segmentName = $i->createListWithSubscriber();

    // step 2 - Go to editor
    $i->login();
    $i->amEditingNewsletter($newsletter->id);
    $i->click('Next');

    // step 4 - Choose list and schedule
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="email-schedule-checkbox"]');
    $i->click('select[name=time]');
    $i->selectOption('form select[name=time]', '6:00');
    $i->click('Schedule');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');

  }
}
