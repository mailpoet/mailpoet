<?php

namespace MailPoet\Test\Acceptance;

class TemplatesPagesLoadCest {
  public function loadTemplatesPage(\AcceptanceTester $I) {
    $I->wantTo('Confirm template page loads and tabs can be clicked through');
    $I->login();
    $I->activateWooCommerce();
    //get to Template Selection page
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="create_standard"]');
    $I->waitForText('Select a responsive template');
    //Standard email templates tab
    $I->waitForElement('[data-automation-id="select_template_8"]');
    $I->waitForElement('[data-automation-id="select_template_14"]');
    $I->waitForElement('[data-automation-id="select_template_23"]');
    //Post Notification templates tab
    $I->click('Post Notifications');
    $I->see('Post Notifications', ['css' => 'a.current']);
    $I->waitForElement('[data-automation-id="select_template_5"]');
    $I->waitForElement('[data-automation-id="select_template_8"]');
    //Welcome Emails templates tab
    $I->click('Welcome Emails');
    $I->see('Welcome Emails', ['css' => 'a.current']);
    $I->waitForElement('[data-automation-id="select_template_5"]');
    $I->waitForElement('[data-automation-id="select_template_9"]');
    //WooCommerce templates tab
    $I->click('WooCommerce Emails');
    $I->see('WooCommerce Emails', ['css' => 'a.current']);
    $I->waitForElement('[data-automation-id="select_template_5"]');
    $I->waitForElement('[data-automation-id="select_template_2"]');
  }

}
