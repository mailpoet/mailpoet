<?php

 namespace MailPoet\Test\Acceptance;

class ConfirmConfirmationPageCest {
  public function confirmDefaultConfirmationPage(\AcceptanceTester $I) {
    $I->wantTo('Confirm link to default confirmation page works correctly');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');
    $I->waitForText('MailPoet Page');
    $I->click('[data-automation-id="preview_page_link"]');
    $I->switchToNextTab();
    $I->waitForText('You have subscribed to: demo 1, demo 2');
    $pageTitle = 'MailPoetConfirmationPage';
    $postContent = 'BobsYourUncle';
    $I->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle", "--post_content=$postContent"]);
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');
    $I->waitForText('MailPoet Page');
    $I->selectOption('[data-automation-id="page_selection"]', $pageTitle);
    $I->click('[data-automation-id="preview_page_link"]');
    $I->switchToNextTab();
    $I->waitForText($postContent);
  }
}

