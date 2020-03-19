<?php

namespace MailPoet\Test\Acceptance;

class MailpoetMenuCest {
  public function sendEmail(\AcceptanceTester $i) {
    $i->wantTo('Use MailPoet menu in WordPress admin');

    $i->login();
    $i->amOnPage('/wp-admin');
    $i->click('MailPoet');

    $i->click('Emails');
    $i->seeInCurrentUrl('?page=mailpoet-newsletters');

    $i->click('Forms');
    $i->seeInCurrentUrl('?page=mailpoet-forms');

    $i->click('Subscribers');
    $i->seeInCurrentUrl('?page=mailpoet-subscribers');

    $i->click('Lists');
    $i->seeInCurrentUrl('?page=mailpoet-segments');

    $i->click('Settings');
    // this is temporary while implementing the new settings
    $i->seeInCurrentUrl('?page=mailpoet-new-settings');

    $i->click('Help');
    $i->seeInCurrentUrl('?page=mailpoet-help');
  }
}
