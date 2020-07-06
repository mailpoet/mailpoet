<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;

class SettingsArchivePageCest {
  public function createArchivePageNoSentNewsletters(\AcceptanceTester $i) {
    $i->wantTo('Create page with MP archive shortcode, showing no sent newsletters');
    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName('Empty Send')->create();
    $pageTitle = 'EmptyNewsletterArchive';
    $pageContent = "[mailpoet_archive segments=\"$segment->id\"]";
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle", "--post_content=$pageContent"]);
    $i->login();
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle);
    $i->clickItemRowActionByItemName($pageTitle, 'View');
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
    $pageContent2 = "[mailpoet_archive segments=\"$segment2->id\"]";
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle2", "--post_content=$pageContent2"]);
    $i->login();
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle2);
    $i->clickItemRowActionByItemName($pageTitle2, 'View');
    $i->waitForText($pageTitle2);
    $i->waitForText('SentNewsletter');
  }

  public function createArchivePageWithVariousStatusNewsletters(\AcceptanceTester $i) {
    $i->wantTo('Create page with MP archive shortcode, showing only sent newsletters but having various in database');
    $segmentFactory = new Segment();
    $segment3 = $segmentFactory->withName('SentNewsletters')->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject('SentNewsletter')->withSentStatus()->withSendingQueue()->withSegments([$segment3])->create();
    $newsletterFactory->withSubject('DraftNewsletter')->withDraftStatus()->withScheduledQueue()->withSegments([$segment3])->create();
    $newsletterFactory->withSubject('ScheduledNewsletter')->withScheduledStatus()->withScheduledQueue()->withSegments([$segment3])->create();
    $pageTitle3 = 'SentNewsletterArchive';
    $pageContent3 = "[mailpoet_archive segments=\"$segment3->id\"]";
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle3", "--post_content=$pageContent3"]);
    $i->login();
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle3);
    $i->clickItemRowActionByItemName($pageTitle3, 'View');
    $i->waitForText($pageTitle3);
    $i->waitForText('SentNewsletter');
    $i->dontSee('DraftNewsletter');
    $i->dontSee('ScheduledNewsletter');
    $newsletterFactory->withSubject('SentNewsletter2')->withDraftStatus()->withSendingQueue()->withSegments([$segment3])->create();
    $newsletterFactory->withSubject('SentNewsletter3')->withDraftStatus()->withSendingQueue()->withSegments([$segment3])->create();
    $i->reloadPage();
    $i->waitForText('SentNewsletter');
    $i->waitForText('SentNewsletter2');
    $i->waitForText('SentNewsletter3');
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('SentNewsletter3');
    $i->clickItemRowActionByItemName('SentNewsletter3', 'Move to trash');
    $i->waitForText('1 email was moved to the trash.');
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle3);
    $i->clickItemRowActionByItemName($pageTitle3, 'View');
    $i->waitForText($pageTitle3);
    $i->waitForText('SentNewsletter');
    $i->waitForText('SentNewsletter2');
    $i->dontSee('SentNewsletter3');
  }
}
