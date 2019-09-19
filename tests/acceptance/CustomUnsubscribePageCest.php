<?php

namespace MailPoet\Test\Acceptance;

class CustomUnsubscribePageCest {
  function createCustomUnsubscribePage(\AcceptanceTester $I) {
    $I->wantTo('Create page with MP subscriber shortcode');
    $pageTitle = 'SorryToSeeYouGo';
    $pageText = 'Manage your subscription';
    $pageContent = "[mailpoet_manage text=\"$pageText\"]";
    $I->cli(['post', 'create', '--post_type=page', '--post_status=publish', '--post_title=' . $pageTitle, '--post_content=' . $pageContent]);
    $I->login();
    $I->amOnPage('/wp-admin/edit.php?post_type=page');
    $I->waitForText($pageTitle);
    $I->click($pageTitle);
    //see live page with shortcode output
    $I->click('View Page');
    $I->waitForText($pageTitle);
    $I->waitForText($pageText);
  }
}
