<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\Exception\TimeoutException;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\Newsletter;
use PHPUnit\Framework\Assert;

class ScheduleNewsletterCest {
  public function scheduleStandardNewsletterForLater(\AcceptanceTester $i) {
    $i->wantTo('Schedule a newsletter');
    $newsletterTitle = 'Schedule Test Newsletter';

    // step 1 - Prepare standard newsletter
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
    $i->waitForElement('.mailpoet_modal_overlay');
    $i->waitForElementVisible('.notice-success');
    $i->waitForText('The newsletter has been scheduled.');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('6:00 am');
    $i->cli(['option', 'update', 'timezone_string', 'Etc/GMT+10']);
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('8:00 pm');
  }

  public function scheduleStandardNewsletterAcrossClockChange(\AcceptanceTester $i) {
    $i->wantTo('Schedule a newsletter for a date after the next clock change');
    $newsletterTitle = 'Clock Change Test Newsletter';

    // The browser runs in UTC, so it can only get the clock changes from the site.
    $siteTimeZone = new \DateTimeZone('America/New_York');
    $i->cli(['option', 'update', 'timezone_string', $siteTimeZone->getName()]);

    // The date picker opens on tomorrow's month. Find the first later month whose
    // 15th has a different UTC offset than today.
    $now = new \DateTimeImmutable('now', $siteTimeZone);
    $scheduledFor = $now->modify('+1 day')->modify('first day of this month')->setTime(6, 0);
    $monthsAhead = 0;
    do {
      $scheduledFor = $scheduledFor->modify('+1 month');
      $monthsAhead++;
    } while ($scheduledFor->modify('+14 days')->getOffset() === $now->getOffset());
    $scheduledFor = $scheduledFor->modify('+14 days');

    $newsletter = (new Newsletter())->withSubject($newsletterTitle)->create();
    $segmentName = $i->createListWithSubscriber();

    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="email-schedule-checkbox"]');
    $i->waitForElement('form input[name=date]');
    $i->click('form input[name=date]');
    $i->waitForElement('.react-datepicker');
    for ($month = 0; $month < $monthsAhead; $month++) {
      $i->click(['class' => 'react-datepicker__navigation--next']);
    }
    $i->click(['class' => 'react-datepicker__day--015']);
    $i->selectOption('form select[name=time]', '6:00');
    $i->click('Schedule');
    $i->waitForText('The newsletter has been scheduled.');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('6:00 am');

    $task = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class)->findOneByNewsletter($newsletter);
    Assert::assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    Assert::assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    Assert::assertSame(
      $scheduledFor->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
      $scheduledAt->format('Y-m-d H:i:s')
    );
  }

  public function scheduleStandardNewsletterButtonCaption(\AcceptanceTester $i) {
    $i->wantTo('Check the submit button caption on time change');
    $newsletterTitle = 'Schedule Test Newsletter';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)
      ->create();
    $segmentName = $i->createListWithSubscriber();
    /** @var string $value - for PHPStan because strval() doesn't accept a value of mixed */
    $value = $i->executeJS('return window.mailpoet_current_date_time');

    // step 2 - Go to newsletter send page
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="email-schedule-checkbox"]');

    $i->wantTo('Wait for datetime picker');
    $i->waitForElement('form input[name=date]');
    // By default, tomorrow 8am is selected, so the Schedule button should be visible immediately
    $i->see("Schedule", "button span");

    $i->wantTo('Pick today‘s date');
    $i->click('form input[name=date]');
    // If tomorrow falls on the 1st of the month, today's date is not clickable.
    // We need to switch to previous month first
    try {
      $i->waitForElementClickable(['class' => 'react-datepicker__day--today'], 1);
    } catch (TimeoutException $e) {
      $i->click(['class' => 'react-datepicker__navigation--previous']);
      $i->waitForElementClickable(['class' => 'react-datepicker__day--today']);
    }
    $i->click(['class' => "react-datepicker__day--today"]);

    // `Send` caption - change time to midnight of the current day simulating selecting time in past
    $i->selectOption('form select[name=time]', '12:00 am');
    $i->see("Send", "button span");

    // `Schedule` caption
    $i->wantTo('Pick future date');
    // Open datepicker
    $i->click('form input[name=date]');
    // Select next month
    $i->click(['class' => 'react-datepicker__navigation--next']);
    // Click on 1st
    $i->click(['class' => 'react-datepicker__day--001']);
    $i->see("Schedule", "button span");
  }
}
