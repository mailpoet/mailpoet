<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use DateTime;
use Facebook\WebDriver\WebDriverKeys;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\Tag;
use PHPUnit\Framework\Assert;

class SubscribersListingCest {
  public function subscribersListing(\AcceptanceTester $i) {
    $i->wantTo('Open subscribers listings page');

    $tag1 = (new Tag())
      ->withName('My Tag 1')
      ->create();
    $tag2 = (new Tag())
      ->withName('My Tag 2')
      ->create();
    $tag3 = (new Tag())
      ->withName('My Tag 3')
      ->create();

    (new Subscriber())
      ->withEmail('wp@example.com')
      ->withTags([$tag1, $tag2, $tag3])
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('wp@example.com');
    $i->waitForText('wp@example.com');
    $i->waitForText('My Tag 1');
    $i->waitForText('My Tag 2');
    $i->waitForText('My Tag 3');
  }

  public function useTagFilter(\AcceptanceTester $i) {
    $i->wantTo('Open subscribers listings page');

    $tag = (new Tag())
      ->withName('My Tag')
      ->create();

    (new Subscriber())
      ->withEmail('wp@example.com')
      ->create();

    (new Subscriber())
      ->withEmail('wp@mailpoet.com')
      ->withTags([$tag])
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText('All Tags');
    $i->selectOption('[data-automation-id="listing_filter_tag"]', $tag->getName());
    $i->waitForText('wp@mailpoet.com');
    $i->dontSee('wp@example.com');
    $i->waitForText('My Tag');
  }

  public function sendConfirmationEmail(\AcceptanceTester $i) {
    $i->wantTo('Send confirmation email');

    $maxConfirmationsEmail = 'disallowed@example.com';
    $allowedEmail = 'allowed@example.com';

    $subscriberResendDisallowed = (new Subscriber())
      ->withEmail($maxConfirmationsEmail)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $subscriberResendAllowed = (new Subscriber())
      ->withEmail($allowedEmail)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(0)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    $i->waitForText($maxConfirmationsEmail);
    $i->moveMouseOver(['xpath' => '//*[text()="' . $maxConfirmationsEmail . '"]//ancestor::tr']);
    // The backend enforces resend limits when admins use this row action.
    $i->see('Resend confirmation email', '//*[text()="' . $maxConfirmationsEmail . '"]//ancestor::tr');

    $i->clickItemRowActionByItemName($allowedEmail, 'Resend confirmation email');
    $i->waitForText('1 confirmation email has been sent.');

    $i->checkEmailWasReceived('Confirm your subscription');
  }

  public function bulkUnsubscribe(\AcceptanceTester $i) {
    $i->wantTo('Unsubscribe subscribers using a bulk action');
    $i->wantTo('Setup data');
    $subscriber1 = (new Subscriber())
      ->withEmail('subscriber1@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('subscriber2@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber3 = (new Subscriber())
      ->withEmail('subscriber3@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber4 = (new Subscriber())
      ->withEmail('subscriber4@example.com')
      ->withStatus('subscribed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    $i->wantTo('Select first two subscribers and unsubscribe them');
    $i->waitForText('subscriber1@example.com');
    $i->click("[data-automation-id='listing-row-checkbox-{$subscriber1->getId()}']");
    $i->click("[data-automation-id='listing-row-checkbox-{$subscriber2->getId()}']");

    $i->waitForElement("[data-automation-id='action-unsubscribe']");
    $i->click("[data-automation-id='action-unsubscribe']");

    $i->wantTo('Confirm the action in the modal window');
    $i->waitForElement("[data-automation-id='bulk-unsubscribe-confirm']");
    $i->click("[data-automation-id='bulk-unsubscribe-confirm']");

    $i->wantTo('Check the final status');
    $i->waitForText('subscriber2@example.com');
    $i->waitForText('Unsubscribed', 10, "[data-automation-id='listing_item_{$subscriber1->getId()}']");
    $i->waitForText('Unsubscribed', 10, "[data-automation-id='listing_item_{$subscriber2->getId()}']");
    $i->waitForText('Subscribed', 10, "[data-automation-id='listing_item_{$subscriber3->getId()}']");
    $i->waitForText('Subscribed', 10, "[data-automation-id='listing_item_{$subscriber4->getId()}']");
    $i->dontSee('Unsubscribed', "[data-automation-id='listing_item_{$subscriber3->getId()}']");
    $i->dontSee('Unsubscribed', "[data-automation-id='listing_item_{$subscriber4->getId()}']");
  }

  public function bulkResendConfirmationEmailActionVisibility(\AcceptanceTester $i) {
    $i->wantTo('Show the bulk confirmation resend action only for unconfirmed subscribers');

    $settings = new Settings();
    $settings->withConfirmationEmailEnabled();

    $subscribers = [
      'all' => (new Subscriber())
        ->withEmail('bulk-resend-all@example.com')
        ->withStatus('unconfirmed')
        ->create(),
      'subscribed' => (new Subscriber())
        ->withEmail('bulk-resend-subscribed@example.com')
        ->withStatus('subscribed')
        ->create(),
      'unsubscribed' => (new Subscriber())
        ->withEmail('bulk-resend-unsubscribed@example.com')
        ->withStatus('unsubscribed')
        ->create(),
      'inactive' => (new Subscriber())
        ->withEmail('bulk-resend-inactive@example.com')
        ->withStatus('inactive')
        ->create(),
      'bounced' => (new Subscriber())
        ->withEmail('bulk-resend-bounced@example.com')
        ->withStatus('bounced')
        ->create(),
      'trash' => (new Subscriber())
        ->withEmail('bulk-resend-trash@example.com')
        ->withDeletedAt(new DateTime())
        ->create(),
    ];
    $unconfirmedSubscriber = (new Subscriber())
      ->withEmail('bulk-resend-unconfirmed@example.com')
      ->withStatus('unconfirmed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    $this->selectSubscriberForBulkAction($i, $subscribers['all']);
    $i->dontSeeElement("[data-automation-id='action-resendConfirmationEmails']");

    foreach (['subscribed', 'unsubscribed', 'inactive', 'bounced'] as $group) {
      $this->openSubscribersGroup($i, $group);
      $this->selectSubscriberForBulkAction($i, $subscribers[$group]);
      $i->dontSeeElement("[data-automation-id='action-resendConfirmationEmails']");
    }

    $this->openSubscribersGroup($i, 'trash');
    $i->waitForElement("tbody [data-automation-id^='listing-row-checkbox-']");
    $i->click("tbody [data-automation-id^='listing-row-checkbox-']");
    $i->waitForElement("[data-automation-id='listing-bulk-actions']");
    $i->dontSeeElement("[data-automation-id='action-resendConfirmationEmails']");

    $this->openSubscribersGroup($i, 'unconfirmed');
    $this->selectSubscriberForBulkAction($i, $unconfirmedSubscriber);
    $i->seeElement("[data-automation-id='action-resendConfirmationEmails']");

    $settings->withConfirmationEmailDisabled();
    $this->openSubscribersGroup($i, 'unconfirmed');
    $this->selectSubscriberForBulkAction($i, $unconfirmedSubscriber);
    $i->dontSeeElement("[data-automation-id='action-resendConfirmationEmails']");
    $settings->withConfirmationEmailEnabled();
  }

  public function bulkResendConfirmationEmailModalAndNotice(\AcceptanceTester $i) {
    $i->wantTo('Queue bulk confirmation email resends with a gated accessible modal');

    $eligibleSubscriber = (new Subscriber())
      ->withEmail('bulk-resend-eligible@example.com')
      ->withStatus('unconfirmed')
      ->withCountConfirmations(0)
      ->create();
    $skippedSubscriber = (new Subscriber())
      ->withEmail('bulk-resend-skipped@example.com')
      ->withStatus('unconfirmed')
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $eligibleSubscriber);
    $this->selectSubscriberForBulkAction($i, $skippedSubscriber);
    $this->openBulkResendConfirmationModal($i);

    $i->waitForElement('#bulk-resend-confirmation-checkbox-input');
    Assert::assertSame(
      'bulk-resend-confirmation-checkbox-input',
      $i->executeJS('return document.activeElement.id;')
    );
    $i->seeElement('div[role="dialog"][aria-labelledby]');
    $i->seeElement('div[role="dialog"] button[aria-label="Close"]');
    $modalTitleId = $i->grabAttributeFrom('div[role="dialog"]', 'aria-labelledby');
    Assert::assertSame(
      'Resend confirmation emails',
      $i->executeJS(
        'return document.getElementById(' . json_encode($modalTitleId) . ').textContent;'
      )
    );
    $i->see(
      'I confirm these subscribers asked to join and can be sent a confirmation email.',
      "[data-automation-id='bulk-resend-confirmation-checkbox']"
    );
    $i->seeElement("[data-automation-id='bulk-resend-confirmation-confirm']:disabled");
    Assert::assertSame(
      'bulk-resend-confirmation-confirm-help',
      $i->grabAttributeFrom("[data-automation-id='bulk-resend-confirmation-confirm']", 'aria-describedby')
    );

    $i->pressKey('#bulk-resend-confirmation-checkbox-input', WebDriverKeys::ESCAPE);
    $i->waitForElementNotVisible('div[role="dialog"]');
    Assert::assertSame(
      'action-resendConfirmationEmails',
      $i->executeJS('return document.activeElement.getAttribute("data-automation-id");')
    );

    $this->openBulkResendConfirmationModal($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-checkbox']");
    $i->dontSeeElement("[data-automation-id='bulk-resend-confirmation-confirm']:disabled");
    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");

    $i->waitForText('A resend job was queued for up to 1 eligible subscriber. 1 selected subscriber was not queued because they were ineligible or beyond the batch limit.');
  }

  public function bulkResendConfirmationEmailPreventsDuplicateSubmits(\AcceptanceTester $i) {
    $i->wantTo('Prevent duplicate bulk confirmation resend requests while queueing is pending');

    $subscriber = (new Subscriber())
      ->withEmail('bulk-resend-pending@example.com')
      ->withStatus('unconfirmed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-checkbox']");

    $i->executeJS(<<<'JS'
      window.mailpoetBulkResendRequests = 0;
      window.mailpoetBulkResendDeferred = jQuery.Deferred();
      window.mailpoetOriginalAjaxPost = MailPoet.Ajax.post;
      MailPoet.Ajax.post = function(request) {
        if (
          request.action === 'bulkAction'
          && request.data
          && request.data.action === 'resendConfirmationEmails'
        ) {
          window.mailpoetBulkResendRequests += 1;
          return window.mailpoetBulkResendDeferred.promise();
        }
        return window.mailpoetOriginalAjaxPost.apply(this, arguments);
      };
    JS);

    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    $i->click("[data-automation-id='action-resendConfirmationEmails']");
    Assert::assertSame(1, $i->executeJS('return window.mailpoetBulkResendRequests;'));

    $i->executeJS(<<<'JS'
      MailPoet.Ajax.post = window.mailpoetOriginalAjaxPost;
      window.mailpoetBulkResendDeferred.resolve({
        data: {
          selected_count: 1,
          eligible_count: 1,
          queued_count: 1,
          skipped_count: 0,
          skipped_by_reason: {},
          task_id: 1,
          message: 'Confirmation emails were queued.'
        }
      });
    JS);
  }

  public function bulkResendConfirmationEmailSelectAllKeepsListingScope(\AcceptanceTester $i) {
    $i->wantTo('Queue bulk confirmation resends for all pages without sending an explicit empty selection');

    for ($index = 1; $index <= 31; $index++) {
      (new Subscriber())
        ->withEmail(sprintf('bulk-resend-all-pages-%02d@example.com', $index))
        ->withStatus('unconfirmed')
        ->create();
    }

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $i->waitForText('bulk-resend-all-pages-01@example.com');
    $i->click("[data-automation-id='select_all']");
    $i->waitForText('Select all items on all pages');
    $i->click('Select all items on all pages');
    $i->waitForElement('tbody .mailpoet-form-checkbox.mailpoet-disabled');
    $this->openBulkResendConfirmationModal($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-checkbox']");

    $i->executeJS(<<<'JS'
      window.mailpoetBulkResendRequestHasListingSelection = null;
      window.mailpoetBulkResendListingGroup = null;
      window.mailpoetOriginalAjaxPost = MailPoet.Ajax.post;
      MailPoet.Ajax.post = function(request) {
        if (
          request.action === 'bulkAction'
          && request.data
          && request.data.action === 'resendConfirmationEmails'
        ) {
          window.mailpoetBulkResendRequestHasListingSelection = Object.prototype.hasOwnProperty.call(
            request.data.listing,
            'selection'
          );
          window.mailpoetBulkResendListingGroup = request.data.listing.group;
          return jQuery.Deferred().resolve({
            data: {
              selected_count: 31,
              eligible_count: 31,
              queued_count: 20,
              skipped_count: 11,
              skipped_by_reason: {},
              task_id: 1,
              message: 'Confirmation emails were queued.'
            }
          }).promise();
        }
        return window.mailpoetOriginalAjaxPost.apply(this, arguments);
      };
    JS);

    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    $i->waitForJS(
      'return window.mailpoetBulkResendRequestHasListingSelection !== null;'
    );
    Assert::assertFalse(
      $i->executeJS('return window.mailpoetBulkResendRequestHasListingSelection;')
    );
    Assert::assertSame(
      'unconfirmed',
      $i->executeJS('return window.mailpoetBulkResendListingGroup;')
    );

    $i->executeJS('MailPoet.Ajax.post = window.mailpoetOriginalAjaxPost;');
  }

  public function bulkResendConfirmationEmailFailureClearsLoading(\AcceptanceTester $i) {
    $i->wantTo('Clear the subscribers listing loading state after a failed bulk confirmation resend');

    $subscriber = (new Subscriber())
      ->withEmail('bulk-resend-failed@example.com')
      ->withStatus('unconfirmed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-checkbox']");

    $i->executeJS(<<<'JS'
      window.mailpoetOriginalAjaxPost = MailPoet.Ajax.post;
      MailPoet.Ajax.post = function(request) {
        if (
          request.action === 'bulkAction'
          && request.data
          && request.data.action === 'resendConfirmationEmails'
        ) {
          return jQuery.Deferred().reject({
            errors: [
              {
                error: 'forced_failure',
                message: 'Forced bulk resend failure.'
              }
            ]
          }).promise();
        }
        return window.mailpoetOriginalAjaxPost.apply(this, arguments);
      };
    JS);

    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    $i->waitForText('Forced bulk resend failure.');
    $i->waitForElementNotVisible(\AcceptanceTester::LISTING_LOADING_SELECTOR);

    $i->executeJS('MailPoet.Ajax.post = window.mailpoetOriginalAjaxPost;');
  }

  public function bulkResendConfirmationEmailSettingsErrorShowsSafeLink(\AcceptanceTester $i) {
    $i->wantTo('Show the confirmation-disabled error with a safe settings link');

    $subscriber = (new Subscriber())
      ->withEmail('bulk-resend-settings-link@example.com')
      ->withStatus('unconfirmed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-checkbox']");

    $i->executeJS(<<<'JS'
      window.mailpoetOriginalAjaxPost = MailPoet.Ajax.post;
      MailPoet.Ajax.post = function(request) {
        if (
          request.action === 'bulkAction'
          && request.data
          && request.data.action === 'resendConfirmationEmails'
        ) {
          return jQuery.Deferred().reject({
            errors: [
              {
                error: 'confirmation_disabled',
                message: [
                  'Sign-up confirmation is disabled in your ',
                  '<a href="admin.php?page=mailpoet-settings#/signup">',
                  'MailPoet settings',
                  '</a>. Please enable it to resend confirmation emails ',
                  'or update your subscriber’s status manually.'
                ].join('')
              }
            ]
          }).promise();
        }
        return window.mailpoetOriginalAjaxPost.apply(this, arguments);
      };
    JS);

    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    $i->waitForText('Sign-up confirmation is disabled in your');
    Assert::assertSame(
      'admin.php?page=mailpoet-settings#/signup',
      $i->executeJS(
        'return document.querySelector(".mailpoet_notice.error a").getAttribute("href");'
      )
    );
    $noticeHtml = $i->executeJS('return document.querySelector(".mailpoet_notice.error").innerHTML;');
    Assert::assertIsString($noticeHtml);
    Assert::assertStringNotContainsString('&lt;a', $noticeHtml);

    $i->executeJS('MailPoet.Ajax.post = window.mailpoetOriginalAjaxPost;');
  }

  public function bulkResendConfirmationEmailShowsZeroEligibleNotice(\AcceptanceTester $i) {
    $i->wantTo('Show a zero eligible notice for bulk confirmation resends');

    $subscriber = (new Subscriber())
      ->withEmail('bulk-resend-zero@example.com')
      ->withStatus('unconfirmed')
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-checkbox']");
    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");

    $i->waitForText('No confirmation email resend job was queued. No selected subscribers were currently eligible.');
  }

  public function searchInTrashWithNoResultsStaysInTrash(\AcceptanceTester $i) {
    $i->wantTo('Verify that searching in Trash with no results does not redirect to All');

    // Create a trashed subscriber
    (new Subscriber())
      ->withEmail('trashed@example.com')
      ->withDeletedAt(new DateTime())
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    // Navigate to the Trash group
    $i->changeGroupInListingFilter('trash');
    $i->waitForText('trashed@example.com');

    // Search for something that won't match any trashed subscriber
    $i->searchFor('noresultsexpected_xyz');

    // Should stay in trash — not redirect to All
    $i->waitForText('No items found.');
    $i->seeInCurrentURL(urlencode('group[trash]'));
  }

  private function selectSubscriberForBulkAction(\AcceptanceTester $i, $subscriber): void {
    $i->waitForText($subscriber->getEmail());
    $i->click("[data-automation-id='listing-row-checkbox-{$subscriber->getId()}']");
    $i->waitForElement("[data-automation-id='listing-bulk-actions']");
  }

  private function openBulkResendConfirmationModal(\AcceptanceTester $i): void {
    $i->waitForElement("[data-automation-id='action-resendConfirmationEmails']");
    $i->click("[data-automation-id='action-resendConfirmationEmails']");
    $i->waitForElement("[data-automation-id='bulk-resend-confirmation-checkbox']");
  }

  private function openSubscribersGroup(\AcceptanceTester $i, string $group): void {
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/group[' . $group . ']');
    $i->waitForElement('#search_input');
    $i->waitForElementNotVisible(\AcceptanceTester::LISTING_LOADING_SELECTOR);
  }
}
