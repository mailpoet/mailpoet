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
    $search_field_element = 'input.select2-search__field';
    $I->fillField($search_field_element, 'My First List');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->click('[data-automation-id="save_form"]');
    $I->click('[data-automation-id="mailpoet_form_go_back"]');

    $I->waitForElement('[data-automation-id="listing_item_1"]');
    $I->see('My new form');
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
