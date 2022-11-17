<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

class CustomUnsubscribePageCest {
  public function createCustomUnsubscribePage(\AcceptanceTester $i) {
    $i->wantTo('Create page with MP subscriber shortcode');
    $pageTitle = 'SorryToSeeYouGo';
    $pageText = 'Manage your subscription';
    $pageContent = "[mailpoet_manage text=\"$pageText\"]";
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title='$pageTitle'" , "--post_content='$pageContent'" ]);
    $i->login();
    $i->amOnPage('/wp-admin/edit.php?post_type=page');
    $i->waitForText($pageTitle);
    $i->clickItemRowActionByItemName($pageTitle, 'View');
    $i->waitForText($pageTitle);
    $i->waitForText($pageText);
  }
}
