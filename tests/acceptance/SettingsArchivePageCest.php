<?php

namespace MailPoet\Test\Acceptance;

class SettingsArchivePageCest {
  function createArchivePage(\AcceptanceTester $I) {
    $I->wantTo('Create page with MP archive shortcode');
    $page_title='NewsletterArchive';
    $page_content='[mailpoet_archive]';
    $I->cli('post create --allow-root --post_type=page --post_title=' . $page_title . '  --post_content=' . $page_content);
    $I->login();
    $I->amOnPage('/wp-admin/edit.php?post_type=page');
    $I->waitForText($page_title);
    $I->click($page_title);
    $I->seeInPageSource($page_content);
    //cli would only accept limited params
    $I->click('Publish');
    //see live page with shortcode output
    $I->click('View page');
    $I->waitForText($page_title);
  }
}