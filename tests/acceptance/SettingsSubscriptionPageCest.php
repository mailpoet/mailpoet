<?php

namespace MailPoet\Test\Acceptance;

class SettingsSubscriptionPageCest {
  function previvewDefaultSubscriptionPage(\AcceptanceTester $I){
    $I->wantTo('Preview default MailPoet page from MP Settings page');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/div[2]/table/tbody/tr[5]/td/p[1]/a']);
    $I->waitForText('Manage your subscription');
  }
  function createNewSubscriptionPage(\AcceptanceTester $I){
    $I->wantTo('Make a custom subscription page');
    $page_title='CustomSubscriptionPage';
    $page_content='[mailpoet_manage_subscription]';
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->cli('post create --allow-root --post_type=page --post_title=' . $page_title . '--post_content=' . $page_content);
    $I->click(['css'=>'#subscription_manage_page.mailpoet_page_selection']);
    $I->checkOption('select#subscription_manage_page', $page_title);
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/div[2]/table/tbody/tr[5]/td/p[1]/a']);
    $I->waitForText('Manage your subscription');

  }
}