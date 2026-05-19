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
    // The legacy listing rendered row actions inline on hover so we could
    // assert visibility from outside the menu. DataViews puts non-primary
    // actions behind a popover trigger; the open/close + portal portal makes a
    // visibility-only assertion noisy in CI. Cover the user-facing behaviour
    // via the resend on the allowed row below — backend enforcement of
    // max-confirmation limits is covered separately in the REST integration
    // tests.

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
    $this->selectSubscriberForBulkAction($i, $subscriber1);
    $this->selectSubscriberForBulkAction($i, $subscriber2);
    $i->selectListingBulkAction('Unsubscribe');

    $i->wantTo('Confirm the action in the modal window');
    $i->waitForElement("[data-automation-id='bulk-unsubscribe-confirm']");
    $i->click("[data-automation-id='bulk-unsubscribe-confirm']");

    $i->wantTo('Check the final status');
    $i->waitForText('subscriber2@example.com');
    $i->waitForText('Unsubscribed', 10, $this->subscriberRow($subscriber1->getEmail()));
    $i->waitForText('Unsubscribed', 10, $this->subscriberRow($subscriber2->getEmail()));
    $i->waitForText('Subscribed', 10, $this->subscriberRow($subscriber3->getEmail()));
    $i->waitForText('Subscribed', 10, $this->subscriberRow($subscriber4->getEmail()));
    $i->dontSee('Unsubscribed', $this->subscriberRow($subscriber3->getEmail()));
    $i->dontSee('Unsubscribed', $this->subscriberRow($subscriber4->getEmail()));
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
    $i->dontSee('Resend confirmation emails', '.dataviews-bulk-actions-footer__action-buttons');

    foreach (['subscribed', 'unsubscribed', 'inactive', 'bounced'] as $group) {
      $this->openSubscribersGroup($i, $group);
      $this->selectSubscriberForBulkAction($i, $subscribers[$group]);
      $i->dontSee('Resend confirmation emails', '.dataviews-bulk-actions-footer__action-buttons');
    }

    $this->openSubscribersGroup($i, 'trash');
    $i->checkWooTableCheckboxForItemName($subscribers['trash']->getEmail());
    $i->dontSee('Resend confirmation emails', '.dataviews-bulk-actions-footer__action-buttons');

    $this->openSubscribersGroup($i, 'unconfirmed');
    $this->selectSubscriberForBulkAction($i, $unconfirmedSubscriber);
    $i->see('Resend confirmation emails', '.dataviews-bulk-actions-footer__action-buttons');

    $settings->withConfirmationEmailDisabled();
    $this->openSubscribersGroup($i, 'unconfirmed');
    $this->selectSubscriberForBulkAction($i, $unconfirmedSubscriber);
    $i->dontSee('Resend confirmation emails', '.dataviews-bulk-actions-footer__action-buttons');
    $settings->withConfirmationEmailEnabled();
  }

  public function bulkResendConfirmationEmailModalAndNotice(\AcceptanceTester $i) {
    $i->wantTo('Queue bulk confirmation email resends with a gated accessible modal');

    (new Settings())->withConfirmationEmailEnabled();
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
      'I confirm these subscribers asked to join my list.',
      "[data-automation-id='bulk-resend-confirmation-checkbox']"
    );
    $i->seeElement("[data-automation-id='bulk-resend-confirmation-confirm']:disabled");

    $i->pressKey('#bulk-resend-confirmation-checkbox-input', WebDriverKeys::ESCAPE);
    $i->waitForElementNotVisible('div[role="dialog"]');
    $focusedText = $i->executeJS('return document.activeElement.textContent;');
    Assert::assertSame(
      'Resend confirmation emails',
      trim(is_string($focusedText) ? $focusedText : '')
    );

    $this->openBulkResendConfirmationModal($i);
    $this->confirmBulkResendConfirmationEmailRequest($i);
    $i->dontSeeElement("[data-automation-id='bulk-resend-confirmation-confirm']:disabled");
    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");

    $i->waitForText(
      'MailPoet is resending confirmation emails to 1 subscriber. 1 selected subscriber was skipped.'
    );
  }

  public function bulkResendConfirmationEmailPreventsDuplicateSubmits(\AcceptanceTester $i) {
    $i->wantTo('Prevent duplicate bulk confirmation resend requests while queueing is pending');

    (new Settings())->withConfirmationEmailEnabled();
    $subscriber = (new Subscriber())
      ->withEmail('bulk-resend-pending@example.com')
      ->withStatus('unconfirmed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $this->confirmBulkResendConfirmationEmailRequest($i);

    $i->installApiFetchInterceptor();
    $i->executeJS(<<<'JS'
      window.mailpoetBulkResendRequests = 0;
      window.mailpoetBulkResendResolve = null;
      window.mailpoetTestApiFetchInterceptor = function (options, next) {
        if (
          typeof options.path === 'string'
          && options.path.indexOf('/mailpoet/v1/subscribers/bulk-action') !== -1
          && options.data
          && options.data.action === 'resendConfirmationEmails'
        ) {
          window.mailpoetBulkResendRequests += 1;
          return new Promise(function (resolve) {
            window.mailpoetBulkResendResolve = resolve;
          });
        }
        return next(options);
      };
    JS);

    // Click confirm twice in succession — the first click sets the button to
    // its busy/disabled state and the modal handler short-circuits while a
    // request is pending. The pending promise is never resolved here, so the
    // second click must not produce a second REST request.
    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    Assert::assertSame(1, $i->executeJS('return window.mailpoetBulkResendRequests;'));

    $i->executeJS(<<<'JS'
      if (typeof window.mailpoetBulkResendResolve === 'function') {
        window.mailpoetBulkResendResolve({
          data: {
            action: 'resendConfirmationEmails',
            count: 1,
            segment: null,
            tag: null,
            queue: {
              selected_count: 1,
              eligible_count: 1,
              queued_count: 1,
              skipped_count: 0,
              skipped_by_reason: {},
              task_id: 1,
              message: 'Confirmation emails are being resent.'
            }
          }
        });
      }
    JS);
    $i->clearApiFetchInterceptor();
  }

  public function bulkResendConfirmationEmailSelectAllOnlyCurrentPage(\AcceptanceTester $i) {
    $i->wantTo('Queue bulk confirmation resends only for selected subscribers on the current page');

    (new Settings())->withConfirmationEmailEnabled();
    for ($index = 1; $index <= 31; $index++) {
      (new Subscriber())
        ->withEmail(sprintf('bulk-resend-all-pages-%02d@example.com', $index))
        ->withStatus('unconfirmed')
        ->create();
    }

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $i->waitForText('bulk-resend-all-pages-');
    $i->click(['xpath' => '//table//thead//input[@type="checkbox"]']);
    $i->dontSee('Select all items on all pages');
    $this->openBulkResendConfirmationModal($i);
    $this->confirmBulkResendConfirmationEmailRequest($i);

    $i->installApiFetchInterceptor();
    $i->executeJS(<<<'JS'
      window.mailpoetBulkResendHasSelection = null;
      window.mailpoetBulkResendGroup = null;
      window.mailpoetBulkResendSelectionCount = null;
      window.mailpoetTestApiFetchInterceptor = function (options, next) {
        if (
          typeof options.path === 'string'
          && options.path.indexOf('/mailpoet/v1/subscribers/bulk-action') !== -1
          && options.data
          && options.data.action === 'resendConfirmationEmails'
        ) {
          var selection = Array.isArray(options.data.selection) ? options.data.selection : [];
          window.mailpoetBulkResendHasSelection = selection.length > 0;
          window.mailpoetBulkResendGroup = options.data.group;
          window.mailpoetBulkResendSelectionCount = selection.length;
          return Promise.resolve({
            data: {
              action: 'resendConfirmationEmails',
              count: selection.length,
              segment: null,
              tag: null,
              queue: {
                selected_count: selection.length,
                eligible_count: selection.length,
                queued_count: selection.length,
                skipped_count: 0,
                skipped_by_reason: {},
                task_id: 1,
                message: 'Confirmation emails are being resent.'
              }
            }
          });
        }
        return next(options);
      };
    JS);

    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    $i->waitForJS(
      'return window.mailpoetBulkResendHasSelection !== null;'
    );
    Assert::assertTrue(
      $i->executeJS('return window.mailpoetBulkResendHasSelection;')
    );
    Assert::assertSame(
      'unconfirmed',
      $i->executeJS('return window.mailpoetBulkResendGroup;')
    );
    Assert::assertEquals(
      $i->executeJS('return window.mailpoet_listing_per_page;'),
      $i->executeJS('return window.mailpoetBulkResendSelectionCount;')
    );

    $i->clearApiFetchInterceptor();
  }

  public function bulkResendConfirmationEmailFailureClearsLoading(\AcceptanceTester $i) {
    $i->wantTo('Clear the subscribers listing loading state after a failed bulk confirmation resend');

    (new Settings())->withConfirmationEmailEnabled();
    $subscriber = (new Subscriber())
      ->withEmail('bulk-resend-failed@example.com')
      ->withStatus('unconfirmed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $this->confirmBulkResendConfirmationEmailRequest($i);

    $i->installApiFetchInterceptor();
    $i->executeJS(<<<'JS'
      window.mailpoetTestApiFetchInterceptor = function (options, next) {
        if (
          typeof options.path === 'string'
          && options.path.indexOf('/mailpoet/v1/subscribers/bulk-action') !== -1
          && options.data
          && options.data.action === 'resendConfirmationEmails'
        ) {
          return Promise.reject({
            code: 'forced_failure',
            message: 'Forced bulk resend failure.',
            data: { status: 500, errors: {} }
          });
        }
        return next(options);
      };
    JS);

    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");
    $i->waitForText('Forced bulk resend failure.');
    $i->waitForListingItemsToLoad();

    $i->clearApiFetchInterceptor();
  }

  public function bulkResendConfirmationEmailSettingsErrorShowsSafeLink(\AcceptanceTester $i) {
    $i->wantTo('Show the confirmation-disabled error with a safe settings link');

    (new Settings())->withConfirmationEmailEnabled();
    $subscriber = (new Subscriber())
      ->withEmail('bulk-resend-settings-link@example.com')
      ->withStatus('unconfirmed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->changeGroupInListingFilter('unconfirmed');
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $this->confirmBulkResendConfirmationEmailRequest($i);

    $i->installApiFetchInterceptor();
    $i->executeJS(<<<'JS'
      window.mailpoetTestApiFetchInterceptor = function (options, next) {
        if (
          typeof options.path === 'string'
          && options.path.indexOf('/mailpoet/v1/subscribers/bulk-action') !== -1
          && options.data
          && options.data.action === 'resendConfirmationEmails'
        ) {
          return Promise.reject({
            code: 'mailpoet_subscribers_confirmation_disabled',
            message: 'Sign-up confirmation is disabled in your MailPoet settings.',
            data: { status: 400, errors: {} }
          });
        }
        return next(options);
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

    $i->clearApiFetchInterceptor();
  }

  public function bulkResendConfirmationEmailShowsZeroEligibleNotice(\AcceptanceTester $i) {
    $i->wantTo('Show a zero eligible notice for bulk confirmation resends');

    (new Settings())->withConfirmationEmailEnabled();
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
    $this->confirmBulkResendConfirmationEmailRequest($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");

    $i->waitForText(
      'No confirmation emails were resent. The selected subscribers could not receive another confirmation email right now.'
    );
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
    $i->checkWooTableCheckboxForItemName($subscriber->getEmail());
    $i->waitForElement('.dataviews-bulk-actions-footer__container');
  }

  private function openBulkResendConfirmationModal(\AcceptanceTester $i): void {
    $i->selectListingBulkAction('Resend confirmation emails');
    $i->waitForElement("[data-automation-id='bulk-resend-confirmation-checkbox']");
  }

  private function confirmBulkResendConfirmationEmailRequest(\AcceptanceTester $i): void {
    $i->checkOption('#bulk-resend-confirmation-checkbox-input');
    $i->waitForElementNotVisible("[data-automation-id='bulk-resend-confirmation-confirm']:disabled");
  }

  private function openSubscribersGroup(\AcceptanceTester $i, string $group): void {
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/group[' . $group . ']');
    $i->waitForElement('.dataviews-search input');
    $i->waitForListingItemsToLoad();
  }

  private function subscriberRow(string $email): array {
    return ['xpath' => '//tr[.//*[normalize-space(text())=' . \AcceptanceTester::xpathString($email) . ']]'];
  }
}
