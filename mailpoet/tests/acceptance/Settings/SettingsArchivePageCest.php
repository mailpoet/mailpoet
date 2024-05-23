<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;

/**
 * @group frontend
 */
class SettingsArchivePageCest {
  public function createArchivePageNoSentNewsletters(\AcceptanceTester $i) {
    $i->wantTo('Create page with MP archive shortcode, showing no sent newsletters');

    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName('Empty Send')->create();
    $pageTitle = 'EmptyNewsletterArchive';
    $pageContent = "[mailpoet_archive segments=\"{$segment->getId()}\"]";
    $postUrl = $i->createPost($pageTitle, $pageContent);

    $i->login();
    $i->amOnUrl($postUrl);
    $i->waitForText($pageTitle);
    $i->waitForText('Oops! There are no newsletters to display.');
  }

  public function createArchivePageWithSentNewsletters(\AcceptanceTester $i) {
    $i->wantTo('Create page with MP archive shortcode, showing sent newsletters');

    $segmentFactory = new Segment();
    $segment2 = $segmentFactory->withName('SentNewsletters')->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject('SentNewsletter')->withSentStatus()->withSendingQueue()->withSegments([$segment2])->create();
    $pageTitle2 = 'SentNewsletterArchive';
    $pageContent2 = "[mailpoet_archive segments=\"{$segment2->getId()}\"]";
    $postUrl = $i->createPost($pageTitle2, $pageContent2);

    $i->login();
    $i->amOnUrl($postUrl);
    $i->waitForText($pageTitle2);
    $i->waitForText('SentNewsletter');
  }

  public function createArchivePageWithVariousStatusNewsletters(\AcceptanceTester $i) {
    $i->wantTo('Create page with MP archive shortcode, showing only sent newsletters but having various in database');

    $segmentFactory = new Segment();
    $segment3 = $segmentFactory->withName('SentNewsletters')->create();
    $segmentFactory = new Segment();
    $segment4 = $segmentFactory->withName('SentNewsletters2')->create();
    (new Newsletter())->withSubject('SentNewsletter')->withSentStatus()->withSendingQueue()->withSegments([$segment3])->create();
    (new Newsletter())->withSubject('DraftNewsletter')->withDraftStatus()->withScheduledQueue()->withSegments([$segment3])->create();
    (new Newsletter())->withSubject('ScheduledNewsletter')->withScheduledStatus()->withScheduledQueue()->withSegments([$segment3])->create();
    $pageTitle3 = 'SentNewsletterArchive';
    $pageContent3 = "[mailpoet_archive segments=\"{$segment3->getId()}\"]";
    $postUrl = $i->createPost($pageTitle3, $pageContent3);

    $i->login();
    $i->amOnUrl($postUrl);
    $i->waitForText($pageTitle3);
    $i->waitForText('SentNewsletter');
    $i->dontSee('DraftNewsletter');
    $i->dontSee('ScheduledNewsletter');

    // Create 2 additional sent newsletters
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject('SentNewsletter2')->withSentStatus()->withSendingQueue()->withSegments([$segment3])->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject('SentNewsletter3')->withSentStatus()->withSendingQueue()->withSegments([$segment3])->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject('SentNewsletter4')->withSentStatus()->withSendingQueue()->withSegments([$segment4])->create();

    $i->wantTo('See the newly created sent newsletters are present on the page');
    $i->reloadPage();
    $i->waitForText('SentNewsletter');
    $i->waitForText('SentNewsletter2');
    $i->waitForText('SentNewsletter3');

    $i->wantTo('Move both sent newsletters to the trash and delete only one');
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('SentNewsletter2');
    $i->clickItemRowActionByItemName('SentNewsletter2', 'Move to trash');
    $i->waitForNoticeAndClose('1 email was moved to the trash.');
    $i->waitForListingItemsToLoad();
    $i->waitForText('SentNewsletter3');
    $i->clickItemRowActionByItemName('SentNewsletter3', 'Move to trash');
    $i->waitForNoticeAndClose('1 email was moved to the trash.');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForElement('[data-automation-id="empty_trash"]');
    $i->waitForListingItemsToLoad();
    $i->waitForText('SentNewsletter3');
    $i->clickItemRowActionByItemName('SentNewsletter3', 'Delete permanently');
    $i->waitForText('1 email was permanently deleted.');

    $i->amOnUrl($postUrl);
    $i->waitForText($pageTitle3);
    $i->waitForText('SentNewsletter');
    $i->dontSee('SentNewsletter2');
    $i->dontSee('SentNewsletter3');
    $i->dontSee('SentNewsletter4');

    $pageTitle4 = 'SentNewsletterArchive';
    $pageContent4 = "[mailpoet_archive segments=\"{$segment3->getId()},{$segment4->getId()}\"]";
    $postUrl2 = $i->createPost($pageTitle4, $pageContent4);

    $i->wantTo('Create another page containing archive shortcode with multiple lists');
    $i->amOnUrl($postUrl2);
    $i->waitForText($pageTitle4);
    $i->waitForText('SentNewsletter');
    $i->dontSee('SentNewsletter2');
    $i->dontSee('SentNewsletter3');
    $i->waitForText('SentNewsletter4');
  }
}
