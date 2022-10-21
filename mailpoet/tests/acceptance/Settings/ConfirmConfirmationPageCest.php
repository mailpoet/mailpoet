<?php

 namespace MailPoet\Test\Acceptance;

class ConfirmConfirmationPageCest {
  public function confirmDefaultConfirmationPage(\AcceptanceTester $i) {
    $i->wantTo('Confirm link to default confirmation page works correctly');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->waitForText('MailPoet Page');
    $i->click('[data-automation-id="preview_page_link"]');
    $i->switchToNextTab();
    $siteTitle = get_bloginfo('name', 'raw');
    $i->see("You have subscribed to $siteTitle");
    $pageTitle = 'MailPoetConfirmationPage';
    $postContent = 'BobsYourUncle';
    $i->cli(['post', 'create', '--post_type=page', '--post_status=publish', "--post_title=$pageTitle", "--post_content=$postContent"]);
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->waitForText('MailPoet Page');
    $i->selectOption('[data-automation-id="page_selection"]', $pageTitle);
    $i->click('[data-automation-id="preview_page_link"]');
    $i->switchToNextTab();
    $i->waitForText($postContent);
  }
}
