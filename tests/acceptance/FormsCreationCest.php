<?php

namespace MailPoet\Test\Acceptance;

class FormsCreationCest {
  public function createForm(\AcceptanceTester $i) {
    $i->wantTo('Create a new Form');

    $i->login();
    $i->amOnMailpoetPage('Forms');

    $i->click('[data-automation-id="create_new_form"]');
    $i->waitForElement('[data-automation-id="mailpoet_form_name_input"]');
    $i->fillField('[data-automation-id="mailpoet_form_name_input"]', 'My new form');
    $i->selectOptionInSelect2('My First List');
    $i->click('[data-automation-id="save_form"]');
    $i->click('[data-automation-id="mailpoet_form_go_back"]');

    $i->waitForElement('[data-automation-id="listing_item_1"]');
    $i->see('My new form');
    $i->seeNoJSErrors();
  }

  public function createFormWithoutAList(\AcceptanceTester $i) {
    $i->wantTo('Create a new Form');

    $i->login();
    $i->amOnMailpoetPage('Forms');

    $i->click('[data-automation-id="create_new_form"]');
    $i->waitForElement('[data-automation-id="mailpoet_form_name_input"]');
    $i->click('[data-automation-id="save_form"]');

    $i->waitForText('Please select a list.');
  }

}
