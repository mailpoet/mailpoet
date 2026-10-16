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

  public function filtersBySendResult(\AcceptanceTester $i) {
    $i->wantTo('Filter the sending status by the send result');
    [$newsletter, $lucky, $unlucky, $taskId] = $this->createSentNewsletter();
    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/sending-status/' . $newsletter->getId());
    $this->checkSubscriber($i, $taskId, $lucky, 'Sent');
    $this->checkSubscriber($i, $taskId, $unlucky, 'Failed', 'Oh no!');

    $i->applyDataViewsFilter('Sending status', ['Failed']);
    $i->waitForElementNotVisible($this->rowSelector($taskId, $lucky));
    $i->seeElement($this->rowSelector($taskId, $unlucky));
    $i->waitForHashFilterValue('result', 'failed');
    $i->waitForElement($this->filterChip('Sending status'));

    $i->reloadPage();
    $i->waitForElement($this->filterChip('Sending status'));
    $i->waitForElement($this->rowSelector($taskId, $unlucky));
    $i->dontSeeElement($this->rowSelector($taskId, $lucky));
  }

  public function opensLegacyGroupLinkAsResultFilter(\AcceptanceTester $i) {
    $i->wantTo('Open a legacy sending status group link as a result filter');
    [$newsletter, $lucky, $unlucky, $taskId] = $this->createSentNewsletter();
    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/sending-status/' . $newsletter->getId() . '/group[failed]');
    $i->waitForElement($this->filterChip('Sending status'));
    $i->waitForElement($this->rowSelector($taskId, $unlucky));
    $i->dontSeeElement($this->rowSelector($taskId, $lucky));
    $i->waitForHashFilterValue('result', 'failed');
    $i->dontSeeInCurrentUrl('group[');
    $i->dontSeeElement('.mailpoet-dataviews-group-failed');
  }

  public function explicitResultFilterWinsOverLegacyGroup(\AcceptanceTester $i) {
    $i->wantTo('Prefer the result filter over a legacy group in the same link');
    [$newsletter, $lucky, $unlucky, $taskId] = $this->createSentNewsletter();
    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/sending-status/' . $newsletter->getId() . '/group[failed]/filter[result=sent]');
    $i->waitForElement($this->rowSelector($taskId, $lucky));
    $i->dontSeeElement($this->rowSelector($taskId, $unlucky));
    $i->waitForHashFilterValue('result', 'sent');
  }

  public function showsCountsInResultOptions(\AcceptanceTester $i) {
    $i->wantTo('See the send result counts in the filter options');
    [$newsletter, $lucky, $unlucky, $taskId] = $this->createSentNewsletter();
    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/sending-status/' . $newsletter->getId());
    $this->checkSubscriber($i, $taskId, $lucky, 'Sent');
    $i->dontSeeElement('[role="tablist"]');
    $addFilter = ['xpath' => '//button[@aria-label="Add filter" or normalize-space(.)="Add filter"]'];
    $i->waitForElementClickable($addFilter);
    $i->click($addFilter);
    $i->click(['xpath' => '//*[@role="menuitem"][contains(normalize-space(.), "Sending status")]']);
    $i->waitForText('Sent (1)');
    $i->waitForText('Failed (1)');
    $i->waitForText('Unprocessed (0)');
  }

  private function createSentNewsletter(): array {
    $lucky = (new Subscriber)->withFirstName('Lucky')->withLastName('Luke')->create();
    $unlucky = (new Subscriber)->withFirstName('Unlucky')->withLastName('John')->create();
    $newsletter = (new Newsletter)
      ->withSubject('Testing sending status filter')
      ->withSentStatus()
      ->withSendingQueue(['count_processed' => 2, 'count_total' => 2])
      ->withSubscriber($lucky)
      ->withSubscriber($unlucky, ['failed' => 1, 'error' => 'Oh no!'])
      ->create();
    return [$newsletter, $lucky, $unlucky, $newsletter->getLatestQueue()->getTask()->getId()];
  }

  private function rowSelector($taskId, $subscriber): string {
    return '[data-automation-id="name_' . $taskId . '_' . $subscriber->getId() . '"]';
  }

  private function filterChip(string $name): array {
    return ['xpath' => '//*[contains(@class, "dataviews-filters__summary-chip-container")][contains(., "' . $name . '")]'];
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
