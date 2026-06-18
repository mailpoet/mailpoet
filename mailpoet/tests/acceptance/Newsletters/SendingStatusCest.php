<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Subscriber;

class SendingStatusCest {
  public function newsletterSendingStatus(\AcceptanceTester $i) {
    $i->wantTo('Switch between the sending status tabs for a standard newsletter');
    // Having a standard newsletter sent to 2 subscribers (one sent, one failed)
    $luckySubscriber = (new Subscriber)
      ->withFirstName('Lucky')
      ->withLastName('Luke')
      ->create();
    $unluckySubscriber = (new Subscriber)
      ->withFirstName('Unlucky')
      ->withLastName('John')
      ->create();
    $newsletter = (new Newsletter)
      ->withSubject('Testing newsletter sending status')
      ->withSentStatus()
      ->withSendingQueue([
        'count_processed' => 2,
        'count_total' => 2,
      ])
      ->withSubscriber($luckySubscriber)
      ->withSubscriber($unluckySubscriber, [
        'failed' => 1,
        'error' => 'Oh no!',
      ])
      ->create();
    // When I visit the newsletters page
    $i->login();
    $i->amOnMailPoetPage('Emails');
    $i->waitForText($newsletter->getSubject());
    // I click on the "Sent to 2 of 2" link
    $i->click('[data-automation-id="sending_status_' . $newsletter->getId() . '"]');
    $i->waitForText('Sending status');
    $taskId = $newsletter->getLatestQueue()->getTask()->getId();

    // The Sent tab (default) lists only the successfully sent recipient
    $this->checkSubscriber($i, $taskId, $luckySubscriber, 'Sent');
    $i->dontSee($unluckySubscriber->getEmail());

    // The Failed tab lists only the failed recipient with its error
    $i->click('[data-automation-id="filters_failed"]');
    $this->checkSubscriber($i, $taskId, $unluckySubscriber, 'Failed', 'Oh no!');
    $i->dontSee($luckySubscriber->getEmail());

    // The Unprocessed tab is empty for a completed send
    $i->click('[data-automation-id="filters_unprocessed"]');
    $i->waitForText('No recipients are waiting to be sent.');
  }

  private function checkSubscriber(\AcceptanceTester $i, $taskId, $subscriber, $status, $error = false) {
    $nameSelector = '[data-automation-id="name_' . $taskId . '_' . $subscriber->getId() . '"]';
    $statusSelector = '[data-automation-id="status_' . $taskId . '_' . $subscriber->getId() . '"]';
    $fullName = $subscriber->getFirstName() . ' ' . $subscriber->getLastName();
    $i->waitForText($subscriber->getEmail(), 10, $nameSelector);
    $i->waitForText($fullName, 10, $nameSelector);
    $i->waitForText($status, 10, $statusSelector);
    if ($error) {
      $errorSelector = '[data-automation-id="error_' . $taskId . '_' . $subscriber->getId() . '"]';
      $i->waitForText($error, 10, $errorSelector);
    }
  }
}
