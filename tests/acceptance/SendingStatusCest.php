<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Subscriber;

class SendingStatusCest {
  function newsletterSendingStatus(\AcceptanceTester $I) {
    $I->wantTo('Check the sending status page for a standard newsletter');
    // Having a standard newsletter sent to 2 subscribers
    $lucky_subscriber = (new Subscriber)
      ->withFirstName('Lucky')
      ->withLastName('Luke')
      ->create();
    $unlucky_subscriber = (new Subscriber)
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
      ->withSubscriber($lucky_subscriber)
      ->withSubscriber($unlucky_subscriber, [
        'failed' => 1,
        'error' => 'Oh no!',
      ])
      ->create();
    // When I visit the newsletters page
    $I->login();
    $I->amOnMailPoetPage('Emails');
    $I->waitForText($newsletter->subject);
    // I click on the "Sent to 3 of 3" link
    $I->click('[data-automation-id="sending_status_' . $newsletter->id . '"]');
    $I->waitForText('Sending status');
    // I see the subscribers with related statuses
    $task_id = $newsletter->getQueue()->task_id;
    $this->checkSubscriber($I, $task_id, $lucky_subscriber, 'Sent');
    $this->checkSubscriber($I, $task_id, $unlucky_subscriber, 'Failed', 'Oh no!');
  }

  private function checkSubscriber($I, $task_id, $subscriber, $status, $error = false) {
    $name_selector = '[data-automation-id="name_' . $task_id . '_' . $subscriber->id . '"]';
    $status_selector = '[data-automation-id="status_' . $task_id . '_' . $subscriber->id . '"]';
    $full_name = $subscriber->first_name . ' ' . $subscriber->last_name;
    $I->waitForText($subscriber->email, 10, $name_selector);
    $I->waitForText($full_name, 10, $name_selector);
    $I->waitForText($status, 10, $status_selector);
    if ($error) {
      $error_selector = '[data-automation-id="error_' . $task_id . '_' . $subscriber->id . '"]';
      $I->waitForText($error, 10, $error_selector);
    }
  }
}
