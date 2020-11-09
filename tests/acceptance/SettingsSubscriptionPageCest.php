<?php

namespace MailPoet\Test\Acceptance;

class SettingsSubscriptionPageCest {
  public function previewDefaultSubscriptionPage(\AcceptanceTester $i) {
    $i->wantTo('Preview default MailPoet page from MP Settings page');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="preview_manage_subscription_page_link"]');
    $i->switchToNextTab();
    $i->waitForText('Manage your subscription');
  }

  public function createNewSubscriptionPage(\AcceptanceTester $i) {
    $i->wantTo('Make a custom subscription page');
    $pageTitle = 'CustomSubscriptionPage';
    $pageContent = '[mailpoet_manage_subscription]';
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->cli(['post', 'create', '--post_type=page', "--post_title=$pageTitle", "--post_content=$pageContent"]);
    $i->click(['css' => '[data-automation-id="subscription-manage-page-selection"]']);
    $i->checkOption('[data-automation-id="subscription-manage-page-selection"]', $pageTitle);
    //save settings and then verify the page
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->click('[data-automation-id="preview_manage_subscription_page_link"]');
    $i->switchToNextTab();
    $i->waitForText('Manage your subscription');
  }
}
