<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Assert;

class NewslettersListingCest {
  public function newslettersListing(\AcceptanceTester $i) {
    $standardNewsletterSubject = 'Standard newsletter';
    $welcomeEmailSubject = 'Welcome email';
    $postNotificationEmailSubject = 'Post notification';
    $standardNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject($standardNewsletterSubject)
      ->create();
    $welcomeEmail = (new Newsletter())
      ->withSentStatus()
      ->withSubject($welcomeEmailSubject)
      ->withWelcomeTypeForSegment()
      ->create();
    $postNotificationEmail = (new Newsletter())
      ->withSentStatus()
      ->withPostNotificationsType()
      ->withSubject($postNotificationEmailSubject)
      ->create();

    $i->wantTo('Open newsletters listings page');

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // Standard newsletters is the default tab
    $i->waitForText('Standard newsletter', 5, '[data-automation-id="listing_item_' . $standardNewsletter->getId() . '"]');

    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('Post notification', 5, '[data-automation-id="listing_item_' . $postNotificationEmail->getId() . '"]');
    $i->seeNoJSErrors();
  }

  public function sortByDateCreatedAndLastModified(\AcceptanceTester $i) {
    $oldest = $this->createNewsletterWithDates('Created first, edited last', '2026-01-01 10:00:00', '2026-03-01 10:00:00');
    $middle = $this->createNewsletterWithDates('Created second, edited first', '2026-02-01 10:00:00', '2026-01-15 10:00:00');
    $newest = $this->createNewsletterWithDates('Created last, edited second', '2026-03-01 10:00:00', '2026-02-15 10:00:00');

    $i->wantTo('Sort newsletters by Date created, oldest first');
    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/sort_by[created_at]/sort_order[asc]');
    $i->waitForListingItemsToLoad();
    Assert::assertSame(
      [$oldest->getId(), $middle->getId(), $newest->getId()],
      $this->grabListedNewsletterIds($i)
    );

    $i->wantTo('See Date created selected and Last modified offered in Sort by');
    $i->click(['xpath' => '//button[@aria-label="View options"]']);
    $sortBySelect = ['xpath' => '//select[option[normalize-space()="Date sent"]]'];
    $i->waitForElement($sortBySelect);
    $i->seeOptionIsSelected($sortBySelect, 'Date created');
    $i->seeElement(['xpath' => '//select/option[normalize-space()="Last modified"]']);

    $i->wantTo('Sort newsletters by Last modified, newest first');
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/sort_by[updated_at]/sort_order[desc]');
    $i->reloadPage();
    $i->waitForListingItemsToLoad();
    Assert::assertSame(
      [$oldest->getId(), $newest->getId(), $middle->getId()],
      $this->grabListedNewsletterIds($i)
    );
    $i->seeNoJSErrors();
  }

  public function statisticsColumn(\AcceptanceTester $i) {
    $subject = 'Statistics column subject';
    (new Newsletter())->withSubject($subject)->create();

    $i->wantTo('Check if statistics column is visible depending on tracking option');

    $i->login();

    // column is hidden when tracking is not enabled
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->click('[data-automation-id="tracking-basic-radio"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('Emails');
    $i->waitForText($subject);
    $i->dontSee('Clicked, Opened');

    // column is visible when tracking is enabled
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->click('[data-automation-id="tracking-partial-radio"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('Emails');
    $i->waitForText($subject);
    $i->see('Clicked, Opened');
    $i->seeNoJSErrors();
  }

  private function createNewsletterWithDates(string $subject, string $createdAt, string $updatedAt): NewsletterEntity {
    $newsletter = (new Newsletter())
      ->withSubject($subject)
      ->withDraftStatus()
      ->withUnsubscribeToken(substr(md5($subject), 0, 15))
      ->create();
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $entityManager->getConnection()->executeStatement(
      'UPDATE ' . $entityManager->getClassMetadata(NewsletterEntity::class)->getTableName() .
      ' SET created_at = :createdAt, updated_at = :updatedAt WHERE id = :id',
      ['createdAt' => $createdAt, 'updatedAt' => $updatedAt, 'id' => $newsletter->getId()]
    );
    return $newsletter;
  }

  /**
   * @return int[]
   */
  private function grabListedNewsletterIds(\AcceptanceTester $i): array {
    $automationIds = $i->grabMultiple('tbody [data-automation-id^="listing_item_"]', 'data-automation-id');
    return array_map(function (string $automationId): int {
      return (int)str_replace('listing_item_', '', $automationId);
    }, $automationIds);
  }

  public function filterByStatusAndLists(\AcceptanceTester $i) {
    $i->wantTo('Filter the standard emails listing by status and list');
    $segment = (new Segment())->withName('Filter list')->create();
    $sentInList = (new Newsletter())->withSubject('Sent in list')->withSentStatus()->withSegments([$segment])->create();
    $draftInList = (new Newsletter())->withSubject('Draft in list')->withDraftStatus()->withSegments([$segment])->create();
    $sentNoList = (new Newsletter())->withSubject('Sent no list')->withSentStatus()->create();
    $scheduledNoList = (new Newsletter())->withSubject('Scheduled no list')->withScheduledStatus()->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Scheduled no list', 10, '[data-automation-id="newsletters_listing_standard"]');

    $i->applyDataViewsFilter('Status', ['Sent', 'Draft']);
    $i->waitForElementNotVisible('[data-automation-id="listing_item_' . $scheduledNoList->getId() . '"]');
    $i->seeElement('[data-automation-id="listing_item_' . $sentInList->getId() . '"]');
    $i->seeElement('[data-automation-id="listing_item_' . $draftInList->getId() . '"]');
    $i->seeElement('[data-automation-id="listing_item_' . $sentNoList->getId() . '"]');
    $i->waitForHashContaining('filter[status=');

    $i->applyDataViewsFilter('Lists', ['Filter list']);
    $i->waitForElementNotVisible('[data-automation-id="listing_item_' . $sentNoList->getId() . '"]');
    $i->seeElement('[data-automation-id="listing_item_' . $sentInList->getId() . '"]');
    $i->seeElement('[data-automation-id="listing_item_' . $draftInList->getId() . '"]');
    $i->waitForHashFilterValue('segment', (string)$segment->getId());
    $i->seeNoJSErrors();
  }

  public function restoreFiltersFromDeepLink(\AcceptanceTester $i) {
    $i->wantTo('Restore filters from a deep link');
    $segment = (new Segment())->withName('Deep link list')->create();
    $sentInList = (new Newsletter())->withSubject('Deep sent in list')->withSentStatus()->withSegments([$segment])->create();
    $draftInList = (new Newsletter())->withSubject('Deep draft in list')->withDraftStatus()->withSegments([$segment])->create();
    $sentNoList = (new Newsletter())->withSubject('Deep sent no list')->withSentStatus()->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/filter[status=sent&segment=' . $segment->getId() . ']');
    $i->waitForElement('[data-automation-id="listing_item_' . $sentInList->getId() . '"]');
    $i->waitForElement($this->filterChip('Status'));
    $i->waitForElement($this->filterChip('Lists'));
    $i->dontSeeElement('[data-automation-id="listing_item_' . $draftInList->getId() . '"]');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $sentNoList->getId() . '"]');
  }

  public function keepLegacyHashLinksWorking(\AcceptanceTester $i) {
    $i->wantTo('Open the legacy hash links');
    $segment = (new Segment())->withName('Legacy list')->create();
    $sentInList = (new Newsletter())->withSubject('Legacy sent in list')->withSentStatus()->withSegments([$segment])->create();
    $draftInList = (new Newsletter())->withSubject('Legacy draft in list')->withDraftStatus()->withSegments([$segment])->create();
    $sentNoList = (new Newsletter())->withSubject('Legacy sent no list')->withSentStatus()->create();
    $trashed = (new Newsletter())->withSubject('Legacy trashed')->withDraftStatus()->withDeleted()->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/group[sent]/filter[segment=' . $segment->getId() . ']');
    $i->waitForElement('.mailpoet-dataviews-group-all.is-active');
    $i->waitForElement($this->filterChip('Status'));
    $i->waitForElement($this->filterChip('Lists'));
    $i->waitForElement('[data-automation-id="listing_item_' . $sentInList->getId() . '"]');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $draftInList->getId() . '"]');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $sentNoList->getId() . '"]');
    $i->waitForHashFilterValue('status', 'sent');
    $i->dontSeeElement('.mailpoet-dataviews-group-sent');

    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/group[trash]');
    $i->waitForElement('.mailpoet-dataviews-group-trash.is-active');
    $i->waitForElement('[data-automation-id="listing_item_' . $trashed->getId() . '"]');
    $i->seeElement('[data-automation-id="empty_trash"]');
  }

  public function explicitStatusFilterWinsOverLegacyGroup(\AcceptanceTester $i) {
    $i->wantTo('Prefer the status filter over a legacy group in the same link');
    $sent = (new Newsletter())->withSubject('Wins sent')->withSentStatus()->create();
    $draft = (new Newsletter())->withSubject('Wins draft')->withDraftStatus()->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/group[sent]/filter[status=draft]');
    $i->waitForElement('[data-automation-id="listing_item_' . $draft->getId() . '"]');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $sent->getId() . '"]');
    $i->waitForHashFilterValue('status', 'draft');
  }

  public function filterByStatusOnAllAndTrashTabs(\AcceptanceTester $i) {
    $i->wantTo('Keep the status filter across the All and Trash tabs');
    $sent = (new Newsletter())->withSubject('Tab sent')->withSentStatus()->create();
    $draft = (new Newsletter())->withSubject('Tab draft')->withDraftStatus()->create();
    $trashedSent = (new Newsletter())->withSubject('Tab trashed sent')->withSentStatus()->withDeleted()->create();
    $trashedDraft = (new Newsletter())->withSubject('Tab trashed draft')->withDraftStatus()->withDeleted()->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('[data-automation-id="listing_item_' . $draft->getId() . '"]');
    $i->dontSeeElement('.mailpoet-dataviews-group-draft');
    $i->dontSeeElement('.mailpoet-dataviews-group-sent');

    $i->applyDataViewsFilter('Status', ['Sent']);
    $i->waitForElementNotVisible('[data-automation-id="listing_item_' . $draft->getId() . '"]');
    $i->seeElement('[data-automation-id="listing_item_' . $sent->getId() . '"]');

    $i->changeGroupInListingFilter('trash');
    $i->waitForElement('[data-automation-id="listing_item_' . $trashedSent->getId() . '"]');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $trashedDraft->getId() . '"]');
    $i->waitForElement($this->filterChip('Status'));
    $i->waitForHashFilterValue('status', 'sent');

    $i->changeGroupInListingFilter('all');
    $i->waitForElement('[data-automation-id="listing_item_' . $sent->getId() . '"]');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $draft->getId() . '"]');
    $i->waitForElement($this->filterChip('Status'));
  }

  public function showCountsInStatusOptions(\AcceptanceTester $i) {
    $i->wantTo('See the email counts in the status filter options');
    (new Newsletter())->withSubject('Count draft one')->withDraftStatus()->create();
    (new Newsletter())->withSubject('Count draft two')->withDraftStatus()->create();
    (new Newsletter())->withSubject('Count sent')->withSentStatus()->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Count sent', 10, '[data-automation-id="newsletters_listing_standard"]');
    $addFilter = ['xpath' => '//button[@aria-label="Add filter" or normalize-space(.)="Add filter"]'];
    $i->waitForElementClickable($addFilter);
    $i->click($addFilter);
    $i->click(['xpath' => '//*[@role="menuitem"][contains(normalize-space(.), "Status")]']);
    $i->waitForText('Draft (2)');
    $i->waitForText('Sent (1)');
    $i->waitForText('Scheduled (0)');
  }

  public function dropDeletedListFromDeepLink(\AcceptanceTester $i) {
    $i->wantTo('Open a deep link with a deleted list without a list filter');
    $segment = (new Segment())->withName('Existing list')->create();
    (new Newsletter())->withSubject('Pruned link in list')->withSentStatus()->withSegments([$segment])->create();
    $newsletter = (new Newsletter())->withSubject('Pruned link email')->withSentStatus()->create();
    $deletedId = $segment->getId() + 1000;

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/filter[segment=' . $deletedId . ']');
    $i->waitForElement('[data-automation-id="listing_item_' . $newsletter->getId() . '"]');
    $i->waitForJS('return decodeURIComponent(window.location.hash).indexOf("segment") === -1;', 10);
    $i->dontSeeElement($this->filterChip('Lists'));
  }

  public function filterToggleShowsAndHidesTheFilterRow(\AcceptanceTester $i) {
    $i->wantTo('Hide and show the active filters with the filter toggle button');
    $segment = (new Segment())->withName('Toggle list')->create();
    (new Newsletter())->withSubject('Toggle in list')->withSentStatus()->withSegments([$segment])->create();
    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/filter[segment=' . $segment->getId() . ']');
    $i->waitForText('Toggle in list');
    $i->waitForElement($this->filterChip('Lists'));
    $i->waitForElement('.dataviews-filters-toggle__count');

    $i->wantTo('Hide the filter row with the toggle');
    $i->click('.dataviews-filters__visibility-toggle');
    $i->waitForElementNotVisible('.dataviews-filters__summary-chip');

    $i->wantTo('Show the filter row again with the toggle');
    $i->click('.dataviews-filters__visibility-toggle');
    $i->waitForElementVisible('.dataviews-filters__summary-chip');
    $i->waitForElement($this->filterChip('Lists'));
  }

  public function dropDeletedListFromDeepLinkWhenNoEmailHasList(\AcceptanceTester $i) {
    $i->wantTo('Open a deep link with a deleted list when no email has a list');
    $newsletter = (new Newsletter())->withSubject('No list email')->withSentStatus()->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/filter[segment=1000]');
    $i->waitForElement('[data-automation-id="listing_item_' . $newsletter->getId() . '"]');
    $i->waitForJS('return decodeURIComponent(window.location.hash).indexOf("segment") === -1;', 10);
    $i->dontSeeElement($this->filterChip('Lists'));
  }

  public function keepKnownListWhenDroppingDeletedOne(\AcceptanceTester $i) {
    $i->wantTo('Keep the existing list when a deep link also names a deleted one');
    $segment = (new Segment())->withName('Kept list')->create();
    $inList = (new Newsletter())->withSubject('Kept in list')->withSentStatus()->withSegments([$segment])->create();
    $noList = (new Newsletter())->withSubject('Kept no list')->withSentStatus()->create();
    $deletedId = $segment->getId() + 1000;

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/filter[segment=' . $segment->getId() . ',' . $deletedId . ']');
    $i->waitForElement('[data-automation-id="listing_item_' . $inList->getId() . '"]');
    $i->waitForElementNotVisible('[data-automation-id="listing_item_' . $noList->getId() . '"]');
    $i->waitForHashFilterValue('segment', (string)$segment->getId());
    $i->waitForJS('return decodeURIComponent(window.location.hash).indexOf(' . json_encode((string)$deletedId) . ') === -1;', 10);
    $i->waitForElement($this->filterChip('Lists'));
  }

  public function keepBeforeOperatorAfterReload(\AcceptanceTester $i) {
    $i->wantTo('Open a sent on "before" deep link with the same operator');
    $early = (new Newsletter())->withSubject('Before early')->withSentStatus()->withSentAt(new \DateTimeImmutable('2026-01-10 12:00:00'))->create();
    $edge = (new Newsletter())->withSubject('Before edge')->withSentStatus()->withSentAt(new \DateTimeImmutable('2026-01-15 12:00:00'))->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/standard/filter[sent_to=2026-01-14]');
    $i->waitForElement('[data-automation-id="listing_item_' . $early->getId() . '"]');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $edge->getId() . '"]');
    $i->waitForElement(['xpath' => '//*[contains(@class, "dataviews-filters__summary-chip-container")][contains(., "Sent on")][contains(., "is before:")]']);

    $i->reloadPage();
    $i->waitForElement('[data-automation-id="listing_item_' . $early->getId() . '"]');
    $i->waitForElement(['xpath' => '//*[contains(@class, "dataviews-filters__summary-chip-container")][contains(., "Sent on")][contains(., "is before:")]']);
    $i->waitForHashFilterValue('sent_to', '2026-01-14');
    $i->dontSeeElement('[data-automation-id="listing_item_' . $edge->getId() . '"]');
  }

  public function startNewSentOnFilterOnBetween(\AcceptanceTester $i) {
    $i->wantTo('Start a new sent on filter on the between operator');
    (new Newsletter())->withSubject('Operator email')->withSentStatus()->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Operator email', 10, '[data-automation-id="newsletters_listing_standard"]');
    $addFilter = ['xpath' => '//button[@aria-label="Add filter" or normalize-space(.)="Add filter"]'];
    $i->waitForElementClickable($addFilter);
    $i->click($addFilter);
    $i->click(['xpath' => '//*[@role="menuitem"][contains(normalize-space(.), "Sent on")]']);
    $i->waitForJS('var select = document.querySelector(".dataviews-filters__summary-operators-filter-select select"); return !!select && select.value === "between";', 10);
  }

  public function filterNotificationHistoryBySentOn(\AcceptanceTester $i) {
    $i->wantTo('Filter the notification history by the sent on date');
    $parent = (new Newsletter())->withSubject('History parent')->withPostNotificationsType()->create();
    $january = (new Newsletter())
      ->withSubject('History January')
      ->withPostNotificationHistoryType()
      ->withParent($parent)
      ->withSentStatus()
      ->withSentAt(new \DateTimeImmutable('2026-01-10 12:00:00'))
      ->create();
    $march = (new Newsletter())
      ->withSubject('History March')
      ->withPostNotificationHistoryType()
      ->withParent($parent)
      ->withSentStatus()
      ->withSentAt(new \DateTimeImmutable('2026-03-10 12:00:00'))
      ->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/notification/history/' . $parent->getId() . '/filter[sent_from=2026-01-01&sent_to=2026-01-31]');
    $i->waitForElement('[data-automation-id="listing_item_' . $january->getId() . '"]');
    $i->waitForElement($this->filterChip('Sent on'));
    $i->dontSeeElement('[data-automation-id="listing_item_' . $march->getId() . '"]');
  }

  public function showSingleDaySentOnLinkAsOnOperator(\AcceptanceTester $i) {
    $i->wantTo('Open a single-day sent on deep link as the "is" operator');
    $parent = (new Newsletter())->withSubject('Day parent')->withPostNotificationsType()->create();
    $day = (new Newsletter())
      ->withSubject('Day match')
      ->withPostNotificationHistoryType()
      ->withParent($parent)
      ->withSentStatus()
      ->withSentAt(new \DateTimeImmutable('2026-01-10 12:00:00'))
      ->create();
    $other = (new Newsletter())
      ->withSubject('Day other')
      ->withPostNotificationHistoryType()
      ->withParent($parent)
      ->withSentStatus()
      ->withSentAt(new \DateTimeImmutable('2026-01-11 12:00:00'))
      ->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/notification/history/' . $parent->getId() . '/filter[sent_from=2026-01-10&sent_to=2026-01-10]');
    $i->waitForElement('[data-automation-id="listing_item_' . $day->getId() . '"]');
    $i->waitForElement(['xpath' => '//*[contains(@class, "dataviews-filters__summary-chip-container")][contains(., "Sent on")][contains(., "is:")]']);
    $i->dontSeeElement(['xpath' => '//*[contains(@class, "dataviews-filters__summary-chip-container")][contains(., "or after") or contains(., "between")]']);
    $i->dontSeeElement('[data-automation-id="listing_item_' . $other->getId() . '"]');
  }

  public function sortByStatus(\AcceptanceTester $i) {
    $i->wantTo('Sort the standard emails listing by status');
    (new Newsletter())->withSubject('Sort draft')->withDraftStatus()->create();
    (new Newsletter())->withSubject('Sort sent')->withSentStatus()->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Sort draft', 10, '[data-automation-id="newsletters_listing_standard"]');
    // The sort is saved as the user's default, so the hash omits it; check the row order.
    $i->click(['xpath' => '//th//button[contains(., "Status")]']);
    $i->waitForText('Sort ascending');
    $i->click(['xpath' => '//*[@role="menuitemradio"][contains(normalize-space(.), "Sort ascending")]']);
    $this->waitForFirstRow($i, 'Sort draft');

    $i->click(['xpath' => '//th//button[contains(., "Status")]']);
    $i->waitForText('Sort descending');
    $i->click(['xpath' => '//*[@role="menuitemradio"][contains(normalize-space(.), "Sort descending")]']);
    $this->waitForFirstRow($i, 'Sort sent');
  }

  /**
   * @return array{xpath: string}
   */
  private function filterChip(string $name): array {
    return ['xpath' => '//*[contains(@class, "dataviews-filters__summary-chip-container")][contains(., "' . $name . '")]'];
  }

  private function waitForFirstRow(\AcceptanceTester $i, string $subject): void {
    $i->waitForJS('return (document.querySelector(".mailpoet-newsletters-dataviews tbody tr")?.textContent ?? "").includes(' . json_encode($subject) . ');', 10);
  }
}
