<?php

namespace MailPoet\Test\Acceptance;

class TemplatesPagesLoadCest {
  public function loadTemplatesPage(\AcceptanceTester $i) {
    $i->wantTo('Confirm template page loads and tabs can be clicked through');
    $i->login();
    $i->activateWooCommerce();
    //get to Template Selection page
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForText('Select a responsive template');
    //Standard email templates tab
    $i->waitForElement('[data-automation-id="select_template_8"]');
    $i->waitForElement('[data-automation-id="select_template_14"]');
    $i->waitForElement('[data-automation-id="select_template_23"]');
    //Post Notification templates tab
    $i->click('Post Notifications');
    $i->see('Post Notifications', ['css' => 'a.current']);
    $i->waitForElement('[data-automation-id="select_template_5"]');
    $i->waitForElement('[data-automation-id="select_template_8"]');
    //Welcome Emails templates tab
    $i->click('Welcome Emails');
    $i->see('Welcome Emails', ['css' => 'a.current']);
    $i->waitForElement('[data-automation-id="select_template_5"]');
    $i->waitForElement('[data-automation-id="select_template_9"]');
    //WooCommerce templates tab
    $i->click('WooCommerce Emails');
    $i->see('WooCommerce Emails', ['css' => 'a.current']);
    $i->waitForElement('[data-automation-id="select_template_5"]');
    $i->waitForElement('[data-automation-id="select_template_2"]');
  }
}
