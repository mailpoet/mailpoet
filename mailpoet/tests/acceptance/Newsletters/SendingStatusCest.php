<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Subscriber;

class SendingStatusCest {
  public function newsletterSendingStatus(\AcceptanceTester $i) {
    $i->wantTo('Check the sending status page for a standard newsletter');
    // Having a standard newsletter sent to 2 subscribers
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
    // I see the subscribers with related statuses
    $taskId = $newsletter->getLatestQueue()->getTask()->getId();
    $this->checkSubscriber($i, $taskId, $luckySubscriber, 'Sent');
    $this->checkSubscriber($i, $taskId, $unluckySubscriber, 'Failed', 'Oh no!');
  }

  private function checkSubscriber($i, $taskId, $subscriber, $status, $error = false) {
    $nameSelector = '[data-automation-id="name_' . $taskId . '_' . $subscriber->id . '"]';
    $statusSelector = '[data-automation-id="status_' . $taskId . '_' . $subscriber->id . '"]';
    $fullName = $subscriber->firstName . ' ' . $subscriber->lastName;
    $i->waitForText($subscriber->email, 10, $nameSelector);
    $i->waitForText($fullName, 10, $nameSelector);
    $i->waitForText($status, 10, $statusSelector);
    if ($error) {
      $errorSelector = '[data-automation-id="error_' . $taskId . '_' . $subscriber->id . '"]';
      $i->waitForText($error, 10, $errorSelector);
    }
  }
}
