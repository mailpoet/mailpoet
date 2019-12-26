<?php

namespace MailPoet\Test\Acceptance;

class MailpoetMenuCest {
  public function sendEmail(\AcceptanceTester $I) {
    $I->wantTo('Use MailPoet menu in WordPress admin');

    $I->login();
    $I->amOnPage('/wp-admin');
    $I->click('MailPoet');

    $I->click('Emails');
    $I->seeInCurrentUrl('?page=mailpoet-newsletters');

    $I->click('Forms');
    $I->seeInCurrentUrl('?page=mailpoet-forms');

    $I->click('Subscribers');
    $I->seeInCurrentUrl('?page=mailpoet-subscribers');

    $I->click('Lists');
    $I->seeInCurrentUrl('?page=mailpoet-segments');

    $I->click('Settings');
    $I->seeInCurrentUrl('?page=mailpoet-settings');

    $I->click('Help');
    $I->seeInCurrentUrl('?page=mailpoet-help');
  }
}
