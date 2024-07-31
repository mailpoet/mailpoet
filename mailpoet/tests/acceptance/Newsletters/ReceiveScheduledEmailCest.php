<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\Settings;

class ReceiveScheduledEmailCest {

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
  }

  public function receiveScheduledEmail(\AcceptanceTester $i) {
    $i->wantTo('Receive a scheduled standard newsletter as a subscriber');
    $this->settings->withCronTriggerMethod('Action Scheduler');

    $newsletterTitle = 'Scheduled Test Newsletter';
    $standardTemplate = '[data-automation-id="select_template_0"]';
    $titleElement = '[data-automation-id="newsletter_title"]';
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Create a wp user with wp role subscriber');
    $i->cli(['user', 'create', 'narwhal', 'standardtest@example.com', '--role=subscriber']);

    $i->wantTo('Create a newsletter with template');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForElement('[data-automation-id="templates-standard"]');
    $i->click('[data-automation-id="templates-standard"]');
    $i->waitForElement($standardTemplate);
    $i->see('Newsletters', ['css' => '.mailpoet-categories-item.active']);
    $i->click($standardTemplate);
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    $i->wantTo('Choose list and send');
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);

    $i->wantTo('Schedule newsletter at midnight on the 1st of next month');
    $i->click('[data-automation-id="email-schedule-checkbox"]');
    $i->waitForElement('form select[name=time]');
    $i->selectOption('form select[name=time]', '12:00 am');
    $i->waitForElement('form input[name=date]');
    $i->click('form input[name=date]');
    $i->waitForElement('.react-datepicker');
    $i->click(['class' => 'react-datepicker__navigation--next']);
    $i->click(['class' => 'react-datepicker__day--001']);
    $i->click('Schedule');
    $i->waitForText('The newsletter has been scheduled.');

    $newsletterRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $scheduledTasksRepository = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);

    $i->wantTo('Set scheduledAt to be in the past');
    $newsletter = $newsletterRepository->findOneBy(['subject' => $newsletterTitle]);
    if ($newsletter instanceof NewsletterEntity) {
      $scheduledTask = $scheduledTasksRepository->findOneByNewsletter($newsletter);
      if ($scheduledTask instanceof ScheduledTaskEntity) {
        $scheduledTask->setScheduledAt((new \DateTime)->modify("-1 day"));
        $scheduledTasksRepository->persist($scheduledTask);
        $scheduledTasksRepository->flush();
      }
    }

    $i->triggerMailPoetActionScheduler();

    $i->wantTo('Check that scheduled newsletter is received');
    $i->checkEmailWasReceived($newsletterTitle);
    $i->click(Locator::contains('span.subject', $newsletterTitle));
  }
}
