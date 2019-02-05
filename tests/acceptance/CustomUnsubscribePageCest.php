<?php

namespace MailPoet\Test\Acceptance;

class CustomUnsubscribePageCest {
  function createCustomUnsubscribePage(\AcceptanceTester $I) {
    $I->wantTo('Create page with MP subscriber shortcode');
    $pageTitle='SorryToSeeYouGo';
    $pageContent='[mailpoet_manage\ text=\"Manage\ your\ subscription\"]';
    $I->cli('post create --allow-root --post_type=page --post_title=' . $pageTitle . '  --post_content=' . $pageContent);
    $I->login();
    $I->amOnPage('/wp-admin/edit.php?post_type=page');
    $I->waitForText($pageTitle);
    $I->click($pageTitle);
    $I->click('Publish');
    //see live page with shortcode output
    $I->click('View page');
    $I->waitForText($pageTitle);
    $I->waitForText('Manage your subscription');
  }
}
