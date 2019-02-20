<?php
namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class ScheduleNewsletterCest {

  function scheduleStandardNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Schedule a newsletter');
    $newsletter_title = 'Schedule Test Newsletter';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->create();

    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');

    // step 4 - Choose list and schedule
    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $I->selectOptionInSelect2('WordPress Users');
    $I->checkOption('isScheduled');
    $I->click('select[name=time]');
    $I->selectOption('form select[name=time]', '6:00');
    $I->click('Schedule');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');

  }

}
