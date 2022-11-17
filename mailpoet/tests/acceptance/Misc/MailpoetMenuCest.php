<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

class MailpoetMenuCest {
  public function sendEmail(\AcceptanceTester $i) {
    $i->wantTo('Use MailPoet menu in WordPress admin');

    $i->login();
    $i->amOnPage('/wp-admin');
    $i->click('MailPoet');

    $i->click('Emails');
    $i->waitForElement('.mailpoet-newsletter-type');
    $i->seeInCurrentUrl('?page=mailpoet-newsletters');

    $i->click('Forms');
    $i->waitForElement('[data-automation-id="create_new_form"]');
    $i->seeInCurrentUrl('?page=mailpoet-forms');

    $i->click('Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->seeInCurrentUrl('?page=mailpoet-subscribers');

    $i->click('Lists');
    $i->waitForElement('[data-automation-id="new-segment"]');
    $i->seeInCurrentUrl('?page=mailpoet-segments');

    $i->click('Settings');
    $i->waitForElement('[data-automation-id="basic_settings_tab"]');
    $i->seeInCurrentUrl('?page=mailpoet-settings');

    $i->click('Help');
    $i->waitForElement('.mailpoet-tab-content');
    $i->seeInCurrentUrl('?page=mailpoet-help');
  }
}
