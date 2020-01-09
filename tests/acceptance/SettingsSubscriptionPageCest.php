<?php

namespace MailPoet\Test\Acceptance;

class SettingsSubscriptionPageCest {
  public function previewDefaultSubscriptionPage(\AcceptanceTester $i) {
    $i->wantTo('Preview default MailPoet page from MP Settings page');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="preview_manage_subscription_page_link"]');
    $i->waitForText('Manage your subscription');
  }
  public function createNewSubscriptionPage(\AcceptanceTester $i) {
    $i->wantTo('Make a custom subscription page');
    $pageTitle = 'CustomSubscriptionPage';
    $pageContent = '[mailpoet_manage_subscription]';
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->cli(['post', 'create', '--post_type=page', "--post_title=$page_title", "--post_content=$page_content"]);
    $i->click(['css' => '#subscription_manage_page.mailpoet_page_selection']);
    $i->checkOption('select#subscription_manage_page', $pageTitle);
    $i->click('[data-automation-id="preview_manage_subscription_page_link"]');
    $i->waitForText('Manage your subscription');

  }
}
