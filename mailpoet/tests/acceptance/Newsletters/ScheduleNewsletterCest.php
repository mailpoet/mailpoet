<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\Exception\TimeoutException;
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
    $i->amEditingNewsletter($newsletter->getId());
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

  public function scheduleStandardNewsletterButtonCaption(\AcceptanceTester $i) {
    $i->wantTo('Check the submit button caption on time change');
    $newsletterTitle = 'Schedule Test Newsletter';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)
      ->create();
    $segmentName = $i->createListWithSubscriber();
    $currentDateTime = new \DateTime(strval($i->executeJS('return window.mailpoet_current_date_time')));

    // step 2 - Go to newsletter send page
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="email-schedule-checkbox"]');

    // step 3 - Pick today's date
    $i->wantTo('Pick today‘s date');
    $i->waitForElement('form input[name=date]');
    $i->click('form input[name=date]');
    // The calendar preselects tomorrow's date, making today's date not clickable on the last day of a month.
    // In case it is not clickable try switching to previous month
    try {
      $i->waitForElementClickable(['class' => 'react-datepicker__day--today'], 1);
    } catch (TimeoutException $e) {
      $i->click(['class' => 'react-datepicker__navigation--previous']);
      $i->waitForElementClickable(['class' => 'react-datepicker__day--today']);
    }
    $i->click(['class' => "react-datepicker__day--today"]);

    // `Schedule` caption - change time to 1 hour after now
    $i->selectOption('form select[name=time]', $currentDateTime->modify("+1 hour")->format('g:00 a'));
    $i->see("Schedule", "button span");

    // `Send` caption - change time to 1 hour before now
    $i->selectOption('form select[name=time]', $currentDateTime->modify("-1 hour")->format('g:00 a'));
    $i->see("Send", "button span");
  }
}
