<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;

class SettingsArchivePageCest {
  function createArchivePageNoSentNewsletters(\AcceptanceTester $I) {
    $I->wantTo('Create page with MP archive shortcode, showing no sent newsletters');
    $segment_factory = new Segment();
    $segment = $segment_factory->withName('Empty Send')->create();
    $pageTitle = 'EmptyNewsletterArchive';
    $pageContent = "[mailpoet_archive segments=\"$segment->id\"]";
    $I->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle", "--post_content=$pageContent"]);
    $I->login();
    $I->amOnPage('/wp-admin/edit.php?post_type=page');
    $I->waitForText($pageTitle);
    $I->click($pageTitle);
    //see live page with shortcode output
    $I->click('View Page');
    $I->waitForText($pageTitle);
    $I->waitForText('Oops! There are no newsletters to display.');
  }
  function createArchivePageWithSentNewsletters(\AcceptanceTester $I) {
    $I->wantTo('Create page with MP archive shortcode, showing sent newsletters');
    $segment_factory = new Segment();
    $segment2 = $segment_factory->withName('SentNewsletters')->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject('SentNewsletter')->withSentStatus()->withSendingQueue()->withSegments([$segment2])->create();
    $pageTitle2 = 'SentNewsletterArchive';
    $pageContent2 = "[mailpoet_archive segments=\"$segment2->id\"]";
    $I->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle2", "--post_content=$pageContent2"]);
    $I->login();
    $I->amOnPage('/wp-admin/edit.php?post_type=page');
    $I->waitForText($pageTitle2);
    $I->click($pageTitle2);
    //see live page with shortcode output
    $I->click('View Page');
    $I->waitForText($pageTitle2);
    $I->waitForText('SentNewsletter');
  }
}
