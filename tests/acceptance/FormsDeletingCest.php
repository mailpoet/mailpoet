<?php

namespace MailPoet\Test\Acceptance;



class FormsDeletingCest {

  function test(\AcceptanceTester $I) {
    $form_name = 'My new form for delete test';

    $I->wantTo('Move a form to trash');

    $I->login();
    $I->amOnMailpoetPage('Forms');

    // 1 - create a new form
    $I->click('[data-automation-id="create_new_form"]');
    $I->waitForElement('[data-automation-id="mailpoet_form_name_input"]');
    $I->fillField('[data-automation-id="mailpoet_form_name_input"]', $form_name);
    $search_field_element = 'input.select2-search__field';
    $I->fillField($search_field_element, 'My First List');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->click('[data-automation-id="save_form"]');
    $I->click('[data-automation-id="mailpoet_form_go_back"]');
    $I->waitForElement('[data-automation-id="listing_item_1"]');

    // 2 - Move form to trash
    $I->clickItemRowActionByItemName($form_name, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($form_name);

    // 3 - Restore the form
    $I->clickItemRowActionByItemName($form_name, 'Restore');
    $I->waitForText('form has been restored from the trash');
    $I->waitForText($form_name, 10);

    // 4 - Move to trash again
    $I->clickItemRowActionByItemName($form_name, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($form_name);

    // 5 - Delete permanently
    $I->clickItemRowActionByItemName($form_name, 'Delete Permanently');
    $I->waitForText('1 form was permanently deleted.');
    $I->waitForElementNotVisible($form_name);
  }


}
