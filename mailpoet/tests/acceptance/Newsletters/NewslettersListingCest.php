<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Test\DataFactories\Newsletter;
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
}
