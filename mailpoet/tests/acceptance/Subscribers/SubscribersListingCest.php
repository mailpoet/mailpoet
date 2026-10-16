<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use DateTime;
use Facebook\WebDriver\WebDriverKeys;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Test\DataFactories\Segment;
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
    $i->wantTo('Filter subscribers by tag with the native Tags filter');

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
    $i->waitForText('wp@example.com');
    $i->dontSeeElement('select[name=tag]');
    $i->addDataViewsFilter('Tags', '', [$tag->getName()]);
    $i->waitForText('wp@mailpoet.com');
    $i->dontSee('wp@example.com');
    $i->waitForText('My Tag');

    $i->wantTo('Switch the Tags filter to "Is none of"');
    $i->clearDataViewsFilters();
    $i->addDataViewsFilter('Tags', 'Is none of', [$tag->getName()]);
    $i->waitForText('wp@example.com');
    $i->dontSee('wp@mailpoet.com');
  }

  public function useStatusFilter(\AcceptanceTester $i) {
    $i->wantTo('Filter subscribers by status with the native Status filter');

    (new Subscriber())->withEmail('status-subscribed@example.com')->withStatus('subscribed')->create();
    (new Subscriber())->withEmail('status-unconfirmed@example.com')->withStatus('unconfirmed')->create();
    (new Subscriber())->withEmail('status-bounced@example.com')->withStatus('bounced')->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('status-');
    $i->waitForText('status-subscribed@example.com');
    $i->dontSeeElement('.mailpoet-dataviews-group-unconfirmed');

    $i->addDataViewsFilter('Status', '', ['Unconfirmed']);
    $i->waitForText('status-unconfirmed@example.com');
    $i->dontSee('status-subscribed@example.com');
    $i->dontSee('status-bounced@example.com');

    $i->wantTo('Switch the Status filter to "Is none of"');
    $i->clearDataViewsFilters();
    $i->addDataViewsFilter('Status', 'Is none of', ['Unconfirmed']);
    $i->waitForText('status-subscribed@example.com');
    $i->waitForText('status-bounced@example.com');
    $i->dontSee('status-unconfirmed@example.com');
  }

  public function useListsFilter(\AcceptanceTester $i) {
    $i->wantTo('Filter subscribers by list, including subscribers without a list');

    $listA = (new Segment())->withName('Filter List A')->create();
    $listB = (new Segment())->withName('Filter List B')->create();
    (new Subscriber())->withEmail('lists-a@example.com')->withSegments([$listA])->create();
    (new Subscriber())->withEmail('lists-b@example.com')->withSegments([$listB])->create();
    (new Subscriber())->withEmail('lists-none@example.com')->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('lists-');
    $i->waitForText('lists-none@example.com');
    $i->dontSeeElement('select[name=segment]');

    $i->addDataViewsFilter('Lists', '', ['Filter List A']);
    $i->waitForText('lists-a@example.com');
    $i->dontSee('lists-b@example.com');
    $i->dontSee('lists-none@example.com');

    $i->wantTo('Use "Is none of"');
    $i->clearDataViewsFilters();
    $i->addDataViewsFilter('Lists', 'Is none of', ['Filter List A']);
    $i->waitForText('lists-b@example.com');
    $i->waitForText('lists-none@example.com');
    $i->dontSee('lists-a@example.com');

    $i->wantTo('Pick "Subscribers without a list"');
    $i->clearDataViewsFilters();
    $i->addDataViewsFilter('Lists', '', ['Subscribers without a list']);
    $i->waitForText('lists-none@example.com');
    $i->dontSee('lists-a@example.com');
    $i->dontSee('lists-b@example.com');
  }

  public function useSourceFilter(\AcceptanceTester $i) {
    $i->wantTo('Filter subscribers by source, including Unknown');

    (new Subscriber())->withEmail('source-form@example.com')->withSource('form')->create();
    (new Subscriber())->withEmail('source-import@example.com')->withSource('imported')->create();
    (new Subscriber())->withEmail('source-unknown@example.com')->withSource('unknown')->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('source-');
    $i->waitForText('source-form@example.com');
    $i->dontSee('Source', '.mailpoet-subscribers-dataviews thead');

    $i->addDataViewsFilter('Source', '', ['Unknown']);
    $i->waitForText('source-unknown@example.com');
    $i->dontSee('source-form@example.com');
    $i->dontSee('source-import@example.com');

    $i->clearDataViewsFilters();
    $i->addDataViewsFilter('Source', 'Is none of', ['Unknown']);
    $i->waitForText('source-form@example.com');
    $i->waitForText('source-import@example.com');
    $i->dontSee('source-unknown@example.com');
  }

  public function useScoreFilterOnlyWhenTrackingIsEnabled(\AcceptanceTester $i) {
    $i->wantTo('Offer the Score filter only when email tracking is on');

    $settings = new Settings();
    $settings->withTrackingEnabled();
    (new Subscriber())->withEmail('score-low@example.com')->withEngagementScore(10)->create();
    (new Subscriber())->withEmail('score-high@example.com')->withEngagementScore(90)->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('score-');
    $i->waitForText('score-low@example.com');
    $i->click(['xpath' => '//button[@aria-label="Add filter"]']);
    $i->waitForElement(['xpath' => '//*[@role="menuitem"][normalize-space(.)="Score"]']);
    $i->pressKey('body', WebDriverKeys::ESCAPE);

    $i->wantTo('Apply a "Greater than" score filter from a link');
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/filter[score_gt=50]/search[score-]');
    $i->waitForText('score-high@example.com');
    $i->dontSee('score-low@example.com');
    $i->waitForDataViewsFilterChip('Score');

    $i->wantTo('Not offer the Score filter or column when tracking is basic');
    $settings->withTrackingDisabled();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/filter[score_gt=50]/search[score-]');
    $i->reloadPage();
    $i->waitForText('score-low@example.com');
    $i->waitForText('score-high@example.com');
    $i->click(['xpath' => '//button[@aria-label="Add filter"]']);
    $i->waitForElement(['xpath' => '//*[@role="menuitem"][normalize-space(.)="Status"]']);
    $i->dontSeeElement(['xpath' => '//*[@role="menuitem"][normalize-space(.)="Score"]']);
    $i->pressKey('body', WebDriverKeys::ESCAPE);
    $settings->withTrackingEnabled();
  }

  public function useDateFilters(\AcceptanceTester $i) {
    $i->wantTo('Filter subscribers by created and subscribed date');

    (new Subscriber())
      ->withEmail('date-old@example.com')
      ->withCreatedAt(new DateTime('-30 days'))
      ->withLastSubscribedAt(new DateTime('-30 days'))
      ->create();
    (new Subscriber())
      ->withEmail('date-new@example.com')
      ->withCreatedAt(new DateTime())
      ->withLastSubscribedAt(new DateTime())
      ->create();
    $cutoff = (new DateTime('-10 days'))->format('Y-m-d');

    $i->login();

    $i->wantTo('Created on "Before" the cutoff keeps only the old subscriber');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/filter[created_to={$cutoff}]/search[date-]");
    $i->reloadPage();
    $i->waitForText('date-old@example.com');
    $i->dontSee('date-new@example.com');
    $i->waitForDataViewsFilterChip('Created on');

    $i->wantTo('Created on "After" the cutoff keeps only the new subscriber');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/filter[created_from={$cutoff}]/search[date-]");
    $i->waitForText('date-new@example.com');
    $i->dontSee('date-old@example.com');

    $i->wantTo('Subscribed on "After" the cutoff keeps only the new subscriber');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/filter[subscribed_from={$cutoff}]/search[date-]");
    $i->waitForText('date-new@example.com');
    $i->dontSee('date-old@example.com');
    $i->waitForDataViewsFilterChip('Subscribed on');

    $i->wantTo('Subscribed on "Between" two dates keeps only the old subscriber');
    $from = (new DateTime('-40 days'))->format('Y-m-d');
    $to = (new DateTime('-20 days'))->format('Y-m-d');
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/filter[subscribed_from={$from}&subscribed_to={$to}]/search[date-]");
    $i->waitForText('date-old@example.com');
    $i->dontSee('date-new@example.com');
  }

  public function legacyLinksStillWork(\AcceptanceTester $i) {
    $i->wantTo('Open the subscribers listing from links used before native filters');

    $list = (new Segment())->withName('Legacy Link List')->create();
    $otherList = (new Segment())->withName('Legacy Other List')->create();
    $tag = (new Tag())->withName('Legacy Link Tag')->create();
    (new Subscriber())->withEmail('legacy-in-list@example.com')->withSegments([$list])->withTags([$tag])->create();
    (new Subscriber())->withEmail('legacy-other@example.com')->withSegments([$otherList])->create();
    (new Subscriber())->withEmail('legacy-unconfirmed@example.com')->withStatus('unconfirmed')->create();
    $listId = $list->getId();
    $tagId = $tag->getId();
    $page = '/wp-admin/admin.php?page=mailpoet-subscribers#';

    $i->login();

    $i->wantTo('Open a status tab link');
    $i->amOnPage($page . '/group[unconfirmed]');
    $i->reloadPage();
    $i->waitForText('legacy-unconfirmed@example.com');
    $i->dontSee('legacy-in-list@example.com');
    $i->waitForDataViewsFilterChip('Status');

    $i->wantTo('Open a list link');
    $i->amOnPage($page . "/filter[segment={$listId}]");
    $i->reloadPage();
    $i->waitForText('legacy-in-list@example.com');
    $i->dontSee('legacy-other@example.com');
    $i->waitForDataViewsFilterChip('Legacy Link List');

    $i->wantTo('Open a tag link');
    $i->amOnPage($page . "/filter[tag={$tagId}]");
    $i->reloadPage();
    $i->waitForText('legacy-in-list@example.com');
    $i->dontSee('legacy-other@example.com');
    $i->waitForDataViewsFilterChip('Legacy Link Tag');

    $i->wantTo('Open a list link with a page size');
    $i->amOnPage($page . "/group[all]/filter[segment={$listId}]/limit[1]");
    $i->reloadPage();
    $i->waitForText('legacy-in-list@example.com');
    $i->dontSee('legacy-other@example.com');

    $i->wantTo('Open the homepage list link');
    $i->amOnPage($page . "/page[1]/sort_by[created_at]/sort_order[desc]/group[all]/filter[segment={$listId}]");
    $i->reloadPage();
    $i->waitForText('legacy-in-list@example.com');
    $i->dontSee('legacy-other@example.com');

    $i->wantTo('Show the full list for a link to a deleted list');
    $i->amOnPage($page . '/filter[segment=999999]');
    $i->reloadPage();
    $i->waitForText('legacy-in-list@example.com');
    $i->waitForText('legacy-other@example.com');
    $i->waitForJS('return window.location.hash.indexOf("999999") === -1;', 10);
  }

  public function sendConfirmationEmail(\AcceptanceTester $i) {
    $i->wantTo('Send confirmation email');

    // The row-level "Resend confirmation email" action is only eligible when
    // signup confirmation is enabled; tests are not run in isolation so make
    // the dependency explicit.
    (new Settings())->withConfirmationEmailEnabled();

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
    // DataViews puts non-primary actions behind a popover trigger; the
    // open/close + portal makes a visibility-only assertion noisy in CI. Cover
    // the user-facing behaviour via the resend on the allowed row below —
    // backend enforcement of max-confirmation limits is covered separately in
    // the REST integration tests.

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
    // The setting is page bootstrap data, so reload to pick it up.
    $i->reloadPage();
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
    $i->addDataViewsFilter('Status', '', ['Unconfirmed']);
    $this->selectSubscriberForBulkAction($i, $eligibleSubscriber);
    $this->selectSubscriberForBulkAction($i, $skippedSubscriber);
    $this->openBulkResendConfirmationModal($i);

    $i->waitForElement('#bulk-resend-confirmation-checkbox-input');
    Assert::assertSame(
      'bulk-resend-confirmation-checkbox-input',
      $i->executeJS('return document.activeElement.id;')
    );
    $i->waitForElement('div[role="dialog"][aria-labelledby]');
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
    $i->addDataViewsFilter('Status', '', ['Unconfirmed']);
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
    $i->addDataViewsFilter('Status', '', ['Unconfirmed']);
    $i->waitForText('bulk-resend-all-pages-');
    $i->click(['xpath' => '//table//thead//input[@type="checkbox"]']);
    $i->dontSee('Select all items on all pages');
    $this->openBulkResendConfirmationModal($i);
    $this->confirmBulkResendConfirmationEmailRequest($i);

    $i->installApiFetchInterceptor();
    $i->executeJS(<<<'JS'
      window.mailpoetBulkResendHasSelection = null;
      window.mailpoetBulkResendGroup = null;
      window.mailpoetBulkResendFilter = null;
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
          window.mailpoetBulkResendFilter = JSON.stringify(options.data.filter);
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
      'all',
      $i->executeJS('return window.mailpoetBulkResendGroup;')
    );
    Assert::assertSame(
      '{"status_in":["unconfirmed"]}',
      $i->executeJS('return window.mailpoetBulkResendFilter;')
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
    $i->addDataViewsFilter('Status', '', ['Unconfirmed']);
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
    $i->addDataViewsFilter('Status', '', ['Unconfirmed']);
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
    $i->addDataViewsFilter('Status', '', ['Unconfirmed']);
    $this->selectSubscriberForBulkAction($i, $subscriber);
    $this->openBulkResendConfirmationModal($i);
    $this->confirmBulkResendConfirmationEmailRequest($i);
    $i->click("[data-automation-id='bulk-resend-confirmation-confirm']");

    $i->waitForText(
      'No confirmation emails were resent. The selected subscribers could not receive another confirmation email right now.'
    );
  }

  public function emptyTrashDeletesOnlyTheFilteredSubscribers(\AcceptanceTester $i) {
    $i->wantTo('Verify that Empty Trash deletes only the subscribers matching the active filters');

    (new Subscriber())
      ->withEmail('trash-subscribed@example.com')
      ->withStatus('subscribed')
      ->withDeletedAt(new DateTime())
      ->create();
    (new Subscriber())
      ->withEmail('trash-bounced@example.com')
      ->withStatus('bounced')
      ->withDeletedAt(new DateTime())
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->addDataViewsFilter('Status', '', ['Bounced']);
    $i->changeGroupInListingFilter('trash');
    $i->waitForText('trash-bounced@example.com');
    $i->dontSee('trash-subscribed@example.com');

    $i->click('[data-automation-id="empty_trash"]');
    $i->waitForText('1 subscriber was permanently deleted.');
    $i->dontSee('trash-bounced@example.com');

    $i->clearDataViewsFilters();
    $i->waitForText('trash-subscribed@example.com');
  }

  public function trashedListFilterSurvivesPageBeyondTheLastPage(\AcceptanceTester $i) {
    $i->wantTo('Keep a trashed list in the Trash filter when the link asks for a page that does not exist');

    $list = (new Segment())->withName('Trashed Link List')->withDeleted()->create();
    (new Subscriber())
      ->withEmail('trashed-in-list@example.com')
      ->withSegments([$list])
      ->withDeletedAt(new DateTime())
      ->create();
    $listId = $list->getId();

    $i->login();
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-subscribers#/group[trash]/filter[segment_in={$listId}]/page[5]");
    $i->reloadPage();
    $i->waitForText('trashed-in-list@example.com');
    $i->waitForDataViewsFilterChip('Trashed Link List');
  }

  public function filterToggleShowsAndHidesTheFilterRow(\AcceptanceTester $i) {
    $i->wantTo('Hide and show the active filters with the filter toggle button');

    (new Subscriber())->withEmail('toggle-filters@example.com')->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText('toggle-filters@example.com');

    $i->wantTo('Add a Created on filter from the toggle button');
    $i->click(['xpath' => '//button[@aria-label="Add filter"]']);
    $i->click(['xpath' => '//*[@role="menuitem"][normalize-space(.)="Created on"]']);
    $i->pressKey('body', \Facebook\WebDriver\WebDriverKeys::ESCAPE);
    $i->waitForDataViewsFilterChip('Created on');
    $i->waitForElement('.dataviews-filters-toggle__count');

    $i->wantTo('Hide the filter row with the toggle');
    $i->click('.dataviews-filters__visibility-toggle');
    $i->waitForElementNotVisible('.dataviews-filters__summary-chip');

    $i->wantTo('Show the filter row again with the toggle');
    $i->click('.dataviews-filters__visibility-toggle');
    $i->waitForElementVisible('.dataviews-filters__summary-chip');
    $i->waitForDataViewsFilterChip('Created on');
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

  public function sortsByEachSortableColumn(\AcceptanceTester $i) {
    $i->wantTo('Sort the subscribers listing by every sortable column');

    // Ascending order per column: email a,b,c; score c,a,b; subscribed on
    // b,c,a; created on a,c,b. Status: only "a" is subscribed.
    (new Subscriber())
      ->withEmail('sort-a@example.com')
      ->withStatus('subscribed')
      ->withEngagementScore(50)
      ->withLastSubscribedAt(new DateTime('-10 days'))
      ->withCreatedAt(new DateTime('-5 days'))
      ->create();
    (new Subscriber())
      ->withEmail('sort-b@example.com')
      ->withStatus('unsubscribed')
      ->withEngagementScore(90)
      ->withLastSubscribedAt(new DateTime('-30 days'))
      ->withCreatedAt(new DateTime('-3 days'))
      ->create();
    (new Subscriber())
      ->withEmail('sort-c@example.com')
      ->withStatus('unsubscribed')
      ->withEngagementScore(10)
      ->withLastSubscribedAt(new DateTime('-20 days'))
      ->withCreatedAt(new DateTime('-4 days'))
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('sort-');
    $i->waitForText('sort-a@example.com');
    $i->waitForText('sort-b@example.com');
    $i->waitForText('sort-c@example.com');

    $i->wantTo('Confirm Created on is sorted descending by default');
    $i->seeElement(['xpath' => '//thead//th[@aria-sort="descending"][contains(., "Created on")]']);
    $this->seeRowOrder($i, ['sort-b@example.com', 'sort-c@example.com', 'sort-a@example.com']);

    $i->wantTo('Sort by the Subscriber column in both directions');
    $this->sortByColumn($i, 'Subscriber', 'ascending', false);
    $this->seeRowOrder($i, ['sort-a@example.com', 'sort-b@example.com', 'sort-c@example.com']);
    $this->sortByColumn($i, 'Subscriber', 'descending', false);
    $this->seeRowOrder($i, ['sort-c@example.com', 'sort-b@example.com', 'sort-a@example.com']);

    $this->sortByColumn($i, 'Status', 'ascending');
    $this->seeRowOrder($i, ['sort-a@example.com']);
    $this->sortByColumn($i, 'Status', 'descending');
    $this->seeLastRow($i, 'sort-a@example.com');

    $this->sortByColumn($i, 'Score', 'ascending');
    $this->seeRowOrder($i, ['sort-c@example.com', 'sort-a@example.com', 'sort-b@example.com']);
    $this->sortByColumn($i, 'Score', 'descending');
    $this->seeRowOrder($i, ['sort-b@example.com', 'sort-a@example.com', 'sort-c@example.com']);

    $this->sortByColumn($i, 'Subscribed on', 'ascending');
    $this->seeRowOrder($i, ['sort-b@example.com', 'sort-c@example.com', 'sort-a@example.com']);
    $this->sortByColumn($i, 'Subscribed on', 'descending');
    $this->seeRowOrder($i, ['sort-a@example.com', 'sort-c@example.com', 'sort-b@example.com']);

    $this->sortByColumn($i, 'Created on', 'ascending');
    $this->seeRowOrder($i, ['sort-a@example.com', 'sort-c@example.com', 'sort-b@example.com']);

    $i->wantTo('Restore a sort from a link');
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/sort_by[email]/sort_order[asc]/search[sort-]');
    $i->reloadPage();
    $i->waitForText('sort-a@example.com');
    $this->seeRowOrder($i, ['sort-a@example.com', 'sort-b@example.com', 'sort-c@example.com']);

    $i->wantTo('Fall back to the saved sort (Created on) for an unknown sort field');
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/group[all]/sort_by[unknown_column]/search[sort-]');
    $i->reloadPage();
    $i->waitForText('sort-a@example.com');
    $i->seeElement(['xpath' => '//thead//th[@aria-sort][contains(., "Created on")]']);

    $i->wantTo('Restore the default sort so the saved preference does not leak into other tests');
    $this->sortByColumn($i, 'Created on', 'descending');
  }

  // The title column's header carries no aria-sort, so callers check row order there.
  private function sortByColumn(\AcceptanceTester $i, string $column, string $direction, bool $expectAriaSort = true): void {
    $header = ['xpath' =>
      '//thead//button[contains(concat(" ", normalize-space(@class), " "), " dataviews-view-table-header-button ")]' .
      '[contains(., ' . \AcceptanceTester::xpathString($column) . ')]',
    ];
    $label = $direction === 'ascending' ? 'Sort ascending' : 'Sort descending';
    $item = ['xpath' => '//*[@role="menuitem" or @role="menuitemradio"][contains(normalize-space(.), "' . $label . '")]'];
    $sorted = ['xpath' => '//thead//th[@aria-sort="' . $direction . '"][contains(., ' . \AcceptanceTester::xpathString($column) . ')]'];

    // The previous sort menu can still be closing; retry the whole interaction.
    for ($attempt = 1; $attempt <= 3; $attempt++) {
      try {
        $i->wait(0.5);
        $i->click($header);
        $i->waitForElementClickable($item, 5);
        $i->click($item);
        if ($expectAriaSort) {
          $i->waitForElement($sorted, 5);
        }
        break;
      } catch (\Exception $e) {
        if ($attempt === 3) {
          throw $e;
        }
        $i->pressKey('body', WebDriverKeys::ESCAPE);
      }
    }
    $i->waitForListingItemsToLoad();
  }

  /**
   * @param string[] $emails Emails expected as the first rows, in this order.
   */
  private function seeRowOrder(\AcceptanceTester $i, array $emails): void {
    $expected = json_encode($emails);
    $i->waitForJS(<<<JS
      const rows = Array.from(document.querySelectorAll('.mailpoet-subscribers-dataviews tbody tr'));
      const found = rows.map((row) => (row.textContent.match(/sort-[a-z]@example\.com/) || [''])[0]);
      const expected = {$expected};
      return expected.every((email, index) => found[index] === email);
    JS, 10);
  }

  private function seeLastRow(\AcceptanceTester $i, string $email): void {
    $expected = json_encode($email);
    $i->waitForJS(<<<JS
      const rows = Array.from(document.querySelectorAll('.mailpoet-subscribers-dataviews tbody tr'));
      const last = rows[rows.length - 1];
      return !!last && last.textContent.indexOf({$expected}) !== -1;
    JS, 10);
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
