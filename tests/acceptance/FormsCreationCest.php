<?php

namespace MailPoet\Test\Acceptance;

class FormsCreationCest {
  function createForm(\AcceptanceTester $I) {
    $I->wantTo('Create a new Form');

    $I->login();
    $I->amOnMailpoetPage('Forms');

    $I->click('[data-automation-id="create_new_form"]');
    $I->waitForElement('[data-automation-id="mailpoet_form_name_input"]');
    $I->fillField('[data-automation-id="mailpoet_form_name_input"]', 'My new form');
    $I->selectOptionInSelect2('My First List');
    $I->click('[data-automation-id="save_form"]');
    $I->click('[data-automation-id="mailpoet_form_go_back"]');

    $I->waitForElement('[data-automation-id="listing_item_1"]');
    $I->see('My new form');
    $I->seeNoJSErrors();
  }

  function createFormWithoutAList(\AcceptanceTester $I) {
    $I->wantTo('Create a new Form');

    $I->login();
    $I->amOnMailpoetPage('Forms');

    $I->click('[data-automation-id="create_new_form"]');
    $I->waitForElement('[data-automation-id="mailpoet_form_name_input"]');
    $I->click('[data-automation-id="save_form"]');

    $I->waitForText('Please select a list.');
  }

}
